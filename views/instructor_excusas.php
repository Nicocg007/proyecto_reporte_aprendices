<!DOCTYPE html>
<html lang="es">
<head>
    <?php include 'components/header.php'; ?>
</head>
<body>

    <?php include 'components/sidebar_instructor.php'; ?>

    <?php include 'components/navbar_instructor.php'; ?>

    <main class="main-content">

        <div class="dashboard-card" style="margin-bottom: 20px;">
            <div class="card-header">
                <h2 class="card-title">Excusa Pendientes por Revisar</h2>
                <span class="badge-status badge-retardo"><?php echo $excusas_pendientes ?? '0'; ?> pendientes</span>
            </div>
            <div class="card-body" style="padding: 0;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Aprendiz</th>
                            <th>Fecha Inasistencia</th>
                            <th>Motivo</th>
                            <th>Accion</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($excusas_por_revisar)): ?>
                            <?php foreach ($excusas_por_revisar as $excusa): ?>
                                <tr>
                                    <td class="font-medium"><?php echo $excusa['nombre_aprendiz']; ?></td>
                                    <td><?php echo $excusa['fecha']; ?></td>
                                    <td><?php echo $excusa['motivo']; ?></td>
                                    <td>
                                        <div class="flex gap-2">
                                            <button class="badge-status badge-normal" style="cursor:pointer;border:none;">Aprobar</button>
                                            <button class="badge-status badge-inasistencia" style="cursor:pointer;border:none;">Rechazar</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center py-8 text-slate-400">No hay excusas pendientes</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="dashboard-card">
            <div class="card-header">
                <h2 class="card-title">Excusas Revisadas</h2>
            </div>
            <div class="card-body" style="padding: 0;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Aprendiz</th>
                            <th>Fecha Inasistencia</th>
                            <th>Motivo</th>
                            <th>Estado</th>
                            <th>Fecha Revision</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($excusas_revisadas)): ?>
                            <?php foreach ($excusas_revisadas as $excusa): ?>
                                <tr>
                                    <td class="font-medium"><?php echo $excusa['nombre_aprendiz']; ?></td>
                                    <td><?php echo $excusa['fecha']; ?></td>
                                    <td><?php echo $excusa['motivo']; ?></td>
                                    <td><span class="badge-status badge-<?php echo strtolower($excusa['estado']); ?>"><?php echo $excusa['estado']; ?></span></td>
                                    <td><?php echo $excusa['fecha_revision']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-8 text-slate-400">No hay excusas revisadas</td>
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