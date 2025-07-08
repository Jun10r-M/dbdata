<?php
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        try {
            if (isset($uriPartes[2])) {
                $id = (int)$uriPartes[2];
                $stmt = $pdo->prepare("SELECT c.id, c.nombre, f.nombre as facultad FROM $tabla c
                                    INNER JOIN facultades f ON c.facultad_id = f.id 
                                    WHERE c.id = ?");
                $stmt->execute([$id]);
                $result = $stmt->fetch();
            } else {
                $stmt = $pdo->query("SELECT c.id, c.nombre, f.nombre as facultad FROM $tabla c
                                    INNER JOIN facultades f ON c.facultad_id = f.id 
                                    WHERE c.estadoCarreras = 1
                                    ");
                $result = $stmt->fetchAll();
            }
            echo json_encode($result);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Consulta fallida: ' . $e->getMessage()]);
        }
        break;

    case 'POST':
        if ($tabla === 'carreras') {
            try {
                $data = json_decode(file_get_contents("php://input"), true);
                $nombre = $data['nombre'] ?? null;
                $facultad_id = $data['facultad_id'] ?? null;

                if ($nombre && $facultad_id) {
                    $stmt = $pdo->prepare(
                        "INSERT INTO carreras (nombre, facultad_id) VALUES (:nombre, :facultad_id)"
                    );
                    $stmt->bindParam(':nombre', $nombre);
                    $stmt->bindParam(':facultad_id', $facultad_id);
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
        $nombre = $put_vars['nombre'] ?? null;
        $facultad_id = $put_vars['facultad_id'] ?? null;
        $estadoCarreras = $put_vars['estadoCarreras'] ?? null;

        if ($id && $nombre && $facultad_id && $estadoCarreras !== null) {
            try {
                $stmt = $pdo->prepare("UPDATE carreras SET nombre = ?, facultad_id = ?, estadoCarreras = ? WHERE id = ?");
                $stmt->execute([$nombre, $facultad_id, $estadoCarreras, $id]);
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
                $stmt = $pdo->prepare("UPDATE carreras SET estadoCarreras = 0  WHERE id = ?");
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