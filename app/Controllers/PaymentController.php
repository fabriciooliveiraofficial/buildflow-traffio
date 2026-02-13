<?php

namespace App\Controllers;

use App\Core\Controller;

class PaymentController extends Controller
{
    /**
     * Store a direct payment for a project (creates Invoice + Payment)
     */
    public function storeProjectPayment(string $id): array
    {
        $tenantId = $this->db->getTenantId();

        $data = $this->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'payment_method' => 'required',
            'description' => 'required',
        ]);

        $input = $this->getJsonInput();

        // Get Project Details
        $project = $this->db->fetch(
            "SELECT * FROM projects WHERE id = ? AND tenant_id = ?",
            [$id, $tenantId]
        );

        if (!$project) {
            $this->error('Project not found', 404);
        }

        if (empty($project['client_id'])) {
            $this->error('Project must have a client assigned to record payments', 400);
        }

        // Generate Invoice Number (Unique based on time and random)
        $invoiceNumber = 'INV-' . date('Ymd-His') . '-' . mt_rand(1000, 9999);

        try {
            // 1. Create Invoice (Paid)
            $invoiceId = $this->db->insert('invoices', [
                'tenant_id' => $tenantId,
                'client_id' => $project['client_id'],
                'project_id' => $id,
                'invoice_number' => $invoiceNumber,
                'issue_date' => $data['payment_date'],
                'due_date' => $data['payment_date'],
                'subtotal' => $data['amount'],
                'total_amount' => $data['amount'],
                'paid_amount' => $data['amount'],
                'status' => 'paid',
                'notes' => $data['description'],
                'paid_at' => date('Y-m-d H:i:s'),
            ]);

            // 2. Create Invoice Item
            $this->db->query(
                "INSERT INTO `invoice_items` (`invoice_id`, `description`, `quantity`, `unit_price`, `amount`) VALUES (?, ?, ?, ?, ?)",
                [$invoiceId, $data['description'], 1, $data['amount'], $data['amount']]
            );

            // 3. Create Payment
            $paymentId = $this->db->insert('payments', [
                'tenant_id' => $tenantId,
                'invoice_id' => $invoiceId,
                'amount' => $data['amount'],
                'payment_date' => $data['payment_date'],
                'payment_method' => $data['payment_method'],
                'status' => 'completed',
                'notes' => $data['description'],
            ]);

            // 4. Check & Update Project Status
            $this->updateProjectStatus($id, $project['contract_value'] ?? 0);

            // 5. Optional: Create Journal Entry (after payment is saved, so it's truly optional)
            $journalEntryId = null;
            if (!empty($input['journal_entry']) && !empty($input['journal_entry']['debit_account_id']) && !empty($input['journal_entry']['credit_account_id'])) {
                try {
                    $je = $input['journal_entry'];
                    $amount = (float) $data['amount'];

                    $glLines = [
                        [
                            'account_id' => (int) $je['debit_account_id'],
                            'type' => 'debit',
                            'amount' => $amount,
                            'description' => $je['note'] ?? $data['description']
                        ],
                        [
                            'account_id' => (int) $je['credit_account_id'],
                            'type' => 'credit',
                            'amount' => $amount,
                            'description' => $je['note'] ?? $data['description']
                        ]
                    ];

                    $gl = new \App\Core\Finance\GeneralLedger();
                    $journalEntryId = $gl->postEntry(
                        $data['payment_date'],
                        'Income: ' . $data['description'],
                        $glLines,
                        'income',
                        null
                    );
                } catch (\Exception $jeError) {
                    // Log but don't fail - payment is already saved
                    error_log('Journal entry creation failed: ' . $jeError->getMessage());
                }
            }

            return $this->success([
                'payment_id' => $paymentId,
                'invoice_id' => $invoiceId,
                'journal_entry_id' => $journalEntryId
            ], 'Payment recorded successfully', 201);

        } catch (\Exception $e) {
            $this->error('Failed to record payment: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Check if project is fully paid and close it if necessary
     */
    private function updateProjectStatus($projectId, $contractValue)
    {
        if (!$contractValue || $contractValue <= 0)
            return;

        $tenantId = $this->db->getTenantId();

        // Calculate total paid for this project
        // (Sum of paid_amount of all invoices linked to project)
        $totalPaid = $this->db->fetch(
            "SELECT COALESCE(SUM(paid_amount), 0) as total 
             FROM invoices 
             WHERE project_id = ? AND tenant_id = ? AND status != 'cancelled'",
            [$projectId, $tenantId]
        )['total'];

        // If Paid >= Contract Value, mark as completed
        if ($totalPaid >= $contractValue) {
            $this->db->update('projects', [
                'status' => 'completed',
                'updated_at' => date('Y-m-d H:i:s')
            ], ['id' => $projectId]);
        }
    }

    /**
     * Get a single payment with related invoice info
     */
    public function show(string $id): array
    {
        $tenantId = $this->db->getTenantId();

        $payment = $this->db->fetch(
            "SELECT p.*, i.invoice_number, i.notes as invoice_notes, i.project_id
             FROM payments p
             JOIN invoices i ON p.invoice_id = i.id
             WHERE p.id = ? AND p.tenant_id = ?",
            [$id, $tenantId]
        );

        if (!$payment) {
            $this->error('Payment not found', 404);
        }

        return $this->success($payment);
    }

    /**
     * Update a payment and its linked invoice
     */
    public function update(string $id): array
    {
        $tenantId = $this->db->getTenantId();

        $payment = $this->db->fetch(
            "SELECT p.*, i.id as invoice_id, i.project_id
             FROM payments p
             JOIN invoices i ON p.invoice_id = i.id
             WHERE p.id = ? AND p.tenant_id = ?",
            [$id, $tenantId]
        );

        if (!$payment) {
            $this->error('Payment not found', 404);
        }

        $data = $this->validate([
            'amount' => 'numeric|min:0.01',
            'payment_date' => 'date',
            'description' => 'string',
        ]);

        try {
            $this->db->beginTransaction();

            // Update payment
            $paymentUpdate = [];
            if (isset($data['amount'])) {
                $paymentUpdate['amount'] = $data['amount'];
            }
            if (isset($data['payment_date'])) {
                $paymentUpdate['payment_date'] = $data['payment_date'];
            }
            if (isset($data['description'])) {
                $paymentUpdate['notes'] = $data['description'];
            }

            if (!empty($paymentUpdate)) {
                $paymentUpdate['updated_at'] = date('Y-m-d H:i:s');
                $this->db->update('payments', $paymentUpdate, ['id' => $id]);
            }

            // Update invoice
            $invoiceUpdate = [];
            if (isset($data['amount'])) {
                $invoiceUpdate['subtotal'] = $data['amount'];
                $invoiceUpdate['total_amount'] = $data['amount'];
                $invoiceUpdate['paid_amount'] = $data['amount'];
            }
            if (isset($data['payment_date'])) {
                $invoiceUpdate['issue_date'] = $data['payment_date'];
                $invoiceUpdate['due_date'] = $data['payment_date'];
            }
            if (isset($data['description'])) {
                $invoiceUpdate['notes'] = $data['description'];
            }

            if (!empty($invoiceUpdate)) {
                $invoiceUpdate['updated_at'] = date('Y-m-d H:i:s');
                $this->db->update('invoices', $invoiceUpdate, ['id' => $payment['invoice_id']]);

                // Also update invoice_items
                if (isset($data['amount'])) {
                    $this->db->query(
                        "UPDATE invoice_items SET unit_price = ?, amount = ? WHERE invoice_id = ?",
                        [$data['amount'], $data['amount'], $payment['invoice_id']]
                    );
                }
                if (isset($data['description'])) {
                    $this->db->query(
                        "UPDATE invoice_items SET description = ? WHERE invoice_id = ?",
                        [$data['description'], $payment['invoice_id']]
                    );
                }
            }

            $this->db->commit();

            return $this->success(null, 'Payment updated successfully');

        } catch (\Exception $e) {
            $this->db->rollBack();
            $this->error('Failed to update payment: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete a payment and its linked invoice
     */
    public function destroy(string $id): array
    {
        $tenantId = $this->db->getTenantId();

        $payment = $this->db->fetch(
            "SELECT p.*, i.id as invoice_id, i.project_id
             FROM payments p
             JOIN invoices i ON p.invoice_id = i.id
             WHERE p.id = ? AND p.tenant_id = ?",
            [$id, $tenantId]
        );

        if (!$payment) {
            $this->error('Payment not found', 404);
        }

        try {
            $this->db->beginTransaction();

            // Delete invoice items first (foreign key constraint)
            $this->db->query(
                "DELETE FROM invoice_items WHERE invoice_id = ?",
                [$payment['invoice_id']]
            );

            // Delete payment
            $this->db->delete('payments', ['id' => $id]);

            // Delete invoice
            $this->db->delete('invoices', ['id' => $payment['invoice_id']]);

            $this->db->commit();

            return $this->success(null, 'Payment deleted successfully');

        } catch (\Exception $e) {
            $this->db->rollBack();
            $this->error('Failed to delete payment: ' . $e->getMessage(), 500);
        }
    }
}