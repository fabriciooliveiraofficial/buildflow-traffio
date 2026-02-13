<?php
/**
 * Settings API Controller
 */

namespace App\Controllers\Api;

use App\Core\Controller;

class SettingsController extends Controller
{
    /**
     * Get all settings
     */
    public function index(): array
    {
        $tenantId = $this->db->getTenantId();

        $settings = $this->db->fetchAll(
            "SELECT `key`, `value` FROM settings WHERE tenant_id = ?",
            [$tenantId]
        );

        $result = [];
        foreach ($settings as $setting) {
            // Try to decode JSON values
            $value = $setting['value'];
            $decoded = json_decode($value, true);
            $result[$setting['key']] = $decoded !== null ? $decoded : $value;
        }

        // Add default settings if not present
        $defaults = [
            'language' => 'en',
            'theme' => 'light',
            'timezone' => 'America/New_York',
            'date_format' => 'Y-m-d',
            'currency' => 'USD',
            'currency_symbol' => '$',
            'notifications_email' => true,
            'notifications_browser' => true,
            'overtime_threshold' => 40,
            'overtime_multiplier' => 1.5,
            'fiscal_year_start' => '01-01',
        ];

        foreach ($defaults as $key => $default) {
            if (!isset($result[$key])) {
                $result[$key] = $default;
            }
        }

        return $this->success($result);
    }

    /**
     * Update settings
     */
    public function update(): array
    {
        $this->authorize('settings.update');

        $input = $this->getJsonInput();
        $tenantId = $this->db->getTenantId();

        // Protected settings that only admin can change
        $protectedSettings = ['stripe_secret_key', 'stripe_webhook_secret'];
        $user = $_SESSION['user'];

        foreach ($input as $key => $value) {
            // Skip protected settings for non-admins
            if (in_array($key, $protectedSettings) && $user['role_name'] !== 'admin') {
                continue;
            }

            // Encode arrays/objects as JSON
            $storedValue = is_array($value) || is_object($value) ? json_encode($value) : (string) $value;

            $existing = $this->db->fetch(
                "SELECT id FROM settings WHERE tenant_id = ? AND `key` = ?",
                [$tenantId, $key]
            );

            if ($existing) {
                $this->db->update('settings', [
                    'value' => $storedValue,
                    'updated_at' => date('Y-m-d H:i:s'),
                ], ['id' => $existing['id']]);
            } else {
                $this->db->insert('settings', [
                    'tenant_id' => $tenantId,
                    'key' => $key,
                    'value' => $storedValue,
                ]);
            }
        }

        return $this->success(null, 'Settings updated');
    }

    /**
     * Get custom categories
     */
    public function categories(): array
    {
        $params = $this->getQueryParams();
        $type = $params['type'] ?? null;

        $tenantId = $this->db->getTenantId();

        // Categories are stored as JSON array in settings
        $categories = $this->db->fetch(
            "SELECT value FROM settings WHERE tenant_id = ? AND `key` = 'custom_categories'",
            [$tenantId]
        );

        $allCategories = $categories ? json_decode($categories['value'], true) : [];

        if ($type && isset($allCategories[$type])) {
            return $this->success($allCategories[$type]);
        }

        // Default categories
        $defaults = [
            'project_types' => ['Residential', 'Commercial', 'Industrial', 'Renovation', 'New Construction'],
            'expense_categories' => ['Labor', 'Materials', 'Equipment', 'Subcontractor', 'Permits', 'Insurance', 'Overhead'],
            'task_categories' => ['Planning', 'Design', 'Procurement', 'Construction', 'Inspection', 'Cleanup'],
            'document_categories' => ['Contract', 'Blueprint', 'Permit', 'Invoice', 'Photo', 'Report'],
            'inventory_categories' => ['Building Materials', 'Tools', 'Equipment', 'Safety Gear', 'Electrical', 'Plumbing'],
        ];

        foreach ($defaults as $key => $default) {
            if (!isset($allCategories[$key])) {
                $allCategories[$key] = $default;
            }
        }

        return $this->success($allCategories);
    }

    /**
     * Add custom category
     */
    public function storeCategory(): array
    {
        $this->authorize('settings.update');

        $data = $this->validate([
            'type' => 'required',
            'name' => 'required',
        ]);

        $tenantId = $this->db->getTenantId();

        // Get existing categories
        $existing = $this->db->fetch(
            "SELECT value FROM settings WHERE tenant_id = ? AND `key` = 'custom_categories'",
            [$tenantId]
        );

        $categories = $existing ? json_decode($existing['value'], true) : [];

        if (!isset($categories[$data['type']])) {
            $categories[$data['type']] = [];
        }

        // Check if already exists
        if (in_array($data['name'], $categories[$data['type']])) {
            $this->error('Category already exists', 422);
        }

        $categories[$data['type']][] = $data['name'];

        if ($existing) {
            $this->db->update('settings', [
                'value' => json_encode($categories),
            ], ['tenant_id' => $tenantId, 'key' => 'custom_categories']);
        } else {
            $this->db->insert('settings', [
                'tenant_id' => $tenantId,
                'key' => 'custom_categories',
                'value' => json_encode($categories),
            ]);
        }

        return $this->success($categories[$data['type']], 'Category added');
    }

    /**
     * Delete custom category
     */
    public function destroyCategory(string $id): array
    {
        $this->authorize('settings.update');

        $input = $this->getJsonInput();
        $type = $input['type'] ?? null;
        $name = $input['name'] ?? $id;

        if (!$type) {
            $this->error('Category type required', 422);
        }

        $tenantId = $this->db->getTenantId();

        $existing = $this->db->fetch(
            "SELECT value FROM settings WHERE tenant_id = ? AND `key` = 'custom_categories'",
            [$tenantId]
        );

        if (!$existing) {
            $this->error('Category not found', 404);
        }

        $categories = json_decode($existing['value'], true);

        if (!isset($categories[$type])) {
            $this->error('Category type not found', 404);
        }

        $index = array_search($name, $categories[$type]);
        if ($index === false) {
            $this->error('Category not found', 404);
        }

        unset($categories[$type][$index]);
        $categories[$type] = array_values($categories[$type]); // Re-index

        $this->db->update('settings', [
            'value' => json_encode($categories),
        ], ['tenant_id' => $tenantId, 'key' => 'custom_categories']);

        return $this->success(null, 'Category deleted');
    }
}
