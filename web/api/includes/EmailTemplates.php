<?php
/**
 * Email Templates
 * Pre-formatted email templates for various notifications
 */

class EmailTemplates {
    
    /**
     * Registration verification email
     */
    public static function registrationVerification($verificationCode, $email) {
        $subject = 'Verify your ShareMyCard account';
        
        // Create verification link with pre-filled code
        $verifyUrl = 'https://sharemycard.app/user/register.php?code=' . urlencode($verificationCode) . '&email=' . urlencode($email);
        
        $html = self::getEmailWrapper(
            'Verify Your Email',
            '<p>Thank you for registering with ShareMyCard!</p>
            <p>Your verification code is:</p>
            <div style="background: #f5f5f5; padding: 20px; text-align: center; font-size: 32px; font-weight: bold; letter-spacing: 5px; margin: 20px 0; border-radius: 5px;">
                ' . htmlspecialchars($verificationCode) . '
            </div>
            <p style="text-align: center; margin: 30px 0;">
                <a href="' . htmlspecialchars($verifyUrl) . '" style="display: inline-block; padding: 15px 40px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 16px;">
                    Verify Email & Complete Registration
                </a>
            </p>
            <p style="text-align: center; color: #666; font-size: 14px;">Or copy and paste this link into your browser:</p>
            <p style="text-align: center; word-break: break-all; color: #667eea; font-size: 12px;">
                ' . htmlspecialchars($verifyUrl) . '
            </p>
            <p>This code will expire in 10 minutes.</p>
            <p>If you didn\'t request this verification, please ignore this email.</p>'
        );
        
        $text = "Thank you for registering with ShareMyCard!\n\n"
              . "Your verification code is: {$verificationCode}\n\n"
              . "Click this link to complete your registration:\n{$verifyUrl}\n\n"
              . "Or enter the code manually to complete your registration.\n"
              . "This code will expire in 10 minutes.\n\n"
              . "If you didn't request this verification, please ignore this email.";
        
        return [
            'subject' => $subject,
            'html' => $html,
            'text' => $text
        ];
    }
    
    /**
     * Login verification email
     */
    public static function loginVerification($verificationCode, $email, $isAdmin = false, $isApp = false) {
        $subject = 'Your ShareMyCard login code';
        
        // Create verification link with pre-filled code - use admin or user login page
        // Only include link if NOT from mobile app
        $loginPath = $isAdmin ? '/admin/login.php' : '/user/login.php';
        $verifyUrl = 'https://sharemycard.app' . $loginPath . '?code=' . urlencode($verificationCode) . '&email=' . urlencode($email);
        
        // Build email content based on whether it's from app or web
        $content = '<p>Someone is trying to log in to your ShareMyCard account.</p>
            <p>Your login code is:</p>
            <div style="background: #f5f5f5; padding: 20px; text-align: center; font-size: 32px; font-weight: bold; letter-spacing: 5px; margin: 20px 0; border-radius: 5px;">
                ' . htmlspecialchars($verificationCode) . '
            </div>';
        
        if (!$isApp) {
            // Only include web link if NOT from mobile app
            $content .= '<p style="text-align: center; margin: 30px 0;">
                <a href="' . htmlspecialchars($verifyUrl) . '" style="display: inline-block; padding: 15px 40px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 16px;">
                    Complete Login
                </a>
            </p>
            <p style="text-align: center; color: #666; font-size: 14px;">Or copy and paste this link into your browser:</p>
            <p style="text-align: center; word-break: break-all; color: #667eea; font-size: 12px;">
                ' . htmlspecialchars($verifyUrl) . '
            </p>';
        } else {
            // For mobile app users - just instructions to enter the code
            $content .= '<p style="text-align: center; margin: 20px 0; color: #666;">
                Enter this code in the ShareMyCard app to complete your login.
            </p>';
        }
        
        $content .= '<p>This code will expire in 10 minutes.</p>
            <p>If you didn\'t try to log in, please ignore this email.</p>';
        
        $html = self::getEmailWrapper('Login Verification', $content);
        
        // Plain text version
        $text = "Someone is trying to log in to your ShareMyCard account.\n\n"
              . "Your login code is: {$verificationCode}\n\n";
        
        if (!$isApp) {
            $text .= "Click this link to complete your login:\n{$verifyUrl}\n\n"
                  . "Or enter the code manually to complete your login.\n";
        } else {
            $text .= "Enter this code in the ShareMyCard app to complete your login.\n\n";
        }
        
        $text .= "This code will expire in 10 minutes.\n\n"
              . "If you didn't try to log in, please ignore this email.";
        
        return [
            'subject' => $subject,
            'html' => $html,
            'text' => $text
        ];
    }
    
    /**
     * Password reset email
     */
    public static function passwordReset($resetCode, $email) {
        $subject = 'Reset your ShareMyCard password';
        
        $html = self::getEmailWrapper(
            'Password Reset Request',
            '<p>We received a request to reset your ShareMyCard password.</p>
            <p>Your password reset code is:</p>
            <div style="background: #f5f5f5; padding: 20px; text-align: center; font-size: 32px; font-weight: bold; letter-spacing: 5px; margin: 20px 0; border-radius: 5px;">
                ' . htmlspecialchars($resetCode) . '
            </div>
            <p>Enter this code in the app to reset your password.</p>
            <p>This code will expire in 30 minutes.</p>
            <p>If you didn\'t request a password reset, please ignore this email and ensure your account is secure.</p>'
        );
        
        $text = "We received a request to reset your ShareMyCard password.\n\n"
              . "Your password reset code is: {$resetCode}\n\n"
              . "Enter this code in the app to reset your password.\n"
              . "This code will expire in 30 minutes.\n\n"
              . "If you didn't request a password reset, please ignore this email and ensure your account is secure.";
        
        return [
            'subject' => $subject,
            'html' => $html,
            'text' => $text
        ];
    }
    
    /**
     * Password set confirmation email
     */
    public static function passwordSet($email) {
        $subject = 'Password set successfully - ShareMyCard';
        
        $html = self::getEmailWrapper(
            'Password Set Successfully',
            '<p>Your ShareMyCard account password has been set successfully.</p>
            <p>You can now login using either:</p>
            <ul>
                <li>Your email address and password</li>
                <li>Your email address and verification code (sent to your email)</li>
            </ul>
            <p>If you did not set this password, please contact support immediately.</p>'
        );
        
        $text = "Your ShareMyCard account password has been set successfully.\n\n"
              . "You can now login using either:\n"
              . "- Your email address and password\n"
              . "- Your email address and verification code (sent to your email)\n\n"
              . "If you did not set this password, please contact support immediately.";
        
        return [
            'subject' => $subject,
            'html' => $html,
            'text' => $text
        ];
    }
    
    /**
     * Password changed notification email
     */
    public static function passwordChanged($email) {
        $subject = 'Password changed - ShareMyCard';
        
        $html = self::getEmailWrapper(
            'Password Changed',
            '<p>Your ShareMyCard account password has been changed successfully.</p>
            <p>If you did not make this change, please contact support immediately as your account may have been compromised.</p>
            <p>For security, we recommend:</p>
            <ul>
                <li>Using a strong, unique password</li>
                <li>Not sharing your password with anyone</li>
                <li>Logging out of all devices if you suspect unauthorized access</li>
            </ul>'
        );
        
        $text = "Your ShareMyCard account password has been changed successfully.\n\n"
              . "If you did not make this change, please contact support immediately as your account may have been compromised.\n\n"
              . "For security, we recommend:\n"
              . "- Using a strong, unique password\n"
              . "- Not sharing your password with anyone\n"
              . "- Logging out of all devices if you suspect unauthorized access";
        
        return [
            'subject' => $subject,
            'html' => $html,
            'text' => $text
        ];
    }
    
    /**
     * Welcome email (after successful verification)
     */
    public static function welcome($firstName = null) {
        $subject = 'Welcome to ShareMyCard!';
        
        $greeting = $firstName ? "Hi {$firstName}!" : "Welcome!";
        
        $html = self::getEmailWrapper(
            'Welcome to ShareMyCard',
            "<p>{$greeting}</p>
            <p>Your account has been successfully verified. You're all set to create and share your digital business cards!</p>
            <h3 style='color: #333; margin: 20px 0 10px 0;'>Getting Started:</h3>
            <ul style='line-height: 1.8;'>
                <li>Create your first business card</li>
                <li>Add your contact information and social links</li>
                <li>Generate a QR code to share with others</li>
                <li>Scan QR codes to save contacts</li>
            </ul>
            <p style='text-align: center; margin: 30px 0;'>
                <a href='https://sharemycard.app/' style='display: inline-block; padding: 15px 40px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 16px;'>
                    Visit ShareMyCard
                </a>
            </p>
            <p style='text-align: center; color: #666; font-size: 14px;'>Or copy and paste this link into your browser:</p>
            <p style='text-align: center; word-break: break-all; color: #667eea; font-size: 12px;'>https://sharemycard.app/</p>
            <p>If you have any questions or need help, feel free to reach out to our support team.</p>
            <p>Happy networking!</p>"
        );
        
        $text = "{$greeting}\n\n"
              . "Your account has been successfully verified. You're all set to create and share your digital business cards!\n\n"
              . "Getting Started:\n"
              . "- Create your first business card\n"
              . "- Add your contact information and social links\n"
              . "- Generate a QR code to share with others\n"
              . "- Scan QR codes to save contacts\n\n"
              . "Visit ShareMyCard: https://sharemycard.app/\n\n"
              . "If you have any questions or need help, feel free to reach out to our support team.\n\n"
              . "Happy networking!";
        
        return [
            'subject' => $subject,
            'html' => $html,
            'text' => $text
        ];
    }
    
    /**
     * Generic notification email
     */
    public static function notification($title, $message) {
        $subject = $title;
        
        $html = self::getEmailWrapper($title, '<p>' . nl2br(htmlspecialchars($message)) . '</p>');
        $text = $message;
        
        return [
            'subject' => $subject,
            'html' => $html,
            'text' => $text
        ];
    }
    
    /**
     * Email wrapper template
     */
    private static function getEmailWrapper($title, $content) {
        return '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($title) . '</title>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif; background-color: #f5f5f5;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f5f5f5; padding: 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
                            <h1 style="color: white; margin: 0; font-size: 28px;">📱 ShareMyCard</h1>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            <h2 style="color: #333; margin: 0 0 20px 0; font-size: 24px;">' . htmlspecialchars($title) . '</h2>
                            <div style="color: #666; line-height: 1.6; font-size: 16px;">
                                ' . $content . '
                            </div>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f8f9fa; padding: 20px 30px; text-align: center; border-radius: 0 0 10px 10px; border-top: 1px solid #e0e0e0;">
                            <p style="color: #999; font-size: 14px; margin: 0;">
                                © ' . date('Y') . ' ShareMyCard. All rights reserved.
                            </p>
                            <p style="color: #999; font-size: 12px; margin: 10px 0 0 0;">
                                This is an automated email. Please do not reply.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';
    }
    
    /**
     * Invitation email template
     */
    public static function invitation($inviterName, $inviteeFirstName, $cardUrl, $comment, $token) {
        $subject = $inviterName . ' invited you to check out their business card';
        
        $respondUrl = 'https://sharemycard.app/invitation-response.php?token=' . urlencode($token);
        $interestedUrl = $respondUrl . '&response=interested';
        $notInterestedUrl = $respondUrl . '&response=not_interested';
        
        $html = self::getEmailWrapper(
            'You\'re Invited to ShareMyCard',
            '<p>Hi ' . htmlspecialchars($inviteeFirstName) . ',</p>
            <p><strong>' . htmlspecialchars($inviterName) . '</strong> has invited you to check out their business card on ShareMyCard!</p>
            
            <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #667eea;">
                <h3 style="margin: 0 0 10px 0; color: #333;">View Business Card</h3>
                <p style="margin: 0 0 15px 0; color: #666;">Click the link below to see their digital business card:</p>
                <p style="text-align: center; margin: 20px 0;">
                    <a href="' . htmlspecialchars($cardUrl) . '" style="display: inline-block; padding: 12px 30px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-decoration: none; border-radius: 6px; font-weight: bold;">
                        View Business Card
                    </a>
                </p>
            </div>
            
            ' . (!empty($comment) ? '<div style="background: #fff3cd; padding: 15px; border-radius: 6px; margin: 20px 0; border-left: 4px solid #ffc107;">
                <h4 style="margin: 0 0 10px 0; color: #856404;">Personal Message:</h4>
                <p style="margin: 0; color: #856404; font-style: italic;">"' . htmlspecialchars($comment) . '"</p>
            </div>' : '') . '
            
            <div style="background: #e7f3ff; padding: 20px; border-radius: 8px; margin: 20px 0; text-align: center;">
                <h3 style="margin: 0 0 15px 0; color: #333;">Interested in creating your own digital business card?</h3>
                <p style="margin: 0 0 20px 0; color: #666;">ShareMyCard makes it easy to create and share professional digital business cards.</p>
                
                <div style="margin: 20px 0;">
                    <a href="' . htmlspecialchars($interestedUrl) . '" style="display: inline-block; padding: 12px 25px; background: #28a745; color: white; text-decoration: none; border-radius: 6px; font-weight: bold; margin: 0 10px 10px 0;">
                        Yes, I\'m Interested
                    </a>
                    <a href="' . htmlspecialchars($notInterestedUrl) . '" style="display: inline-block; padding: 12px 25px; background: #6c757d; color: white; text-decoration: none; border-radius: 6px; font-weight: bold; margin: 0 10px 10px 0;">
                        No, Not Interested
                    </a>
                </div>
            </div>
            
            <p style="color: #666; font-size: 14px; margin-top: 30px;">
                This invitation was sent by ' . htmlspecialchars($inviterName) . '. 
                If you didn\'t expect this invitation, you can safely ignore this email.
            </p>
            
            <!-- Email tracking pixel -->
            <img src="https://sharemycard.app/api/analytics/track-email?token=' . urlencode($token) . '" width="1" height="1" style="display: none;" alt="">'
        );
        
        $text = "Hi {$inviteeFirstName},\n\n"
              . "{$inviterName} has invited you to check out their business card on ShareMyCard!\n\n"
              . "View their business card: {$cardUrl}\n\n"
              . (!empty($comment) ? "Personal Message: \"{$comment}\"\n\n" : "")
              . "Interested in creating your own digital business card?\n"
              . "ShareMyCard makes it easy to create and share professional digital business cards.\n\n"
              . "Respond to this invitation:\n"
              . "Yes, I'm Interested: {$interestedUrl}\n"
              . "No, Not Interested: {$notInterestedUrl}\n\n"
              . "This invitation was sent by {$inviterName}. If you didn't expect this invitation, you can safely ignore this email.";
        
        return [
            'subject' => $subject,
            'html' => $html,
            'text' => $text
        ];
    }
    
    /**
     * Invitation email template for preview (minimal spacing)
     */
    public static function invitationPreview($inviterName, $inviteeFirstName, $cardUrl, $comment, $token) {
        $subject = $inviterName . ' invited you to check out their business card';
        
        $respondUrl = 'https://sharemycard.app/invitation-response.php?token=' . urlencode($token);
        $interestedUrl = $respondUrl . '&response=interested';
        $notInterestedUrl = $respondUrl . '&response=not_interested';
        
        // Start with completely blank template - just the basic structure
        $html = '
        <div style="background: white; padding: 0; margin: 0;">
            <div style="background: #667eea; color: white; padding: 15px; text-align: center; margin-bottom: 10px;">
                <h1 style="margin: 0; font-size: 24px; font-weight: bold;">You\'re Invited to ShareMyCard</h1>
            </div>
            <p style="margin: 5px 0; font-size: 16px;">Hi ' . htmlspecialchars($inviteeFirstName) . ',</p>
            <p style="margin: 5px 0; font-size: 16px;">' . htmlspecialchars($inviterName) . ' has invited you to check out their business card on ShareMyCard!</p>
            
            <div>
                <h3>View Business Card</h3>
                <p>Click the link below to see their digital business card:</p>
                <a href="' . htmlspecialchars($cardUrl) . '">View Business Card</a>
            </div>
            
            ' . (!empty($comment) ? '<div>
                <h4>Personal Message:</h4>
                <p>"' . htmlspecialchars($comment) . '"</p>
            </div>' : '') . '
            
            <div>
                <h3>Interested in creating your own digital business card?</h3>
                <p>ShareMyCard makes it easy to create and share professional digital business cards.</p>
                
                <a href="' . htmlspecialchars($interestedUrl) . '">Yes, I\'m Interested</a>
                <a href="' . htmlspecialchars($notInterestedUrl) . '">No, Not Interested</a>
            </div>
            
            <p>This invitation was sent by ' . htmlspecialchars($inviterName) . '. If you didn\'t expect this invitation, you can safely ignore this email.</p>
        </div>';
        
        $text = "Hi {$inviteeFirstName},\n\n"
              . "{$inviterName} has invited you to check out their business card on ShareMyCard!\n\n"
              . "View their business card: {$cardUrl}\n\n"
              . (!empty($comment) ? "Personal Message: \"{$comment}\"\n\n" : "")
              . "Interested in creating your own digital business card?\n"
              . "ShareMyCard makes it easy to create and share professional digital business cards.\n\n"
              . "Respond to this invitation:\n"
              . "Yes, I'm Interested: {$interestedUrl}\n"
              . "No, Not Interested: {$notInterestedUrl}\n\n"
              . "This invitation was sent by {$inviterName}. If you didn't expect this invitation, you can safely ignore this email.";
        
        return [
            'subject' => $subject,
            'html' => $html,
            'text' => $text
        ];
    }
}

