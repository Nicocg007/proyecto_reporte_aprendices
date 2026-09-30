<?php
require_once '../model/auth_helper.php';
requiereLogin();
requiereRol('Administrador');

// conexion a la base de datos
require_once '../config/database.php';
$db = new Database();
$conn = $db->getConnection();

// lista de fichas para el filtro
$stmt = $conn->query("SELECT id_ficha, codigo_ficha FROM ficha ORDER BY codigo_ficha");
$lista_fichas = $stmt->fetchAll();

// insertar un nuevo aprendiz cuando llega el formulario
if (isset($_POST['documento'])) {
    // datos del formulario
    $numero_documento = $_POST['documento'];
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $correo = $_POST['correo'];
    $password = $_POST['password'];
    $rfid = $_POST['rfid'] ?? null;
    $ficha = $_POST['ficha'] ?? '';

    // guardar el aprendiz con rol 3
    // los signos ? son los valores que se pasan despues en execute
    try {
        $stmt = $conn->prepare("INSERT INTO usuario
            (numero_documento, nombre, apellido, correo, password, rfid_uid, id_rol, estado)
            VALUES (?, ?, ?, ?, ?, ?, 3, 'Activo')");
        $stmt->execute([$numero_documento, $nombre, $apellido, $correo, $password, $rfid]);

        // id del aprendiz recien creado
        $id_nuevo = $conn->lastInsertId();

        // asignarlo a la ficha elegida en el formulario
        if ($ficha != '') {
            $stmt = $conn->prepare("INSERT INTO usuario_has_ficha (id_aprendiz, id_ficha) VALUES (?, ?)");
            $stmt->execute([$id_nuevo, $ficha]);
        }

        // redirigir a la misma pagina con mensaje de exito
        header('Location: admin_aprendices.php?agregado=1');
        exit();
    } catch (Exception $e) {
        // si el documento ya existe se muestra el error sin romper la pagina
        $duplicado = true;
    }
}

// filtros de busqueda
$buscar = $_GET['buscar'] ?? '';
$ficha = $_GET['ficha'] ?? '';
$estado = $_GET['estado'] ?? '';

// lista de aprendices con su ficha
$sql = "SELECT u.numero_documento AS documento, CONCAT(u.nombre, ' ', u.apellido) AS nombre, u.correo,
    f.codigo_ficha AS ficha, u.rfid_uid AS rfid, u.estado
    FROM usuario u
    LEFT JOIN usuario_has_ficha uf ON uf.id_aprendiz = u.id_usuario
    LEFT JOIN ficha f ON f.id_ficha = uf.id_ficha
    WHERE u.id_rol = 3";

// filtro por nombre o documento
if ($buscar != '') {
    $sql .= " AND (u.numero_documento LIKE '%$buscar%' OR u.nombre LIKE '%$buscar%')";
}
// filtro por ficha
if ($ficha != '') {
    $sql .= " AND uf.id_ficha = $ficha";
}
// filtro por estado
if ($estado != '') {
    $sql .= " AND u.estado = '$estado'";
}
$sql .= " ORDER BY u.nombre";
$stmt = $conn->query($sql);
$lista_aprendices = $stmt->fetchAll();
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

        <div class="dashboard-card" style="margin-bottom: 20px;">
            <div class="card-header">
                <h2 class="card-title">Filtros de Busqueda</h2>
            </div>
            <div class="card-body">
                <form method="GET" action="admin_aprendices.php">
                    <div class="filter-grid">
                        <div class="filter-group">
                            <label class="filter-label">Buscar</label>
                            <input type="text" name="buscar" class="filter-input" placeholder="Nombre o documento">
                        </div>
                        <div class="filter-group">
                            <label class="filter-label">Ficha</label>
                            <select name="ficha" class="filter-input">
                                <option value="">Todas</option>
                                <?php if (!empty($lista_fichas)): ?>
                                    <?php foreach ($lista_fichas as $ficha): ?>
                                        <option value="<?php echo $ficha['id_ficha']; ?>"><?php echo $ficha['codigo_ficha']; ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label class="filter-label">Estado</label>
                            <select name="estado" class="filter-input">
                                <option value="Activo">Activo</option>
                                <option value="Inactivo">Inactivo</option>
                            </select>
                        </div>
                        <div class="filter-group filter-actions">
                            <button type="submit" class="btn-filter-primary">
                                <i data-lucide="search" class="w-4 h-4"></i>
                                Buscar
                            </button>
                            <a href="admin_aprendices.php" class="btn-filter-secondary">
                                <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                                Limpiar
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="dashboard-card">
            <div class="card-header">
                <h2 class="card-title">Aprendices</h2>
                <a href="#" class="btn-filter-primary" style="text-decoration:none;" onclick="document.getElementById('formAgregar').style.display='block'; return false;">
                    <i data-lucide="user-plus" class="w-4 h-4"></i>
                    Agregar Aprendiz
                </a>
            </div>

            <div class="dashboard-card" id="formAgregar" style="margin-bottom: 20px; display:none;">
                <div class="card-header">
                    <h2 class="card-title">Agregar Nuevo Aprendiz</h2>
                </div>
                <div class="card-body">
                    <form method="POST" action="admin_aprendices.php">
                        <div class="filter-grid" style="grid-template-columns: repeat(3, 1fr);">
                            <div class="filter-group">
                                <label class="filter-label">Numero de Documento</label>
                                <input type="text" name="documento" class="filter-input" required>
                            </div>
                            <div class="filter-group">
                                <label class="filter-label">Nombre</label>
                                <input type="text" name="nombre" class="filter-input" required>
                            </div>
                            <div class="filter-group">
                                <label class="filter-label">Apellido</label>
                                <input type="text" name="apellido" class="filter-input" required>
                            </div>
                            <div class="filter-group">
                                <label class="filter-label">Correo</label>
                                <input type="email" name="correo" class="filter-input">
                            </div>
                            <div class="filter-group">
                                <label class="filter-label">Contraseña</label>
                                <input type="text" name="password" class="filter-input" value="123456" required>
                            </div>
                            <div class="filter-group">
                                <label class="filter-label">RFID</label>
                                <input type="text" name="rfid" class="filter-input" placeholder="Opcional">
                            </div>
                            <div class="filter-group">
                                <label class="filter-label">Ficha</label>
                                <select name="ficha" class="filter-input">
                                    <option value="">Sin ficha</option>
                                    <?php foreach ($lista_fichas as $ficha_opcion): ?>
                                        <option value="<?php echo $ficha_opcion['id_ficha']; ?>"><?php echo $ficha_opcion['codigo_ficha']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="filter-actions" style="margin-top: 15px;">
                            <button type="submit" class="btn-filter-primary">
                                <i data-lucide="save" class="w-4 h-4"></i>
                                Guardar Aprendiz
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="card-body" style="padding: 0;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Documento</th>
                            <th>Nombre</th>
                            <th>Correo</th>
                            <th>Ficha</th>
                            <th>RFID</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($lista_aprendices)): ?>
                            <?php foreach ($lista_aprendices as $aprendiz): ?>
                                <tr>
                                    <td><?php echo $aprendiz['documento']; ?></td>
                                    <td class="font-medium"><?php echo $aprendiz['nombre']; ?></td>
                                    <td><?php echo $aprendiz['correo']; ?></td>
                                    <td><?php echo $aprendiz['ficha']; ?></td>
                                    <td><?php echo $aprendiz['rfid'] ?? '--'; ?></td>
                                    <td><span class="badge-status <?php echo $aprendiz['estado'] == 'Activo' ? 'badge-normal' : 'badge-inasistencia'; ?>"><?php echo $aprendiz['estado']; ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-8 text-slate-400">No hay aprendices registrados</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>

    <?php include 'components/footer.php'; ?>

    <script>
    // mostrar mensaje de exito si trae ?agregado=1 en la url
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('agregado') === '1') {
        SenaToast.success('Listo', 'Aprendiz agregado correctamente');
    }
    // si el documento ya existe se muestra el error sin recargar
    <?php if (isset($duplicado)): ?>
    SenaToast.error('Documento duplicado', 'Ya existe un aprendiz con ese numero de documento');
    <?php endif; ?>
    </script>

</body>
</html>