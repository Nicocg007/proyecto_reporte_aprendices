<?php
require_once '../model/auth_helper.php';
requiereLogin();
requiereRol('Administrador');

// conexion a la base de datos
require_once '../config/database.php';
$db = new Database();
$conn = $db->getConnection();

// lista de instructores activos para el formulario
$stmt = $conn->query("SELECT id_usuario, nombre, apellido FROM usuario
    WHERE id_rol = 2 AND estado = 'Activo'
    ORDER BY nombre");
$lista_instructores = $stmt->fetchAll();

// insertar una nueva ficha cuando llega el formulario
if (isset($_POST['codigo_ficha'])) {
    // datos del formulario
    $codigo_ficha = trim($_POST['codigo_ficha']);
    $nombre_programa = trim($_POST['nombre_programa']);
    $hora_entrada = $_POST['hora_entrada'] ?? '';
    $hora_salida = $_POST['hora_salida'] ?? '';
    $id_instructor = $_POST['id_instructor'] ?? '';

    // todos los campos son obligatorios
    if ($codigo_ficha === '' || $nombre_programa === '' || $hora_entrada === '' || $hora_salida === '' || $id_instructor === '') {
        header('Location: admin_fichas.php?faltan=1');
        exit();
    }

    // guardar la ficha en la base de datos
    try {
        $stmt = $conn->prepare("INSERT INTO ficha
            (codigo_ficha, nombre_programa, hora_entrada, hora_salida, id_instructor_encargado)
            VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$codigo_ficha, $nombre_programa, $hora_entrada, $hora_salida, $id_instructor]);

        // redirigir a la misma pagina con mensaje de exito
        header('Location: admin_fichas.php?creada=1');
        exit();
    } catch (Exception $e) {
        // si el codigo ya existe se muestra el error sin romper la pagina
        $duplicada = true;
    }
}

// lista de fichas con instructor y cantidad de aprendices
$stmt = $conn->query("SELECT f.codigo_ficha, f.nombre_programa, f.hora_entrada, f.hora_salida,
    CONCAT(u.nombre, ' ', u.apellido) AS instructor,
    COUNT(uf.id_aprendiz) AS total_aprendices
    FROM ficha f
    INNER JOIN usuario u ON u.id_usuario = f.id_instructor_encargado
    LEFT JOIN usuario_has_ficha uf ON uf.id_ficha = f.id_ficha
    GROUP BY f.id_ficha
    ORDER BY f.codigo_ficha");
$lista_fichas_completa = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php include 'components/header.php'; ?>
</head>
<body>

    <?php include 'components/sidebar.php'; ?>

    <?php include 'components/navbar.php'; ?>

    <main class="main-content">

        <div class="dashboard-card">
            <div class="card-header">
                <h2 class="card-title">Fichas de Formacion</h2>
                <a href="#" class="btn-filter-primary" style="text-decoration:none;" onclick="document.getElementById('formCrearFicha').style.display='block'; return false;">
                    <i data-lucide="folder-plus" class="w-4 h-4"></i>
                    Crear Ficha
                </a>
            </div>

            <div class="dashboard-card" id="formCrearFicha" style="margin-bottom: 20px; display:none;">
                <div class="card-header">
                    <h2 class="card-title">Crear Nueva Ficha</h2>
                </div>
                <div class="card-body">
                    <form method="POST" action="admin_fichas.php">
                        <div class="filter-grid" style="grid-template-columns: repeat(3, 1fr);">
                            <div class="filter-group">
                                <label class="filter-label">Codigo de Ficha</label>
                                <input type="text" name="codigo_ficha" class="filter-input" placeholder="Ej: 2999876" required>
                            </div>
                            <div class="filter-group" style="grid-column: span 2;">
                                <label class="filter-label">Nombre del Programa</label>
                                <input type="text" name="nombre_programa" class="filter-input" placeholder="Ej: Analisis y Desarrollo de Software" required>
                            </div>
                            <div class="filter-group">
                                <label class="filter-label">Hora de Entrada</label>
                                <input type="time" name="hora_entrada" class="filter-input" required>
                            </div>
                            <div class="filter-group">
                                <label class="filter-label">Hora de Salida</label>
                                <input type="time" name="hora_salida" class="filter-input" required>
                            </div>
                            <div class="filter-group">
                                <label class="filter-label">Instructor Encargado</label>
                                <select name="id_instructor" class="filter-input" required>
                                    <option value="">Seleccione un instructor</option>
                                    <?php foreach ($lista_instructores as $instructor_opcion): ?>
                                        <option value="<?php echo $instructor_opcion['id_usuario']; ?>"><?php echo $instructor_opcion['nombre'] . ' ' . $instructor_opcion['apellido']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="filter-actions" style="margin-top: 15px;">
                            <button type="submit" class="btn-filter-primary">
                                <i data-lucide="save" class="w-4 h-4"></i>
                                Guardar Ficha
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="card-body" style="padding: 0;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Codigo</th>
                            <th>Programa</th>
                            <th>Horario</th>
                            <th>Instructor</th>
                            <th>Aprendices</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($lista_fichas_completa)): ?>
                            <?php foreach ($lista_fichas_completa as $ficha): ?>
                                <tr>
                                    <td class="font-medium"><?php echo $ficha['codigo_ficha']; ?></td>
                                    <td><?php echo $ficha['nombre_programa']; ?></td>
                                    <td><?php echo $ficha['hora_entrada']; ?> - <?php echo $ficha['hora_salida']; ?></td>
                                    <td><?php echo $ficha['instructor']; ?></td>
                                    <td><?php echo $ficha['total_aprendices']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-8 text-slate-400">No hay fichas registradas</td>
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

    if (urlParams.get('creada') === '1') {
        SenaToast.success('Listo', 'Ficha creada correctamente');
    }

    if (urlParams.get('faltan') === '1') {
        SenaToast.error('Faltan datos', 'Completa todos los campos de la ficha');
    }

    // si el codigo ya existe se muestra el error sin recargar
    <?php if (isset($duplicada)): ?>
    SenaToast.error('Codigo duplicado', 'Ya existe una ficha con ese codigo');
    <?php endif; ?>
    </script>

</body>
</html>