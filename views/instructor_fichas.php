<!DOCTYPE html>
<html lang="es">
<head>
    <?php include 'components/header.php'; ?>
</head>
<body>

    <?php include 'components/sidebar_instructor.php'; ?>

    <?php include 'components/navbar_instructor.php'; ?>

    <main class="main-content">

        <div class="dashboard-card">
            <div class="card-header">
                <h2 class="card-title">Mis Fichas</h2>
            </div>
            <div class="card-body" style="padding: 0;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Codigo</th>
                            <th>Programa</th>
                            <th>Horario</th>
                            <th>Aprendices</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($mis_fichas)): ?>
                            <?php foreach ($mis_fichas as $ficha): ?>
                                <tr>
                                    <td class="font-medium"><?php echo $ficha['codigo_ficha']; ?></td>
                                    <td><?php echo $ficha['nombre_programa']; ?></td>
                                    <td><?php echo $ficha['hora_entrada']; ?> - <?php echo $ficha['hora_salida']; ?></td>
                                    <td><?php echo $ficha['total_aprendices']; ?></td>
                                    <td><span class="badge-status badge-normal">Activa</span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-8 text-slate-400">No tienes fichas asignadas</td>
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