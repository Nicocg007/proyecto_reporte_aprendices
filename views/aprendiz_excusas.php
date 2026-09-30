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

// excusas enviadas por el aprendiz
$stmt = $conn->query("SELECT fecha_inasistencia AS fecha, observacion AS motivo, estado, archivo_adjunto, fecha_revision
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

        <div class="dashboard-card" style="margin-bottom: 20px;">
            <div class="card-header">
                <h2 class="card-title">Enviar Nueva Excusa</h2>
            </div>
            <div class="card-body">
                <form method="POST" action="../controllers/excusa_controller.php" enctype="multipart/form-data">
                    <input type="hidden" name="accion" value="enviar">
                    <div class="filter-grid">
                        <div class="filter-group">
                            <label class="filter-label">Fecha de Inasistencia</label>
                            <input type="date" name="fecha_inasistencia" class="filter-input" required>
                        </div>
                        <div class="filter-group" style="grid-column: span 2;">
                            <label class="filter-label">Motivo</label>
                            <textarea name="motivo" class="filter-input" rows="3" placeholder="Describe el motivo de tu inasistencia..." required style="resize: vertical;"></textarea>
                        </div>
                        <div class="filter-group">
                            <label class="filter-label">Adjuntar Archivo</label>
                            <input type="file" name="archivo" class="filter-input" accept=".pdf,.jpg,.png">
                        </div>
                        <div class="filter-group filter-actions">
                            <button type="submit" class="btn-filter-primary">
                                <i data-lucide="send" class="w-4 h-4"></i>
                                Enviar Excusa
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="dashboard-card">
            <div class="card-header">
                <h2 class="card-title">Mis Excusas Enviadas</h2>
            </div>
            <div class="card-body" style="padding: 0;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Fecha Inasistencia</th>
                            <th>Motivo</th>
                            <th>Archivo</th>
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
                                    <td>
                                        <?php if (!empty($excusa['archivo_adjunto'])): ?>
                                            <a href="../<?php echo $excusa['archivo_adjunto']; ?>" target="_blank" class="text-teal-600 font-medium">Ver archivo</a>
                                        <?php else: ?>
                                            <span class="text-slate-400">Sin archivo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="badge-status badge-<?php echo strtolower($excusa['estado']); ?>"><?php echo $excusa['estado']; ?></span></td>
                                    <td><?php echo $excusa['fecha_revision'] ?? '--'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-8 text-slate-400">No has enviado excusas</td>
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

    if (urlParams.get('enviada') === '1') {
        SenaToast.success('Listo', 'Excusa enviada correctamente');
    }

    if (urlParams.get('error_campos') === '1') {
        SenaToast.error('Faltan datos', 'La fecha y el motivo son obligatorios');
    }

    if (urlParams.get('error_archivo') === '1') {
        SenaToast.error('Archivo no valido', 'Solo se permiten archivos pdf, jpg o png');
    }

    if (urlParams.get('error_tamano') === '1') {
        SenaToast.error('Archivo muy pesado', 'El archivo no puede superar los 5 megas');
    }

    if (urlParams.get('error_subida') === '1') {
        SenaToast.error('Error al subir', 'No se pudo guardar el archivo, intenta de nuevo');
    }
    </script>

</body>
</html>
