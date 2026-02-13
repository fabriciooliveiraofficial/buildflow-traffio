<?php
/**
 * Employee API Controller
 */

namespace App\Controllers\Api;

use App\Core\Controller;

class EmployeeController extends Controller
{
    /**
     * List employees
     */
    public function index(): array
    {
        $params = $this->getQueryParams();
        $page = (int) ($params['page'] ?? 1);
        $perPage = (int) ($params['per_page'] ?? 15);
        $status = $params['status'] ?? null;
        $department = $params['department'] ?? null;
        $paymentType = $params['payment_type'] ?? null;
        $search = $params['search'] ?? null;

        $tenantId = $this->db->getTenantId();
        $conditions = ["e.tenant_id = ?"];
        $bindings = [$tenantId];

        if ($status) {
            $conditions[] = "e.status = ?";
            $bindings[] = $status;
        }

        if ($department) {
            $conditions[] = "e.department = ?";
            $bindings[] = $department;
        }

        if ($paymentType) {
            $conditions[] = "e.payment_type = ?";
            $bindings[] = $paymentType;
        }

        if ($search) {
            $conditions[] = "(e.first_name LIKE ? OR e.last_name LIKE ? OR e.email LIKE ?)";
            $bindings[] = "%{$search}%";
            $bindings[] = "%{$search}%";
            $bindings[] = "%{$search}%";
        }

        $where = implode(' AND ', $conditions);
        $offset = ($page - 1) * $perPage;

        $countResult = $this->db->fetch(
            "SELECT COUNT(*) as total FROM employees e WHERE {$where}",
            $bindings
        );
        $total = (int) $countResult['total'];

        $employees = $this->db->fetchAll(
            "SELECT 
                e.*,
                u.email as user_email,
                (SELECT COALESCE(SUM(hours), 0) FROM time_logs WHERE employee_id = e.id AND MONTH(log_date) = MONTH(CURDATE())) as hours_this_month
             FROM employees e
             LEFT JOIN users u ON e.user_id = u.id
             WHERE {$where}
             ORDER BY e.last_name, e.first_name
             LIMIT {$perPage} OFFSET {$offset}",
            $bindings
        );

        return $this->paginate($employees, $total, $page, $perPage);
    }

    /**
     * Create employee
     */
    public function store(): array
    {
        $data = $this->validate([
            'first_name' => 'required',
            'last_name' => 'required',
            'payment_type' => 'required',
        ]);

        $input = $this->getJsonInput();

        // Generate employee ID
        $count = $this->db->fetch(
            "SELECT COUNT(*) as count FROM employees WHERE tenant_id = ?",
            [$this->db->getTenantId()]
        );
        $employeeId = 'EMP-' . str_pad(($count['count'] + 1), 5, '0', STR_PAD_LEFT);

        $id = $this->db->insert('employees', [
            'user_id' => $input['user_id'] ?? null,
            'employee_id' => $employeeId,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $input['email'] ?? null,
            'phone' => $input['phone'] ?? null,
            'address' => $input['address'] ?? null,
            'city' => $input['city'] ?? null,
            'state' => $input['state'] ?? null,
            'zip_code' => $input['zip_code'] ?? null,
            'hire_date' => $input['hire_date'] ?? date('Y-m-d'),
            'job_title' => $input['job_title'] ?? null,
            'department' => $input['department'] ?? null,
            'payment_type' => $data['payment_type'],
            'hourly_rate' => $input['hourly_rate'] ?? null,
            'daily_rate' => $input['daily_rate'] ?? null,
            'salary' => $input['salary'] ?? null,
            'commission_rate' => $input['commission_rate'] ?? null,
            'overtime_threshold' => $input['overtime_threshold'] ?? 40,
            'overtime_multiplier' => $input['overtime_multiplier'] ?? 1.5,
            'bank_name' => $input['bank_name'] ?? null,
            'bank_account' => $input['bank_account'] ?? null,
            'bank_routing' => $input['bank_routing'] ?? null,
            'tax_id' => $input['tax_id'] ?? null,
            'emergency_contact' => $input['emergency_contact'] ?? null,
            'emergency_phone' => $input['emergency_phone'] ?? null,
            'notes' => $input['notes'] ?? null,
            'status' => $input['status'] ?? 'active',
        ]);

        $employee = $this->db->fetch("SELECT * FROM employees WHERE id = ?", [$id]);

        return $this->success($employee, 'Employee created', 201);
    }

    /**
     * Get single employee
     */
    public function show(string $id): array
    {
        $employee = $this->db->fetch(
            "SELECT e.*, u.email as user_email 
             FROM employees e
             LEFT JOIN users u ON e.user_id = u.id
             WHERE e.id = ? AND e.tenant_id = ?",
            [$id, $this->db->getTenantId()]
        );

        if (!$employee) {
            $this->error('Employee not found', 404);
        }

        // Get recent time logs
        $timeLogs = $this->db->fetchAll(
            "SELECT tl.*, p.name as project_name
             FROM time_logs tl
             LEFT JOIN projects p ON tl.project_id = p.id
             WHERE tl.employee_id = ?
             ORDER BY tl.log_date DESC
             LIMIT 20",
            [$id]
        );

        // Get payroll summary
        $payrollSummary = $this->db->fetch(
            "SELECT 
                COUNT(*) as total_periods,
                COALESCE(SUM(net_pay), 0) as total_earned
             FROM payroll_records
             WHERE employee_id = ?",
            [$id]
        );

        $employee['recent_time_logs'] = $timeLogs;
        $employee['payroll_summary'] = $payrollSummary;

        return $this->success($employee);
    }

    /**
     * Update employee
     */
    public function update(string $id): array
    {
        $employee = $this->db->fetch(
            "SELECT * FROM employees WHERE id = ? AND tenant_id = ?",
            [$id, $this->db->getTenantId()]
        );

        if (!$employee) {
            $this->error('Employee not found', 404);
        }

        $input = $this->getJsonInput();
        $allowedFields = [
            'first_name',
            'last_name',
            'email',
            'phone',
            'address',
            'city',
            'state',
            'zip_code',
            'job_title',
            'department',
            'payment_type',
            'hourly_rate',
            'daily_rate',
            'salary',
            'commission_rate',
            'overtime_threshold',
            'overtime_multiplier',
            'bank_name',
            'bank_account',
            'bank_routing',
            'tax_id',
            'emergency_contact',
            'emergency_phone',
            'notes',
            'status',
            'termination_date'
        ];

        $updateData = [];
        foreach ($allowedFields as $field) {
            if (isset($input[$field])) {
                $updateData[$field] = $input[$field];
            }
        }

        if (!empty($updateData)) {
            $updateData['updated_at'] = date('Y-m-d H:i:s');
            $this->db->update('employees', $updateData, ['id' => $id]);
        }

        $updated = $this->db->fetch("SELECT * FROM employees WHERE id = ?", [$id]);

        return $this->success($updated, 'Employee updated');
    }

    /**
     * Delete/terminate employee
     */
    public function destroy(string $id): array
    {
        $employee = $this->db->fetch(
            "SELECT * FROM employees WHERE id = ? AND tenant_id = ?",
            [$id, $this->db->getTenantId()]
        );

        if (!$employee) {
            $this->error('Employee not found', 404);
        }

        // Soft delete - set as terminated
        $this->db->update('employees', [
            'status' => 'terminated',
            'termination_date' => date('Y-m-d'),
            'updated_at' => date('Y-m-d H:i:s'),
        ], ['id' => $id]);

        return $this->success(null, 'Employee terminated');
    }
}
