<?php
/**
 * Time Log API Controller
 */

namespace App\Controllers\Api;

use App\Core\Controller;

class TimeLogController extends Controller
{
    /**
     * List time logs with filters
     */
    public function index(): array
    {
        $params = $this->getQueryParams();
        $page = (int) ($params['page'] ?? 1);
        $perPage = (int) ($params['per_page'] ?? 15);
        $employeeId = $params['employee_id'] ?? null;
        $projectId = $params['project_id'] ?? null;
        $startDate = $params['start_date'] ?? null;
        $endDate = $params['end_date'] ?? null;
        $approved = $params['approved'] ?? null;

        $tenantId = $this->db->getTenantId();
        $conditions = ["tl.tenant_id = ?"];
        $bindings = [$tenantId];

        if ($employeeId) {
            $conditions[] = "tl.employee_id = ?";
            $bindings[] = $employeeId;
        }

        if ($projectId) {
            $conditions[] = "tl.project_id = ?";
            $bindings[] = $projectId;
        }

        if ($startDate) {
            $conditions[] = "tl.log_date >= ?";
            $bindings[] = $startDate;
        }

        if ($endDate) {
            $conditions[] = "tl.log_date <= ?";
            $bindings[] = $endDate;
        }

        if ($approved !== null) {
            $conditions[] = "tl.approved = ?";
            $bindings[] = $approved === 'true' ? 1 : 0;
        }

        $where = implode(' AND ', $conditions);
        $offset = ($page - 1) * $perPage;

        $countResult = $this->db->fetch(
            "SELECT COUNT(*) as total FROM time_logs tl WHERE {$where}",
            $bindings
        );
        $total = (int) $countResult['total'];

        $logs = $this->db->fetchAll(
            "SELECT 
                tl.*,
                e.first_name,
                e.last_name,
                p.name as project_name,
                t.title as task_title
             FROM time_logs tl
             LEFT JOIN employees e ON tl.employee_id = e.id
             LEFT JOIN projects p ON tl.project_id = p.id
             LEFT JOIN tasks t ON tl.task_id = t.id
             WHERE {$where}
             ORDER BY tl.log_date DESC, tl.start_time DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $bindings
        );

        return $this->paginate($logs, $total, $page, $perPage);
    }

    /**
     * Create time log
     */
    public function store(): array
    {
        $data = $this->validate([
            'employee_id' => 'required|numeric',
            'log_date' => 'required',
            'hours' => 'required|numeric',
        ]);

        $input = $this->getJsonInput();

        // Calculate hours from start/end if provided
        $hours = $data['hours'];
        if (isset($input['start_time']) && isset($input['end_time'])) {
            $start = strtotime($input['start_time']);
            $end = strtotime($input['end_time']);
            $breakHours = floatval($input['break_hours'] ?? 0);
            $hours = (($end - $start) / 3600) - $breakHours;
        }

        // Get employee to check overtime
        $employee = $this->db->fetch(
            "SELECT * FROM employees WHERE id = ? AND tenant_id = ?",
            [$data['employee_id'], $this->db->getTenantId()]
        );

        if (!$employee) {
            $this->error('Employee not found', 404);
        }

        // Check weekly hours for overtime
        $weekStart = date('Y-m-d', strtotime('monday this week', strtotime($data['log_date'])));
        $weeklyHours = $this->db->fetch(
            "SELECT COALESCE(SUM(hours), 0) as total
             FROM time_logs 
             WHERE employee_id = ? AND log_date >= ? AND log_date < ?",
            [$data['employee_id'], $weekStart, $data['log_date']]
        );

        $isOvertime = ($weeklyHours['total'] + $hours) > ($employee['overtime_threshold'] ?? 40);

        $logId = $this->db->insert('time_logs', [
            'employee_id' => $data['employee_id'],
            'project_id' => $input['project_id'] ?? null,
            'task_id' => $input['task_id'] ?? null,
            'log_date' => $data['log_date'],
            'start_time' => $input['start_time'] ?? null,
            'end_time' => $input['end_time'] ?? null,
            'hours' => $hours,
            'break_hours' => $input['break_hours'] ?? 0,
            'description' => $input['description'] ?? null,
            'is_overtime' => $isOvertime,
            'billing_rate' => $input['billing_rate'] ?? $employee['hourly_rate'],
            'billable' => $input['billable'] ?? true,
        ]);

        $log = $this->db->fetch("SELECT * FROM time_logs WHERE id = ?", [$logId]);

        return $this->success($log, 'Time log created', 201);
    }

    /**
     * Get single time log
     */
    public function show(string $id): array
    {
        $log = $this->db->fetch(
            "SELECT 
                tl.*,
                e.first_name,
                e.last_name,
                p.name as project_name,
                t.title as task_title
             FROM time_logs tl
             LEFT JOIN employees e ON tl.employee_id = e.id
             LEFT JOIN projects p ON tl.project_id = p.id
             LEFT JOIN tasks t ON tl.task_id = t.id
             WHERE tl.id = ? AND tl.tenant_id = ?",
            [$id, $this->db->getTenantId()]
        );

        if (!$log) {
            $this->error('Time log not found', 404);
        }

        return $this->success($log);
    }

    /**
     * Update time log
     */
    public function update(string $id): array
    {
        $log = $this->db->fetch(
            "SELECT * FROM time_logs WHERE id = ? AND tenant_id = ?",
            [$id, $this->db->getTenantId()]
        );

        if (!$log) {
            $this->error('Time log not found', 404);
        }

        if ($log['approved']) {
            $this->error('Cannot modify approved time logs', 422);
        }

        $input = $this->getJsonInput();
        $allowedFields = [
            'project_id',
            'task_id',
            'log_date',
            'start_time',
            'end_time',
            'hours',
            'break_hours',
            'description',
            'billing_rate',
            'billable'
        ];

        $updateData = [];
        foreach ($allowedFields as $field) {
            if (isset($input[$field])) {
                $updateData[$field] = $input[$field];
            }
        }

        if (!empty($updateData)) {
            $updateData['updated_at'] = date('Y-m-d H:i:s');
            $this->db->update('time_logs', $updateData, ['id' => $id]);
        }

        $updated = $this->db->fetch("SELECT * FROM time_logs WHERE id = ?", [$id]);

        return $this->success($updated, 'Time log updated');
    }

    /**
     * Delete time log
     */
    public function destroy(string $id): array
    {
        $log = $this->db->fetch(
            "SELECT * FROM time_logs WHERE id = ? AND tenant_id = ?",
            [$id, $this->db->getTenantId()]
        );

        if (!$log) {
            $this->error('Time log not found', 404);
        }

        if ($log['approved']) {
            $this->error('Cannot delete approved time logs', 422);
        }

        $this->db->delete('time_logs', ['id' => $id]);

        return $this->success(null, 'Time log deleted');
    }

    /**
     * Start a timer (creates an open time log)
     */
    public function startTimer(): array
    {
        $input = $this->getJsonInput();

        $employeeId = $input['employee_id'] ?? null;

        if (!$employeeId) {
            // Try to get employee from current user
            $user = $_SESSION['user'];
            $employee = $this->db->fetch(
                "SELECT id FROM employees WHERE user_id = ? AND tenant_id = ?",
                [$user['id'], $this->db->getTenantId()]
            );
            $employeeId = $employee['id'] ?? null;
        }

        if (!$employeeId) {
            $this->error('Employee ID required', 422);
        }

        // Check for existing running timer
        $existing = $this->db->fetch(
            "SELECT * FROM time_logs 
             WHERE employee_id = ? AND tenant_id = ? AND end_time IS NULL",
            [$employeeId, $this->db->getTenantId()]
        );

        if ($existing) {
            $this->error('Timer already running', 422);
        }

        $logId = $this->db->insert('time_logs', [
            'employee_id' => $employeeId,
            'project_id' => $input['project_id'] ?? null,
            'task_id' => $input['task_id'] ?? null,
            'log_date' => date('Y-m-d'),
            'start_time' => date('H:i:s'),
            'hours' => 0,
            'description' => $input['description'] ?? null,
            'billable' => $input['billable'] ?? true,
        ]);

        $log = $this->db->fetch("SELECT * FROM time_logs WHERE id = ?", [$logId]);

        return $this->success($log, 'Timer started');
    }

    /**
     * Stop a running timer
     */
    public function stopTimer(): array
    {
        $input = $this->getJsonInput();
        $employeeId = $input['employee_id'] ?? null;

        if (!$employeeId) {
            $user = $_SESSION['user'];
            $employee = $this->db->fetch(
                "SELECT id FROM employees WHERE user_id = ? AND tenant_id = ?",
                [$user['id'], $this->db->getTenantId()]
            );
            $employeeId = $employee['id'] ?? null;
        }

        $log = $this->db->fetch(
            "SELECT * FROM time_logs 
             WHERE employee_id = ? AND tenant_id = ? AND end_time IS NULL",
            [$employeeId, $this->db->getTenantId()]
        );

        if (!$log) {
            $this->error('No running timer found', 404);
        }

        $endTime = date('H:i:s');
        $startTime = strtotime($log['start_time']);
        $end = strtotime($endTime);
        $hours = ($end - $startTime) / 3600;

        $this->db->update('time_logs', [
            'end_time' => $endTime,
            'hours' => round($hours, 2),
            'updated_at' => date('Y-m-d H:i:s'),
        ], ['id' => $log['id']]);

        $updated = $this->db->fetch("SELECT * FROM time_logs WHERE id = ?", [$log['id']]);

        return $this->success($updated, 'Timer stopped');
    }

    /**
     * Approve time log
     */
    public function approve(string $id): array
    {
        $log = $this->db->fetch(
            "SELECT * FROM time_logs WHERE id = ? AND tenant_id = ?",
            [$id, $this->db->getTenantId()]
        );

        if (!$log) {
            $this->error('Time log not found', 404);
        }

        $user = $_SESSION['user'];

        $this->db->update('time_logs', [
            'approved' => true,
            'approved_by' => $user['id'],
            'approved_at' => date('Y-m-d H:i:s'),
        ], ['id' => $id]);

        return $this->success(null, 'Time log approved');
    }

    /**
     * Get active timer for current user
     */
    public function getActive(): array
    {
        $tenantId = $this->db->getTenantId();
        $user = $_SESSION['user'] ?? null;

        if (!$user) {
            return $this->success(null);
        }

        // Get employee ID for current user
        $employee = $this->db->fetch(
            "SELECT id FROM employees WHERE user_id = ? AND tenant_id = ?",
            [$user['id'], $tenantId]
        );

        if (!$employee) {
            return $this->success(null);
        }

        // Find active timer (has start_time but no end_time)
        $activeTimer = $this->db->fetch(
            "SELECT tl.*, p.name as project_name
             FROM time_logs tl
             LEFT JOIN projects p ON tl.project_id = p.id
             WHERE tl.employee_id = ? 
             AND tl.tenant_id = ? 
             AND tl.start_time IS NOT NULL 
             AND tl.end_time IS NULL
             ORDER BY tl.created_at DESC
             LIMIT 1",
            [$employee['id'], $tenantId]
        );

        return $this->success($activeTimer);
    }
}
