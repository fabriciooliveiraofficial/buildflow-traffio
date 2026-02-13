<?php
/**
 * Time Tracking Web Controller
 * 
 * Renders time tracking view.
 */

namespace App\Controllers\Web;

class TimeTrackingController
{
    /**
     * Time tracking page
     */
    public function index(): void
    {
        require VIEWS_PATH . '/time-tracking/index.php';
    }
}
