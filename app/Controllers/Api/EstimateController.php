<?php
/**
 * Estimate API Controller
 */

namespace App\Controllers\Api;

use App\Core\Controller;

class EstimateController extends Controller
{
    /**
     * List estimates
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
        $conditions = ["e.tenant_id = ?"];
        $bindings = [$tenantId];

        if ($status) {
            $conditions[] = "e.status = ?";
            $bindings[] = $status;
        }

        if ($clientId) {
            $conditions[] = "e.client_id = ?";
            $bindings[] = $clientId;
        }

        if ($search) {
            $conditions[] = "(e.estimate_number LIKE ? OR e.title LIKE ?)";
            $bindings[] = "%{$search}%";
            $bindings[] = "%{$search}%";
        }

        $where = implode(' AND ', $conditions);
        $offset = ($page - 1) * $perPage;

        $countResult = $this->db->fetch(
            "SELECT COUNT(*) as total FROM estimates e WHERE {$where}",
            $bindings
        );
        $total = (int) $countResult['total'];

        $estimates = $this->db->fetchAll(
            "SELECT 
                e.*,
                c.name as client_name,
                p.name as project_name
             FROM estimates e
             LEFT JOIN clients c ON e.client_id = c.id
             LEFT JOIN projects p ON e.project_id = p.id
             WHERE {$where}
             ORDER BY e.created_at DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $bindings
        );

        return $this->paginate($estimates, $total, $page, $perPage);
    }

    /**
     * Create estimate
     */
    public function store(): array
    {
        $data = $this->getRequestData();
        $this->validate($data, [
            'client_id' => 'required',
            'issue_date' => 'required'
        ]);

        $tenantId = $this->db->getTenantId();

        // Generate estimate number
        $lastNum = $this->db->fetch(
            "SELECT MAX(CAST(SUBSTRING(estimate_number, 5) AS UNSIGNED)) as last_num 
             FROM estimates WHERE tenant_id = ? AND estimate_number LIKE 'EST-%'",
            [$tenantId]
        );
        $number = 'EST-' . str_pad(($lastNum['last_num'] ?? 0) + 1, 4, '0', STR_PAD_LEFT);

        $this->db->insert('estimates', [
            'tenant_id' => $tenantId,
            'client_id' => $data['client_id'],
            'project_id' => $data['project_id'] ?? null,
            'estimate_number' => $number,
            'title' => $data['title'] ?? null,
            'issue_date' => $data['issue_date'],
            'expiry_date' => $data['expiry_date'] ?? null,
            'subtotal' => 0,
            'tax_rate' => $data['tax_rate'] ?? 0,
            'tax_amount' => 0,
            'total_amount' => 0,
            'status' => 'draft',
            'notes' => $data['notes'] ?? null,
            'terms' => $data['terms'] ?? null,
            'created_by' => $this->getAuthUser()['id'] ?? null
        ]);

        $estimateId = $this->db->lastInsertId();

        return $this->success(['id' => $estimateId, 'estimate_number' => $number], 'Estimate created', 201);
    }

    /**
     * Get single estimate with items
     */
    public function show(string $id): array
    {
        $tenantId = $this->db->getTenantId();

        $estimate = $this->db->fetch(
            "SELECT e.*, c.name as client_name, c.email as client_email, p.name as project_name
             FROM estimates e
             LEFT JOIN clients c ON e.client_id = c.id
             LEFT JOIN projects p ON e.project_id = p.id
             WHERE e.id = ? AND e.tenant_id = ?",
            [$id, $tenantId]
        );

        if (!$estimate) {
            return $this->error('Estimate not found', 404);
        }

        $estimate['items'] = $this->db->fetchAll(
            "SELECT * FROM estimate_items WHERE estimate_id = ? ORDER BY sort_order",
            [$id]
        );

        return $this->success($estimate);
    }

    /**
     * Update estimate
     */
    public function update(string $id): array
    {
        $data = $this->getRequestData();
        $tenantId = $this->db->getTenantId();

        $estimate = $this->db->fetch(
            "SELECT * FROM estimates WHERE id = ? AND tenant_id = ?",
            [$id, $tenantId]
        );

        if (!$estimate) {
            return $this->error('Estimate not found', 404);
        }

        $updateData = [];
        $allowedFields = [
            'client_id',
            'project_id',
            'title',
            'issue_date',
            'expiry_date',
            'tax_rate',
            'status',
            'notes',
            'terms'
        ];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
            }
        }

        if (!empty($updateData)) {
            $this->db->update('estimates', $updateData, ['id' => $id]);
        }

        return $this->success(null, 'Estimate updated');
    }

    /**
     * Delete estimate
     */
    public function destroy(string $id): array
    {
        $tenantId = $this->db->getTenantId();

        $estimate = $this->db->fetch(
            "SELECT * FROM estimates WHERE id = ? AND tenant_id = ?",
            [$id, $tenantId]
        );

        if (!$estimate) {
            return $this->error('Estimate not found', 404);
        }

        if ($estimate['status'] === 'converted') {
            return $this->error('Cannot delete converted estimate', 400);
        }

        $this->db->delete('estimates', ['id' => $id]);

        return $this->success(null, 'Estimate deleted');
    }

    /**
     * Add item to estimate
     */
    public function addItem(string $id): array
    {
        $data = $this->getRequestData();
        $this->validate($data, ['description' => 'required']);

        $tenantId = $this->db->getTenantId();
        $estimate = $this->db->fetch(
            "SELECT * FROM estimates WHERE id = ? AND tenant_id = ?",
            [$id, $tenantId]
        );

        if (!$estimate) {
            return $this->error('Estimate not found', 404);
        }

        $quantity = (float) ($data['quantity'] ?? 1);
        $unitPrice = (float) ($data['unit_price'] ?? 0);
        $total = $quantity * $unitPrice;

        $this->db->insert('estimate_items', [
            'estimate_id' => $id,
            'description' => $data['description'],
            'quantity' => $quantity,
            'unit' => $data['unit'] ?? 'unit',
            'unit_price' => $unitPrice,
            'total' => $total,
            'sort_order' => $data['sort_order'] ?? 0
        ]);

        $this->recalculateTotals($id);

        return $this->success(['id' => $this->db->lastInsertId()], 'Item added', 201);
    }

    /**
     * Update estimate item
     */
    public function updateItem(string $id, string $itemId): array
    {
        $data = $this->getRequestData();
        $tenantId = $this->db->getTenantId();

        $estimate = $this->db->fetch(
            "SELECT * FROM estimates WHERE id = ? AND tenant_id = ?",
            [$id, $tenantId]
        );

        if (!$estimate) {
            return $this->error('Estimate not found', 404);
        }

        $quantity = (float) ($data['quantity'] ?? 1);
        $unitPrice = (float) ($data['unit_price'] ?? 0);
        $total = $quantity * $unitPrice;

        $this->db->update('estimate_items', [
            'description' => $data['description'] ?? '',
            'quantity' => $quantity,
            'unit' => $data['unit'] ?? 'unit',
            'unit_price' => $unitPrice,
            'total' => $total
        ], ['id' => $itemId, 'estimate_id' => $id]);

        $this->recalculateTotals($id);

        return $this->success(null, 'Item updated');
    }

    /**
     * Delete estimate item
     */
    public function deleteItem(string $id, string $itemId): array
    {
        $tenantId = $this->db->getTenantId();

        $estimate = $this->db->fetch(
            "SELECT * FROM estimates WHERE id = ? AND tenant_id = ?",
            [$id, $tenantId]
        );

        if (!$estimate) {
            return $this->error('Estimate not found', 404);
        }

        $this->db->delete('estimate_items', ['id' => $itemId, 'estimate_id' => $id]);
        $this->recalculateTotals($id);

        return $this->success(null, 'Item deleted');
    }

    /**
     * Send estimate to client
     */
    public function send(string $id): array
    {
        $tenantId = $this->db->getTenantId();

        $estimate = $this->db->fetch(
            "SELECT * FROM estimates WHERE id = ? AND tenant_id = ?",
            [$id, $tenantId]
        );

        if (!$estimate) {
            return $this->error('Estimate not found', 404);
        }

        $this->db->update('estimates', ['status' => 'sent'], ['id' => $id]);

        return $this->success(null, 'Estimate sent');
    }

    /**
     * Approve estimate
     */
    public function approve(string $id): array
    {
        $tenantId = $this->db->getTenantId();

        $estimate = $this->db->fetch(
            "SELECT * FROM estimates WHERE id = ? AND tenant_id = ?",
            [$id, $tenantId]
        );

        if (!$estimate) {
            return $this->error('Estimate not found', 404);
        }

        $this->db->update('estimates', ['status' => 'approved'], ['id' => $id]);

        return $this->success(null, 'Estimate approved');
    }

    /**
     * Reject estimate
     */
    public function reject(string $id): array
    {
        $tenantId = $this->db->getTenantId();

        $estimate = $this->db->fetch(
            "SELECT * FROM estimates WHERE id = ? AND tenant_id = ?",
            [$id, $tenantId]
        );

        if (!$estimate) {
            return $this->error('Estimate not found', 404);
        }

        $this->db->update('estimates', ['status' => 'rejected'], ['id' => $id]);

        return $this->success(null, 'Estimate rejected');
    }

    /**
     * Convert estimate to invoice
     */
    public function convertToInvoice(string $id): array
    {
        $tenantId = $this->db->getTenantId();

        $estimate = $this->db->fetch(
            "SELECT * FROM estimates WHERE id = ? AND tenant_id = ?",
            [$id, $tenantId]
        );

        if (!$estimate) {
            return $this->error('Estimate not found', 404);
        }

        if ($estimate['status'] === 'converted') {
            return $this->error('Estimate already converted', 400);
        }

        // Generate invoice number
        $lastNum = $this->db->fetch(
            "SELECT MAX(CAST(SUBSTRING(invoice_number, 5) AS UNSIGNED)) as last_num 
             FROM invoices WHERE tenant_id = ? AND invoice_number LIKE 'INV-%'",
            [$tenantId]
        );
        $invoiceNumber = 'INV-' . str_pad(($lastNum['last_num'] ?? 0) + 1, 4, '0', STR_PAD_LEFT);

        // Create invoice
        $this->db->insert('invoices', [
            'tenant_id' => $tenantId,
            'client_id' => $estimate['client_id'],
            'project_id' => $estimate['project_id'],
            'invoice_number' => $invoiceNumber,
            'issue_date' => date('Y-m-d'),
            'due_date' => date('Y-m-d', strtotime('+30 days')),
            'subtotal' => $estimate['subtotal'],
            'tax_rate' => $estimate['tax_rate'],
            'tax_amount' => $estimate['tax_amount'],
            'total_amount' => $estimate['total_amount'],
            'paid_amount' => 0,
            'status' => 'draft',
            'notes' => $estimate['notes']
        ]);

        $invoiceId = $this->db->lastInsertId();

        // Copy items
        $items = $this->db->fetchAll(
            "SELECT * FROM estimate_items WHERE estimate_id = ?",
            [$id]
        );

        foreach ($items as $item) {
            $this->db->insert('invoice_items', [
                'invoice_id' => $invoiceId,
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'total' => $item['total']
            ]);
        }

        // Update estimate status
        $this->db->update('estimates', [
            'status' => 'converted',
            'converted_invoice_id' => $invoiceId
        ], ['id' => $id]);

        return $this->success([
            'invoice_id' => $invoiceId,
            'invoice_number' => $invoiceNumber
        ], 'Converted to invoice');
    }

    /**
     * Get summary stats
     */
    public function summary(): array
    {
        $tenantId = $this->db->getTenantId();

        $stats = $this->db->fetch(
            "SELECT 
                COUNT(*) as total_count,
                SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft_count,
                SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent_count,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_count,
                SUM(CASE WHEN status = 'converted' THEN 1 ELSE 0 END) as converted_count,
                SUM(total_amount) as total_value,
                SUM(CASE WHEN status = 'approved' THEN total_amount ELSE 0 END) as approved_value,
                SUM(CASE WHEN status = 'converted' THEN total_amount ELSE 0 END) as converted_value
             FROM estimates WHERE tenant_id = ?",
            [$tenantId]
        );

        return $this->success($stats);
    }

    /**
     * Recalculate estimate totals
     */
    private function recalculateTotals(int $estimateId): void
    {
        $estimate = $this->db->fetch(
            "SELECT tax_rate FROM estimates WHERE id = ?",
            [$estimateId]
        );

        $subtotal = $this->db->fetch(
            "SELECT COALESCE(SUM(total), 0) as subtotal FROM estimate_items WHERE estimate_id = ?",
            [$estimateId]
        );

        $sub = (float) $subtotal['subtotal'];
        $taxRate = (float) ($estimate['tax_rate'] ?? 0);
        $taxAmount = $sub * ($taxRate / 100);
        $total = $sub + $taxAmount;

        $this->db->update('estimates', [
            'subtotal' => $sub,
            'tax_amount' => $taxAmount,
            'total_amount' => $total
        ], ['id' => $estimateId]);
    }
}
