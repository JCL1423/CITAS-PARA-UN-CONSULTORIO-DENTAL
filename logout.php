<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/consultorio_dental/includes/config.php';

// Añade esta línea: inicia la sesión
session_start();

// Ahora sí, destruye la sesión
session_destroy();

// Redirige
header("Location: " . BASE_URL . "login.php");
exit();
?>

