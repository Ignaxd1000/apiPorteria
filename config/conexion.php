<?php
class Conexion {

    public static function conectar() {
        $configPath = __DIR__ . '/../config/config.json';
        if (!file_exists($configPath)) {
            die(json_encode(["status" => 500, "mensaje" => "Archivo de configuración no encontrado"]));
        }

        $config = json_decode(file_get_contents($configPath), true);
        if (!$config) {
            die(json_encode(["status" => 500, "mensaje" => "Error leyendo archivo de configuración"]));
        }

        try {
            $pdo = new PDO(
                "mysql:host={$config['host']};dbname={$config['base']};charset=utf8mb4",
                $config['usuario'],
                $config['password']
            );
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $pdo;
        } catch (PDOException $e) {
            die(json_encode(["status" => 500, "mensaje" => "Error de conexión: " . $e->getMessage()]));
        }
    }
}
