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
$scheduleRepository = new \App\Repository\ScheduleRepository($conn);
$scheduleService = new \App\Service\ScheduleService();

// el instructor solo ve sus propias clases
$filtro_instructor = null;
if ($rol_actual === 'Instructor') {
    $filtro_instructor = trim(($_SESSION['nombre'] ?? '') . ' ' . ($_SESSION['apellido'] ?? ''));
}

// el aprendiz solo ve el horario de su ficha
$ficha_aprendiz = null;
if ($rol_actual === 'Aprendiz') {
    $ficha_aprendiz = $scheduleRepository->findLearnerFichaId((int) $_SESSION['id_usuario']);
}

$fichas = $scheduleRepository->findFichasWithSchedule();

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
    $ficha_actual = $scheduleRepository->findFicha($id_ficha);
    $rango = $scheduleRepository->findRange($id_ficha, $filtro_instructor);
    if (!empty($rango['hi'])) {
        $hora_min = $rango['hi'];
        $hora_max = $rango['hf'];
    }
    $fecha_min = $rango['fi'];
    $fecha_max = $rango['ff'];
}

$week = $scheduleService->buildWeek($_GET['ini'] ?? '', $fecha_min, $fecha_max);
$lunes = $week['monday'];
$sabado = $week['saturday'];
$hay_prev = $week['has_previous'];
$hay_next = $week['has_next'];
$weekRows = $ficha_actual
    ? $scheduleRepository->findWeekRows(
        $id_ficha,
        $lunes->format('Y-m-d'),
        $sabado->format('Y-m-d'),
        $filtro_instructor
    )
    : [];
$grid = $scheduleService->buildGrid($weekRows, $hora_min, $hora_max);
$mapa = $grid['map'];
$festivos = $grid['holidays'];
$horas = $grid['hours'];
$instructores_semana = $grid['instructors'];

$nombres_dias = ['Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado'];

$paleta = ['#e0f2fe', '#dcfce7', '#fef9c3', '#fee2e2', '#f3e8ff', '#ffedd5', '#ccfbf1', '#e2e8f0'];

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
                                                <td style="background: <?php echo $scheduleService->colorForInstructor($clase['instructor'], $paleta); ?>;">
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
                                <span style="background: <?php echo $scheduleService->colorForInstructor($nombre_instructor, $paleta); ?>;">
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
