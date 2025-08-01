<?php
session_start();

// Verificar rol de recepcionista
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['nombre_rol'] !== 'Recepcionista') {
    http_response_code(403);
    exit(json_encode(['success' => false, 'message' => 'Acceso denegado']));
}

$pdo = new PDO("mysql:host=localhost;dbname=consultorio_dental;charset=utf8mb4", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$id_cita = filter_input(INPUT_POST, 'id_cita', FILTER_VALIDATE_INT);

if (!$id_cita) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'message' => 'ID de cita inválido']));
}

$sql = "SELECT c.id_cita, 
               p.nombre AS paciente, 
               DATE(c.fecha_hora) AS fecha, 
               TIME(c.fecha_hora) AS hora, 
               s.nombre_servicio AS servicio, 
               u.nombre AS dentista, 
               c.estado,
               c.pagado
        FROM citas c
        JOIN pacientes p ON c.id_paciente = p.id_paciente
        JOIN servicios s ON c.id_servicio = s.id_servicio
        JOIN dentistas d ON c.id_dentista = d.id_dentista
        JOIN usuarios u ON d.id_usuario = u.id_usuario
        WHERE c.id_cita = :id_cita";

$stmt = $pdo->prepare($sql);
$stmt->execute(['id_cita' => $id_cita]);
$cita = $stmt->fetch(PDO::FETCH_ASSOC);

if ($cita) {
    echo json_encode(['success' => true, 'cita' => $cita]);
} else {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Cita no encontrada']);
}
?>