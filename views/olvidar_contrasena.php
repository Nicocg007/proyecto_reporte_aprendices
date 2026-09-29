<?php
require_once '../config/database.php';
require_once '../config/mail.php';

// si ya hay sesion activa, no hace falta recuperar contrasena
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (isset($_SESSION['id_usuario'])) {
    header('Location: login.php');
    exit();
}

// conexion a la base de datos
$db = new Database();
$conn = $db->getConnection();

// enviar el codigo al correo cuando llega el documento
if (isset($_POST['enviar_codigo'])) {
    $documento = trim($_POST['documento'] ?? '');

    // buscar el usuario por su numero de documento
    $stmt = $conn->prepare("SELECT id_usuario, numero_documento, nombre, correo FROM usuario WHERE numero_documento = ?");
    $stmt->execute([$documento]);
    $usuario = $stmt->fetch();

    if (!$usuario) {
        $error = 'No existe un usuario con ese documento';
    } elseif (empty($usuario['correo'])) {
        $error = 'El usuario no tiene un correo registrado';
    } else {
        // guardar el documento en la sesion para saber a quien cambiarle
        $_SESSION['reset_documento'] = $documento;

        // generar codigo aleatorio de 6 numeros
        $codigo = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $_SESSION['reset_codigo'] = $codigo;
        $_SESSION['reset_expiracion'] = time() + 300;

        // enviar el codigo con la plantilla html
        $enviado = enviarCorreo($usuario['correo'], 'Restablecer tu contraseña', mensajeHtml($codigo, $usuario['nombre']));

        if ($enviado) {
            $mostrar_codigo = true;
            $correo_destino = $usuario['correo'];
        } else {
            $error = 'No se pudo enviar el correo. Revisa el config/mail.php';
        }
    }
}

// cambiar la contrasena cuando llega el codigo y la contrasena nueva
if (isset($_POST['cambiar_password'])) {
    $codigo_ingresado = $_POST['codigo'] ?? '';
    $password_nueva = $_POST['password_nueva'] ?? '';
    $password_confirmar = $_POST['password_confirmar'] ?? '';
    $documento = $_SESSION['reset_documento'] ?? '';

    // revisar el codigo, la vigencia y la contrasena nueva
    if ($codigo_ingresado != ($_SESSION['reset_codigo'] ?? '')) {
        $error_codigo = 'El código no es correcto. Revisa tu correo';
    } elseif (time() > ($_SESSION['reset_expiracion'] ?? 0)) {
        $error_codigo = 'El código ha expirado. Solicita uno nuevo';
    } elseif ($password_nueva !== $password_confirmar) {
        $error_codigo = 'Las contraseñas no coinciden';
    } elseif (strlen($password_nueva) < 6) {
        $error_codigo = 'La contraseña debe tener al menos 6 caracteres';
    } else {
        // cambiar la contrasena del usuario del documento guardado
        $stmt = $conn->prepare("UPDATE usuario SET password = ? WHERE numero_documento = ?");
        $stmt->execute([$password_nueva, $documento]);

        // limpiar los datos del reseteo de la sesion
        unset($_SESSION['reset_documento'], $_SESSION['reset_codigo'], $_SESSION['reset_expiracion']);

        // ir al login con mensaje de exito
        header('Location: login.php?reset=1');
        exit();
    }

    // si fallo, mostrar de nuevo el formulario del codigo
    if (!empty($_SESSION['reset_expiracion'])) {
        $mostrar_codigo = true;
    }
}

// segundos que le quedan al codigo para el contador
$segundos_restantes = (int)(($_SESSION['reset_expiracion'] ?? 0) - time());
$segundos_restantes = $segundos_restantes > 0 ? $segundos_restantes : 0;
?>
<!DOCTYPE html>
<html lang="es" data-theme="wireframe">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - SENA Control</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] }
                }
            }
        }
    </script>

    <link href="https://cdn.jsdelivr.net/npm/daisyui@4/dist/full.min.css" rel="stylesheet">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="../public/css/login.css">
    <link rel="stylesheet" href="../public/css/toast.css">
</head>
<body class="fondo-login">

    <div class="shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
    </div>

    <div class="min-h-screen flex items-center justify-center px-4 py-8 relative z-10">
        <div class="w-full max-w-md">

            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-white shadow-lg mb-4 logo-sena">
                    <img src="../public/img/logo_sena.jpg" alt="Logo SENA" class="login-logo-img">
                </div>
                <h1 class="text-3xl font-bold text-white mb-2">SENA Control</h1>
                <p class="text-teal-200 font-light">Recuperación de Contraseña</p>
            </div>

            <div class="card-glass rounded-3xl shadow-2xl p-8">

                <?php if (empty($mostrar_codigo)): ?>

                <div class="text-center mb-6">
                    <h2 class="text-2xl font-bold text-slate-800">¿Olvidaste tu contraseña?</h2>
                    <p class="text-slate-500 text-sm mt-1">Escribe tu numero de documento y te enviamos un codigo al correo</p>
                </div>

                <form method="POST" action="olvidar_contrasena.php" class="space-y-5">
                    <div class="input-group">
                        <i data-lucide="user" class="icon-input w-5 h-5"></i>
                        <input
                            type="text"
                            name="documento"
                            placeholder="Numero de documento"
                            class="input input-bordered w-full h-12 rounded-xl input-focus transition-all duration-200"
                            required
                        >
                    </div>

                    <button type="submit" name="enviar_codigo" value="1" class="btn btn-login w-full h-12 rounded-xl text-white font-semibold text-base border-none">
                        <i data-lucide="mail" class="w-5 h-5 mr-2"></i>
                        Enviar Codigo al Correo
                    </button>
                </form>

                <?php else: ?>

                <div class="text-center mb-6">
                    <h2 class="text-2xl font-bold text-slate-800">Verifica tu correo</h2>
                    <p class="text-slate-500 text-sm mt-1">Te enviamos un codigo de 6 numeros a <?php echo $correo_destino ?? ''; ?></p>
                </div>

                <form method="POST" action="olvidar_contrasena.php" class="space-y-5">
                    <div class="input-group">
                        <i data-lucide="shield" class="icon-input w-5 h-5"></i>
                        <input
                            type="text"
                            name="codigo"
                            id="inputCodigo"
                            placeholder="000000"
                            maxlength="6"
                            class="input input-bordered w-full h-12 rounded-xl text-center tracking-[8px] text-xl"
                            style="letter-spacing: 8px;"
                            required
                        >
                    </div>

                    <div class="flex items-center justify-center gap-3">
                        <span id="contadorCodigo" style="font-size: 1.5rem; font-weight: bold; color: #0f766e; font-variant-numeric: tabular-nums;">05:00</span>
                        <span class="text-slate-400 text-xs">tiempo restante</span>
                    </div>

                    <div class="input-group">
                        <i data-lucide="lock" class="icon-input w-5 h-5"></i>
                        <input
                            type="password"
                            name="password_nueva"
                            placeholder="Nueva contraseña"
                            class="input input-bordered w-full h-12 rounded-xl input-focus transition-all duration-200"
                            required
                        >
                    </div>

                    <div class="input-group">
                        <i data-lucide="lock" class="icon-input w-5 h-5"></i>
                        <input
                            type="password"
                            name="password_confirmar"
                            placeholder="Confirmar contraseña"
                            class="input input-bordered w-full h-12 rounded-xl input-focus transition-all duration-200"
                            required
                        >
                    </div>

                    <button type="submit" name="cambiar_password" id="btnConfirmar" value="1" class="btn btn-login w-full h-12 rounded-xl text-white font-semibold text-base border-none">
                        <i data-lucide="check" class="w-5 h-5 mr-2"></i>
                        Cambiar Contraseña
                    </button>
                </form>

                <script>
                // contador regresivo real del codigo
                (function() {
                    let segundos = <?php echo $segundos_restantes; ?>;
                    const span = document.getElementById('contadorCodigo');
                    const boton = document.getElementById('btnConfirmar');

                    function pintar() {
                        const min = String(Math.floor(segundos / 60)).padStart(2, '0');
                        const seg = String(segundos % 60).padStart(2, '0');
                        span.textContent = min + ':' + seg;

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

                <div class="divider my-6 text-slate-400 text-xs"></div>
                <div class="text-center">
                    <a href="login.php" class="text-teal-600 hover:text-teal-700 font-medium text-sm">
                        <i data-lucide="arrow-left" class="w-4 h-4 inline mr-1"></i>
                        Volver al inicio de sesion
                    </a>
                </div>

            </div>

            <div class="text-center mt-6">
                <p class="text-teal-200 text-xs">&copy; 2026 - Todos los derechos reservados</p>
            </div>

        </div>
    </div>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../public/js/toast.js"></script>

    <script>
    lucide.createIcons();

    // mensajes de error cuando se quedan en la pagina
    <?php if (!empty($error)): ?>
    SenaToast.error('Error', '<?php echo $error; ?>');
    <?php endif; ?>
    <?php if (!empty($error_codigo)): ?>
    SenaToast.error('Error', '<?php echo $error_codigo; ?>');
    <?php endif; ?>
    </script>

</body>
</html>