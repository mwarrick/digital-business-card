<?php
/**
 * Simple Debug Logger
 * Writes to /storage/debug.log for easy access
 */

class DebugLogger {
    private static $logFile = __DIR__ . '/../../storage/debug.log';
    
    public static function log($message) {
        // Ensure storage directory exists
        $dir = dirname(self::$logFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] $message\n";
        @file_put_contents(self::$logFile, $logMessage, FILE_APPEND);
    }
    
    public static function clear() {
        if (file_exists(self::$logFile)) {
            @file_put_contents(self::$logFile, "");
        }
    }
    
    public static function getLog($lines = 100) {
        if (!file_exists(self::$logFile)) {
            return "No log entries yet. The log will appear here once errors are logged.";
        }
        
        $log = file_get_contents(self::$logFile);
        if (empty(trim($log))) {
            return "Log file is empty.";
        }
        
        $logLines = explode("\n", $log);
        $logLines = array_filter($logLines); // Remove empty lines
        $logLines = array_slice($logLines, -$lines); // Get last N lines
        
        return implode("\n", $logLines);
    }
}

