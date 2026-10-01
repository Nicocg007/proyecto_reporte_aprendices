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

// editar un aprendiz existente (solo admin)
if (isset($_POST['editar_aprendiz'])) {
    $id_aprendiz = $_POST['id_aprendiz'] ?? '';
    $numero_documento = trim($_POST['documento'] ?? '');
    $nombre = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $password = $_POST['password'] ?? '';
    $rfid = trim($_POST['rfid'] ?? '');
    $rfid = $rfid !== '' ? $rfid : null;
    $ficha = $_POST['ficha'] ?? '';
    $estado = $_POST['estado'] ?? 'Activo';

    try {
        // actualizar los datos del aprendiz
        $stmt = $conn->prepare("UPDATE usuario
            SET numero_documento = ?, nombre = ?, apellido = ?, correo = ?, rfid_uid = ?, estado = ?
            WHERE id_usuario = ?");
        $stmt->execute([$numero_documento, $nombre, $apellido, $correo, $rfid, $estado, $id_aprendiz]);

        // cambiar la contraseña solo si se escribio una nueva
        if ($password !== '') {
            $stmt = $conn->prepare("UPDATE usuario SET password = ? WHERE id_usuario = ?");
            $stmt->execute([$password, $id_aprendiz]);
        }

        // dejar una sola ficha asignada
        $stmt = $conn->prepare("DELETE FROM usuario_has_ficha WHERE id_aprendiz = ?");
        $stmt->execute([$id_aprendiz]);
        if ($ficha != '') {
            $stmt = $conn->prepare("INSERT INTO usuario_has_ficha (id_aprendiz, id_ficha) VALUES (?, ?)");
            $stmt->execute([$id_aprendiz, $ficha]);
        }

        header('Location: admin_aprendices.php?editado=1');
        exit();
    } catch (Exception $e) {
        // si algo ya existe se muestra el error sin romper la pagina
        $error_editar = true;
        if (strpos($e->getMessage(), 'rfid_uid') !== false) {
            $mensaje_editar = 'Ese RFID ya esta registrado en otro usuario';
        } else {
            $mensaje_editar = 'Ese numero de documento ya esta registrado';
        }
    }
}

// insertar un nuevo aprendiz cuando llega el formulario
if (isset($_POST['documento']) && empty($_POST['id_aprendiz'])) {
    // datos del formulario
    $numero_documento = $_POST['documento'];
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $correo = $_POST['correo'];
    $password = $_POST['password'];
    // el rfid es opcional: si viene vacio se guarda como null
    $rfid = trim($_POST['rfid'] ?? '');
    $rfid = $rfid !== '' ? $rfid : null;
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
        // si algo ya existe se muestra el error sin romper la pagina
        $duplicado = true;
        if (strpos($e->getMessage(), 'rfid_uid') !== false) {
            $mensaje_duplicado = 'Ese RFID ya esta registrado en otro aprendiz';
        } else {
            $mensaje_duplicado = 'Ya existe un aprendiz con ese numero de documento';
        }
    }
}

// filtros de busqueda
$buscar = $_GET['buscar'] ?? '';
$ficha = $_GET['ficha'] ?? '';
$estado = $_GET['estado'] ?? '';

// lista de aprendices con su ficha
$sql = "SELECT u.id_usuario AS id, u.numero_documento AS documento, CONCAT(u.nombre, ' ', u.apellido) AS nombre, u.correo,
    f.codigo_ficha AS ficha, uf.id_ficha AS id_ficha, u.rfid_uid AS rfid, u.estado
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

// aprendiz que se va a editar, si llega el id por la url
$modo_editar = isset($_GET['editar']) ? (int)$_GET['editar'] : 0;
$aprendiz_editar = null;
if ($modo_editar > 0) {
    $stmt = $conn->prepare("SELECT id_usuario, numero_documento, nombre, apellido, correo, rfid_uid, estado
        FROM usuario WHERE id_usuario = ? AND id_rol = 3");
    $stmt->execute([$modo_editar]);
    $aprendiz_editar = $stmt->fetch();

    if ($aprendiz_editar) {
        // ficha que tiene asignada ahora
        $stmt = $conn->prepare("SELECT id_ficha FROM usuario_has_ficha WHERE id_aprendiz = ? LIMIT 1");
        $stmt->execute([$modo_editar]);
        $aprendiz_editar['id_ficha'] = $stmt->fetchColumn();
    }
}
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

            <?php if ($aprendiz_editar): ?>
            <div class="dashboard-card" style="margin-bottom: 20px; border: 2px solid #0d9488;">
                <div class="card-header">
                    <h2 class="card-title">Editar Aprendiz</h2>
                    <a href="admin_aprendices.php" class="btn-filter-secondary" style="text-decoration:none;">
                        <i data-lucide="x" class="w-4 h-4"></i>
                        Cancelar
                    </a>
                </div>
                <div class="card-body">
                    <form method="POST" action="admin_aprendices.php">
                        <input type="hidden" name="editar_aprendiz" value="1">
                        <input type="hidden" name="id_aprendiz" value="<?php echo $aprendiz_editar['id_usuario']; ?>">
                        <div class="filter-grid" style="grid-template-columns: repeat(3, 1fr);">
                            <div class="filter-group">
                                <label class="filter-label">Numero de Documento</label>
                                <input type="text" name="documento" class="filter-input" value="<?php echo $aprendiz_editar['numero_documento']; ?>" required>
                            </div>
                            <div class="filter-group">
                                <label class="filter-label">Nombre</label>
                                <input type="text" name="nombre" class="filter-input" value="<?php echo $aprendiz_editar['nombre']; ?>" required>
                            </div>
                            <div class="filter-group">
                                <label class="filter-label">Apellido</label>
                                <input type="text" name="apellido" class="filter-input" value="<?php echo $aprendiz_editar['apellido']; ?>" required>
                            </div>
                            <div class="filter-group">
                                <label class="filter-label">Correo</label>
                                <input type="email" name="correo" class="filter-input" value="<?php echo $aprendiz_editar['correo']; ?>">
                            </div>
                            <div class="filter-group">
                                <label class="filter-label">Contraseña (vacio = sin cambios)</label>
                                <input type="text" name="password" class="filter-input" placeholder="Sin cambios">
                            </div>
                            <div class="filter-group">
                                <label class="filter-label">RFID</label>
                                <input type="text" name="rfid" class="filter-input" value="<?php echo $aprendiz_editar['rfid_uid']; ?>">
                            </div>
                            <div class="filter-group">
                                <label class="filter-label">Ficha</label>
                                <select name="ficha" class="filter-input">
                                    <option value="">Sin ficha</option>
                                    <?php foreach ($lista_fichas as $ficha_opcion): ?>
                                        <option value="<?php echo $ficha_opcion['id_ficha']; ?>" <?php echo ($aprendiz_editar['id_ficha'] == $ficha_opcion['id_ficha']) ? 'selected' : ''; ?>><?php echo $ficha_opcion['codigo_ficha']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="filter-group">
                                <label class="filter-label">Estado</label>
                                <select name="estado" class="filter-input">
                                    <option value="Activo" <?php echo ($aprendiz_editar['estado'] == 'Activo') ? 'selected' : ''; ?>>Activo</option>
                                    <option value="Inactivo" <?php echo ($aprendiz_editar['estado'] == 'Inactivo') ? 'selected' : ''; ?>>Inactivo</option>
                                </select>
                            </div>
                        </div>
                        <div class="filter-actions" style="margin-top: 15px;">
                            <button type="submit" class="btn-filter-primary">
                                <i data-lucide="save" class="w-4 h-4"></i>
                                Guardar Cambios
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>

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
                            <th>Acciones</th>
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
                                    <td>
                                        <a href="admin_aprendices.php?editar=<?php echo $aprendiz['id']; ?>" class="btn-filter-secondary" style="text-decoration:none;">
                                            <i data-lucide="pencil" class="w-4 h-4"></i>
                                            Editar
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-8 text-slate-400">No hay aprendices registrados</td>
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
    if (urlParams.get('editado') === '1') {
        SenaToast.success('Listo', 'Aprendiz actualizado correctamente');
    }
    // si algo ya existe se muestra el error sin recargar
    <?php if (isset($duplicado)): ?>
    SenaToast.error('Registro duplicado', '<?php echo $mensaje_duplicado; ?>');
    <?php endif; ?>
    <?php if (isset($error_editar)): ?>
    SenaToast.error('No se pudo guardar', '<?php echo $mensaje_editar; ?>');
    <?php endif; ?>
    </script>

</body>
</html>