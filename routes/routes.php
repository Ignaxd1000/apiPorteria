<?php

header('Content-Type: application/json');

$arrayRutas = array_values(array_filter(explode("/", $_SERVER['REQUEST_URI'])));

// Ajustar según el prefijo de la API (ej: api29-main)
$baseIndex = array_search('api29-main', $arrayRutas);
$rutas = array_slice($arrayRutas, $baseIndex + 1);

// Rutas que NO requieren autenticación
$publicRoutes = [
    ['path' => ['alumnos', 'token'], 'method' => 'POST'],
    ['path' => ['clientes'], 'method' => 'POST'] // Permitir crear clientes sin autenticación
];

// Verificar si la ruta actual es pública
$isPublicRoute = false;
foreach ($publicRoutes as $publicRoute) {
    if (count($rutas) >= count($publicRoute['path'])) {
        $match = true;
        for ($i = 0; $i < count($publicRoute['path']); $i++) {
            if ($rutas[$i] !== $publicRoute['path'][$i]) {
                $match = false;
                break;
            }
        }
        if ($match && $_SERVER['REQUEST_METHOD'] === $publicRoute['method']) {
            $isPublicRoute = true;
            break;
        }
    }
}

// Autenticación: validar credenciales si no es una ruta pública
if (!$isPublicRoute && !empty($rutas)) {
    try {
        // Obtener credenciales desde los headers
        $id_cliente = $_SERVER['HTTP_X_CLIENT_ID'] ?? null;
        $llave_secreta = $_SERVER['HTTP_X_SECRET_KEY'] ?? null;
        
        // Si no están en headers, intentar desde el cuerpo JSON (para POST/PUT)
        if ((!$id_cliente || !$llave_secreta) && in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT'])) {
            $inputData = json_decode(file_get_contents("php://input"), true);
            if (is_array($inputData)) {
                $id_cliente = $inputData['id_cliente'] ?? $id_cliente;
                $llave_secreta = $inputData['llave_secreta'] ?? $llave_secreta;
            }
        }
        
        if (!$id_cliente || !$llave_secreta) {
            ResponseHelper::unauthorized("Credenciales requeridas. Envíe id_cliente y llave_secreta en headers (X-Client-Id, X-Secret-Key) o en el cuerpo de la petición");
        }
        
        // Validar credenciales
        ValidationHelper::authenticateClient($id_cliente, $llave_secreta);
        
    } catch (Exception $e) {
        ResponseHelper::unauthorized($e->getMessage());
    }
}

// Manejo de paginación para cursos vía GET (?pagina=)
if (isset($_GET["pagina"]) && !empty($rutas) && $rutas[0] === 'cursos') {
    if (is_numeric($_GET["pagina"])) {
        $cursos = new ControladorCursos();
        $cursos->index($_GET["pagina"]);
    } else {
        ResponseHelper::badRequest("Número de página inválido");
    }
    return;
}

if (empty($rutas)) {
    ResponseHelper::notFound("Ruta no encontrada");
}

// Validar que la primera ruta sea válida
$validRoutes = ['cursos', 'clientes', 'alumnos'];
if (!in_array($rutas[0], $validRoutes)) {
    ResponseHelper::notFound("Ruta no encontrada");
}

// Rutas principales
switch ($rutas[0]) {
    case 'cursos':
        $cursos = new ControladorCursos();
        if (!isset($rutas[1])) {
            // /cursos
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $cursos->index(null);
            } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
                try {
                    $datos = ValidationHelper::validateJsonInput(file_get_contents("php://input"));
                    $cursos->create($datos);
                } catch (Exception $e) {
                    ResponseHelper::badRequest($e->getMessage());
                }
            } else {
                ResponseHelper::methodNotAllowed();
            }
        } elseif (is_numeric($rutas[1])) {
            // /cursos/{id}
            $idCurso = $rutas[1];
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $cursos->show($idCurso);
            } elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
                try {
                    $datos = [];
                    parse_str(file_get_contents('php://input'), $datos);
                    if (empty($datos)) {
                        // Try JSON format as fallback
                        $datos = ValidationHelper::validateJsonInput(file_get_contents("php://input"));
                    }
                    $cursos->update($idCurso, $datos);
                } catch (Exception $e) {
                    ResponseHelper::badRequest($e->getMessage());
                }
            } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
                $cursos->delete($idCurso);
            } else {
                ResponseHelper::methodNotAllowed();
            }
        } else {
            ResponseHelper::notFound("Ruta no encontrada");
        }
        break;

    case 'clientes':
        $clientes = new ControladorClientes();
        if (!isset($rutas[1])) {
            // /clientes
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $clientes->index();
            } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
                try {
                    $datos = ValidationHelper::validateJsonInput(file_get_contents("php://input"));
                    $clientes->create($datos);
                } catch (Exception $e) {
                    ResponseHelper::badRequest($e->getMessage());
                }
            } else {
                ResponseHelper::methodNotAllowed();
            }
        } else {
            ResponseHelper::notFound("Ruta no encontrada");
        }
        break;

    case 'alumnos':
        $alumnos = new ControladorAlumnos();

        if (!isset($rutas[1])) {
            // /alumnos - POST: buscar solo por token
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                try {
                    $datos = ValidationHelper::validateJsonInput(file_get_contents("php://input"));
                    if (!isset($datos["token"])) {
                        ResponseHelper::badRequest("Token requerido");
                    }
                    $alumnos->buscarPorToken($datos["token"]);
                } catch (Exception $e) {
                    ResponseHelper::badRequest($e->getMessage());
                }
            } else {
                ResponseHelper::methodNotAllowed();
            }
        } elseif (isset($rutas[1]) && $rutas[1] === 'token') {
            // /alumnos/token - POST: obtener token por DNI (sin autenticación)
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                try {
                    $datos = ValidationHelper::validateJsonInput(file_get_contents("php://input"));
                    $alumnos->getTokenByDNI($datos);
                } catch (Exception $e) {
                    ResponseHelper::badRequest($e->getMessage());
                }
            } else {
                ResponseHelper::methodNotAllowed();
            }
        } elseif (isset($rutas[1]) && $rutas[1] === 'foto' && isset($rutas[2])) {
            // /alumnos/foto/{legajo}?token=...
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                try {
                    $legajo = explode('?', $rutas[2])[0];
                    $token = $_GET['token'] ?? null;
                    if (!$token) {
                        ResponseHelper::forbidden("Token requerido");
                    }
                    $alumnos->obtenerFoto($legajo, $token);
                } catch (Exception $e) {
                    ResponseHelper::badRequest($e->getMessage());
                }
            } else {
                ResponseHelper::methodNotAllowed();
            }
        } else {
            ResponseHelper::notFound("Ruta no encontrada");
        }
        break;

    default:
        ResponseHelper::notFound("Ruta no encontrada");
        break;
}

?>



