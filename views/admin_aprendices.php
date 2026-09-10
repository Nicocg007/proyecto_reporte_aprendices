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
                <a href="#" class="btn-filter-primary" style="text-decoration:none;">
                    <i data-lucide="user-plus" class="w-4 h-4"></i>
                    Agregar Aprendiz
                </a>
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

</body>
</html>