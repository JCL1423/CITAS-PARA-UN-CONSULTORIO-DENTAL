<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/consultorio_dental/includes/config.php';

// Verificar sesión y rol de paciente
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_lifetime' => 86400,
        'cookie_path' => '/consultorio_dental/',
        'cookie_secure' => isset($_SERVER['HTTPS']),
        'cookie_httponly' => true
    ]);
}

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['id_rol'] != 3) {
    header("Location: /consultorio_dental/login.php");
    exit();
}

// Obtener información de servicios y dentistas
try {
    // Consulta para servicios activos
    $stmtServicios = $conn->query("SELECT * FROM servicios WHERE activo = 1");
    $servicios = $stmtServicios->fetchAll(PDO::FETCH_ASSOC);
    
    // Consulta para dentistas activos
    $stmtDentistas = $conn->query("SELECT d.*, u.nombre, u.apellido 
                                 FROM dentistas d
                                 JOIN usuarios u ON d.id_usuario = u.id_usuario
                                 WHERE d.activo = 1");
    $dentistas = $stmtDentistas->fetchAll(PDO::FETCH_ASSOC);
    
    // Consulta para costos actuales
    $stmtCostos = $conn->query("SELECT s.id_servicio, s.nombre_servicio, c.costo 
                               FROM costos c
                               JOIN servicios s ON c.id_servicio = s.id_servicio
                               WHERE c.activo = 1 AND (c.fecha_fin IS NULL OR c.fecha_fin >= CURDATE())");
    $costos = $stmtCostos->fetchAll(PDO::FETCH_ASSOC);
   
    // AQUÍ IRÍA LA VALIDACIÓN ADICIONAL SI EL FORMULARIO SE ENVÍA A SÍ MISMO
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $id_servicio = (int)$_POST['servicio'];
        $id_dentista = (int)$_POST['dentista'];
        
        // Verificar que el servicio tenga costo
        $tiene_costo = false;
        foreach ($costos as $costo) {
            if ($costo['id_servicio'] == $id_servicio) {
                $tiene_costo = true;
                break;
            }
        }
        
        if (!$tiene_costo) {
            $_SESSION['error'] = "El servicio seleccionado no tiene un costo configurado. Por favor elija otro servicio.";
            header("Location: /consultorio_dental/paciente/pre_cita.php");
            exit();
        }
        
      // Obtener id_paciente desde la base de datos
$stmtPaciente = $conn->prepare("SELECT id_paciente FROM pacientes WHERE id_usuario_registro = :id_usuario");
$stmtPaciente->bindParam(':id_usuario', $_SESSION['usuario']['id_usuario']);
$stmtPaciente->execute();
$paciente = $stmtPaciente->fetch(PDO::FETCH_ASSOC);

if ($paciente) {
    $id_paciente = $paciente['id_paciente'];
    
    // Generar fecha y hora como ejemplo (mañana a las 10:00 AM)
    $fecha_hora = date('Y-m-d 10:00:00', strtotime('+1 day'));

    // Obtener duración del servicio
    $stmtDuracion = $conn->prepare("SELECT duracion_estimada FROM servicios WHERE id_servicio = :id_servicio");
    $stmtDuracion->bindParam(':id_servicio', $id_servicio);
    $stmtDuracion->execute();
    $duracion = $stmtDuracion->fetchColumn();

    // Insertar cita
    $stmtInsert = $conn->prepare("INSERT INTO citas (id_paciente, id_dentista, id_servicio, fecha_hora, duracion, estado, motivo, id_usuario_creador)
                                  VALUES (:id_paciente, :id_dentista, :id_servicio, :fecha_hora, :duracion, 'Programada', '', :id_usuario_creador)");
    $stmtInsert->execute([
        ':id_paciente' => $id_paciente,
        ':id_dentista' => $id_dentista,
        ':id_servicio' => $id_servicio,
        ':fecha_hora' => $fecha_hora,
        ':duracion' => $duracion,
        ':id_usuario_creador' => $_SESSION['usuario']['id_usuario']
    ]);

    $_SESSION['exito'] = "¡Cita agendada correctamente!";
    header("Location: /consultorio_dental/paciente/citas.php");
    exit();
} else {
    $_SESSION['error'] = "No se pudo identificar al paciente.";
    header("Location: /consultorio_dental/paciente/pre_cita.php");
    exit();
}
  
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
     // Validaciones
    }    // Inserción en la tabla citas
    } // ← Esta es una llave que usualmente se olvida

}catch (PDOException $e) {
    $error = "Error al obtener información: " . $e->getMessage();
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/consultorio_dental/includes/header.php';
?>

<div class="container">
    <h2 class="mb-4">Información para Agendar Cita</h2>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php elseif (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <div class="row">
        <!-- Sección de Servicios - Versión Mejorada -->
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h4>Servicios Disponibles</h4>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        <?php foreach ($servicios as $servicio): 
                            $costo_servicio = array_filter($costos, function($c) use ($servicio) {
                                return $c['id_servicio'] == $servicio['id_servicio'];
                            });
                            $disponible = !empty($costo_servicio);
                        ?>
                            <div class="list-group-item <?= !$disponible ? 'list-group-item-secondary' : '' ?>">
                                <h5>
                                    <?php echo htmlspecialchars($servicio['nombre_servicio']); ?>
                                    <?php if (!$disponible): ?>
                                        <small class="text-muted float-right">(Próximamente)</small>
                                    <?php endif; ?>
                                </h5>
                                <p><?php echo htmlspecialchars($servicio['descripcion']); ?></p>
                                <p><strong>Duración:</strong> <?php echo $servicio['duracion_estimada']; ?> minutos</p>
                                <p><strong>Costo:</strong> 
                <?php if ($disponible): ?>
                    $<?php echo number_format(reset($costo_servicio)['costo'], 2); ?>
                <?php else: ?>
                    <span class="text-danger">Consultar precio con recepción</span>
                <?php endif; ?>
            </p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Sección de Dentistas (se mantiene igual) -->
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h4>Nuestros Dentistas</h4>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        <?php foreach ($dentistas as $dentista): ?>
                            <div class="list-group-item">
                                <h5>Dr. <?php echo htmlspecialchars($dentista['nombre'] . ' ' . $dentista['apellido']); ?></h5>
                                <p><strong>Especialidad:</strong> <?php echo htmlspecialchars($dentista['especialidad']); ?></p>
                                <p><strong>N° Colegiado:</strong> <?php echo htmlspecialchars($dentista['numero_colegiado']); ?></p>
                                <p><strong>Horario:</strong> <?php echo htmlspecialchars($dentista['horario_disponible']); ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Formulario Mejorado -->
    <div class="card">
        <div class="card-header bg-success text-white">
            <h4>Seleccionar Servicio y Dentista</h4>
        </div>
        <div class="card-body">
            <form method="post" action="/consultorio_dental/paciente/nueva_cita.php" id="citaForm">
                <div class="form-group">
                    <label for="servicio">Servicio Disponible:</label>
                    <select class="form-control" id="servicio" name="servicio" required>
                        <option value="">Seleccione un servicio</option>
                        <?php 
                        // Agrupar servicios disponibles
                        $hay_servicios_disponibles = false;
                        foreach ($servicios as $servicio): 
                            $costo_servicio = array_filter($costos, function($c) use ($servicio) {
                                return $c['id_servicio'] == $servicio['id_servicio'];
                            });
                            if (!empty($costo_servicio)): 
                                $hay_servicios_disponibles = true;
                        ?>
                            <option value="<?php echo $servicio['id_servicio']; ?>"
                                <?php echo (isset($_POST['servicio']) && $_POST['servicio'] == $servicio['id_servicio']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($servicio['nombre_servicio']); ?>
                                ($<?php echo number_format(reset($costo_servicio)['costo'], 2); ?>)
                            </option>
                        <?php 
                            endif;
                        endforeach; 
                        
                        if (!$hay_servicios_disponibles): ?>
                            <option value="" disabled>No hay servicios disponibles actualmente</option>
                        <?php endif; ?>
                    </select>
                </div>
                
                <div class="form-group mt-3">
                    <label for="dentista">Dentista:</label>
                    <select class="form-control" id="dentista" name="dentista" required>
                        <option value="">Seleccione un dentista</option>
                        <?php foreach ($dentistas as $dentista): ?>
                            <option value="<?php echo $dentista['id_dentista']; ?>"
                                <?php echo (isset($_POST['dentista']) && $_POST['dentista'] == $dentista['id_dentista']) ? 'selected' : ''; ?>>
                                Dr. <?php echo htmlspecialchars($dentista['nombre'] . ' ' . $dentista['apellido']); ?> - <?php echo htmlspecialchars($dentista['especialidad']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary mt-3" <?= !$hay_servicios_disponibles ? 'disabled' : '' ?>>Continuar con el Agendamiento</button>
                
                <?php if (!$hay_servicios_disponibles): ?>
                    <div class="alert alert-warning mt-3">
                        Actualmente no hay servicios disponibles para agendar. Por favor contacte a recepción.
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>

<script>
// Validación adicional del formulario
document.getElementById('citaForm').addEventListener('submit', function(e) {
    const servicio = document.getElementById('servicio').value;
    const dentista = document.getElementById('dentista').value;
    
    if (!servicio || !dentista) {
        e.preventDefault();
        alert('Por favor seleccione tanto un servicio como un dentista');
    }
}
</script>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/consultorio_dental/includes/footer.php'; ?>