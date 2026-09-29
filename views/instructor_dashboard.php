<?php
require_once '../model/auth_helper.php';
requiereLogin();
requiereRol('Instructor');

// conexion a la base de datos
require_once '../config/database.php';
$db = new Database();
$conn = $db->getConnection();

// id del instructor logueado (de la sesion)
$id = $_SESSION['id_usuario'];

// mis aprendices (los que estan en las fichas que yo dirijo)
$stmt = $conn->query("SELECT COUNT(DISTINCT uf.id_aprendiz) AS total
    FROM usuario_has_ficha uf
    INNER JOIN ficha f ON f.id_ficha = uf.id_ficha
    WHERE f.id_instructor_encargado = $id");
$mis_aprendices = $stmt->fetch()['total'];

// asistencias de hoy
$stmt = $conn->query("SELECT COUNT(*) AS total FROM ingreso WHERE fecha = CURDATE()");
$asistencias_hoy = $stmt->fetch()['total'];

// retardos de hoy
$stmt = $conn->query("SELECT COUNT(*) AS total FROM ingreso WHERE fecha = CURDATE() AND estado_asistencia = 'Retardo'");
$retardos_hoy = $stmt->fetch()['total'];

// excusas pendientes por revisar
$stmt = $conn->query("SELECT COUNT(*) AS total FROM excusa WHERE estado = 'Pendiente'");
$excusas_pendientes = $stmt->fetch()['total'];

// asistencia de hoy de mis fichas
$stmt = $conn->query("SELECT u.nombre, f.codigo_ficha AS ficha, i.hora_entrada_registrada AS entrada, i.estado_asistencia AS estado
    FROM ingreso i
    INNER JOIN usuario u ON i.id_aprendiz = u.id_usuario
    INNER JOIN usuario_has_ficha uf ON uf.id_aprendiz = u.id_usuario
    INNER JOIN ficha f ON f.id_ficha = uf.id_ficha
    WHERE i.fecha = CURDATE() AND f.id_instructor_encargado = $id
    ORDER BY i.hora_entrada_registrada DESC
    LIMIT 8");
$asistencia_hoy = $stmt->fetchAll();

// excusas pendientes por revisar
$stmt = $conn->query("SELECT u.nombre AS nombre_aprendiz, e.fecha_inasistencia AS fecha, e.observacion AS motivo
    FROM excusa e
    INNER JOIN usuario u ON e.id_aprendiz = u.id_usuario
    WHERE e.estado = 'Pendiente'
    ORDER BY e.fecha_inasistencia ASC");
$excusas_por_revisar = $stmt->fetchAll();
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

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon teal">
                    <i data-lucide="users" class="w-6 h-6"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $mis_aprendices ?? '0'; ?></h3>
                    <p>Mis Aprendices</p>
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
                    <h3><?php echo $retardos_hoy ?? '0'; ?></h3>
                    <p>Retardos Hoy</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon red">
                    <i data-lucide="alert-circle" class="w-6 h-6"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $excusas_pendientes ?? '0'; ?></h3>
                    <p>Excusas por Revisar</p>
                </div>
            </div>
        </div>

        <div class="content-grid">

            <div class="dashboard-card">
                <div class="card-header">
                    <h2 class="card-title">Asistencia de Hoy</h2>
                    <a href="reportes.php" class="text-sm text-teal-600 hover:text-teal-700 font-medium">Ver reporte completo</a>
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
                            <?php if (!empty($asistencia_hoy)): ?>
                                <?php foreach ($asistencia_hoy as $registro): ?>
                                    <tr>
                                        <td class="font-medium"><?php echo $registro['nombre']; ?></td>
                                        <td><?php echo $registro['ficha']; ?></td>
                                        <td><?php echo $registro['entrada']; ?></td>
                                        <td><span class="badge-status badge-<?php echo strtolower($registro['estado']); ?>"><?php echo $registro['estado']; ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center py-8 text-slate-400">No hay registros de asistencia hoy</td>
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
                        <a href="instructor_excusas.php" class="quick-action-btn">
                            <div class="quick-action-icon">
                                <i data-lucide="file-check" class="w-5 h-5"></i>
                            </div>
                            <div class="quick-action-text">
                                <span class="quick-action-title">Revisar Excusas</span>
                                <span class="quick-action-desc"><?php echo $excusas_pendientes ?? '0'; ?> pendientes</span>
                            </div>
                        </a>

                        <a href="reportes.php" class="quick-action-btn">
                            <div class="quick-action-icon">
                                <i data-lucide="file-bar-chart" class="w-5 h-5"></i>
                            </div>
                            <div class="quick-action-text">
                                <span class="quick-action-title">Ver Reportes</span>
                                <span class="quick-action-desc">Asistencia de fichas</span>
                            </div>
                        </a>

                        <a href="instructor_fichas.php" class="quick-action-btn">
                            <div class="quick-action-icon">
                                <i data-lucide="book-open" class="w-5 h-5"></i>
                            </div>
                            <div class="quick-action-text">
                                <span class="quick-action-title">Mis Fichas</span>
                                <span class="quick-action-desc">Ver grupos</span>
                            </div>
                        </a>
                    </div>
                </div>
            </div>

        </div>

        <div class="dashboard-card" style="margin-top: 20px;">
            <div class="card-header">
                <h2 class="card-title">Excusas Pendientes por Revisar</h2>
            </div>
            <div class="card-body" style="padding: 0;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Aprendiz</th>
                            <th>Fecha Inasistencia</th>
                            <th>Motivo</th>
                            <th>Accion</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($excusas_por_revisar)): ?>
                            <?php foreach ($excusas_por_revisar as $excusa): ?>
                                <tr>
                                    <td class="font-medium"><?php echo $excusa['nombre_aprendiz']; ?></td>
                                    <td><?php echo $excusa['fecha']; ?></td>
                                    <td><?php echo $excusa['motivo']; ?></td>
                                    <td>
                                        <div class="flex gap-2">
                                            <button class="badge-status badge-normal" style="cursor:pointer;border:none;">Aprobar</button>
                                            <button class="badge-status badge-inasistencia" style="cursor:pointer;border:none;">Rechazar</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center py-8 text-slate-400">No hay excusas pendientes</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>

    <?php include 'components/footer.php'; ?>

</body>
</html>
