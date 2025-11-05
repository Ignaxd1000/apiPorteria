<?php

header('Content-Type: application/json');

// Ajustar el límite máximo de tamaño del payload (1 MB)
define('MAX_PAYLOAD_SIZE', 1048576);

// Leer entrada cruda para POST/PUT
$rawInput = file_get_contents("php://input"); 

// Funcion helper para manejar entrada JSON con caching
// Cacheado del parseo para evitar múltiples parseos en la misma petición
function getJsonInput($rawInput) {
    static $parsedInput = null;
    static $cachedRawInput = null;
    
    // Parsear solo si es la primera llamada o la entrada ha cambiado
    if ($cachedRawInput !== $rawInput) {
        $cachedRawInput = $rawInput;
        $parsedInput = ValidationHelper::validateJsonInput($rawInput);
    }
    
    return $parsedInput;
}

// Funcion helper para manejar entrada PUT con caching
// Cacheado del parseo para evitar múltiples parseos en la misma petición
function getPutData($rawInput) {
    static $putData = null;
    static $cachedRawInput = null;
    
    // Parsear solo si es la primera llamada o la entrada ha cambiado
    if ($cachedRawInput !== $rawInput) {
        $cachedRawInput = $rawInput;
        parse_str($rawInput, $putData);
        if (empty($putData)) {
            // Try JSON format as fallback
            $putData = ValidationHelper::validateJsonInput($rawInput);
        }
    }
    
    return $putData;
}

$arrayRutas = array_values(array_filter(explode("/", $_SERVER['REQUEST_URI'])));

// Ajustar según el prefijo de la API (ej: api29-main)
$baseIndex = array_search('api29-main', $arrayRutas);
$rutas = array_slice($arrayRutas, $baseIndex + 1);

// Rutas que NO requieren autenticación, cambiar según sea necesario
$publicRoutes = [
    'POST:/alumnos/token' => true,  // Exactly 2 segments
    'POST:/clientes' => true        // Exactly 1 segment
];

// Verificar si la ruta actual es pública
$isPublicRoute = false;
if (!empty($rutas)) {
    $currentRouteKey2 = $_SERVER['REQUEST_METHOD'] . ':/' . implode('/', array_slice($rutas, 0, 2));
    $currentRouteKey1 = $_SERVER['REQUEST_METHOD'] . ':/' . $rutas[0];
    
    // Revisar si la ruta actual coincide con alguna ruta pública
    $isPublicRoute = (count($rutas) === 1 && isset($publicRoutes[$currentRouteKey1])) || 
                     (count($rutas) >= 2 && isset($publicRoutes[$currentRouteKey2]));
}


// Autenticación: validar credenciales si no es una ruta pública
if (!$isPublicRoute && !empty($rutas)) {
    try {
        // Obtener credenciales desde los headers
        $id_cliente = $_SERVER['HTTP_X_CLIENT_ID'] ?? null;
        $llave_secreta = $_SERVER['HTTP_X_SECRET_KEY'] ?? null;
        
        // Si no están en headers, intentar desde el cuerpo JSON (para POST/PUT)
        if ((!$id_cliente || !$llave_secreta) && in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT'])) {
            // Security: Check content length to prevent large payload attacks
            $contentLength = $_SERVER['CONTENT_LENGTH'] ?? 0;
            if ($contentLength > MAX_PAYLOAD_SIZE) {
                ResponseHelper::badRequest("Payload demasiado grande");
            }
            
            $inputData = json_decode($rawInput, true);
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

// Acà arranca el enrutamiento principal
switch ($rutas[0]) {
    case 'cursos':
        $cursos = new ControladorCursos();
        if (!isset($rutas[1])) {
            // /cursos
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $cursos->index(null);
            } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
                try {
                    $datos = getJsonInput($rawInput);
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
                    $datos = getPutData($rawInput);
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
                    $datos = getJsonInput($rawInput);
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
                    $datos = getJsonInput($rawInput);
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
                    $datos = getJsonInput($rawInput);
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



