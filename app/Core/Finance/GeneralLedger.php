<?php

namespace App\Core\Finance;

use App\Core\Database;
use App\Core\Tenant;
use PDO;
use Exception;

class GeneralLedger
{
    private $db;
    private $tenantId;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $current = Tenant::current();
        $this->tenantId = $current ? $current->getId() : null;
    }

    /**
     * Post a Journal Entry
     * 
     * @param string $date (YYYY-MM-DD)
     * @param string $description
     * @param array $lines [['account_id' => 1, 'type' => 'debit', 'amount' => 100.00, 'description' => 'memo']]
     * @param string|null $entityType (e.g. 'invoice')
     * @param int|null $entityId
     * @return int Journal Entry ID
     * @throws Exception
     */
    public function postEntry(string $date, string $description, array $lines, ?string $entityType = null, ?int $entityId = null): int
    {
        // 1. Validate Balance
        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($lines as $line) {
            if ($line['type'] === 'debit') {
                $totalDebit += (float) $line['amount'];
            } elseif ($line['type'] === 'credit') {
                $totalCredit += (float) $line['amount'];
            }
        }

        // Float comparison with epsilon
        if (abs($totalDebit - $totalCredit) > 0.01) {
            throw new Exception("Journal Entry is unbalanced: Debits ($totalDebit) != Credits ($totalCredit)");
        }

        try {
            $this->db->beginTransaction();

            // 2. Create Header
            $refNumber = $this->generateReferenceNumber();

            $sql = "INSERT INTO journal_entries 
                    (tenant_id, entry_date, reference_number, description, status, entity_type, entity_id, created_by) 
                    VALUES 
                    (:tenant_id, :date, :ref, :desc, 'posted', :entity_type, :entity_id, :user_id)";

            $this->db->query($sql, [
                ':tenant_id' => $this->tenantId,
                ':date' => $date,
                ':ref' => $refNumber,
                ':desc' => $description,
                ':entity_type' => $entityType,
                ':entity_id' => $entityId,
                ':user_id' => $_SESSION['user']['id'] ?? null
            ]);

            $entryId = $this->db->getPdo()->lastInsertId();

            // 3. Create Lines
            $lineSql = "INSERT INTO journal_entry_lines 
                        (tenant_id, journal_entry_id, account_id, debit, credit, description) 
                        VALUES 
                        (:tenant_id, :entry_id, :account_id, :debit, :credit, :desc)";

            foreach ($lines as $line) {
                $debit = ($line['type'] === 'debit') ? $line['amount'] : 0;
                $credit = ($line['type'] === 'credit') ? $line['amount'] : 0;

                $this->db->query($lineSql, [
                    ':tenant_id' => $this->tenantId,
                    ':entry_id' => $entryId,
                    ':account_id' => $line['account_id'],
                    ':debit' => $debit,
                    ':credit' => $credit,
                    ':desc' => $line['description'] ?? null
                ]);
            }

            $this->db->commit();
            return $entryId;

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    private function generateReferenceNumber(): string
    {
        // Simple generation: JE-{Timestamp}-{Random}
        // In production, this should be sequential per tenant e.g. JE-00001
        return 'JE-' . time() . '-' . mt_rand(1000, 9999);
    }
}
