<?php

class ValidationHelper {
    
    /**
     * Con este metodo se valida y sanitizan las entradas del usuario
     * @param string $name el nombre a validar
     * @param string $fieldName nombre de campo para mensajes de error
     * @return string nombre sanitizado
     * @throws Exception por si algo falla
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
     * Validar y sanitizar email
     * @param string $email El email a validar
     * @return string Email sanitizado
     * @throws Exception Si algo falla
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
     * Validar formato de token
     * @param string $token El token a validar
     * @return string Token sanitizado
     * @throws Exception si algo falla
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
     * Validar DNI (Documento Nacional de Identidad)
     * @param string $dni El dni a validar
     * @return string DNI sanitizado
     * @throws Exception si algo falla
     */
    public static function validateDNI($dni) {
        if (empty($dni)) {
            throw new Exception("DNI requerido");
        }
        
        $dni = trim($dni);
        
        // El dni tiene que tener 7 u 8 dígitos
        if (!preg_match('/^[0-9]{7,8}$/', $dni)) {
            throw new Exception("DNI debe contener entre 7 y 8 dígitos");
        }
        
        return $dni;
    }
    
    /**
     * Valido legajo (código de estudiante o empleado)
     * @param string $legajo Legajo a validar
     * @return string legajo sanitizado
     * @throws Exception si algo falla
     */
    public static function validateLegajo($legajo) {
        if (empty($legajo)) {
            throw new Exception("Legajo requerido");
        }
        
        $legajo = trim($legajo);
        
        // El legajo debe ser alfanumérico
        if (!preg_match('/^[a-zA-Z0-9]+$/', $legajo)) {
            throw new Exception("Legajo debe contener solo letras y números");
        }
        
        if (strlen($legajo) < 1 || strlen($legajo) > 20) {
            throw new Exception("El legajo debe tener entre 1 y 20 caracteres");
        }
        
        return $legajo;
    }
    
    /**
     * Validar texto general (permitir letras, números, espacios y puntuación común)
     * @param string $text El texto a validar
     * @param string $fieldName Campo para mensajes de error
     * @param int $minLength Tamaño mínimo
     * @param int $maxLength Tamaño máximo
     * @return string Texto sanitizado
     * @throws Exception si algo falla
     */
    public static function validateText($text, $fieldName = "campo", $minLength = 1, $maxLength = 255) {
        if (empty($text)) {
            throw new Exception("El campo {$fieldName} es requerido");
        }
        
        $text = trim($text);
        
        // Permitir letras, números, espacios y puntuación común
        if (!preg_match('/^[()=&$;_*"<>?¿!¡:,.\s0-9a-zA-ZñÑáéíóúÁÉÍÓÚ]+$/', $text)) {
            throw new Exception("Error en el campo {$fieldName}, contiene caracteres no permitidos");
        }
        
        if (strlen($text) < $minLength || strlen($text) > $maxLength) {
            throw new Exception("El campo {$fieldName} debe tener entre {$minLength} y {$maxLength} caracteres");
        }
        
        return $text;
    }
    
    /**
     * Validar y sanitizar precio
     * @param mixed $price El precio a validar
     * @return float Precio sanitizado
     * @throws Exception si algo falla
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
     * Validar y parsear entrada JSON
     * @param string $jsonInput Entrada JSON
     * @return array Datos parseados
     * @throws Exception si algo falla
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
    
    /**
     * Autenticar cliente usando credenciales hash
     * @param string $id_cliente Client ID (recibida como hash)
     * @param string $llave_secreta Secret Key (recibida como hash)
     * @return bool true si autenticación exitosa
     * @throws Exception si algo falla
     */
    public static function authenticateClient($id_cliente, $llave_secreta) {
        if (empty($id_cliente) || empty($llave_secreta)) {
            throw new Exception("Credenciales requeridas");
        }
        
        require_once __DIR__ . '/../models/clientes.modelo.php';
        
        $cliente = ModeloClientes::getClientByCredentials($id_cliente, $llave_secreta);
        
        if (!$cliente) {
            throw new Exception("Credenciales inválidas");
        }
        
        return true;
    }
}

?>
