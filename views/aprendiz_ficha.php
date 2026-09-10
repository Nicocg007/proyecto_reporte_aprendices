<!DOCTYPE html>
<html lang="es">
<head>
    <?php include 'components/header.php'; ?>
</head>
<body>

    <?php include 'components/sidebar_aprendiz.php'; ?>

    <?php include 'components/navbar_aprendiz.php'; ?>

    <main class="main-content">

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon teal">
                    <i data-lucide="hash" class="w-6 h-6"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $codigo_ficha ?? '--'; ?></h3>
                    <p>Codigo Ficha</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon blue">
                    <i data-lucide="book-open" class="w-6 h-6"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $nombre_programa ?? '--'; ?></h3>
                    <p>Programa</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon green">
                    <i data-lucide="clock" class="w-6 h-6"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $hora_entrada ?? '--'; ?> - <?php echo $hora_salida ?? '--'; ?></h3>
                    <p>Horario</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon amber">
                    <i data-lucide="users" class="w-6 h-6"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $total_aprendices_ficha ?? '0'; ?></h3>
                    <p>Aprendices en Ficha</p>
                </div>
            </div>
        </div>

        <div class="dashboard-card">
            <div class="card-header">
                <h2 class="card-title">Informacion del Instructor</h2>
            </div>
            <div class="card-body">
                <div class="filter-grid" style="grid-template-columns: repeat(2, 1fr);">
                    <div class="filter-group">
                        <label class="filter-label">Nombre</label>
                        <p class="text-sm text-slate-700"><?php echo $instructor_nombre ?? '--'; ?></p>
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">Correo</label>
                        <p class="text-sm text-slate-700"><?php echo $instructor_correo ?? '--'; ?></p>
                    </div>
                </div>
            </div>
        </div>

    </main>

    <?php include 'components/footer.php'; ?>

</body>
</html>
