<?php

require_once "models/alumnos.modelo.php";

class ControladorAlumnos {

    // Consultar alumno usando solo token
    public function buscarPorToken($token) {
    if (!$token) {
        echo json_encode(["status" => 400, "mensaje" => "Token vacío"]);
        return;
    }

    if (!ModeloAlumnos::validarToken($token)) {
        echo json_encode(["status" => 403, "mensaje" => "Token inválido"]);
        return;
    }

    $alumno = ModeloAlumnos::buscarPorToken($token);

    if (!$alumno) {
        echo json_encode(["status" => 404, "mensaje" => "Alumno no encontrado"]);
        return;
    }

    echo json_encode([
        "status" => 200,
        "alumno" => [
            "legajo" => $alumno["legajo"],
            "nombres" => $alumno["nombres"] ?? "Desconocido",
            "dni" => $alumno["dni"] ?? "Desconocido",
            "foto" => "alumnos/foto/" . $alumno["legajo"] . "?token=" . $token
        ]
    ]);
}


    // Obtener foto de alumno usando legajo y token
    public function obtenerFoto($legajo, $token) {
        if (!ModeloAlumnos::validarToken($token)) {
            header("HTTP/1.1 403 Forbidden");
            echo json_encode(["status" => 403, "mensaje" => "Token inválido"]);
            exit;
        }

        $alumno = ModeloAlumnos::buscarPorLegajo($legajo);

        if (!$alumno || empty($alumno['foto'])) {
            header("HTTP/1.1 404 Not Found");
            echo json_encode(["status" => 404, "mensaje" => "Imagen no encontrada"]);
            exit;
        }

        $ruta = __DIR__ . "/../fotos/" . $alumno['foto'];
        if (!file_exists($ruta)) {
            header("HTTP/1.1 404 Not Found");
            echo json_encode(["status" => 404, "mensaje" => "Imagen no encontrada"]);
            exit;
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
    }
}


