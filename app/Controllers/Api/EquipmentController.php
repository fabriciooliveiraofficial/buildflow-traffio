<?php
/**
 * Equipment API Controller
 */

namespace App\Controllers\Api;

use App\Core\Controller;

class EquipmentController extends Controller
{
    /**
     * List equipment
     */
    public function index(): array
    {
        $params = $this->getQueryParams();
        $page = (int) ($params['page'] ?? 1);
        $perPage = (int) ($params['per_page'] ?? 15);
        $status = $params['status'] ?? null;
        $category = $params['category'] ?? null;

        $tenantId = $this->db->getTenantId();
        $conditions = ["tenant_id = ?"];
        $bindings = [$tenantId];

        if ($status) {
            $conditions[] = "status = ?";
            $bindings[] = $status;
        }
        if ($category) {
            $conditions[] = "category = ?";
            $bindings[] = $category;
        }

        $where = implode(' AND ', $conditions);
        $offset = ($page - 1) * $perPage;

        $countResult = $this->db->fetch(
            "SELECT COUNT(*) as total FROM equipment WHERE {$where}",
            $bindings
        );
        $total = (int) $countResult['total'];

        $equipment = $this->db->fetchAll(
            "SELECT * FROM equipment WHERE {$where} ORDER BY name ASC LIMIT {$perPage} OFFSET {$offset}",
            $bindings
        );

        return $this->paginate($equipment, $total, $page, $perPage);
    }

    /**
     * Create equipment
     */
    public function store(): array
    {
        $data = $this->getRequestData();
        $this->validate($data, [
            'name' => 'required'
        ]);

        $tenantId = $this->db->getTenantId();

        $this->db->insert('equipment', [
            'tenant_id' => $tenantId,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'category' => $data['category'] ?? null,
            'serial_number' => $data['serial_number'] ?? null,
            'model' => $data['model'] ?? null,
            'manufacturer' => $data['manufacturer'] ?? null,
            'purchase_date' => $data['purchase_date'] ?? null,
            'purchase_price' => $data['purchase_price'] ?? 0,
            'current_value' => $data['current_value'] ?? $data['purchase_price'] ?? 0,
            'location' => $data['location'] ?? null,
            'status' => 'available',
            'notes' => $data['notes'] ?? null
        ]);

        return $this->success(['id' => $this->db->lastInsertId()], 'Equipment added', 201);
    }

    /**
     * Get single equipment with maintenance history
     */
    public function show(string $id): array
    {
        $tenantId = $this->db->getTenantId();

        $equipment = $this->db->fetch(
            "SELECT * FROM equipment WHERE id = ? AND tenant_id = ?",
            [$id, $tenantId]
        );

        if (!$equipment) {
            return $this->error('Equipment not found', 404);
        }

        // Get maintenance history
        $equipment['maintenance'] = $this->db->fetchAll(
            "SELECT * FROM equipment_maintenance WHERE equipment_id = ? ORDER BY maintenance_date DESC LIMIT 10",
            [$id]
        );

        return $this->success($equipment);
    }

    /**
     * Update equipment
     */
    public function update(string $id): array
    {
        $data = $this->getRequestData();
        $tenantId = $this->db->getTenantId();

        $equipment = $this->db->fetch(
            "SELECT * FROM equipment WHERE id = ? AND tenant_id = ?",
            [$id, $tenantId]
        );

        if (!$equipment) {
            return $this->error('Equipment not found', 404);
        }

        $allowedFields = [
            'name',
            'description',
            'category',
            'serial_number',
            'model',
            'manufacturer',
            'purchase_date',
            'purchase_price',
            'current_value',
            'location',
            'status',
            'notes'
        ];

        $updateData = [];
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
            }
        }

        if (!empty($updateData)) {
            $this->db->update('equipment', $updateData, ['id' => $id]);
        }

        return $this->success(null, 'Equipment updated');
    }

    /**
     * Delete equipment
     */
    public function destroy(string $id): array
    {
        $tenantId = $this->db->getTenantId();

        $equipment = $this->db->fetch(
            "SELECT * FROM equipment WHERE id = ? AND tenant_id = ?",
            [$id, $tenantId]
        );

        if (!$equipment) {
            return $this->error('Equipment not found', 404);
        }

        $this->db->delete('equipment', ['id' => $id]);
        return $this->success(null, 'Equipment deleted');
    }

    /**
     * Add maintenance record
     */
    public function addMaintenance(string $id): array
    {
        $data = $this->getRequestData();
        $this->validate($data, ['maintenance_type' => 'required']);

        $tenantId = $this->db->getTenantId();

        $equipment = $this->db->fetch(
            "SELECT * FROM equipment WHERE id = ? AND tenant_id = ?",
            [$id, $tenantId]
        );

        if (!$equipment) {
            return $this->error('Equipment not found', 404);
        }

        $this->db->insert('equipment_maintenance', [
            'equipment_id' => $id,
            'maintenance_date' => $data['maintenance_date'] ?? date('Y-m-d'),
            'maintenance_type' => $data['maintenance_type'],
            'description' => $data['description'] ?? null,
            'cost' => $data['cost'] ?? 0,
            'performed_by' => $data['performed_by'] ?? null,
            'next_maintenance_date' => $data['next_maintenance_date'] ?? null,
            'notes' => $data['notes'] ?? null
        ]);

        // Update equipment status and next maintenance
        if ($data['next_maintenance_date'] ?? null) {
            $this->db->update('equipment', [
                'next_maintenance' => $data['next_maintenance_date'],
                'status' => 'available'
            ], ['id' => $id]);
        }

        return $this->success(['id' => $this->db->lastInsertId()], 'Maintenance record added', 201);
    }
}
