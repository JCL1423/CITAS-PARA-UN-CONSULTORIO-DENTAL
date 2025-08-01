<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/consultorio_dental/includes/config.php';
checkRole(3); // Solo para pacientes

$id_paciente = null;

// Obtener ID del paciente asociado al usuario
try {
    $stmt = $conn->prepare("SELECT id_paciente FROM pacientes WHERE id_usuario_registro = :id_usuario");
    $stmt->bindParam(':id_usuario', $_SESSION['usuario']['id_usuario']);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $id_paciente = $result['id_paciente'];
    } else {
        // Si no existe, crear un registro de paciente básico
        $stmt = $conn->prepare("INSERT INTO pacientes 
                               (nombre, apellido, id_usuario_registro) 
                               VALUES (:nombre, :apellido, :id_usuario)");
        $stmt->bindParam(':nombre', $_SESSION['usuario']['nombre']);
        $stmt->bindParam(':apellido', $_SESSION['usuario']['apellido']);
        $stmt->bindParam(':id_usuario', $_SESSION['usuario']['id_usuario']);
        $stmt->execute();
        
        $id_paciente = $conn->lastInsertId();
    }
} catch(PDOException $e) {
    $error = "Error al obtener información del paciente: " . $e->getMessage();
}

// Actualizar citas vencidas a "Cancelada"
try {
    // Obtener citas vencidas del paciente
    $stmt = $conn->prepare("UPDATE citas 
                           SET estado = 'Cancelada' 
                           WHERE id_paciente = :id_paciente 
                           AND fecha_hora < NOW() 
                           AND estado = 'Programada'");
    $stmt->bindParam(':id_paciente', $id_paciente);
    $stmt->execute();
} catch(PDOException $e) {
    $error = "Error al actualizar citas vencidas: " . $e->getMessage();
}

// Obtener citas del paciente (ahora incluye el campo 'pagado')
try {
    $stmt = $conn->prepare("SELECT c.id_cita, d.nombre as dentista_nombre, d.apellido as dentista_apellido, 
                           c.fecha_hora, s.nombre_servicio, c.duracion, c.estado, c.pagado
                           FROM citas c
                           JOIN dentistas dt ON c.id_dentista = dt.id_dentista
                           JOIN usuarios d ON dt.id_usuario = d.id_usuario
                           JOIN servicios s ON c.id_servicio = s.id_servicio
                           WHERE c.id_paciente = :id_paciente
                           ORDER BY c.fecha_hora DESC");
    $stmt->bindParam(':id_paciente', $id_paciente);
    $stmt->execute();
    $citas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Error al obtener citas: " . $e->getMessage();
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/consultorio_dental/includes/header.php';
?>

<!-- Estilos personalizados para esta página -->
<style>
    /* Mejorar visibilidad del estado */
    .estado-combinado {
        color: black;
        background-color: #e9ecef;
        border: 1px solid #ccc;
        padding: 5px 10px;
        border-radius: 5px;
        font-weight: normal;
        font-size: 0.9em;
    }
    .estado-combinado .icono {
        margin-right: 5px;
    }
</style>

<div class="container">
    <h2 class="mb-4">Mis Citas</h2>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5>Historial de Citas</h5>
                <a href="pre_cita.php" class="btn btn-primary">Solicitar Nueva Cita</a>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($citas)): ?>
                <div class="alert alert-info">No tienes citas programadas.</div>
            <?php else: ?>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Fecha y Hora</th>
                            <th>Dentista</th>
                            <th>Servicio</th>
                            <th>Duración</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($citas as $cita): ?>
                            <tr>
                                <td><?php echo date('d/m/Y H:i', strtotime($cita['fecha_hora'])); ?></td>
                                <td><?php echo $cita['dentista_nombre'] . ' ' . $cita['dentista_apellido']; ?></td>
                                <td><?php echo $cita['nombre_servicio']; ?></td>
                                <td><?php echo $cita['duracion'] . ' min'; ?></td>
                                <td>
                                    <span class="estado-combinado">
                                        <?php if ($cita['estado'] === 'Completada'): ?>
                                            <span class="icono">✅</span> Completada
                                        <?php elseif ($cita['estado'] === 'Cancelada'): ?>
                                            <span class="icono">❌</span> Cancelada
                                        <?php else: ?>
                                            <?= htmlspecialchars($cita['estado']) ?>
                                        <?php endif; ?>
                                        
                                        <?php if ($cita['pagado'] === 'Sí'): ?>
                                            <?php if ($cita['estado'] === 'Completada'): ?> | <?php endif; ?>
                                            <span class="icono">💰</span> Pagada
                                        <?php endif; ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="ver_cita.php?id_cita=<?php echo $cita['id_cita']; ?>" class="btn btn-sm btn-info">Ver</a>
                                    <?php if (strtolower(trim($cita['estado'])) != 'cancelada'): ?>
                                        <form action="cancelar_cita.php" method="POST" onsubmit="return confirm('¿Estás seguro que deseas cancelar esta cita?');" style="display:inline;">
                                            <input type="hidden" name="id_cita" value="<?php echo $cita['id_cita']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">Cancelar</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/consultorio_dental/includes/footer.php'; ?>

