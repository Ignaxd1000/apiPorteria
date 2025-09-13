<?php

/**
 * Helper class for input validation and sanitization
 */
class ValidationHelper {
    
    /**
     * Validate and sanitize a name field (only letters and accents)
     * @param string $name The name to validate
     * @param string $fieldName Field name for error messages
     * @return string Sanitized name
     * @throws Exception if validation fails
     */
    public static function validateName($name, $fieldName = "nombre") {
        if (empty($name)) {
            throw new Exception("El campo {$fieldName} es requerido");
        }
        
        $name = trim($name);
        
        if (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/', $name)) {
            throw new Exception("Error en el campo {$fieldName}, ingrese solo letras");
        }
        
        if (strlen($name) < 2 || strlen($name) > 50) {
            throw new Exception("El campo {$fieldName} debe tener entre 2 y 50 caracteres");
        }
        
        return $name;
    }
    
    /**
     * Validate and sanitize email
     * @param string $email The email to validate
     * @return string Sanitized email
     * @throws Exception if validation fails
     */
    public static function validateEmail($email) {
        if (empty($email)) {
            throw new Exception("El campo email es requerido");
        }
        
        $email = trim(strtolower($email));
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Error en el campo email, ingrese un email válido");
        }
        
        if (strlen($email) > 100) {
            throw new Exception("El email es demasiado largo");
        }
        
        return $email;
    }
    
    /**
     * Validate token format and length
     * @param string $token The token to validate
     * @return string Sanitized token
     * @throws Exception if validation fails
     */
    public static function validateToken($token) {
        if (empty($token)) {
            throw new Exception("Token requerido");
        }
        
        $token = trim($token);
        
        // Token should be alphanumeric and between 10-200 characters
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $token)) {
            throw new Exception("Formato de token inválido");
        }
        
        if (strlen($token) < 10 || strlen($token) > 200) {
            throw new Exception("El token debe tener entre 10 y 200 caracteres");
        }
        
        return $token;
    }
    
    /**
     * Validate DNI (Documento Nacional de Identidad)
     * @param string $dni The DNI to validate
     * @return string Sanitized DNI
     * @throws Exception if validation fails
     */
    public static function validateDNI($dni) {
        if (empty($dni)) {
            throw new Exception("DNI requerido");
        }
        
        $dni = trim($dni);
        
        // DNI should be 7-8 digits
        if (!preg_match('/^[0-9]{7,8}$/', $dni)) {
            throw new Exception("DNI debe contener entre 7 y 8 dígitos");
        }
        
        return $dni;
    }
    
    /**
     * Validate legajo (student ID)
     * @param string $legajo The legajo to validate
     * @return string Sanitized legajo
     * @throws Exception if validation fails
     */
    public static function validateLegajo($legajo) {
        if (empty($legajo)) {
            throw new Exception("Legajo requerido");
        }
        
        $legajo = trim($legajo);
        
        // Legajo should be alphanumeric and between 1-20 characters
        if (!preg_match('/^[a-zA-Z0-9]+$/', $legajo)) {
            throw new Exception("Legajo debe contener solo letras y números");
        }
        
        if (strlen($legajo) < 1 || strlen($legajo) > 20) {
            throw new Exception("El legajo debe tener entre 1 y 20 caracteres");
        }
        
        return $legajo;
    }
    
    /**
     * Validate and sanitize general text fields (curso fields, etc.)
     * @param string $text The text to validate
     * @param string $fieldName Field name for error messages
     * @param int $minLength Minimum length
     * @param int $maxLength Maximum length
     * @return string Sanitized text
     * @throws Exception if validation fails
     */
    public static function validateText($text, $fieldName = "campo", $minLength = 1, $maxLength = 255) {
        if (empty($text)) {
            throw new Exception("El campo {$fieldName} es requerido");
        }
        
        $text = trim($text);
        
        // Allow letters, numbers, spaces and common punctuation
        if (!preg_match('/^[()=&$;_*"<>?¿!¡:,.\s0-9a-zA-ZñÑáéíóúÁÉÍÓÚ]+$/', $text)) {
            throw new Exception("Error en el campo {$fieldName}, contiene caracteres no permitidos");
        }
        
        if (strlen($text) < $minLength || strlen($text) > $maxLength) {
            throw new Exception("El campo {$fieldName} debe tener entre {$minLength} y {$maxLength} caracteres");
        }
        
        return $text;
    }
    
    /**
     * Validate price (numeric, positive)
     * @param mixed $price The price to validate
     * @return float Sanitized price
     * @throws Exception if validation fails
     */
    public static function validatePrice($price) {
        if (empty($price) && $price !== "0") {
            throw new Exception("El precio es requerido");
        }
        
        if (!is_numeric($price)) {
            throw new Exception("El precio debe ser numérico");
        }
        
        $price = floatval($price);
        
        if ($price < 0) {
            throw new Exception("El precio no puede ser negativo");
        }
        
        if ($price > 999999.99) {
            throw new Exception("El precio es demasiado alto");
        }
        
        return $price;
    }
    
    /**
     * Sanitize and validate JSON input data
     * @param string $jsonInput Raw JSON input
     * @return array Parsed and validated data
     * @throws Exception if JSON is invalid
     */
    public static function validateJsonInput($jsonInput) {
        if (empty($jsonInput)) {
            throw new Exception("Datos requeridos");
        }
        
        $data = json_decode($jsonInput, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Formato JSON inválido");
        }
        
        if (!is_array($data)) {
            throw new Exception("Los datos deben ser un objeto JSON");
        }
        
        return $data;
    }
}

?>