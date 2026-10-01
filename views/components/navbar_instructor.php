<header class="navbar-top">
    <div class="navbar-left">
        <button id="sidebarToggle" class="sidebar-toggle-btn lg:hidden">
            <i data-lucide="menu" class="w-6 h-6"></i>
        </button>
        <h1 class="navbar-title" id="pageTitle">Dashboard</h1>
    </div>

    <div class="navbar-right">
        <div class="navbar-search">
            <i data-lucide="search" class="navbar-search-icon"></i>
            <input type="text" placeholder="Buscar..." class="navbar-search-input">
        </div>

        <?php
        // notificaciones reales de la campana segun el rol
        if (!isset($conn)) {
            require_once __DIR__ . '/../../config/database.php';
            $db = new Database();
            $conn = $db->getConnection();
        }
        require_once __DIR__ . '/../../model/notificacion_helper.php';
        $notificaciones = obtenerNotificaciones($conn, $_SESSION['id_usuario'], $_SESSION['rol']);
        ?>
        <div class="notif-menu">
            <button class="navbar-icon-btn" id="notificationsBtn">
                <i data-lucide="bell" class="w-5 h-5"></i>
                <?php if (count($notificaciones) > 0): ?>
                    <span class="navbar-badge"><?php echo count($notificaciones); ?></span>
                <?php endif; ?>
            </button>

            <div class="user-dropdown notif-dropdown" id="notifDropdown">
                <div class="dropdown-header">
                    <span class="dropdown-name">Notificaciones</span>
                    <span class="dropdown-role"><?php echo count($notificaciones); ?> nueva(s)</span>
                </div>
                <div class="dropdown-divider"></div>
                <?php if (count($notificaciones) > 0): ?>
                    <?php foreach ($notificaciones as $notif): ?>
                        <a href="<?php echo $notif['url']; ?>" class="dropdown-item notif-item">
                            <i data-lucide="bell-ring" class="w-4 h-4"></i>
                            <span><?php echo htmlspecialchars($notif['texto']); ?></span>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="dropdown-item notif-item notif-empty">
                        <i data-lucide="bell-off" class="w-4 h-4"></i>
                        <span>No tienes notificaciones</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="navbar-divider"></div>

        <div class="user-menu">
            <div class="navbar-user" id="userMenuBtn">
                <div class="navbar-avatar">
                    <i data-lucide="user" class="w-4 h-4"></i>
                </div>
                <div class="navbar-user-info">
                    <span class="navbar-user-name">Juan Vanegas</span>
                    <span class="navbar-user-role">Instructor</span>
                </div>
                <i data-lucide="chevron-down" id="userMenuArrow" class="w-4 h-4 navbar-user-arrow"></i>
            </div>

            <div class="user-dropdown" id="userDropdown">
                <div class="dropdown-header">
                    <span class="dropdown-name">Juan Vanegas</span>
                    <span class="dropdown-role">Instructor</span>
                </div>
                <div class="dropdown-divider"></div>
                <a href="editar_perfil.php?rol=instructor" class="dropdown-item">
                    <i data-lucide="user-cog" class="w-4 h-4"></i>
                    Editar Perfil
                </a>
                <a href="../logout.php" class="dropdown-item dropdown-item-danger">
                    <i data-lucide="log-out" class="w-4 h-4"></i>
                    Cerrar Sesion
                </a>
            </div>
        </div>
</header>