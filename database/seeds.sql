USE `schema`;

-- 1. Insertar Roles del sistema
INSERT INTO `roles` (`id_rol`, `nombre`) VALUES
(1, 'Administrador'),
(2, 'Instructor'),
(3, 'Aprendiz');

-- 2. Insertar Usuarios de Prueba
-- Nota: La contraseña para todos en esta prueba inicial es '123456' 
-- (En producción se deben encriptar con password_hash de PHP)
INSERT INTO `usuario` (`numero_documento`, `nombre`, `apellido`, `correo`, `password`, `rfid_uid`, `id_rol`, `estado`) VALUES
('1000000001', 'Admin', 'SENA', 'admin@sena.edu.co', '123456', NULL, 1, 'Activo'),
('1000000002', 'Juan Camilo', 'Vanegas González', 'jvanegas@sena.edu.co', '123456', NULL, 2, 'Activo'),
('1000000003', 'Carlos', 'Pérez', 'cperez@gmail.com', '123456', 'RFID-A1B2C3D4', 3, 'Activo'),
('1000000004', 'María', 'López', 'mlopez@gmail.com', '123456', 'RFID-E5F6G7H8', 3, 'Activo');

-- 3. Insertar Ficha (Asignada al Instructor Juan Camilo)
-- Horario de ejemplo: 07:00:00 a 13:00:00
INSERT INTO `ficha` (`codigo_ficha`, `nombre_programa`, `hora_entrada`, `hora_salida`, `id_instructor_encargado`) VALUES
('2670123', 'Análisis y Desarrollo de Software (ADSO)', '07:00:00', '13:00:00', 2);

-- 4. Asignar Aprendices a la Ficha
INSERT INTO `usuario_has_ficha` (`id_aprendiz`, `id_ficha`) VALUES
(3, 1), -- Carlos Pérez a ADSO
(4, 1); -- María López a ADSO