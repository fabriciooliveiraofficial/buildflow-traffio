<?php
/**
 * Email Queue Processor (Cron Job)
 * 
 * Run this script via cron to process queued emails and check for overdue invoices.
 * Recommended: Every 5 minutes
 * 
 * Cron example:
 * * /5 * * * * php /path/to/cron/process-email-queue.php >> /var/log/email-queue.log 2>&1
 */

// Bootstrap the application
define('APP_PATH', dirname(__DIR__) . '/app');
require_once dirname(__DIR__) . '/vendor/autoload.php';

use App\Core\Database;
use App\Services\Email\EmailService;
use App\Services\Email\AutomationService;

// Initialize
$startTime = microtime(true);
$processedCount = 0;
$errorCount = 0;

echo "[" . date('Y-m-d H:i:s') . "] Starting email queue processor...\n";

try {
    // Get database connection
    $db = new Database();

    // 1. Process pending emails in queue
    echo "Processing email queue...\n";

    $pendingEmails = $db->fetchAll(
        "SELECT q.*, t.id as tid 
         FROM email_queue q
         JOIN tenants t ON q.tenant_id = t.id
         WHERE q.status = 'pending' 
           AND (q.scheduled_at IS NULL OR q.scheduled_at <= NOW())
           AND q.attempts < q.max_attempts
         ORDER BY q.priority ASC, q.created_at ASC
         LIMIT 50"
    );

    echo "Found " . count($pendingEmails) . " pending emails\n";

    foreach ($pendingEmails as $email) {
        try {
            // Set tenant context
            $db->setTenantId($email['tenant_id']);

            // Mark as processing
            $db->update('email_queue', [
                'status' => 'processing',
                'processing_at' => date('Y-m-d H:i:s'),
                'attempts' => $email['attempts'] + 1,
            ], ['id' => $email['id']]);

            // Initialize email service for this tenant
            $emailService = new EmailService($db);

            // Build email data
            $toAddresses = json_decode($email['to_addresses'], true);
            $ccAddresses = json_decode($email['cc_addresses'] ?? '[]', true);
            $bccAddresses = json_decode($email['bcc_addresses'] ?? '[]', true);

            $emailData = [
                'to' => $toAddresses,
                'cc' => $ccAddresses,
                'bcc' => $bccAddresses,
                'subject' => $email['subject'],
                'body_html' => $email['body_html'],
                'body_plain' => $email['body_plain'],
                'context_type' => $email['context_type'],
                'context_id' => $email['context_id'],
            ];

            // Send the email
            $result = $emailService->send($emailData, $email['id']);

            if ($result['success']) {
                $db->update('email_queue', [
                    'status' => 'sent',
                    'sent_at' => date('Y-m-d H:i:s'),
                    'last_error' => null,
                ], ['id' => $email['id']]);

                $processedCount++;
                echo "  ✓ Sent email #{$email['id']} to " . $toAddresses[0]['email'] . "\n";
            } else {
                throw new \Exception($result['error'] ?? 'Unknown error');
            }

        } catch (\Exception $e) {
            $error = $e->getMessage();
            $newStatus = $email['attempts'] + 1 >= $email['max_attempts'] ? 'failed' : 'pending';

            $db->update('email_queue', [
                'status' => $newStatus,
                'last_error' => $error,
            ], ['id' => $email['id']]);

            $errorCount++;
            echo "  ✗ Failed email #{$email['id']}: $error\n";
        }
    }

    // 2. Process overdue invoice reminders
    echo "\nChecking for overdue invoices...\n";

    $automationService = new AutomationService($db);
    $overdueCount = $automationService->processOverdueInvoices();

    echo "Queued $overdueCount overdue invoice reminders\n";

    // 3. Reset daily email counters (if needed)
    $today = date('Y-m-d');
    $db->query(
        "UPDATE email_settings 
         SET emails_sent_today = 0, last_reset_date = ? 
         WHERE last_reset_date IS NULL OR last_reset_date < ?",
        [$today, $today]
    );

} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

$duration = round(microtime(true) - $startTime, 2);
echo "\n[" . date('Y-m-d H:i:s') . "] Completed in {$duration}s - Processed: $processedCount, Errors: $errorCount\n";
