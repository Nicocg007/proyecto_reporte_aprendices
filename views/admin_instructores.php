<?php
require_once '../model/auth_helper.php';
requiereLogin();
requiereRol('Administrador');

// conexion a la base de datos
require_once '../config/database.php';
$db = new Database();
$conn = $db->getConnection();

// editar un instructor existente (solo admin)
if (isset($_POST['editar_instructor'])) {
    $id_instructor = $_POST['id_instructor'] ?? '';
    $numero_documento = trim($_POST['documento'] ?? '');
    $nombre = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $password = $_POST['password'] ?? '';
    $rfid = trim($_POST['rfid'] ?? '');
    $rfid = $rfid !== '' ? $rfid : null;
    $estado = $_POST['estado'] ?? 'Activo';

    try {
        // actualizar los datos del instructor
        $stmt = $conn->prepare("UPDATE usuario
            SET numero_documento = ?, nombre = ?, apellido = ?, correo = ?, rfid_uid = ?, estado = ?
            WHERE id_usuario = ? AND id_rol = 2");
        $stmt->execute([$numero_documento, $nombre, $apellido, $correo, $rfid, $estado, $id_instructor]);

        // cambiar la contraseña solo si se escribio una nueva
        if ($password !== '') {
            $stmt = $conn->prepare("UPDATE usuario SET password = ? WHERE id_usuario = ?");
            $stmt->execute([$password, $id_instructor]);
        }

        header('Location: admin_instructores.php?editado=1');
        exit();
    } catch (Exception $e) {
        $error_editar = true;
        if (strpos($e->getMessage(), 'rfid_uid') !== false) {
            $mensaje_editar = 'Ese RFID ya esta registrado en otro usuario';
        } else {
            $mensaje_editar = 'Ese numero de documento ya esta registrado';
        }
    }
}

// insertar un nuevo instructor cuando llega el formulario
if (isset($_POST['documento']) && empty($_POST['id_instructor'])) {
    $numero_documento = trim($_POST['documento']);
    $nombre = trim($_POST['nombre']);
    $apellido = trim($_POST['apellido']);
    $correo = trim($_POST['correo'] ?? '');
    $password = $_POST['password'];
    $rfid = trim($_POST['rfid'] ?? '');
    $rfid = $rfid !== '' ? $rfid : null;

    // guardar el instructor con rol 2
    try {
        $stmt = $conn->prepare("INSERT INTO usuario
            (numero_documento, nombre, apellido, correo, password, rfid_uid, id_rol, estado)
            VALUES (?, ?, ?, ?, ?, ?, 2, 'Activo')");
        $stmt->execute([$numero_documento, $nombre, $apellido, $correo, $password, $rfid]);

        header('Location: admin_instructores.php?agregado=1');
        exit();
    } catch (Exception $e) {
        $duplicado = true;
        if (strpos($e->getMessage(), 'rfid_uid') !== false) {
            $mensaje_duplicado = 'Ese RFID ya esta registrado en otro usuario';
        } else {
            $mensaje_duplicado = 'Ya existe un instructor con ese numero de documento';
        }
    }
}

// lista de instructores
$stmt = $conn->query("SELECT id_usuario AS id, numero_documento, nombre, apellido, correo, rfid_uid, estado
    FROM usuario WHERE id_rol = 2 ORDER BY nombre");
$lista_instructores = $stmt->fetchAll();

// instructor que se va a editar, si llega el id por la url
$modo_editar = isset($_GET['editar']) ? (int)$_GET['editar'] : 0;
$instructor_editar = null;
if ($modo_editar > 0) {
    $stmt = $conn->prepare("SELECT id_usuario, numero_documento, nombre, apellido, correo, rfid_uid, estado
        FROM usuario WHERE id_usuario = ? AND id_rol = 2");
    $stmt->execute([$modo_editar]);
    $instructor_editar = $stmt->fetch();
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

        <div class="dashboard-card">
            <div class="card-header">
                <h2 class="card-title">Instructores</h2>
                <a href="#" class="btn-filter-primary" style="text-decoration:none;" onclick="document.getElementById('formAgregar').style.display='block'; return false;">
                    <i data-lucide="user-plus" class="w-4 h-4"></i>
                    Agregar Instructor
                </a>
            </div>

            <?php if ($instructor_editar): ?>
            <div class="dashboard-card" style="margin-bottom: 20px; border: 2px solid #0d9488;">
                <div class="card-header">
                    <h2 class="card-title">Editar Instructor</h2>
                    <a href="admin_instructores.php" class="btn-filter-secondary" style="text-decoration:none;">
                        <i data-lucide="x" class="w-4 h-4"></i>
                        Cancelar
                    </a>
                </div>
                <div class="card-body">
                    <form method="POST" action="admin_instructores.php">
                        <input type="hidden" name="editar_instructor" value="1">
                        <input type="hidden" name="id_instructor" value="<?php echo $instructor_editar['id_usuario']; ?>">
                        <div class="filter-grid" style="grid-template-columns: repeat(3, 1fr);">
                            <div class="filter-group">
                                <label class="filter-label">Numero de Documento</label>
                                <input type="text" name="documento" class="filter-input" value="<?php echo $instructor_editar['numero_documento']; ?>" required>
                            </div>
                            <div class="filter-group">
                                <label class="filter-label">Nombre</label>
                                <input type="text" name="nombre" class="filter-input" value="<?php echo $instructor_editar['nombre']; ?>" required>
                            </div>
                            <div class="filter-group">
                                <label class="filter-label">Apellido</label>
                                <input type="text" name="apellido" class="filter-input" value="<?php echo $instructor_editar['apellido']; ?>" required>
                            </div>
                            <div class="filter-group">
                                <label class="filter-label">Correo</label>
                                <input type="email" name="correo" class="filter-input" value="<?php echo $instructor_editar['correo']; ?>">
                            </div>
                            <div class="filter-group">
                                <label class="filter-label">Contraseña (vacio = sin cambios)</label>
                                <input type="text" name="password" class="filter-input" placeholder="Sin cambios">
                            </div>
                            <div class="filter-group">
                                <label class="filter-label">RFID</label>
                                <input type="text" name="rfid" class="filter-input" value="<?php echo $instructor_editar['rfid_uid']; ?>">
                            </div>
                            <div class="filter-group">
                                <label class="filter-label">Estado</label>
                                <select name="estado" class="filter-input">
                                    <option value="Activo" <?php echo ($instructor_editar['estado'] == 'Activo') ? 'selected' : ''; ?>>Activo</option>
                                    <option value="Inactivo" <?php echo ($instructor_editar['estado'] == 'Inactivo') ? 'selected' : ''; ?>>Inactivo</option>
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
                    <h2 class="card-title">Agregar Nuevo Instructor</h2>
                </div>
                <div class="card-body">
                    <form method="POST" action="admin_instructores.php">
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
                        </div>
                        <div class="filter-actions" style="margin-top: 15px;">
                            <button type="submit" class="btn-filter-primary">
                                <i data-lucide="save" class="w-4 h-4"></i>
                                Guardar Instructor
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
                            <th>RFID</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($lista_instructores)): ?>
                            <?php foreach ($lista_instructores as $instructor): ?>
                                <tr>
                                    <td><?php echo $instructor['numero_documento']; ?></td>
                                    <td class="font-medium"><?php echo $instructor['nombre'] . ' ' . $instructor['apellido']; ?></td>
                                    <td><?php echo $instructor['correo']; ?></td>
                                    <td><?php echo $instructor['rfid_uid'] ?? '--'; ?></td>
                                    <td><span class="badge-status <?php echo $instructor['estado'] == 'Activo' ? 'badge-normal' : 'badge-inasistencia'; ?>"><?php echo $instructor['estado']; ?></span></td>
                                    <td>
                                        <a href="admin_instructores.php?editar=<?php echo $instructor['id']; ?>" class="btn-filter-secondary" style="text-decoration:none;">
                                            <i data-lucide="pencil" class="w-4 h-4"></i>
                                            Editar
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-8 text-slate-400">No hay instructores registrados</td>
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
    if (urlParams.get('agregado') === '1') {
        SenaToast.success('Listo', 'Instructor agregado correctamente');
    }
    if (urlParams.get('editado') === '1') {
        SenaToast.success('Listo', 'Instructor actualizado correctamente');
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
