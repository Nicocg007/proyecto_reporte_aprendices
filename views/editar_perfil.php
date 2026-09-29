<?php
require_once '../model/auth_helper.php';
require_once '../config/database.php';
require_once '../config/mail.php';
requiereLogin();

// rol para elegir sidebar y navbar, viene de la url o del formulario
$rol = $_POST['rol'] ?? $_GET['rol'] ?? 'admin';

// el usuario logueado segun la sesion
$usuario = getUsuarioActual();

// conexion a la base de datos
$db = new Database();
$conn = $db->getConnection();

// datos reales del usuario logueado
$stmt = $conn->prepare("SELECT numero_documento, nombre, apellido, correo FROM usuario WHERE id_usuario = ?");
$stmt->execute([$usuario['id']]);
$perfil = $stmt->fetch();

// guardar el correo cuando llega el formulario del perfil
if (isset($_POST['guardar_perfil'])) {
    $correo_nuevo = $_POST['correo'];
    $stmt = $conn->prepare("UPDATE usuario SET correo = ? WHERE id_usuario = ?");
    $stmt->execute([$correo_nuevo, $usuario['id']]);
    header('Location: editar_perfil.php?rol=' . $rol . '&perfil=1');
    exit();
}

// al llegar el formulario se valida y se envia el codigo por correo
if (isset($_POST['enviar_codigo'])) {
    $password_actual = $_POST['password_actual'];
    $password_nueva = $_POST['password_nueva'];
    $password_confirmar = $_POST['password_confirmar'];

    // traer la contrasena guardada en la base
    $stmt = $conn->prepare("SELECT password FROM usuario WHERE id_usuario = ?");
    $stmt->execute([$usuario['id']]);
    $fila = $stmt->fetch();

    // revisar que la contrasena actual sea correcta
    if ($password_actual !== $fila['password']) {
        $mostrar_error = 'La contraseña actual no es correcta';
    } elseif ($password_nueva !== $password_confirmar) {
        $mostrar_error = 'La nueva contraseña y la confirmación no coinciden';
    } elseif (strlen($password_nueva) < 6) {
        $mostrar_error = 'La contraseña debe tener al menos 6 caracteres';
    } else {
        // generar un codigo aleatorio de 6 numeros
        $codigo = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // guardar el codigo y la contrasena nueva en la sesion
        $_SESSION['codigo_cambio'] = $codigo;
        $_SESSION['password_provisoria'] = $password_nueva;

        // el codigo vence en 5 minutos, se guarda la hora de vencimiento
        $_SESSION['codigo_expiracion'] = time() + 300;

        // enviar el codigo al correo del usuario con la plantilla html
        $enviado = enviarCorreo(
            $perfil['correo'],
            'Código para cambiar tu contraseña',
            mensajeHtml($codigo, $perfil['nombre'])
        );

        if ($enviado) {
            // si el correo salio bien, se muestra el campo del codigo
            $mostrar_codigo = true;
        } else {
            $mostrar_error = 'No se pudo enviar el correo. Revisa el config/mail.php';
        }
    }
}

// al llegar el codigo se valida y se guarda la contrasena nueva
if (isset($_POST['cambiar_password'])) {
    $codigo_ingresado = $_POST['codigo'] ?? '';

    // comparar el codigo ingresado con el que se envio
    if ($codigo_ingresado == ($_SESSION['codigo_cambio'] ?? '')) {
        // revisar que el codigo no haya vencido
        if (time() > ($_SESSION['codigo_expiracion'] ?? 0)) {
            $mostrar_error_codigo = 'El código ha expirado. Solicita uno nuevo';
        } else {
            $stmt = $conn->prepare("UPDATE usuario SET password = ? WHERE id_usuario = ?");
            $stmt->execute([$_SESSION['password_provisoria'], $usuario['id']]);

            // borrar el codigo y la contrasena temporal de la sesion
            unset($_SESSION['codigo_cambio'], $_SESSION['password_provisoria'], $_SESSION['codigo_expiracion']);

            header('Location: editar_perfil.php?rol=' . $rol . '&password=1');
            exit();
        }
    } else {
        $mostrar_error_codigo = 'El código no es correcto. Revisa tu correo';
    }
}

// nombre completo para mostrar en el avatar
$nombre_completo = ($perfil['nombre'] ?? '') . ' ' . ($perfil['apellido'] ?? '');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php include 'components/header.php'; ?>
</head>
<body>

    <?php
    switch ($rol) {
        case 'aprendiz':
            include 'components/sidebar_aprendiz.php';
            include 'components/navbar_aprendiz.php';
            break;
        case 'instructor':
            include 'components/sidebar_instructor.php';
            include 'components/navbar_instructor.php';
            break;
        default:
            include 'components/sidebar.php';
            include 'components/navbar.php';
            break;
    }
    ?>

    <main class="main-content">

        <div class="dashboard-card" style="margin-bottom: 20px;">
            <div class="card-header">
                <h2 class="card-title">Editar Perfil</h2>
            </div>
            <div class="card-body">
                <form method="POST" action="editar_perfil.php">
                    <input type="hidden" name="rol" value="<?php echo $rol; ?>">
                    <input type="hidden" name="guardar_perfil" value="1">
                    <div class="profile-layout">

                        <div class="profile-avatar-section">
                            <div class="profile-avatar">
                                <i data-lucide="user" class="w-10 h-10"></i>
                            </div>
                            <span class="profile-avatar-name"><?php echo $nombre_completo; ?></span>
                            <span class="profile-avatar-role"><?php echo ucfirst($rol); ?></span>
                            <label class="btn-filter-primary btn-profile-photo" style="cursor:pointer;">
                                <i data-lucide="camera" class="w-4 h-4"></i>
                                Cambiar Foto
                                <input type="file" name="foto" accept="image/*" hidden>
                            </label>
                        </div>

                        <div class="profile-form-section">
                            <h3 class="profile-form-title">Datos Personales</h3>
                            <div class="filter-grid" style="grid-template-columns: repeat(2, 1fr);">
                                <div class="filter-group">
                                    <label class="filter-label">Numero de Documento</label>
                                    <input type="text" class="filter-input" value="<?php echo $perfil['numero_documento'] ?? ''; ?>" disabled>
                                </div>
                                <div class="filter-group">
                                    <label class="filter-label">Correo</label>
                                    <input type="email" name="correo" class="filter-input" value="<?php echo $perfil['correo'] ?? ''; ?>" required>
                                </div>
                                <div class="filter-group">
                                    <label class="filter-label">Nombre</label>
                                    <input type="text" class="filter-input" value="<?php echo $perfil['nombre'] ?? ''; ?>" disabled>
                                </div>
                                <div class="filter-group">
                                    <label class="filter-label">Apellido</label>
                                    <input type="text" class="filter-input" value="<?php echo $perfil['apellido'] ?? ''; ?>" disabled>
                                </div>
                            </div>

                            <div class="profile-form-actions">
                                <button type="submit" class="btn-filter-primary">
                                    <i data-lucide="save" class="w-4 h-4"></i>
                                    Guardar Cambios
                                </button>
                            </div>
                        </div>

                    </div>
                </form>
            </div>
        </div>

        <div class="dashboard-card">
            <div class="card-header">
                <h2 class="card-title">Cambiar Contraseña</h2>
            </div>
            <div class="card-body">
                <form method="POST" action="editar_perfil.php">
                    <input type="hidden" name="rol" value="<?php echo $rol; ?>">
                    <div class="filter-grid" style="grid-template-columns: repeat(3, 1fr);">
                        <div class="filter-group">
                            <label class="filter-label">Contraseña Actual</label>
                            <input type="password" name="password_actual" class="filter-input" placeholder="Contraseña actual" required>
                        </div>
                        <div class="filter-group">
                            <label class="filter-label">Nueva Contraseña</label>
                            <input type="password" name="password_nueva" class="filter-input" placeholder="Nueva contraseña" required>
                        </div>
                        <div class="filter-group">
                            <label class="filter-label">Confirmar Contraseña</label>
                            <input type="password" name="password_confirmar" class="filter-input" placeholder="Confirmar contraseña" required>
                        </div>
                    </div>

                    <div class="profile-form-actions" style="margin-top: 16px;">
                        <button type="submit" name="enviar_codigo" value="1" class="btn-filter-primary">
                            <i data-lucide="lock" class="w-4 h-4"></i>
                            Enviar Codigo y Cambiar
                        </button>
                    </div>
                </form>

                <?php if (!empty($mostrar_codigo)): ?>
                <?php $segundos_restantes = (int)($_SESSION['codigo_expiracion'] - time()); ?>
                <form method="POST" action="editar_perfil.php" style="margin-top: 20px;">
                    <input type="hidden" name="rol" value="<?php echo $rol; ?>">
                    <div class="card-body" style="padding: 16px; border: 2px dashed #0d9488; border-radius: 12px;">
                        <h3 class="profile-form-title">Ingresa el código enviado a tu correo</h3>
                        <p class="text-sm text-slate-500">Revisa <?php echo $perfil['correo'] ?? ''; ?> y escribe el código de 6 números</p>
                        <div style="display: flex; align-items: center; gap: 12px; margin-top: 12px;">
                            <div class="filter-group" style="max-width: 220px;">
                                <input type="text" name="codigo" id="inputCodigo" class="filter-input" placeholder="000000" maxlength="6" required style="text-align:center; letter-spacing:8px; font-size:1.4rem;">
                            </div>
                            <div style="text-align: center;">
                                <span id="contadorCodigo" style="font-size: 1.6rem; font-weight: bold; color: #0f766e; font-variant-numeric: tabular-nums;">05:00</span>
                                <p style="margin: 0; font-size: 12px; color: #64748b;">tiempo restante</p>
                            </div>
                        </div>
                        <button type="submit" name="cambiar_password" id="btnConfirmar" value="1" class="btn-filter-primary" style="margin-top: 12px;">
                            Confirmar Código
                        </button>
                    </div>
                </form>

                <script>
                // contador regresivo real del codigo
                (function() {
                    // segundos que quedan cuando se genero la pagina
                    let segundos = <?php echo $segundos_restantes > 0 ? $segundos_restantes : 0; ?>;
                    const span = document.getElementById('contadorCodigo');
                    const boton = document.getElementById('btnConfirmar');

                    function pintar() {
                        // formato de 2 numeros para minuto y segundo
                        const min = String(Math.floor(segundos / 60)).padStart(2, '0');
                        const seg = String(segundos % 60).padStart(2, '0');
                        span.textContent = min + ':' + seg;

                        // cuando llega a cero se bloquea el boton
                        if (segundos <= 0) {
                            clearInterval(timer);
                            span.textContent = '00:00';
                            span.style.color = '#dc2626';
                            boton.disabled = true;
                            boton.textContent = 'El código expiró';
                        }
                    }

                    pintar();
                    const timer = setInterval(function() {
                        segundos = segundos - 1;
                        pintar();
                    }, 1000);
                })();
                </script>
                <?php endif; ?>
            </div>
        </div>

    </main>

    <?php include 'components/footer.php'; ?>

    <script>
    // mensajes que llegan por la url
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('perfil') === '1') {
        SenaToast.success('Listo', 'Correo actualizado correctamente');
    }
    if (urlParams.get('password') === '1') {
        SenaToast.success('Listo', 'Contraseña actualizada correctamente');
    }
    // mensajes de error cuando se quedan en la pagina
    <?php if (!empty($mostrar_error)): ?>
    SenaToast.error('Error', '<?php echo $mostrar_error; ?>');
    <?php endif; ?>
    <?php if (!empty($mostrar_error_codigo)): ?>
    SenaToast.error('Error', '<?php echo $mostrar_error_codigo; ?>');
    <?php endif; ?>
    </script>

</body>
</html>