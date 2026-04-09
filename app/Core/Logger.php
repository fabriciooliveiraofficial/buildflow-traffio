<?php
/**
 * Construction ERP - Logger
 * Unified logging system for backend and frontend debugging.
 */

namespace App\Core;

class Logger
{
    private static string $logDir = ROOT_PATH . '/storage/logs';
    private static string $logFile = 'app.log';

    /**
     * Log a message to the application log
     */
    public static function log(string $message, string $level = 'INFO', array $context = []): void
    {
        try {
            if (!is_dir(self::$logDir)) {
                mkdir(self::$logDir, 0755, true);
            }

            $date = date('Y-m-d H:i:s');
            $contextStr = !empty($context) ? ' ' . json_encode($context) : '';
            $entry = "[$date] [$level] $message$contextStr" . PHP_EOL;

            file_put_contents(self::$logDir . '/' . self::$logFile, $entry, FILE_APPEND);
        } catch (\Exception $e) {
            // Fallback to error_log if file logging fails
            error_log("Logging failed: " . $e->getMessage());
        }
    }

    public static function info(string $message, array $context = []): void { self::log($message, 'INFO', $context); }
    public static function error(string $message, array $context = []): void { self::log($message, 'ERROR', $context); }
    public static function debug(string $message, array $context = []): void { self::log($message, 'DEBUG', $context); }

    /**
     * Read the log content
     */
    public static function read(int $lines = 100): string
    {
        $file = self::$logDir . '/' . self::$logFile;
        if (!file_exists($file)) return "No logs found.";

        // Basic implementation for reading last N lines
        $data = file($file);
        $data = array_slice($data, -$lines);
        return implode('', $data);
    }
}
