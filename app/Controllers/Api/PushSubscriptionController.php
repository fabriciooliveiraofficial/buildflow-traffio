<?php

namespace App\Controllers\Api;

use App\Core\Controller;

class PushSubscriptionController extends Controller
{
    /**
     * Store a new push subscription
     */
    public function store(): array
    {
        $data = $this->validate([
            'endpoint' => 'required',
            'keys' => 'required|array',
            'keys.p256dh' => 'required',
            'keys.auth' => 'required'
        ]);

        $employeeId = $this->getEmployeeId();
        if (!$employeeId) {
            $this->error('Employee record not found', 404);
        }

        // Check if subscription already exists for this endpoint
        $exists = $this->db->fetch(
            "SELECT id FROM push_subscriptions WHERE endpoint = ? AND employee_id = ?",
            [$data['endpoint'], $employeeId]
        );

        if ($exists) {
            $this->db->update('push_subscriptions', [
                'p256dh' => $data['keys']['p256dh'],
                'auth' => $data['keys']['auth']
            ], $exists['id']);
            return $this->success(null, 'Subscription updated');
        }

        $this->db->insert('push_subscriptions', [
            'employee_id' => $employeeId,
            'endpoint' => $data['endpoint'],
            'p256dh' => $data['keys']['p256dh'],
            'auth' => $data['keys']['auth']
        ]);

        return $this->success(null, 'Subscription saved', 201);
    }

    /**
     * Delete a subscription (e.g. on logout or invalid endpoint)
     */
    public function destroy(): array
    {
        $endpoint = $_GET['endpoint'] ?? null;
        if (!$endpoint) {
            $this->error('Endpoint required', 400);
        }

        $this->db->execute(
            "DELETE FROM push_subscriptions WHERE endpoint = ? AND employee_id = ?",
            [$endpoint, $this->getEmployeeId()]
        );

        return $this->success(null, 'Subscription removed');
    }
}
