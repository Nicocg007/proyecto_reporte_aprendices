<?php
require_once '../model/auth_helper.php';
require_once '../model/seguridad_helper.php';
requiereRoles(['Administrador', 'Instructor', 'Aprendiz']);

// conexion a la base de datos
require_once '../config/database.php';
$db = new Database();
$conn = $db->getConnection();

$rol_actual = $_SESSION['rol'] ?? '';
$id_usuario = (int)($_SESSION['id_usuario'] ?? 0);

// lista de fichas para el filtro (instructor solo las suyas)
if ($rol_actual === 'Instructor') {
    $stmt = $conn->prepare("SELECT id_ficha, codigo_ficha, nombre_programa FROM ficha WHERE id_instructor_encargado = ? ORDER BY codigo_ficha");
    $stmt->execute([$id_usuario]);
} else {
    $stmt = $conn->query("SELECT id_ficha, codigo_ficha, nombre_programa FROM ficha ORDER BY codigo_ficha");
}
$lista_fichas = $stmt->fetchAll();

// filtros de busqueda
$fecha_inicio = trim($_GET['fecha_inicio'] ?? '');
$fecha_fin = trim($_GET['fecha_fin'] ?? '');
$ficha = $_GET['ficha'] ?? '';
$estado = trim($_GET['estado'] ?? '');

$estados_validos = ['Normal', 'Retardo', 'Inasistencia', 'Salida Temprana'];
if ($estado !== '' && !in_array($estado, $estados_validos, true)) {
    $estado = '';
}
if ($fecha_inicio !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_inicio)) {
    $fecha_inicio = '';
}
if ($fecha_fin !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_fin)) {
    $fecha_fin = '';
}

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
$pagina = max(1, (int)($_GET['pagina'] ?? 1));

$condiciones = [];
$params = [];
if ($fecha_inicio != '') {
    $condiciones[] = "i.fecha >= :fecha_inicio";
    $params[':fecha_inicio'] = $fecha_inicio;
}
if ($fecha_fin != '') {
    $condiciones[] = "i.fecha <= :fecha_fin";
    $params[':fecha_fin'] = $fecha_fin;
}
if ($ficha != '') {
    $condiciones[] = "uf.id_ficha = :ficha";
    $params[':ficha'] = (int)$ficha;
}
if ($estado != '') {
    $condiciones[] = "i.estado_asistencia = :estado";
    $params[':estado'] = $estado;
}

// alcance por rol
if ($rol_actual === 'Aprendiz') {
    $condiciones[] = "i.id_aprendiz = :id_aprendiz";
    $params[':id_aprendiz'] = $id_usuario;
} elseif ($rol_actual === 'Instructor') {
    $condiciones[] = "f.id_instructor_encargado = :id_instructor";
    $params[':id_instructor'] = $id_usuario;
}

$where = count($condiciones) > 0 ? " WHERE " . implode(' AND ', $condiciones) : '';

$from = " FROM ingreso i
    INNER JOIN usuario u ON u.id_usuario = i.id_aprendiz
    INNER JOIN usuario_has_ficha uf ON uf.id_aprendiz = u.id_usuario
    INNER JOIN ficha f ON f.id_ficha = uf.id_ficha";

$stmt = $conn->prepare("SELECT COUNT(*) AS total" . $from . $where);
$stmt->execute($params);
$total_registros = $stmt->fetch()['total'];
$total_paginas = max(1, (int)ceil($total_registros / $por_pagina));

if ($pagina > $total_paginas) {
    $pagina = $total_paginas;
}
$offset = ($pagina - 1) * $por_pagina;

$sql = "SELECT CONCAT(u.nombre, ' ', u.apellido) AS nombre, u.numero_documento AS documento,
    f.codigo_ficha AS ficha, i.fecha, i.hora_entrada_registrada AS entrada,
    i.hora_salida_registrada AS salida, i.minutos_retardo AS retardo, i.estado_asistencia AS estado"
    . $from . $where . "
    ORDER BY i.fecha DESC, i.hora_entrada_registrada DESC
    LIMIT :limite OFFSET :offset";
$stmt = $conn->prepare($sql);
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->bindValue(':limite', $por_pagina, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$reporte_asistencias = $stmt->fetchAll();

$total_normales = 0;
$total_retardos = 0;
$total_inasistencias = 0;
$total_salidas = 0;
$stmt = $conn->prepare("SELECT i.estado_asistencia AS estado, COUNT(*) AS total" . $from . $where . " GROUP BY i.estado_asistencia");
$stmt->execute($params);
foreach ($stmt->fetchAll() as $fila) {
    switch ($fila['estado']) {
        case 'Normal': $total_normales = $fila['total']; break;
        case 'Retardo': $total_retardos = $fila['total']; break;
        case 'Inasistencia': $total_inasistencias = $fila['total']; break;
        case 'Salida Temprana': $total_salidas = $fila['total']; break;
    }
}

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
