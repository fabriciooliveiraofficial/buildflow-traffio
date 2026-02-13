<?php
/**
 * Client Web Controller
 * 
 * Renders client views.
 */

namespace App\Controllers\Web;

class ClientController
{
    /**
     * Client list page
     */
    public function index(): void
    {
        require VIEWS_PATH . '/clients/index.php';
    }

    /**
     * Client detail page
     */
    public function show(string $id): void
    {
        $params = ['id' => $id];
        require VIEWS_PATH . '/clients/show.php';
    }
}
