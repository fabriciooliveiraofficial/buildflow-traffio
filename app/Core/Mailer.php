<?php
/**
 * Mailer Service
 * 
 * SMTP email sending service using PHPMailer-style approach
 * with native PHP sockets for SSL SMTP connections.
 */

namespace App\Core;

class Mailer
{
    private string $host;
    private int $port;
    private string $username;
    private string $password;
    private string $encryption;
    private string $fromAddress;
    private string $fromName;
    private array $errors = [];

    public function __construct()
    {
        $this->host = $_ENV['MAIL_HOST'] ?? 'smtp.hostinger.com';
        $this->port = (int) ($_ENV['MAIL_PORT'] ?? 465);
        $this->username = $_ENV['MAIL_USERNAME'] ?? '';
        $this->password = $_ENV['MAIL_PASSWORD'] ?? '';
        $this->encryption = $_ENV['MAIL_ENCRYPTION'] ?? 'ssl';
        $this->fromAddress = $_ENV['MAIL_FROM_ADDRESS'] ?? $this->username;

        // Handle fromName - strip quotes and resolve ${VAR} patterns
        $fromName = $_ENV['MAIL_FROM_NAME'] ?? '';
        $fromName = trim($fromName, '"\''); // Remove surrounding quotes

        // If it contains ${...} pattern (unresolved variable), use APP_NAME or default
        if (empty($fromName) || preg_match('/\$\{[^}]+\}/', $fromName)) {
            $appName = $_ENV['APP_NAME'] ?? 'Buildflow';
            $fromName = trim($appName, '"\'');
        }

        $this->fromName = $fromName ?: 'Buildflow';
    }

    /**
     * Send an email
     */
    public function send(string $to, string $subject, string $body, bool $isHtml = true): bool
    {
        $this->errors = [];

        // Validate email
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $this->errors[] = 'Invalid recipient email address';
            return false;
        }

        try {
            // Build headers
            $headers = $this->buildHeaders($isHtml);

            // For SSL SMTP, we need to use fsockopen
            $result = $this->sendViaSMTP($to, $subject, $body, $headers, $isHtml);

            if (!$result) {
                // Fallback to PHP mail() if SMTP fails
                error_log("SMTP failed, attempting PHP mail()");
                return $this->sendViaMail($to, $subject, $body, $headers);
            }

            return true;
        } catch (\Exception $e) {
            $this->errors[] = $e->getMessage();
            error_log("Mailer error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send email via SMTP with SSL
     */
    private function sendViaSMTP(string $to, string $subject, string $body, array $headers, bool $isHtml): bool
    {
        $protocol = $this->encryption === 'ssl' ? 'ssl://' : '';
        $socket = @fsockopen($protocol . $this->host, $this->port, $errno, $errstr, 30);

        if (!$socket) {
            $this->errors[] = "Could not connect to SMTP server: $errstr ($errno)";
            return false;
        }

        // Set stream timeout
        stream_set_timeout($socket, 30);

        try {
            // Read greeting
            $this->readResponse($socket);

            // EHLO
            $this->sendCommand($socket, "EHLO " . gethostname());
            $this->readResponse($socket);

            // AUTH LOGIN
            $this->sendCommand($socket, "AUTH LOGIN");
            $response = $this->readResponse($socket);

            if (strpos($response, '334') === false) {
                throw new \Exception("AUTH LOGIN not accepted");
            }

            // Username
            $this->sendCommand($socket, base64_encode($this->username));
            $this->readResponse($socket);

            // Password
            $this->sendCommand($socket, base64_encode($this->password));
            $response = $this->readResponse($socket);

            if (strpos($response, '235') === false) {
                throw new \Exception("Authentication failed");
            }

            // MAIL FROM
            $this->sendCommand($socket, "MAIL FROM:<{$this->fromAddress}>");
            $this->readResponse($socket);

            // RCPT TO
            $this->sendCommand($socket, "RCPT TO:<{$to}>");
            $this->readResponse($socket);

            // DATA
            $this->sendCommand($socket, "DATA");
            $response = $this->readResponse($socket);

            if (strpos($response, '354') === false) {
                throw new \Exception("DATA command not accepted");
            }

            // Build email content
            $message = $this->buildMessage($to, $subject, $body, $headers, $isHtml);

            // Send message
            fwrite($socket, $message);
            fwrite($socket, "\r\n.\r\n");
            $this->readResponse($socket);

            // QUIT
            $this->sendCommand($socket, "QUIT");

            fclose($socket);
            return true;

        } catch (\Exception $e) {
            fclose($socket);
            $this->errors[] = $e->getMessage();
            return false;
        }
    }

    /**
     * Build email message
     */
    private function buildMessage(string $to, string $subject, string $body, array $headers, bool $isHtml): string
    {
        $boundary = md5(uniqid(time()));

        $message = "From: {$this->fromName} <{$this->fromAddress}>\r\n";
        $message .= "To: {$to}\r\n";
        $message .= "Subject: {$subject}\r\n";
        $message .= "MIME-Version: 1.0\r\n";

        if ($isHtml) {
            $message .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";
            $message .= "\r\n";
            $message .= "--{$boundary}\r\n";
            $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
            $message .= "\r\n";
            $message .= strip_tags($body) . "\r\n";
            $message .= "--{$boundary}\r\n";
            $message .= "Content-Type: text/html; charset=UTF-8\r\n";
            $message .= "\r\n";
            $message .= $body . "\r\n";
            $message .= "--{$boundary}--\r\n";
        } else {
            $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
            $message .= "\r\n";
            $message .= $body . "\r\n";
        }

        return $message;
    }

    /**
     * Send command to SMTP server
     */
    private function sendCommand($socket, string $command): void
    {
        fwrite($socket, $command . "\r\n");
    }

    /**
     * Read response from SMTP server
     */
    private function readResponse($socket): string
    {
        $response = '';
        while ($line = fgets($socket, 515)) {
            $response .= $line;
            // Check if this is the last line (no continuation)
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }
        return $response;
    }

    /**
     * Build email headers
     */
    private function buildHeaders(bool $isHtml): array
    {
        $headers = [
            'From' => "{$this->fromName} <{$this->fromAddress}>",
            'Reply-To' => $this->fromAddress,
            'X-Mailer' => 'Buildflow-Mailer/1.0',
            'MIME-Version' => '1.0',
        ];

        if ($isHtml) {
            $headers['Content-Type'] = 'text/html; charset=UTF-8';
        } else {
            $headers['Content-Type'] = 'text/plain; charset=UTF-8';
        }

        return $headers;
    }

    /**
     * Fallback: Send via PHP mail()
     */
    private function sendViaMail(string $to, string $subject, string $body, array $headers): bool
    {
        $headerString = '';
        foreach ($headers as $key => $value) {
            $headerString .= "{$key}: {$value}\r\n";
        }

        return mail($to, $subject, $body, $headerString);
    }

    /**
     * Send invitation email
     */
    public function sendInvitation(array $invitation, string $tenantName, string $acceptUrl): bool
    {
        $subject = "You're invited to join {$tenantName} on Buildflow";

        $body = $this->getInvitationTemplate([
            'tenant_name' => $tenantName,
            'invitee_name' => $invitation['first_name'] ?? 'there',
            'inviter_name' => $invitation['inviter_name'] ?? 'The team',
            'role_name' => $invitation['role_name'] ?? 'Team Member',
            'message' => $invitation['message'] ?? '',
            'accept_url' => $acceptUrl,
            'expires_at' => $invitation['expires_at'] ?? '',
        ]);

        return $this->send($invitation['email'], $subject, $body, true);
    }

    /**
     * Get invitation email template
     */
    private function getInvitationTemplate(array $data): string
    {
        $message = $data['message'] ? "<p style=\"color: #555; font-style: italic; padding: 15px; background: #f9f9f9; border-left: 3px solid #4F46E5; margin: 20px 0;\">\"{$data['message']}\"</p>" : '';

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; background-color: #f4f4f5;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f4f5; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 12px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
                    <!-- Header -->
                    <tr>
                        <td style="background-color: #4F46E5; padding: 40px; text-align: center; border-radius: 12px 12px 0 0;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 28px; font-weight: 600;">
                                You're Invited! 🎉
                            </h1>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px;">
                            <p style="color: #333; font-size: 16px; line-height: 1.6; margin: 0 0 20px;">
                                Hi {$data['invitee_name']},
                            </p>
                            <p style="color: #333; font-size: 16px; line-height: 1.6; margin: 0 0 20px;">
                                <strong>{$data['inviter_name']}</strong> has invited you to join <strong>{$data['tenant_name']}</strong> on Buildflow as a <strong>{$data['role_name']}</strong>.
                            </p>
                            
                            {$message}
                            
                            <p style="color: #333; font-size: 16px; line-height: 1.6; margin: 0 0 30px;">
                                Click the button below to accept this invitation and create your account:
                            </p>
                            
                            <!-- CTA Button -->
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center">
                                        <a href="{$data['accept_url']}" style="display: inline-block; background-color: #4F46E5; color: #ffffff; text-decoration: none; padding: 16px 40px; border-radius: 8px; font-size: 16px; font-weight: 600;">
                                            Accept Invitation
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            
                            <p style="color: #666; font-size: 14px; line-height: 1.6; margin: 30px 0 0; text-align: center;">
                                This invitation expires on <strong>{$data['expires_at']}</strong>
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="padding: 30px 40px; background-color: #f9fafb; border-radius: 0 0 12px 12px; border-top: 1px solid #e5e7eb;">
                            <p style="color: #9ca3af; font-size: 13px; line-height: 1.5; margin: 0; text-align: center;">
                                If you didn't expect this invitation, you can safely ignore this email.<br>
                                If you have questions, reply to this email or contact support.
                            </p>
                        </td>
                    </tr>
                </table>
                
                <!-- Branding -->
                <p style="color: #9ca3af; font-size: 12px; margin-top: 20px; text-align: center;">
                    Powered by <strong>Buildflow</strong> - Construction Management Made Simple
                </p>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }

    /**
     * Get errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get last error
     */
    public function getLastError(): ?string
    {
        return end($this->errors) ?: null;
    }
}
