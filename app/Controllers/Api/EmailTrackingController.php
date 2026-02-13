<?php
/**
 * Email Tracking Controller
 * 
 * Public endpoints for tracking pixel and click tracking (no auth required)
 */

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Database;
use App\Services\Email\TrackingService;

class EmailTrackingController extends Controller
{
    private TrackingService $trackingService;

    public function __construct()
    {
        // Don't call parent - these are public endpoints
        $this->db = new Database();
        $this->trackingService = new TrackingService($this->db);
    }

    /**
     * Track email open (returns 1x1 transparent GIF)
     */
    public function trackOpen(string $token): void
    {
        // Record the open
        $this->trackingService->recordOpen($token);

        // Return 1x1 transparent GIF
        header('Content-Type: image/gif');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        // 1x1 transparent GIF
        echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
        exit;
    }

    /**
     * Track link click (redirects to original URL)
     */
    public function trackClick(string $token): void
    {
        $encodedUrl = $_GET['url'] ?? '';

        $originalUrl = $this->trackingService->recordClick($token, $encodedUrl);

        if ($originalUrl) {
            header('Location: ' . $originalUrl, true, 302);
        } else {
            // Fallback - redirect to homepage
            header('Location: /', true, 302);
        }
        exit;
    }

    /**
     * Get email analytics (authenticated)
     */
    public function analytics(): array
    {
        $days = (int) ($_GET['days'] ?? 30);
        $days = min(365, max(7, $days));

        $analytics = $this->trackingService->getAnalytics($days);

        return ['success' => true, 'data' => $analytics];
    }
}
