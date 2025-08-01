<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/consultorio_dental/includes/config.php';
checkRole(3); // Solo pacientes

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['id_cita']) && is_numeric($_POST['id_cita'])) {
    $id_cita = $_POST['id_cita'];

    try {
        // Verificar que la cita pertenece al usuario actual
        $stmt = $conn->prepare("SELECT c.id_cita
                                FROM citas c
                                JOIN pacientes p ON c.id_paciente = p.id_paciente
                                WHERE c.id_cita = :id_cita AND p.id_usuario_registro = :id_usuario");
        $stmt->bindParam(':id_cita', $id_cita);
        $stmt->bindParam(':id_usuario', $_SESSION['usuario']['id_usuario']);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            // Eliminar la cita
            $stmt = $conn->prepare("DELETE FROM citas WHERE id_cita = :id_cita");
            $stmt->bindParam(':id_cita', $id_cita);
            $stmt->execute();

            header("Location: citas.php?mensaje=Cita eliminada exitosamente");
            exit();
        } else {
            header("Location: citas.php?error=Cita no encontrada o no autorizada");
            exit();
        }

    } catch (PDOException $e) {
        echo "Error al eliminar la cita: " . $e->getMessage();
    }
} else {
    header("Location: citas.php?error=Solicitud inválida");
    exit();
}
