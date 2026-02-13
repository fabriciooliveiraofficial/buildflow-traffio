<?php
/**
 * Budget API Controller
 */

namespace App\Controllers\Api;

use App\Core\Controller;

class BudgetController extends Controller
{
    /**
     * List budgets
     */
    public function index(): array
    {
        $params = $this->getQueryParams();
        $page = (int) ($params['page'] ?? 1);
        $perPage = (int) ($params['per_page'] ?? 15);
        $projectId = $params['project_id'] ?? null;
        $category = $params['category'] ?? null;

        $tenantId = $this->db->getTenantId();
        $conditions = ["b.tenant_id = ?"];
        $bindings = [$tenantId];

        if ($projectId) {
            $conditions[] = "b.project_id = ?";
            $bindings[] = $projectId;
        }

        if ($category) {
            $conditions[] = "b.category = ?";
            $bindings[] = $category;
        }

        $where = implode(' AND ', $conditions);
        $offset = ($page - 1) * $perPage;

        $countResult = $this->db->fetch(
            "SELECT COUNT(*) as total FROM budgets b WHERE {$where}",
            $bindings
        );
        $total = (int) $countResult['total'];

        $budgets = $this->db->fetchAll(
            "SELECT 
                b.*,
                p.name as project_name,
                p.code as project_code,
                (b.budgeted_amount - b.spent_amount) as remaining,
                CASE 
                    WHEN b.budgeted_amount > 0 THEN ROUND((b.spent_amount / b.budgeted_amount) * 100, 1)
                    ELSE 0 
                END as spent_percentage
             FROM budgets b
             LEFT JOIN projects p ON b.project_id = p.id
             WHERE {$where}
             ORDER BY p.name, b.category
             LIMIT {$perPage} OFFSET {$offset}",
            $bindings
        );

        return $this->paginate($budgets, $total, $page, $perPage);
    }

    /**
     * Create budget
     */
    public function store(): array
    {
        $data = $this->validate([
            'project_id' => 'required|numeric',
            'category' => 'required',
            'budgeted_amount' => 'required|numeric',
        ]);

        $input = $this->getJsonInput();

        // Check for duplicate category in project
        $existing = $this->db->fetch(
            "SELECT id FROM budgets WHERE project_id = ? AND category = ? AND tenant_id = ?",
            [$data['project_id'], $data['category'], $this->db->getTenantId()]
        );

        if ($existing) {
            $this->error('Budget category already exists for this project', 422);
        }

        $budgetId = $this->db->insert('budgets', [
            'project_id' => $data['project_id'],
            'category' => $data['category'],
            'description' => $input['description'] ?? null,
            'budgeted_amount' => $data['budgeted_amount'],
            'spent_amount' => 0,
        ]);

        $budget = $this->db->fetch("SELECT * FROM budgets WHERE id = ?", [$budgetId]);

        return $this->success($budget, 'Budget created', 201);
    }

    /**
     * Get single budget
     */
    public function show(string $id): array
    {
        $budget = $this->db->fetch(
            "SELECT 
                b.*,
                p.name as project_name,
                p.code as project_code
             FROM budgets b
             LEFT JOIN projects p ON b.project_id = p.id
             WHERE b.id = ? AND b.tenant_id = ?",
            [$id, $this->db->getTenantId()]
        );

        if (!$budget) {
            $this->error('Budget not found', 404);
        }

        // Get related expenses
        $expenses = $this->db->fetchAll(
            "SELECT * FROM expenses 
             WHERE project_id = ? AND category = ? AND tenant_id = ?
             ORDER BY expense_date DESC",
            [$budget['project_id'], $budget['category'], $this->db->getTenantId()]
        );

        $budget['expenses'] = $expenses;

        return $this->success($budget);
    }

    /**
     * Update budget
     */
    public function update(string $id): array
    {
        $budget = $this->db->fetch(
            "SELECT * FROM budgets WHERE id = ? AND tenant_id = ?",
            [$id, $this->db->getTenantId()]
        );

        if (!$budget) {
            $this->error('Budget not found', 404);
        }

        $input = $this->getJsonInput();
        $allowedFields = ['category', 'description', 'budgeted_amount', 'notes'];

        $updateData = [];
        foreach ($allowedFields as $field) {
            if (isset($input[$field])) {
                $updateData[$field] = $input[$field];
            }
        }

        if (!empty($updateData)) {
            $updateData['updated_at'] = date('Y-m-d H:i:s');
            $this->db->update('budgets', $updateData, ['id' => $id]);
        }

        $updated = $this->db->fetch("SELECT * FROM budgets WHERE id = ?", [$id]);

        return $this->success($updated, 'Budget updated');
    }

    /**
     * Delete budget
     */
    public function destroy(string $id): array
    {
        $budget = $this->db->fetch(
            "SELECT * FROM budgets WHERE id = ? AND tenant_id = ?",
            [$id, $this->db->getTenantId()]
        );

        if (!$budget) {
            $this->error('Budget not found', 404);
        }

        if ($budget['spent_amount'] > 0) {
            $this->error('Cannot delete budget with recorded expenses', 422);
        }

        $this->db->delete('budgets', ['id' => $id]);

        return $this->success(null, 'Budget deleted');
    }

    /**
     * Get budget summary for a project
     */
    public function projectSummary(string $projectId): array
    {
        $project = $this->db->fetch(
            "SELECT * FROM projects WHERE id = ? AND tenant_id = ?",
            [$projectId, $this->db->getTenantId()]
        );

        if (!$project) {
            $this->error('Project not found', 404);
        }

        // Overall summary
        $summary = $this->db->fetch(
            "SELECT 
                COALESCE(SUM(budgeted_amount), 0) as total_budget,
                COALESCE(SUM(spent_amount), 0) as total_spent,
                COALESCE(SUM(budgeted_amount - spent_amount), 0) as total_remaining
             FROM budgets 
             WHERE project_id = ? AND tenant_id = ?",
            [$projectId, $this->db->getTenantId()]
        );

        // By category
        $byCategory = $this->db->fetchAll(
            "SELECT 
                category,
                budgeted_amount,
                spent_amount,
                (budgeted_amount - spent_amount) as remaining,
                CASE 
                    WHEN budgeted_amount > 0 THEN ROUND((spent_amount / budgeted_amount) * 100, 1)
                    ELSE 0 
                END as spent_percentage
             FROM budgets 
             WHERE project_id = ? AND tenant_id = ?
             ORDER BY budgeted_amount DESC",
            [$projectId, $this->db->getTenantId()]
        );

        // Over budget items
        $overBudget = array_filter($byCategory, function ($item) {
            return $item['spent_amount'] > $item['budgeted_amount'];
        });

        return $this->success([
            'project' => [
                'id' => $project['id'],
                'name' => $project['name'],
                'code' => $project['code'],
            ],
            'summary' => [
                'total_budget' => (float) $summary['total_budget'],
                'total_spent' => (float) $summary['total_spent'],
                'total_remaining' => (float) $summary['total_remaining'],
                'spent_percentage' => $summary['total_budget'] > 0
                    ? round(($summary['total_spent'] / $summary['total_budget']) * 100, 1)
                    : 0,
            ],
            'by_category' => $byCategory,
            'over_budget_count' => count($overBudget),
        ]);
    }

    /**
     * Get budget vs actual comparison across all projects
     */
    public function comparison(): array
    {
        $params = $this->getQueryParams();
        $startDate = $params['start_date'] ?? date('Y-01-01');
        $endDate = $params['end_date'] ?? date('Y-12-31');

        $tenantId = $this->db->getTenantId();

        // By project
        $byProject = $this->db->fetchAll(
            "SELECT 
                p.id,
                p.name,
                p.code,
                p.status,
                COALESCE(SUM(b.budgeted_amount), 0) as budget,
                COALESCE(SUM(b.spent_amount), 0) as spent,
                COALESCE(SUM(b.budgeted_amount - b.spent_amount), 0) as variance
             FROM projects p
             LEFT JOIN budgets b ON b.project_id = p.id
             WHERE p.tenant_id = ?
             AND p.created_at BETWEEN ? AND ?
             GROUP BY p.id, p.name, p.code, p.status
             ORDER BY budget DESC",
            [$tenantId, $startDate, $endDate]
        );

        // By category (across all projects)
        $byCategory = $this->db->fetchAll(
            "SELECT 
                b.category,
                COALESCE(SUM(b.budgeted_amount), 0) as budget,
                COALESCE(SUM(b.spent_amount), 0) as spent,
                COALESCE(SUM(b.budgeted_amount - b.spent_amount), 0) as variance
             FROM budgets b
             JOIN projects p ON b.project_id = p.id
             WHERE b.tenant_id = ?
             AND p.created_at BETWEEN ? AND ?
             GROUP BY b.category
             ORDER BY budget DESC",
            [$tenantId, $startDate, $endDate]
        );

        // Monthly trend
        $monthlyTrend = $this->db->fetchAll(
            "SELECT 
                DATE_FORMAT(e.expense_date, '%Y-%m') as month,
                COALESCE(SUM(e.amount), 0) as spent
             FROM expenses e
             WHERE e.tenant_id = ?
             AND e.expense_date BETWEEN ? AND ?
             GROUP BY DATE_FORMAT(e.expense_date, '%Y-%m')
             ORDER BY month",
            [$tenantId, $startDate, $endDate]
        );

        return $this->success([
            'by_project' => $byProject,
            'by_category' => $byCategory,
            'monthly_trend' => $monthlyTrend,
        ]);
    }
}
