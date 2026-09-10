<!DOCTYPE html>
<html lang="es">
<head>
    <?php include 'components/header.php'; ?>
</head>
<body>

    <?php include 'components/sidebar.php'; ?>

    <?php include 'components/navbar.php'; ?>

    <main class="main-content">

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon teal">
                    <i data-lucide="users" class="w-6 h-6"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $total_aprendices ?? '0'; ?></h3>
                    <p>Aprendices Inscritos</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon blue">
                    <i data-lucide="book-open" class="w-6 h-6"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $total_fichas ?? '0'; ?></h3>
                    <p>Fichas Activas</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon green">
                    <i data-lucide="check-circle" class="w-6 h-6"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $asistencias_hoy ?? '0'; ?></h3>
                    <p>Asistencias Hoy</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon amber">
                    <i data-lucide="clock" class="w-6 h-6"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $excusas_pendientes ?? '0'; ?></h3>
                    <p>Excusas Pendientes</p>
                </div>
            </div>
        </div>

        <div class="content-grid">

            <div class="dashboard-card">
                <div class="card-header">
                    <h2 class="card-title">Ultimas Asistencias</h2>
                    <a href="reportes.php" class="text-sm text-teal-600 hover:text-teal-700 font-medium">Ver todas</a>
                </div>
                <div class="card-body" style="padding: 0;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Aprendiz</th>
                                <th>Ficha</th>
                                <th>Entrada</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($ultimas_asistencias)): ?>
                                <?php foreach ($ultimas_asistencias as $asistencia): ?>
                                    <tr>
                                        <td class="font-medium"><?php echo $asistencia['nombre']; ?></td>
                                        <td><?php echo $asistencia['ficha']; ?></td>
                                        <td><?php echo $asistencia['entrada']; ?></td>
                                        <td><span class="badge-status badge-<?php echo strtolower($asistencia['estado']); ?>"><?php echo $asistencia['estado']; ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center py-8 text-slate-400">No hay registros de asistencia</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="dashboard-card">
                <div class="card-header">
                    <h2 class="card-title">Acciones Rapidas</h2>
                </div>
                <div class="card-body">
                    <div class="quick-actions">
                        <a href="#" class="quick-action-btn">
                            <div class="quick-action-icon">
                                <i data-lucide="user-plus" class="w-5 h-5"></i>
                            </div>
                            <div class="quick-action-text">
                                <span class="quick-action-title">Agregar Aprendiz</span>
                                <span class="quick-action-desc">Registrar nuevo aprendiz</span>
                            </div>
                        </a>

                        <a href="#" class="quick-action-btn">
                            <div class="quick-action-icon">
                                <i data-lucide="folder-plus" class="w-5 h-5"></i>
                            </div>
                            <div class="quick-action-text">
                                <span class="quick-action-title">Crear Ficha</span>
                                <span class="quick-action-desc">Nueva ficha de formacion</span>
                            </div>
                        </a>

                        <a href="reportes.php" class="quick-action-btn">
                            <div class="quick-action-icon">
                                <i data-lucide="file-bar-chart" class="w-5 h-5"></i>
                            </div>
                            <div class="quick-action-text">
                                <span class="quick-action-title">Generar Reporte</span>
                                <span class="quick-action-desc">Exportar asistencias</span>
                            </div>
                        </a>

                        <a href="#" class="quick-action-btn">
                            <div class="quick-action-icon">
                                <i data-lucide="scan-line" class="w-5 h-5"></i>
                            </div>
                            <div class="quick-action-text">
                                <span class="quick-action-title">Asignar RFID</span>
                                <span class="quick-action-desc">Vincular llavero</span>
                            </div>
                        </a>
                    </div>
                </div>
            </div>

        </div>

    </main>

    <?php include 'components/footer.php'; ?>

</body>
</html>
