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
        $projectId = $params['project_id'] ?? null;
        $search = $params['search'] ?? null;
        $category = $params['category'] ?? null;
        $tenantId = $this->db->getTenantId();

        // Base conditions for reuse
        $incomeWhere = ["tenant_id = ?", "paid_at BETWEEN ? AND ?"];
        $incomeBindings = [$tenantId, $startDate, $endDate];
        
        $expenseWhere = ["tenant_id = ?", "expense_date BETWEEN ? AND ?", "status = 'approved'"];
        $expenseBindings = [$tenantId, $startDate, $endDate];

        $payrollWhere = ["pr.tenant_id = ?", "pp.period_end BETWEEN ? AND ?"];
        $payrollBindings = [$tenantId, $startDate, $endDate];

        // Apply filters
        if ($projectId) {
            $incomeWhere[] = "project_id = ?";
            $incomeBindings[] = $projectId;
            
            $expenseWhere[] = "project_id = ?";
            $expenseBindings[] = $projectId;
            
            // Payroll filtering by project is usually via time logs, here we simplify or skip
        }

        if ($search) {
            $incomeWhere[] = "(invoice_number LIKE ? OR notes LIKE ?)";
            $incomeBindings[] = "%$search%";
            $incomeBindings[] = "%$search%";
            
            $expenseWhere[] = "(description LIKE ? OR vendor LIKE ?)";
            $expenseBindings[] = "%$search%";
            $expenseBindings[] = "%$search%";
        }

        $incomeWhereStr = implode(" AND ", $incomeWhere);
        $expenseWhereStr = implode(" AND ", $expenseWhere);
        $payrollWhereStr = implode(" AND ", $payrollWhere);

        // 1. Cash In: Total paid invoices in period
        $cashIn = $this->db->fetch(
            "SELECT COALESCE(SUM(paid_amount), 0) as total FROM invoices 
             WHERE {$incomeWhereStr}",
            $incomeBindings
        );

        // 2. Cash Out: Total approved expenses in period
        $cashOutExpenses = $this->db->fetch(
            "SELECT COALESCE(SUM(amount), 0) as total FROM expenses 
             WHERE {$expenseWhereStr}",
            $expenseBindings
        );

        // 3. Cash Out: Total payroll paid in period
        // (Note: Payroll doesn't always have a direct project_id link in summary unless filtered by project_id)
        $cashOutPayroll = ['total' => 0];
        if (!$projectId) { // Simplify: only show payroll in global summary or implement deep logic
            $cashOutPayroll = $this->db->fetch(
                "SELECT COALESCE(SUM(pr.net_pay), 0) as total
                 FROM payroll_records pr
                 JOIN payroll_periods pp ON pr.payroll_period_id = pp.id
                 WHERE {$payrollWhereStr}",
                $payrollBindings
            );
        }

        $totalIn = (float) $cashIn['total'];
        $totalOut = (float) $cashOutExpenses['total'] + (float) $cashOutPayroll['total'];
        $netFlow = $totalIn - $totalOut;

        // 4. Monthly Trend (Last 6 months)
        $trendStart = date('Y-m-01', strtotime('-5 months'));
        $trendEnd = date('Y-m-t');

        // Note: Charts usually stay global or respond to project filter only
        $monthlyTrend = $this->db->fetchAll(
            "SELECT month, SUM(income) as income, SUM(expense) as expense FROM (
                SELECT DATE_FORMAT(paid_at, '%Y-%m') as month, SUM(paid_amount) as income, 0 as expense
                FROM invoices WHERE tenant_id = ? AND paid_at BETWEEN ? AND ? " . ($projectId ? "AND project_id = ?" : "") . " GROUP BY month
                UNION ALL
                SELECT DATE_FORMAT(expense_date, '%Y-%m') as month, 0 as income, SUM(amount) as expense
                FROM expenses WHERE tenant_id = ? AND expense_date BETWEEN ? AND ? AND status = 'approved' " . ($projectId ? "AND project_id = ?" : "") . " GROUP BY month
                UNION ALL
                SELECT DATE_FORMAT(pp.period_end, '%Y-%m') as month, 0 as income, SUM(pr.net_pay) as expense
                FROM payroll_records pr JOIN payroll_periods pp ON pr.payroll_period_id = pp.id 
                WHERE pr.tenant_id = ? AND pp.period_end BETWEEN ? AND ? GROUP BY month
            ) combined GROUP BY month ORDER BY month ASC",
            $projectId 
                ? [$tenantId, $trendStart, $trendEnd, $projectId, $tenantId, $trendStart, $trendEnd, $projectId, $tenantId, $trendStart, $trendEnd]
                : [$tenantId, $trendStart, $trendEnd, $tenantId, $trendStart, $trendEnd, $tenantId, $trendStart, $trendEnd]
        );

        // 5. Expense Distribution (by category)
        $categories = $this->db->fetchAll(
            "SELECT category, SUM(amount) as total FROM expenses 
             WHERE {$expenseWhereStr}
             GROUP BY category ORDER BY total DESC",
            $expenseBindings
        );

        // Add payroll as a virtual category for distribution
        if ((float)$cashOutPayroll['total'] > 0) {
            $categories[] = [
                'category' => 'payroll',
                'total' => (float)$cashOutPayroll['total']
            ];
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
     * Create quick entry (Income/Expense)
     */
    public function store(): array
    {
        $tenantId = $this->db->getTenantId();
        $input = $this->getJsonInput();
        $user = $this->getUser();

        $data = $this->validate([
            'type' => 'required|in:income,expense',
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'description' => 'required|string',
            'category' => 'required|string',
        ]);

        try {
            if ($data['type'] === 'expense') {
                $expenseId = $this->db->insert('expenses', [
                    'tenant_id' => $tenantId,
                    'user_id' => $user['id'],
                    'project_id' => $input['project_id'] ?? null,
                    'category' => $data['category'],
                    'description' => $data['description'],
                    'amount' => $data['amount'],
                    'expense_date' => $data['date'],
                    'payment_method' => $input['payment_method'] ?? 'cash',
                    'status' => 'approved', // Auto-approve quick entries from Cash Flow
                    'vendor' => $input['person'] ?? null
                ]);
                return $this->success(['id' => $expenseId, 'type' => 'expense'], 'Saída registrada com sucesso');
            } else {
                // For income, we create a simplified invoice + payment
                $invoiceNumber = 'QUICK-' . date('Ymd-His');
                $invoiceId = $this->db->insert('invoices', [
                    'tenant_id' => $tenantId,
                    'project_id' => $input['project_id'] ?? null,
                    'invoice_number' => $invoiceNumber,
                    'issue_date' => $data['date'],
                    'due_date' => $data['date'],
                    'total_amount' => $data['amount'],
                    'paid_amount' => $data['amount'],
                    'status' => 'paid',
                    'notes' => $data['description'],
                    'paid_at' => $data['date'] . ' ' . date('H:i:s')
                ]);

                $paymentId = $this->db->insert('payments', [
                    'tenant_id' => $tenantId,
                    'invoice_id' => $invoiceId,
                    'amount' => $data['amount'],
                    'payment_date' => $data['date'],
                    'payment_method' => $input['payment_method'] ?? 'cash',
                    'status' => 'completed',
                    'notes' => $data['description']
                ]);

                return $this->success(['id' => $paymentId, 'type' => 'income'], 'Entrada registrada com sucesso');
            }
        } catch (\Exception $e) {
            $this->error('Falha ao registrar lançamento: ' . $e->getMessage(), 500);
        }
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
