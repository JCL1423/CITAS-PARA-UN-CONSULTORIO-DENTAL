<?php
session_start();
// Verificar si el usuario es la recepcionista
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['nombre_rol'] !== 'Recepcionista') {
    header("Location: login.php");
    exit();
}
// Conexión directa a la base de datos
$servername = "localhost";
$username = "root";
$password = ""; // Cambia si tienes contraseña
$dbname = "consultorio_dental";
try {
    $conexion = new PDO(
        "mysql:host=$servername;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
// Consulta SQL (incluye el campo 'pagado')
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
        ORDER BY c.fecha_hora DESC";
try {
    $stmt = $conexion->prepare($sql);
    $stmt->execute();
    $resultado = $stmt->fetchAll();
} catch (PDOException $e) {
    $resultado = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de la Recepcionista</title>
    <style>
        /* Estilos generales */
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            background-color: #f4f4f4;
            color: #333;
        }
        header {
            background-color: #007bff;
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        header h1 {
            margin: 0;
            font-size: 1.5em;
        }
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .user-info a {
            text-decoration: none;
            color: white;
            background-color: #dc3545;
            padding: 8px 16px;
            border-radius: 5px;
            font-weight: bold;
        }
        main {
            padding: 20px;
            background-color: #fff;
        }
        .search-box {
            margin-bottom: 20px;
        }
        .search-box input {
            padding: 8px;
            width: 300px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .search-box button {
            padding: 8px 15px;
            margin-left: 5px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .citas-lista {
            margin-top: 20px;
        }
        .cita-card {
            background-color: #e9ecef;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 15px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .cita-card h4 {
            margin: 0 0 10px;
        }
        .cita-info {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }
        .cita-info div {
            flex: 1 1 200px;
        }
        .cita-info .label {
            font-weight: bold;
            color: #007bff;
        }
        .actions button, .actions select {
            margin-right: 10px;
            padding: 6px 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .btn-print {
            background-color: #28a745;
            color: white;
        }
        /* Botón de pago */
        .btn-pagar {
            background-color: #6c757d;
            color: white;
            transition: all 0.3s ease;
        }
        .btn-pagar.pago-realizado {
            background-color: #28a745 !important;
            color: white;
            font-weight: bold;
            cursor: not-allowed;
        }
        /* Select de estado */
        .estado-select {
            background-color: #6f42c1;
            color: white;
            min-width: 140px;
        }
        .estado-select option {
            color: black;
        }
        /* === DISEÑO DE IMPRESIÓN PERSONALIZADO === */
        #printSection {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 80%;
            max-width: 600px;
            background: linear-gradient(to bottom, #ffffff, #f8f9fa);
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 123, 255, 0.2);
            z-index: 9999;
            font-family: 'Arial', sans-serif;
            border: 1px solid #007bff;
        }
        #printSection header {
            text-align: center;
            margin-bottom: 20px;
        }
        #printSection img {
            width: 80px;
            height: auto;
            margin-bottom: 10px;
        }
        #printSection h2 {
            color: #007bff;
            font-size: 24px;
            margin: 0;
            font-weight: bold;
        }
        #printSection p {
            color: #555;
            margin: 5px 0;
        }
        #printSection hr {
            border: 1px solid #007bff;
            margin: 20px 0;
            opacity: 0.7;
        }
        #printContent {
            font-size: 16px;
            line-height: 1.8;
            color: #333;
        }
        #printContent .label {
            display: inline-block;
            width: 120px;
            font-weight: bold;
            color: #007bff;
        }
        #printSection footer {
            text-align: center;
            margin-top: 25px;
            font-size: 12px;
            color: #6c757d;
            border-top: 1px dashed #ccc;
            padding-top: 10px;
        }
        /* Estilo para impresión */
        @media print {
            @page {
                margin: 20mm;
            }
            body * {
                visibility: hidden;
            }
            #printSection, #printSection * {
                visibility: visible;
            }
            #printSection {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                transform: none;
                box-shadow: none;
                border: none;
                background: white;
            }
            .actions, .search-box, header, .cita-card {
                display: none !important;
            }
        }
    </style>
</head>
<body>
<!-- Encabezado -->
<header>
    <h1>Consultorio Dental</h1>
    <div class="user-info">
        <p>Bienvenida, Recepcionista</p>
        <a href="logout.php">Cerrar Sesión</a>
    </div>
</header>
<!-- Contenido principal -->
<main>
    <h2>Gestión de Citas por la Recepcionista</h2>
    <!-- Buscador de citas por nombre de paciente -->
    <div class="search-box">
        <input type="text" id="searchInput" placeholder="Buscar cita por nombre del paciente..." oninput="buscarCitas()">
    </div>
    <!-- Lista de citas -->
    <div class="citas-lista">
        <?php if (!empty($resultado)): ?>
            <?php foreach ($resultado as $cita): ?>
                <div class="cita-card">
                    <h4>Cita #<?= htmlspecialchars($cita['id_cita']) ?></h4>
                    <div class="cita-info">
                        <div>
                            <div class="label">Paciente</div>
                            <div><?= htmlspecialchars($cita['paciente']) ?></div>
                        </div>
                        <div>
                            <div class="label">Fecha</div>
                            <div><?= htmlspecialchars($cita['fecha']) ?></div>
                        </div>
                        <div>
                            <div class="label">Hora</div>
                            <div><?= htmlspecialchars($cita['hora']) ?></div>
                        </div>
                        <div>
                            <div class="label">Servicio</div>
                            <div><?= htmlspecialchars($cita['servicio']) ?></div>
                        </div>
                        <div>
                            <div class="label">Dentista</div>
                            <div><?= htmlspecialchars($cita['dentista']) ?></div>
                        </div>
                        <div>
                            <div class="label">Estado</div>
                            <div>
                                <?= htmlspecialchars($cita['estado']) ?>
                                <?php if ($cita['pagado'] === 'Sí'): ?>
                                    | 💰 Pagada
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="actions">
                        <!-- Select para cambiar estado -->
                        <?php if (in_array($cita['estado'], ['Cancelada', 'Completada', 'No asistió'])): ?>
                            <select class="estado-select" disabled>
                                <option><?= htmlspecialchars($cita['estado']) ?></option>
                            </select>
                        <?php else: ?>
                            <select class="estado-select" onchange="cambiarEstado(<?= (int)$cita['id_cita'] ?>, this.value)">
                                <option value="">Cambiar estado</option>
                                <option value="Cancelada" <?= $cita['estado'] === 'Cancelada' ? 'selected' : '' ?>>❌ Cancelada</option>
                                <option value="Completada" <?= $cita['estado'] === 'Completada' ? 'selected' : '' ?>>✅ Completada</option>
                                <option value="No asistió" <?= $cita['estado'] === 'No asistió' ? 'selected' : '' ?>>👤 No asistió</option>
                            </select>
                        <?php endif; ?>
                        <!-- Botón de impresión -->
                        <button class="btn-print" onclick="imprimirCita(
                            <?= (int)$cita['id_cita'] ?>,
                            '<?= addslashes($cita['paciente']) ?>',
                            '<?= addslashes($cita['fecha']) ?>',
                            '<?= addslashes($cita['hora']) ?>',
                            '<?= addslashes($cita['servicio']) ?>',
                            '<?= addslashes($cita['dentista']) ?>',
                            '<?= addslashes($cita['estado']) ?>'
                        )">
                            Imprimir
                        </button>
                        <!-- Botón de pago (solo si no está en estado final) -->
                        <?php if (in_array($cita['estado'], ['Cancelada', 'No asistió', 'Completada'])): ?>
                            <!-- No mostrar botón de pago si ya está cerrada -->
                        <?php elseif ($cita['pagado'] === 'Sí'): ?>
                            <button class="btn-pagar pago-realizado" disabled>💰 Cita Pagada</button>
                        <?php else: ?>
                            <button class="btn-pagar" id="btn-pago-<?= (int)$cita['id_cita'] ?>" onclick="marcarComoPagada(<?= (int)$cita['id_cita'] ?>)">
                                Marcar como Pagada
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No hay citas registradas aún.</p>
        <?php endif; ?>
    </div>
</main>
<!-- SECCIÓN OCULTA PARA IMPRESIÓN -->
<div id="printSection">
    <header>
        <img src="logo_consultorio.png" alt="Logo Consultorio Dental" onerror="this.style.display='none'">
        <h2>Consultorio Dental</h2>
        <p>Sistema de Gestión de Citas Dentales</p>
    </header>
    <hr>
    <div id="printContent"></div>
    <footer>
        <p><small>Impreso el: <?= date('d/m/Y H:i') ?></small></p>
        <p><small>Dirección: Calle Principal #123, Ciudad | Tel: (555) 123-4567</small></p>
    </footer>
</div>
<script>
    // Función para marcar como pagada
    function marcarComoPagada(id) {
        if (confirm("¿Deseas marcar esta cita como pagada?")) {
            const boton = document.getElementById(`btn-pago-${id}`);
            boton.textContent = "💰 Cita Pagada";
            boton.classList.add("pago-realizado");
            boton.disabled = true;
            // Guardar en la base de datos
            fetch('marcar_pago.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'id_cita=' + id
            }).catch(err => {
                alert("Error de conexión. Recarga para ver el estado real.");
            });
        }
    }
    // Función para cambiar el estado (Cancelada, Completada, No asistió)
    function cambiarEstado(id, nuevoEstado) {
        if (!nuevoEstado) return;
        const select = document.querySelector(`.cita-card:has(button[id="btn-pago-${id}"]) .estado-select`);
        fetch('actualizar_estado.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'id_cita=' + id + '&estado=' + encodeURIComponent(nuevoEstado)
        })
        .then(response => response.text())
        .then(data => {
            if (data.includes("ok")) {
                // Deshabilitar select si es estado final
                if (['Cancelada', 'Completada', 'No asistió'].includes(nuevoEstado)) {
                    select.disabled = true;
                }
                // Actualizar visualmente el estado
                const estadoDiv = select.closest('.cita-card').querySelector('.cita-info div:last-child div');
                let texto = nuevoEstado;
                if (document.getElementById(`btn-pago-${id}`)?.disabled) {
                    texto += ' | 💰 Pagada';
                }
                estadoDiv.textContent = texto;
            } else {
                alert("Error al guardar el estado.");
                select.value = ""; // revertir
            }
        })
        .catch(err => {
            alert("Error de conexión. Estado no guardado.");
            console.error(err);
            select.value = ""; // revertir
        });
    }
    // Función para imprimir cita
    function imprimirCita(id) {
        // Consultar la base de datos para obtener el estado completo
        fetch('obtener_estado_cita.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'id_cita=' + id
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const { paciente, fecha, hora, servicio, dentista, estado, pagado } = data.cita;
                let estadoCompleto = estado;
                if (pagado === 'Sí') {
                    estadoCompleto += ' | 💰 Pagada';
                }
                const content = `
                    <p><span class="label">Cita #:</span> ${id}</p>
                    <p><span class="label">Paciente:</span> ${paciente}</p>
                    <p><span class="label">Fecha:</span> ${fecha}</p>
                    <p><span class="label">Hora:</span> ${hora}</p>
                    <p><span class="label">Servicio:</span> ${servicio}</p>
                    <p><span class="label">Dentista:</span> ${dentista}</p>
                    <p><span class="label">Estado:</span> ${estadoCompleto}</p>
                `;
                document.getElementById("printContent").innerHTML = content;
                document.getElementById("printSection").style.display = "block";
                setTimeout(() => {
                    window.print();
                    document.getElementById("printSection").style.display = "none";
                }, 500);
            } else {
                alert("Error al obtener el estado de la cita.");
            }
        })
        .catch(err => {
            console.error("Error de conexión:", err);
            alert("Error de conexión. No se pudo obtener el estado de la cita.");
        });
    }
    // Buscador de citas
    function buscarCitas() {
        const nombre = document.getElementById('searchInput').value.trim().toLowerCase();
        const citas = document.querySelectorAll('.cita-card');
        citas.forEach(cita => {
            const paciente = cita.querySelector('.cita-info div:nth-child(1) div:last-child').textContent.toLowerCase();
            cita.style.display = paciente.includes(nombre) ? 'block' : 'none';
        });
    }
    // Cerrar ventana de impresión después de imprimir
    window.addEventListener('afterprint', () => {
        document.getElementById("printSection").style.display = "none";
    });
</script>
</body>
</html>