<?php
require_once '../model/auth_helper.php';
require_once '../model/seguridad_helper.php';
requiereLogin();

// conexion a la base de datos
require_once '../config/database.php';
$db = new Database();
$conn = $db->getConnection();

iniciarSesionSegura();

// mismos filtros que usa el reporte
$fecha_inicio = trim($_GET['fecha_inicio'] ?? '');
$fecha_fin = trim($_GET['fecha_fin'] ?? '');
$ficha = $_GET['ficha'] ?? '';
$estado = trim($_GET['estado'] ?? '');
$tipo = $_GET['tipo'] ?? 'excel';

// estados validos del enum
$estados_validos = ['Normal', 'Retardo', 'Inasistencia', 'Salida Temprana'];
if ($estado !== '' && !in_array($estado, $estados_validos, true)) {
    $estado = '';
}

// fechas solo si parecen YYYY-MM-DD
if ($fecha_inicio !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_inicio)) {
    $fecha_inicio = '';
}
if ($fecha_fin !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_fin)) {
    $fecha_fin = '';
}

$sql = "SELECT CONCAT(u.nombre, ' ', u.apellido) AS nombre, u.numero_documento AS documento,
    f.codigo_ficha AS ficha, i.fecha, i.hora_entrada_registrada AS entrada,
    i.hora_salida_registrada AS salida, i.minutos_retardo AS retardo, i.estado_asistencia AS estado
    FROM ingreso i
    INNER JOIN usuario u ON u.id_usuario = i.id_aprendiz
    INNER JOIN usuario_has_ficha uf ON uf.id_aprendiz = u.id_usuario
    INNER JOIN ficha f ON f.id_ficha = uf.id_ficha";

$condiciones = [];
$params = [];

if ($fecha_inicio !== '') {
    $condiciones[] = "i.fecha >= :fecha_inicio";
    $params[':fecha_inicio'] = $fecha_inicio;
}
if ($fecha_fin !== '') {
    $condiciones[] = "i.fecha <= :fecha_fin";
    $params[':fecha_fin'] = $fecha_fin;
}
if ($ficha !== '') {
    $condiciones[] = "uf.id_ficha = :ficha";
    $params[':ficha'] = (int)$ficha;
}
if ($estado !== '') {
    $condiciones[] = "i.estado_asistencia = :estado";
    $params[':estado'] = $estado;
}

// alcance por rol (se refuerza en commit de autorizacion; aqui base segura)
$rol = $_SESSION['rol'] ?? '';
if ($rol === 'Aprendiz') {
    $condiciones[] = "i.id_aprendiz = :id_aprendiz";
    $params[':id_aprendiz'] = (int)$_SESSION['id_usuario'];
} elseif ($rol === 'Instructor') {
    $condiciones[] = "f.id_instructor_encargado = :id_instructor";
    $params[':id_instructor'] = (int)$_SESSION['id_usuario'];
}

if (count($condiciones) > 0) {
    $sql .= " WHERE " . implode(' AND ', $condiciones);
}
$sql .= " ORDER BY i.fecha DESC, i.hora_entrada_registrada DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$filas = $stmt->fetchAll();

// excel: se descarga un csv que abre directo en excel
if ($tipo === 'excel') {
    $nombre = 'reporte_asistencias_' . date('Y-m-d') . '.csv';
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $nombre . '"');

    echo "\xEF\xBB\xBF";
    $salida = fopen('php://output', 'w');
    fputcsv($salida, ['Aprendiz', 'Documento', 'Ficha', 'Fecha', 'Entrada', 'Salida', 'Retardo (min)', 'Estado'], ';');
    foreach ($filas as $f) {
        fputcsv($salida, [$f['nombre'], $f['documento'], $f['ficha'], $f['fecha'], $f['entrada'], $f['salida'], $f['retardo'], $f['estado']], ';');
    }
    fclose($salida);
    exit();
}

$totales = ['Normal' => 0, 'Retardo' => 0, 'Inasistencia' => 0, 'Salida Temprana' => 0];
foreach ($filas as $f) {
    if (isset($totales[$f['estado']])) $totales[$f['estado']]++;
}
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
