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
$stmt = $conn->query("SELECT id_ficha, codigo_ficha FROM ficha WHERE id_instructor_encargado = $id ORDER BY codigo_ficha");
$lista_fichas = $stmt->fetchAll();

// filtros del formulario
$ficha_filtro = $_POST['ficha'] ?? '';
$fecha_filtro = $_POST['fecha'] ?? '';

// asistencias registradas con filtros opcionales
$sql = "SELECT CONCAT(u.nombre, ' ', u.apellido) AS nombre, f.codigo_ficha AS ficha, i.fecha,
    i.hora_entrada_registrada AS entrada, i.hora_salida_registrada AS salida, i.estado_asistencia AS estado
    FROM ingreso i
    INNER JOIN usuario u ON u.id_usuario = i.id_aprendiz
    INNER JOIN usuario_has_ficha uf ON uf.id_aprendiz = u.id_usuario
    INNER JOIN ficha f ON f.id_ficha = uf.id_ficha
    WHERE f.id_instructor_encargado = $id";

// filtro por ficha
if ($ficha_filtro != '') {
    $sql .= " AND uf.id_ficha = $ficha_filtro";
}
// filtro por fecha
if ($fecha_filtro != '') {
    $sql .= " AND i.fecha = '$fecha_filtro'";
}
$sql .= " ORDER BY i.fecha DESC, i.hora_entrada_registrada DESC";
$stmt = $conn->query($sql);
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
                <form method="POST" action="#">
                    <div class="filter-grid" style="grid-template-columns: repeat(3, 1fr);">
                        <div class="filter-group">
                            <label class="filter-label">Ficha</label>
                            <select name="ficha" class="filter-input">
                                <option value="">Selecciona ficha</option>
                                <?php if (!empty($lista_fichas)): ?>
                                    <?php foreach ($lista_fichas as $ficha): ?>
                                        <option value="<?php echo $ficha['id_ficha']; ?>"><?php echo $ficha['codigo_ficha']; ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label class="filter-label">Fecha</label>
                            <input type="date" name="fecha" class="filter-input">
                        </div>
                        <div class="filter-group filter-actions">
                            <button type="submit" class="btn-filter-primary">
                                <i data-lucide="clipboard-check" class="w-4 h-4"></i>
                                Registrar
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

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
                                    <td><?php echo $registro['entrada']; ?></td>
                                    <td><?php echo $registro['salida']; ?></td>
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

</body>
</html>