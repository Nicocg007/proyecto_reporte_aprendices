<?php
require_once '../model/auth_helper.php';
requiereLogin();

// admin, instructor y aprendiz pueden ver el horario
$rol_actual = $_SESSION['rol'] ?? '';
if (!in_array($rol_actual, ['Administrador', 'Instructor', 'Aprendiz'])) {
    header('Location: login.php');
    exit();
}

// conexion a la base de datos
require_once '../config/database.php';
$db = new Database();
$conn = $db->getConnection();

// el instructor solo ve sus propias clases
$filtro_instructor = null;
if ($rol_actual === 'Instructor') {
    $filtro_instructor = trim(($_SESSION['nombre'] ?? '') . ' ' . ($_SESSION['apellido'] ?? ''));
}

// el aprendiz solo ve el horario de su ficha
$ficha_aprendiz = null;
if ($rol_actual === 'Aprendiz') {
    $stmt = $conn->prepare("SELECT f.id_ficha
        FROM usuario_has_ficha uf
        INNER JOIN ficha f ON f.id_ficha = uf.id_ficha
        WHERE uf.id_aprendiz = ?
        ORDER BY f.codigo_ficha LIMIT 1");
    $stmt->execute([$_SESSION['id_usuario']]);
    $fila = $stmt->fetch();
    if ($fila) {
        $ficha_aprendiz = (int)$fila['id_ficha'];
    }
}

// fichas que tienen horario cargado
$fichas = $conn->query("SELECT DISTINCT f.id_ficha, f.codigo_ficha, f.nombre_programa
    FROM ficha f
    INNER JOIN horario h ON h.id_ficha = f.id_ficha
    ORDER BY f.codigo_ficha")->fetchAll();

// ficha seleccionada
if ($rol_actual === 'Aprendiz') {
    $id_ficha = $ficha_aprendiz ?: 0;
} else {
    $id_ficha = (int)($_GET['ficha'] ?? 0);
    if ($id_ficha === 0 && !empty($fichas)) {
        $id_ficha = (int)$fichas[0]['id_ficha'];
    }
}

// datos de la ficha y rango de horas y fechas
$ficha_actual = null;
$hora_min = '06:00:00';
$hora_max = '12:00:00';
$fecha_min = null;
$fecha_max = null;

if ($id_ficha > 0) {
    $stmt = $conn->prepare("SELECT id_ficha, codigo_ficha, nombre_programa, hora_entrada, hora_salida
        FROM ficha WHERE id_ficha = ?");
    $stmt->execute([$id_ficha]);
    $ficha_actual = $stmt->fetch();

    $sql_rango = "SELECT MIN(hora_inicio) AS hi, MAX(hora_fin) AS hf, MIN(fecha) AS fi, MAX(fecha) AS ff
        FROM horario WHERE id_ficha = ?";
    $params_rango = [$id_ficha];
    if ($filtro_instructor !== null) {
        $sql_rango .= " AND (instructor LIKE ? OR es_festivo = 1)";
        $params_rango[] = $filtro_instructor . '%';
    }
    $stmt = $conn->prepare($sql_rango);
    $stmt->execute($params_rango);
    $rango = $stmt->fetch();
    if (!empty($rango['hi'])) {
        $hora_min = $rango['hi'];
        $hora_max = $rango['hf'];
    }
    $fecha_min = $rango['fi'];
    $fecha_max = $rango['ff'];
}

// lunes de la semana a mostrar
$ini = $_GET['ini'] ?? '';
if ($ini !== '') {
    $lunes = new DateTime($ini);
} elseif ($fecha_min) {
    $lunes = new DateTime($fecha_min);
} else {
    $lunes = new DateTime('monday this week');
}
$lunes->modify('monday this week');
$sabado = (clone $lunes)->modify('+5 days');

// si hay semanas antes o despues
$hay_prev = $fecha_min && $lunes->format('Y-m-d') > $fecha_min;
$hay_next = $fecha_max && $sabado->format('Y-m-d') < $fecha_max;

// armar el mapa de la semana: [dia][hora] y dias festivos
$mapa = [];
$festivos = [];
if ($ficha_actual) {
    $sql = "SELECT fecha, hora_inicio, instructor, competencia, es_festivo
        FROM horario
        WHERE id_ficha = ? AND fecha BETWEEN ? AND ?";
    $params = [$id_ficha, $lunes->format('Y-m-d'), $sabado->format('Y-m-d')];
    if ($filtro_instructor !== null) {
        $sql .= " AND (instructor LIKE ? OR es_festivo = 1)";
        $params[] = $filtro_instructor . '%';
    }
    $sql .= " ORDER BY fecha, hora_inicio";
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    foreach ($stmt->fetchAll() as $fila) {
        $dia = (int)date('N', strtotime($fila['fecha'])) - 1; // 0 lunes ... 5 sabado
        if ($dia < 0 || $dia > 5) {
            continue;
        }
        if ((int)$fila['es_festivo'] === 1) {
            $festivos[$dia] = true;
            continue;
        }
        $mapa[$dia][substr($fila['hora_inicio'], 0, 5)] = $fila;
    }
}

// horas del grid de una en una
$horas = [];
$t = strtotime($hora_min);
$fin = strtotime($hora_max);
while ($t < $fin) {
    $horas[] = date('H:i', $t);
    $t = strtotime('+1 hour', $t);
}

$nombres_dias = ['Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado'];

// color estable por instructor para ver el flujo
$paleta = ['#e0f2fe', '#dcfce7', '#fef9c3', '#fee2e2', '#f3e8ff', '#ffedd5', '#ccfbf1', '#e2e8f0'];
function color_instructor($nombre, $paleta) {
    return $paleta[crc32($nombre) % count($paleta)];
}

// instructores de la semana para la convencion
$instructores_semana = [];
foreach ($mapa as $por_hora) {
    foreach ($por_hora as $fila) {
        $instructores_semana[$fila['instructor']] = true;
    }
}
ksort($instructores_semana);

// navbar y sidebar segun el rol
if ($rol_actual === 'Administrador') {
    $sidebar = 'components/sidebar.php';
    $navbar = 'components/navbar.php';
} elseif ($rol_actual === 'Instructor') {
    $sidebar = 'components/sidebar_instructor.php';
    $navbar = 'components/navbar_instructor.php';
} else {
    $sidebar = 'components/sidebar_aprendiz.php';
    $navbar = 'components/navbar_aprendiz.php';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php include 'components/header.php'; ?>
    <style>
        .horario-tabla { width: 100%; border-collapse: collapse; font-size: 12px; }
        .horario-tabla th { background: #f1f5f9; padding: 8px; text-align: center; border: 1px solid #e2e8f0; font-weight: 600; }
        .horario-tabla td { border: 1px solid #e2e8f0; padding: 6px; vertical-align: top; height: 44px; }
        .horario-hora { background: #f8fafc; font-weight: 600; text-align: center; width: 70px; color: #475569; }
        .horario-inst { font-weight: 600; color: #1e293b; line-height: 1.2; }
        .horario-comp { color: #64748b; font-size: 10px; line-height: 1.2; margin-top: 2px; }
        .horario-festivo { background: #fee2e2; color: #b91c1c; font-weight: 700; text-align: center; }
        .horario-vacio { color: #cbd5e1; text-align: center; }
        .horario-leyenda { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 12px; }
        .horario-leyenda span { padding: 4px 10px; border-radius: 999px; font-size: 11px; color: #1e293b; }
        .horario-nav { display: flex; align-items: center; gap: 10px; }
    </style>
</head>
<body>

    <?php include $sidebar; ?>

    <?php include $navbar; ?>

    <main class="main-content">

        <div class="dashboard-card">
            <div class="card-header">
                <h2 class="card-title">
                    <?php echo $rol_actual === 'Instructor' ? 'Mi Horario' : 'Horario de Formacion'; ?>
                </h2>
                <div class="horario-nav">
                    <?php if ($hay_prev): ?>
                        <a class="btn-filter-primary" style="text-decoration:none;"
                           href="horario.php?ficha=<?php echo $id_ficha; ?>&ini=<?php echo (clone $lunes)->modify('-7 days')->format('Y-m-d'); ?>">
                            <i data-lucide="chevron-left" class="w-4 h-4"></i> Anterior
                        </a>
                    <?php endif; ?>
                    <span class="font-medium">
                        <?php echo $lunes->format('d/m/Y'); ?> - <?php echo $sabado->format('d/m/Y'); ?>
                    </span>
                    <?php if ($hay_next): ?>
                        <a class="btn-filter-primary" style="text-decoration:none;"
                           href="horario.php?ficha=<?php echo $id_ficha; ?>&ini=<?php echo (clone $lunes)->modify('+7 days')->format('Y-m-d'); ?>">
                            Siguiente <i data-lucide="chevron-right" class="w-4 h-4"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card-body">
                <?php if ($rol_actual !== 'Aprendiz' && !empty($fichas)): ?>
                    <div class="filter-actions" style="margin-bottom: 15px; gap: 8px; flex-wrap: wrap;">
                        <?php foreach ($fichas as $ficha_opcion): ?>
                            <a class="btn-filter-primary" style="text-decoration:none; <?php echo ((int)$ficha_opcion['id_ficha'] === $id_ficha) ? '' : 'background:#e2e8f0;color:#1e293b;'; ?>"
                               href="horario.php?ficha=<?php echo $ficha_opcion['id_ficha']; ?>">
                                Ficha <?php echo $ficha_opcion['codigo_ficha']; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (!$ficha_actual): ?>
                    <p class="text-center text-slate-400" style="padding: 30px;">
                        <?php echo $rol_actual === 'Aprendiz' ? 'Todavia no tienes una ficha asignada.' : 'No hay horarios cargados todavia.'; ?>
                    </p>
                <?php elseif (!$fecha_min): ?>
                    <p class="text-center text-slate-400" style="padding: 30px;">
                        <?php echo $rol_actual === 'Instructor' ? 'No tienes clases en este horario.' : 'Esta ficha aun no tiene horario cargado.'; ?>
                    </p>
                <?php else: ?>
                    <p class="text-slate-500" style="margin-bottom: 10px;">
                        <strong><?php echo htmlspecialchars($ficha_actual['nombre_programa']); ?></strong>
                        (<?php echo htmlspecialchars($ficha_actual['codigo_ficha']); ?>)
                        - jornada <?php echo substr($hora_min, 0, 5); ?> a <?php echo substr($hora_max, 0, 5); ?>
                    </p>

                    <div style="overflow-x: auto;">
                        <table class="horario-tabla">
                            <thead>
                                <tr>
                                    <th class="horario-hora">Hora</th>
                                    <?php for ($d = 0; $d < 6; $d++): ?>
                                        <th>
                                            <?php echo $nombres_dias[$d]; ?><br>
                                            <span style="font-weight:400;color:#64748b;"><?php echo (clone $lunes)->modify('+' . $d . ' days')->format('d/m'); ?></span>
                                        </th>
                                    <?php endfor; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($horas as $hora): ?>
                                    <tr>
                                        <td class="horario-hora"><?php echo $hora; ?></td>
                                        <?php for ($d = 0; $d < 6; $d++): ?>
                                            <?php if (isset($festivos[$d])): ?>
                                                <td class="horario-festivo">Festivo</td>
                                            <?php elseif (isset($mapa[$d][$hora])): ?>
                                                <?php $clase = $mapa[$d][$hora]; ?>
                                                <td style="background: <?php echo color_instructor($clase['instructor'], $paleta); ?>;">
                                                    <div class="horario-inst"><?php echo htmlspecialchars($clase['instructor']); ?></div>
                                                    <?php if (!empty($clase['competencia'])): ?>
                                                        <div class="horario-comp"><?php echo htmlspecialchars($clase['competencia']); ?></div>
                                                    <?php endif; ?>
                                                </td>
                                            <?php else: ?>
                                                <td class="horario-vacio">-</td>
                                            <?php endif; ?>
                                        <?php endfor; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if (!empty($instructores_semana)): ?>
                        <div class="horario-leyenda">
                            <?php foreach (array_keys($instructores_semana) as $nombre_instructor): ?>
                                <span style="background: <?php echo color_instructor($nombre_instructor, $paleta); ?>;">
                                    <?php echo htmlspecialchars($nombre_instructor); ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

    </main>

    <?php include 'components/footer.php'; ?>

</body>
</html>
