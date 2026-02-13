<?php
/**
 * Email Automation API Controller
 */

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Services\Email\AutomationService;
use App\Services\Email\TemplateService;

class EmailAutomationController extends Controller
{
    private AutomationService $automationService;
    private TemplateService $templateService;

    public function __construct()
    {
        parent::__construct();
        $this->automationService = new AutomationService($this->db);
        $this->templateService = new TemplateService($this->db);
    }

    /**
     * List all automations
     */
    public function index(): array
    {
        $automations = $this->automationService->getAll();
        return $this->success($automations);
    }

    /**
     * Get available triggers
     */
    public function triggers(): array
    {
        return $this->success(AutomationService::getTriggers());
    }

    /**
     * Update automation settings
     */
    public function update(string $triggerEvent): array
    {
        $input = $this->getJsonInput();

        try {
            $result = $this->automationService->save($triggerEvent, [
                'template_id' => $input['template_id'] ?? null,
                'is_enabled' => $input['is_enabled'] ?? false,
                'delay_minutes' => $input['delay_minutes'] ?? 0,
                'send_to' => $input['send_to'] ?? 'client',
                'custom_recipients' => $input['custom_recipients'] ?? [],
                'conditions' => $input['conditions'] ?? [],
            ]);

            return $this->success($result, 'Automation updated');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Toggle automation on/off
     */
    public function toggle(string $triggerEvent): array
    {
        $input = $this->getJsonInput();

        try {
            $this->automationService->save($triggerEvent, [
                'is_enabled' => $input['is_enabled'] ?? false,
            ]);

            $status = $input['is_enabled'] ? 'enabled' : 'disabled';
            return $this->success(null, "Automation $status");
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Get templates for dropdown
     */
    public function templates(): array
    {
        $templates = $this->templateService->getAll();
        return $this->success($templates);
    }
}
