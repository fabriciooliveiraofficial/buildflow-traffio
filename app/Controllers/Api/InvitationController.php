<?php
/**
 * Invitation API Controller
 * 
 * Handles user invitations for tenant admins
 */

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Mailer;

class InvitationController extends Controller
{
    /**
     * List all invitations for the tenant
     */
    public function index(): array
    {
        $params = $this->getQueryParams();
        $status = $params['status'] ?? null;
        $tenantId = $this->db->getTenantId();

        $conditions = ["i.tenant_id = ?"];
        $bindings = [$tenantId];

        if ($status) {
            $conditions[] = "i.status = ?";
            $bindings[] = $status;
        }

        $where = implode(' AND ', $conditions);

        $invitations = $this->db->fetchAll(
            "SELECT 
                i.*,
                r.display_name as role_name,
                CONCAT(u.first_name, ' ', u.last_name) as inviter_name
             FROM user_invitations i
             LEFT JOIN roles r ON i.role_id = r.id
             LEFT JOIN users u ON i.invited_by = u.id
             WHERE {$where}
             ORDER BY i.created_at DESC",
            $bindings
        );

        // Update expired invitations
        $this->updateExpiredInvitations($tenantId);

        return $this->success($invitations);
    }

    /**
     * Create and send an invitation
     */
    public function store(): array
    {
        $data = $this->getJsonInput();
        $tenantId = $this->db->getTenantId();
        $userId = $this->getCurrentUserId();

        // Validate required fields
        if (empty($data['email'])) {
            $this->error('Email is required', 422);
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $this->error('Invalid email address', 422);
        }

        if (empty($data['role_id'])) {
            $this->error('Role is required', 422);
        }

        $email = strtolower(trim($data['email']));

        // Check if user already exists in this tenant
        $existingUser = $this->db->fetch(
            "SELECT id FROM users WHERE tenant_id = ? AND email = ?",
            [$tenantId, $email]
        );

        if ($existingUser) {
            $this->error('A user with this email already exists in your organization', 422);
        }

        // Check for pending invitation
        $pendingInvitation = $this->db->fetch(
            "SELECT id FROM user_invitations 
             WHERE tenant_id = ? AND email = ? AND status = 'pending' AND expires_at > NOW()",
            [$tenantId, $email]
        );

        if ($pendingInvitation) {
            $this->error('An invitation is already pending for this email address', 422);
        }

        // Rate limiting: Max 10 invitations per hour
        $recentCount = $this->db->fetch(
            "SELECT COUNT(*) as count FROM user_invitations 
             WHERE tenant_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)",
            [$tenantId]
        );

        if ($recentCount['count'] >= 10) {
            $this->error('You have reached the hourly invitation limit. Please try again later.', 429);
        }

        // Generate secure token
        $token = bin2hex(random_bytes(32)); // 64 characters

        // Calculate expiry (7 days)
        $expiresAt = date('Y-m-d H:i:s', strtotime('+7 days'));

        // Insert invitation
        $invitationId = $this->db->insert('user_invitations', [
            'tenant_id' => $tenantId,
            'email' => $email,
            'role_id' => $data['role_id'],
            'token' => $token,
            'first_name' => $data['first_name'] ?? null,
            'last_name' => $data['last_name'] ?? null,
            'invited_by' => $userId,
            'message' => $data['message'] ?? null,
            'status' => 'pending',
            'expires_at' => $expiresAt,
        ]);

        // Get tenant info
        $tenant = $this->db->fetch(
            "SELECT name, subdomain FROM tenants WHERE id = ?",
            [$tenantId]
        );

        // Get inviter info
        $inviter = $this->db->fetch(
            "SELECT first_name, last_name FROM users WHERE id = ?",
            [$userId]
        );

        // Get role info
        $role = $this->db->fetch(
            "SELECT display_name FROM roles WHERE id = ?",
            [$data['role_id']]
        );

        // Build accept URL
        $baseUrl = $_ENV['APP_URL'] ?? 'https://buildflow-traffio.com';
        $acceptUrl = "{$baseUrl}/invite/{$token}";

        // Send invitation email
        $mailer = new Mailer();
        $emailSent = $mailer->sendInvitation([
            'email' => $email,
            'first_name' => $data['first_name'] ?? null,
            'inviter_name' => $inviter ? "{$inviter['first_name']} {$inviter['last_name']}" : 'The team',
            'role_name' => $role['display_name'] ?? 'Team Member',
            'message' => $data['message'] ?? null,
            'expires_at' => date('F j, Y \a\t g:i A', strtotime($expiresAt)),
        ], $tenant['name'], $acceptUrl);

        if (!$emailSent) {
            // Still return success but note email failed
            error_log("Failed to send invitation email to {$email}: " . $mailer->getLastError());
        }

        // Fetch the created invitation
        $invitation = $this->db->fetch(
            "SELECT i.*, r.display_name as role_name
             FROM user_invitations i
             LEFT JOIN roles r ON i.role_id = r.id
             WHERE i.id = ?",
            [$invitationId]
        );

        return $this->success([
            'invitation' => $invitation,
            'email_sent' => $emailSent,
            'message' => $emailSent
                ? 'Invitation sent successfully!'
                : 'Invitation created but email delivery failed. You can resend it.'
        ]);
    }

    /**
     * Resend an invitation email
     */
    public function resend(int $id): array
    {
        $tenantId = $this->db->getTenantId();

        $invitation = $this->db->fetch(
            "SELECT i.*, r.display_name as role_name,
                    CONCAT(u.first_name, ' ', u.last_name) as inviter_name
             FROM user_invitations i
             LEFT JOIN roles r ON i.role_id = r.id
             LEFT JOIN users u ON i.invited_by = u.id
             WHERE i.id = ? AND i.tenant_id = ?",
            [$id, $tenantId]
        );

        if (!$invitation) {
            $this->error('Invitation not found', 404);
        }

        if ($invitation['status'] !== 'pending') {
            $this->error('Only pending invitations can be resent', 422);
        }

        // Extend expiry
        $newExpiry = date('Y-m-d H:i:s', strtotime('+7 days'));
        $this->db->update('user_invitations', [
            'expires_at' => $newExpiry,
        ], ['id' => $id]);

        // Get tenant info
        $tenant = $this->db->fetch(
            "SELECT name, subdomain FROM tenants WHERE id = ?",
            [$tenantId]
        );

        // Build accept URL
        $baseUrl = $_ENV['APP_URL'] ?? 'https://buildflow-traffio.com';
        $acceptUrl = "{$baseUrl}/invite/{$invitation['token']}";

        // Send invitation email
        $mailer = new Mailer();
        $emailSent = $mailer->sendInvitation([
            'email' => $invitation['email'],
            'first_name' => $invitation['first_name'],
            'inviter_name' => $invitation['inviter_name'] ?? 'The team',
            'role_name' => $invitation['role_name'] ?? 'Team Member',
            'message' => $invitation['message'],
            'expires_at' => date('F j, Y \a\t g:i A', strtotime($newExpiry)),
        ], $tenant['name'], $acceptUrl);

        return $this->success([
            'email_sent' => $emailSent,
            'new_expiry' => $newExpiry,
            'message' => $emailSent ? 'Invitation resent successfully!' : 'Failed to send email'
        ]);
    }

    /**
     * Cancel an invitation
     */
    public function destroy(int $id): array
    {
        $tenantId = $this->db->getTenantId();

        $invitation = $this->db->fetch(
            "SELECT * FROM user_invitations WHERE id = ? AND tenant_id = ?",
            [$id, $tenantId]
        );

        if (!$invitation) {
            $this->error('Invitation not found', 404);
        }

        if ($invitation['status'] === 'accepted') {
            $this->error('Cannot cancel an accepted invitation', 422);
        }

        $this->db->update('user_invitations', [
            'status' => 'cancelled',
        ], ['id' => $id]);

        return $this->success(['message' => 'Invitation cancelled']);
    }

    /**
     * Validate an invitation token (public endpoint)
     */
    public function validateToken(string $token): array
    {
        $invitation = $this->db->fetch(
            "SELECT i.*, r.display_name as role_name, t.name as tenant_name
             FROM user_invitations i
             LEFT JOIN roles r ON i.role_id = r.id
             LEFT JOIN tenants t ON i.tenant_id = t.id
             WHERE i.token = ?",
            [$token]
        );

        if (!$invitation) {
            $this->error('Invalid invitation link', 404);
        }

        if ($invitation['status'] === 'accepted') {
            $this->error('This invitation has already been accepted', 422);
        }

        if ($invitation['status'] === 'cancelled') {
            $this->error('This invitation has been cancelled', 422);
        }

        if (strtotime($invitation['expires_at']) < time()) {
            $this->db->update('user_invitations', [
                'status' => 'expired',
            ], ['id' => $invitation['id']]);
            $this->error('This invitation has expired', 422);
        }

        return $this->success([
            'email' => $invitation['email'],
            'first_name' => $invitation['first_name'],
            'last_name' => $invitation['last_name'],
            'role_name' => $invitation['role_name'],
            'tenant_name' => $invitation['tenant_name'],
        ]);
    }

    /**
     * Accept an invitation and create user account
     */
    public function accept(string $token): array
    {
        $data = $this->getJsonInput();

        // Validate password
        if (empty($data['password']) || strlen($data['password']) < 8) {
            $this->error('Password must be at least 8 characters', 422);
        }

        $invitation = $this->db->fetch(
            "SELECT * FROM user_invitations WHERE token = ?",
            [$token]
        );

        if (!$invitation) {
            $this->error('Invalid invitation link', 404);
        }

        if ($invitation['status'] !== 'pending') {
            $this->error('This invitation is no longer valid', 422);
        }

        if (strtotime($invitation['expires_at']) < time()) {
            $this->db->update('user_invitations', [
                'status' => 'expired',
            ], ['id' => $invitation['id']]);
            $this->error('This invitation has expired', 422);
        }

        // Create user account
        $userId = $this->db->insert('users', [
            'tenant_id' => $invitation['tenant_id'],
            'role_id' => $invitation['role_id'],
            'first_name' => $data['first_name'] ?? $invitation['first_name'] ?? '',
            'last_name' => $data['last_name'] ?? $invitation['last_name'] ?? '',
            'email' => $invitation['email'],
            'password' => password_hash($data['password'], PASSWORD_DEFAULT),
            'status' => 'active',
            'email_verified_at' => date('Y-m-d H:i:s'), // Email is verified via invite
        ]);

        // Update invitation status
        $this->db->update('user_invitations', [
            'status' => 'accepted',
            'accepted_at' => date('Y-m-d H:i:s'),
        ], ['id' => $invitation['id']]);

        // Get tenant subdomain for login redirect
        $tenant = $this->db->fetch(
            "SELECT subdomain FROM tenants WHERE id = ?",
            [$invitation['tenant_id']]
        );

        return $this->success([
            'message' => 'Account created successfully! You can now log in.',
            'subdomain' => $tenant['subdomain'] ?? null,
        ], 201);
    }

    /**
     * Update expired invitations
     */
    private function updateExpiredInvitations(int $tenantId): void
    {
        $this->db->query(
            "UPDATE user_invitations 
             SET status = 'expired' 
             WHERE tenant_id = ? AND status = 'pending' AND expires_at < NOW()",
            [$tenantId]
        );
    }

    /**
     * Get current user ID from JWT
     */
    private function getCurrentUserId(): ?int
    {
        $auth = $GLOBALS['auth'] ?? null;
        return $auth ? $auth->getUserId() : null;
    }
}
