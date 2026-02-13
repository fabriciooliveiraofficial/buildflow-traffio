<?php
/**
 * Notification API Controller
 */

namespace App\Controllers\Api;

use App\Core\Controller;

class NotificationController extends Controller
{
    /**
     * List unread notifications for the current user
     */
    public function index()
    {
        $user = $_SESSION['user'] ?? null;
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }

        // Get unread notifications
        $notifications = $this->db->fetchAll(
            "SELECT * FROM notifications 
             WHERE user_id = ? AND read_at IS NULL 
             ORDER BY created_at DESC 
             LIMIT 50",
            [$user['id']]
        );

        // Get unread count
        $count = $this->db->fetch(
            "SELECT COUNT(*) as count FROM notifications 
             WHERE user_id = ? AND read_at IS NULL",
            [$user['id']]
        );

        return $this->success([
            'notifications' => $notifications,
            'unread_count' => (int) $count['count']
        ]);
    }

    /**
     * Mark a notification as read
     */
    public function markAsRead($id)
    {
        $user = $_SESSION['user'] ?? null;
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }

        $notification = $this->db->fetch("SELECT * FROM notifications WHERE id = ?", [$id]);

        if (!$notification) {
            return $this->error("Notification not found", 404);
        }

        if ($notification['user_id'] != $user['id']) {
            return $this->error("Unauthorized", 403);
        }

        $this->db->update(
            'notifications',
            ['read_at' => date('Y-m-d H:i:s')],
            ['id' => $id]
        );

        return $this->success(['message' => 'Notification marked as read']);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllRead()
    {
        $user = $_SESSION['user'] ?? null;
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }

        $this->db->query(
            "UPDATE notifications SET read_at = NOW() WHERE user_id = ? AND read_at IS NULL",
            [$user['id']]
        );

        return $this->success(['message' => 'All notifications marked as read']);
    }
}
