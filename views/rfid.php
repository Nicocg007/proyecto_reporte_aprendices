<?php
require_once '../model/auth_helper.php';
require_once '../config/database.php';
requiereLogin();

// rol actual para saber que sidebar usar y validar acceso
$rol_actual = getRol();
if ($rol_actual !== 'Administrador' && $rol_actual !== 'Instructor') {
    header('Location: login.php');
    exit();
}

// conexion a la base de datos
$db = new Database();
$conn = $db->getConnection();

// registros de asistencia de hoy para la tabla
$stmt = $conn->query("SELECT CONCAT(u.nombre, ' ', u.apellido) AS nombre,
    i.hora_entrada_registrada AS entrada, i.minutos_retardo AS retardo, i.estado_asistencia AS estado
    FROM ingreso i
    INNER JOIN usuario u ON u.id_usuario = i.id_aprendiz
    WHERE i.fecha = CURDATE()
    ORDER BY i.hora_entrada_registrada DESC");
$registros_hoy = $stmt->fetchAll();

// mensajes que llegan por la url cuando se registra una tarjeta
$ok = $_GET['ok'] ?? '';
$error = $_GET['error'] ?? '';
$nombre = $_GET['nombre'] ?? '';

if ($ok === '1') {
    $mensaje_ok = [
        'nombre' => $nombre,
        'estado' => $_GET['estado'] ?? 'Normal',
        'retardo' => $_GET['retardo'] ?? '0',
        'hora' => $_GET['hora'] ?? ''
    ];
}

if ($error === '1') {
    $mensaje_error = 'La tarjeta no esta registrada en el sistema';
} elseif ($error === '2') {
    $mensaje_error = $nombre . ' no se encuentra activo';
} elseif ($error === '3') {
    $mensaje_error = $nombre . ' ya registro su entrada hoy';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php include 'components/header.php'; ?>
</head>
<body>

    <?php
    if ($rol_actual === 'Instructor') {
        include 'components/sidebar_instructor.php';
        include 'components/navbar_instructor.php';
    } else {
        include 'components/sidebar.php';
        include 'components/navbar.php';
    }
    ?>

    <main class="main-content">

        <div class="dashboard-card" style="max-width: 760px; margin: 0 auto;">
            <div class="card-header">
                <h2 class="card-title">Registro de Asistencia RFID</h2>
            </div>
            <div class="card-body" style="text-align: center;">

                <div style="width: 90px; height: 90px; margin: 10px auto 16px; border-radius: 50%; background-color: #f0fdfa; display: flex; align-items: center; justify-content: center;">
                    <i data-lucide="scan-line" class="w-12 h-12 text-teal-600"></i>
                </div>
                <p class="text-slate-600 font-medium">Pase la tarjeta RFID cerca del lector</p>
                <p class="text-slate-400 text-sm">La tarjeta se lee sola al apoyarla, el sistema registra la entrada</p>

                <form method="POST" action="../controllers/rfid_controller.php" id="rfidForm" style="margin-top: 18px;">
                    <input
                        type="text"
                        name="uid"
                        id="rfidInput"
                        class="filter-input"
                        style="margin: 0 auto; max-width: 260px; text-align: center; letter-spacing: 3px;"
                        placeholder="--- esperando tarjeta ---"
                        autocomplete="off"
                    >
                </form>

                <div class="flex gap-3" style="justify-content: center; margin-top: 16px;">
                    <button class="btn-filter-primary" style="border:none; cursor:pointer;" onclick="input.value=''; input.focus();">
                        <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                        Limpiar
                    </button>
                </div>

                <div id="areaResultado">

                    <?php if (!empty($mensaje_ok)): ?>
                    <div class="dashboard-card" style="margin-top: 20px; border: 2px solid #10b981; border-radius: 14px;">
                        <div class="card-body" style="text-align: center;">
                            <span class="badge-status badge-normal" style="font-size: 1rem;">Entrada registrada</span>
                            <h3 class="card-title" style="margin-top: 8px;"><?php echo $mensaje_ok['nombre']; ?></h3>
                            <p class="text-slate-500 text-sm">
                                Hora: <strong><?php echo $mensaje_ok['hora']; ?></strong> |
                                Estado: <strong><?php echo $mensaje_ok['estado']; ?></strong>
                            </p>
                            <?php if ($mensaje_ok['estado'] === 'Retardo'): ?>
                            <p class="text-amber-600 text-sm font-medium"><?php echo $mensaje_ok['retardo']; ?> minutos de retardo</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($mensaje_error)): ?>
                    <div class="dashboard-card" style="margin-top: 20px; border: 2px solid #ef4444; border-radius: 14px;">
                        <div class="card-body" style="text-align: center;">
                            <span class="badge-status badge-inasistencia" style="font-size: 1rem;">No registrado</span>
                            <h3 class="card-title" style="margin-top: 8px;"><?php echo $mensaje_error; ?></h3>
                        </div>
                    </div>
                    <?php endif; ?>

                </div>

            </div>
        </div>

        <div class="dashboard-card" style="max-width: 760px; margin: 20px auto 0;">
            <div class="card-header">
                <h2 class="card-title">Entradas de Hoy</h2>
                <span class="badge-status badge-retardo"><?php echo count($registros_hoy); ?> registros</span>
            </div>
            <div class="card-body" style="padding: 0;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Aprendiz</th>
                            <th>Entrada</th>
                            <th>Retardo</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($registros_hoy)): ?>
                            <?php foreach ($registros_hoy as $registro): ?>
                                <tr>
                                    <td class="font-medium"><?php echo $registro['nombre']; ?></td>
                                    <td><?php echo $registro['entrada']; ?></td>
                                    <td><?php echo $registro['retardo'] ?? '0'; ?> min</td>
                                    <td><span class="badge-status badge-<?php echo strtolower($registro['estado']); ?>"><?php echo $registro['estado']; ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center py-8 text-slate-400">Aun no hay entradas registradas hoy</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>

    <?php include 'components/footer.php'; ?>

    <script>
    // autofoco: el input siempre queda listo para la siguiente tarjeta
    const input = document.getElementById('rfidInput');

    // enfocar el input al cargar la pagina
    window.addEventListener('load', function() {
        input.focus();
        limpiarResultado();
    });

    // si se pierde el foco, volver a enfocar para no perder la siguiente lectura
    input.addEventListener('blur', function() {
        setTimeout(function() { input.focus(); }, 50);
    });

    // ocultar el resultado anterior cuando empieza a leer una tarjeta nueva
    input.addEventListener('input', function() {
        const area = document.getElementById('areaResultado');
        if (area) area.innerHTML = '';
    });

    function limpiarResultado() {
        input.value = '';
    }
    </script>

</body>
</html>