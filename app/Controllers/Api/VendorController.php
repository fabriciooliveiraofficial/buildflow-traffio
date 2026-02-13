<?php
/**
 * Vendor API Controller
 */

namespace App\Controllers\Api;

use App\Core\Controller;

class VendorController extends Controller
{
    /**
     * List vendors
     */
    public function index(): array
    {
        $params = $this->getQueryParams();
        $page = (int) ($params['page'] ?? 1);
        $perPage = (int) ($params['per_page'] ?? 15);
        $search = $params['search'] ?? null;

        $tenantId = $this->db->getTenantId();
        $conditions = ["tenant_id = ?"];
        $bindings = [$tenantId];

        if ($search) {
            $conditions[] = "(name LIKE ? OR email LIKE ? OR company LIKE ?)";
            $bindings[] = "%{$search}%";
            $bindings[] = "%{$search}%";
            $bindings[] = "%{$search}%";
        }

        $where = implode(' AND ', $conditions);
        $offset = ($page - 1) * $perPage;

        $countResult = $this->db->fetch(
            "SELECT COUNT(*) as total FROM vendors WHERE {$where}",
            $bindings
        );
        $total = (int) $countResult['total'];

        $vendors = $this->db->fetchAll(
            "SELECT * FROM vendors WHERE {$where} ORDER BY name ASC LIMIT {$perPage} OFFSET {$offset}",
            $bindings
        );

        return $this->paginate($vendors, $total, $page, $perPage);
    }

    /**
     * Create vendor
     */
    public function store(): array
    {
        $data = $this->getRequestData();
        $this->validate($data, [
            'name' => 'required'
        ]);

        $tenantId = $this->db->getTenantId();

        $this->db->insert('vendors', [
            'tenant_id' => $tenantId,
            'name' => $data['name'],
            'company' => $data['company'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'state' => $data['state'] ?? null,
            'zip' => $data['zip'] ?? null,
            'country' => $data['country'] ?? null,
            'website' => $data['website'] ?? null,
            'payment_terms' => $data['payment_terms'] ?? 'Net 30',
            'category' => $data['category'] ?? null,
            'notes' => $data['notes'] ?? null,
            'status' => 'active'
        ]);

        return $this->success(['id' => $this->db->lastInsertId()], 'Vendor created', 201);
    }

    /**
     * Get single vendor
     */
    public function show(string $id): array
    {
        $tenantId = $this->db->getTenantId();

        $vendor = $this->db->fetch(
            "SELECT * FROM vendors WHERE id = ? AND tenant_id = ?",
            [$id, $tenantId]
        );

        if (!$vendor) {
            return $this->error('Vendor not found', 404);
        }

        // Get PO stats
        $poStats = $this->db->fetch(
            "SELECT COUNT(*) as po_count, COALESCE(SUM(total_amount), 0) as total_spent
             FROM purchase_orders WHERE vendor_id = ?",
            [$id]
        );
        $vendor['po_count'] = $poStats['po_count'];
        $vendor['total_spent'] = $poStats['total_spent'];

        return $this->success($vendor);
    }

    /**
     * Update vendor
     */
    public function update(string $id): array
    {
        $data = $this->getRequestData();
        $tenantId = $this->db->getTenantId();

        $vendor = $this->db->fetch(
            "SELECT * FROM vendors WHERE id = ? AND tenant_id = ?",
            [$id, $tenantId]
        );

        if (!$vendor) {
            return $this->error('Vendor not found', 404);
        }

        $allowedFields = [
            'name',
            'company',
            'email',
            'phone',
            'address',
            'city',
            'state',
            'zip',
            'country',
            'website',
            'payment_terms',
            'category',
            'notes',
            'status'
        ];

        $updateData = [];
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
            }
        }

        if (!empty($updateData)) {
            $this->db->update('vendors', $updateData, ['id' => $id]);
        }

        return $this->success(null, 'Vendor updated');
    }

    /**
     * Delete vendor
     */
    public function destroy(string $id): array
    {
        $tenantId = $this->db->getTenantId();

        $vendor = $this->db->fetch(
            "SELECT * FROM vendors WHERE id = ? AND tenant_id = ?",
            [$id, $tenantId]
        );

        if (!$vendor) {
            return $this->error('Vendor not found', 404);
        }

        // Check for existing POs
        $poCount = $this->db->fetch(
            "SELECT COUNT(*) as cnt FROM purchase_orders WHERE vendor_id = ?",
            [$id]
        );

        if ($poCount['cnt'] > 0) {
            // Soft delete by marking inactive
            $this->db->update('vendors', ['status' => 'inactive'], ['id' => $id]);
            return $this->success(null, 'Vendor marked as inactive (has existing orders)');
        }

        $this->db->delete('vendors', ['id' => $id]);
        return $this->success(null, 'Vendor deleted');
    }
}
