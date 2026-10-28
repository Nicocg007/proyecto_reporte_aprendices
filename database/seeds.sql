USE `sena_asistencia`;

-- 1. Insertar Roles del sistema
INSERT INTO `roles` (`id_rol`, `nombre`) VALUES
(1, 'Administrador'),
(2, 'Instructor'),
(3, 'Aprendiz');

-- 2. Insertar Usuarios de Prueba
-- Se corrigió la columna 'contraseña' por 'password'
-- Las contraseñas van con hash (bcrypt). Usuarios de prueba; cambia las claves tras importar.
INSERT INTO `usuario` (`numero_documento`, `nombre`, `apellido`, `correo`, `password`, `rfid_uid`, `id_rol`, `estado`) VALUES
('1114150420', 'Admin', 'SENA', 'xivivi5170@deertees.com', '$2y$12$n.UKQWyVTDGJDZ6N1zAMJ.g/xb7Fgf./ZG.DtV/GOiHLev2sUb0Pa', NULL, 1, 'Activo'),
('1000000002', 'Instructor', 'Demo', 'instructor.demo@example.com', '$2y$12$8Afsid0bPDzd32evteNbQeYl51UBYETz5D1jgy.AoC2bjkSeiKpQS', NULL, 2, 'Activo'),
('1000000003', 'Aprendiz', 'Uno', 'aprendiz1@example.com', '$2y$12$8Afsid0bPDzd32evteNbQeYl51UBYETz5D1jgy.AoC2bjkSeiKpQS', 'RFID-A1B2C3D4', 3, 'Activo'),
('1000000004', 'Aprendiz', 'Dos', 'aprendiz2@example.com', '$2y$12$8Afsid0bPDzd32evteNbQeYl51UBYETz5D1jgy.AoC2bjkSeiKpQS', 'RFID-E5F6G7H8', 3, 'Activo');

-- 3. Insertar Ficha (Asignada al Instructor Juan Camilo)
INSERT INTO `ficha` (`codigo_ficha`, `nombre_programa`, `hora_entrada`, `hora_salida`, `id_instructor_encargado`) VALUES
('2670123', 'Análisis y Desarrollo de Software (ADSO)', '07:00:00', '13:00:00', 2);

-- 4. Asignar Aprendices a la Ficha
INSERT INTO `usuario_has_ficha` (`id_aprendiz`, `id_ficha`) VALUES
(3, 1), -- Aprendiz uno a ADSO
(4, 1); -- Aprendiz dos a ADSO