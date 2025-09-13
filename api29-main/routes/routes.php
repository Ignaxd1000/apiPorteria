<?php

header('Content-Type: application/json');

$arrayRutas = array_values(array_filter(explode("/", $_SERVER['REQUEST_URI'])));

// Ajustar según el prefijo de la API (ej: api29-main)
$baseIndex = array_search('api29-main', $arrayRutas);
$rutas = array_slice($arrayRutas, $baseIndex + 1);

// Manejo de paginación para cursos vía GET (?pagina=)
if (isset($_GET["pagina"]) && is_numeric($_GET["pagina"])) {
    $cursos = new ControladorCursos();
    $cursos->index($_GET["pagina"]);
    return;
}

if (empty($rutas)) {
    echo json_encode(["detalle" => "no encontrado"]);
    return;
}

// Rutas principales
switch ($rutas[0]) {
    case 'cursos':
        $cursos = new ControladorCursos();
        if (!isset($rutas[1])) {
            if ($_SERVER['REQUEST_METHOD'] === 'GET') $cursos->index(null);
            elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $datos = [
                    "titulo" => $_POST["titulo"] ?? null,
                    "descripcion" => $_POST["descripcion"] ?? null,
                    "instructor" => $_POST["instructor"] ?? null,
                    "imagen" => $_POST["imagen"] ?? null,
                    "precio" => $_POST["precio"] ?? null
                ];
                $cursos->create($datos);
            } else echo json_encode(["status" => 405, "mensaje" => "Método no permitido"]);
        } elseif (is_numeric($rutas[1])) {
            $idCurso = $rutas[1];
            if ($_SERVER['REQUEST_METHOD'] === 'GET') $cursos->show($idCurso);
            elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
                $datos = [];
                parse_str(file_get_contents('php://input'), $datos);
                $cursos->update($idCurso, $datos);
            } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') $cursos->delete($idCurso);
            else echo json_encode(["status" => 405, "mensaje" => "Método no permitido"]);
        }
        break;

    case 'clientes':
        $clientes = new ControladorClientes();
        if ($_SERVER['REQUEST_METHOD'] === 'GET') $clientes->index();
        elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $datos = json_decode(file_get_contents("php://input"), true);
            $clientes->create($datos);
        } else echo json_encode(["status" => 405, "mensaje" => "Método no permitido"]);
        break;

    case 'alumnos':
        $alumnos = new ControladorAlumnos();

        // POST: buscar solo por token
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $datos = json_decode(file_get_contents("php://input"), true);
            if (isset($datos["token"])) {
                $alumnos->buscarPorToken($datos["token"]);
            } else {
                echo json_encode(["status" => 400, "mensaje" => "Token requerido"]);
            }

        // GET: obtener foto /alumnos/foto/{legajo}?token=...
        } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
            if (isset($rutas[1]) && $rutas[1] === 'foto' && isset($rutas[2])) {
                $legajo = explode('?', $rutas[2])[0];
                $token = $_GET['token'] ?? null;
                if (!$token) {
                    echo json_encode(["status" => 403, "mensaje" => "Token requerido"]);
                    exit;
                }
                $alumnos->obtenerFoto($legajo, $token);
            } else {
                echo json_encode(["status" => 404, "mensaje" => "Ruta no encontrada"]);
            }
        } else {
            echo json_encode(["status" => 405, "mensaje" => "Método no permitido"]);
        }
        break;

    default:
        echo json_encode(["status" => 404, "mensaje" => "Ruta no encontrada"]);
        break;
}




