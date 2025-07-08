<?php
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        try {
            if (isset($uriPartes[2])) {
                $id = (int)$uriPartes[2];
                $stmt = $pdo->prepare("SELECT v.id, v.placa, v.tipo, p.id as personaId, p.identificacion, p.nombre, p.apePaterno, p.apeMaterno FROM $tabla v 
                INNER JOIN personas p ON v.persona_id = p.id WHERE v.id = ?");
                $stmt->execute([$id]);
                $result = $stmt->fetch();
                if ($result) {
                    $result['persona'] = [
                        'personaId' => $result['personaId'],
                        'identificacion' => $result['identificacion'],
                        'datos' => $result['nombre'] . ' ' . $result['apePaterno'] . ' ' . $result['apeMaterno']
                    ];
                    unset($result['personaId'], $result['identificacion'], $result['datos']);
                }
                echo json_encode($result);
            } else {
                $stmt = $pdo->query("SELECT v.id, v.placa, v.tipo, p.id as personaId, p.identificacion, p.nombre, p.apePaterno, p.apeMaterno FROM $tabla v 
                    INNER JOIN personas p ON v.persona_id = p.id");
                $result = $stmt->fetchAll();

                foreach ($result as &$row) {
                    $row['persona'] = [
                        'personaId' => $row['personaId'],
                        'identificacion' => $row['identificacion'],
                        'datos' => $row['nombre'] . ' ' . $row['apePaterno'] . ' ' . $row['apeMaterno']
                    ];
                    // Elimina los campos individuales si no los quieres en el resultado final
                    unset($row['personaId'], $row['identificacion'], $row['nombre'], $row['apePaterno'], $row['apeMaterno']);
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
        if ($tabla === 'vehiculos') {
            try {
                $data = json_decode(file_get_contents("php://input"), true);
                $placa = $data['placa'] ?? null;
                $tipo = $data['tipo'] ?? null;
                $persona_id = $data['persona_id'] ?? null;

                if ($placa && $tipo && $persona_id) {
                    $stmt = $pdo->prepare(
                        "INSERT INTO vehiculos (placa, tipo, persona_id) VALUES (:placa, :tipo, :persona_id)"
                    );
                    $stmt->bindParam(':placa', $placa);
                    $stmt->bindParam(':tipo', $tipo);
                    $stmt->bindParam(':persona_id', $persona_id);
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
        $placa = $put_vars['placa'] ?? null;
        $tipo = $put_vars['tipo'] ?? null;
        $persona_id = $put_vars['persona_id'] ?? null;
        $estadoVehiculo = $put_vars['estadoVehiculo'] ?? null;

        if ($id && $placa && $tipo && $persona_id) {
            try {
                $stmt = $pdo->prepare("UPDATE vehiculos SET placa = ?, tipo = ?, persona_id = ?, estadoVehiculo = ? WHERE id = ?");
                $stmt->execute([$placa, $tipo, $persona_id, $estadoVehiculo, $id]);
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
                $stmt = $pdo->prepare("UPDATE vehiculos SET estadoVehiculo = 0 WHERE id = ?");
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