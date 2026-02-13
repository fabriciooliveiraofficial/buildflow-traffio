<?php
/**
 * Supplier API Controller
 */

namespace App\Controllers\Api;

use App\Core\Controller;

class SupplierController extends Controller
{
    /**
     * List suppliers
     */
    public function index(): array
    {
        $params = $this->getQueryParams();
        $page = (int) ($params['page'] ?? 1);
        $perPage = (int) ($params['per_page'] ?? 15);
        $status = $params['status'] ?? null;
        $search = $params['search'] ?? null;

        $tenantId = $this->db->getTenantId();
        $conditions = ["s.tenant_id = ?"];
        $bindings = [$tenantId];

        if ($status) {
            $conditions[] = "s.status = ?";
            $bindings[] = $status;
        }

        if ($search) {
            $conditions[] = "(s.name LIKE ? OR s.email LIKE ? OR s.contact_person LIKE ?)";
            $bindings[] = "%{$search}%";
            $bindings[] = "%{$search}%";
            $bindings[] = "%{$search}%";
        }

        $where = implode(' AND ', $conditions);
        $offset = ($page - 1) * $perPage;

        $countResult = $this->db->fetch(
            "SELECT COUNT(*) as total FROM suppliers s WHERE {$where}",
            $bindings
        );
        $total = (int) $countResult['total'];

        $suppliers = $this->db->fetchAll(
            "SELECT 
                s.*,
                (SELECT COUNT(*) FROM inventory_items WHERE supplier_id = s.id) as item_count,
                (SELECT COALESCE(SUM(quantity * unit_cost), 0) FROM inventory_items WHERE supplier_id = s.id) as total_inventory_value
             FROM suppliers s
             WHERE {$where}
             ORDER BY s.name
             LIMIT {$perPage} OFFSET {$offset}",
            $bindings
        );

        return $this->paginate($suppliers, $total, $page, $perPage);
    }

    /**
     * Create supplier
     */
    public function store(): array
    {
        $data = $this->validate([
            'name' => 'required',
        ]);

        $input = $this->getJsonInput();

        $supplierId = $this->db->insert('suppliers', [
            'name' => $data['name'],
            'contact_person' => $input['contact_person'] ?? null,
            'email' => $input['email'] ?? null,
            'phone' => $input['phone'] ?? null,
            'address' => $input['address'] ?? null,
            'city' => $input['city'] ?? null,
            'state' => $input['state'] ?? null,
            'zip_code' => $input['zip_code'] ?? null,
            'country' => $input['country'] ?? 'USA',
            'website' => $input['website'] ?? null,
            'payment_terms' => $input['payment_terms'] ?? 30,
            'tax_id' => $input['tax_id'] ?? null,
            'notes' => $input['notes'] ?? null,
            'rating' => $input['rating'] ?? null,
            'status' => $input['status'] ?? 'active',
        ]);

        $supplier = $this->db->fetch("SELECT * FROM suppliers WHERE id = ?", [$supplierId]);

        return $this->success($supplier, 'Supplier created', 201);
    }

    /**
     * Get single supplier
     */
    public function show(string $id): array
    {
        $supplier = $this->db->fetch(
            "SELECT * FROM suppliers WHERE id = ? AND tenant_id = ?",
            [$id, $this->db->getTenantId()]
        );

        if (!$supplier) {
            $this->error('Supplier not found', 404);
        }

        // Get supplier's inventory items
        $items = $this->db->fetchAll(
            "SELECT id, name, sku, category, quantity, unit_cost 
             FROM inventory_items 
             WHERE supplier_id = ? AND tenant_id = ?
             ORDER BY name",
            [$id, $this->db->getTenantId()]
        );

        $supplier['items'] = $items;

        return $this->success($supplier);
    }

    /**
     * Update supplier
     */
    public function update(string $id): array
    {
        $supplier = $this->db->fetch(
            "SELECT * FROM suppliers WHERE id = ? AND tenant_id = ?",
            [$id, $this->db->getTenantId()]
        );

        if (!$supplier) {
            $this->error('Supplier not found', 404);
        }

        $input = $this->getJsonInput();
        $allowedFields = [
            'name',
            'contact_person',
            'email',
            'phone',
            'address',
            'city',
            'state',
            'zip_code',
            'country',
            'website',
            'payment_terms',
            'tax_id',
            'notes',
            'rating',
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
            $this->db->update('suppliers', $updateData, ['id' => $id]);
        }

        $updated = $this->db->fetch("SELECT * FROM suppliers WHERE id = ?", [$id]);

        return $this->success($updated, 'Supplier updated');
    }

    /**
     * Delete supplier
     */
    public function destroy(string $id): array
    {
        $supplier = $this->db->fetch(
            "SELECT * FROM suppliers WHERE id = ? AND tenant_id = ?",
            [$id, $this->db->getTenantId()]
        );

        if (!$supplier) {
            $this->error('Supplier not found', 404);
        }

        // Check for linked inventory items
        $itemCount = $this->db->fetch(
            "SELECT COUNT(*) as count FROM inventory_items WHERE supplier_id = ?",
            [$id]
        );

        if ($itemCount['count'] > 0) {
            // Soft delete
            $this->db->update('suppliers', ['status' => 'inactive'], ['id' => $id]);
            return $this->success(null, 'Supplier deactivated (has linked items)');
        }

        $this->db->delete('suppliers', ['id' => $id]);

        return $this->success(null, 'Supplier deleted');
    }
}
