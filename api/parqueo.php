<?php
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        try {
            if (isset($uriPartes[2])) {
                $id = (int)$uriPartes[2];
                $stmt = $pdo->prepare("SELECT * FROM $tabla WHERE id = ? ");
                $stmt->execute([$id]);
                $result = $stmt->fetch();
            } else {
                $stmt = $pdo->query("SELECT * FROM $tabla WHERE estadoParqueo = 'libre'");
                $result = $stmt->fetchAll();
            }
            echo json_encode($result);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Consulta fallida: ' . $e->getMessage()]);
        }
        break;

    case 'POST':
        if ($tabla === 'parqueo') {
            try {
                $data = json_decode(file_get_contents("php://input"), true);
                $piso = $data['nombre'] ?? null;
                $espacio = $data['apePaterno'] ?? null;
                $estadoParqueo = $data['apeMaterno'] ?? null;

                $enumEstadoParqueo = ['libre', 'reservado', 'ocupado']; 

                if (
                    $piso && $espacio && $estadoParqueo &&
                    in_array($estadoParqueo, $enumEstadoParqueo))
                {
                    $stmt = $pdo->prepare(
                        "INSERT INTO parqueo 
                        (piso, espacio, estado_parqueo) 
                        VALUES 
                        (:piso, :espacio, :estadoParqueo)" 
                    );
                    $stmt->bindParam(':piso', $piso);
                    $stmt->bindParam(':espacio', $espacio);
                    $stmt->bindParam(':estadoParqueo', $estadoParqueo);
                    $stmt->execute();
                    $lastId = $pdo->lastInsertId();
                    echo json_encode(['mensaje' => 'Registro creado correctamente', 'id' => $lastId]);
                } else {
                    http_response_code(400);
                    echo json_encode(['error' => 'Datos incompletos o tipos de datos inválido']);
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
        $piso = $put_vars['piso'] ?? null;
        $espacio = $put_vars['espacio'] ?? null;
        $estadoParqueo = $put_vars['estadoParqueo'] ?? null;

        $enumEstadoParqueo = ['libre', 'reservado', 'ocupado'];

        if($id && $piso && $espacio && $estadoParqueo && in_array($estadoParqueo, $enumEstadoParqueo)){
            try {
                $stmt = $pdo->prepare("UPDATE parqueo SET piso = ?, espacio = ?, estado_parqueo = ? WHERE id = ?");
                $stmt->execute([$piso, $espacio, $estadoParqueo, $id]);
                echo json_encode(['mensaje' => 'Registro actualizado correctamente']);
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['error' => 'Error al actualizar el registro: ' . $e->getMessage()]);
            }
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Faltan datos o tipos de datos inválidos para actualizar el registro']);
        }
        break;

    case 'DELETE':
        $id = $uriPartes[2] ?? null;
        if ($id) {
            try {
                $stmt = $pdo->prepare("UPDATE parqueo SET estadoParqueo = ? WHERE id = ?");
                $stmt->execute(["ocupado", $id]);
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