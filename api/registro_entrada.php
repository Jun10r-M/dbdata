<?php
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        try {
            if (isset($uriPartes[2])) {
                $id = (int)$uriPartes[2];
                $stmt = $pdo->prepare("SELECT re.id, re.observaciones, re.fecha_entrada, 
                    concat(p.nombre, ' ', p.apePaterno, ' ', p.apeMaterno) as persona, 
                    v.placa, v.tipo, pa.piso, pa.espacio, u.usuario 
                    FROM $tabla re
                    INNER JOIN personas p ON re.persona_id = p.id
                    INNER JOIN vehiculos v ON re.vehiculo_id = v.id
                    INNER JOIN parqueo pa ON re.parqueo_id = pa.id
                    INNER JOIN usuarios u ON re.usuario_id = u.id 
                    WHERE re.id = ?");
                $stmt->execute([$id]);
                $result = $stmt->fetch();

                if ($result) {
                    $result['vehiculo'] = [
                        'placa' => $result['placa'],
                        'tipo' => $result['tipo']
                    ];
                    unset($result['placa'], $result['tipo']);
                    $result['parqueo'] = [
                        'piso' => $result['piso'],
                        'espacio' => $result['espacio']
                    ];
                    unset($result['piso'], $result['espacio']);
                }
                echo json_encode($result);
            } else {
                $stmt = $pdo->query("SELECT re.id, re.observaciones, re.fecha_entrada, 
                    concat(p.nombre, ' ', p.apePaterno, ' ', p.apeMaterno) as persona, 
                    v.placa, v.tipo, pa.piso, pa.espacio, u.usuario 
                    FROM $tabla re
                    INNER JOIN personas p ON re.persona_id = p.id
                    INNER JOIN vehiculos v ON re.vehiculo_id = v.id
                    INNER JOIN parqueo pa ON re.parqueo_id = pa.id
                    INNER JOIN usuarios u ON re.usuario_id = u.id");
                $result = $stmt->fetchAll();

                foreach ($result as &$row) {
                    $row['vehiculo'] = [
                        'placa' => $row['placa'],
                        'tipo' => $row['tipo']
                    ];
                    unset($row['placa'], $row['tipo']);
                    $row['parqueo'] = [
                        'piso' => $row['piso'],
                        'espacio' => $row['espacio']
                    ];
                    unset($row['piso'], $row['espacio']);
                }
                unset($row);

                echo json_encode($result);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Consulta fallida: ' . $e->getMessage()]);
        }
    break;

    case 'POST':
        if ($tabla === 'registro_entrada') {
            try {
                $data = json_decode(file_get_contents("php://input"), true);
                $persona_id = $data['persona_id'] ?? null;
                $vehiculo_id = $data['vehiculo_id'] ?? null;
                $parqueo_id = $data['parqueo_id'] ?? null;
                $usuario_id = $data['usuario_id'] ?? null;
                $observaciones = $data['observaciones'] ?? null;
                $fecha_entrada = date('Y-m-d H:i:s');

                if ($persona_id && $vehiculo_id && $parqueo_id && $usuario_id && $observaciones !== null && $fecha_entrada !== null) {
                    $stmt = $pdo->prepare(
                        "INSERT INTO registro_entrada (persona_id, vehiculo_id, parqueo_id, usuario_id, observaciones, fecha_entrada) VALUES (:persona_id, :vehiculo_id, :parqueo_id, :usuario_id, :observaciones, :fecha_entrada)"
                    );
                    $stmt->bindParam(':persona_id', $persona_id);
                    $stmt->bindParam(':vehiculo_id', $vehiculo_id);
                    $stmt->bindParam(':parqueo_id', $parqueo_id);
                    $stmt->bindParam(':usuario_id', $usuario_id);
                    $stmt->bindParam(':observaciones', $observaciones);
                    $stmt->bindParam(':fecha_entrada', $fecha_entrada);
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
        $persona_id = $put_vars['persona_id'] ?? null;
        $vehiculo_id = $put_vars['vehiculo_id'] ?? null;
        $parqueo_id = $put_vars['parqueo_id'] ?? null;
        $usuario_id = $put_vars['usuario_id'] ?? null;
        $observaciones = $put_vars['observaciones'] ?? null;
        $estadoReEntrada = $put_vars['estadoReEntrada'] ?? null;

        if ($id && $persona_id && $vehiculo_id && $parqueo_id && $usuario_id && $observaciones !== null && $estadoReEntrada !== null) {
            try {
                $stmt = $pdo->prepare("UPDATE registro_entrada SET persona_id = ?, vehiculo_id = ?, parqueo_id = ?, usuario_id = ?, observaciones = ?, estadoReEntrada = ? WHERE id = ?");
                $stmt->execute([$persona_id, $vehiculo_id, $parqueo_id, $usuario_id, $observaciones, $estadoReEntrada, $id]);
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
                $stmt = $pdo->prepare("UPDATE registro_entrada SET estadoReEntrada = 0 WHERE id = ?");
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