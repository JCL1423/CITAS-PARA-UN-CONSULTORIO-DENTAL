<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/consultorio_dental/includes/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    
    try {
        $stmt = $conn->prepare("INSERT INTO usuarios (nombre, email, password, id_rol) VALUES (?, ?, ?, ?)");
        $stmt->execute([$nombre, $email, $password, ROL_RECEPCIONISTA]);
        
        $_SESSION['exito'] = "Recepcionista registrada correctamente";
        header("Location: login_recepcionista.php");
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error al registrar: " . $e->getMessage();
    }
}
?>

<form method="post">
    <input type="text" name="nombre" placeholder="Nombre completo" required>
    <input type="email" name="email" placeholder="Correo electrónico" required>
    <input type="password" name="password" placeholder="Contraseña" required>
    <button type="submit">Registrar Recepcionista</button>
</form>