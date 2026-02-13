<?php
/**
 * Invoice Web Controller
 * 
 * Renders invoice views.
 */

namespace App\Controllers\Web;

class InvoiceController
{
    /**
     * Invoice list page
     */
    public function index(): void
    {
        require VIEWS_PATH . '/invoices/index.php';
    }

    /**
     * Invoice detail page
     */
    public function show(string $id): void
    {
        $params = ['id' => $id];
        require VIEWS_PATH . '/invoices/show.php';
    }
}
