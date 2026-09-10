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

    if (!documento || !password) {
        e.preventDefault();
        SenaToast.warning('Campos vacios', 'Por favor completa todos los campos');
        return;
    }

    if (documento.length < 5) {
        e.preventDefault();
        SenaToast.error('Documento invalido', 'El numero de documento debe tener al menos 5 caracteres');
        return;
    }

    // Prevenir envio real (solo diseno)
    e.preventDefault();

    // Animacion del boton al enviar
    const btn = this.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.innerHTML = '<span class="loading loading-spinner loading-sm"></span> Verificando...';

    // Mostrar toast de exito despues de 1.5 segundos
    setTimeout(() => {
        SenaToast.success('Bienvenido!', 'Has iniciado sesion exitosamente');
        btn.disabled = false;
        btn.innerHTML = '<i data-lucide="log-in" class="w-5 h-5 mr-2"></i> Iniciar Sesion';
        lucide.createIcons();
    }, 1500);
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
