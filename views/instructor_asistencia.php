<?php
require_once '../model/auth_helper.php';
requiereLogin();
requiereRol('Instructor');

// conexion a la base de datos
require_once '../config/database.php';
$db = new Database();
$conn = $db->getConnection();

// id del instructor logueado
$id = $_SESSION['id_usuario'];

// fichas del instructor para el selector
$stmt = $conn->prepare("SELECT id_ficha, codigo_ficha FROM ficha WHERE id_instructor_encargado = ? ORDER BY codigo_ficha");
$stmt->execute([$id]);
$lista_fichas = $stmt->fetchAll();

// opciones de estado que acepta la tabla ingreso
$estados_opciones = ['Normal', 'Retardo', 'Inasistencia', 'Salida Temprana'];

// guardar la asistencia manual que llega del formulario
if (isset($_POST['guardar_asistencia'])) {
    $id_ficha = $_POST['id_ficha'] ?? '';
    $fecha = $_POST['fecha'] ?? '';

    // la ficha debe ser de este instructor
    $stmt = $conn->prepare("SELECT hora_entrada FROM ficha WHERE id_ficha = ? AND id_instructor_encargado = ?");
    $stmt->execute([$id_ficha, $id]);
    $ficha_guardar = $stmt->fetch();

    if (!$ficha_guardar || $fecha === '') {
        header('Location: instructor_asistencia.php?error=1');
        exit();
    }

    $estados = $_POST['estado'] ?? [];
    $entradas = $_POST['entrada'] ?? [];
    $salidas = $_POST['salida'] ?? [];

    // hora de entrada de la ficha en minutos para calcular el retardo
    $min_ficha = (int)substr($ficha_guardar['hora_entrada'], 0, 2) * 60 + (int)substr($ficha_guardar['hora_entrada'], 3, 2);

    foreach ($estados as $id_aprendiz => $estado) {
        $hora_entrada = $entradas[$id_aprendiz] ?? '';
        $hora_salida = $salidas[$id_aprendiz] ?? '';
        $hora_entrada = $hora_entrada !== '' ? $hora_entrada : null;
        $hora_salida = $hora_salida !== '' ? $hora_salida : null;
        $estado = in_array($estado, $estados_opciones) ? $estado : 'Inasistencia';

        // minutos de retardo comparando con la hora de entrada de la ficha
        $retardo = 0;
        if ($hora_entrada !== null && $estado !== 'Inasistencia') {
            $min_entrada = (int)substr($hora_entrada, 0, 2) * 60 + (int)substr($hora_entrada, 3, 2);
            if ($min_entrada > $min_ficha) {
                $retardo = $min_entrada - $min_ficha;
            }
        }

        // marca si la salida fue temprana
        $salida_temprana = $estado === 'Salida Temprana' ? 1 : 0;

        // si ya existe un ingreso de ese aprendiz ese dia se actualiza
        $stmt = $conn->prepare("SELECT id_ingreso FROM ingreso WHERE id_aprendiz = ? AND fecha = ?");
        $stmt->execute([$id_aprendiz, $fecha]);
        $existe = $stmt->fetch();

        if ($existe) {
            $stmt = $conn->prepare("UPDATE ingreso
                SET hora_entrada_registrada = ?, hora_salida_registrada = ?, minutos_retardo = ?, salida_temprana = ?, estado_asistencia = ?
                WHERE id_ingreso = ?");
            $stmt->execute([$hora_entrada, $hora_salida, $retardo, $salida_temprana, $estado, $existe['id_ingreso']]);
        } else {
            $stmt = $conn->prepare("INSERT INTO ingreso
                (id_aprendiz, fecha, hora_entrada_registrada, hora_salida_registrada, minutos_retardo, salida_temprana, estado_asistencia)
                VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$id_aprendiz, $fecha, $hora_entrada, $hora_salida, $retardo, $salida_temprana, $estado]);
        }
    }

    header('Location: instructor_asistencia.php?guardado=1');
    exit();
}

// ficha y fecha elegidas para marcar la asistencia
$id_ficha_sel = $_POST['id_ficha'] ?? '';
$fecha_sel = $_POST['fecha'] ?? date('Y-m-d');

// datos de la ficha seleccionada, solo si es de este instructor
$ficha_sel = null;
if ($id_ficha_sel != '') {
    $stmt = $conn->prepare("SELECT id_ficha, codigo_ficha, hora_entrada FROM ficha WHERE id_ficha = ? AND id_instructor_encargado = ?");
    $stmt->execute([$id_ficha_sel, $id]);
    $ficha_sel = $stmt->fetch();
}

// aprendices de la ficha con su ingreso de ese dia si ya existe
$aprendices = [];
if ($ficha_sel) {
    $stmt = $conn->prepare("SELECT u.id_usuario, u.numero_documento, u.nombre, u.apellido,
        i.hora_entrada_registrada, i.hora_salida_registrada, i.estado_asistencia
        FROM usuario_has_ficha uf
        INNER JOIN usuario u ON u.id_usuario = uf.id_aprendiz
        LEFT JOIN ingreso i ON i.id_aprendiz = u.id_usuario AND i.fecha = ?
        WHERE uf.id_ficha = ? AND u.id_rol = 3
        ORDER BY u.nombre");
    $stmt->execute([$fecha_sel, $id_ficha_sel]);
    $aprendices = $stmt->fetchAll();
}

// hora de entrada que se propone en el formulario
$hora_entrada_ficha = $ficha_sel ? substr($ficha_sel['hora_entrada'], 0, 5) : '';

// asistencias ya registradas en las fichas del instructor
$stmt = $conn->prepare("SELECT CONCAT(u.nombre, ' ', u.apellido) AS nombre, f.codigo_ficha AS ficha, i.fecha,
    i.hora_entrada_registrada AS entrada, i.hora_salida_registrada AS salida, i.estado_asistencia AS estado
    FROM ingreso i
    INNER JOIN usuario u ON u.id_usuario = i.id_aprendiz
    INNER JOIN usuario_has_ficha uf ON uf.id_aprendiz = u.id_usuario
    INNER JOIN ficha f ON f.id_ficha = uf.id_ficha
    WHERE f.id_instructor_encargado = ?
    ORDER BY i.fecha DESC, i.hora_entrada_registrada DESC");
$stmt->execute([$id]);
$asistencia_registrada = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php include 'components/header.php'; ?>
</head>
<body>

    <?php include 'components/sidebar_instructor.php'; ?>

    <?php include 'components/navbar_instructor.php'; ?>

    <main class="main-content">

        <div class="dashboard-card" style="margin-bottom: 20px;">
            <div class="card-header">
                <h2 class="card-title">Registrar Asistencia</h2>
            </div>
            <div class="card-body">
                <form method="POST" action="instructor_asistencia.php">
                    <div class="filter-grid" style="grid-template-columns: repeat(3, 1fr);">
                        <div class="filter-group">
                            <label class="filter-label">Ficha</label>
                            <select name="id_ficha" class="filter-input" required>
                                <option value="">Selecciona ficha</option>
                                <?php foreach ($lista_fichas as $ficha): ?>
                                    <option value="<?php echo $ficha['id_ficha']; ?>" <?php echo ($id_ficha_sel == $ficha['id_ficha']) ? 'selected' : ''; ?>><?php echo $ficha['codigo_ficha']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label class="filter-label">Fecha</label>
                            <input type="date" name="fecha" class="filter-input" value="<?php echo $fecha_sel; ?>" required>
                        </div>
                        <div class="filter-group filter-actions">
                            <button type="submit" name="cargar" value="1" class="btn-filter-primary">
                                <i data-lucide="clipboard-check" class="w-4 h-4"></i>
                                Cargar Aprendices
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <?php if ($ficha_sel): ?>
        <div class="dashboard-card" style="margin-bottom: 20px;">
            <div class="card-header">
                <h2 class="card-title">Marcar Asistencia - <?php echo $ficha_sel['codigo_ficha']; ?> (<?php echo $fecha_sel; ?>)</h2>
            </div>
            <div class="card-body" style="padding: 0;">
                <form method="POST" action="instructor_asistencia.php">
                    <input type="hidden" name="id_ficha" value="<?php echo $ficha_sel['id_ficha']; ?>">
                    <input type="hidden" name="fecha" value="<?php echo $fecha_sel; ?>">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Aprendiz</th>
                                <th>Documento</th>
                                <th>Estado</th>
                                <th>Entrada</th>
                                <th>Salida</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($aprendices)): ?>
                                <?php foreach ($aprendices as $a): ?>
                                    <?php
                                    $estado_actual = $a['estado_asistencia'] ?? 'Normal';
                                    $entrada_actual = $a['hora_entrada_registrada'] ? substr($a['hora_entrada_registrada'], 0, 5) : $hora_entrada_ficha;
                                    $salida_actual = $a['hora_salida_registrada'] ? substr($a['hora_salida_registrada'], 0, 5) : '';
                                    ?>
                                    <tr>
                                        <td class="font-medium"><?php echo $a['nombre'] . ' ' . $a['apellido']; ?></td>
                                        <td><?php echo $a['numero_documento']; ?></td>
                                        <td>
                                            <select name="estado[<?php echo $a['id_usuario']; ?>]" class="filter-input">
                                                <?php foreach ($estados_opciones as $opcion): ?>
                                                    <option value="<?php echo $opcion; ?>" <?php echo ($estado_actual == $opcion) ? 'selected' : ''; ?>><?php echo $opcion; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="time" name="entrada[<?php echo $a['id_usuario']; ?>]" class="filter-input" value="<?php echo $entrada_actual; ?>">
                                        </td>
                                        <td>
                                            <input type="time" name="salida[<?php echo $a['id_usuario']; ?>]" class="filter-input" value="<?php echo $salida_actual; ?>">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-8 text-slate-400">Esta ficha no tiene aprendices asignados</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <?php if (!empty($aprendices)): ?>
                    <div class="filter-actions" style="padding: 15px;">
                        <button type="submit" name="guardar_asistencia" value="1" class="btn-filter-primary">
                            <i data-lucide="save" class="w-4 h-4"></i>
                            Guardar Asistencia
                        </button>
                    </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <div class="dashboard-card">
            <div class="card-header">
                <h2 class="card-title">Asistencia Registrada</h2>
            </div>
            <div class="card-body" style="padding: 0;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Aprendiz</th>
                            <th>Ficha</th>
                            <th>Fecha</th>
                            <th>Entrada</th>
                            <th>Salida</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($asistencia_registrada)): ?>
                            <?php foreach ($asistencia_registrada as $registro): ?>
                                <tr>
                                    <td class="font-medium"><?php echo $registro['nombre']; ?></td>
                                    <td><?php echo $registro['ficha']; ?></td>
                                    <td><?php echo $registro['fecha']; ?></td>
                                    <td><?php echo $registro['entrada'] ?? '--'; ?></td>
                                    <td><?php echo $registro['salida'] ?? '--'; ?></td>
                                    <td><span class="badge-status badge-<?php echo strtolower($registro['estado']); ?>"><?php echo $registro['estado']; ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-8 text-slate-400">No hay registros de asistencia</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>

    <?php include 'components/footer.php'; ?>

    <script>
    // mensajes que llegan por la url
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('guardado') === '1') {
        SenaToast.success('Listo', 'Asistencia guardada correctamente');
    }
    if (urlParams.get('error') === '1') {
        SenaToast.error('Error', 'Selecciona una ficha y una fecha validas');
    }
    </script>

</body>
</html>
