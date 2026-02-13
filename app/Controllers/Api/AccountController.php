<?php
/**
 * Chart of Accounts API Controller
 */

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Tenant;

class AccountController extends Controller
{
    /**
     * List all accounts
     */
    public function index(): array
    {
        $params = $this->getQueryParams();
        $type = $params['type'] ?? null;

        $tenantId = $this->db->getTenantId();
        $conditions = ["tenant_id = ?"];
        $bindings = [$tenantId];

        if ($type) {
            $conditions[] = "type = ?";
            $bindings[] = $type;
        }

        $where = implode(' AND ', $conditions);

        $accounts = $this->db->fetchAll(
            "SELECT * FROM chart_of_accounts WHERE {$where} ORDER BY code ASC",
            $bindings
        );

        return $this->success($accounts);
    }

    /**
     * Create account
     */
    public function store(): array
    {
        $data = $this->validate([
            'name' => 'required',
            'type' => 'required',
        ]);

        $input = $this->getJsonInput();
        $tenantId = $this->db->getTenantId();

        // Auto-generate account code based on type
        // Standard accounting code ranges:
        // 1000-1999: Assets
        // 2000-2999: Liabilities
        // 3000-3999: Equity
        // 4000-4999: Income/Revenue
        // 5000-5999: Expenses
        $typeRanges = [
            'asset' => [1000, 1999],
            'liability' => [2000, 2999],
            'equity' => [3000, 3999],
            'income' => [4000, 4999],
            'expense' => [5000, 5999],
        ];

        $type = $input['type'];
        if (!isset($typeRanges[$type])) {
            $this->error('Invalid account type', 422);
        }

        [$rangeStart, $rangeEnd] = $typeRanges[$type];

        // Find the next available code in this range
        $maxCode = $this->db->fetch(
            "SELECT MAX(CAST(code AS UNSIGNED)) as max_code 
             FROM chart_of_accounts 
             WHERE tenant_id = ? 
             AND CAST(code AS UNSIGNED) BETWEEN ? AND ?",
            [$tenantId, $rangeStart, $rangeEnd]
        );

        $nextCode = ($maxCode['max_code'] ?? $rangeStart - 1) + 1;

        // Ensure we haven't exceeded the range
        if ($nextCode > $rangeEnd) {
            $this->error('Maximum accounts reached for this type. Please contact support.', 422);
        }

        // Format as 4-digit code
        $accountCode = str_pad($nextCode, 4, '0', STR_PAD_LEFT);

        $accountId = $this->db->insert('chart_of_accounts', [
            'tenant_id' => $tenantId,
            'code' => $accountCode,
            'name' => $input['name'],
            'type' => $input['type'],
            'subtype' => $input['subtype'] ?? null,
            'description' => $input['description'] ?? null,
            'is_system' => 0,
        ]);

        $account = $this->db->fetch("SELECT * FROM chart_of_accounts WHERE id = ?", [$accountId]);

        return $this->success($account, 'Account created', 201);
    }

    /**
     * Get single account
     */
    public function show(string $id): array
    {
        $account = $this->db->fetch(
            "SELECT * FROM chart_of_accounts WHERE id = ? AND tenant_id = ?",
            [$id, $this->db->getTenantId()]
        );

        if (!$account) {
            $this->error('Account not found', 404);
        }

        return $this->success($account);
    }

    /**
     * Update account
     */
    public function update(string $id): array
    {
        $account = $this->db->fetch(
            "SELECT * FROM chart_of_accounts WHERE id = ? AND tenant_id = ?",
            [$id, $this->db->getTenantId()]
        );

        if (!$account) {
            $this->error('Account not found', 404);
        }

        if ($account['is_system']) {
            $this->error('System accounts cannot be modified', 422);
        }

        $input = $this->getJsonInput();
        $allowedFields = ['name', 'subtype', 'description', 'status'];

        $updateData = [];
        foreach ($allowedFields as $field) {
            if (isset($input[$field])) {
                $updateData[$field] = $input[$field];
            }
        }

        if (!empty($updateData)) {
            $updateData['updated_at'] = date('Y-m-d H:i:s');
            $this->db->update('chart_of_accounts', $updateData, ['id' => $id]);
        }

        $updated = $this->db->fetch("SELECT * FROM chart_of_accounts WHERE id = ?", [$id]);

        return $this->success($updated, 'Account updated');
    }

    /**
     * Delete account
     */
    public function destroy(string $id): array
    {
        $account = $this->db->fetch(
            "SELECT * FROM chart_of_accounts WHERE id = ? AND tenant_id = ?",
            [$id, $this->db->getTenantId()]
        );

        if (!$account) {
            $this->error('Account not found', 404);
        }

        if ($account['is_system']) {
            $this->error('System accounts cannot be deleted', 422);
        }

        // Check for journal entries using this account
        $usage = $this->db->fetch(
            "SELECT COUNT(*) as count FROM journal_entry_lines WHERE account_id = ?",
            [$id]
        );

        if ($usage['count'] > 0) {
            $this->error('Cannot delete account with existing transactions', 422);
        }

        $this->db->delete('chart_of_accounts', ['id' => $id]);

        return $this->success(null, 'Account deleted');
    }

    /**
     * Get account balance
     */
    public function balance(string $id): array
    {
        $account = $this->db->fetch(
            "SELECT * FROM chart_of_accounts WHERE id = ? AND tenant_id = ?",
            [$id, $this->db->getTenantId()]
        );

        if (!$account) {
            $this->error('Account not found', 404);
        }

        $totals = $this->db->fetch(
            "SELECT 
                COALESCE(SUM(debit), 0) as total_debits,
                COALESCE(SUM(credit), 0) as total_credits
             FROM journal_entry_lines jel
             JOIN journal_entries je ON jel.journal_entry_id = je.id
             WHERE jel.account_id = ? AND je.status = 'posted'",
            [$id]
        );

        // Calculate balance based on account type
        // Assets and Expenses: Debits increase, Credits decrease
        // Liabilities, Equity, Income: Credits increase, Debits decrease
        $debitTypes = ['asset', 'expense'];
        $balance = in_array($account['type'], $debitTypes)
            ? $totals['total_debits'] - $totals['total_credits']
            : $totals['total_credits'] - $totals['total_debits'];

        return $this->success([
            'account' => $account,
            'total_debits' => (float) $totals['total_debits'],
            'total_credits' => (float) $totals['total_credits'],
            'balance' => $balance,
        ]);
    }
}
