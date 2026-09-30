<?php
require_once '../model/auth_helper.php';
requiereLogin();

// conexion a la base de datos
require_once '../config/database.php';
$db = new Database();
$conn = $db->getConnection();

// mismos filtros que usa el reporte
$fecha_inicio = $_GET['fecha_inicio'] ?? '';
$fecha_fin = $_GET['fecha_fin'] ?? '';
$ficha = $_GET['ficha'] ?? '';
$estado = $_GET['estado'] ?? '';
$tipo = $_GET['tipo'] ?? 'excel';

// consulta del reporte con las condiciones activas
$sql = "SELECT CONCAT(u.nombre, ' ', u.apellido) AS nombre, u.numero_documento AS documento,
    f.codigo_ficha AS ficha, i.fecha, i.hora_entrada_registrada AS entrada,
    i.hora_salida_registrada AS salida, i.minutos_retardo AS retardo, i.estado_asistencia AS estado
    FROM ingreso i
    INNER JOIN usuario u ON u.id_usuario = i.id_aprendiz
    INNER JOIN usuario_has_ficha uf ON uf.id_aprendiz = u.id_usuario
    INNER JOIN ficha f ON f.id_ficha = uf.id_ficha";

$condiciones = [];
if ($fecha_inicio != '') $condiciones[] = "i.fecha >= '$fecha_inicio'";
if ($fecha_fin != '') $condiciones[] = "i.fecha <= '$fecha_fin'";
if ($ficha != '') $condiciones[] = "uf.id_ficha = " . (int)$ficha;
if ($estado != '') $condiciones[] = "i.estado_asistencia = '$estado'";
if (count($condiciones) > 0) $sql .= " WHERE " . implode(' AND ', $condiciones);
$sql .= " ORDER BY i.fecha DESC, i.hora_entrada_registrada DESC";
$stmt = $conn->query($sql);
$filas = $stmt->fetchAll();

// excel: se descarga un csv que abre directo en excel
if ($tipo === 'excel') {
    $nombre = 'reporte_asistencias_' . date('Y-m-d') . '.csv';
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $nombre . '"');

    // el bom hace que excel reconozca los acentos
    echo "\xEF\xBB\xBF";
    $salida = fopen('php://output', 'w');
    fputcsv($salida, ['Aprendiz', 'Documento', 'Ficha', 'Fecha', 'Entrada', 'Salida', 'Retardo (min)', 'Estado'], ';');
    foreach ($filas as $f) {
        fputcsv($salida, [$f['nombre'], $f['documento'], $f['ficha'], $f['fecha'], $f['entrada'], $f['salida'], $f['retardo'], $f['estado']], ';');
    }
    fclose($salida);
    exit();
}

// totales por estado para el encabezado del pdf
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
        Generado: <?php echo date('Y-m-d H:i'); ?> |
        Fecha inicio: <?php echo $fecha_inicio !== '' ? $fecha_inicio : 'todas'; ?> |
        Fecha fin: <?php echo $fecha_fin !== '' ? $fecha_fin : 'todas'; ?> |
        Estado: <?php echo $estado !== '' ? $estado : 'todos'; ?>
    </div>
    <div class="totales">
        <div>Normales: <span><?php echo $totales['Normal']; ?></span></div>
        <div>Retardos: <span><?php echo $totales['Retardo']; ?></span></div>
        <div>Inasistencias: <span><?php echo $totales['Inasistencia']; ?></span></div>
        <div>Salidas Tempranas: <span><?php echo $totales['Salida Temprana']; ?></span></div>
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
                        <td><?php echo $f['nombre']; ?></td>
                        <td><?php echo $f['documento']; ?></td>
                        <td><?php echo $f['ficha']; ?></td>
                        <td><?php echo $f['fecha']; ?></td>
                        <td><?php echo $f['entrada']; ?></td>
                        <td><?php echo $f['salida']; ?></td>
                        <td><?php echo $f['retardo']; ?></td>
                        <td><?php echo $f['estado']; ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="8">No hay registros para los filtros seleccionados</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
