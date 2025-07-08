<?php
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        try {
            if (isset($uriPartes[2])) {
                $id = (int)$uriPartes[2];
                $stmt = $pdo->prepare("SELECT id, usuario, estadoUsuario FROM $tabla WHERE id = ?");
                $stmt->execute([$id]);
                $result = $stmt->fetch();
            } else {
                $stmt = $pdo->query("SELECT id, usuario, estadoUsuario FROM $tabla");
                $result = $stmt->fetchAll();
            }
            echo json_encode($result);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Consulta fallida: ' . $e->getMessage()]);
        }
        break;

    case 'POST':
        if ($tabla === 'usuarios') {
            try {
                $data = json_decode(file_get_contents("php://input"), true);
                $usuario = $data['usuario'] ?? null;
                $contrasena = $data['contrasena'] ?? null;

                if ($usuario && $contrasena ) {
                    $stmt = $pdo->prepare(
                        "INSERT INTO usuarios (usuario, contrasena) VALUES (:usuario, :contrasena)"
                    );
                    $stmt->bindParam(':usuario', $usuario);
                    $stmt->bindParam(':contrasena', $contrasena);
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
        $usuario = $put_vars['usuario'] ?? null;
        $contrasena = $put_vars['contrasena'] ?? null;
        $estadoUsuario = $put_vars['estadoUsuario'] ?? null;

        if ($id && $usuario && $contrasena && $estadoUsuario !== null) {
            try {
                $stmt = $pdo->prepare("UPDATE usuarios SET usuario = ?, contrasena = ?, estadoUsuario = ? WHERE id = ?");
                $stmt->execute([$usuario, $contrasena, $estadoUsuario, $id]);
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
                $stmt = $pdo->prepare("UPDATE usuarios SET estadoUsuario = 0 WHERE id = ?");
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