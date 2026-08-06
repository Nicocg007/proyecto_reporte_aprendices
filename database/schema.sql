-- MySQL Workbench Forward Engineering Corregido

SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

-- -----------------------------------------------------
-- Schema sena_asistencia
-- -----------------------------------------------------
CREATE SCHEMA IF NOT EXISTS `sena_asistencia` DEFAULT CHARACTER SET utf8mb4 ;
USE `sena_asistencia` ;

-- -----------------------------------------------------
-- Table `sena_asistencia`.`roles`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `sena_asistencia`.`roles` (
  `id_rol` INT NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(45) NOT NULL,
  PRIMARY KEY (`id_rol`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `sena_asistencia`.`usuario`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `sena_asistencia`.`usuario` (
  `id_usuario` INT NOT NULL AUTO_INCREMENT,
  `numero_documento` VARCHAR(20) NOT NULL,
  `nombre` VARCHAR(45) NOT NULL,
  `apellido` VARCHAR(45) NOT NULL,
  `correo` VARCHAR(100) NULL,
  `password` VARCHAR(255) NOT NULL,
  `rfid_uid` VARCHAR(45) NULL,
  `id_rol` INT NOT NULL,
  `estado` ENUM('Activo', 'Inactivo') NULL DEFAULT 'Activo',
  PRIMARY KEY (`id_usuario`),
  UNIQUE INDEX `numero_documento_UNIQUE` (`numero_documento` ASC),
  UNIQUE INDEX `rfid_uid_UNIQUE` (`rfid_uid` ASC),
  INDEX `fk_usuario_roles_idx` (`id_rol` ASC),
  CONSTRAINT `fk_usuario_roles`
    FOREIGN KEY (`id_rol`)
    REFERENCES `sena_asistencia`.`roles` (`id_rol`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `sena_asistencia`.`ficha`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `sena_asistencia`.`ficha` (
  `id_ficha` INT NOT NULL AUTO_INCREMENT,
  `codigo_ficha` VARCHAR(20) NOT NULL,
  `nombre_programa` VARCHAR(100) NOT NULL,
  `hora_entrada` TIME NOT NULL,
  `hora_salida` TIME NOT NULL,
  `id_instructor_encargado` INT NOT NULL,
  PRIMARY KEY (`id_ficha`),
  UNIQUE INDEX `codigo_ficha_UNIQUE` (`codigo_ficha` ASC),
  INDEX `fk_ficha_usuario1_idx` (`id_instructor_encargado` ASC),
  CONSTRAINT `fk_ficha_usuario1`
    FOREIGN KEY (`id_instructor_encargado`)
    REFERENCES `sena_asistencia`.`usuario` (`id_usuario`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `sena_asistencia`.`usuario_has_ficha`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `sena_asistencia`.`usuario_has_ficha` (
  `id_aprendiz_ficha` INT NOT NULL AUTO_INCREMENT,
  `id_aprendiz` INT NOT NULL,
  `id_ficha` INT NOT NULL,
  PRIMARY KEY (`id_aprendiz_ficha`),
  INDEX `fk_usuario_has_ficha_ficha1_idx` (`id_ficha` ASC),
  INDEX `fk_usuario_has_ficha_usuario1_idx` (`id_aprendiz` ASC),
  CONSTRAINT `fk_usuario_has_ficha_usuario1`
    FOREIGN KEY (`id_aprendiz`)
    REFERENCES `sena_asistencia`.`usuario` (`id_usuario`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_usuario_has_ficha_ficha1`
    FOREIGN KEY (`id_ficha`)
    REFERENCES `sena_asistencia`.`ficha` (`id_ficha`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `sena_asistencia`.`ingreso`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `sena_asistencia`.`ingreso` (
  `id_ingreso` INT NOT NULL AUTO_INCREMENT,
  `id_aprendiz` INT NOT NULL,
  `fecha` DATE NOT NULL,
  `hora_entrada_registrada` TIME NULL,
  `hora_salida_registrada` TIME NULL,
  `minutos_retardo` INT NULL DEFAULT 0,
  `salida_temprana` TINYINT(1) NULL DEFAULT 0,
  `estado_asistencia` ENUM('Normal', 'Retardo', 'Inasistencia', 'Salida Temprana') NULL DEFAULT 'Normal',
  PRIMARY KEY (`id_ingreso`),
  INDEX `fk_ingreso_usuario1_idx` (`id_aprendiz` ASC),
  CONSTRAINT `fk_ingreso_usuario1`
    FOREIGN KEY (`id_aprendiz`)
    REFERENCES `sena_asistencia`.`usuario` (`id_usuario`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `sena_asistencia`.`excusa`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `sena_asistencia`.`excusa` (
  `id_excusa` INT NOT NULL AUTO_INCREMENT,
  `id_aprendiz` INT NOT NULL,
  `id_ingreso` INT NULL,
  `fecha_inasistencia` DATE NOT NULL,
  `archivo_adjunto` VARCHAR(255) NOT NULL,
  `observacion` TEXT NOT NULL,
  `estado` ENUM('Pendiente', 'Aprobada', 'Rechazada') NULL DEFAULT 'Pendiente',
  `id_instructor_revisor` INT NULL,
  `fecha_revision` DATETIME NULL,
  PRIMARY KEY (`id_excusa`),
  INDEX `fk_excusa_usuario1_idx` (`id_aprendiz` ASC),
  INDEX `fk_excusa_ingreso1_idx` (`id_ingreso` ASC),
  INDEX `fk_excusa_usuario2_idx` (`id_instructor_revisor` ASC),
  CONSTRAINT `fk_excusa_usuario1`
    FOREIGN KEY (`id_aprendiz`)
    REFERENCES `sena_asistencia`.`usuario` (`id_usuario`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_excusa_ingreso1`
    FOREIGN KEY (`id_ingreso`)
    REFERENCES `sena_asistencia`.`ingreso` (`id_ingreso`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_excusa_usuario2`
    FOREIGN KEY (`id_instructor_revisor`)
    REFERENCES `sena_asistencia`.`usuario` (`id_usuario`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;