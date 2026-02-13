<?php
/**
 * Expense API Controller
 */

namespace App\Controllers\Api;

use App\Core\Controller;

class ExpenseController extends Controller
{
    /**
     * List expenses
     */
    public function index(): array
    {
        $params = $this->getQueryParams();
        $page = (int) ($params['page'] ?? 1);
        $perPage = (int) ($params['per_page'] ?? 15);
        $projectId = $params['project_id'] ?? null;
        $category = $params['category'] ?? null;
        $startDate = $params['start_date'] ?? null;
        $endDate = $params['end_date'] ?? null;
        $status = $params['status'] ?? null;

        $tenantId = $this->db->getTenantId();
        $conditions = ["e.tenant_id = ?"];
        $bindings = [$tenantId];

        if ($projectId) {
            $conditions[] = "e.project_id = ?";
            $bindings[] = $projectId;
        }

        if ($category) {
            $conditions[] = "e.category = ?";
            $bindings[] = $category;
        }

        if ($startDate) {
            $conditions[] = "e.expense_date >= ?";
            $bindings[] = $startDate;
        }

        if ($endDate) {
            $conditions[] = "e.expense_date <= ?";
            $bindings[] = $endDate;
        }

        if ($status) {
            $conditions[] = "e.status = ?";
            $bindings[] = $status;
        }

        $where = implode(' AND ', $conditions);
        $offset = ($page - 1) * $perPage;

        $countResult = $this->db->fetch(
            "SELECT COUNT(*) as total FROM expenses e WHERE {$where}",
            $bindings
        );
        $total = (int) $countResult['total'];

        $expenses = $this->db->fetchAll(
            "SELECT 
                e.*,
                p.name as project_name,
                p.code as project_code,
                u.first_name,
                u.last_name,
                CONCAT(emp.first_name, ' ', emp.last_name) as employee_name,
                emp.payment_type as employee_payment_type
             FROM expenses e
             LEFT JOIN projects p ON e.project_id = p.id
             LEFT JOIN users u ON e.user_id = u.id
             LEFT JOIN employees emp ON e.employee_id = emp.id
             WHERE {$where}
             ORDER BY e.expense_date DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $bindings
        );

        return $this->paginate($expenses, $total, $page, $perPage);
    }

    /**
     * Create expense
     */
    public function store(): array
    {
        try {
            $data = $this->validate([
                'category' => 'required',
                'amount' => 'required|numeric',
                'expense_date' => 'required',
            ]);

            $input = $this->getJsonInput();
            $user = $this->getUser();

            // Check if user is authenticated
            if (!$user || !isset($user['id'])) {
                $this->error('User not authenticated', 401);
            }

            $expenseData = [
                'project_id' => !empty($input['project_id']) ? $input['project_id'] : null,
                'user_id' => $user['id'],
                'employee_id' => !empty($input['employee_id']) ? $input['employee_id'] : null,
                'category' => $data['category'],
                'description' => !empty($input['description']) ? $input['description'] : null,
                'amount' => $data['amount'],
                'expense_date' => $data['expense_date'],
                'vendor' => !empty($input['vendor']) ? $input['vendor'] : null,
                'receipt_number' => !empty($input['receipt_number']) ? $input['receipt_number'] : null,
                'receipt_path' => !empty($input['receipt_path']) ? $input['receipt_path'] : null,
                'payment_method' => !empty($input['payment_method']) ? $input['payment_method'] : null,
                'is_billable' => $input['is_billable'] ?? false,
                'status' => $input['status'] ?? 'pending',
                'notes' => !empty($input['notes']) ? $input['notes'] : null,
            ];

            // Handle optional journal entry (only if user enabled it)
            if (!empty($input['journal_entry']) && !empty($input['journal_entry']['debit_account_id']) && !empty($input['journal_entry']['credit_account_id'])) {
                $je = $input['journal_entry'];
                $amount = (float) $data['amount'];

                $glLines = [
                    [
                        'account_id' => (int) $je['debit_account_id'],
                        'type' => 'debit',
                        'amount' => $amount,
                        'description' => $je['note'] ?? $input['description'] ?? 'Expense'
                    ],
                    [
                        'account_id' => (int) $je['credit_account_id'],
                        'type' => 'credit',
                        'amount' => $amount,
                        'description' => $je['note'] ?? $input['description'] ?? 'Expense'
                    ]
                ];

                try {
                    $gl = new \App\Core\Finance\GeneralLedger();
                    $journalEntryId = $gl->postEntry(
                        $data['expense_date'],
                        $input['description'] ?? 'Expense: ' . $data['category'],
                        $glLines,
                        'expense',
                        null
                    );

                    // Only add journal_entry_id if column exists in expenses table
                    if ($journalEntryId) {
                        // Check if column exists before adding
                        $columnCheck = $this->db->fetch(
                            "SELECT COUNT(*) as cnt FROM information_schema.columns 
                             WHERE table_schema = DATABASE() 
                             AND table_name = 'expenses' 
                             AND column_name = 'journal_entry_id'"
                        );
                        if ($columnCheck && $columnCheck['cnt'] > 0) {
                            $expenseData['journal_entry_id'] = $journalEntryId;
                        } else {
                            error_log('Journal entry created but journal_entry_id column missing in expenses table. Run migration 012.');
                        }
                    }
                } catch (\Exception $jeError) {
                    // Log but don't fail - journal entry is optional
                    error_log('Journal entry creation failed: ' . $jeError->getMessage());
                }
            }

            $expenseId = $this->db->insert('expenses', $expenseData);

            // Update budget spent amount if linked to project
            if (!empty($input['project_id'])) {
                $this->updateBudgetSpent((int) $input['project_id'], $data['category']);
            }

            $expense = $this->db->fetch("SELECT * FROM expenses WHERE id = ?", [$expenseId]);

            return $this->success($expense, 'Expense created', 201);
        } catch (\Exception $e) {
            $this->error('Failed to create expense: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get single expense
     */
    public function show(string $id): array
    {
        $expense = $this->db->fetch(
            "SELECT 
                e.*,
                p.name as project_name,
                u.first_name,
                u.last_name
             FROM expenses e
             LEFT JOIN projects p ON e.project_id = p.id
             LEFT JOIN users u ON e.user_id = u.id
             WHERE e.id = ? AND e.tenant_id = ?",
            [$id, $this->db->getTenantId()]
        );

        if (!$expense) {
            $this->error('Expense not found', 404);
        }

        return $this->success($expense);
    }

    /**
     * Update expense
     */
    public function update(string $id): array
    {
        $expense = $this->db->fetch(
            "SELECT * FROM expenses WHERE id = ? AND tenant_id = ?",
            [$id, $this->db->getTenantId()]
        );

        if (!$expense) {
            $this->error('Expense not found', 404);
        }

        // Note: We allow editing approved expenses from project ledger
        // The original restriction was: if ($expense['status'] === 'approved') { error }

        $input = $this->getJsonInput();
        $allowedFields = [
            'project_id',
            'employee_id',
            'category',
            'description',
            'amount',
            'expense_date',
            'vendor',
            'receipt_number',
            'receipt_path',
            'payment_method',
            'is_billable',
            'notes'
        ];

        $updateData = [];
        foreach ($allowedFields as $field) {
            if (isset($input[$field])) {
                $updateData[$field] = $input[$field];
            }
        }

        $oldProjectId = $expense['project_id'];
        $oldCategory = $expense['category'];

        if (!empty($updateData)) {
            $updateData['updated_at'] = date('Y-m-d H:i:s');
            $this->db->update('expenses', $updateData, ['id' => $id]);
        }

        // Recalculate budget if project/category changed
        if (!empty($oldProjectId)) {
            $this->updateBudgetSpent($oldProjectId, $oldCategory);
        }
        if (!empty($input['project_id'])) {
            $this->updateBudgetSpent($input['project_id'], $input['category'] ?? $oldCategory);
        }

        $updated = $this->db->fetch("SELECT * FROM expenses WHERE id = ?", [$id]);

        return $this->success($updated, 'Expense updated');
    }

    /**
     * Delete expense
     */
    public function destroy(string $id): array
    {
        $expense = $this->db->fetch(
            "SELECT * FROM expenses WHERE id = ? AND tenant_id = ?",
            [$id, $this->db->getTenantId()]
        );

        if (!$expense) {
            $this->error('Expense not found', 404);
        }

        // Note: We allow deleting approved expenses from project ledger
        // The original restriction was: if ($expense['status'] === 'approved') { error }

        $projectId = $expense['project_id'];
        $category = $expense['category'];

        $this->db->delete('expenses', ['id' => $id]);

        // Update budget
        if ($projectId) {
            $this->updateBudgetSpent($projectId, $category);
        }

        return $this->success(null, 'Expense deleted');
    }

    /**
     * Approve expense
     */
    public function approve(string $id): array
    {
        $expense = $this->db->fetch(
            "SELECT * FROM expenses WHERE id = ? AND tenant_id = ?",
            [$id, $this->db->getTenantId()]
        );

        if (!$expense) {
            $this->error('Expense not found', 404);
        }

        $user = $_SESSION['user'];

        $this->db->update('expenses', [
            'status' => 'approved',
            'approved_by' => $user['id'],
            'approved_at' => date('Y-m-d H:i:s'),
        ], ['id' => $id]);

        return $this->success(null, 'Expense approved');
    }

    /**
     * Reject expense
     */
    public function reject(string $id): array
    {
        $expense = $this->db->fetch(
            "SELECT * FROM expenses WHERE id = ? AND tenant_id = ?",
            [$id, $this->db->getTenantId()]
        );

        if (!$expense) {
            $this->error('Expense not found', 404);
        }

        $input = $this->getJsonInput();
        $user = $_SESSION['user'];

        $this->db->update('expenses', [
            'status' => 'rejected',
            'approved_by' => $user['id'],
            'approved_at' => date('Y-m-d H:i:s'),
            'notes' => ($expense['notes'] ?? '') . "\nRejected: " . ($input['reason'] ?? 'No reason provided'),
        ], ['id' => $id]);

        return $this->success(null, 'Expense rejected');
    }

    /**
     * Get expense summary
     */
    public function summary(): array
    {
        $params = $this->getQueryParams();
        $startDate = $params['start_date'] ?? date('Y-m-01');
        $endDate = $params['end_date'] ?? date('Y-m-t');
        $projectId = $params['project_id'] ?? null;

        $tenantId = $this->db->getTenantId();
        $conditions = ["tenant_id = ?", "expense_date BETWEEN ? AND ?"];
        $bindings = [$tenantId, $startDate, $endDate];

        if ($projectId) {
            $conditions[] = "project_id = ?";
            $bindings[] = $projectId;
        }

        $where = implode(' AND ', $conditions);

        // Total
        $total = $this->db->fetch(
            "SELECT 
                COUNT(*) as count,
                COALESCE(SUM(amount), 0) as total
             FROM expenses 
             WHERE {$where}",
            $bindings
        );

        // By category
        $byCategory = $this->db->fetchAll(
            "SELECT 
                category,
                COUNT(*) as count,
                SUM(amount) as total
             FROM expenses 
             WHERE {$where}
             GROUP BY category
             ORDER BY total DESC",
            $bindings
        );

        // By status
        $byStatus = $this->db->fetchAll(
            "SELECT 
                status,
                COUNT(*) as count,
                SUM(amount) as total
             FROM expenses 
             WHERE {$where}
             GROUP BY status",
            $bindings
        );

        // Daily trend
        $dailyTrend = $this->db->fetchAll(
            "SELECT 
                expense_date as date,
                SUM(amount) as total
             FROM expenses 
             WHERE {$where}
             GROUP BY expense_date
             ORDER BY expense_date",
            $bindings
        );

        return $this->success([
            'total' => [
                'count' => (int) $total['count'],
                'amount' => (float) $total['total'],
            ],
            'by_category' => $byCategory,
            'by_status' => $byStatus,
            'daily_trend' => $dailyTrend,
        ]);
    }

    /**
     * Update budget spent amount
     */
    private function updateBudgetSpent(int $projectId, string $category): void
    {
        $totalSpent = $this->db->fetch(
            "SELECT COALESCE(SUM(amount), 0) as total 
             FROM expenses 
             WHERE project_id = ? AND category = ? AND tenant_id = ? AND status != 'rejected'",
            [$projectId, $category, $this->db->getTenantId()]
        );

        $this->db->query(
            "UPDATE budgets SET spent_amount = ?, updated_at = NOW() 
             WHERE project_id = ? AND category = ? AND tenant_id = ?",
            [$totalSpent['total'], $projectId, $category, $this->db->getTenantId()]
        );
    }
}
