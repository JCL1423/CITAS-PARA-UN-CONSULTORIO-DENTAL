<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/consultorio_dental/includes/config.php';
checkRole(2); // Solo para dentistas

$id_dentista = $_SESSION['usuario']['id_usuario'];

// Obtener citas del dentista
try {
    $stmt = $conn->prepare("SELECT c.id_cita, p.nombre, p.apellido, c.fecha_hora, 
                           c.duracion, s.nombre_servicio, c.estado
                           FROM citas c
                           JOIN pacientes p ON c.id_paciente = p.id_paciente
                           JOIN servicios s ON c.id_servicio = s.id_servicio
                           JOIN dentistas d ON c.id_dentista = d.id_dentista
                           WHERE d.id_usuario = :id_dentista
                           AND c.fecha_hora >= CURDATE()
                           ORDER BY c.fecha_hora ASC");
    $stmt->bindParam(':id_dentista', $id_dentista);
    $stmt->execute();
    $citas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Error al obtener citas: " . $e->getMessage();
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/consultorio_dental/includes/header.php';
?>

<div class="container">
    <h2 class="mb-4">Mi Agenda</h2>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5>Próximas Citas</h5>
                <a href="nueva_cita.php" class="btn btn-primary">Agendar Nueva Cita</a>
            </div>
        </div>
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Fecha y Hora</th>
                        <th>Paciente</th>
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
                            <td><?php echo $cita['nombre'] . ' ' . $cita['apellido']; ?></td>
                            <td><?php echo $cita['nombre_servicio']; ?></td>
                            <td><?php echo $cita['duracion'] . ' min'; ?></td>
                            <td>
                                <span class="badge 
                                    <?php echo $cita['estado'] == 'Programada' ? 'badge-primary' : 
                                          ($cita['estado'] == 'Confirmada' ? 'badge-success' : 
                                          ($cita['estado'] == 'Cancelada' ? 'badge-danger' : 'badge-secondary')); ?>">
                                    <?php echo $cita['estado']; ?>
                                </span>
                            </td>
                            <td>
                                <a href="detalle_cita.php?id=<?php echo $cita['id_cita']; ?>" class="btn btn-sm btn-info">Ver</a>
