<?php
/**
 * Input Validator Class
 * Handles validation of input data for API endpoints
 */

class InputValidator {
    private $errors = [];
    
    /**
     * Check if validation passed
     */
    public function isValid() {
        return empty($this->errors);
    }
    
    /**
     * Get validation errors
     */
    public function getErrors() {
        return $this->errors;
    }
    
    /**
     * Add error message
     */
    private function addError($field, $message) {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        $this->errors[$field][] = $message;
    }
    
    /**
     * Validate required field
     */
    public function required($field, $value) {
        if (empty($value) || trim($value) === '') {
            $this->addError($field, "$field is required");
        }
        return $this;
    }
    
    /**
     * Validate email field
     */
    public function email($field, $value) {
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->addError($field, "$field must be a valid email address");
        }
        return $this;
    }
    
    /**
     * Validate phone field
     */
    public function phone($field, $value) {
        if (!empty($value)) {
            // Basic phone validation - allow various formats
            $phone = preg_replace('/[^0-9+()-.\s]/', '', $value);
            if (strlen($phone) < 10) {
                $this->addError($field, "$field must be a valid phone number");
            }
        }
        return $this;
    }
    
    /**
     * Validate URL field
     */
    public function url($field, $value) {
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
            $this->addError($field, "$field must be a valid URL");
        }
        return $this;
    }
    
    /**
     * Validate string length
     */
    public function maxLength($field, $value, $maxLength) {
        if (!empty($value) && strlen($value) > $maxLength) {
            $this->addError($field, "$field must be no more than $maxLength characters");
        }
        return $this;
    }
    
    /**
     * Validate minimum length
     */
    public function minLength($field, $value, $minLength) {
        if (!empty($value) && strlen($value) < $minLength) {
            $this->addError($field, "$field must be at least $minLength characters");
        }
        return $this;
    }
    
    /**
     * Validate numeric field
     */
    public function numeric($field, $value) {
        if (!empty($value) && !is_numeric($value)) {
            $this->addError($field, "$field must be a number");
        }
        return $this;
    }
    
    /**
     * Validate integer field
     */
    public function integer($field, $value) {
        if (!empty($value) && !is_int($value) && !ctype_digit($value)) {
            $this->addError($field, "$field must be an integer");
        }
        return $this;
    }
    
    /**
     * Validate date field
     */
    public function date($field, $value) {
        if (!empty($value)) {
            $date = DateTime::createFromFormat('Y-m-d', $value);
            if (!$date || $date->format('Y-m-d') !== $value) {
                $this->addError($field, "$field must be a valid date (YYYY-MM-DD)");
            }
        }
        return $this;
    }
    
    /**
     * Validate custom regex pattern
     */
    public function pattern($field, $value, $pattern, $message = null) {
        if (!empty($value) && !preg_match($pattern, $value)) {
            $errorMessage = $message ?: "$field format is invalid";
            $this->addError($field, $errorMessage);
        }
        return $this;
    }
}
?>
