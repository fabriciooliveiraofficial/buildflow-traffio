<?php
/**
 * Inventory API Controller
 */

namespace App\Controllers\Api;

use App\Core\Controller;

class InventoryController extends Controller
{
    /**
     * List inventory items
     */
    public function index(): array
    {
        $params = $this->getQueryParams();
        $page = (int) ($params['page'] ?? 1);
        $perPage = (int) ($params['per_page'] ?? 15);
        $category = $params['category'] ?? null;
        $status = $params['status'] ?? null;
        $lowStock = $params['low_stock'] ?? null;
        $search = $params['search'] ?? null;

        $tenantId = $this->db->getTenantId();
        $conditions = ["i.tenant_id = ?"];
        $bindings = [$tenantId];

        if ($category) {
            $conditions[] = "i.category = ?";
            $bindings[] = $category;
        }

        if ($status) {
            $conditions[] = "i.status = ?";
            $bindings[] = $status;
        }

        if ($lowStock === 'true') {
            $conditions[] = "i.quantity <= i.min_quantity";
        }

        if ($search) {
            $conditions[] = "(i.name LIKE ? OR i.sku LIKE ? OR i.barcode LIKE ?)";
            $bindings[] = "%{$search}%";
            $bindings[] = "%{$search}%";
            $bindings[] = "%{$search}%";
        }

        $where = implode(' AND ', $conditions);
        $offset = ($page - 1) * $perPage;

        $countResult = $this->db->fetch(
            "SELECT COUNT(*) as total FROM inventory_items i WHERE {$where}",
            $bindings
        );
        $total = (int) $countResult['total'];

        $items = $this->db->fetchAll(
            "SELECT 
                i.*,
                s.name as supplier_name,
                (i.quantity * i.unit_cost) as total_value,
                CASE WHEN i.quantity <= i.min_quantity THEN 1 ELSE 0 END as is_low_stock
             FROM inventory_items i
             LEFT JOIN suppliers s ON i.supplier_id = s.id
             WHERE {$where}
             ORDER BY i.name
             LIMIT {$perPage} OFFSET {$offset}",
            $bindings
        );

        return $this->paginate($items, $total, $page, $perPage);
    }

    /**
     * Create inventory item
     */
    public function store(): array
    {
        $data = $this->validate([
            'name' => 'required',
            'category' => 'required',
        ]);

        $input = $this->getJsonInput();

        // Generate SKU if not provided
        $sku = $input['sku'] ?? null;
        if (!$sku) {
            $prefix = strtoupper(substr($data['category'], 0, 3));
            $count = $this->db->fetch(
                "SELECT COUNT(*) as count FROM inventory_items WHERE tenant_id = ?",
                [$this->db->getTenantId()]
            );
            $sku = $prefix . '-' . str_pad(($count['count'] + 1), 5, '0', STR_PAD_LEFT);
        }

        $itemId = $this->db->insert('inventory_items', [
            'name' => $data['name'],
            'sku' => $sku,
            'barcode' => $input['barcode'] ?? null,
            'category' => $data['category'],
            'description' => $input['description'] ?? null,
            'unit' => $input['unit'] ?? 'piece',
            'quantity' => $input['quantity'] ?? 0,
            'min_quantity' => $input['min_quantity'] ?? 5,
            'max_quantity' => $input['max_quantity'] ?? null,
            'unit_cost' => $input['unit_cost'] ?? 0,
            'unit_price' => $input['unit_price'] ?? 0,
            'supplier_id' => $input['supplier_id'] ?? null,
            'location' => $input['location'] ?? null,
            'notes' => $input['notes'] ?? null,
            'status' => $input['status'] ?? 'active',
        ]);

        // Log activity
        $this->logActivity('inventory.create', 'inventory_items', $itemId, [
            'name' => $data['name'],
            'quantity' => $input['quantity'] ?? 0,
        ]);

        $item = $this->db->fetch("SELECT * FROM inventory_items WHERE id = ?", [$itemId]);

        return $this->success($item, 'Inventory item created', 201);
    }

    /**
     * Get single inventory item
     */
    public function show(string $id): array
    {
        $item = $this->db->fetch(
            "SELECT 
                i.*,
                s.name as supplier_name,
                s.email as supplier_email,
                s.phone as supplier_phone
             FROM inventory_items i
             LEFT JOIN suppliers s ON i.supplier_id = s.id
             WHERE i.id = ? AND i.tenant_id = ?",
            [$id, $this->db->getTenantId()]
        );

        if (!$item) {
            $this->error('Inventory item not found', 404);
        }

        // Get recent transactions
        $transactions = $this->db->fetchAll(
            "SELECT * FROM inventory_transactions 
             WHERE item_id = ? 
             ORDER BY created_at DESC 
             LIMIT 20",
            [$id]
        );

        $item['transactions'] = $transactions;

        return $this->success($item);
    }

    /**
     * Update inventory item
     */
    public function update(string $id): array
    {
        $item = $this->db->fetch(
            "SELECT * FROM inventory_items WHERE id = ? AND tenant_id = ?",
            [$id, $this->db->getTenantId()]
        );

        if (!$item) {
            $this->error('Inventory item not found', 404);
        }

        $input = $this->getJsonInput();
        $allowedFields = [
            'name',
            'sku',
            'barcode',
            'category',
            'description',
            'unit',
            'min_quantity',
            'max_quantity',
            'unit_cost',
            'unit_price',
            'supplier_id',
            'location',
            'notes',
            'status'
        ];

        $updateData = [];
        foreach ($allowedFields as $field) {
            if (isset($input[$field])) {
                $updateData[$field] = $input[$field];
            }
        }

        if (!empty($updateData)) {
            $updateData['updated_at'] = date('Y-m-d H:i:s');
            $this->db->update('inventory_items', $updateData, ['id' => $id]);
        }

        $updated = $this->db->fetch("SELECT * FROM inventory_items WHERE id = ?", [$id]);

        return $this->success($updated, 'Inventory item updated');
    }

    /**
     * Delete inventory item
     */
    public function destroy(string $id): array
    {
        $item = $this->db->fetch(
            "SELECT * FROM inventory_items WHERE id = ? AND tenant_id = ?",
            [$id, $this->db->getTenantId()]
        );

        if (!$item) {
            $this->error('Inventory item not found', 404);
        }

        // Soft delete by setting status
        $this->db->update('inventory_items', [
            'status' => 'discontinued',
            'updated_at' => date('Y-m-d H:i:s'),
        ], ['id' => $id]);

        return $this->success(null, 'Inventory item discontinued');
    }

    /**
     * Adjust inventory quantity
     */
    public function adjust(string $id): array
    {
        $item = $this->db->fetch(
            "SELECT * FROM inventory_items WHERE id = ? AND tenant_id = ?",
            [$id, $this->db->getTenantId()]
        );

        if (!$item) {
            $this->error('Inventory item not found', 404);
        }

        $input = $this->getJsonInput();
        $adjustmentType = $input['type'] ?? 'add'; // add, remove, set
        $quantity = (float) ($input['quantity'] ?? 0);
        $reason = $input['reason'] ?? 'Manual adjustment';
        $projectId = $input['project_id'] ?? null;

        $previousQty = (float) $item['quantity'];
        $newQty = $previousQty;

        switch ($adjustmentType) {
            case 'add':
                $newQty = $previousQty + $quantity;
                break;
            case 'remove':
                $newQty = max(0, $previousQty - $quantity);
                break;
            case 'set':
                $newQty = $quantity;
                break;
        }

        $this->db->beginTransaction();

        try {
            // Update quantity
            $this->db->update('inventory_items', [
                'quantity' => $newQty,
                'updated_at' => date('Y-m-d H:i:s'),
            ], ['id' => $id]);

            // Record transaction
            $this->db->query(
                "INSERT INTO inventory_transactions 
                 (tenant_id, item_id, type, quantity, previous_quantity, new_quantity, 
                  reason, project_id, user_id, created_at) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())",
                [
                    $this->db->getTenantId(),
                    $id,
                    $adjustmentType,
                    $quantity,
                    $previousQty,
                    $newQty,
                    $reason,
                    $projectId,
                    $_SESSION['user']['id'] ?? null,
                ]
            );

            $this->db->commit();

            // Check for low stock alert
            if ($newQty <= $item['min_quantity']) {
                $this->createLowStockAlert($item, $newQty);
            }

            $updated = $this->db->fetch("SELECT * FROM inventory_items WHERE id = ?", [$id]);

            return $this->success([
                'item' => $updated,
                'adjustment' => [
                    'type' => $adjustmentType,
                    'quantity' => $quantity,
                    'previous' => $previousQty,
                    'new' => $newQty,
                ],
            ], 'Inventory adjusted');

        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Transfer inventory between locations/projects
     */
    public function transfer(): array
    {
        $input = $this->getJsonInput();

        $itemId = $input['item_id'] ?? null;
        $quantity = (float) ($input['quantity'] ?? 0);
        $fromProject = $input['from_project_id'] ?? null;
        $toProject = $input['to_project_id'] ?? null;
        $fromLocation = $input['from_location'] ?? null;
        $toLocation = $input['to_location'] ?? null;

        if (!$itemId || $quantity <= 0) {
            $this->error('Item ID and quantity required', 422);
        }

        $item = $this->db->fetch(
            "SELECT * FROM inventory_items WHERE id = ? AND tenant_id = ?",
            [$itemId, $this->db->getTenantId()]
        );

        if (!$item) {
            $this->error('Inventory item not found', 404);
        }

        if ($item['quantity'] < $quantity) {
            $this->error('Insufficient quantity available', 422);
        }

        // Log the transfer
        $this->db->query(
            "INSERT INTO inventory_transactions 
             (tenant_id, item_id, type, quantity, previous_quantity, new_quantity,
              reason, project_id, user_id, created_at) 
             VALUES (?, ?, 'transfer', ?, ?, ?, ?, ?, ?, NOW())",
            [
                $this->db->getTenantId(),
                $itemId,
                $quantity,
                $item['quantity'],
                $item['quantity'],
                "Transfer from {$fromLocation} to {$toLocation}",
                $toProject,
                $_SESSION['user']['id'] ?? null,
            ]
        );

        // Update location if provided
        if ($toLocation) {
            $this->db->update('inventory_items', [
                'location' => $toLocation,
                'updated_at' => date('Y-m-d H:i:s'),
            ], ['id' => $itemId]);
        }

        return $this->success(null, 'Transfer recorded');
    }

    /**
     * Get low stock items
     */
    public function lowStock(): array
    {
        $items = $this->db->fetchAll(
            "SELECT 
                i.*,
                s.name as supplier_name,
                s.email as supplier_email
             FROM inventory_items i
             LEFT JOIN suppliers s ON i.supplier_id = s.id
             WHERE i.tenant_id = ? 
             AND i.quantity <= i.min_quantity 
             AND i.status = 'active'
             ORDER BY (i.min_quantity - i.quantity) DESC",
            [$this->db->getTenantId()]
        );

        return $this->success($items);
    }

    /**
     * Get inventory categories
     */
    public function categories(): array
    {
        $categories = $this->db->fetchAll(
            "SELECT 
                category,
                COUNT(*) as item_count,
                SUM(quantity) as total_quantity,
                SUM(quantity * unit_cost) as total_value
             FROM inventory_items 
             WHERE tenant_id = ? AND status = 'active'
             GROUP BY category
             ORDER BY category",
            [$this->db->getTenantId()]
        );

        return $this->success($categories);
    }

    /**
     * Get inventory valuation report
     */
    public function valuation(): array
    {
        $summary = $this->db->fetch(
            "SELECT 
                COUNT(*) as total_items,
                SUM(quantity) as total_units,
                SUM(quantity * unit_cost) as total_cost_value,
                SUM(quantity * unit_price) as total_retail_value
             FROM inventory_items 
             WHERE tenant_id = ? AND status = 'active'",
            [$this->db->getTenantId()]
        );

        $byCategory = $this->db->fetchAll(
            "SELECT 
                category,
                COUNT(*) as items,
                SUM(quantity) as units,
                SUM(quantity * unit_cost) as cost_value,
                SUM(quantity * unit_price) as retail_value
             FROM inventory_items 
             WHERE tenant_id = ? AND status = 'active'
             GROUP BY category
             ORDER BY cost_value DESC",
            [$this->db->getTenantId()]
        );

        return $this->success([
            'summary' => $summary,
            'by_category' => $byCategory,
        ]);
    }

    /**
     * Create low stock notification
     */
    private function createLowStockAlert(array $item, float $currentQty): void
    {
        $this->db->insert('notifications', [
            'tenant_id' => $this->db->getTenantId(),
            'type' => 'low_stock',
            'title' => 'Low Stock Alert',
            'message' => "Item '{$item['name']}' is running low. Current: {$currentQty}, Minimum: {$item['min_quantity']}",
            'data' => json_encode(['item_id' => $item['id'], 'sku' => $item['sku']]),
            'priority' => 'high',
        ]);
    }

    /**
     * Log activity
     */
    private function logActivity(string $action, string $entityType, int $entityId, array $data = []): void
    {
        $user = $_SESSION['user'] ?? null;

        $this->db->insert('activity_logs', [
            'tenant_id' => $this->db->getTenantId(),
            'user_id' => $user['id'] ?? null,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'old_values' => null,
            'new_values' => json_encode($data),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        ]);
    }
}
