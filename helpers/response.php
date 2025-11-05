<?php

/**
 * Clase para manejar respuestas estandarizadas de la API
 */
class ResponseHelper {
    
    /**
     * Enviar una respuesta exitosa estandarizada
     * @param mixed $data  Datos a incluir en la respuesta
     * @param string $message Mensaje opcional
     * @param int $statusCode Codigo HTTP (default: 200)
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
     * Enviar una respuesta de error estandarizada
     * @param string $message Mensaje de error
     * @param int $statusCode Codigo HTTP (default: 400)
     * @param mixed $details Detalles adicionales (opcional)
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
     * Enviar una respuesta de método no permitido (405)
     * @param string $message mensaje custom (opcional)
     */
    public static function methodNotAllowed($message = "Método no permitido") {
        self::error($message, 405);
    }
    
    /**
     * Enviar una respuesta de recurso no encontrado (404)
     * @param string $message mensaje custom (opcional)
     */
    public static function notFound($message = "Recurso no encontrado") {
        self::error($message, 404);
    }
    
    /**
     * Enviar una respuesta de no autorizado (401)
     * @param string $message mensaje custom (opcional)
     */
    public static function unauthorized($message = "No autorizado") {
        self::error($message, 401);
    }
    
    /**
     * Enviar una respuesta de acceso prohibido (403)
     * @param string $message mensaje custom (opcional)
     */
    public static function forbidden($message = "Acceso prohibido") {
        self::error($message, 403);
    }
    
    /**
     * Enviar una respuesta de solicitud incorrecta (400)
     * @param string $message mensaje custom (opcional)
     */
    public static function badRequest($message = "Solicitud incorrecta") {
        self::error($message, 400);
    }
    
    /**
     * Enviar una respuesta de error interno del servidor (500)
     * @param string $message mensaje custom (opcional)
     */
    public static function serverError($message = "Error interno del servidor") {
        self::error($message, 500);
    }
}

?>