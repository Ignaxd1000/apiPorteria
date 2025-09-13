<?php

/**
 * Helper class for standardized JSON responses and error handling
 */
class ResponseHelper {
    
    /**
     * Send a standardized success response
     * @param mixed $data Response data
     * @param string $message Success message
     * @param int $statusCode HTTP status code (default: 200)
     */
    public static function success($data = null, $message = null, $statusCode = 200) {
        http_response_code($statusCode);
        
        $response = [
            "status" => $statusCode
        ];
        
        if ($message !== null) {
            $response["mensaje"] = $message;
        }
        
        if ($data !== null) {
            // Handle different data structures for backward compatibility
            if (is_array($data) && isset($data['total'])) {
                $response["total"] = $data['total'];
                $response["detalle"] = $data['detalle'] ?? $data;
            } elseif (is_array($data) && isset($data['total_registros'])) {
                $response["total_registros"] = $data['total_registros'];
                $response["detalle"] = $data['detalle'] ?? $data;
            } else {
                $response["detalle"] = $data;
            }
        }
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Send a standardized error response
     * @param string $message Error message
     * @param int $statusCode HTTP status code (default: 400)
     * @param mixed $details Additional error details (optional)
     */
    public static function error($message, $statusCode = 400, $details = null) {
        http_response_code($statusCode);
        
        $response = [
            "status" => $statusCode,
            "mensaje" => $message
        ];
        
        if ($details !== null) {
            $response["detalle"] = $details;
        }
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Send a method not allowed response (405)
     * @param string $message Custom message (optional)
     */
    public static function methodNotAllowed($message = "Método no permitido") {
        self::error($message, 405);
    }
    
    /**
     * Send a not found response (404)
     * @param string $message Custom message (optional)
     */
    public static function notFound($message = "Recurso no encontrado") {
        self::error($message, 404);
    }
    
    /**
     * Send an unauthorized response (401)
     * @param string $message Custom message (optional)
     */
    public static function unauthorized($message = "No autorizado") {
        self::error($message, 401);
    }
    
    /**
     * Send a forbidden response (403)
     * @param string $message Custom message (optional)
     */
    public static function forbidden($message = "Acceso prohibido") {
        self::error($message, 403);
    }
    
    /**
     * Send a bad request response (400)
     * @param string $message Custom message (optional)
     */
    public static function badRequest($message = "Solicitud incorrecta") {
        self::error($message, 400);
    }
    
    /**
     * Send an internal server error response (500)
     * @param string $message Custom message (optional)
     */
    public static function serverError($message = "Error interno del servidor") {
        self::error($message, 500);
    }
}

?>