<?php
// 1. Incluir configuraciones con verificación de sesión
require_once $_SERVER['DOCUMENT_ROOT'] . '/consultorio_dental/includes/config.php';
// Verificar sesión activa
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_lifetime' => 86400,
        'cookie_path' => '/consultorio_dental/',
        'cookie_secure' => isset($_SERVER['HTTPS']),
        'cookie_httponly' => true
    ]);
}
// Verificar que el usuario esté logueado y sea paciente
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['id_rol'] != 3) {
    header("Location: /consultorio_dental/login.php");
    exit();
}
// 2. Verificar parámetros POST
if (empty($_POST['servicio']) || empty($_POST['dentista'])) {
    $_SESSION['error'] = "Debes seleccionar un servicio y un dentista";
    header("Location: /consultorio_dental/paciente/pre_cita.php");
    exit();
}
// 3. Obtener datos con manejo de errores mejorado
try {
    $id_servicio = (int)$_POST['servicio'];
    $id_dentista = (int)$_POST['dentista'];
    // Validar IDs
    if ($id_servicio <= 0 || $id_dentista <= 0) {
        throw new Exception("IDs de servicio o dentista inválidos");
    }
    // Consulta para el servicio
    $stmtServicio = $conn->prepare("SELECT * FROM servicios WHERE id_servicio = ? AND activo = 1");
    $stmtServicio->execute([$id_servicio]);
    $servicio = $stmtServicio->fetch(PDO::FETCH_ASSOC);
    if (!$servicio) {
        throw new Exception("Servicio no encontrado o inactivo");
    }
    // Consulta para el dentista
    $stmtDentista = $conn->prepare("SELECT d.*, u.nombre, u.apellido 
                                  FROM dentistas d
                                  JOIN usuarios u ON d.id_usuario = u.id_usuario
                                  WHERE d.id_dentista = ? AND d.activo = 1");
    $stmtDentista->execute([$id_dentista]);
    $dentista = $stmtDentista->fetch(PDO::FETCH_ASSOC);
    if (!$dentista) {
        throw new Exception("Dentista no encontrado o inactivo");
    }
    // Consulta para el costo
   $stmtCosto = $conn->prepare("SELECT costo FROM costos 
                                WHERE id_servicio = ? AND activo = 1 
                                AND (fecha_fin IS NULL OR fecha_fin >= CURDATE())
                                ORDER BY fecha_inicio DESC LIMIT 1");
    $stmtCosto->execute([$id_servicio]);
    $costo = $stmtCosto->fetch(PDO::FETCH_ASSOC);
    if (!$costo) {
        $costo = ['costo' => 0]; // O puedes usar un valor por defecto como 100.00
        // Buscar el último costo registrado (aunque esté inactivo o caducado)
        $stmtUltimoCosto = $conn->prepare("SELECT costo FROM costos 
                                         WHERE id_servicio = ?
                                         ORDER BY fecha_inicio DESC LIMIT 1");
        $stmtUltimoCosto->execute([$id_servicio]);
        $costo = $stmtUltimoCosto->fetch(PDO::FETCH_ASSOC);
        // Si aún no hay costo, usar valor predeterminado
        if (!$costo) {
            $costo = ['costo' => 0];
            error_log("Advertencia: Servicio ID $id_servicio sin costo, usando valor predeterminado");
        }
        // Opcional: Mostrar advertencia pero permitir continuar
        $_SESSION['advertencia'] = "El servicio no tiene un costo activo actualmente. Se usará el valor de $" . number_format($costo['costo'], 2);
    }
} catch(PDOException $e) {
    error_log("Error en nueva_cita.php: " . $e->getMessage());
    $_SESSION['error'] = "Error al obtener información de la base de datos";
    header("Location: /consultorio_dental/paciente/pre_cita.php");
    exit();
} catch(Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header("Location: /consultorio_dental/paciente/pre_cita.php");
    exit();
}
// 4. Incluir header después de toda la lógica PHP
require_once $_SERVER['DOCUMENT_ROOT'] . '/consultorio_dental/includes/header.php';
?>
<!-- Formulario de agendamiento -->
<div class="container">
    <h2 class="mb-4">Agendar Nueva Cita</h2>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h4>Confirmación de Cita</h4>
        </div>
        <div class="card-body">
            <form action="/consultorio_dental/paciente/registrar_cita.php" method="post">
                <input type="hidden" name="servicio" value="<?php echo $id_servicio; ?>">
                <input type="hidden" name="dentista" value="<?php echo $id_dentista; ?>">
                <input type="hidden" name="costo" value="<?php echo $costo['costo']; ?>">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <h5>Servicio Seleccionado:</h5>
                        <p><strong><?php echo htmlspecialchars($servicio['nombre_servicio']); ?></strong></p>
                        <p><?php echo htmlspecialchars($servicio['descripcion']); ?></p>
                        <p><strong>Costo:</strong> $<?php echo number_format($costo['costo'], 2); ?></p>
                    </div>
                    <div class="col-md-6">
                        <h5>Dentista:</h5>
                        <p><strong>Dr. <?php echo htmlspecialchars($dentista['nombre'] . ' ' . $dentista['apellido']); ?></strong></p>
                        <p><strong>Especialidad:</strong> <?php echo htmlspecialchars($dentista['especialidad']); ?></p>
                    </div>
                </div>
                <div class="form-group mb-3">
                    <label for="fecha">Fecha de la cita:</label>
                    <input type="date" class="form-control" id="fecha" name="fecha" min="<?php echo date('Y-m-d'); ?>" required>
                </div>
               <div class="form-group mb-3">
    <label for="hora">Hora de la cita:</label>
    <select class="form-control" id="hora" name="hora" required>
        <option value="">Seleccione una hora</option>
        <?php
        // Normalizar nombre del servicio para comparación segura
        $nombre_servicio = trim(strtolower($servicio['nombre_servicio']));

      if ($nombre_servicio === 'extraccion de piezas dentales') {
    // Configuración específica para Extracción de Piezas Dentales
    $inicio = new DateTime('10:00'); // 10:00 AM
    $fin_jornada = '19:00'; // 7:00 PM
    $duracion = 45; // minutos
    $tiempo_actual = clone $inicio;
    for ($i = 1; $i <= 8; $i++) {
        $hora_inicio = $tiempo_actual->format('H:i');
        $fin_consulta = clone $tiempo_actual;
        $fin_consulta->modify("+$duracion minutes");
        // Mostrar si la consulta termina antes o a las 7:00 PM
        if ($fin_consulta->format('H:i') <= $fin_jornada) {
            echo "<option value='$hora_inicio'>$hora_inicio</option>";
        }
        // Aplicar descanso según la consulta
        if ($i == 2) {
            $tiempo_actual = clone $fin_consulta;
            $tiempo_actual->modify("+40 minutes"); // descanso largo después de 2da
        } else {
            $tiempo_actual = clone $fin_consulta;
            $tiempo_actual->modify("+20 minutes"); // descanso normal
        }
    }

        } elseif ($nombre_servicio === 'ortodoncia') {
            // Mostrar EXACTAMENTE las horas que pediste
            echo "<option value='08:00'>08:00</option>";
            echo "<option value='08:57'>08:57</option>";
            echo "<option value='10:37'>10:37</option>";
            echo "<option value='11:44'>11:44</option>";
            echo "<option value='12:51'>12:51</option>";
        } elseif ($nombre_servicio === 'resina dental') {
            // Configuración específica para Resina Dental
            $inicio = new DateTime('14:00'); // 2:00 PM
            $fin_jornada = '20:00'; // 8:00 PM
            $duracion = 45; // minutos
            $tiempo_actual = clone $inicio;

            for ($i = 1; $i <= 6; $i++) {
                $hora_inicio = $tiempo_actual->format('H:i');
                $fin_consulta = clone $tiempo_actual;
                $fin_consulta->modify("+$duracion minutes");

                if ($fin_consulta->format('H:i') <= $fin_jornada) {
                    echo "<option value='$hora_inicio'>$hora_inicio</option>";
                }

                if ($i == 1) {
                    $tiempo_actual = clone $fin_consulta;
                    $tiempo_actual->modify("+15 minutes");
                } elseif ($i == 2) {
                    $tiempo_actual = clone $fin_consulta;
                    $tiempo_actual->modify("+30 minutes");
                } elseif ($i == 6) {
                    $tiempo_actual = clone $fin_consulta;
                    $tiempo_actual->modify("+15 minutes");
                } else {
                    $tiempo_actual = clone $fin_consulta;
                    $tiempo_actual->modify("+10 minutes");
                }
            }
        } elseif ($nombre_servicio === 'limpieza dental') {
            // Configuración específica para Limpieza Dental
            $inicio = new DateTime('08:00');
            $fin_jornada = '13:00';
            $duracion = 30;
            $tiempo_actual = clone $inicio;

            for ($i = 1; $i <= 7; $i++) {
                $hora_inicio = $tiempo_actual->format('H:i');
                $fin_consulta = clone $tiempo_actual;
                $fin_consulta->modify("+$duracion minutes");

                if ($fin_consulta->format('H:i') <= $fin_jornada) {
                    echo "<option value='$hora_inicio'>$hora_inicio</option>";
                }

                if ($i == 4) {
                    $tiempo_actual = clone $fin_consulta;
                    $tiempo_actual->modify("+30 minutes");
                } else {
                    $tiempo_actual = clone $fin_consulta;
                    $tiempo_actual->modify("+10 minutes");
                }
            }
        }elseif ($nombre_servicio === 'blanqueamiento dental') {
    // Configuración específica para Blanqueamiento Dental
    $inicio = new DateTime('09:00'); // 9:00 AM
    $fin_jornada = '14:00'; // 2:00 PM
    $duracion = 60; // minutos
    $tiempo_actual = clone $inicio;
    $contador_consultas = 1;
    while ($tiempo_actual->format('H:i') <= $fin_jornada) {
        $hora_inicio = $tiempo_actual->format('H:i');
        $fin_consulta = clone $tiempo_actual;
        $fin_consulta->modify("+$duracion minutes");

        // Verificar que la consulta termine antes o a las 14:00
        if ($fin_consulta->format('H:i') > $fin_jornada) {
            break; // No mostrar si se pasa del horario
        }

        echo "<option value='$hora_inicio'>$hora_inicio</option>";

        // Aplicar descanso según la consulta
        if ($contador_consultas == 1) {
            $tiempo_actual = clone $fin_consulta;
            $tiempo_actual->modify("+30 minutes"); // descanso largo después de 1ra
        } else {
            $tiempo_actual = clone $fin_consulta;
            $tiempo_actual->modify("+20 minutes"); // descanso normal
        }

        $contador_consultas++;
    }
        } else {
            // Para cualquier otro servicio (ej. Coronas Dentales)
            $inicio = new DateTime('09:00');
            $tiempo_actual = clone $inicio;
            $duracion_minutos = 90;
            $descanso_10min = 10;
            $descanso_30min = 30;

            for ($i = 1; $i <= 5; $i++) {
                $hora_inicio = $tiempo_actual->format('H:i');
                $fin_consulta = clone $tiempo_actual;
                $fin_consulta->modify("+$duracion_minutos minutes");

                if ($fin_consulta->format('H:i') <= '18:00') {
                    echo "<option value='$hora_inicio'>$hora_inicio</option>";
                }

                if ($i == 1) {
                    $tiempo_actual = clone $fin_consulta;
                    $tiempo_actual->modify("+$descanso_30min minutes");
                } elseif ($i == 4) {
                    $tiempo_actual = clone $fin_consulta;
                    $tiempo_actual->modify("+$descanso_30min minutes");
                } else {
                    $tiempo_actual = clone $fin_consulta;
                    $tiempo_actual->modify("+$descanso_10min minutes");
                }
            }
        }
        ?>
    </select>

</div>
                <div class="form-group mb-3">
                    <label for="notas">Notas adicionales:</label>
                    <textarea class="form-control" id="notas" name="notas" rows="3"></textarea>
                </div>
                <button type="submit" class="btn btn-success btn-lg">Confirmar Cita</button>
            </form>
        </div>
    </div>
</div>
<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/consultorio_dental/includes/footer.php'; ?>