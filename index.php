<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/consultorio_dental/includes/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/consultorio_dental/includes/header.php';

// Redirigir según el rol del usuario
if (isset($_SESSION['usuario'])) {
    switch ($_SESSION['usuario']['id_rol']) {
        case 1: // Administrador
            header("Location: admin/dashboard.php");
            break;
        case 2: // Dentista
            header("Location: dentista/agenda.php");
            break;
        case 3: // Paciente
            header("Location: paciente/citas.php");
            break;
        default:
            // Rol no reconocido
            header("Location: login.php");
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultorio Dental - Sonríe con Salud</title>
    <!-- Estilos CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f8f9fa;
        }
        .hero-image {
            background-image: url('img/interior_consultorio.jpg'); /* Imagen del interior del consultorio */
            background-size: cover;
            background-position: center;
            height: 50vh;
            display: flex;
            justify-content: center;
            align-items: center;
            color: white; /* Cambio de color a blanco */
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5); /* Sombra para mejorar legibilidad */
        }
        .btn-primary {
            background-color: #0d6efd !important; /* Azul principal */
            border-color: #0d6efd !important;
        }
        .btn-secondary {
            background-color: #1379dfff !important; /* Gris oscuro */
            border-color: #6c757d !important;
        }
        .mt-5 {
            margin-top: 5rem !important;
        }
    </style>
</head>
<body>

<!-- Sección Hero -->
<div class="hero-image">
    <div class="text-center">
        <h1 class="display-4">SONRÍE CON SALUD</h1>
        <p class="lead">Sistema de gestión de citas dentales</p>
        <div class="mt-4">
            <a href="login.php" class="btn btn-primary btn-lg mr-3">Iniciar Sesión</a>
            <a href="registro.php" class="btn btn-secondary btn-lg">Registrarse</a>
        </div>
    </div>
</div>

<!-- Sección Información -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="row">
            <div class="col-md-6">
                <img src="img/paciente_con_dentista.jpg" alt="Paciente con dentista" class="img-fluid rounded shadow">
            </div>
            <div class="col-md-6">
                <h2>Nuestro Compromiso</h2>
                <p>Somos un equipo dedicado a brindarte atención personalizada y cuidados dentales de calidad. Nuestro objetivo es promover tu salud bucal y ayudarte a mantener una sonrisa radiante.</p>
                <p>Ofrecemos servicios como:</p>
                <ul>
                    <li>Blanqueamiento dental</li>
                    <li>Extracción de pieza dental</li>
                    <li>Consulta de ortodoncia</li>
                    <li>Limpieza dental</li>
                    <li>Resina dental</li>
                    <li>Corona dental</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- Pie de Página -->
<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/consultorio_dental/includes/footer.php'; ?>

<!-- Scripts Bootstrap -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

