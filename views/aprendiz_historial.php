<?php
require_once '../model/auth_helper.php';
requiereLogin();
requiereRol('Aprendiz');

// conexion a la base de datos
require_once '../config/database.php';
$db = new Database();
$conn = $db->getConnection();

// id del aprendiz logueado
$id = $_SESSION['id_usuario'];

// historial completo de asistencia del aprendiz
$stmt = $conn->query("SELECT fecha, hora_entrada_registrada AS entrada, hora_salida_registrada AS salida,
    minutos_retardo AS retardo, estado_asistencia AS estado
    FROM ingreso
    WHERE id_aprendiz = $id
    ORDER BY fecha DESC, hora_entrada_registrada DESC");
$mi_historial = $stmt->fetchAll();
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
                            <th>Retardo (min)</th>
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
                                    <td><?php echo $registro['retardo'] ?? '0'; ?></td>
                                    <td><span class="badge-status badge-<?php echo strtolower($registro['estado']); ?>"><?php echo $registro['estado']; ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-8 text-slate-400">No hay registros de asistencia</td>
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
