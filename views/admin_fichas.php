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
                <a href="#" class="btn-filter-primary" style="text-decoration:none;">
                    <i data-lucide="folder-plus" class="w-4 h-4"></i>
                    Crear Ficha
                </a>
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

</body>
</html>