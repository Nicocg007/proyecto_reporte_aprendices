<?php
require_once '../model/auth_helper.php';
requiereLogin();
requiereRol('Aprendiz');

// conexion a la base de datos
require_once '../config/database.php';
$db = new Database();
$conn = $db->getConnection();

// id del aprendiz logueado de la sesion
$id = $_SESSION['id_usuario'];

// dias asistidos dias que marco ingreso, normal o con retardo
$stmt = $conn->query("SELECT COUNT(*) AS total FROM ingreso
    WHERE id_aprendiz = $id AND estado_asistencia IN ('Normal','Retardo','Salida Temprana')");
$dias_asistidos = $stmt->fetch()['total'];

// total de retardos
$stmt = $conn->query("SELECT COUNT(*) AS total FROM ingreso WHERE id_aprendiz = $id AND estado_asistencia = 'Retardo'");
$total_retardos = $stmt->fetch()['total'];

// total de inasistencias
$stmt = $conn->query("SELECT COUNT(*) AS total FROM ingreso WHERE id_aprendiz = $id AND estado_asistencia = 'Inasistencia'");
$total_inasistencias = $stmt->fetch()['total'];

// excusas pendientes mias
$stmt = $conn->query("SELECT COUNT(*) AS total FROM excusa WHERE id_aprendiz = $id AND estado = 'Pendiente'");
$excusas_pendientes = $stmt->fetch()['total'];

// mi historial de asistencia
$stmt = $conn->query("SELECT fecha, hora_entrada_registrada AS entrada, hora_salida_registrada AS salida, estado_asistencia AS estado
    FROM ingreso
    WHERE id_aprendiz = $id
    ORDER BY fecha DESC, hora_entrada_registrada DESC
    LIMIT 10");
$mi_historial = $stmt->fetchAll();

// mis excusas enviadas
$stmt = $conn->query("SELECT fecha_inasistencia AS fecha, observacion AS motivo, estado, fecha_revision
    FROM excusa
    WHERE id_aprendiz = $id
    ORDER BY fecha_inasistencia DESC");
$mis_excusas = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php include 'components/header.php'; ?>
</head>
<body>

    <?php include 'components/sidebar_aprendiz.php'; ?>

    <?php include 'components/navbar_aprendiz.php'; ?>

    <main class="main-content">

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon green">
                    <i data-lucide="check-circle" class="w-6 h-6"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $dias_asistidos ?? '0'; ?></h3>
                    <p>Dias Asistidos</p>
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
                    <i data-lucide="file-text" class="w-6 h-6"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $excusas_pendientes ?? '0'; ?></h3>
                    <p>Excusas Pendientes</p>
                </div>
            </div>
        </div>

        <div class="content-grid">

            <div class="dashboard-card">
                <div class="card-header">
                    <h2 class="card-title">Mi Historial de Asistencia</h2>
                </div>
                <div class="card-body" style="padding: 0;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Entrada</th>
                                <th>Salida</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($mi_historial)): ?>
                                <?php foreach ($mi_historial as $registro): ?>
                                    <tr>
                                        <td><?php echo $registro['fecha']; ?></td>
                                        <td><?php echo $registro['entrada']; ?></td>
                                        <td><?php echo $registro['salida']; ?></td>
                                        <td><span class="badge-status badge-<?php echo strtolower($registro['estado']); ?>"><?php echo $registro['estado']; ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center py-8 text-slate-400">No hay registros de asistencia</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="dashboard-card">
                <div class="card-header">
                    <h2 class="card-title">Acciones</h2>
                </div>
                <div class="card-body">
                    <div class="quick-actions">
                        <a href="aprendiz_excusas.php" class="quick-action-btn">
                            <div class="quick-action-icon">
                                <i data-lucide="file-plus" class="w-5 h-5"></i>
                            </div>
                            <div class="quick-action-text">
                                <span class="quick-action-title">Enviar Excusa</span>
                                <span class="quick-action-desc">Justificar inasistencia</span>
                            </div>
                        </a>

                        <a href="aprendiz_historial.php" class="quick-action-btn">
                            <div class="quick-action-icon">
                                <i data-lucide="calendar" class="w-5 h-5"></i>
                            </div>
                            <div class="quick-action-text">
                                <span class="quick-action-title">Ver Historial</span>
                                <span class="quick-action-desc">Tu historial de asistencia</span>
                            </div>
                        </a>

                        <a href="aprendiz_ficha.php" class="quick-action-btn">
                            <div class="quick-action-icon">
                                <i data-lucide="info" class="w-5 h-5"></i>
                            </div>
                            <div class="quick-action-text">
                                <span class="quick-action-title">Mi Ficha</span>
                                <span class="quick-action-desc">Info de tu grupo</span>
                            </div>
                        </a>
                    </div>
                </div>
            </div>

        </div>

        <div class="dashboard-card" style="margin-top: 20px;">
            <div class="card-header">
                <h2 class="card-title">Mis Excusas Enviadas</h2>
            </div>
            <div class="card-body" style="padding: 0;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Fecha Inasistencia</th>
                            <th>Motivo</th>
                            <th>Estado</th>
                            <th>Fecha Revision</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($mis_excusas)): ?>
                            <?php foreach ($mis_excusas as $excusa): ?>
                                <tr>
                                    <td><?php echo $excusa['fecha']; ?></td>
                                    <td><?php echo $excusa['motivo']; ?></td>
                                    <td><span class="badge-status badge-<?php echo strtolower($excusa['estado']); ?>"><?php echo $excusa['estado']; ?></span></td>
                                    <td><?php echo $excusa['fecha_revision'] ?? '--'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center py-8 text-slate-400">No has enviado excusas</td>
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
