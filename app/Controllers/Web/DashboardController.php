<?php
/**
 * Dashboard Web Controller
 * 
 * Renders dashboard view.
 */

namespace App\Controllers\Web;

class DashboardController
{
    /**
     * Dashboard page
     */
    public function index(): void
    {
        require VIEWS_PATH . '/dashboard.php';
    }
}
