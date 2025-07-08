<?php
header('Content-Type: application/json');

// Obtener el token de Authorization
$headers = getallheaders();
$token = '';

if (isset($headers['Authorization'])) {
    if (preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches)) {
        $token = $matches[1];
    }
}

// if (!$token) {
//     http_response_code(401);
//     echo json_encode(['error' => 'Token no proporcionado.']);
//     exit;
// }

// Restaurar la sesión desde el token
// session_id($token);
// session_start();

// if (!isset($_SESSION['usuario'])) {
//     http_response_code(401);
//     echo json_encode(['error' => 'Token inválido o sesión expirada.']);
//     exit;
// }

require_once __DIR__ . '/../db_conect.php';

$tablasPermitidas = [
    'personas', 'vehiculos', 'parqueo', 'registro_entrada',
    'registro_salida', 'eventos', 'evento_persona',
    'usuarios', 'carreras', 'facultades'
];

$uri = trim($_SERVER['REQUEST_URI'], '/');
$uriPartes = explode('/', $uri);

if (count($uriPartes) < 2 || $uriPartes[0] !== 'api') {
    http_response_code(400);
    echo json_encode(['error' => 'Ruta no valida']);
    exit;
}

$tabla = $uriPartes[1];
if (!in_array($tabla, $tablasPermitidas)) {
    http_response_code(404);
    echo json_encode(['error' => 'Tabla no permitida']);
    exit;
}

$archivo = __DIR__ . "/{$tabla}.php";
if (file_exists($archivo)) {
    require $archivo;
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Archivo de operaciones no encontrado']);
    exit;
}
?>
