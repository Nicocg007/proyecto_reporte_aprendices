<?php
require_once '../model/auth_helper.php';
require_once '../model/seguridad_helper.php';
requiereRoles(['Administrador', 'Instructor', 'Aprendiz']);

// conexion a la base de datos
require_once '../config/database.php';
require_once '../vendor/autoload.php';
$db = new Database();
$conn = $db->getConnection();

iniciarSesionSegura();

// mismos filtros que usa el reporte
$filtros = (new \App\Service\AttendanceService())->normalizeReportFilters($_GET);
$fecha_inicio = $filtros['fecha_inicio'];
$fecha_fin = $filtros['fecha_fin'];
$ficha = $filtros['ficha'];
$estado = $filtros['estado'];
$tipo = $_GET['tipo'] ?? 'excel';

$rol = $_SESSION['rol'] ?? '';
$filas = (new \App\Repository\AttendanceRepository($conn))->findReportRows(
    $filtros,
    $rol,
    (int) ($_SESSION['id_usuario'] ?? 0)
);

// excel: se descarga un csv que abre directo en excel
if ($tipo === 'excel') {
    $reportService = new \App\Service\ReportService();
    $nombre = $reportService->csvFilename(date('Y-m-d'));
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $nombre . '"');

    echo "\xEF\xBB\xBF";
    $salida = fopen('php://output', 'w');
    fputcsv($salida, $reportService->csvHeaders(), ';');
    foreach ($filas as $f) {
        fputcsv($salida, $reportService->csvRow($f), ';');
    }
    fclose($salida);
    exit();
}

$totales = (new \App\Service\AttendanceService())->countByStatus($filas);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Asistencias</title>
    <style>
        body { font-family: Arial, sans-serif; color: #1e293b; margin: 24px; }
        h1 { font-size: 20px; margin: 0 0 4px; }
        .info { font-size: 12px; color: #64748b; margin-bottom: 12px; }
        .totales { display: flex; gap: 18px; font-size: 12px; margin-bottom: 14px; }
        .totales span { font-weight: bold; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        th, td { border: 1px solid #cbd5e1; padding: 6px 8px; text-align: left; }
        th { background: #f1f5f9; }
        @media print { body { margin: 0; } }
    </style>
</head>
<body onload="window.print()">
    <h1>Reporte de Asistencias</h1>
    <div class="info">
        Generado: <?php echo e(date('Y-m-d H:i')); ?> |
        Fecha inicio: <?php echo $fecha_inicio !== '' ? e($fecha_inicio) : 'todas'; ?> |
        Fecha fin: <?php echo $fecha_fin !== '' ? e($fecha_fin) : 'todas'; ?> |
        Estado: <?php echo $estado !== '' ? e($estado) : 'todos'; ?>
    </div>
    <div class="totales">
        <div>Normales: <span><?php echo (int)$totales['Normal']; ?></span></div>
        <div>Retardos: <span><?php echo (int)$totales['Retardo']; ?></span></div>
        <div>Inasistencias: <span><?php echo (int)$totales['Inasistencia']; ?></span></div>
        <div>Salidas Tempranas: <span><?php echo (int)$totales['Salida Temprana']; ?></span></div>
    </div>
    <table>
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
            <?php if (!empty($filas)): ?>
                <?php foreach ($filas as $f): ?>
                    <tr>
                        <td><?php echo e($f['nombre']); ?></td>
                        <td><?php echo e($f['documento']); ?></td>
                        <td><?php echo e($f['ficha']); ?></td>
                        <td><?php echo e($f['fecha']); ?></td>
                        <td><?php echo e($f['entrada']); ?></td>
                        <td><?php echo e($f['salida']); ?></td>
                        <td><?php echo e($f['retardo']); ?></td>
                        <td><?php echo e($f['estado']); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="8">No hay registros para los filtros seleccionados</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
