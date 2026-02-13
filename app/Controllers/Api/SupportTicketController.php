<?php
/**
 * Support Ticket API Controller
 * 
 * Handles support ticket CRUD, messages, and remote support features.
 * Updated: 2024-12-10 17:20
 */

namespace App\Controllers\Api;

use App\Core\Controller;

class SupportTicketController extends Controller
{
    /**
     * List tickets (tenant users see own, admins see all)
     */
    public function index(): array
    {
        try {
            $tenantId = $this->db->getTenantId();
            $user = $this->getUser();
            $isAdmin = in_array($user['role'] ?? '', ['admin', 'super_admin', 'developer']);

            $page = (int) ($_GET['page'] ?? 1);
            $perPage = min((int) ($_GET['per_page'] ?? 20), 100);
            $offset = ($page - 1) * $perPage;

            // Build query based on user role
            $where = "t.tenant_id = ?";
            $params = [$tenantId];

            // Non-admins only see their own tickets
            if (!$isAdmin) {
                $where .= " AND t.user_id = ?";
                $params[] = $user['id'];
            }

            // Filters
            if (!empty($_GET['status'])) {
                $where .= " AND t.status = ?";
                $params[] = $_GET['status'];
            }
            if (!empty($_GET['priority'])) {
                $where .= " AND t.priority = ?";
                $params[] = $_GET['priority'];
            }
            if (!empty($_GET['category'])) {
                $where .= " AND t.category = ?";
                $params[] = $_GET['category'];
            }
            if (!empty($_GET['assigned_to'])) {
                $where .= " AND t.assigned_to = ?";
                $params[] = $_GET['assigned_to'];
            }
            if (!empty($_GET['search'])) {
                $where .= " AND (t.subject LIKE ? OR t.ticket_number LIKE ?)";
                $search = '%' . $_GET['search'] . '%';
                $params[] = $search;
                $params[] = $search;
            }

            // Count total
            $total = $this->db->fetch(
                "SELECT COUNT(*) as count FROM support_tickets t WHERE {$where}",
                $params
            )['count'];

            // Get tickets
            $tickets = $this->db->fetchAll(
                "SELECT 
                    t.*,
                    u.first_name as reporter_first_name,
                    u.last_name as reporter_last_name,
                    u.email as reporter_email,
                    a.first_name as assignee_first_name,
                    a.last_name as assignee_last_name,
                    p.name as project_name,
                    (SELECT COUNT(*) FROM ticket_messages WHERE ticket_id = t.id) as message_count
                 FROM support_tickets t
                 LEFT JOIN users u ON t.user_id = u.id
                 LEFT JOIN users a ON t.assigned_to = a.id
                 LEFT JOIN projects p ON t.project_id = p.id
                 WHERE {$where}
                 ORDER BY 
                    CASE t.priority WHEN 'urgent' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 ELSE 4 END,
                    t.created_at DESC
                 LIMIT ? OFFSET ?",
                array_merge($params, [$perPage, $offset])
            );

            return $this->success([
                'tickets' => $tickets,
                'pagination' => [
                    'page' => $page,
                    'per_page' => $perPage,
                    'total' => (int) $total,
                    'total_pages' => ceil($total / $perPage)
                ]
            ]);
        } catch (\Exception $e) {
            error_log("Support tickets list error: " . $e->getMessage());
            $this->error('Failed to load tickets', 500);
            return [];
        }
    }

    /**
     * Get single ticket with messages
     */
    public function show($id): array
    {
        try {
            $tenantId = $this->db->getTenantId();
            $user = $this->getUser();
            $isAdmin = in_array($user['role'] ?? '', ['admin', 'super_admin', 'developer']);

            $ticket = $this->db->fetch(
                "SELECT 
                    t.*,
                    u.first_name as reporter_first_name,
                    u.last_name as reporter_last_name,
                    u.email as reporter_email,
                    a.first_name as assignee_first_name,
                    a.last_name as assignee_last_name,
                    p.name as project_name
                 FROM support_tickets t
                 LEFT JOIN users u ON t.user_id = u.id
                 LEFT JOIN users a ON t.assigned_to = a.id
                 LEFT JOIN projects p ON t.project_id = p.id
                 WHERE t.id = ? AND t.tenant_id = ?",
                [$id, $tenantId]
            );

            if (!$ticket) {
                $this->error('Ticket not found', 404);
                return [];
            }

            // Non-admins can only view their own tickets
            if (!$isAdmin && $ticket['user_id'] != $user['id']) {
                $this->error('Access denied', 403);
                return [];
            }

            // Get messages (hide internal notes from non-admins)
            $messageWhere = $isAdmin ? "" : "AND m.is_internal = 0";
            $messages = $this->db->fetchAll(
                "SELECT 
                    m.*,
                    u.first_name,
                    u.last_name,
                    u.email,
                    r.name as role
                 FROM ticket_messages m
                 LEFT JOIN users u ON m.user_id = u.id
                 LEFT JOIN roles r ON u.role_id = r.id
                 WHERE m.ticket_id = ? {$messageWhere}
                 ORDER BY m.created_at ASC",
                [$id]
            );

            // Get attachments
            $attachments = $this->db->fetchAll(
                "SELECT * FROM ticket_attachments WHERE ticket_id = ?",
                [$id]
            );

            // Get remote sessions (admin only)
            $remoteSessions = [];
            if ($isAdmin) {
                $remoteSessions = $this->db->fetchAll(
                    "SELECT 
                        rs.*,
                        u.first_name,
                        u.last_name
                     FROM remote_sessions rs
                     LEFT JOIN users u ON rs.developer_id = u.id
                     WHERE rs.ticket_id = ?
                     ORDER BY rs.session_start DESC",
                    [$id]
                );
            }

            return $this->success([
                'ticket' => $ticket,
                'messages' => $messages,
                'attachments' => $attachments,
                'remote_sessions' => $remoteSessions
            ]);
        } catch (\Exception $e) {
            error_log("Support ticket show error: " . $e->getMessage());
            $this->error('Failed to load ticket: ' . $e->getMessage(), 500);
            return [];
        }
    }

    /**
     * Create new support ticket
     */
    public function store(): array
    {
        try {
            $tenantId = $this->db->getTenantId();
            $user = $this->getUser();
            $input = $this->getJsonInput();

            // Validate required fields
            if (empty($input['subject']) || empty($input['description'])) {
                $this->error('Subject and description are required', 400);
                return [];
            }

            // Generate ticket number
            $ticketNumber = 'TKT-' . strtoupper(substr(uniqid(), -8));

            $ticketId = $this->db->insert('support_tickets', [
                'user_id' => $user['id'],
                'project_id' => !empty($input['project_id']) ? $input['project_id'] : null,
                'ticket_number' => $ticketNumber,
                'subject' => $input['subject'],
                'description' => $input['description'],
                'category' => $input['category'] ?? 'other',
                'priority' => $input['priority'] ?? 'medium',
                'status' => 'new',
                'os_name' => $input['os_name'] ?? null,
                'os_version' => $input['os_version'] ?? null,
                'browser_name' => $input['browser_name'] ?? null,
                'browser_version' => $input['browser_version'] ?? null,
                'screen_resolution' => $input['screen_resolution'] ?? null,
                'user_agent' => $input['user_agent'] ?? null,
                'timezone' => $input['timezone'] ?? null,
                'anydesk_id' => $input['anydesk_id'] ?? null,
            ]);

            // Add initial message from description
            $this->db->insert('ticket_messages', [
                'ticket_id' => $ticketId,
                'user_id' => $user['id'],
                'message' => $input['description'],
                'is_internal' => false
            ]);

            $ticket = $this->db->fetch(
                "SELECT * FROM support_tickets WHERE id = ?",
                [$ticketId]
            );

            return $this->success($ticket, 201);
        } catch (\Exception $e) {
            error_log("Support ticket create error: " . $e->getMessage());
            $this->error('Failed to create ticket: ' . $e->getMessage(), 500);
            return [];
        }
    }

    /**
     * Update ticket (status, priority, assignment, etc.)
     */
    public function update($id): array
    {
        try {
            $tenantId = $this->db->getTenantId();
            $user = $this->getUser();
            $isAdmin = in_array($user['role'] ?? '', ['admin', 'super_admin', 'developer']);
            $input = $this->getJsonInput();

            $ticket = $this->db->fetch(
                "SELECT * FROM support_tickets WHERE id = ? AND tenant_id = ?",
                [$id, $tenantId]
            );

            if (!$ticket) {
                $this->error('Ticket not found', 404);
                return [];
            }

            // Non-admins can only update their own tickets (limited fields)
            if (!$isAdmin && $ticket['user_id'] != $user['id']) {
                $this->error('Access denied', 403);
                return [];
            }

            $updateData = [];

            // Fields anyone can update
            if (isset($input['anydesk_id'])) {
                $updateData['anydesk_id'] = $input['anydesk_id'];
            }

            // Admin-only fields
            if ($isAdmin) {
                if (isset($input['status'])) {
                    $updateData['status'] = $input['status'];
                    if ($input['status'] === 'resolved') {
                        $updateData['resolved_at'] = date('Y-m-d H:i:s');
                    }
                    if ($input['status'] === 'closed') {
                        $updateData['closed_at'] = date('Y-m-d H:i:s');
                    }
                }
                if (isset($input['priority'])) {
                    $updateData['priority'] = $input['priority'];
                }
                if (isset($input['assigned_to'])) {
                    $updateData['assigned_to'] = $input['assigned_to'] ?: null;
                    $updateData['assigned_at'] = $input['assigned_to'] ? date('Y-m-d H:i:s') : null;
                }
                if (isset($input['category'])) {
                    $updateData['category'] = $input['category'];
                }
            }

            // Satisfaction rating (only ticket owner)
            if (isset($input['satisfaction_rating']) && $ticket['user_id'] == $user['id']) {
                $updateData['satisfaction_rating'] = (int) $input['satisfaction_rating'];
            }

            if (!empty($updateData)) {
                $this->db->update('support_tickets', $updateData, ['id' => $id]);
            }

            $updatedTicket = $this->db->fetch(
                "SELECT * FROM support_tickets WHERE id = ?",
                [$id]
            );

            return $this->success($updatedTicket);
        } catch (\Exception $e) {
            error_log("Support ticket update error: " . $e->getMessage());
            $this->error('Failed to update ticket', 500);
            return [];
        }
    }

    /**
     * Add message to ticket
     */
    public function addMessage($id): array
    {
        try {
            $tenantId = $this->db->getTenantId();
            $user = $this->getUser();
            $isAdmin = in_array($user['role'] ?? '', ['admin', 'super_admin', 'developer']);
            $input = $this->getJsonInput();

            $ticket = $this->db->fetch(
                "SELECT * FROM support_tickets WHERE id = ? AND tenant_id = ?",
                [$id, $tenantId]
            );

            if (!$ticket) {
                $this->error('Ticket not found', 404);
                return [];
            }

            // Non-admins can only message their own tickets
            if (!$isAdmin && $ticket['user_id'] != $user['id']) {
                $this->error('Access denied', 403);
                return [];
            }

            if (empty($input['message'])) {
                $this->error('Message is required', 400);
                return [];
            }

            // Internal notes only for admins
            $isInternal = $isAdmin && !empty($input['is_internal']);

            $messageId = $this->db->insert('ticket_messages', [
                'ticket_id' => $id,
                'user_id' => $user['id'],
                'message' => $input['message'],
                'is_internal' => $isInternal
            ]);

            // Update ticket status
            if ($ticket['status'] === 'new') {
                $this->db->update('support_tickets', ['status' => 'open'], ['id' => $id]);
            } elseif ($ticket['status'] === 'awaiting_info' && $ticket['user_id'] == $user['id']) {
                $this->db->update('support_tickets', ['status' => 'in_progress'], ['id' => $id]);
            }

            $message = $this->db->fetch(
                "SELECT m.*, u.first_name, u.last_name, u.email, u.role
                 FROM ticket_messages m
                 LEFT JOIN users u ON m.user_id = u.id
                 WHERE m.id = ?",
                [$messageId]
            );

            return $this->success($message, 201);
        } catch (\Exception $e) {
            error_log("Support message error: " . $e->getMessage());
            $this->error('Failed to add message', 500);
            return [];
        }
    }

    /**
     * Get ticket statistics (admin only)
     */
    public function stats(): array
    {
        try {
            $tenantId = $this->db->getTenantId();
            $user = $this->getUser();
            $userRole = $user['role'] ?? $user['role_name'] ?? '';
            $isAdmin = in_array($userRole, ['admin', 'super_admin', 'developer']) || ($user['is_developer'] ?? false);

            if (!$isAdmin) {
                $this->error('Access denied', 403);
                return [];
            }

            $stats = $this->db->fetch(
                "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) as new_count,
                    SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open_count,
                    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_count,
                    SUM(CASE WHEN status = 'awaiting_info' THEN 1 ELSE 0 END) as awaiting_count,
                    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_count,
                    SUM(CASE WHEN priority = 'urgent' AND status NOT IN ('resolved', 'closed') THEN 1 ELSE 0 END) as urgent_open,
                    SUM(CASE WHEN priority = 'high' AND status NOT IN ('resolved', 'closed') THEN 1 ELSE 0 END) as high_open
                 FROM support_tickets 
                 WHERE tenant_id = ?",
                [$tenantId]
            );

            // OS distribution
            $osDistribution = $this->db->fetchAll(
                "SELECT os_name, COUNT(*) as count
                 FROM support_tickets 
                 WHERE tenant_id = ? AND os_name IS NOT NULL
                 GROUP BY os_name
                 ORDER BY count DESC",
                [$tenantId]
            );

            // Category distribution
            $categoryDistribution = $this->db->fetchAll(
                "SELECT category, COUNT(*) as count
                 FROM support_tickets 
                 WHERE tenant_id = ?
                 GROUP BY category
                 ORDER BY count DESC",
                [$tenantId]
            );

            return $this->success([
                'counts' => $stats,
                'os_distribution' => $osDistribution,
                'category_distribution' => $categoryDistribution
            ]);
        } catch (\Exception $e) {
            error_log("Support stats error: " . $e->getMessage());
            $this->error('Failed to load stats', 500);
            return [];
        }
    }
}
