<?php
require_once '../model/auth_helper.php';
require_once '../model/seguridad_helper.php';
requiereLogin();
requiereRol('Administrador');

// conexion a la base de datos
require_once '../config/database.php';
$db = new Database();
$conn = $db->getConnection();
$fichaRepository = new \App\Repository\FichaRepository($conn);
$fichaService = new \App\Service\FichaService();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificarCsrf();
}

$lista_instructores = $fichaRepository->findActiveInstructors();

// insertar una nueva ficha cuando llega el formulario
if (isset($_POST['codigo_ficha'])) {
    $ficha = [
        'codigo_ficha' => trim($_POST['codigo_ficha']),
        'nombre_programa' => trim($_POST['nombre_programa']),
        'hora_entrada' => $_POST['hora_entrada'] ?? '',
        'hora_salida' => $_POST['hora_salida'] ?? '',
        'id_instructor' => $_POST['id_instructor'] ?? '',
    ];

    if (!$fichaService->validateCreation($ficha)) {
        header('Location: admin_fichas.php?faltan=1');
        exit();
    }

    try {
        $fichaRepository->create($ficha);
        header('Location: admin_fichas.php?creada=1');
        exit();
    } catch (Exception $e) {
        $duplicada = true;
    }
}

$lista_fichas_completa = $fichaRepository->findAllWithLearnerCounts();
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
                    <?php echo csrfField(); ?>

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