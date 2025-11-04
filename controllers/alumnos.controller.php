<?php

require_once "models/alumnos.modelo.php";

class ControladorAlumnos {

    // Consultar alumno usando solo token
    public function buscarPorToken($token) {
        try {
            // Validar token
            $token = ValidationHelper::validateToken($token);
            
            // Buscar alumno por token en una sola consulta
            $alumno = ModeloAlumnos::buscarPorToken($token);

            if (!$alumno) {
                ResponseHelper::notFound("Alumno no encontrado o token inválido");
            }

            // Determinar si pertenece a la institución basado en el campo 'activo'
            $pertenece = !empty($alumno["activo"]) && $alumno["activo"] == 1;

            ResponseHelper::success([
                "alumno" => [
                    "legajo" => $alumno["legajo"],
                    "nombres" => $alumno["nombres"] ?? "Desconocido",
                    "dni" => $alumno["dni"] ?? "Desconocido",
                    "pertenece" => $pertenece,
                    "foto" => "alumnos/foto/" . $alumno["legajo"] . "?token=" . $token
                ]
            ]);
            
        } catch (Exception $e) {
            ResponseHelper::badRequest($e->getMessage());
        }
    }

    // Obtener foto de alumno usando legajo y token
    public function obtenerFoto($legajo, $token) {
        try {
            // Validar inputs
            $legajo = ValidationHelper::validateLegajo($legajo);
            $token = ValidationHelper::validateToken($token);
            
            // Buscar alumno por token para validar y obtener datos
            $alumno = ModeloAlumnos::buscarPorToken($token);
            
            if (!$alumno) {
                ResponseHelper::forbidden("Token inválido");
            }

            // Corregi esto de acà, Copilot estaba comparando mal y por eso no se devolvia la foto
            if ((int)$alumno['legajo'] !== (int)$legajo) {
                ResponseHelper::forbidden("Legajo no coincide con el token");
            }

            if (empty($alumno['foto'])) {
                ResponseHelper::notFound("Imagen no encontrada");
            }

            $ruta = __DIR__ . "/../fotos/" . $alumno['foto'];
            if (!file_exists($ruta)) {
                ResponseHelper::notFound("Imagen no encontrada");
            }

            // Registrar acceso (logging básico)
            $this->registrarAccesoFoto($legajo, $token);

            $ext = strtolower(pathinfo($ruta, PATHINFO_EXTENSION));
            $mime = match($ext) {
                "png" => "image/png",
                default => "image/jpeg",
            };

            header("Content-Type: $mime");
            header("Content-Length: " . filesize($ruta));
            readfile($ruta);
            exit;
            
        } catch (Exception $e) {
            ResponseHelper::badRequest($e->getMessage());
        }
    }

    // Método para registrar accesos a fotos (logging básico)
    private function registrarAccesoFoto($legajo, $token) {
        try {
            $logFile = __DIR__ . "/../logs/acceso_fotos.log";
            $logDir = dirname($logFile);
            
            // Crear directorio de logs si no existe
            if (!file_exists($logDir)) {
                mkdir($logDir, 0755, true);
            }
            
            $fecha = date('Y-m-d H:i:s');
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
            
            $logEntry = "[{$fecha}] Acceso a foto - Legajo: {$legajo}, IP: {$ip}, UserAgent: {$userAgent}" . PHP_EOL;
            
            file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
        } catch (Exception $e) {
            // Si falla el logging, no interrumpir el flujo principal
            error_log("Error en logging de acceso a foto: " . $e->getMessage());
        }
    }

    // Obtener token de alumno por DNI (sin autenticación requerida)
    public function getTokenByDNI($datos) {
        try {
            // Validar que el DNI esté presente
            if (!isset($datos["dni"])) {
                ResponseHelper::badRequest("DNI requerido");
            }
            
            // Validar formato del DNI
            $dni = ValidationHelper::validateDNI($datos["dni"]);
            
            // Buscar token por DNI
            $token = ModeloAlumnos::getTokenByDni($dni);
            
            if (!$token) {
                ResponseHelper::notFound("Alumno no encontrado");
            }
            
            ResponseHelper::success([
                "token" => $token
            ]);
            
        } catch (Exception $e) {
            ResponseHelper::badRequest($e->getMessage());
        }
    }
}

?>
