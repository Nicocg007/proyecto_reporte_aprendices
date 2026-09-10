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

        <button class="navbar-icon-btn" id="notificationsBtn">
            <i data-lucide="bell" class="w-5 h-5"></i>
            <span class="navbar-badge">3</span>
        </button>

        <div class="navbar-divider"></div>

        <div class="user-menu">
            <div class="navbar-user" id="userMenuBtn">
                <div class="navbar-avatar">
                    <i data-lucide="user" class="w-4 h-4"></i>
                </div>
                <div class="navbar-user-info">
                    <span class="navbar-user-name">Admin SENA</span>
                    <span class="navbar-user-role">Administrador</span>
                </div>
                <i data-lucide="chevron-down" id="userMenuArrow" class="w-4 h-4 navbar-user-arrow"></i>
            </div>

            <div class="user-dropdown" id="userDropdown">
                <div class="dropdown-header">
                    <span class="dropdown-name">Admin SENA</span>
                    <span class="dropdown-role">Administrador</span>
                </div>
                <div class="dropdown-divider"></div>
                <a href="editar_perfil.php?rol=admin" class="dropdown-item">
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