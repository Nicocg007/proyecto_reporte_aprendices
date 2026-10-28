<aside id="sidebar" class="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <img src="../public/img/logo_sena.jpg" alt="Logo SENA" class="sidebar-logo-img">
            <span class="sidebar-title">SENA Control</span>
        </div>
        <button id="sidebarClose" class="sidebar-close-btn lg:hidden">
            <i data-lucide="x" class="w-5 h-5"></i>
        </button>
    </div>

    <?php
$paginaActual = basename($_SERVER['PHP_SELF']);

$enlaces = [
    'admin_dashboard.php' => [
        'titulo' => 'Dashboard',
        'icono' => 'layout-dashboard'
    ],
    'admin_aprendices.php' => [
        'titulo' => 'Aprendices',
        'icono' => 'users'
    ],
    'admin_instructores.php' => [
        'titulo' => 'Instructores',
        'icono' => 'graduation-cap'
    ],
    'admin_fichas.php' => [
        'titulo' => 'Fichas',
        'icono' => 'book-open'
    ],
    'horario.php' => [
        'titulo' => 'Horario',
        'icono' => 'calendar-days'
    ],
    'rfid.php' => [
        'titulo' => 'Registro RFID',
        'icono' => 'scan-line'
    ],
    'reportes.php' => [
        'titulo' => 'Reportes',
        'icono' => 'bar-chart-3'
    ],
    'admin_excusas.php' => [
        'titulo' => 'Excusas',
        'icono' => 'file-text'
    ]
];
?>

<nav class="sidebar-nav">
    <?php foreach ($enlaces as $archivo => $enlace): ?>
        <a
            href="<?php echo htmlspecialchars($archivo); ?>"
            class="sidebar-link <?php echo $paginaActual === $archivo ? 'active' : ''; ?>"
        >
            <i data-lucide="<?php echo htmlspecialchars($enlace['icono']); ?>" class="w-5 h-5"></i>
            <span><?php echo htmlspecialchars($enlace['titulo']); ?></span>
        </a>
    <?php endforeach; ?>
</nav>

    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="sidebar-avatar">
                <i data-lucide="shield" class="w-4 h-4"></i>
            </div>
            <div class="sidebar-user-info">
                <span class="sidebar-user-name">Administrador</span>
                <span class="sidebar-user-role">Admin</span>
            </div>
        </div>
    </div>
</aside>
