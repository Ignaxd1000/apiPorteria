<?php

require_once __DIR__ . '/../config/conexion.php';

class ModeloAlumnos {

    public static function buscarPorDni($dni) {
        $db = Conexion::conectar();
        $stmt = $db->prepare("SELECT legajo, nombres, dni, foto FROM alu_alumnos WHERE dni = ?");
        $stmt->execute([$dni]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function buscarPorEmail($email) {
        $db = Conexion::conectar();
        $stmt = $db->prepare("SELECT legajo, nombres, dni, foto FROM alu_alumnos WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function buscarPorLegajo($legajo) {
        $db = Conexion::conectar();
        $stmt = $db->prepare("SELECT legajo, nombres, dni, foto FROM alu_alumnos WHERE legajo = ?");
        $stmt->execute([$legajo]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function validarToken($token) {
        $db = Conexion::conectar();
        $stmt = $db->prepare("SELECT 1 FROM alu_alumnos WHERE qr_code = ?");
        $stmt->execute([$token]);
        return $stmt->fetch() ? true : false;
    }

    public static function buscarPorToken($token) {
    $db = Conexion::conectar();
    $stmt = $db->prepare("SELECT legajo, nombres, dni, foto FROM alu_alumnos WHERE qr_code = ?");
    $stmt->execute([$token]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}


}


