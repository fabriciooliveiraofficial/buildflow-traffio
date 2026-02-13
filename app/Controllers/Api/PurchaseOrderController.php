<?php
/**
 * Purchase Order API Controller
 */

namespace App\Controllers\Api;

use App\Core\Controller;

class PurchaseOrderController extends Controller
{
    /**
     * List purchase orders
     */
    public function index(): array
    {
        $params = $this->getQueryParams();
        $page = (int) ($params['page'] ?? 1);
        $perPage = (int) ($params['per_page'] ?? 15);
        $status = $params['status'] ?? null;
        $vendorId = $params['vendor_id'] ?? null;
        $projectId = $params['project_id'] ?? null;

        $tenantId = $this->db->getTenantId();
        $conditions = ["po.tenant_id = ?"];
        $bindings = [$tenantId];

        if ($status) {
            $conditions[] = "po.status = ?";
            $bindings[] = $status;
        }
        if ($vendorId) {
            $conditions[] = "po.vendor_id = ?";
            $bindings[] = $vendorId;
        }
        if ($projectId) {
            $conditions[] = "po.project_id = ?";
            $bindings[] = $projectId;
        }

        $where = implode(' AND ', $conditions);
        $offset = ($page - 1) * $perPage;

        $countResult = $this->db->fetch(
            "SELECT COUNT(*) as total FROM purchase_orders po WHERE {$where}",
            $bindings
        );
        $total = (int) $countResult['total'];

        $orders = $this->db->fetchAll(
            "SELECT 
                po.*,
                v.name as vendor_name,
                p.name as project_name
             FROM purchase_orders po
             LEFT JOIN vendors v ON po.vendor_id = v.id
             LEFT JOIN projects p ON po.project_id = p.id
             WHERE {$where}
             ORDER BY po.created_at DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $bindings
        );

        return $this->paginate($orders, $total, $page, $perPage);
    }

    /**
     * Create purchase order
     */
    public function store(): array
    {
        $data = $this->getRequestData();
        $this->validate($data, [
            'vendor_id' => 'required',
            'order_date' => 'required'
        ]);

        $tenantId = $this->db->getTenantId();

        $lastNum = $this->db->fetch(
            "SELECT MAX(CAST(SUBSTRING(po_number, 4) AS UNSIGNED)) as last_num 
             FROM purchase_orders WHERE tenant_id = ? AND po_number LIKE 'PO-%'",
            [$tenantId]
        );
        $number = 'PO-' . str_pad(($lastNum['last_num'] ?? 0) + 1, 4, '0', STR_PAD_LEFT);

        $this->db->insert('purchase_orders', [
            'tenant_id' => $tenantId,
            'vendor_id' => $data['vendor_id'],
            'project_id' => $data['project_id'] ?? null,
            'po_number' => $number,
            'order_date' => $data['order_date'],
            'expected_date' => $data['expected_date'] ?? null,
            'subtotal' => 0,
            'tax_rate' => $data['tax_rate'] ?? 0,
            'tax_amount' => 0,
            'total_amount' => 0,
            'status' => 'draft',
            'notes' => $data['notes'] ?? null,
            'shipping_address' => $data['shipping_address'] ?? null,
            'created_by' => $this->getAuthUser()['id'] ?? null
        ]);

        return $this->success(['id' => $this->db->lastInsertId(), 'po_number' => $number], 'Purchase Order created', 201);
    }

    /**
     * Get single purchase order with items
     */
    public function show(string $id): array
    {
        $tenantId = $this->db->getTenantId();

        $po = $this->db->fetch(
            "SELECT po.*, v.name as vendor_name, v.email as vendor_email, p.name as project_name
             FROM purchase_orders po
             LEFT JOIN vendors v ON po.vendor_id = v.id
             LEFT JOIN projects p ON po.project_id = p.id
             WHERE po.id = ? AND po.tenant_id = ?",
            [$id, $tenantId]
        );

        if (!$po) {
            return $this->error('Purchase Order not found', 404);
        }

        $po['items'] = $this->db->fetchAll(
            "SELECT * FROM purchase_order_items WHERE purchase_order_id = ? ORDER BY id",
            [$id]
        );

        return $this->success($po);
    }

    /**
     * Update purchase order
     */
    public function update(string $id): array
    {
        $data = $this->getRequestData();
        $tenantId = $this->db->getTenantId();

        $po = $this->db->fetch(
            "SELECT * FROM purchase_orders WHERE id = ? AND tenant_id = ?",
            [$id, $tenantId]
        );

        if (!$po) {
            return $this->error('Purchase Order not found', 404);
        }

        $updateData = [];
        $allowedFields = [
            'vendor_id',
            'project_id',
            'order_date',
            'expected_date',
            'tax_rate',
            'status',
            'notes',
            'shipping_address'
        ];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
            }
        }

        if (!empty($updateData)) {
            $this->db->update('purchase_orders', $updateData, ['id' => $id]);
        }

        return $this->success(null, 'Purchase Order updated');
    }

    /**
     * Delete purchase order
     */
    public function destroy(string $id): array
    {
        $tenantId = $this->db->getTenantId();

        $po = $this->db->fetch(
            "SELECT * FROM purchase_orders WHERE id = ? AND tenant_id = ?",
            [$id, $tenantId]
        );

        if (!$po) {
            return $this->error('Purchase Order not found', 404);
        }

        if ($po['status'] === 'received') {
            return $this->error('Cannot delete received order', 400);
        }

        $this->db->delete('purchase_orders', ['id' => $id]);

        return $this->success(null, 'Purchase Order deleted');
    }

    /**
     * Add item to purchase order
     */
    public function addItem(string $id): array
    {
        $data = $this->getRequestData();
        $this->validate($data, ['description' => 'required']);

        $tenantId = $this->db->getTenantId();
        $po = $this->db->fetch(
            "SELECT * FROM purchase_orders WHERE id = ? AND tenant_id = ?",
            [$id, $tenantId]
        );

        if (!$po) {
            return $this->error('Purchase Order not found', 404);
        }

        $quantity = (float) ($data['quantity'] ?? 1);
        $unitPrice = (float) ($data['unit_price'] ?? 0);
        $total = $quantity * $unitPrice;

        $this->db->insert('purchase_order_items', [
            'purchase_order_id' => $id,
            'inventory_item_id' => $data['inventory_item_id'] ?? null,
            'description' => $data['description'],
            'quantity' => $quantity,
            'unit' => $data['unit'] ?? 'unit',
            'unit_price' => $unitPrice,
            'total' => $total,
            'received_quantity' => 0
        ]);

        $this->recalculateTotals($id);

        return $this->success(['id' => $this->db->lastInsertId()], 'Item added', 201);
    }

    /**
     * Update purchase order item
     */
    public function updateItem(string $id, string $itemId): array
    {
        $data = $this->getRequestData();
        $tenantId = $this->db->getTenantId();

        $po = $this->db->fetch(
            "SELECT * FROM purchase_orders WHERE id = ? AND tenant_id = ?",
            [$id, $tenantId]
        );

        if (!$po) {
            return $this->error('Purchase Order not found', 404);
        }

        $quantity = (float) ($data['quantity'] ?? 1);
        $unitPrice = (float) ($data['unit_price'] ?? 0);
        $total = $quantity * $unitPrice;

        $this->db->update('purchase_order_items', [
            'description' => $data['description'] ?? '',
            'quantity' => $quantity,
            'unit' => $data['unit'] ?? 'unit',
            'unit_price' => $unitPrice,
            'total' => $total
        ], ['id' => $itemId, 'purchase_order_id' => $id]);

        $this->recalculateTotals($id);

        return $this->success(null, 'Item updated');
    }

    /**
     * Delete purchase order item
     */
    public function deleteItem(string $id, string $itemId): array
    {
        $tenantId = $this->db->getTenantId();

        $po = $this->db->fetch(
            "SELECT * FROM purchase_orders WHERE id = ? AND tenant_id = ?",
            [$id, $tenantId]
        );

        if (!$po) {
            return $this->error('Purchase Order not found', 404);
        }

        $this->db->delete('purchase_order_items', ['id' => $itemId, 'purchase_order_id' => $id]);
        $this->recalculateTotals($id);

        return $this->success(null, 'Item deleted');
    }

    /**
     * Send purchase order to vendor
     */
    public function send(string $id): array
    {
        $tenantId = $this->db->getTenantId();

        $po = $this->db->fetch(
            "SELECT * FROM purchase_orders WHERE id = ? AND tenant_id = ?",
            [$id, $tenantId]
        );

        if (!$po) {
            return $this->error('Purchase Order not found', 404);
        }

        $this->db->update('purchase_orders', ['status' => 'sent'], ['id' => $id]);

        return $this->success(null, 'Purchase Order sent to vendor');
    }

    /**
     * Mark purchase order as received
     */
    public function receive(string $id): array
    {
        $data = $this->getRequestData();
        $tenantId = $this->db->getTenantId();

        $po = $this->db->fetch(
            "SELECT * FROM purchase_orders WHERE id = ? AND tenant_id = ?",
            [$id, $tenantId]
        );

        if (!$po) {
            return $this->error('Purchase Order not found', 404);
        }

        // Update all items as received (full quantity)
        $this->db->query(
            "UPDATE purchase_order_items SET received_quantity = quantity WHERE purchase_order_id = ?",
            [$id]
        );

        $this->db->update('purchase_orders', [
            'status' => 'received',
            'received_date' => date('Y-m-d')
        ], ['id' => $id]);

        return $this->success(null, 'Purchase Order marked as received');
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
                SUM(CASE WHEN status = 'received' THEN 1 ELSE 0 END) as received_count,
                SUM(total_amount) as total_value,
                SUM(CASE WHEN status = 'sent' THEN total_amount ELSE 0 END) as pending_value
             FROM purchase_orders WHERE tenant_id = ?",
            [$tenantId]
        );

        return $this->success($stats);
    }

    /**
     * Recalculate purchase order totals
     */
    private function recalculateTotals(int $poId): void
    {
        $po = $this->db->fetch(
            "SELECT tax_rate FROM purchase_orders WHERE id = ?",
            [$poId]
        );

        $subtotal = $this->db->fetch(
            "SELECT COALESCE(SUM(total), 0) as subtotal FROM purchase_order_items WHERE purchase_order_id = ?",
            [$poId]
        );

        $sub = (float) $subtotal['subtotal'];
        $taxRate = (float) ($po['tax_rate'] ?? 0);
        $taxAmount = $sub * ($taxRate / 100);
        $total = $sub + $taxAmount;

        $this->db->update('purchase_orders', [
            'subtotal' => $sub,
            'tax_amount' => $taxAmount,
            'total_amount' => $total
        ], ['id' => $poId]);
    }
}
