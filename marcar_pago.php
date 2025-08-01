<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['nombre_rol'] !== 'Recepcionista') {
    http_response_code(403);
    exit();
}

$pdo = new PDO("mysql:host=localhost;dbname=consultorio_dental;charset=utf8mb4", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$id = filter_input(INPUT_POST, 'id_cita', FILTER_VALIDATE_INT);
if ($id) {
    $stmt = $pdo->prepare("UPDATE citas SET pagado = 'Sí' WHERE id_cita = ?");
    $stmt->execute([$id]);
    echo "ok";
}
?>