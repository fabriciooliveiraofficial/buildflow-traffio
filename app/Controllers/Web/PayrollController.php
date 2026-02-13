<?php
/**
 * Payroll Web Controller
 * 
 * Renders payroll views.
 */

namespace App\Controllers\Web;

class PayrollController
{
    /**
     * Payroll list page
     */
    public function index(): void
    {
        require VIEWS_PATH . '/payroll/index.php';
    }

    /**
     * Payroll period detail page
     */
    public function show(string $id): void
    {
        $params = ['id' => $id];
        require VIEWS_PATH . '/payroll/show.php';
    }
}
