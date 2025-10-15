<?php
/**
 * Gmail API Client
 * Handles sending emails via Gmail API using OAuth2
 */

require_once __DIR__ . '/../../config/gmail.php';

class GmailClient {
    private $accessToken;
    
    /**
     * Get OAuth2 authorization URL
     */
    public static function getAuthUrl() {
        $params = [
            'client_id' => GMAIL_CLIENT_ID,
            'redirect_uri' => GMAIL_REDIRECT_URI,
            'response_type' => 'code',
            'scope' => GMAIL_SCOPES,
            'access_type' => 'offline',
            'prompt' => 'consent'
        ];
        
        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
    }
    
    /**
     * Exchange authorization code for tokens
     */
    public static function exchangeCodeForTokens($code) {
        $tokenUrl = 'https://oauth2.googleapis.com/token';
        
        $params = [
            'code' => $code,
            'client_id' => GMAIL_CLIENT_ID,
            'client_secret' => GMAIL_CLIENT_SECRET,
            'redirect_uri' => GMAIL_REDIRECT_URI,
            'grant_type' => 'authorization_code'
        ];
        
        $ch = curl_init($tokenUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            throw new Exception('OAuth connection error: ' . $curlError);
        }
        
        if ($httpCode === 200) {
            $tokens = json_decode($response, true);
            self::saveTokens($tokens);
            return $tokens;
        }
        
        throw new Exception('Failed to exchange code for tokens: ' . $response);
    }
    
    /**
     * Refresh access token using refresh token
     */
    public static function refreshAccessToken() {
        $tokens = self::loadTokens();
        
        if (!isset($tokens['refresh_token'])) {
            throw new Exception('No refresh token available');
        }
        
        $tokenUrl = 'https://oauth2.googleapis.com/token';
        
        $params = [
            'client_id' => GMAIL_CLIENT_ID,
            'client_secret' => GMAIL_CLIENT_SECRET,
            'refresh_token' => $tokens['refresh_token'],
            'grant_type' => 'refresh_token'
        ];
        
        $ch = curl_init($tokenUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            throw new Exception('Token refresh connection error: ' . $curlError);
        }
        
        if ($httpCode === 200) {
            $newTokens = json_decode($response, true);
            // Keep the refresh token from old tokens
            $newTokens['refresh_token'] = $tokens['refresh_token'];
            self::saveTokens($newTokens);
            return $newTokens;
        }
        
        throw new Exception('Failed to refresh access token: ' . $response);
    }
    
    /**
     * Get valid access token
     */
    private static function getAccessToken() {
        $tokens = self::loadTokens();
        
        if (!$tokens) {
            throw new Exception('No tokens available. Please authenticate first.');
        }
        
        // Check if token is expired (with 5 minute buffer)
        if (isset($tokens['expires_at']) && $tokens['expires_at'] < (time() + 300)) {
            $tokens = self::refreshAccessToken();
        }
        
        return $tokens['access_token'];
    }
    
    /**
     * Send email via Gmail API
     */
    public static function sendEmail($to, $subject, $htmlBody, $textBody = null) {
        try {
            $accessToken = self::getAccessToken();
            // Create email message
            $message = self::createMessage($to, $subject, $htmlBody, $textBody);
            // Send via Gmail API
            $url = 'https://gmail.googleapis.com/gmail/v1/users/me/messages/send';
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['raw' => $message]));
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            if ($curlError) {
                throw new Exception('Gmail API connection error: ' . $curlError);
            }
            if ($httpCode === 200) {
                return json_decode($response, true);
            }
            throw new Exception('Failed to send email: ' . $response);
        } catch (Exception $e) {
            error_log('Gmail API Error: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Create RFC 2822 compliant email message
     */
    private static function createMessage($to, $subject, $htmlBody, $textBody = null) {
        $from = GMAIL_FROM_NAME . ' <' . GMAIL_FROM_EMAIL . '>';
        
        $boundary = uniqid('boundary_');
        
        $headers = [
            'From: ' . $from,
            'To: ' . $to,
            'Subject: ' . $subject,
            'MIME-Version: 1.0',
            'Content-Type: multipart/alternative; boundary="' . $boundary . '"'
        ];
        
        $body = [];
        
        // Add plain text version
        if ($textBody) {
            $body[] = '--' . $boundary;
            $body[] = 'Content-Type: text/plain; charset=UTF-8';
            $body[] = '';
            $body[] = $textBody;
        }
        
        // Add HTML version
        $body[] = '--' . $boundary;
        $body[] = 'Content-Type: text/html; charset=UTF-8';
        $body[] = '';
        $body[] = $htmlBody;
        $body[] = '--' . $boundary . '--';
        
        $message = implode("\r\n", $headers) . "\r\n\r\n" . implode("\r\n", $body);
        
        // Base64url encode
        return rtrim(strtr(base64_encode($message), '+/', '-_'), '=');
    }
    
    /**
     * Save tokens to file
     */
    private static function saveTokens($tokens) {
        // Add expiration timestamp
        if (isset($tokens['expires_in'])) {
            $tokens['expires_at'] = time() + $tokens['expires_in'];
        }
        
        $dir = dirname(GMAIL_TOKEN_FILE);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        file_put_contents(GMAIL_TOKEN_FILE, json_encode($tokens, JSON_PRETTY_PRINT));
    }
    
    /**
     * Load tokens from file
     */
    private static function loadTokens() {
        if (!file_exists(GMAIL_TOKEN_FILE)) {
            return null;
        }
        
        $contents = file_get_contents(GMAIL_TOKEN_FILE);
        return json_decode($contents, true);
    }
    
    /**
     * Check if authenticated
     */
    public static function isAuthenticated() {
        $tokens = self::loadTokens();
        return $tokens && isset($tokens['access_token']);
    }
}

