<?php

require_once "models/alumnos.modelo.php";

class ControladorAlumnos {

    // Consultar alumno usando solo token
    public function buscarPorToken($token) {
        try {
            // Validar token
            $token = ValidationHelper::validateToken($token);
            
            if (!ModeloAlumnos::validarToken($token)) {
                ResponseHelper::forbidden("Token inválido");
            }

            $alumno = ModeloAlumnos::buscarPorToken($token);

            if (!$alumno) {
                ResponseHelper::notFound("Alumno no encontrado");
            }

            ResponseHelper::success([
                "alumno" => [
                    "legajo" => $alumno["legajo"],
                    "nombres" => $alumno["nombres"] ?? "Desconocido",
                    "dni" => $alumno["dni"] ?? "Desconocido",
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
            
            if (!ModeloAlumnos::validarToken($token)) {
                ResponseHelper::forbidden("Token inválido");
            }

            $alumno = ModeloAlumnos::buscarPorLegajo($legajo);

            if (!$alumno || empty($alumno['foto'])) {
                ResponseHelper::notFound("Imagen no encontrada");
            }

            $ruta = __DIR__ . "/../fotos/" . $alumno['foto'];
            if (!file_exists($ruta)) {
                ResponseHelper::notFound("Imagen no encontrada");
            }

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
}

?>
