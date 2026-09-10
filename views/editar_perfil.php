<!DOCTYPE html>
<html lang="es">
<head>
    <?php include 'components/header.php'; ?>
</head>
<body>

    <?php
    $rol = $_GET['rol'] ?? 'admin';

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
                <form method="POST" action="#">
                    <div class="profile-layout">

                        <div class="profile-avatar-section">
                            <div class="profile-avatar">
                                <i data-lucide="user" class="w-10 h-10"></i>
                            </div>
                            <span class="profile-avatar-name">
                                <?php
                                $nombres = [
                                    'admin' => 'Admin SENA',
                                    'instructor' => 'Juan Vanegas',
                                    'aprendiz' => 'Carlos Perez'
                                ];
                                echo $nombres[$rol] ?? 'Usuario';
                                ?>
                            </span>
                            <span class="profile-avatar-role">
                                <?php
                                $roles = [
                                    'admin' => 'Administrador',
                                    'instructor' => 'Instructor',
                                    'aprendiz' => 'Aprendiz'
                                ];
                                echo $roles[$rol] ?? 'Usuario';
                                ?>
                            </span>
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
                                    <input type="text" name="numero_documento" class="filter-input" value="<?php echo $perfil['numero_documento'] ?? '1000000003'; ?>" placeholder="Numero de documento">
                                </div>
                                <div class="filter-group">
                                    <label class="filter-label">Correo</label>
                                    <input type="email" name="correo" class="filter-input" value="<?php echo $perfil['correo'] ?? 'cperez@gmail.com'; ?>" placeholder="Correo electronico">
                                </div>
                                <div class="filter-group">
                                    <label class="filter-label">Nombre</label>
                                    <input type="text" name="nombre" class="filter-input" value="<?php echo $perfil['nombre'] ?? 'Carlos'; ?>" placeholder="Nombre">
                                </div>
                                <div class="filter-group">
                                    <label class="filter-label">Apellido</label>
                                    <input type="text" name="apellido" class="filter-input" value="<?php echo $perfil['apellido'] ?? 'Perez'; ?>" placeholder="Apellido">
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
                <h2 class="card-title">Cambiar Contrasena</h2>
            </div>
            <div class="card-body">
                <form method="POST" action="#">
                    <div class="filter-grid" style="grid-template-columns: repeat(3, 1fr);">
                        <div class="filter-group">
                            <label class="filter-label">Contrasena Actual</label>
                            <input type="password" name="password_actual" class="filter-input" placeholder="Contrasena actual">
                        </div>
                        <div class="filter-group">
                            <label class="filter-label">Nueva Contrasena</label>
                            <input type="password" name="password_nueva" class="filter-input" placeholder="Nueva contrasena">
                        </div>
                        <div class="filter-group">
                            <label class="filter-label">Confirmar Contrasena</label>
                            <input type="password" name="password_confirmar" class="filter-input" placeholder="Confirmar contrasena">
                        </div>
                    </div>

                    <div class="profile-form-actions" style="margin-top: 16px;">
                        <button type="submit" class="btn-filter-primary">
                            <i data-lucide="lock" class="w-4 h-4"></i>
                            Actualizar Contrasena
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </main>

    <?php include 'components/footer.php'; ?>

</body>
</html>