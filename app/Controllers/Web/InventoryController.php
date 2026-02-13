<?php
/**
 * Inventory Web Controller
 * 
 * Renders inventory view.
 */

namespace App\Controllers\Web;

class InventoryController
{
    /**
     * Inventory page
     */
    public function index(): void
    {
        require VIEWS_PATH . '/inventory/index.php';
    }
}
