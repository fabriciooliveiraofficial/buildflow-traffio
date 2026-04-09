<?php
/**
 * Reports API Controller
 */

namespace App\Controllers\Api;

use App\Core\Controller;

class ReportsController extends Controller
{
    /**
     * Financial overview report
     */
    public function financial(): array
    {
        $params = $this->getQueryParams();
        $startDate = $params['start_date'] ?? date('Y-01-01');
        $endDate = $params['end_date'] ?? date('Y-12-31');

        $tenantId = $this->db->getTenantId();

        // Revenue (paid invoices)
        $revenue = $this->db->fetch(
            "SELECT COALESCE(SUM(paid_amount), 0) as total
             FROM invoices 
             WHERE tenant_id = ? AND paid_at BETWEEN ? AND ?",
            [$tenantId, $startDate, $endDate]
        );

        // Expenses
        $expenses = $this->db->fetch(
            "SELECT COALESCE(SUM(amount), 0) as total
             FROM expenses 
             WHERE tenant_id = ? AND expense_date BETWEEN ? AND ? AND status = 'approved'",
            [$tenantId, $startDate, $endDate]
        );

        // Payroll
        $payroll = $this->db->fetch(
            "SELECT COALESCE(SUM(pr.net_pay), 0) as total
             FROM payroll_records pr
             JOIN payroll_periods pp ON pr.payroll_period_id = pp.id
             WHERE pr.tenant_id = ? AND pp.period_end BETWEEN ? AND ?",
            [$tenantId, $startDate, $endDate]
        );

        // Outstanding invoices
        $outstanding = $this->db->fetch(
            "SELECT COALESCE(SUM(total_amount - paid_amount), 0) as total
             FROM invoices 
             WHERE tenant_id = ? AND status NOT IN ('paid', 'cancelled')",
            [$tenantId]
        );

        // Monthly breakdown
        $monthlyRevenue = $this->db->fetchAll(
            "SELECT 
                DATE_FORMAT(paid_at, '%Y-%m') as month,
                SUM(paid_amount) as revenue
             FROM invoices 
             WHERE tenant_id = ? AND paid_at BETWEEN ? AND ?
             GROUP BY DATE_FORMAT(paid_at, '%Y-%m')
             ORDER BY month",
            [$tenantId, $startDate, $endDate]
        );

        $monthlyExpenses = $this->db->fetchAll(
            "SELECT 
                DATE_FORMAT(expense_date, '%Y-%m') as month,
                SUM(amount) as expenses
             FROM expenses 
             WHERE tenant_id = ? AND expense_date BETWEEN ? AND ? AND status = 'approved'
             GROUP BY DATE_FORMAT(expense_date, '%Y-%m')
             ORDER BY month",
            [$tenantId, $startDate, $endDate]
        );

        $totalRevenue = (float) $revenue['total'];
        $totalExpenses = (float) $expenses['total'];
        $totalPayroll = (float) $payroll['total'];
        $netProfit = $totalRevenue - $totalExpenses - $totalPayroll;

        return $this->success([
            'summary' => [
                'revenue' => $totalRevenue,
                'expenses' => $totalExpenses,
                'payroll' => $totalPayroll,
                'net_profit' => $netProfit,
                'outstanding' => (float) $outstanding['total'],
                'profit_margin' => $totalRevenue > 0 ? round(($netProfit / $totalRevenue) * 100, 1) : 0,
            ],
            'monthly_revenue' => $monthlyRevenue,
            'monthly_expenses' => $monthlyExpenses,
        ]);
    }

    /**
     * Project performance report
     */
    public function projects(): array
    {
        $params = $this->getQueryParams();
        $status = $params['status'] ?? null;

        $tenantId = $this->db->getTenantId();
        $conditions = ["p.tenant_id = ?"];
        $bindings = [$tenantId];

        if ($status) {
            $conditions[] = "p.status = ?";
            $bindings[] = $status;
        }

        $where = implode(' AND ', $conditions);

        $projects = $this->db->fetchAll(
            "SELECT 
                p.*,
                c.name as client_name,
                (SELECT COALESCE(SUM(budgeted_amount), 0) FROM budgets WHERE project_id = p.id) as total_budget,
                (SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE project_id = p.id AND status = 'approved') as actual_expenses,
                (SELECT COALESCE(SUM(
                    CASE e.payment_type
                        WHEN 'hourly' THEN tl.hours * e.hourly_rate * CASE WHEN tl.is_overtime THEN COALESCE(e.overtime_multiplier, 1.5) ELSE 1 END
                        WHEN 'daily' THEN (tl.hours / 8) * e.daily_rate
                        WHEN 'salary' THEN (tl.hours / 160) * e.salary
                        ELSE 0
                    END
                ), 0) FROM time_logs tl JOIN employees e ON tl.employee_id = e.id WHERE tl.project_id = p.id) as total_labor_cost,
                (SELECT COALESCE(SUM(hours), 0) FROM time_logs WHERE project_id = p.id) as hours_logged,
                (SELECT COUNT(*) FROM tasks WHERE project_id = p.id) as total_tasks,
                (SELECT COUNT(*) FROM tasks WHERE project_id = p.id AND status = 'completed') as completed_tasks
             FROM projects p
             LEFT JOIN clients c ON p.client_id = c.id
             WHERE {$where}
             ORDER BY p.created_at DESC",
            $bindings
        );

        // Calculate profitability and health
        foreach ($projects as &$project) {
            $project['total_spent'] = (float)$project['actual_expenses'] + (float)$project['total_labor_cost'];
            $project['budget_remaining'] = $project['total_budget'] - $project['total_spent'];
            $project['budget_utilization'] = $project['total_budget'] > 0
                ? round(($project['total_spent'] / $project['total_budget']) * 100, 1)
                : 0;
            $project['task_completion'] = $project['total_tasks'] > 0
                ? round(($project['completed_tasks'] / $project['total_tasks']) * 100, 1)
                : 0;
        }

        return $this->success($projects);
    }

    /**
     * Employee performance report
     */
    public function employees(): array
    {
        $params = $this->getQueryParams();
        $startDate = $params['start_date'] ?? date('Y-m-01');
        $endDate = $params['end_date'] ?? date('Y-m-t');

        $tenantId = $this->db->getTenantId();

        $employees = $this->db->fetchAll(
            "SELECT 
                e.id,
                e.employee_id,
                e.first_name,
                e.last_name,
                e.job_title,
                e.department,
                e.payment_type,
                COALESCE((
                    SELECT SUM(hours) FROM time_logs 
                    WHERE employee_id = e.id AND log_date BETWEEN ? AND ?
                ), 0) as hours_worked,
                COALESCE((
                    SELECT SUM(hours) FROM time_logs 
                    WHERE employee_id = e.id AND log_date BETWEEN ? AND ? AND billable = 1
                ), 0) as billable_hours,
                COALESCE((
                    SELECT COUNT(DISTINCT project_id) FROM time_logs 
                    WHERE employee_id = e.id AND log_date BETWEEN ? AND ?
                ), 0) as projects_worked,
                COALESCE((
                    SELECT SUM(net_pay) FROM payroll_records pr
                    JOIN payroll_periods pp ON pr.payroll_period_id = pp.id
                    WHERE pr.employee_id = e.id AND pp.period_end BETWEEN ? AND ?
                ), 0) as total_paid
             FROM employees e
             WHERE e.tenant_id = ? AND e.status = 'active'
             ORDER BY hours_worked DESC",
            [
                $startDate,
                $endDate,
                $startDate,
                $endDate,
                $startDate,
                $endDate,
                $startDate,
                $endDate,
                $tenantId
            ]
        );

        // Calculate utilization
        $workDays = $this->getWorkDays($startDate, $endDate);
        $expectedHours = $workDays * 8;

        foreach ($employees as &$emp) {
            $emp['utilization'] = $expectedHours > 0
                ? round(($emp['hours_worked'] / $expectedHours) * 100, 1)
                : 0;
            $emp['billable_percentage'] = $emp['hours_worked'] > 0
                ? round(($emp['billable_hours'] / $emp['hours_worked']) * 100, 1)
                : 0;
        }

        return $this->success([
            'period' => ['start' => $startDate, 'end' => $endDate],
            'expected_hours' => $expectedHours,
            'employees' => $employees,
        ]);
    }

    /**
     * Time tracking report
     */
    public function timeTracking(): array
    {
        $params = $this->getQueryParams();
        $startDate = $params['start_date'] ?? date('Y-m-01');
        $endDate = $params['end_date'] ?? date('Y-m-t');
        $groupBy = $params['group_by'] ?? 'project'; // project, employee, day

        $tenantId = $this->db->getTenantId();

        $baseQuery = "
            SELECT 
                %s,
                COALESCE(SUM(tl.hours), 0) as total_hours,
                COALESCE(SUM(CASE WHEN tl.billable = 1 THEN tl.hours ELSE 0 END), 0) as billable_hours,
                COALESCE(SUM(CASE WHEN tl.is_overtime = 1 THEN tl.hours ELSE 0 END), 0) as overtime_hours,
                COUNT(*) as entry_count
            FROM time_logs tl
            LEFT JOIN projects p ON tl.project_id = p.id
            LEFT JOIN employees e ON tl.employee_id = e.id
            WHERE tl.tenant_id = ? AND tl.log_date BETWEEN ? AND ?
            GROUP BY %s
            ORDER BY total_hours DESC
        ";

        switch ($groupBy) {
            case 'employee':
                $selectFields = "e.id as group_id, CONCAT(e.first_name, ' ', e.last_name) as group_name";
                $groupByField = "e.id, e.first_name, e.last_name";
                break;
            case 'day':
                $selectFields = "tl.log_date as group_id, tl.log_date as group_name";
                $groupByField = "tl.log_date";
                break;
            default: // project
                $selectFields = "p.id as group_id, p.name as group_name";
                $groupByField = "p.id, p.name";
        }

        $query = sprintf($baseQuery, $selectFields, $groupByField);
        $data = $this->db->fetchAll($query, [$tenantId, $startDate, $endDate]);

        // Summary
        $summary = $this->db->fetch(
            "SELECT 
                COALESCE(SUM(hours), 0) as total_hours,
                COALESCE(SUM(CASE WHEN billable = 1 THEN hours ELSE 0 END), 0) as billable_hours,
                COALESCE(SUM(CASE WHEN is_overtime = 1 THEN hours ELSE 0 END), 0) as overtime_hours
             FROM time_logs 
             WHERE tenant_id = ? AND log_date BETWEEN ? AND ?",
            [$tenantId, $startDate, $endDate]
        );

        return $this->success([
            'period' => ['start' => $startDate, 'end' => $endDate],
            'group_by' => $groupBy,
            'summary' => $summary,
            'data' => $data,
        ]);
    }

    /**
     * Export report data
     */
    public function export(): array
    {
        $params = $this->getQueryParams();
        $reportType = $params['type'] ?? 'financial';
        $format = $params['format'] ?? 'json'; // json, csv

        // Get report data based on type
        switch ($reportType) {
            case 'financial':
                $data = $this->financial()['data'];
                break;
            case 'projects':
                $data = $this->projects()['data'];
                break;
            case 'employees':
                $data = $this->employees()['data'];
                break;
            case 'time':
                $data = $this->timeTracking()['data'];
                break;
            case 'cash-flow':
                $cf = new CashFlowController();
                $transactions = $cf->transactions();
                $data = $transactions['data']['transactions'];
                break;
            default:
                $this->error('Invalid report type', 422);
        }

        if ($format === 'csv') {
            // Include original params in download URL
            $query = http_build_query($params);
            return $this->success([
                'download_url' => '/api/reports/download?' . $query,
                'format' => 'csv',
            ]);
        }

        return $this->success($data);
    }

    /**
     * Download report as CSV
     */
    public function download(): void
    {
        $params = $_GET;
        $reportType = $params['type'] ?? 'financial';
        
        $data = [];
        $filename = $reportType . '_report_' . date('Y-m-d') . '.csv';

        switch ($reportType) {
            case 'cash-flow':
                $cf = new CashFlowController();
                $res = $cf->transactions();
                $data = $res['data']['transactions'];
                $headers = ['Date', 'Type', 'Description', 'Category', 'Person/Vendor', 'Amount', 'Balance'];
                $rows = array_map(function($t) {
                    return [
                        $t['date'],
                        $t['type'],
                        $t['description'],
                        $t['category'],
                        $t['person'],
                        $t['amount'],
                        $t['running_balance']
                    ];
                }, $data);
                break;
            // Add other cases as needed
            default:
                $data = []; // Fallback
                $headers = [];
                $rows = [];
        }

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        fputcsv($output, $headers);
        foreach ($rows as $row) {
            fputcsv($output, $row);
        }
        fclose($output);
        exit;
    }

    /**
     * Calculate work days between two dates
     */
    private function getWorkDays(string $start, string $end): int
    {
        $startDate = new \DateTime($start);
        $endDate = new \DateTime($end);
        $workDays = 0;

        while ($startDate <= $endDate) {
            $dayOfWeek = $startDate->format('N');
            if ($dayOfWeek < 6) { // Mon-Fri
                $workDays++;
            }
            $startDate->modify('+1 day');
        }

        return $workDays;
    }
}
