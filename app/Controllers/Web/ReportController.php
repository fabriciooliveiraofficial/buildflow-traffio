<?php
/**
 * Report Web Controller
 * 
 * Renders reports view.
 */

namespace App\Controllers\Web;

class ReportController
{
    /**
     * Reports page
     */
    public function index(): void
    {
        require VIEWS_PATH . '/reports/index.php';
    }
}
