<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/consultorio_dental/includes/config.php';
checkRole(3); // Solo pacientes

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        // Obtener ID de paciente desde la tabla pacientes
        $stmt = $conn->prepare("SELECT id_paciente FROM pacientes WHERE id_usuario_registro = :id_usuario");
        $stmt->bindParam(':id_usuario', $_SESSION['usuario']['id_usuario']);
        $stmt->execute();
        $paciente = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$paciente) {
            throw new Exception("No se encontró el paciente.");
        }

        $id_paciente = $paciente['id_paciente'];
        $id_dentista = (int)$_POST['dentista'];
        $id_servicio = (int)$_POST['servicio'];
        $fecha = $_POST['fecha']; // YYYY-MM-DD
        $hora = $_POST['hora'];   // HH:MM
        $fecha_hora = "$fecha $hora";
        $estado = 'Programada';
        $notas = trim($_POST['notas']);
        $id_usuario_creador = $_SESSION['usuario']['id_usuario'];

        // VALIDACIÓN PARA EVITAR CITAS DUPLICADAS
        $stmtCheck = $conn->prepare("SELECT id_cita FROM citas WHERE DATE(fecha_hora) = ? AND HOUR(fecha_hora) = ? AND MINUTE(fecha_hora) = ? AND id_dentista = ? AND id_servicio = ? AND estado != 'Cancelada' LIMIT 1");
        $partes = explode(':', $hora);
        $stmtCheck->execute([$fecha, $partes[0], $partes[1], $id_dentista, $id_servicio]);
        
        if ($stmtCheck->rowCount() > 0) {
            $_SESSION['error'] = "Fecha y hora ya están ocupadas";
            // NO REDIRIGIR, solo mostrar el mensaje
            require_once $_SERVER['DOCUMENT_ROOT'] . '/consultorio_dental/includes/header.php';
            ?>
            <div class="container">
                <div class="alert alert-danger mt-4"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                <div class="text-center mt-3">
                    <a href="nueva_cita.php" class="btn btn-primary">Volver a intentar</a>
                </div>
            </div>
            <?php
            require_once $_SERVER['DOCUMENT_ROOT'] . '/consultorio_dental/includes/footer.php';
            exit();
        }

        // Insertar la cita
        $stmt = $conn->prepare("INSERT INTO citas 
            (id_paciente, id_dentista, id_servicio, fecha_hora, estado, notas, id_usuario_creador)
            VALUES 
            (:id_paciente, :id_dentista, :id_servicio, :fecha_hora, :estado, :notas, :id_usuario_creador)");

        $stmt->execute([
            ':id_paciente' => $id_paciente,
            ':id_dentista' => $id_dentista,
            ':id_servicio' => $id_servicio,
            ':fecha_hora' => $fecha_hora,
            ':estado' => $estado,
            ':notas' => $notas,
            ':id_usuario_creador' => $id_usuario_creador
        ]);

        $_SESSION['exito'] = "Cita registrada exitosamente.";
        header("Location:citas.php");
        exit();

    } catch (Exception $e) {
        error_log("Error al registrar cita: " . $e->getMessage());
        $_SESSION['error'] = "No se pudo registrar la cita.";
        header("Location:nueva_cita.php");
        exit();
    }
} else {
    header("Location: nueva_cita.php");
    exit();
}
?>