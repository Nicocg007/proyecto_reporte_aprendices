<?php
/* ========================================
   SENA Control - Cerrar Sesion
   ======================================== */

require_once __DIR__ . '/model/seguridad_helper.php';
cerrarSesionSegura();

header('Location: views/login.php?logout=1');
exit();
