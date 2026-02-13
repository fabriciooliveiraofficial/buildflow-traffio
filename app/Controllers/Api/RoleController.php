<?php
/**
 * Role API Controller
 * Manages roles and permissions
 */

namespace App\Controllers\Api;

use App\Core\Controller;

class RoleController extends Controller
{
    /**
     * Available permissions organized by module
     */
    private const AVAILABLE_PERMISSIONS = [
        'projects' => [
            'projects.view' => 'View Projects',
            'projects.create' => 'Create Projects',
            'projects.edit' => 'Edit Projects',
            'projects.delete' => 'Delete Projects',
        ],
        'clients' => [
            'clients.view' => 'View Clients',
            'clients.create' => 'Create Clients',
            'clients.edit' => 'Edit Clients',
            'clients.delete' => 'Delete Clients',
        ],
        'employees' => [
            'employees.view' => 'View Employees',
            'employees.create' => 'Create Employees',
            'employees.edit' => 'Edit Employees',
            'employees.delete' => 'Delete Employees',
        ],
        'expenses' => [
            'expenses.view' => 'View Expenses',
            'expenses.create' => 'Create Expenses',
            'expenses.edit' => 'Edit Expenses',
            'expenses.delete' => 'Delete Expenses',
            'expenses.approve' => 'Approve Expenses',
        ],
        'invoices' => [
            'invoices.view' => 'View Invoices',
            'invoices.create' => 'Create Invoices',
            'invoices.edit' => 'Edit Invoices',
            'invoices.delete' => 'Delete Invoices',
            'invoices.send' => 'Send Invoices',
        ],
        'reports' => [
            'reports.view' => 'View Reports',
            'reports.export' => 'Export Reports',
        ],
        'settings' => [
            'settings.view' => 'View Settings',
            'settings.edit' => 'Edit Settings',
            'roles.manage' => 'Manage Roles',
            'users.manage' => 'Manage Users',
        ],
    ];

    /**
     * List all roles
     */
    public function index(): array
    {
        $tenantId = $this->db->getTenantId();

        $roles = $this->db->fetchAll(
            "SELECT r.*, 
                    (SELECT COUNT(*) FROM users WHERE role_id = r.id) as user_count
             FROM roles r 
             WHERE r.tenant_id = ?
             ORDER BY r.name ASC",
            [$tenantId]
        );

        // Decode permissions JSON for each role
        foreach ($roles as &$role) {
            $role['permissions'] = $role['permissions'] ? json_decode($role['permissions'], true) : [];
        }

        return $this->success($roles);
    }

    /**
     * Get available permissions
     */
    public function permissions(): array
    {
        return $this->success(self::AVAILABLE_PERMISSIONS);
    }

    /**
     * Create a new role
     */
    public function store(): array
    {
        $input = $this->getJsonInput();

        if (empty($input['name'])) {
            return $this->error('Role name is required', 422);
        }

        $tenantId = $this->db->getTenantId();

        // Check if role name already exists for this tenant
        $existing = $this->db->fetch(
            "SELECT id FROM roles WHERE name = ? AND tenant_id = ?",
            [$input['name'], $tenantId]
        );

        if ($existing) {
            return $this->error('Role name already exists', 422);
        }

        $permissions = $input['permissions'] ?? [];

        $roleId = $this->db->insert('roles', [
            'tenant_id' => $tenantId,
            'name' => $input['name'],
            'display_name' => $input['description'] ?? $input['name'],
            'permissions' => json_encode($permissions),
            'is_system' => 0,
        ]);

        $role = $this->db->fetch("SELECT * FROM roles WHERE id = ?", [$roleId]);
        $role['permissions'] = json_decode($role['permissions'], true);

        return $this->success($role, 'Role created');
    }

    /**
     * Get a single role
     */
    public function show($id): array
    {
        $tenantId = $this->db->getTenantId();

        $role = $this->db->fetch(
            "SELECT * FROM roles WHERE id = ? AND (tenant_id = ? OR tenant_id IS NULL)",
            [$id, $tenantId]
        );

        if (!$role) {
            return $this->error('Role not found', 404);
        }

        $role['permissions'] = $role['permissions'] ? json_decode($role['permissions'], true) : [];

        // Get users with this role
        $role['users'] = $this->db->fetchAll(
            "SELECT id, first_name, last_name, email FROM users WHERE role_id = ? AND tenant_id = ?",
            [$id, $tenantId]
        );

        return $this->success($role);
    }

    /**
     * Update a role
     */
    public function update($id): array
    {
        $tenantId = $this->db->getTenantId();

        $role = $this->db->fetch(
            "SELECT * FROM roles WHERE id = ? AND (tenant_id = ? OR tenant_id IS NULL)",
            [$id, $tenantId]
        );

        if (!$role) {
            return $this->error('Role not found', 404);
        }

        // Prevent editing system roles name
        $input = $this->getJsonInput();

        $updateData = [];

        if (isset($input['name']) && !$role['is_system']) {
            // Check if name conflicts with another role
            $existing = $this->db->fetch(
                "SELECT id FROM roles WHERE name = ? AND id != ? AND tenant_id = ?",
                [$input['name'], $id, $tenantId]
            );

            if ($existing) {
                return $this->error('Role name already exists', 422);
            }

            $updateData['name'] = $input['name'];
        }

        if (isset($input['description'])) {
            $updateData['display_name'] = $input['description'];
        }

        if (isset($input['permissions'])) {
            $updateData['permissions'] = json_encode($input['permissions']);
        }

        if (!empty($updateData)) {
            $this->db->update('roles', $updateData, ['id' => $id]);
        }

        $role = $this->db->fetch("SELECT * FROM roles WHERE id = ?", [$id]);
        $role['permissions'] = json_decode($role['permissions'], true);

        return $this->success($role, 'Role updated');
    }

    /**
     * Delete a role
     */
    public function destroy($id): array
    {
        $tenantId = $this->db->getTenantId();

        $role = $this->db->fetch(
            "SELECT * FROM roles WHERE id = ? AND tenant_id = ?",
            [$id, $tenantId]
        );

        if (!$role) {
            return $this->error('Role not found', 404);
        }

        if ($role['is_system']) {
            return $this->error('Cannot delete system roles', 403);
        }

        // Check if any users have this role
        $userCount = $this->db->fetch(
            "SELECT COUNT(*) as count FROM users WHERE role_id = ?",
            [$id]
        );

        if ($userCount['count'] > 0) {
            return $this->error('Cannot delete role with assigned users. Reassign users first.', 422);
        }

        $this->db->delete('roles', ['id' => $id]);

        return $this->success(null, 'Role deleted');
    }

    /**
     * Assign a role to a user
     */
    public function assignUserRole($id): array
    {
        try {
            $tenantId = $this->db->getTenantId();

            $user = $this->db->fetch(
                "SELECT * FROM users WHERE id = ? AND tenant_id = ?",
                [$id, $tenantId]
            );

            if (!$user) {
                return $this->error('User not found', 404);
            }

            $input = $this->getJsonInput();

            if (empty($input['role_id'])) {
                return $this->error('Role ID is required', 422);
            }

            $role = $this->db->fetch(
                "SELECT * FROM roles WHERE id = ? AND (tenant_id = ? OR tenant_id IS NULL)",
                [$input['role_id'], $tenantId]
            );

            if (!$role) {
                return $this->error('Role not found', 404);
            }

            // Use direct query to avoid tenant_id auto-append issue
            $this->db->query(
                "UPDATE users SET role_id = ? WHERE id = ? AND tenant_id = ?",
                [$input['role_id'], $id, $tenantId]
            );

            return $this->success(['user_id' => $id, 'role_id' => $input['role_id']], 'User role updated');
        } catch (\Exception $e) {
            return $this->error('Failed to assign role: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get users list for role assignment
     */
    public function users(): array
    {
        $tenantId = $this->db->getTenantId();

        $users = $this->db->fetchAll(
            "SELECT u.id, u.first_name, u.last_name, u.email, u.role_id, r.name as role_name
             FROM users u
             LEFT JOIN roles r ON u.role_id = r.id
             WHERE u.tenant_id = ? AND u.status = 'active'
             ORDER BY u.first_name, u.last_name",
            [$tenantId]
        );

        return $this->success($users);
    }
}
