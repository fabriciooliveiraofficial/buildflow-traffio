<?php
/**
 * Cash Flow API Controller
 * 
 * Provides unified access to company cash flow, including combined ledger 
 * of invoices (income) and expenses (outgoings).
 */

namespace App\Controllers\Api;

use App\Core\Controller;

class CashFlowController extends Controller
{
    /**
     * Get cash flow summary for dashboard
     */
    public function summary(): array
    {
        $params = $this->getQueryParams();
        $startDate = $params['start_date'] ?? date('Y-m-01');
        $endDate = $params['end_date'] ?? date('Y-m-t');
        $tenantId = $this->db->getTenantId();

        // 1. Cash In: Total paid invoices in period
        $cashIn = $this->db->fetch(
            "SELECT COALESCE(SUM(paid_amount), 0) as total FROM invoices 
             WHERE tenant_id = ? AND paid_at BETWEEN ? AND ?",
            [$tenantId, $startDate, $endDate]
        );

        // 2. Cash Out: Total approved expenses in period
        $cashOutExpenses = $this->db->fetch(
            "SELECT COALESCE(SUM(amount), 0) as total FROM expenses 
             WHERE tenant_id = ? AND expense_date BETWEEN ? AND ? AND status = 'approved'",
            [$tenantId, $startDate, $endDate]
        );

        // 3. Cash Out: Total payroll paid in period
        $cashOutPayroll = $this->db->fetch(
            "SELECT COALESCE(SUM(pr.net_pay), 0) as total
             FROM payroll_records pr
             JOIN payroll_periods pp ON pr.payroll_period_id = pp.id
             WHERE pr.tenant_id = ? AND pp.period_end BETWEEN ? AND ?",
            [$tenantId, $startDate, $endDate]
        );

        $totalIn = (float) $cashIn['total'];
        $totalOut = (float) $cashOutExpenses['total'] + (float) $cashOutPayroll['total'];
        $netFlow = $totalIn - $totalOut;

        // 4. Monthly Trend (Last 6 months)
        $trendStart = date('Y-m-01', strtotime('-5 months'));
        $trendEnd = date('Y-m-t');

        // Combined monthly trend using UNION for efficiency
        $monthlyTrend = $this->db->fetchAll(
            "SELECT month, SUM(income) as income, SUM(expense) as expense FROM (
                SELECT DATE_FORMAT(paid_at, '%Y-%m') as month, SUM(paid_amount) as income, 0 as expense
                FROM invoices WHERE tenant_id = ? AND paid_at BETWEEN ? AND ? GROUP BY month
                UNION ALL
                SELECT DATE_FORMAT(expense_date, '%Y-%m') as month, 0 as income, SUM(amount) as expense
                FROM expenses WHERE tenant_id = ? AND expense_date BETWEEN ? AND ? AND status = 'approved' GROUP BY month
                UNION ALL
                SELECT DATE_FORMAT(pp.period_end, '%Y-%m') as month, 0 as income, SUM(pr.net_pay) as expense
                FROM payroll_records pr JOIN payroll_periods pp ON pr.payroll_period_id = pp.id 
                WHERE pr.tenant_id = ? AND pp.period_end BETWEEN ? AND ? GROUP BY month
            ) combined GROUP BY month ORDER BY month ASC",
            [$tenantId, $trendStart, $trendEnd, $tenantId, $trendStart, $trendEnd, $tenantId, $trendStart, $trendEnd]
        );

        // 5. Expense Distribution (by category)
        $categories = $this->db->fetchAll(
            "SELECT category, SUM(amount) as total FROM expenses 
             WHERE tenant_id = ? AND expense_date BETWEEN ? AND ? AND status = 'approved'
             GROUP BY category ORDER BY total DESC",
            [$tenantId, $startDate, $endDate]
        );

        // Add payroll as a virtual category for distribution
        if ((float)$cashOutPayroll['total'] > 0) {
            $categories[] = [
                'category' => 'payroll',
                'total' => (float)$cashOutPayroll['total']
            ];
            // Sort again if needed
            usort($categories, fn($a, $b) => $b['total'] <=> $a['total']);
        }

        return $this->success([
            'period' => ['start' => $startDate, 'end' => $endDate],
            'stats' => [
                'cash_in' => $totalIn,
                'cash_out' => $totalOut,
                'net_flow' => $netFlow,
                'savings_rate' => $totalIn > 0 ? round(($netFlow / $totalIn) * 100, 1) : 0
            ],
            'charts' => [
                'trend' => $monthlyTrend,
                'categories' => $categories
            ]
        ]);
    }

    /**
     * Get unified transaction ledger
     */
    public function transactions(): array
    {
        $params = $this->getQueryParams();
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = min(5000, max(10, (int) ($_GET['per_page'] ?? 50))); // Ledger usually needs more context
        $offset = ($page - 1) * $perPage;

        $tenantId = $this->db->getTenantId();
        
        // Filters
        $category = $_GET['category'] ?? null;
        $projectId = $_GET['project_id'] ?? null;
        $startDate = $_GET['start_date'] ?? null;
        $endDate = $_GET['end_date'] ?? null;
        $search = $_GET['search'] ?? null;

        $expenseWhere = ["e.tenant_id = ?"];
        $incomeWhere = ["p.status = 'completed'", "i.tenant_id = ?"];
        $bindings = [$tenantId];

        if ($projectId) {
            $expenseWhere[] = "e.project_id = ?";
            $incomeWhere[] = "i.project_id = ?";
            $bindings[] = $projectId;
        }

        if ($startDate) {
            $expenseWhere[] = "e.expense_date >= ?";
            $incomeWhere[] = "p.payment_date >= ?";
            $bindings[] = $startDate;
        }

        if ($endDate) {
            $expenseWhere[] = "e.expense_date <= ?";
            $incomeWhere[] = "p.payment_date <= ?";
            $bindings[] = $endDate;
        }
        
        // Category filtering is tricky as income doesn't have a category field usually
        $expWhereStr = implode(" AND ", $expenseWhere);
        $incWhereStr = implode(" AND ", $incomeWhere);
        
        // Unified Transactions Query (Excludes Payroll for ledger detail unless requested, usually handled separately)
        $query = "
            SELECT * FROM (
                SELECT 
                    e.id, 'expense' as type, e.expense_date as date, e.description, e.category, 
                    e.vendor as person, e.amount, e.payment_method, e.status, pr.name as project_name, -e.amount as flow
                FROM expenses e
                LEFT JOIN projects pr ON e.project_id = pr.id
                WHERE {$expWhereStr}
                
                UNION ALL
                
                SELECT 
                    p.id, 'income' as type, p.payment_date as date, CONCAT('Payment for Invoice #', i.invoice_number) as description, 
                    'income' as category, c.name as person, p.amount, p.payment_method, p.status, pr.name as project_name, p.amount as flow
                FROM payments p
                JOIN invoices i ON p.invoice_id = i.id
                LEFT JOIN clients c ON i.client_id = c.id
                LEFT JOIN projects pr ON i.project_id = pr.id
                WHERE {$incWhereStr}
            ) combined
        ";

        // Apply secondary filters globally on combined set if needed
        $combinedWhere = [];
        $combinedBindings = array_merge($bindings, $bindings); // Doubled for UNION

        if ($category && $category !== 'all') {
            $combinedWhere[] = "category = ?";
            $combinedBindings[] = $category;
        }

        if ($search) {
            $combinedWhere[] = "(description LIKE ? OR person LIKE ?)";
            $combinedBindings[] = "%$search%";
            $combinedBindings[] = "%$search%";
        }

        if (!empty($combinedWhere)) {
            $query .= " WHERE " . implode(" AND ", $combinedWhere);
        }

        $query .= " ORDER BY date DESC, id DESC";

        // Get total for pagination
        $totalResults = $this->db->fetchAll($query, $combinedBindings);
        $total = count($totalResults);

        // Calculate running balance for the FULL set before slicing
        // Note: Running balance correctly needs chronological order
        $chronological = array_reverse($totalResults);
        $runningBalance = 0;
        foreach ($chronological as &$t) {
            $runningBalance += (float) $t['flow'];
            $t['running_balance'] = round($runningBalance, 2);
        }
        $totalResults = array_reverse($chronological);

        // Apply pagination
        $transactions = array_slice($totalResults, $offset, $perPage);

        return $this->success([
            'transactions' => $transactions,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => ceil($total / $perPage),
            ]
        ]);
    }
}
