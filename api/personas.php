<?php
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        try {
            if (isset($uriPartes[2])) {
                $id = (int)$uriPartes[2];
                $stmt = $pdo->prepare("SELECT p.id, p.tipo_identificacion, p.identificacion, p.nombre, p.apePaterno, p.apeMaterno, f.nombre as facultad FROM $tabla p INNER JOIN facultades f ON p.facultad_id = f.id 
                WHERE p.id = ? AND p.estadoPersona = 1");
                $stmt->execute([$id]);
                $result = $stmt->fetch();
            } else {
                $stmt = $pdo->query("SELECT p.id, p.tipo_identificacion, p.identificacion, p.nombre, p.apePaterno, p.apeMaterno, f.nombre as facultad FROM $tabla p INNER JOIN facultades f ON p.facultad_id = f.id 
                WHERE p.estadoPersona = 1");
                $result = $stmt->fetchAll();
            }
            echo json_encode($result);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Consulta fallida: ' . $e->getMessage()]);
        }
    break;

    case 'POST':
        if ($tabla === 'personas') {
            try {
                $data = json_decode(file_get_contents("php://input"), true);
                
                // Si es búsqueda
                if (isset($data['modo']) && $data['modo'] === 'buscar' && !empty($data['identificacion'])) {
                    $stmt = $pdo->prepare("SELECT p.id, p.tipo_identificacion, p.identificacion, p.nombre, p.apePaterno, p.apeMaterno, p.tipo_persona, f.nombre AS facultad 
                        FROM personas p
                        INNER JOIN facultades f ON p.facultad_id = f.id 
                        WHERE p.identificacion = ? AND p.estadoPersona = 1
                    ");
                    $stmt->execute([$data['identificacion']]);
                    $result = $stmt->fetch();
                    if ($result) {
                        echo json_encode(['existe' => true, 'datos' => $result]);
                    } else {
                        echo json_encode(['existe' => false, 'datos' => null]);
                    }
                    exit;
                }
            
                // Si es creación
                $nombre = $data['nombre'] ?? null;
                $apePaterno = $data['apePaterno'] ?? null;
                $apeMaterno = $data['apeMaterno'] ?? null;
                $tipoPersona = $data['tipoPersona'] ?? null;
                $tipoIdentificacion = $data['tipoIdentificacion'] ?? null;
                $identificacion = $data['identificacion'] ?? null;
                $facultad_id = $data['facultad_id'] ?? null;
            
                $enumTipoPersona = ['Estudiante', 'Docente', 'Invitado']; 
                $enumTipoIdentificacion = ['DNI', 'CARNET_EXTRANJERIA']; 
            
                if (
                    $identificacion && $nombre && $apePaterno && $apeMaterno &&
                    $tipoPersona && $tipoIdentificacion && $facultad_id &&
                    in_array($tipoPersona, $enumTipoPersona) &&
                    in_array($tipoIdentificacion, $enumTipoIdentificacion)
                ) {
                    $stmt = $pdo->prepare(
                        "INSERT INTO personas 
                        (nombre, apePaterno, apeMaterno, tipo_persona, tipo_identificacion, identificacion, facultad_id) 
                        VALUES 
                        (:nombre, :apePaterno, :apeMaterno, :tipoPersona, :tipoIdentificacion, :identificacion, :facultad_id)"
                    );
                    $stmt->bindParam(':nombre', $nombre);
                    $stmt->bindParam(':apePaterno', $apePaterno);
                    $stmt->bindParam(':apeMaterno', $apeMaterno);
                    $stmt->bindParam(':tipoPersona', $tipoPersona);
                    $stmt->bindParam(':tipoIdentificacion', $tipoIdentificacion);
                    $stmt->bindParam(':identificacion', $identificacion);
                    $stmt->bindParam(':facultad_id', $facultad_id);
                    $stmt->execute();
                    $lastId = $pdo->lastInsertId();
                    echo json_encode(['mensaje' => 'Registro creado correctamente', 'id' => $lastId]);
                } else {
                    http_response_code(400);
                    echo json_encode(['error' => 'Datos incompletos o tipos inválidos']);
                }
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['error' => 'Error al procesar la solicitud: ' . $e->getMessage()]);
            }
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Operación no permitida para esta tabla']);
        }
        break;

    case 'PUT':
        parse_str(file_get_contents("php://input"), $put_vars);
        $id = $put_vars['id'] ?? null;
        $identificacion = $put_vars['identificacion'] ?? null;
        $nombre = $put_vars['nombre'] ?? null;
        $apePaterno = $put_vars['apePaterno'] ?? null;
        $apeMaterno = $put_vars['apeMaterno'] ?? null;
        $tipoPersona = $put_vars['tipoPersona'] ?? null;
        $tipoIdentificacion = $put_vars['tipoIdentificacion'] ?? null;
        $estadoPersona = $put_vars['estadoPersona'] ?? null;

        $enumTipoPersona = ['Estudiante', 'Docente', 'Invitado']; 
        $enumTipoIdentificacion = ['DNI', 'CARNET_EXTRANJERIA']; 

        if ($id && $identificacion && $nombre && $apePaterno && $apeMaterno && $tipoPersona && $tipoIdentificacion && 
        in_array($tipoPersona, $enumTipoPersona) && in_array($tipoIdentificacion, $enumTipoIdentificacion && $estadoPersona !== null)) {
            try {
                $stmt = $pdo->prepare("UPDATE personas SET identificacion = ?, nombre = ?, apePaterno = ?, apeMaterno = ?, tipo_persona = ?, tipo_identificacion = ?, estadoPersona = ? WHERE id = ?");
                $stmt->execute([$identificacion, $nombre, $apePaterno, $apeMaterno, $tipoPersona, $tipoIdentificacion, $estadoPersona, $id]);
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
                $stmt = $pdo->prepare("UPDATE personas SET estadoPersona = 0  WHERE id = ?");
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