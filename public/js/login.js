/* ========================================
   SENA Control - Logica del Login
   ======================================== */

// Inicializar iconos Lucide
lucide.createIcons();

// Mostrar / ocultar contrasena
function togglePassword() {
    const input = document.getElementById('password');
    const icon = document.getElementById('eyeIcon');

    if (input.type === 'password') {
        input.type = 'text';
        icon.setAttribute('data-lucide', 'eye-off');
    } else {
        input.type = 'password';
        icon.setAttribute('data-lucide', 'eye');
    }

    lucide.createIcons();
}

// Validar formulario antes de enviar
document.getElementById('loginForm').addEventListener('submit', function(e) {
    const documento = document.getElementById('documento').value.trim();
    const password = document.getElementById('password').value.trim();
    const rol = document.getElementById('rol').value;

    if (!documento || !password) {
        e.preventDefault();
        SenaToast.warning('Campos vacios', 'Por favor completa todos los campos');
        return;
    }

    if (!rol) {
        e.preventDefault();
        SenaToast.warning('Rol requerido', 'Selecciona tu rol para continuar');
        return;
    }

    if (documento.length < 5) {
        e.preventDefault();
        SenaToast.error('Documento invalido', 'El numero de documento debe tener al menos 5 caracteres');
        return;
    }

    e.preventDefault();

    const btn = this.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.innerHTML = '<span class="loading loading-spinner loading-sm"></span> Verificando...';

    const rutas = {
        'admin': '../views/admin_dashboard.php',
        'instructor': '../views/instructor_dashboard.php',
        'aprendiz': '../views/aprendiz_dashboard.php'
    };

    setTimeout(() => {
        SenaToast.success('Bienvenido!', 'Has iniciado sesion exitosamente');
        setTimeout(() => {
            window.location.href = rutas[rol];
        }, 800);
    }, 1000);
});

// Mostrar mensajes por URL
function mostrarMensajes() {
    const urlParams = new URLSearchParams(window.location.search);
    const error = urlParams.get('error');
    const success = urlParams.get('success');
    const logout = urlParams.get('logout');

    // Toast de cerrar sesion
    if (logout === '1') {
        SenaToast.info('Sesion cerrada', 'Has cerrado sesion exitosamente');
        return;
    }

    // Toast de inicio de sesion exitoso
    if (success === '1') {
        SenaToast.success('Bienvenido!', 'Has iniciado sesion exitosamente');
        return;
    }

    if (!error) return;

    // Toast de errores
    const errores = {
        '1':   ['Usuario no encontrado', 'El numero de documento no esta registrado'],
        '2':   ['Contrasena incorrecta', 'La contrasena ingresada no es correcta'],
        '3':   ['Sesion expirada', 'Tu sesion ha expirado, inicia sesion nuevamente'],
        '500': ['Error del servidor', 'Ocurrio un error inesperado, intenta de nuevo']
    };

    const [title, message] = errores[error] || ['Error', 'Ocurrio un error desconocido'];
    SenaToast.error(title, message);
}

// Ejecutar al cargar la pagina
mostrarMensajes();
