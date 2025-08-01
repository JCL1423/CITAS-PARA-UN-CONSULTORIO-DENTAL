<?php
define('BASE_URL', 'http://localhost/consultorio_dental/');

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Datos de conexión a la base de datos
define('DB_HOST', 'localhost');
define('DB_USER', 'root');        // Usuario por defecto en XAMPP
define('DB_PASS', '');            // Contraseña vacía por defecto
define('DB_NAME', 'consultorio_dental');

try {
    // Conexión PDO
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
define('ROL_RECEPCIONISTA', 2);
    // Activar manejo de errores con excepciones
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión PDO: " . $e->getMessage());
}

// Función opcional para verificar el rol del usuario
function checkRole($requiredRole) {
    if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['id_rol'] != $requiredRole) {
        header("Location: /consultorio_dental/login.php");
        exit();
    }
}