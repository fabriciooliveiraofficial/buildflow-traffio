<?php
/**
 * Dashboard API Controller
 */

namespace App\Controllers\Api;

use App\Core\Controller;

class DashboardController extends Controller
{
    /**
     * Get dashboard statistics
     */
    public function stats(): array
    {
        $tenantId = $this->db->getTenantId();

        // Active projects count
        $projectStats = $this->db->fetch(
            "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'on_hold' THEN 1 ELSE 0 END) as on_hold
             FROM projects WHERE tenant_id = ?",
            [$tenantId]
        );

        // Financial summary
        $financials = $this->db->fetch(
            "SELECT 
                COALESCE(SUM(total_amount), 0) as total_invoiced,
                COALESCE(SUM(paid_amount), 0) as total_received,
                COALESCE(SUM(total_amount - paid_amount), 0) as outstanding
             FROM invoices 
             WHERE tenant_id = ? AND status != 'cancelled'",
            [$tenantId]
        );

        // Overdue invoices
        $overdueInvoices = $this->db->fetch(
            "SELECT COUNT(*) as count, COALESCE(SUM(total_amount - paid_amount), 0) as amount
             FROM invoices 
             WHERE tenant_id = ? 
             AND status NOT IN ('paid', 'cancelled') 
             AND due_date < CURDATE()",
            [$tenantId]
        );

        // This month's expenses
        $monthlyExpenses = $this->db->fetch(
            "SELECT COALESCE(SUM(amount), 0) as total
             FROM expenses 
             WHERE tenant_id = ? 
             AND MONTH(expense_date) = MONTH(CURDATE())
             AND YEAR(expense_date) = YEAR(CURDATE())",
            [$tenantId]
        );

        // Active employees
        $employeeCount = $this->db->fetch(
            "SELECT COUNT(*) as count FROM employees WHERE tenant_id = ? AND status = 'active'",
            [$tenantId]
        );

        // Hours logged this week
        $weeklyHours = $this->db->fetch(
            "SELECT COALESCE(SUM(hours), 0) as total
             FROM time_logs 
             WHERE tenant_id = ? 
             AND log_date >= DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY)",
            [$tenantId]
        );

        // Low stock items - wrapped in try-catch as table might not exist
        $lowStockCount = ['count' => 0];
        try {
            $lowStockCount = $this->db->fetch(
                "SELECT COUNT(*) as count 
                 FROM inventory_items 
                 WHERE tenant_id = ? AND quantity <= min_quantity AND status = 'active'",
                [$tenantId]
            ) ?? ['count' => 0];
        } catch (\Exception $e) {
            // Inventory table might not exist, skip silently
            $lowStockCount = ['count' => 0];
        }

        return $this->success([
            'projects' => [
                'total' => (int) $projectStats['total'],
                'active' => (int) $projectStats['active'],
                'completed' => (int) $projectStats['completed'],
                'on_hold' => (int) $projectStats['on_hold'],
            ],
            'financials' => [
                'total_invoiced' => (float) $financials['total_invoiced'],
                'total_received' => (float) $financials['total_received'],
                'outstanding' => (float) $financials['outstanding'],
            ],
            'overdue' => [
                'count' => (int) $overdueInvoices['count'],
                'amount' => (float) $overdueInvoices['amount'],
            ],
            'monthly_expenses' => (float) $monthlyExpenses['total'],
            'employees' => (int) $employeeCount['count'],
            'weekly_hours' => (float) $weeklyHours['total'],
            'low_stock_items' => (int) ($lowStockCount['count'] ?? 0),
        ]);
    }

    /**
     * Get chart data
     */
    public function charts(): array
    {
        $tenantId = $this->db->getTenantId();

        // Revenue by month (last 12 months)
        $revenueByMonth = $this->db->fetchAll(
            "SELECT 
                DATE_FORMAT(payment_date, '%Y-%m') as month,
                SUM(amount) as total
             FROM payments 
             WHERE tenant_id = ? 
             AND payment_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
             AND status = 'completed'
             GROUP BY DATE_FORMAT(payment_date, '%Y-%m')
             ORDER BY month",
            [$tenantId]
        );

        // Expenses by category
        $expensesByCategory = $this->db->fetchAll(
            "SELECT category, SUM(amount) as total
             FROM expenses 
             WHERE tenant_id = ? 
             AND expense_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
             GROUP BY category
             ORDER BY total DESC
             LIMIT 10",
            [$tenantId]
        );

        // Projects by status
        $projectsByStatus = $this->db->fetchAll(
            "SELECT status, COUNT(*) as count
             FROM projects 
             WHERE tenant_id = ?
             GROUP BY status",
            [$tenantId]
        );

        // Budget vs Actual (current projects)
        $budgetVsActual = $this->db->fetchAll(
            "SELECT 
                p.name as project_name,
                COALESCE(SUM(b.budgeted_amount), 0) as budgeted,
                COALESCE(SUM(b.spent_amount), 0) as spent
             FROM projects p
             LEFT JOIN budgets b ON b.project_id = p.id
             WHERE p.tenant_id = ? AND p.status IN ('planning', 'in_progress')
             GROUP BY p.id, p.name
             ORDER BY budgeted DESC
             LIMIT 10",
            [$tenantId]
        );

        return $this->success([
            'revenue_by_month' => $revenueByMonth,
            'expenses_by_category' => $expensesByCategory,
            'projects_by_status' => $projectsByStatus,
            'budget_vs_actual' => $budgetVsActual,
        ]);
    }

    /**
     * Get recent activities
     */
    public function activities(): array
    {
        $tenantId = $this->db->getTenantId();

        $activities = $this->db->fetchAll(
            "SELECT 
                a.*, 
                u.first_name, 
                u.last_name
             FROM activity_logs a
             LEFT JOIN users u ON a.user_id = u.id
             WHERE a.tenant_id = ?
             ORDER BY a.created_at DESC
             LIMIT 20",
            [$tenantId]
        );

        return $this->success($activities);
    }

    /**
     * Cash flow forecast (next 12 weeks)
     */
    public function cashFlowForecast(): array
    {
        $tenantId = $this->db->getTenantId();

        // Expected incoming (unpaid invoices by due date)
        $incoming = $this->db->fetchAll(
            "SELECT 
                YEARWEEK(due_date, 1) as week,
                DATE_FORMAT(MIN(due_date), '%b %d') as week_start,
                SUM(total_amount - paid_amount) as amount
             FROM invoices 
             WHERE tenant_id = ? 
             AND status NOT IN ('paid', 'cancelled')
             AND due_date >= CURDATE()
             AND due_date <= DATE_ADD(CURDATE(), INTERVAL 12 WEEK)
             GROUP BY YEARWEEK(due_date, 1)
             ORDER BY week",
            [$tenantId]
        );

        // Expected outgoing (payroll estimate based on active employees)
        $payrollEstimate = $this->db->fetch(
            "SELECT 
                SUM(CASE 
                    WHEN payment_type = 'hourly' THEN hourly_rate * 40 
                    WHEN payment_type = 'salary' THEN salary / 4
                    ELSE 0 
                END) as weekly_payroll
             FROM employees 
             WHERE tenant_id = ? AND status = 'active'",
            [$tenantId]
        );
        $weeklyPayroll = (float) ($payrollEstimate['weekly_payroll'] ?? 0);

        // Build 12-week forecast
        $forecast = [];
        for ($i = 0; $i < 12; $i++) {
            $weekStart = date('Y-m-d', strtotime("+{$i} weeks"));
            $weekNum = date('W', strtotime($weekStart));
            $year = date('Y', strtotime($weekStart));
            $weekKey = $year . $weekNum;

            $inAmount = 0;
            foreach ($incoming as $inc) {
                if (substr($inc['week'], -2) == $weekNum) {
                    $inAmount = (float) $inc['amount'];
                    break;
                }
            }

            $forecast[] = [
                'week' => date('M d', strtotime($weekStart)),
                'incoming' => $inAmount,
                'outgoing' => $weeklyPayroll,
                'net' => $inAmount - $weeklyPayroll,
            ];
        }

        // Current cash position (simplified - would need bank integration)
        $recentPayments = $this->db->fetch(
            "SELECT COALESCE(SUM(amount), 0) as total 
             FROM payments 
             WHERE tenant_id = ? AND status = 'completed' 
             AND payment_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)",
            [$tenantId]
        );

        return $this->success([
            'forecast' => $forecast,
            'weekly_payroll_estimate' => $weeklyPayroll,
            'recent_payments_30d' => (float) $recentPayments['total'],
        ]);
    }

    /**
     * Get alerts requiring attention
     */
    public function alerts(): array
    {
        try {
            $tenantId = $this->db->getTenantId();

            // Over-budget projects
            $overBudget = $this->db->fetchAll(
                "SELECT 
                    p.id, p.name, p.code,
                    COALESCE(SUM(b.budgeted_amount), 0) as budget,
                    COALESCE(SUM(b.spent_amount), 0) as spent
                 FROM projects p
                 LEFT JOIN budgets b ON b.project_id = p.id
                 WHERE p.tenant_id = ? AND p.status IN ('planning', 'in_progress')
                 GROUP BY p.id, p.name, p.code
                 HAVING COALESCE(SUM(b.spent_amount), 0) > COALESCE(SUM(b.budgeted_amount), 0) 
                    AND COALESCE(SUM(b.budgeted_amount), 0) > 0
                 ORDER BY (COALESCE(SUM(b.spent_amount), 0) - COALESCE(SUM(b.budgeted_amount), 0)) DESC
                 LIMIT 5",
                [$tenantId]
            );

            // Overdue invoices
            $overdueInvoices = $this->db->fetchAll(
                "SELECT 
                    i.id, i.invoice_number, 
                    c.name as client_name,
                    i.total_amount - i.paid_amount as amount_due,
                    DATEDIFF(CURDATE(), i.due_date) as days_overdue
                 FROM invoices i
                 LEFT JOIN clients c ON i.client_id = c.id
                 WHERE i.tenant_id = ? 
                 AND i.status NOT IN ('paid', 'cancelled')
                 AND i.due_date < CURDATE()
                 ORDER BY i.due_date
                 LIMIT 5",
                [$tenantId]
            );

            // Pending time log approvals
            $pendingTimeLogs = $this->db->fetch(
                "SELECT COUNT(*) as count, COALESCE(SUM(hours), 0) as hours
                 FROM time_logs 
                 WHERE tenant_id = ? AND approved = FALSE",
                [$tenantId]
            ) ?? ['count' => 0, 'hours' => 0];

            // Pending expense approvals
            $pendingExpenses = $this->db->fetch(
                "SELECT COUNT(*) as count, COALESCE(SUM(amount), 0) as amount
                 FROM expenses 
                 WHERE tenant_id = ? AND status = 'pending'",
                [$tenantId]
            ) ?? ['count' => 0, 'amount' => 0];

            // Low stock items - wrapped in try-catch as table might not exist
            $lowStock = [];
            try {
                $lowStock = $this->db->fetchAll(
                    "SELECT id, name, quantity, min_quantity
                     FROM inventory_items 
                     WHERE tenant_id = ? AND quantity <= min_quantity AND status = 'active'
                     LIMIT 5",
                    [$tenantId]
                );
            } catch (\Exception $e) {
                // Inventory table might not exist, skip silently
                $lowStock = [];
            }

            return $this->success([
                'over_budget_projects' => $overBudget,
                'overdue_invoices' => $overdueInvoices,
                'pending_time_logs' => [
                    'count' => (int) ($pendingTimeLogs['count'] ?? 0),
                    'hours' => (float) ($pendingTimeLogs['hours'] ?? 0),
                ],
                'pending_expenses' => [
                    'count' => (int) ($pendingExpenses['count'] ?? 0),
                    'amount' => (float) ($pendingExpenses['amount'] ?? 0),
                ],
                'low_stock_items' => $lowStock,
                'total_alerts' => count($overBudget) + count($overdueInvoices) +
                    (($pendingTimeLogs['count'] ?? 0) > 0 ? 1 : 0) +
                    (($pendingExpenses['count'] ?? 0) > 0 ? 1 : 0) +
                    count($lowStock),
            ]);
        } catch (\Exception $e) {
            error_log("Dashboard alerts error: " . $e->getMessage());
            $this->error('Failed to load alerts: ' . $e->getMessage(), 500);
            return []; // Unreachable but needed for type safety
        }
    }

    /**
     * Today's schedule - who's working where
     */
    public function todaySchedule(): array
    {
        $tenantId = $this->db->getTenantId();
        $today = date('Y-m-d');

        // Employees with time logs today
        $workingToday = $this->db->fetchAll(
            "SELECT 
                e.id, e.first_name, e.last_name, e.job_title,
                p.id as project_id, p.name as project_name, p.address as project_address,
                SUM(tl.hours) as hours_today,
                MAX(tl.created_at) as last_activity
             FROM time_logs tl
             JOIN employees e ON tl.employee_id = e.id
             LEFT JOIN projects p ON tl.project_id = p.id
             WHERE tl.tenant_id = ? AND tl.log_date = ?
             GROUP BY e.id, p.id
             ORDER BY e.first_name",
            [$tenantId, $today]
        );

        // Active timers (currently clocked in)
        $activeTimers = $this->db->fetchAll(
            "SELECT 
                e.id, e.first_name, e.last_name,
                p.name as project_name,
                tl.start_time,
                TIMESTAMPDIFF(MINUTE, tl.start_time, NOW()) as minutes_elapsed
             FROM time_logs tl
             JOIN employees e ON tl.employee_id = e.id
             LEFT JOIN projects p ON tl.project_id = p.id
             WHERE tl.tenant_id = ? 
             AND tl.log_date = ? 
             AND tl.start_time IS NOT NULL 
             AND tl.end_time IS NULL",
            [$tenantId, $today]
        );

        // Summary
        $uniqueEmployees = [];
        $uniqueProjects = [];
        foreach ($workingToday as $w) {
            $uniqueEmployees[$w['id']] = true;
            if ($w['project_id'])
                $uniqueProjects[$w['project_id']] = true;
        }

        return $this->success([
            'working_today' => $workingToday,
            'active_timers' => $activeTimers,
            'summary' => [
                'employees_working' => count($uniqueEmployees),
                'projects_active' => count($uniqueProjects),
                'currently_clocked_in' => count($activeTimers),
            ],
        ]);
    }

    /**
     * Project profitability summary
     */
    public function profitability(): array
    {
        $tenantId = $this->db->getTenantId();

        $projects = $this->db->fetchAll(
            "SELECT 
                p.id, p.name, p.code, p.status,
                c.name as client_name,
                COALESCE((SELECT SUM(total_amount) FROM invoices WHERE project_id = p.id AND status != 'cancelled'), 0) as revenue,
                COALESCE((SELECT SUM(amount) FROM expenses WHERE project_id = p.id AND status = 'approved'), 0) as expenses,
                COALESCE((SELECT SUM(hours) FROM time_logs WHERE project_id = p.id), 0) as total_hours
             FROM projects p
             LEFT JOIN clients c ON p.client_id = c.id
             WHERE p.tenant_id = ? AND p.status IN ('in_progress', 'completed')
             ORDER BY p.updated_at DESC
             LIMIT 10",
            [$tenantId]
        );

        // Calculate labor cost for each project
        foreach ($projects as &$proj) {
            $laborCost = $this->db->fetch(
                "SELECT 
                    SUM(
                        CASE e.payment_type
                            WHEN 'hourly' THEN tl.hours * e.hourly_rate
                            WHEN 'daily' THEN (tl.hours / 8) * e.daily_rate
                            WHEN 'salary' THEN (tl.hours / 160) * e.salary
                            ELSE 0
                        END
                    ) as cost
                 FROM time_logs tl
                 JOIN employees e ON tl.employee_id = e.id
                 WHERE tl.project_id = ?",
                [$proj['id']]
            );

            $proj['labor_cost'] = (float) ($laborCost['cost'] ?? 0);
            $proj['total_cost'] = $proj['expenses'] + $proj['labor_cost'];
            $proj['profit'] = $proj['revenue'] - $proj['total_cost'];
            $proj['margin'] = $proj['revenue'] > 0
                ? round(($proj['profit'] / $proj['revenue']) * 100, 1)
                : 0;
        }

        // Summary totals
        $totals = [
            'total_revenue' => array_sum(array_column($projects, 'revenue')),
            'total_cost' => array_sum(array_column($projects, 'total_cost')),
            'total_profit' => array_sum(array_column($projects, 'profit')),
        ];

        return $this->success([
            'projects' => $projects,
            'totals' => $totals,
        ]);
    }
}
