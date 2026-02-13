<?php
/**
 * Email Template API Controller
 */

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Services\Email\TemplateService;

class EmailTemplateController extends Controller
{
    private TemplateService $templateService;

    public function __construct()
    {
        parent::__construct();
        $this->templateService = new TemplateService($this->db);
    }

    /**
     * List all templates
     */
    public function index(): array
    {
        $templates = $this->templateService->getAll();

        // Parse variables JSON
        foreach ($templates as &$template) {
            $template['variables'] = json_decode($template['variables'] ?? '[]', true);
        }

        return $this->success($templates);
    }

    /**
     * Get single template
     */
    public function show(string $id): array
    {
        $template = $this->templateService->getById((int) $id);

        if (!$template) {
            return $this->error('Template not found', 404);
        }

        $template['variables'] = json_decode($template['variables'] ?? '[]', true);

        return $this->success($template);
    }

    /**
     * Create new template
     */
    public function store(): array
    {
        $this->validate([
            'name' => 'required',
            'subject' => 'required',
            'body_html' => 'required',
        ]);

        $input = $this->getJsonInput();

        try {
            $template = $this->templateService->save([
                'name' => $input['name'],
                'slug' => $input['slug'] ?? null,
                'subject' => $input['subject'],
                'body_html' => $input['body_html'],
                'body_plain' => $input['body_plain'] ?? null,
                'variables' => $input['variables'] ?? [],
                'is_active' => $input['is_active'] ?? true,
            ]);

            $template['variables'] = json_decode($template['variables'] ?? '[]', true);

            return $this->success($template, 'Template created', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Update template
     */
    public function update(string $id): array
    {
        $template = $this->templateService->getById((int) $id);

        if (!$template) {
            return $this->error('Template not found', 404);
        }

        $input = $this->getJsonInput();

        try {
            $updated = $this->templateService->save([
                'id' => (int) $id,
                'name' => $input['name'] ?? $template['name'],
                'slug' => $input['slug'] ?? $template['slug'],
                'subject' => $input['subject'] ?? $template['subject'],
                'body_html' => $input['body_html'] ?? $template['body_html'],
                'body_plain' => $input['body_plain'] ?? $template['body_plain'],
                'variables' => $input['variables'] ?? json_decode($template['variables'], true),
                'is_active' => $input['is_active'] ?? $template['is_active'],
            ]);

            $updated['variables'] = json_decode($updated['variables'] ?? '[]', true);

            return $this->success($updated, 'Template updated');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Delete template
     */
    public function destroy(string $id): array
    {
        try {
            $this->templateService->delete((int) $id);
            return $this->success(null, 'Template deleted');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Preview template with sample data
     */
    public function preview(string $id): array
    {
        try {
            $preview = $this->templateService->preview((int) $id);
            return $this->success($preview);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Get available variables for a context
     */
    public function variables(string $context): array
    {
        $variables = $this->templateService->getVariablesForContext($context);
        return $this->success($variables);
    }
}
