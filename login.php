<?php
session_start();

require_once $_SERVER['DOCUMENT_ROOT'] . '/consultorio_dental/includes/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = htmlspecialchars(trim($_POST['username']));
    $password = trim($_POST['password']);

    // Credenciales fijas para la recepcionista
    $usuario_recepcionista = 'receptionista';
    $contrasena_recepcionista = '123456';

    // Verificar si es la recepcionista
    if ($username === $usuario_recepcionista && $password === $contrasena_recepcionista) {
        // Iniciar sesión como recepcionista
        $_SESSION['usuario'] = [
            'id_usuario' => 0,
            'nombre' => 'Recepcionista',
            'username' => $usuario_recepcionista,
            'nombre_rol' => 'Recepcionista',
            'activo' => 1
        ];

        // ✅ Redirigir directamente al panel de la recepcionista
        header("Location: panel_recepcionista.php");
        exit();
    }

    // Flujo normal: autenticación contra la base de datos
    try {
        $stmt = $conn->prepare("SELECT u.*, r.nombre_rol FROM usuarios u 
                              JOIN roles r ON u.id_rol = r.id_rol 
                              WHERE username = :username AND activo = 1");
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        if ($stmt->rowCount() == 1) {
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (password_verify($password, $usuario['password_hash'])) {
                // Autenticación exitosa
                $_SESSION['usuario'] = $usuario;
                
                // Registrar acceso
                $stmt = $conn->prepare("INSERT INTO historial_accesos 
                                        (id_usuario, fecha_hora_login, direccion_ip, dispositivo) 
                                        VALUES (:id_usuario, NOW(), :ip, :dispositivo)");
                $stmt->bindParam(':id_usuario', $usuario['id_usuario']);
                $stmt->bindParam(':ip', $_SERVER['REMOTE_ADDR']);
                $stmt->bindParam(':dispositivo', $_SERVER['HTTP_USER_AGENT']);
                $stmt->execute();
                
                // Redirigir según rol
                if ($usuario['nombre_rol'] === 'Recepcionista') {
                    header("Location: panel_recepcionista.php");
                } else {
                    header("Location: index.php");
                }
                exit();
            }
        }
        
        // Si llega aquí, la autenticación falló
        $error = "Usuario o contraseña incorrectos";
    } catch(PDOException $e) {
        $error = "Error al iniciar sesión: " . $e->getMessage();
    }
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/consultorio_dental/includes/header.php';
?>
<!-- TU FORMULARIO DE LOGIN -->
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="text-center">Iniciar Sesión</h3>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form method="post">
                        <div class="form-group">
                            <label for="username">Usuario</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="form-group">
                            <label for="password">Contraseña</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">Ingresar</button>
                    </form>
                </div>
                <div class="card-footer text-center">
                    <a href="registro.php">¿No tienes una cuenta? Regístrate</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/consultorio_dental/includes/footer.php'; ?>