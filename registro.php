<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/consultorio_dental/includes/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = trim($_POST['nombre']);
    $apellido = trim($_POST['apellido']);
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $telefono = trim($_POST['telefono']);
    
    try {
        // Verificar si el usuario ya existe
        $stmt = $conn->prepare("SELECT id_usuario FROM usuarios WHERE username = :username OR email = :email");
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $error = "El nombre de usuario o correo electrónico ya está en uso";
        } else {
            // Hash de la contraseña
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            // Insertar nuevo usuario (rol 3 = Paciente por defecto)
            $stmt = $conn->prepare("INSERT INTO usuarios 
                                  (id_rol, username, password_hash, email, nombre, apellido, telefono, fecha_registro) 
                                  VALUES (3, :username, :password_hash, :email, :nombre, :apellido, :telefono, NOW())");
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':password_hash', $password_hash);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':nombre', $nombre);
            $stmt->bindParam(':apellido', $apellido);
            $stmt->bindParam(':telefono', $telefono);
            $stmt->execute();
            
            // Obtener ID del nuevo usuario
            $id_usuario = $conn->lastInsertId();
            
            // Crear registro de paciente asociado
            $stmt = $conn->prepare("INSERT INTO pacientes 
                                   (nombre, apellido, id_usuario_registro, fecha_registro) 
                                   VALUES (:nombre, :apellido, :id_usuario, NOW())");
            $stmt->bindParam(':nombre', $nombre);
            $stmt->bindParam(':apellido', $apellido);
            $stmt->bindParam(':id_usuario', $id_usuario);
            $stmt->execute();
            
            // Redirigir a login con mensaje de éxito
            $_SESSION['mensaje'] = "Registro exitoso. Por favor inicia sesión.";
            header("Location: login.php");
            exit();
        }
    } catch(PDOException $e) {
        $error = "Error al registrar usuario: " . $e->getMessage();
    }
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/consultorio_dental/includes/header.php';
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="text-center">Registro de Paciente</h3>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form method="post">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="nombre">Nombre</label>
                                    <input type="text" class="form-control" id="nombre" name="nombre" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="apellido">Apellido</label>
                                    <input type="text" class="form-control" id="apellido" name="apellido" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="email">Correo Electrónico</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="username">Nombre de Usuario</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="password">Contraseña</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="telefono">Teléfono</label>
                            <input type="tel" class="form-control" id="telefono" name="telefono">
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-block">Registrarse</button>
                    </form>
                </div>
                <div class="card-footer text-center">
                    <a href="login.php">¿Ya tienes una cuenta? Inicia Sesión</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/consultorio_dental/includes/footer.php'; ?>
