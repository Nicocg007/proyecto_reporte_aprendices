<!DOCTYPE html>
<html lang="es">
<head>
    <?php include 'components/header.php'; ?>
</head>
<body>

    <?php include 'components/sidebar.php'; ?>

    <?php include 'components/navbar.php'; ?>

    <main class="main-content">

        <div class="dashboard-card" style="margin-bottom: 20px;">
            <div class="card-header">
                <h2 class="card-title">Filtros de Busqueda</h2>
            </div>
            <div class="card-body">
                <form method="GET" action="reportes.php">
                    <div class="filter-grid">
                        <div class="filter-group">
                            <label class="filter-label">Fecha Inicio</label>
                            <input type="date" name="fecha_inicio" class="filter-input" value="<?php echo $_GET['fecha_inicio'] ?? ''; ?>">
                        </div>
                        <div class="filter-group">
                            <label class="filter-label">Fecha Fin</label>
                            <input type="date" name="fecha_fin" class="filter-input" value="<?php echo $_GET['fecha_fin'] ?? ''; ?>">
                        </div>
                        <div class="filter-group">
                            <label class="filter-label">Ficha</label>
                            <select name="ficha" class="filter-input">
                                <option value="">Todas las fichas</option>
                                <?php if (!empty($lista_fichas)): ?>
                                    <?php foreach ($lista_fichas as $ficha): ?>
                                        <option value="<?php echo $ficha['id_ficha']; ?>" <?php echo ($_GET['ficha'] ?? '') == $ficha['id_ficha'] ? 'selected' : ''; ?>>
                                            <?php echo $ficha['codigo_ficha'] . ' - ' . $ficha['nombre_programa']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label class="filter-label">Estado</label>
                            <select name="estado" class="filter-input">
                                <option value="">Todos</option>
                                <option value="Normal" <?php echo ($_GET['estado'] ?? '') == 'Normal' ? 'selected' : ''; ?>>Normal</option>
                                <option value="Retardo" <?php echo ($_GET['estado'] ?? '') == 'Retardo' ? 'selected' : ''; ?>>Retardo</option>
                                <option value="Inasistencia" <?php echo ($_GET['estado'] ?? '') == 'Inasistencia' ? 'selected' : ''; ?>>Inasistencia</option>
                                <option value="Salida Temprana" <?php echo ($_GET['estado'] ?? '') == 'Salida Temprana' ? 'selected' : ''; ?>>Salida Temprana</option>
                            </select>
                        </div>
                        <div class="filter-group filter-actions">
                            <button type="submit" class="btn-filter-primary">
                                <i data-lucide="search" class="w-4 h-4"></i>
                                Buscar
                            </button>
                            <a href="reportes.php" class="btn-filter-secondary">
                                <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                                Limpiar
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon green">
                    <i data-lucide="check-circle" class="w-6 h-6"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $total_normales ?? '0'; ?></h3>
                    <p>Asistencias Normales</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon amber">
                    <i data-lucide="clock" class="w-6 h-6"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $total_retardos ?? '0'; ?></h3>
                    <p>Retardos</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon red">
                    <i data-lucide="x-circle" class="w-6 h-6"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $total_inasistencias ?? '0'; ?></h3>
                    <p>Inasistencias</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon blue">
                    <i data-lucide="log-out" class="w-6 h-6"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $total_salidas ?? '0'; ?></h3>
                    <p>Salidas Tempranas</p>
                </div>
            </div>
        </div>

        <div class="dashboard-card">
            <div class="card-header">
                <h2 class="card-title">Reporte de Asistencias</h2>
                <div class="export-buttons">
                    <button class="btn-export btn-pdf">
                        <i data-lucide="file-text" class="w-4 h-4"></i>
                        PDF
                    </button>
                    <button class="btn-export btn-excel">
                        <i data-lucide="table" class="w-4 h-4"></i>
                        Excel
                    </button>
                </div>
            </div>
            <div class="card-body" style="padding: 0;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Aprendiz</th>
                            <th>Documento</th>
                            <th>Ficha</th>
                            <th>Fecha</th>
                            <th>Entrada</th>
                            <th>Salida</th>
                            <th>Retardo (min)</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($reporte_asistencias)): ?>
                            <?php foreach ($reporte_asistencias as $registro): ?>
                                <tr>
                                    <td class="font-medium"><?php echo $registro['nombre']; ?></td>
                                    <td><?php echo $registro['documento']; ?></td>
                                    <td><?php echo $registro['ficha']; ?></td>
                                    <td><?php echo $registro['fecha']; ?></td>
                                    <td><?php echo $registro['entrada']; ?></td>
                                    <td><?php echo $registro['salida']; ?></td>
                                    <td><?php echo $registro['retardo']; ?></td>
                                    <td><span class="badge-status badge-<?php echo strtolower($registro['estado']); ?>"><?php echo $registro['estado']; ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center py-8 text-slate-400">No hay registros para los filtros seleccionados</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="pagination">
            <button class="pagination-btn" disabled>
                <i data-lucide="chevron-left" class="w-4 h-4"></i>
                Anterior
            </button>
            <div class="pagination-numbers">
                <button class="pagination-num active">1</button>
            </div>
            <button class="pagination-btn" disabled>
                Siguiente
                <i data-lucide="chevron-right" class="w-4 h-4"></i>
            </button>
        </div>

    </main>

    <?php include 'components/footer.php'; ?>

</body>
</html>
