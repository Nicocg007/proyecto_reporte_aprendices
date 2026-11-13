<?php
require_once '../model/auth_helper.php';
require_once '../model/seguridad_helper.php';
requiereRoles(['Administrador', 'Instructor', 'Aprendiz']);

// conexion a la base de datos
require_once '../config/database.php';
require_once '../vendor/autoload.php';
$db = new Database();
$conn = $db->getConnection();

$rol_actual = $_SESSION['rol'] ?? '';
$id_usuario = (int)($_SESSION['id_usuario'] ?? 0);

$attendanceRepository = new \App\Repository\AttendanceRepository($conn);
$attendanceService = new \App\Service\AttendanceService();
$lista_fichas = $attendanceRepository->findFichasForReport($rol_actual, $id_usuario);

$filtros = $attendanceService->normalizeReportFilters($_GET);
$fecha_inicio = $filtros['fecha_inicio'];
$fecha_fin = $filtros['fecha_fin'];
$ficha = $filtros['ficha'];
$estado = $filtros['estado'];

// filtros activos para pasarlos a la exportacion y a la paginacion
$filtros = [
    'fecha_inicio' => $fecha_inicio,
    'fecha_fin' => $fecha_fin,
    'ficha' => $ficha,
    'estado' => $estado,
];
$filtros_activos = array_filter($filtros, function ($v) { return $v !== ''; });
$query_actual = http_build_query($filtros_activos);

$por_pagina = 10;
$paginaSolicitada = max(1, (int)($_GET['pagina'] ?? 1));
$resultados = $attendanceRepository->findReportPage(
    $filtros,
    $rol_actual,
    $id_usuario,
    $por_pagina,
    ($paginaSolicitada - 1) * $por_pagina
);
$pageInfo = $attendanceService->normalizePagination(
    $paginaSolicitada,
    $por_pagina,
    $resultados['total_rows']
);
$pagina = $pageInfo['page'];
if ($pagina !== $paginaSolicitada) {
    $resultados = $attendanceRepository->findReportPage(
        $filtros,
        $rol_actual,
        $id_usuario,
        $por_pagina,
        $pageInfo['offset']
    );
}
$total_paginas = $pageInfo['total_pages'];
$reporte_asistencias = $resultados['rows'];
$totales_estado = array_merge(
    array_fill_keys(['Normal', 'Retardo', 'Inasistencia', 'Salida Temprana'], 0),
    $resultados['status_counts']
);
$total_normales = $totales_estado['Normal'];
$total_retardos = $totales_estado['Retardo'];
$total_inasistencias = $totales_estado['Inasistencia'];
$total_salidas = $totales_estado['Salida Temprana'];

$url_pagina = 'reportes.php?' . ($query_actual !== '' ? $query_actual . '&' : '') . 'pagina=';
?>
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
                            <input type="date" name="fecha_inicio" class="filter-input" value="<?php echo e($fecha_inicio); ?>">
                        </div>
                        <div class="filter-group">
                            <label class="filter-label">Fecha Fin</label>
                            <input type="date" name="fecha_fin" class="filter-input" value="<?php echo e($fecha_fin); ?>">
                        </div>
                        <div class="filter-group">
                            <label class="filter-label">Ficha</label>
                            <select name="ficha" class="filter-input">
                                <option value="">Todas las fichas</option>
                                <?php if (!empty($lista_fichas)): ?>
                                    <?php foreach ($lista_fichas as $ficha): ?>
                                        <option value="<?php echo e($ficha['id_ficha']); ?>" <?php echo ($ficha == $ficha['id_ficha']) ? 'selected' : ''; ?>>
                                            <?php echo e($ficha['codigo_ficha'] . ' - ' . $ficha['nombre_programa']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label class="filter-label">Estado</label>
                            <select name="estado" class="filter-input">
                                <option value="">Todos</option>
                                <option value="Normal" <?php echo $estado == 'Normal' ? 'selected' : ''; ?>>Normal</option>
                                <option value="Retardo" <?php echo $estado == 'Retardo' ? 'selected' : ''; ?>>Retardo</option>
                                <option value="Inasistencia" <?php echo $estado == 'Inasistencia' ? 'selected' : ''; ?>>Inasistencia</option>
                                <option value="Salida Temprana" <?php echo $estado == 'Salida Temprana' ? 'selected' : ''; ?>>Salida Temprana</option>
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
                    <a href="../controllers/exportar_controller.php?tipo=pdf&<?php echo $query_actual; ?>" target="_blank" class="btn-export btn-pdf" style="text-decoration:none;">
                        <i data-lucide="file-text" class="w-4 h-4"></i>
                        PDF
                    </a>
                    <a href="../controllers/exportar_controller.php?tipo=excel&<?php echo $query_actual; ?>" class="btn-export btn-excel" style="text-decoration:none;">
                        <i data-lucide="table" class="w-4 h-4"></i>
                        Excel
                    </a>
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
                                    <td class="font-medium"><?php echo e($registro['nombre']); ?></td>
                                    <td><?php echo e($registro['documento']); ?></td>
                                    <td><?php echo e($registro['ficha']); ?></td>
                                    <td><?php echo e($registro['fecha']); ?></td>
                                    <td><?php echo e($registro['entrada']); ?></td>
                                    <td><?php echo e($registro['salida']); ?></td>
                                    <td><?php echo e($registro['retardo']); ?></td>
                                    <td><span class="badge-status badge-<?php echo strtolower($registro['estado']); ?>"><?php echo e($registro['estado']); ?></span></td>
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

        <?php if ($total_paginas > 1): ?>
        <div class="pagination">
            <?php if ($pagina > 1): ?>
                <a href="<?php echo $url_pagina . ($pagina - 1); ?>" class="pagination-btn" style="text-decoration:none;">
                    <i data-lucide="chevron-left" class="w-4 h-4"></i>
                    Anterior
                </a>
            <?php else: ?>
                <span class="pagination-btn" style="opacity:.5;cursor:not-allowed;">
                    <i data-lucide="chevron-left" class="w-4 h-4"></i>
                    Anterior
                </span>
            <?php endif; ?>

            <div class="pagination-numbers">
                <?php
                $inicio_rango = max(1, $pagina - 2);
                $fin_rango = min($total_paginas, $pagina + 2);
                ?>
                <?php if ($inicio_rango > 1): ?>
                    <a href="<?php echo $url_pagina; ?>1" class="pagination-num" style="text-decoration:none;">1</a>
                    <?php if ($inicio_rango > 2): ?><span class="pagination-dots">...</span><?php endif; ?>
                <?php endif; ?>

                <?php for ($p = $inicio_rango; $p <= $fin_rango; $p++): ?>
                    <?php if ($p == $pagina): ?>
                        <span class="pagination-num active"><?php echo $p; ?></span>
                    <?php else: ?>
                        <a href="<?php echo $url_pagina . $p; ?>" class="pagination-num" style="text-decoration:none;"><?php echo $p; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($fin_rango < $total_paginas): ?>
                    <?php if ($fin_rango < $total_paginas - 1): ?><span class="pagination-dots">...</span><?php endif; ?>
                    <a href="<?php echo $url_pagina . $total_paginas; ?>" class="pagination-num" style="text-decoration:none;"><?php echo $total_paginas; ?></a>
                <?php endif; ?>
            </div>

            <?php if ($pagina < $total_paginas): ?>
                <a href="<?php echo $url_pagina . ($pagina + 1); ?>" class="pagination-btn" style="text-decoration:none;">
                    Siguiente
                    <i data-lucide="chevron-right" class="w-4 h-4"></i>
                </a>
            <?php else: ?>
                <span class="pagination-btn" style="opacity:.5;cursor:not-allowed;">
                    Siguiente
                    <i data-lucide="chevron-right" class="w-4 h-4"></i>
                </span>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    </main>

    <?php include 'components/footer.php'; ?>

</body>
</html>
