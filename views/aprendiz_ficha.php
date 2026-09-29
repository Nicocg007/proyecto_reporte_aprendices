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

// datos de la ficha del aprendiz con su instructor
$stmt = $conn->query("SELECT f.codigo_ficha, f.nombre_programa, f.hora_entrada, f.hora_salida,
    CONCAT(ui.nombre, ' ', ui.apellido) AS instructor_nombre, ui.correo AS instructor_correo,
    (SELECT COUNT(*) FROM usuario_has_ficha WHERE id_ficha = f.id_ficha) AS total_aprendices_ficha
    FROM usuario_has_ficha uf
    INNER JOIN ficha f ON f.id_ficha = uf.id_ficha
    INNER JOIN usuario ui ON ui.id_usuario = f.id_instructor_encargado
    WHERE uf.id_aprendiz = $id");
$ficha = $stmt->fetch();

// separar los datos de la ficha
$codigo_ficha = $ficha['codigo_ficha'] ?? '--';
$nombre_programa = $ficha['nombre_programa'] ?? '--';
$hora_entrada = $ficha['hora_entrada'] ?? '--';
$hora_salida = $ficha['hora_salida'] ?? '--';
$total_aprendices_ficha = $ficha['total_aprendices_ficha'] ?? 0;
$instructor_nombre = $ficha['instructor_nombre'] ?? '--';
$instructor_correo = $ficha['instructor_correo'] ?? '--';
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

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon teal">
                    <i data-lucide="hash" class="w-6 h-6"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $codigo_ficha ?? '--'; ?></h3>
                    <p>Codigo Ficha</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon blue">
                    <i data-lucide="book-open" class="w-6 h-6"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $nombre_programa ?? '--'; ?></h3>
                    <p>Programa</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon green">
                    <i data-lucide="clock" class="w-6 h-6"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $hora_entrada ?? '--'; ?> - <?php echo $hora_salida ?? '--'; ?></h3>
                    <p>Horario</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon amber">
                    <i data-lucide="users" class="w-6 h-6"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $total_aprendices_ficha ?? '0'; ?></h3>
                    <p>Aprendices en Ficha</p>
                </div>
            </div>
        </div>

        <div class="dashboard-card">
            <div class="card-header">
                <h2 class="card-title">Informacion del Instructor</h2>
            </div>
            <div class="card-body">
                <div class="filter-grid" style="grid-template-columns: repeat(2, 1fr);">
                    <div class="filter-group">
                        <label class="filter-label">Nombre</label>
                        <p class="text-sm text-slate-700"><?php echo $instructor_nombre ?? '--'; ?></p>
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">Correo</label>
                        <p class="text-sm text-slate-700"><?php echo $instructor_correo ?? '--'; ?></p>
                    </div>
                </div>
            </div>
        </div>

    </main>

    <?php include 'components/footer.php'; ?>

</body>
</html>
