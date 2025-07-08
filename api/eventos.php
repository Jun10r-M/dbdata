<?php
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        try {
            if (isset($uriPartes[2])) {
                $id = (int)$uriPartes[2];
                $stmt = $pdo->prepare("SELECT id, nombre_evento, descripcion, cantidad_estudiantes, fecha_evento FROM $tabla WHERE id = ?");
                $stmt->execute([$id]);
                $result = $stmt->fetch();
            } else {
                $stmt = $pdo->query("SELECT id, nombre_evento, descripcion, cantidad_estudiantes, fecha_evento  FROM $tabla");
                $result = $stmt->fetchAll();
            }
            echo json_encode($result);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Consulta fallida: ' . $e->getMessage()]);
        }
        break;

    case 'POST':
        if ($tabla === 'eventos') {
            try {
                $data = json_decode(file_get_contents("php://input"), true);
                $nombre_evento = $data['nombre_evento'] ?? null;
                $descripcion = $data['descripcion'] ?? null;
                $cantidad_estudiantes = $data['cantidad_estudiantes'] ?? null;
                $fecha_evento = $data['fecha_evento'] ?? null;

                if ($nombre_evento && $descripcion && $cantidad_estudiantes && $fecha_evento) {
                    $stmt = $pdo->prepare(
                        "INSERT INTO eventos (nombre_evento, descripcion, cantidad_estudiantes, fecha_evento) VALUES (:nombre_evento, :descripcion, :cantidad_estudiantes, :fecha_evento)"
                    );
                    $stmt->bindParam(':nombre_evento', $nombre_evento);
                    $stmt->bindParam(':descripcion', $descripcion);
                    $stmt->bindParam(':cantidad_estudiantes', $cantidad_estudiantes);
                    $stmt->bindParam(':fecha_evento', $fecha_evento);
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
        $nombre_evento = $put_vars['nombre_evento'] ?? null;
        $descripcion = $put_vars['descripcion'] ?? null;
        $cantidad_estudiantes = $put_vars['cantidad_estudiantes'] ?? null;
        $fecha_evento = $put_vars['fecha_evento'] ?? null;
        $estadoEvento = $put_vars['estadoEvento'] ?? null;

        if ($id && $nombre_evento && $descripcion && $cantidad_estudiantes && $fecha_evento && $estadoEvento !== null) {
            try {
                $stmt = $pdo->prepare("UPDATE eventos SET nombre_evento = ?, descripcion = ?, cantidad_estudiantes = ?, fecha_evento = ?, estadoEvento = ? WHERE id = ?");
                $stmt->execute([$nombre_evento, $descripcion, $cantidad_estudiantes, $fecha_evento, $estadoEvento, $id]);
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
                $stmt = $pdo->prepare("UPDATE eventos SET estadoEvento = 0 WHERE id = ?");
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