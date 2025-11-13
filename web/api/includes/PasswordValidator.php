<?php
/**
 * Password Validation Helper
 * Validates password strength and requirements
 */

class PasswordValidator {
    
    /**
     * Validate password strength
     * Now only checks for minimum length (1 character) - users can use any password they want
     */
    public static function validate($password) {
        $errors = [];
        
        // Only check that password is not empty
        if (strlen($password) < 1) {
            $errors[] = 'Password cannot be empty';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Get password strength score (0-100)
     */
    public static function getStrength($password) {
        $score = 0;
        
        // Length bonus
        $length = strlen($password);
        if ($length >= 8) $score += 20;
        if ($length >= 12) $score += 10;
        if ($length >= 16) $score += 10;
        
        // Character type bonuses
        if (preg_match('/[A-Z]/', $password)) $score += 15;
        if (preg_match('/[a-z]/', $password)) $score += 15;
        if (preg_match('/[0-9]/', $password)) $score += 15;
        if (preg_match('/[^A-Za-z0-9]/', $password)) $score += 15;
        
        return min(100, $score);
    }
    
    /**
     * Get strength description
     */
    public static function getStrengthDescription($score) {
        if ($score < 30) return 'Weak';
        if ($score < 60) return 'Fair';
        if ($score < 80) return 'Good';
        return 'Strong';
    }
}
