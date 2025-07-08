<?php
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        try {
            if (isset($uriPartes[2])) {
                $id = (int)$uriPartes[2];
                $stmt = $pdo->prepare("SELECT rs.id, re.id, u.usuario, rs.fecha_salida FROM $tabla rs
                INNER JOIN registro_entrada re ON rs.entrada_id = re.id
                INNER JOIN usuarios u ON rs.usuario_id = u.id
                WHERE rs.id = ?");
                $stmt->execute([$id]);
                $result = $stmt->fetch();
            } else {
                $stmt = $pdo->query("SELECT rs.id, re.id, u.usuario, rs.fecha_salida FROM $tabla rs
                INNER JOIN registro_entrada re ON rs.entrada_id = re.id
                INNER JOIN usuarios u ON rs.usuario_id = u.id");
                $result = $stmt->fetchAll();
            }
            echo json_encode($result);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Consulta fallida: ' . $e->getMessage()]);
        }
        break;

    case 'POST':
        if ($tabla === 'registro_salida') {
            try {
                $data = json_decode(file_get_contents("php://input"), true);
                $entrada_id = $data['entrada_id'] ?? null;
                $usuario_id = $data['usuario_id'] ?? null;
                $fecha_salida = date('Y-m-d H:i:s');

                if ($entrada_id && $usuario_id && $fecha_salida) {
                    $stmt = $pdo->prepare(
                        "INSERT INTO registro_salida (entrada_id, usuario_id, fecha_salida) VALUES (:entrada_id, :usuario_id, :fecha_salida)"
                    );
                    $stmt->bindParam(':entrada_id', $entrada_id);
                    $stmt->bindParam(':usuario_id', $usuario_id);
                    $stmt->bindParam(':fecha_salida', $fecha_salida);
                    $stmt->execute();
                    $lastId = $pdo->lastInsertId();
                    echo json_encode(['mensaje' => 'Registro creado correctamente', 'id' => $lastId]);
                } else {
                    http_response_code(400);
                    echo json_encode(['error' => 'Datos incompletos']);
                }
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['error' => 'Error al crear el registro: ' . $e->getMessage()]);
            }
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Operación no permitida para esta tabla']);
        }
        break;

    case 'PUT':
        parse_str(file_get_contents("php://input"), $put_vars);
        $id = $put_vars['id'] ?? null;
        $entrada_id = $put_vars['entrada_id'] ?? null;
        $usuario_id = $put_vars['usuario_id'] ?? null;
        $estadoReSalida = $put_vars['estadoReSalida'] ?? null;

        if ($id && $entrada_id && $usuario_id && $fecha_salida && $estadoReSalida !== null) {
            try {
                $stmt = $pdo->prepare("UPDATE registro_salida SET entrada_id = ?, usuario_id = ?, estadoReSalida = ? WHERE id = ?");
                $stmt->execute([$entrada_id, $usuario_id, $estadoReSalida, $id]);
                echo json_encode(['mensaje' => 'Registro actualizado correctamente']);
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['error' => 'Error al actualizar el registro: ' . $e->getMessage()]);
            }
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Faltan datos para actualizar el registro']);
        }
        break;

    case 'DELETE':
        $id = $uriPartes[2] ?? null;
        if ($id) {
            try {
                $stmt = $pdo->prepare("UPDATE registro_salida SET estadoReSalida = 0 WHERE id = ?");
                $stmt->execute([$id]);
                echo json_encode(['mensaje' => 'Registro eliminado correctamente']);
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['error' => 'Error al eliminar el registro: ' . $e->getMessage()]);
            }
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'ID no proporcionado']);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Método HTTP no permitido']);
        break;
}

?>