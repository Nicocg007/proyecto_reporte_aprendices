<header class="navbar-top">
    <div class="navbar-left">
        <button id="sidebarToggle" class="sidebar-toggle-btn lg:hidden">
            <i data-lucide="menu" class="w-6 h-6"></i>
        </button>
        <h1 class="navbar-title" id="pageTitle">Mi Panel</h1>
    </div>

    <div class="navbar-right">
        <div class="navbar-search">
            <i data-lucide="search" class="navbar-search-icon"></i>
            <input type="text" placeholder="Buscar..." class="navbar-search-input">
        </div>

        <button class="navbar-icon-btn" id="notificationsBtn">
            <i data-lucide="bell" class="w-5 h-5"></i>
            <span class="navbar-badge">1</span>
        </button>

        <div class="navbar-divider"></div>

        <div class="navbar-user" id="userMenu">
            <div class="navbar-avatar">
                <i data-lucide="user" class="w-4 h-4"></i>
            </div>
            <div class="navbar-user-info">
                <span class="navbar-user-name">Carlos Perez</span>
                <span class="navbar-user-role">Aprendiz</span>
            </div>
            <i data-lucide="chevron-down" class="w-4 h-4 navbar-user-arrow"></i>
        </div>

        <a href="../logout.php" class="navbar-logout-btn" title="Cerrar sesion">
            <i data-lucide="log-out" class="w-5 h-5"></i>
        </a>
    </div>
</header>
