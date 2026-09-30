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

// cantidad de excusas pendientes de mis fichas
$stmt = $conn->query("SELECT COUNT(*) AS total FROM excusa e
    INNER JOIN usuario_has_ficha uf ON uf.id_aprendiz = e.id_aprendiz
    INNER JOIN ficha f ON f.id_ficha = uf.id_ficha
    WHERE f.id_instructor_encargado = $id AND e.estado = 'Pendiente'");
$excusas_pendientes = $stmt->fetch()['total'];

// excusas pendientes de mis fichas
$stmt = $conn->query("SELECT e.id_excusa AS id, CONCAT(u.nombre, ' ', u.apellido) AS nombre_aprendiz, e.fecha_inasistencia AS fecha,
    e.observacion AS motivo, e.archivo_adjunto
    FROM excusa e
    INNER JOIN usuario u ON u.id_usuario = e.id_aprendiz
    INNER JOIN usuario_has_ficha uf ON uf.id_aprendiz = u.id_usuario
    INNER JOIN ficha f ON f.id_ficha = uf.id_ficha
    WHERE f.id_instructor_encargado = $id AND e.estado = 'Pendiente'
    ORDER BY e.fecha_inasistencia ASC");
$excusas_por_revisar = $stmt->fetchAll();

// excusas revisadas de mis fichas
$stmt = $conn->query("SELECT CONCAT(u.nombre, ' ', u.apellido) AS nombre_aprendiz, e.fecha_inasistencia AS fecha,
    e.observacion AS motivo, e.estado, e.archivo_adjunto, e.fecha_revision
    FROM excusa e
    INNER JOIN usuario u ON u.id_usuario = e.id_aprendiz
    INNER JOIN usuario_has_ficha uf ON uf.id_aprendiz = u.id_usuario
    INNER JOIN ficha f ON f.id_ficha = uf.id_ficha
    WHERE f.id_instructor_encargado = $id AND e.estado <> 'Pendiente'
    ORDER BY e.fecha_revision DESC");
$excusas_revisadas = $stmt->fetchAll();
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
                <h2 class="card-title">Excusa Pendientes por Revisar</h2>
                <span class="badge-status badge-retardo"><?php echo $excusas_pendientes ?? '0'; ?> pendientes</span>
            </div>
            <div class="card-body" style="padding: 0;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Aprendiz</th>
                            <th>Fecha Inasistencia</th>
                            <th>Motivo</th>
                            <th>Archivo</th>
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
                                        <?php if (!empty($excusa['archivo_adjunto'])): ?>
                                            <a href="../<?php echo $excusa['archivo_adjunto']; ?>" target="_blank" class="text-teal-600 font-medium">Ver archivo</a>
                                        <?php else: ?>
                                            <span class="text-slate-400">Sin archivo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <form method="POST" action="../controllers/excusa_controller.php" class="flex gap-2">
                                            <input type="hidden" name="id_excusa" value="<?php echo $excusa['id']; ?>">
                                            <button type="submit" name="accion" value="aprobar" class="badge-status badge-normal" style="cursor:pointer;border:none;">Aprobar</button>
                                            <button type="submit" name="accion" value="rechazar" class="badge-status badge-inasistencia" style="cursor:pointer;border:none;">Rechazar</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-8 text-slate-400">No hay excusas pendientes</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="dashboard-card">
            <div class="card-header">
                <h2 class="card-title">Excusas Revisadas</h2>
            </div>
            <div class="card-body" style="padding: 0;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Aprendiz</th>
                            <th>Fecha Inasistencia</th>
                            <th>Motivo</th>
                            <th>Archivo</th>
                            <th>Estado</th>
                            <th>Fecha Revision</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($excusas_revisadas)): ?>
                            <?php foreach ($excusas_revisadas as $excusa): ?>
                                <tr>
                                    <td class="font-medium"><?php echo $excusa['nombre_aprendiz']; ?></td>
                                    <td><?php echo $excusa['fecha']; ?></td>
                                    <td><?php echo $excusa['motivo']; ?></td>
                                    <td>
                                        <?php if (!empty($excusa['archivo_adjunto'])): ?>
                                            <a href="../<?php echo $excusa['archivo_adjunto']; ?>" target="_blank" class="text-teal-600 font-medium">Ver archivo</a>
                                        <?php else: ?>
                                            <span class="text-slate-400">Sin archivo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="badge-status badge-<?php echo strtolower($excusa['estado']); ?>"><?php echo $excusa['estado']; ?></span></td>
                                    <td><?php echo $excusa['fecha_revision']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-8 text-slate-400">No hay excusas revisadas</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>

    <?php include 'components/footer.php'; ?>

    <script>
    // mostrar mensajes segun lo que traiga la url
    const urlParams = new URLSearchParams(window.location.search);

    if (urlParams.get('revisada') === 'aprobada') {
        SenaToast.success('Listo', 'La excusa fue aprobada');
    }

    if (urlParams.get('revisada') === 'rechazada') {
        SenaToast.warning('Listo', 'La excusa fue rechazada');
    }

    if (urlParams.get('error') === '1') {
        SenaToast.error('Error', 'No se pudo revisar la excusa');
    }
    </script>

</body>
</html>