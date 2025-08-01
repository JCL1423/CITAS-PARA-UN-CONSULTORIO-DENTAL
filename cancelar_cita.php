<?php
session_start();

// Verificar si el usuario es la recepcionista
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['nombre_rol'] !== 'Recepcionista') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado']);
    exit();
}

// Conexión a la base de datos
$servername = "localhost";
$username = "root";
$password = ""; // Cambia si tienes contraseña
$dbname = "consultorio_dental";

try {
    $conexion = new PDO(
        "mysql:host=$servername;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    die(json_encode(['success' => false, 'message' => 'Error de conexión: ' . $e->getMessage()]));
}

// Obtener el ID de la cita desde la solicitud POST
$data = json_decode(file_get_contents('php://input'), true);
$id_cita = isset($data['id_cita']) ? intval($data['id_cita']) : 0;

// Actualizar el estado de la cita en la base de datos
$sql = "UPDATE citas SET estado = 'Cancelada' WHERE id_cita = :id_cita";
$stmt = $conexion->prepare($sql);
$stmt->execute(['id_cita' => $id_cita]);

// Devolver respuesta JSON
echo json_encode(['success' => true, 'message' => 'Cita cancelada correctamente']);
?>