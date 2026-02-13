<?php
/**
 * Project Web Controller
 * 
 * Renders project views (list and detail pages).
 */

namespace App\Controllers\Web;

class ProjectController
{
    /**
     * Project list page
     */
    public function index(): void
    {
        require VIEWS_PATH . '/projects/index.php';
    }

    /**
     * Project detail page
     */
    public function show(string $id): void
    {
        $params = ['id' => $id];
        require VIEWS_PATH . '/projects/show.php';
    }
}
