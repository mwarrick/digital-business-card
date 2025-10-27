<?php
/**
 * Email Service
 * Handles sending various types of emails using Gmail API
 */

require_once __DIR__ . '/Database.php';

class EmailService {
    
    /**
     * Send lead confirmation email
     */
    public function sendLeadConfirmationEmail($emailData) {
        try {
            // Load required includes for email sending
            require_once __DIR__ . '/GmailClient.php';
            require_once __DIR__ . '/EmailTemplates.php';
            
            // Generate tracking token
            $token = $this->generateTrackingToken();
            
            // Get email template
            $email = EmailTemplates::leadConfirmation(
                $emailData['from_name'],
                $emailData['to_name'],
                $emailData['card_url'],
                $token
            );
            
            // Send email with CC to business card owner and custom from/to addresses
            $result = GmailClient::sendEmail(
                $emailData['to_email'],
                $email['subject'],
                $email['html'],
                $email['text'],
                $emailData['from_email'], // CC the business card owner
                $emailData['from_email'], // From email (business card owner's email)
                $emailData['from_name'],  // From name (business card owner's name)
                $emailData['to_name']     // To name (lead's name)
            );
            
            // Log email tracking
            $this->logEmailTracking($token, $emailData, 'lead_confirmation');
            
            return $result;
            
        } catch (Exception $e) {
            error_log("EmailService::sendLeadConfirmationEmail error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Generate tracking token for email analytics
     */
    private function generateTrackingToken() {
        return bin2hex(random_bytes(16));
    }
    
    /**
     * Log email tracking data
     */
    private function logEmailTracking($token, $emailData, $emailType) {
        try {
            $db = Database::getInstance()->getConnection();
            
            // Check if email_tracking table exists
            $stmt = $db->query("SHOW TABLES LIKE 'email_tracking'");
            if ($stmt->rowCount() == 0) {
                error_log("EmailService::logEmailTracking - email_tracking table does not exist, skipping tracking");
                return;
            }
            
            $stmt = $db->prepare("
                INSERT INTO email_tracking (
                    token, email_type, to_email, from_email, 
                    subject, card_id, sent_at, status
                ) VALUES (?, ?, ?, ?, ?, ?, NOW(), 'sent')
            ");
            
            $stmt->execute([
                $token,
                $emailType,
                $emailData['to_email'],
                $emailData['from_email'],
                $emailData['subject'],
                $emailData['card_id']
            ]);
            
        } catch (Exception $e) {
            error_log("EmailService::logEmailTracking error: " . $e->getMessage());
        }
    }
}
