<?php
// Habilitar visualización de errores para depuración (eliminar en producción)
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once $_SERVER['DOCUMENT_ROOT'] . '/consultorio_dental/includes/config.php';
checkRole(3); // Solo pacientes

if (!isset($_GET['id_cita'])) {
    $_SESSION['error'] = "No se especificó la cita a visualizar";
    header("Location: citas.php");
    exit();
}

$id_cita = (int)$_GET['id_cita'];

try {
    // Consulta mejorada con manejo de errores
    $sql = "
        SELECT c.*, 
               s.nombre_servicio, 
               CONCAT(u.nombre, ' ', u.apellido) AS dentista,
               DATE_FORMAT(c.fecha_hora, '%d/%m/%Y') as fecha,
               DATE_FORMAT(c.fecha_hora, '%H:%i') as hora,
               c.pagado
        FROM citas c
        JOIN servicios s ON c.id_servicio = s.id_servicio
        JOIN dentistas d ON c.id_dentista = d.id_dentista
        JOIN usuarios u ON d.id_usuario = u.id_usuario
        WHERE c.id_cita = ? AND c.id_paciente = ?
    ";
    
    // Obtener ID del paciente
    $id_paciente = obtenerIdPaciente($_SESSION['usuario']['id_usuario']);
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id_cita, $id_paciente]);
    $cita = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cita) {
        throw new Exception("La cita solicitada no existe o no tienes permisos para verla");
    }

    require_once $_SERVER['DOCUMENT_ROOT'] . '/consultorio_dental/includes/header.php';
?>

<!-- Agregar estilos específicos para esta página -->
<style>
    /* Asegurar que el texto del estado sea negro y del mismo tamaño */
    .estado {
        color: black; /* Texto negro */
        font-size: inherit; /* Heredar el tamaño de fuente del contenedor padre */
    }

    /* Mejorar visibilidad de iconos */
    .estado i {
        color: black; /* Iconos negros */
        font-size: inherit; /* Heredar el tamaño de fuente del contenedor padre */
    }
</style>

<div class="container mt-4">
    <h2 class="mb-4">Detalles de la Cita</h2>
    
    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0">Información de la Cita #<?= $id_cita ?></h4>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <h5 class="text-primary"><i class="fas fa-calendar-alt"></i> Fecha y Hora</h5>
                        <p><strong>Fecha:</strong> <?= $cita['fecha'] ?></p>
                        <p><strong>Hora:</strong> <?= $cita['hora'] ?></p>
                    </div>
                    <div class="mb-3">
                        <h5 class="text-primary"><i class="fas fa-info-circle"></i> Estado</h5>
                        <span class="estado">
                            <?php if ($cita['estado'] === 'Completada'): ?>
                                ✅ Completada
                            <?php else: ?>
                                <?= htmlspecialchars($cita['estado']) ?>
                            <?php endif; ?>
                            
                            <?php if ($cita['pagado'] === 'Sí'): ?>
                                | 💰 Pagada
                            <?php endif; ?>
                        </span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <h5 class="text-primary"><i class="fas fa-user-md"></i> Dentista</h5>
                        <p>Dr. <?= $cita['dentista'] ?></p>
                    </div>
                    <div class="mb-3">
                        <h5 class="text-primary"><i class="fas fa-teeth-open"></i> Servicio</h5>
                        <p><?= $cita['nombre_servicio'] ?></p>
                    </div>
                </div>
            </div>
            
            <?php if (!empty($cita['notas'])): ?>
            <div class="mt-3">
                <h5 class="text-primary"><i class="fas fa-sticky-note"></i> Notas Adicionales</h5>
                <p><?= nl2br(htmlspecialchars($cita['notas'])) ?></p>
            </div>
            <?php endif; ?>
            
            <div class="mt-4">
                <a href="citas.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i> Volver al listado
                </a>
            </div>
        </div>
    </div>
</div>

<?php
    require_once $_SERVER['DOCUMENT_ROOT'] . '/consultorio_dental/includes/footer.php';
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header("Location: citas.php");
    exit();
}

// Función auxiliar para obtener ID de paciente
function obtenerIdPaciente($id_usuario) {
    global $conn;
    $stmt = $conn->prepare("SELECT id_paciente FROM pacientes WHERE id_usuario_registro = ?");
    $stmt->execute([$id_usuario]);
    return $stmt->fetchColumn();
}
?>