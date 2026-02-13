<?php
/**
 * Settings Web Controller
 * 
 * Renders settings view.
 */

namespace App\Controllers\Web;

class SettingsController
{
    /**
     * Settings page
     */
    public function index(): void
    {
        require VIEWS_PATH . '/settings/index.php';
    }
}
