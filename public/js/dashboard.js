/* ========================================
   SENA Control - Dashboard Logic
   ======================================== */

document.addEventListener('DOMContentLoaded', function() {
    lucide.createIcons();
    initSidebar();
    initNotifications();
    checkUrlParams();
});

function initSidebar() {
    const toggle = document.getElementById('sidebarToggle');
    const close = document.getElementById('sidebarClose');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');

    if (toggle) {
        toggle.addEventListener('click', function() {
            sidebar.classList.add('open');
            overlay.classList.add('active');
        });
    }

    if (close) {
        close.addEventListener('click', function() {
            sidebar.classList.remove('open');
            overlay.classList.remove('active');
        });
    }

    if (overlay) {
        overlay.addEventListener('click', function() {
            sidebar.classList.remove('open');
            overlay.classList.remove('active');
        });
    }
}

function initNotifications() {
    const btn = document.getElementById('notificationsBtn');
    if (btn) {
        btn.addEventListener('click', function() {
            SenaToast.info('Notificaciones', 'Tienes 3 excusas pendientes por revisar');
        });
    }
}

function checkUrlParams() {
    const urlParams = new URLSearchParams(window.location.search);
    const success = urlParams.get('success');
    const error = urlParams.get('error');

    if (success === '1') {
        SenaToast.success('Bienvenido!', 'Has iniciado sesion exitosamente');
    }

    if (error) {
        SenaToast.error('Error', 'Ocurrio un error inesperado');
    }
}

function setPageTitle(title) {
    const el = document.getElementById('pageTitle');
    if (el) el.textContent = title;
}
