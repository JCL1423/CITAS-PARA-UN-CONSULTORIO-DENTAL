<?php
// includes/header.php
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sonrie con salud</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Estilos personalizados -->
    <link href="../assets/css/styles.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="../index.php">Consultorio Dental</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php if (isset($_SESSION['usuario'])): ?>
                        <li class="nav-item">
                            <span class="nav-link">Bienvenido, <?php echo $_SESSION['usuario']['nombre']; ?></span>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../logout.php">Cerrar Sesión</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
            <a class="nav-link" href="login.php">
                <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
            </a>
        </li>
                       <li class="nav-item">
            <a class="nav-link" href="registro.php">
                <i class="fas fa-user-plus"></i> Registrarse
            </a>
        </li>
    <?php endif; ?>
</ul>
               
            </div>
        </div>
    </nav>
    <div class="container mt-4">


