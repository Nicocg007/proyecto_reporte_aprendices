<?php
session_start();
// Si ya hay una sesión activa, redirigir al panel correspondiente
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_rol'] == 1) header('Location: admin_dashboard.php');
    elseif ($_SESSION['user_rol'] == 2) header('Location: instructor_dashboard.php');
    elseif ($_SESSION['user_rol'] == 3) header('Location: aprendiz_dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión | SENA Control Asistencia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-dark text-white">

    <div class="container mb-3">
        <div class="row justify-content-center mt-5">
            <div class="col-md-4">
                <div class="card shadow border-0 bg-secondary text-white">
                    <div class="card-body p-4">
                        <h3 class="card-title text-center mb-4 text-warning fw-bold">SENA Control Asistencia</h3>

                        <?php if (isset($_GET['error'])): ?>
                            <div class="alert alert-danger py-2 text-center" role="alert">
                                <?php 
                                    if ($_GET['error'] === 'campos_vacios') echo 'Por favor complete todos los campos.';
                                    elseif ($_GET['error'] === 'credenciales_invalidas') echo 'Correo o contraseña incorrectos.';
                                    elseif ($_GET['error'] === 'usuario_inactivo') echo 'El usuario se encuentra inactivo.';
                                    elseif ($_GET['error'] === 'no_session') echo 'Debe iniciar sesión primero.';
                                ?>
                            </div>
                        <?php endif; ?>

                        <form action="../controllers/auth_controller.php" method="POST">

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Correo Electrónico</label>
                                <input type="email" name="correo" class="form-control" required placeholder="ejemplo@sena.edu.co">
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Contraseña</label>
                                <input type="password" name="password" class="form-control" required placeholder="••••••••">
                            </div>

                            <button type="submit" class="btn btn-warning w-100 fw-bold py-2 mt-2">Ingresar</button>

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
</body>
</html>