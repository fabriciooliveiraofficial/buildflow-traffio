<?php
/**
 * Project API Controller
 */

namespace App\Controllers\Api;

use App\Core\Controller;

class ProjectController extends Controller
{
    /**
     * List all projects
     */
    public function index(): array
    {
        $params = $this->getQueryParams();
        $page = (int) ($params['page'] ?? 1);
        $perPage = (int) ($params['per_page'] ?? 15);
        $status = $params['status'] ?? null;
        $clientId = $params['client_id'] ?? null;
        $search = $params['search'] ?? null;

        $tenantId = $this->db->getTenantId();
        $conditions = ["p.tenant_id = ?"];
        $bindings = [$tenantId];

        if ($status) {
            $conditions[] = "p.status = ?";
            $bindings[] = $status;
        }

        if ($clientId) {
            $conditions[] = "p.client_id = ?";
            $bindings[] = $clientId;
        }

        if ($search) {
            $conditions[] = "(p.name LIKE ? OR p.code LIKE ?)";
            $bindings[] = "%{$search}%";
            $bindings[] = "%{$search}%";
        }

        $where = implode(' AND ', $conditions);
        $offset = ($page - 1) * $perPage;

        // Get total count
        $countResult = $this->db->fetch(
            "SELECT COUNT(*) as total FROM projects p WHERE {$where}",
            $bindings
        );
        $total = (int) $countResult['total'];

        // Get projects with client info and financials
        $projects = $this->db->fetchAll(
            "SELECT 
                p.*,
                c.name as client_name,
                u.first_name as manager_first_name,
                u.last_name as manager_last_name,
                (SELECT COALESCE(SUM(budgeted_amount), 0) FROM budgets WHERE project_id = p.id) as total_budget,
                (SELECT COALESCE(SUM(spent_amount), 0) FROM budgets WHERE project_id = p.id) as total_spent,
                (SELECT COALESCE(SUM(paid_amount), 0) FROM invoices WHERE project_id = p.id AND status != 'cancelled') as total_income,
                (SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE project_id = p.id AND status = 'approved') as total_expenses
             FROM projects p
             LEFT JOIN clients c ON p.client_id = c.id
             LEFT JOIN users u ON p.manager_id = u.id
             WHERE {$where}
             ORDER BY p.created_at DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $bindings
        );

        // Calculate profit and health for each project
        foreach ($projects as &$project) {
            $income = (float) $project['total_income'];
            $expenses = (float) $project['total_expenses'];
            $budget = (float) $project['total_budget'];
            $spent = (float) $project['total_spent'];

            $project['profit'] = $income - $expenses;
            $project['profit_margin'] = $income > 0 ? round(($project['profit'] / $income) * 100, 1) : 0;

            // Health status based on budget utilization
            $utilization = $budget > 0 ? ($spent / $budget) * 100 : 0;
            if ($utilization > 100) {
                $project['health'] = 'red';
            } elseif ($utilization > 80) {
                $project['health'] = 'yellow';
            } else {
                $project['health'] = 'green';
            }
        }

        return $this->paginate($projects, $total, $page, $perPage);
    }

    /**
     * Create a new project
     */
    public function store(): array
    {
        $data = $this->validate([
            'client_id' => 'required|numeric',
            'name' => 'required',
        ]);

        $input = $this->getJsonInput();

        $projectId = $this->db->insert('projects', [
            'client_id' => $data['client_id'],
            'name' => $data['name'],
            'description' => $input['description'] ?? null,
            'code' => $input['code'] ?? null,
            'address' => $input['address'] ?? null,
            'start_date' => $input['start_date'] ?? null,
            'end_date' => $input['end_date'] ?? null,
            'estimated_hours' => $input['estimated_hours'] ?? null,
            'contract_value' => $input['contract_value'] ?? 0,
            'manager_id' => $input['manager_id'] ?? null,
            'status' => $input['status'] ?? 'planning',
            'priority' => $input['priority'] ?? 'medium',
        ]);

        // Create default budget categories if provided
        if (isset($input['budget_categories']) && is_array($input['budget_categories'])) {
            foreach ($input['budget_categories'] as $category) {
                $this->db->insert('budgets', [
                    'tenant_id' => $this->db->getTenantId(),
                    'project_id' => $projectId,
                    'category' => $category['category'],
                    'description' => $category['description'] ?? null,
                    'budgeted_amount' => $category['amount'] ?? 0,
                ]);
            }
        }

        $project = $this->db->fetch("SELECT * FROM projects WHERE id = ?", [$projectId]);

        return $this->success($project, 'Project created', 201);
    }

    /**
     * Get single project
     */
    public function show(string $id): array
    {
        $project = $this->db->fetch(
            "SELECT 
                p.*,
                c.name as client_name,
                c.email as client_email,
                c.phone as client_phone,
                u.first_name as manager_first_name,
                u.last_name as manager_last_name
             FROM projects p
             LEFT JOIN clients c ON p.client_id = c.id
             LEFT JOIN users u ON p.manager_id = u.id
             WHERE p.id = ? AND p.tenant_id = ?",
            [$id, $this->db->getTenantId()]
        );

        if (!$project) {
            $this->error('Project not found', 404);
        }

        // Get budget summary
        $budget = $this->db->fetch(
            "SELECT 
                COALESCE(SUM(budgeted_amount), 0) as total_budget,
                COALESCE(SUM(spent_amount), 0) as total_spent
             FROM budgets WHERE project_id = ?",
            [$id]
        );

        // Get task summary
        $tasks = $this->db->fetch(
            "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
             FROM tasks WHERE project_id = ?",
            [$id]
        );

        // Get hours logged
        $hours = $this->db->fetch(
            "SELECT COALESCE(SUM(hours), 0) as total FROM time_logs WHERE project_id = ?",
            [$id]
        );

        // Get actual total expenses (directly from expenses table for accuracy)
        $expenses = $this->db->fetch(
            "SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE project_id = ? AND status = 'approved'",
            [$id]
        );

        $project['budget_summary'] = $budget;
        $project['task_summary'] = $tasks;
        $project['hours_logged'] = (float) $hours['total'];

        // Add convenience totals for frontend stat cards
        $project['total_budget'] = (float) ($budget['total_budget'] ?? 0);
        $project['total_spent'] = (float) ($expenses['total'] ?? 0); // Use actual expenses, not budget spent

        // Calculate progress based on project status
        $statusProgress = [
            'planning' => 10,
            'in_progress' => 50,
            'on_hold' => 50,
            'completed' => 100
        ];
        $project['progress'] = $statusProgress[$project['status']] ?? 0;

        return $this->success($project);
    }

    /**
     * Update project
     */
    public function update(string $id): array
    {
        $project = $this->db->fetch(
            "SELECT * FROM projects WHERE id = ? AND tenant_id = ?",
            [$id, $this->db->getTenantId()]
        );

        if (!$project) {
            $this->error('Project not found', 404);
        }

        $input = $this->getJsonInput();
        $updateData = [];

        $allowedFields = [
            'name',
            'description',
            'code',
            'address',
            'start_date',
            'end_date',
            'estimated_hours',
            'manager_id',
            'status',
            'priority',
            'progress',
            'contract_value'
        ];

        foreach ($allowedFields as $field) {
            if (isset($input[$field])) {
                $updateData[$field] = $input[$field];
            }
        }

        if (!empty($updateData)) {
            $updateData['updated_at'] = date('Y-m-d H:i:s');
            $this->db->update('projects', $updateData, ['id' => $id]);
        }

        $updated = $this->db->fetch("SELECT * FROM projects WHERE id = ?", [$id]);

        return $this->success($updated, 'Project updated');
    }

    /**
     * Delete project
     */
    public function destroy(string $id): array
    {
        $project = $this->db->fetch(
            "SELECT * FROM projects WHERE id = ? AND tenant_id = ?",
            [$id, $this->db->getTenantId()]
        );

        if (!$project) {
            $this->error('Project not found', 404);
        }

        $this->db->delete('projects', ['id' => $id]);

        return $this->success(null, 'Project deleted');
    }

    /**
     * Get project tasks
     */
    public function tasks(string $id): array
    {
        $tasks = $this->db->fetchAll(
            "SELECT 
                t.*,
                u.first_name as assigned_first_name,
                u.last_name as assigned_last_name
             FROM tasks t
             LEFT JOIN users u ON t.assigned_to = u.id
             WHERE t.project_id = ? AND t.tenant_id = ?
             ORDER BY t.sort_order, t.created_at",
            [$id, $this->db->getTenantId()]
        );

        return $this->success($tasks);
    }



    /**
     * Get project expenses
     */
    public function expenses(string $id): array
    {
        $expenses = $this->db->fetchAll(
            "SELECT e.*, u.first_name, u.last_name
             FROM expenses e
             LEFT JOIN users u ON e.user_id = u.id
             WHERE e.project_id = ? AND e.tenant_id = ?
             ORDER BY e.expense_date DESC",
            [$id, $this->db->getTenantId()]
        );

        return $this->success($expenses);
    }

    /**
     * Get project time logs
     */
    public function timeLogs(string $id): array
    {
        $logs = $this->db->fetchAll(
            "SELECT 
                tl.*,
                e.first_name,
                e.last_name,
                t.title as task_title
             FROM time_logs tl
             LEFT JOIN employees e ON tl.employee_id = e.id
             LEFT JOIN tasks t ON tl.task_id = t.id
             WHERE tl.project_id = ? AND tl.tenant_id = ?
             ORDER BY tl.log_date DESC",
            [$id, $this->db->getTenantId()]
        );

        return $this->success($logs);
    }

    /**
     * Get project documents
     */
    public function documents(string $id): array
    {
        $documents = $this->db->fetchAll(
            "SELECT d.*, u.first_name, u.last_name
             FROM documents d
             LEFT JOIN users u ON d.uploaded_by = u.id
             WHERE d.project_id = ? AND d.tenant_id = ?
             ORDER BY d.created_at DESC",
            [$id, $this->db->getTenantId()]
        );

        return $this->success($documents);
    }

    /**
     * Get project labor costs (Job Costing)
     */
    public function laborCost(string $id): array
    {
        $tenantId = $this->db->getTenantId();

        // Get labor budget for project
        $laborBudget = $this->db->fetch(
            "SELECT * FROM budgets WHERE project_id = ? AND tenant_id = ? AND category = 'labor'",
            [$id, $tenantId]
        );

        // Get all time logs for project with employee rates
        $laborByEmployee = $this->db->fetchAll(
            "SELECT 
                e.id as employee_id,
                e.first_name,
                e.last_name,
                e.payment_type,
                e.hourly_rate,
                e.daily_rate,
                e.salary,
                SUM(tl.hours) as total_hours,
                SUM(CASE WHEN tl.is_overtime THEN tl.hours ELSE 0 END) as overtime_hours,
                e.overtime_multiplier
             FROM time_logs tl
             JOIN employees e ON tl.employee_id = e.id
             WHERE tl.project_id = ? AND tl.tenant_id = ?
             GROUP BY e.id
             ORDER BY total_hours DESC",
            [$id, $tenantId]
        );

        // Calculate labor costs for each employee
        $totalLaborCost = 0;
        $totalHours = 0;
        $totalOTHours = 0;

        foreach ($laborByEmployee as &$emp) {
            $regularHours = $emp['total_hours'] - $emp['overtime_hours'];
            $overtimeHours = (float) $emp['overtime_hours'];
            $otMultiplier = (float) ($emp['overtime_multiplier'] ?? 1.5);
            $cost = 0;

            switch ($emp['payment_type']) {
                case 'hourly':
                    $rate = (float) ($emp['hourly_rate'] ?? 0);
                    $cost = ($regularHours * $rate) + ($overtimeHours * $rate * $otMultiplier);
                    break;
                case 'daily':
                    // Approximate from hours (8h day)
                    $days = $emp['total_hours'] / 8;
                    $cost = $days * (float) ($emp['daily_rate'] ?? 0);
                    break;
                case 'salary':
                    // Prorate based on hours (assume 160h/month standard)
                    $monthlyRate = (float) ($emp['salary'] ?? 0);
                    $cost = ($emp['total_hours'] / 160) * $monthlyRate;
                    break;
            }

            $emp['labor_cost'] = round($cost, 2);
            $emp['regular_hours'] = $regularHours;
            $emp['overtime_hours'] = $overtimeHours;
            $totalLaborCost += $cost;
            $totalHours += $emp['total_hours'];
            $totalOTHours += $overtimeHours;
        }

        // Budget comparison
        $budgetedAmount = $laborBudget ? (float) $laborBudget['budgeted_amount'] : 0;
        $variance = $budgetedAmount - $totalLaborCost;
        $utilizationPct = $budgetedAmount > 0 ? round(($totalLaborCost / $budgetedAmount) * 100, 1) : 0;

        return $this->success([
            'summary' => [
                'labor_budget' => $budgetedAmount,
                'actual_cost' => round($totalLaborCost, 2),
                'variance' => round($variance, 2),
                'utilization_percent' => $utilizationPct,
                'total_hours' => round($totalHours, 2),
                'overtime_hours' => round($totalOTHours, 2),
                'cost_per_hour' => $totalHours > 0 ? round($totalLaborCost / $totalHours, 2) : 0,
            ],
            'by_employee' => $laborByEmployee,
        ]);
    }

    /**
     * Get complete project financials
     */
    public function financials(string $id): array
    {
        $tenantId = $this->db->getTenantId();

        // Get project with contract value
        $project = $this->db->fetch(
            "SELECT p.*, c.name as client_name 
             FROM projects p
             LEFT JOIN clients c ON p.client_id = c.id
             WHERE p.id = ? AND p.tenant_id = ?",
            [$id, $tenantId]
        );

        if (!$project) {
            $this->error('Project not found', 404);
        }

        $contractValue = (float) ($project['contract_value'] ?? $project['estimated_value'] ?? 0);

        // === INCOME ===
        // Get all invoices linked to this project
        $invoiceData = $this->db->fetch(
            "SELECT 
                COALESCE(SUM(total_amount), 0) as total_invoiced,
                COALESCE(SUM(paid_amount), 0) as paid,
                COALESCE(SUM(total_amount - paid_amount), 0) as pending,
                COUNT(*) as invoice_count
             FROM invoices 
             WHERE project_id = ? AND tenant_id = ? AND status != 'cancelled'",
            [$id, $tenantId]
        );

        $income = [
            'total' => (float) $invoiceData['total_invoiced'],
            'paid' => (float) $invoiceData['paid'],
            'pending' => (float) $invoiceData['pending'],
            'invoice_count' => (int) $invoiceData['invoice_count'],
        ];

        // === EXPENSES ===
        // Get expenses by category
        $expensesByCategory = $this->db->fetchAll(
            "SELECT 
                category,
                COALESCE(SUM(amount), 0) as total,
                COUNT(*) as count
             FROM expenses 
             WHERE project_id = ? AND tenant_id = ? AND status = 'approved'
             GROUP BY category
             ORDER BY total DESC",
            [$id, $tenantId]
        );

        $totalExpenses = array_sum(array_column($expensesByCategory, 'total'));

        // === LABOR COST ===
        // 1. Calculate labor cost from time logs (employee hourly/daily rates)
        $laborData = $this->db->fetch(
            "SELECT 
                SUM(
                    CASE e.payment_type
                        WHEN 'hourly' THEN tl.hours * e.hourly_rate * 
                            CASE WHEN tl.is_overtime THEN COALESCE(e.overtime_multiplier, 1.5) ELSE 1 END
                        WHEN 'daily' THEN (tl.hours / 8) * e.daily_rate
                        WHEN 'salary' THEN (tl.hours / 160) * e.salary
                        ELSE 0
                    END
                ) as labor_cost,
                SUM(tl.hours) as total_hours
             FROM time_logs tl
             JOIN employees e ON tl.employee_id = e.id
             WHERE tl.project_id = ? AND tl.tenant_id = ?",
            [$id, $tenantId]
        );

        $laborCostFromTimeLogs = (float) ($laborData['labor_cost'] ?? 0);
        $totalHours = (float) ($laborData['total_hours'] ?? 0);

        // 2. Get labor cost from expenses with category 'labor'
        $laborExpenseData = $this->db->fetch(
            "SELECT COALESCE(SUM(amount), 0) as labor_expense_total
             FROM expenses 
             WHERE project_id = ? AND tenant_id = ? AND category = 'labor' AND status = 'approved'",
            [$id, $tenantId]
        );
        $laborCostFromExpenses = (float) ($laborExpenseData['labor_expense_total'] ?? 0);

        // Total labor cost = time logs + labor expenses
        $laborCost = $laborCostFromTimeLogs + $laborCostFromExpenses;

        // === BUDGET vs ACTUAL ===
        $budgets = $this->db->fetchAll(
            "SELECT category, budgeted_amount, spent_amount
             FROM budgets 
             WHERE project_id = ? AND tenant_id = ?",
            [$id, $tenantId]
        );

        $totalBudget = array_sum(array_column($budgets, 'budgeted_amount'));
        $totalSpent = array_sum(array_column($budgets, 'spent_amount'));

        // Add status to each budget category
        foreach ($budgets as &$b) {
            $pct = $b['budgeted_amount'] > 0
                ? ($b['spent_amount'] / $b['budgeted_amount']) * 100
                : 0;
            $b['percent_used'] = round($pct, 1);
            $b['status'] = $pct > 100 ? 'red' : ($pct > 80 ? 'yellow' : 'green');
            $b['variance'] = $b['budgeted_amount'] - $b['spent_amount'];
        }

        // === PROFITABILITY ===
        $totalCost = $totalExpenses + $laborCost;
        $grossProfit = $income['paid'] - $totalCost;
        $profitMargin = $income['paid'] > 0
            ? round(($grossProfit / $income['paid']) * 100, 1)
            : 0;

        // === BURN RATE ===
        $projectStart = $project['start_date'] ? strtotime($project['start_date']) : null;
        $daysElapsed = $projectStart ? max(1, floor((time() - $projectStart) / 86400)) : 1;
        $dailyBurnRate = $totalCost / $daysElapsed;

        $remainingBudget = $totalBudget - $totalSpent;
        $daysOfBudgetRemaining = $dailyBurnRate > 0
            ? round($remainingBudget / $dailyBurnRate, 0)
            : null;

        // === HEALTH STATUS ===
        $budgetUtilization = $totalBudget > 0 ? ($totalSpent / $totalBudget) * 100 : 0;
        $healthStatus = 'green';
        if ($budgetUtilization > 100) {
            $healthStatus = 'red';
        } elseif ($budgetUtilization > 80 || $profitMargin < 10) {
            $healthStatus = 'yellow';
        }

        return $this->success([
            'project' => [
                'id' => $project['id'],
                'name' => $project['name'],
                'code' => $project['code'],
                'client_name' => $project['client_name'],
                'status' => $project['status'],
                'start_date' => $project['start_date'],
                'end_date' => $project['end_date'],
            ],
            'contract_value' => $contractValue,
            'income' => $income,
            'expenses' => [
                'total' => round($totalExpenses, 2),
                'by_category' => $expensesByCategory,
            ],
            'labor' => [
                'cost' => round($laborCost, 2),
                'hours' => round($totalHours, 2),
                'cost_per_hour' => $totalHours > 0 ? round($laborCost / $totalHours, 2) : 0,
            ],
            'total_cost' => round($totalCost, 2),
            'profit' => [
                'gross' => round($grossProfit, 2),
                'margin' => $profitMargin,
            ],
            'budget' => [
                'total' => round($totalBudget, 2),
                'spent' => round($totalSpent, 2),
                'remaining' => round($remainingBudget, 2),
                'utilization_percent' => round($budgetUtilization, 1),
                'by_category' => $budgets,
            ],
            'burn_rate' => [
                'daily' => round($dailyBurnRate, 2),
                'days_elapsed' => $daysElapsed,
                'days_remaining' => $daysOfBudgetRemaining,
            ],
            'balance_due' => round($contractValue - $income['paid'], 2),
            'health_status' => $healthStatus,
        ]);
    }

    /**
     * Get project budget details
     */
    public function budget(string $id): array
    {
        $tenantId = $this->db->getTenantId();

        $budgets = $this->db->fetchAll(
            "SELECT * FROM budgets WHERE project_id = ? AND tenant_id = ?",
            [$id, $tenantId]
        );

        foreach ($budgets as &$b) {
            $b['percent_used'] = $b['budgeted_amount'] > 0
                ? round(($b['spent_amount'] / $b['budgeted_amount']) * 100, 1)
                : 0;
            $b['variance'] = $b['budgeted_amount'] - $b['spent_amount'];

            // Status color
            if ($b['percent_used'] > 100)
                $b['status'] = 'red';
            elseif ($b['percent_used'] > 80)
                $b['status'] = 'yellow';
            else
                $b['status'] = 'green';
        }

        return $this->success($budgets);
    }

    /**
     * Get project transaction ledger
     */
    public function ledger(string $id): array
    {
        $tenantId = $this->db->getTenantId();

        // Get query params for filtering
        $category = $_GET['category'] ?? null;
        $type = $_GET['type'] ?? null; // 'expense', 'income', 'all'
        $paymentMethod = $_GET['payment_method'] ?? null;
        $startDate = $_GET['start_date'] ?? null;
        $endDate = $_GET['end_date'] ?? null;
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = min(100, max(10, (int) ($_GET['per_page'] ?? 25)));
        $offset = ($page - 1) * $perPage;

        $transactions = [];

        // Get expenses if type is 'expense' or 'all'
        if (!$type || $type === 'all' || $type === 'expense') {
            $expenseWhere = "e.project_id = ? AND e.tenant_id = ?";
            $expenseParams = [$id, $tenantId];

            if ($category) {
                $expenseWhere .= " AND e.category = ?";
                $expenseParams[] = $category;
            }
            if ($paymentMethod) {
                $expenseWhere .= " AND e.payment_method = ?";
                $expenseParams[] = $paymentMethod;
            }
            if ($startDate) {
                $expenseWhere .= " AND e.expense_date >= ?";
                $expenseParams[] = $startDate;
            }
            if ($endDate) {
                $expenseWhere .= " AND e.expense_date <= ?";
                $expenseParams[] = $endDate;
            }

            $expenses = $this->db->fetchAll(
                "SELECT 
                    e.id,
                    'expense' as type,
                    e.expense_date as date,
                    e.description,
                    e.category,
                    e.vendor,
                    e.amount,
                    e.payment_method,
                    e.receipt_number as reference_number,
                    e.status,
                    e.receipt_path,
                    e.notes
                 FROM expenses e
                 WHERE {$expenseWhere}
                 ORDER BY e.expense_date DESC",
                $expenseParams
            );

            foreach ($expenses as $exp) {
                $exp['amount'] = (float) $exp['amount'];
                $exp['amount_display'] = -$exp['amount']; // Negative for expenses
                $transactions[] = $exp;
            }
        }

        // Get income (payments) if type is 'income' or 'all'
        if (!$type || $type === 'all' || $type === 'income') {
            $incomeWhere = "i.project_id = ? AND i.tenant_id = ?";
            $incomeParams = [$id, $tenantId];

            if ($paymentMethod) {
                $incomeWhere .= " AND p.payment_method = ?";
                $incomeParams[] = $paymentMethod;
            }
            if ($startDate) {
                $incomeWhere .= " AND p.payment_date >= ?";
                $incomeParams[] = $startDate;
            }
            if ($endDate) {
                $incomeWhere .= " AND p.payment_date <= ?";
                $incomeParams[] = $endDate;
            }

            $payments = $this->db->fetchAll(
                "SELECT 
                    p.id,
                    p.invoice_id,
                    'income' as type,
                    p.payment_date as date,
                    CONCAT('Payment for Invoice #', i.invoice_number) as description,
                    'Payment' as category,
                    c.name as vendor,
                    p.amount,
                    p.payment_method,
                    p.reference_number,
                    p.status,
                    NULL as receipt_path,
                    p.notes
                 FROM payments p
                 JOIN invoices i ON p.invoice_id = i.id
                 LEFT JOIN clients c ON i.client_id = c.id
                 WHERE {$incomeWhere} AND p.status = 'completed'
                 ORDER BY p.payment_date DESC",
                $incomeParams
            );

            foreach ($payments as $pay) {
                $pay['amount'] = (float) $pay['amount'];
                $pay['amount_display'] = $pay['amount']; // Positive for income
                $transactions[] = $pay;
            }
        }

        // Sort all transactions by date ASCENDING (oldest first) for correct running balance
        usort($transactions, function ($a, $b) {
            $dateCompare = strtotime($a['date']) - strtotime($b['date']);
            // If same date, sort by id to maintain consistent order
            if ($dateCompare === 0) {
                return ($a['id'] ?? 0) - ($b['id'] ?? 0);
            }
            return $dateCompare;
        });

        // Calculate running balance chronologically (oldest to newest)
        $runningBalance = 0;
        foreach ($transactions as &$t) {
            $runningBalance += $t['amount_display'];
            $t['running_balance'] = round($runningBalance, 2);
        }
        unset($t); // Break reference

        // Now reverse to show newest first for display
        $transactions = array_reverse($transactions);

        // Paginate
        $total = count($transactions);
        $transactions = array_slice($transactions, $offset, $perPage);

        // Summary
        $totalIncome = array_sum(array_map(fn($t) => $t['type'] === 'income' ? $t['amount'] : 0, $transactions));
        $totalExpenses = array_sum(array_map(fn($t) => $t['type'] === 'expense' ? $t['amount'] : 0, $transactions));

        return $this->success([
            'transactions' => $transactions,
            'summary' => [
                'total_income' => round($totalIncome, 2),
                'total_expenses' => round($totalExpenses, 2),
                'net' => round($totalIncome - $totalExpenses, 2),
            ],
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => ceil($total / $perPage),
            ],
        ]);
    }
}
