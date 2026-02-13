<?php
/**
 * Journal Entry API Controller
 */

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Tenant;
use App\Core\Finance\GeneralLedger;

class JournalEntryController extends Controller
{
    /**
     * List all journal entries
     */
    public function index(): array
    {
        $params = $this->getQueryParams();
        $page = (int) ($params['page'] ?? 1);
        $perPage = (int) ($params['per_page'] ?? 25);
        $status = $params['status'] ?? null;

        $tenantId = $this->db->getTenantId();
        $conditions = ["je.tenant_id = ?"];
        $bindings = [$tenantId];

        if ($status) {
            $conditions[] = "je.status = ?";
            $bindings[] = $status;
        }

        $where = implode(' AND ', $conditions);
        $offset = ($page - 1) * $perPage;

        $countResult = $this->db->fetch(
            "SELECT COUNT(*) as total FROM journal_entries je WHERE {$where}",
            $bindings
        );
        $total = (int) $countResult['total'];

        $entries = $this->db->fetchAll(
            "SELECT 
                je.*,
                (SELECT SUM(debit) FROM journal_entry_lines WHERE journal_entry_id = je.id) as total_debit,
                (SELECT SUM(credit) FROM journal_entry_lines WHERE journal_entry_id = je.id) as total_credit
             FROM journal_entries je
             WHERE {$where}
             ORDER BY je.entry_date DESC, je.id DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $bindings
        );

        return $this->paginate($entries, $total, $page, $perPage);
    }

    /**
     * Get single journal entry with lines
     */
    public function show(string $id): array
    {
        $entry = $this->db->fetch(
            "SELECT * FROM journal_entries WHERE id = ? AND tenant_id = ?",
            [$id, $this->db->getTenantId()]
        );

        if (!$entry) {
            $this->error('Journal entry not found', 404);
        }

        $lines = $this->db->fetchAll(
            "SELECT jel.*, coa.code as account_code, coa.name as account_name, coa.type as account_type
             FROM journal_entry_lines jel
             JOIN chart_of_accounts coa ON jel.account_id = coa.id
             WHERE jel.journal_entry_id = ?
             ORDER BY jel.id",
            [$id]
        );

        $entry['lines'] = $lines;

        return $this->success($entry);
    }

    /**
     * Create a new journal entry
     */
    public function store(): array
    {
        $input = $this->getJsonInput();

        // Validate required fields
        if (empty($input['entry_date'])) {
            $this->error('Entry date is required', 422);
        }
        if (empty($input['description'])) {
            $this->error('Description is required', 422);
        }
        if (empty($input['lines']) || !is_array($input['lines']) || count($input['lines']) < 2) {
            $this->error('At least 2 lines are required', 422);
        }

        // Convert lines to GL format
        $glLines = [];
        foreach ($input['lines'] as $line) {
            if (empty($line['account_id'])) {
                $this->error('Account is required for each line', 422);
            }

            $debit = (float) ($line['debit'] ?? 0);
            $credit = (float) ($line['credit'] ?? 0);

            if ($debit > 0) {
                $glLines[] = [
                    'account_id' => (int) $line['account_id'],
                    'type' => 'debit',
                    'amount' => $debit,
                    'description' => $line['description'] ?? null
                ];
            }
            if ($credit > 0) {
                $glLines[] = [
                    'account_id' => (int) $line['account_id'],
                    'type' => 'credit',
                    'amount' => $credit,
                    'description' => $line['description'] ?? null
                ];
            }
        }

        try {
            $gl = new GeneralLedger();
            $entryId = $gl->postEntry(
                $input['entry_date'],
                $input['description'],
                $glLines,
                'manual',
                null
            );

            $entry = $this->db->fetch("SELECT * FROM journal_entries WHERE id = ?", [$entryId]);
            return $this->success($entry, 'Journal entry created', 201);

        } catch (\Exception $e) {
            $this->error($e->getMessage(), 422);
        }
    }

    /**
     * Void a journal entry
     */
    public function void(string $id): array
    {
        $entry = $this->db->fetch(
            "SELECT * FROM journal_entries WHERE id = ? AND tenant_id = ?",
            [$id, $this->db->getTenantId()]
        );

        if (!$entry) {
            $this->error('Journal entry not found', 404);
        }

        if ($entry['status'] === 'void') {
            $this->error('Entry is already voided', 422);
        }

        $this->db->update('journal_entries', [
            'status' => 'void',
            'updated_at' => date('Y-m-d H:i:s')
        ], ['id' => $id]);

        return $this->success(null, 'Journal entry voided');
    }

    /**
     * Update a journal entry
     */
    public function update(string $id): array
    {
        $entry = $this->db->fetch(
            "SELECT * FROM journal_entries WHERE id = ? AND tenant_id = ?",
            [$id, $this->db->getTenantId()]
        );

        if (!$entry) {
            $this->error('Journal entry not found', 404);
        }

        if ($entry['status'] === 'void') {
            $this->error('Cannot edit a voided entry', 422);
        }

        $input = $this->getJsonInput();

        // Update main entry
        $this->db->update('journal_entries', [
            'entry_date' => $input['entry_date'] ?? $entry['entry_date'],
            'description' => $input['description'] ?? $entry['description'],
            'updated_at' => date('Y-m-d H:i:s')
        ], ['id' => $id]);

        // If lines are provided, replace them
        if (!empty($input['lines']) && is_array($input['lines'])) {
            // Delete existing lines
            $this->db->query("DELETE FROM journal_entry_lines WHERE journal_entry_id = ?", [$id]);

            // Insert new lines
            foreach ($input['lines'] as $line) {
                $debit = (float) ($line['debit'] ?? 0);
                $credit = (float) ($line['credit'] ?? 0);

                $this->db->insert('journal_entry_lines', [
                    'journal_entry_id' => $id,
                    'account_id' => $line['account_id'],
                    'description' => $line['description'] ?? null,
                    'debit' => $debit,
                    'credit' => $credit
                ]);
            }
        }

        return $this->success(null, 'Journal entry updated');
    }

    /**
     * Delete a journal entry
     */
    public function destroy(string $id): array
    {
        $entry = $this->db->fetch(
            "SELECT * FROM journal_entries WHERE id = ? AND tenant_id = ?",
            [$id, $this->db->getTenantId()]
        );

        if (!$entry) {
            $this->error('Journal entry not found', 404);
        }

        if ($entry['status'] === 'posted') {
            $this->error('Cannot delete a posted entry. Void it instead.', 422);
        }

        // Delete lines first (foreign key constraint)
        $this->db->query("DELETE FROM journal_entry_lines WHERE journal_entry_id = ?", [$id]);

        // Delete the entry
        $this->db->query("DELETE FROM journal_entries WHERE id = ?", [$id]);

        return $this->success(null, 'Journal entry deleted');
    }
}
