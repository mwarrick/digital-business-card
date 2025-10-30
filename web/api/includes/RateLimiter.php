<?php
/**
 * Simple rate limiter using file-based storage
 * Tracks requests per IP with configurable windows and limits
 */

class RateLimiter {
    private string $storageDir;
    private array $whitelist = ['76.175.179.85']; // IPs to skip rate limiting
    
    public function __construct(string $storageDir = null) {
        $this->storageDir = $storageDir ?: sys_get_temp_dir() . '/rate_limit';
        if (!is_dir($this->storageDir)) {
            mkdir($this->storageDir, 0755, true);
        }
    }
    
    /**
     * Check if request is allowed within rate limit
     * @param string $key Unique identifier (e.g., IP address)
     * @param int $limit Max requests allowed
     * @param int $windowSeconds Time window in seconds
     * @return bool True if allowed, false if rate limited
     */
    public function isAllowed(string $key, int $limit, int $windowSeconds): bool {
        // Skip rate limiting for whitelisted IPs
        if (in_array($key, $this->whitelist)) {
            return true;
        }
        
        $file = $this->storageDir . '/' . md5($key) . '.json';
        $now = time();
        $windowStart = $now - $windowSeconds;
        
        // Load existing data
        $data = [];
        if (file_exists($file)) {
            $content = file_get_contents($file);
            if ($content !== false) {
                $data = json_decode($content, true) ?: [];
            }
        }
        
        // Clean old entries
        $data = array_filter($data, function($timestamp) use ($windowStart) {
            return $timestamp > $windowStart;
        });
        
        // Check if limit exceeded
        if (count($data) >= $limit) {
            return false;
        }
        
        // Add current request
        $data[] = $now;
        
        // Save updated data
        file_put_contents($file, json_encode($data), LOCK_EX);
        
        return true;
    }
    
    /**
     * Get remaining requests for a key
     * @param string $key Unique identifier
     * @param int $limit Max requests allowed
     * @param int $windowSeconds Time window in seconds
     * @return int Number of remaining requests
     */
    public function getRemaining(string $key, int $limit, int $windowSeconds): int {
        if (in_array($key, $this->whitelist)) {
            return $limit; // Unlimited for whitelisted IPs
        }
        
        $file = $this->storageDir . '/' . md5($key) . '.json';
        $now = time();
        $windowStart = $now - $windowSeconds;
        
        if (!file_exists($file)) {
            return $limit;
        }
        
        $content = file_get_contents($file);
        if ($content === false) {
            return $limit;
        }
        
        $data = json_decode($content, true) ?: [];
        $data = array_filter($data, function($timestamp) use ($windowStart) {
            return $timestamp > $windowStart;
        });
        
        return max(0, $limit - count($data));
    }
    
    /**
     * Add IP to whitelist
     * @param string $ip IP address to whitelist
     */
    public function addToWhitelist(string $ip): void {
        if (!in_array($ip, $this->whitelist)) {
            $this->whitelist[] = $ip;
        }
    }
    
    /**
     * Remove IP from whitelist
     * @param string $ip IP address to remove
     */
    public function removeFromWhitelist(string $ip): void {
        $this->whitelist = array_filter($this->whitelist, function($whitelistedIp) use ($ip) {
            return $whitelistedIp !== $ip;
        });
    }
}