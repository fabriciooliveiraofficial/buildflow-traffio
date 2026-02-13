<?php
/**
 * Employee Web Controller
 * 
 * Renders employee views.
 */

namespace App\Controllers\Web;

class EmployeeController
{
    /**
     * Employee detail page
     */
    public function show(string $id): void
    {
        $params = ['id' => $id];
        require VIEWS_PATH . '/employees/show.php';
    }
}
