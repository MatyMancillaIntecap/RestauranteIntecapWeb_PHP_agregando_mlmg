-- Esquema MySQL del sistema Restaurante Escuela INTECAP
-- Base de datos: intecap_proy_rest_m

CREATE DATABASE IF NOT EXISTS intecap_proy_rest_m
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE intecap_proy_rest_m;

-- Tabla roles
CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    descripcion VARCHAR(255) NULL,
    max_almuerzos INT NOT NULL DEFAULT 2
) ENGINE=InnoDB;

-- Tabla usuarios
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    rol_id INT NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    nit_facturacion VARCHAR(20) NOT NULL DEFAULT 'C/F',
    CONSTRAINT fk_usuarios_rol FOREIGN KEY (rol_id) REFERENCES roles(id)
) ENGINE=InnoDB;

CREATE UNIQUE INDEX idx_usuarios_email ON usuarios(email);

-- Tabla formas_pago
CREATE TABLE IF NOT EXISTS formas_pago (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL
) ENGINE=InnoDB;

-- Tabla menu_diario
CREATE TABLE IF NOT EXISTS menu_diario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_plato VARCHAR(150) NOT NULL,
    descripcion VARCHAR(1000) NULL,
    precio DECIMAL(10,2) NOT NULL,
    stock INT NOT NULL,
    cantidad_solicitada INT NOT NULL DEFAULT 0,
    imagen_url VARCHAR(255) NULL,
    fecha DATE NOT NULL,
    hora_habilitacion DATETIME NOT NULL,
    es_dieta TINYINT(1) NOT NULL DEFAULT 0,
    estado VARCHAR(20) NOT NULL DEFAULT 'Disponible'
) ENGINE=InnoDB;

-- Tabla reservas
CREATE TABLE IF NOT EXISTS reservas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    menu_id INT NOT NULL,
    forma_pago_id INT NOT NULL,
    cantidad INT NOT NULL,
    donde_consume VARCHAR(20) NOT NULL DEFAULT 'En restaurante',
    fecha_reserva DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_consumo DATE NOT NULL,
    estado VARCHAR(20) NOT NULL DEFAULT 'Activa',
    nit_facturacion VARCHAR(20) NOT NULL DEFAULT 'C/F',
    CONSTRAINT fk_reservas_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    CONSTRAINT fk_reservas_menu FOREIGN KEY (menu_id) REFERENCES menu_diario(id),
    CONSTRAINT fk_reservas_forma_pago FOREIGN KEY (forma_pago_id) REFERENCES formas_pago(id)
) ENGINE=InnoDB;

-- Tabla historial_login
CREATE TABLE IF NOT EXISTS historial_login (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    fecha_login DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_historial_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
) ENGINE=InnoDB;

-- Tabla solicitudes_restablecimiento_password
CREATE TABLE IF NOT EXISTS solicitudes_restablecimiento_password (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    usuario_admin_id INT NULL,
    estado VARCHAR(20) NOT NULL DEFAULT 'Pendiente',
    fecha_solicitud DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_atencion DATETIME NULL,
    CONSTRAINT fk_solicitud_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    CONSTRAINT fk_solicitud_admin FOREIGN KEY (usuario_admin_id) REFERENCES usuarios(id)
) ENGINE=InnoDB;

-- Datos base
INSERT INTO roles (nombre, descripcion, max_almuerzos) VALUES
    ('Administrador', 'Acceso total al sistema', 0),
    ('Cocina', 'Gestión de menús y reservas del día', 0),
    ('Empleado', 'Reserva de almuerzos', 2);

INSERT INTO formas_pago (nombre) VALUES ('Efectivo'), ('Carnet');

-- El usuario administrador inicial se crea ejecutando: php database/seed_admin.php
-- (el hash de contraseña debe generarse con password_hash() de PHP, no puede escribirse a mano en SQL)
