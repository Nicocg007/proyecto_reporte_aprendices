<!DOCTYPE html>
<html lang="es">
<head>
    <?php include 'components/header.php'; ?>
</head>
<body>

    <?php include 'components/sidebar_aprendiz.php'; ?>

    <?php include 'components/navbar_aprendiz.php'; ?>

    <main class="main-content">

        <div class="dashboard-card" style="margin-bottom: 20px;">
            <div class="card-header">
                <h2 class="card-title">Enviar Nueva Excusa</h2>
            </div>
            <div class="card-body">
                <form method="POST" action="#" enctype="multipart/form-data">
                    <div class="filter-grid">
                        <div class="filter-group">
                            <label class="filter-label">Fecha de Inasistencia</label>
                            <input type="date" name="fecha_inasistencia" class="filter-input" required>
                        </div>
                        <div class="filter-group" style="grid-column: span 2;">
                            <label class="filter-label">Motivo</label>
                            <textarea name="motivo" class="filter-input" rows="3" placeholder="Describe el motivo de tu inasistencia..." required style="resize: vertical;"></textarea>
                        </div>
                        <div class="filter-group">
                            <label class="filter-label">Adjuntar Archivo</label>
                            <input type="file" name="archivo" class="filter-input" accept=".pdf,.jpg,.png">
                        </div>
                        <div class="filter-group filter-actions">
                            <button type="submit" class="btn-filter-primary">
                                <i data-lucide="send" class="w-4 h-4"></i>
                                Enviar Excusa
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="dashboard-card">
            <div class="card-header">
                <h2 class="card-title">Mis Excusas Enviadas</h2>
            </div>
            <div class="card-body" style="padding: 0;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Fecha Inasistencia</th>
                            <th>Motivo</th>
                            <th>Estado</th>
                            <th>Fecha Revision</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($mis_excusas)): ?>
                            <?php foreach ($mis_excusas as $excusa): ?>
                                <tr>
                                    <td><?php echo $excusa['fecha']; ?></td>
                                    <td><?php echo $excusa['motivo']; ?></td>
                                    <td><span class="badge-status badge-<?php echo strtolower($excusa['estado']); ?>"><?php echo $excusa['estado']; ?></span></td>
                                    <td><?php echo $excusa['fecha_revision'] ?? '--'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center py-8 text-slate-400">No has enviado excusas</td>
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
