<?php
/**
 * Task API Controller
 */

namespace App\Controllers\Api;

use App\Core\Controller;

class TaskController extends Controller
{
    /**
     * List tasks
     */
    public function index(): array
    {
        $params = $this->getQueryParams();
        $page = (int) ($params['page'] ?? 1);
        $perPage = (int) ($params['per_page'] ?? 15);
        $projectId = $params['project_id'] ?? null;
        $status = $params['status'] ?? null;
        $assignedTo = $params['assigned_to'] ?? null;
        $priority = $params['priority'] ?? null;

        $tenantId = $this->db->getTenantId();
        $conditions = ["t.tenant_id = ?"];
        $bindings = [$tenantId];

        if ($projectId) {
            $conditions[] = "t.project_id = ?";
            $bindings[] = $projectId;
        }

        if ($status) {
            $conditions[] = "t.status = ?";
            $bindings[] = $status;
        }

        if ($assignedTo) {
            $conditions[] = "t.assigned_to = ?";
            $bindings[] = $assignedTo;
        }

        if ($priority) {
            $conditions[] = "t.priority = ?";
            $bindings[] = $priority;
        }

        $where = implode(' AND ', $conditions);
        $offset = ($page - 1) * $perPage;

        $countResult = $this->db->fetch(
            "SELECT COUNT(*) as total FROM tasks t WHERE {$where}",
            $bindings
        );
        $total = (int) $countResult['total'];

        $tasks = $this->db->fetchAll(
            "SELECT 
                t.*,
                p.name as project_name,
                u.first_name as assigned_first_name,
                u.last_name as assigned_last_name,
                DATEDIFF(t.due_date, CURDATE()) as days_until_due
             FROM tasks t
             LEFT JOIN projects p ON t.project_id = p.id
             LEFT JOIN users u ON t.assigned_to = u.id
             WHERE {$where}
             ORDER BY t.sort_order, t.due_date, t.priority DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $bindings
        );

        return $this->paginate($tasks, $total, $page, $perPage);
    }

    /**
     * Create task
     */
    public function store(): array
    {
        $data = $this->validate([
            'project_id' => 'required|numeric',
            'title' => 'required',
        ]);

        $input = $this->getJsonInput();
        $user = $_SESSION['user'];

        // Get max sort order for project
        $maxOrder = $this->db->fetch(
            "SELECT COALESCE(MAX(sort_order), 0) as max_order FROM tasks WHERE project_id = ?",
            [$data['project_id']]
        );

        $taskId = $this->db->insert('tasks', [
            'project_id' => $data['project_id'],
            'parent_id' => $input['parent_id'] ?? null,
            'title' => $data['title'],
            'description' => $input['description'] ?? null,
            'status' => $input['status'] ?? 'pending',
            'priority' => $input['priority'] ?? 'medium',
            'assigned_to' => $input['assigned_to'] ?? null,
            'due_date' => $input['due_date'] ?? null,
            'estimated_hours' => $input['estimated_hours'] ?? null,
            'sort_order' => ($maxOrder['max_order'] ?? 0) + 1,
        ]);

        $task = $this->db->fetch("SELECT * FROM tasks WHERE id = ?", [$taskId]);

        // Always create notification for task creation
        $this->db->insert('notifications', [
            'tenant_id' => $user['tenant_id'],
            'user_id' => $user['id'],
            'type' => 'task_created',
            'title' => 'New Task Created',
            'message' => "Task created: {$data['title']}",
            'link' => "/projects/{$data['project_id']}#tasks",
            'created_at' => date('Y-m-d H:i:s')
        ]);

        return $this->success($task, 'Task created', 201);
    }

    /**
     * Get single task
     */
    public function show(string $id): array
    {
        $task = $this->db->fetch(
            "SELECT 
                t.*,
                p.name as project_name,
                u.first_name as assigned_first_name,
                u.last_name as assigned_last_name,
                creator.first_name as created_by_first_name,
                creator.last_name as created_by_last_name
             FROM tasks t
             LEFT JOIN projects p ON t.project_id = p.id
             LEFT JOIN users u ON t.assigned_to = u.id
             LEFT JOIN users creator ON t.created_by = creator.id
             WHERE t.id = ? AND t.tenant_id = ?",
            [$id, $this->db->getTenantId()]
        );

        if (!$task) {
            $this->error('Task not found', 404);
        }

        // Get subtasks
        $subtasks = $this->db->fetchAll(
            "SELECT * FROM tasks WHERE parent_id = ? ORDER BY sort_order",
            [$id]
        );

        // Get time logged
        $timeLogged = $this->db->fetch(
            "SELECT COALESCE(SUM(hours), 0) as total FROM time_logs WHERE task_id = ?",
            [$id]
        );

        $task['subtasks'] = $subtasks;
        $task['hours_logged'] = (float) $timeLogged['total'];

        return $this->success($task);
    }

    /**
     * Update task
     */
    public function update(string $id): array
    {
        $task = $this->db->fetch(
            "SELECT * FROM tasks WHERE id = ? AND tenant_id = ?",
            [$id, $this->db->getTenantId()]
        );

        if (!$task) {
            $this->error('Task not found', 404);
        }

        $input = $this->getJsonInput();
        $allowedFields = [
            'title',
            'description',
            'status',
            'priority',
            'assigned_to',
            'due_date',
            'estimated_hours',
            'sort_order',
            'progress',
            'completed_at'
        ];

        $updateData = [];
        foreach ($allowedFields as $field) {
            if (isset($input[$field])) {
                $updateData[$field] = $input[$field];
            }
        }

        // Auto-set completed_at when status changes to completed
        if (isset($input['status']) && $input['status'] === 'completed' && !$task['completed_at']) {
            $updateData['completed_at'] = date('Y-m-d H:i:s');
            $updateData['progress'] = 100;
        }

        if (!empty($updateData)) {
            $updateData['updated_at'] = date('Y-m-d H:i:s');
            $this->db->update('tasks', $updateData, ['id' => $id]);
        }

        // Update project progress
        $this->updateProjectProgress($task['project_id']);

        $updated = $this->db->fetch("SELECT * FROM tasks WHERE id = ?", [$id]);

        return $this->success($updated, 'Task updated');
    }

    /**
     * Delete task
     */
    public function destroy(string $id): array
    {
        $task = $this->db->fetch(
            "SELECT * FROM tasks WHERE id = ? AND tenant_id = ?",
            [$id, $this->db->getTenantId()]
        );

        if (!$task) {
            $this->error('Task not found', 404);
        }

        // Delete subtasks first
        $this->db->delete('tasks', ['parent_id' => $id]);
        $this->db->delete('tasks', ['id' => $id]);

        // Update project progress
        $this->updateProjectProgress($task['project_id']);

        return $this->success(null, 'Task deleted');
    }

    /**
     * Reorder tasks
     */
    public function reorder(): array
    {
        $input = $this->getJsonInput();
        $tasks = $input['tasks'] ?? [];

        if (empty($tasks)) {
            $this->error('No tasks provided', 422);
        }

        foreach ($tasks as $index => $taskId) {
            $this->db->update('tasks', [
                'sort_order' => $index,
                'updated_at' => date('Y-m-d H:i:s'),
            ], ['id' => $taskId, 'tenant_id' => $this->db->getTenantId()]);
        }

        return $this->success(null, 'Tasks reordered');
    }

    /**
     * Bulk update task status
     */
    public function bulkStatus(): array
    {
        $input = $this->getJsonInput();
        $taskIds = $input['task_ids'] ?? [];
        $status = $input['status'] ?? null;

        if (empty($taskIds) || !$status) {
            $this->error('Task IDs and status required', 422);
        }

        $placeholders = implode(',', array_fill(0, count($taskIds), '?'));
        $bindings = array_merge($taskIds, [$this->db->getTenantId()]);

        $updateData = [
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($status === 'completed') {
            $updateData['completed_at'] = date('Y-m-d H:i:s');
            $updateData['progress'] = 100;
        }

        $setClause = implode(', ', array_map(fn($k) => "{$k} = ?", array_keys($updateData)));
        $values = array_merge(array_values($updateData), $bindings);

        $this->db->query(
            "UPDATE tasks SET {$setClause} WHERE id IN ({$placeholders}) AND tenant_id = ?",
            $values
        );

        return $this->success(null, count($taskIds) . ' tasks updated');
    }

    /**
     * Update project progress based on task completion
     */
    private function updateProjectProgress(int $projectId): void
    {
        $stats = $this->db->fetch(
            "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
             FROM tasks 
             WHERE project_id = ? AND parent_id IS NULL",
            [$projectId]
        );

        $progress = $stats['total'] > 0
            ? round(($stats['completed'] / $stats['total']) * 100)
            : 0;

        $this->db->update('projects', [
            'progress' => $progress,
            'updated_at' => date('Y-m-d H:i:s'),
        ], ['id' => $projectId]);
    }
}
