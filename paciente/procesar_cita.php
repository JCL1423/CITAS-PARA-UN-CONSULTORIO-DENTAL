<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/consultorio_dental/includes/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar datos recibidos
    $campos_requeridos = ['fecha', 'hora', 'dentista', 'servicio'];
    foreach ($campos_requeridos as $campo) {
        if (empty($_POST[$campo])) {
            header("Location: citas.php?error=El+campo+".$campo."+es+obligatorio");
            exit();
        }
    }

    // Obtener y sanitizar datos
    $fecha = $conexion->real_escape_string($_POST['fecha']);
    $hora = $conexion->real_escape_string($_POST['hora']);
    $id_dentista = $conexion->real_escape_string($_POST['dentista']);
    $id_servicio = $conexion->real_escape_string($_POST['servicio']);
    
    // Combinar fecha y hora en formato DATETIME
    $fecha_hora = $fecha . ' ' . $hora . ':00';
    
    // Obtener ID de paciente (en producción usar $_SESSION['id_usuario'])
    $id_paciente = 1;

    // Query completo con todos los campos requeridos
    $sql = "INSERT INTO citas (fecha_hora, id_paciente, id_dentista, id_servicio) 
            VALUES ('$fecha_hora', '$id_paciente', '$id_dentista', '$id_servicio')";
    
    // Ejecutar consulta
    if ($conexion->query($sql)) {
        header("Location: citas.php?exito=1");
    } else {
        header("Location: citas.php?error=" . urlencode($conexion->error));
    }

    $conexion->close();
    exit();
}

// Si no es POST, redirigir
header("Location: citas.php");