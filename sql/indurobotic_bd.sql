-- InduRobotic · base de datos completa (MySQL 8)
-- ATENCIÓN: borra y recrea las tablas. Úsala en una base nueva o si no necesitas los datos actuales.
CREATE DATABASE IF NOT EXISTS bd_kunturvr CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE bd_kunturvr;
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS mqtt_brokers, usuarios, enlaces_registro, grupos, clientes, roles, configuracion_correo, verificaciones_email;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE roles (
  id_rol int NOT NULL PRIMARY KEY,
  nombre varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE clientes (
  id_cliente int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  razon_social varchar(200) NOT NULL,
  ruc varchar(20) NULL,
  direccion varchar(255) NULL,
  telefono varchar(50) NULL,
  email varchar(150) NULL,
  plan_tipo enum('demo','standard','premium') NOT NULL DEFAULT 'demo',
  max_usuarios int NULL DEFAULT 1 COMMENT '0 = ilimitado (premium)',
  plan_vence_en date NULL,
  registro_token varchar(64) NULL,
  logo_url varchar(255) NULL,
  tema enum('claro','oscuro') NOT NULL DEFAULT 'oscuro',
  estado enum('pending','active','suspended') NOT NULL DEFAULT 'pending',
  fecha_creacion timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY idx_clientes_registro_token (registro_token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE grupos (
  id int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  client_id int NOT NULL,
  nombre varchar(100) NOT NULL,
  descripcion varchar(255) NULL,
  docente varchar(150) NULL,
  creado_en datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_grupos_client (client_id),
  CONSTRAINT fk_grupos_client FOREIGN KEY (client_id) REFERENCES clientes(id_cliente) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE enlaces_registro (
  id_enlace int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  id_cliente int NOT NULL,
  id_grupo int NOT NULL,
  token char(48) NOT NULL UNIQUE,
  vence_en date NULL,
  max_usos int NULL,
  usos int NOT NULL DEFAULT 0,
  activo tinyint(1) NOT NULL DEFAULT 1,
  fecha_creacion timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_enlace_cliente FOREIGN KEY (id_cliente) REFERENCES clientes(id_cliente) ON DELETE CASCADE,
  CONSTRAINT fk_enlace_grupo FOREIGN KEY (id_grupo) REFERENCES grupos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE usuarios (
  id_usuario int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  id_cliente int NULL,
  id_rol int NOT NULL,
  nombre varchar(150) NOT NULL,
  usuario varchar(100) NOT NULL,
  password_hash varchar(255) NOT NULL,
  email varchar(150) NOT NULL,
  email_verificado tinyint(1) NOT NULL DEFAULT 0,
  -- verificación por correo (un solo token vigente por usuario)
  verif_tipo enum('registro','activacion','cambio_email') NULL,
  verif_token_hash char(64) NULL,
  verif_expira datetime NULL,
  email_pendiente varchar(150) NULL,
  direccion varchar(255) NULL,
  telefono varchar(50) NULL,
  fecha_nacimiento date NULL,
  sexo enum('varon','mujer') NULL,
  departamento varchar(80) NULL,
  provincia varchar(80) NULL,
  distrito varchar(80) NULL,
  grupo_id int NULL,
  estado tinyint NOT NULL DEFAULT 1,
  tema enum('claro','oscuro') NOT NULL DEFAULT 'oscuro',
  id_enlace_registro int NULL,
  fecha_creacion timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  session_token varchar(255) NULL,
  ultimo_acceso datetime NULL,
  foto_perfil varchar(255) NULL,
  token_publico varchar(64) NULL,
  UNIQUE KEY usuario (usuario),
  UNIQUE KEY email (email),
  UNIQUE KEY token_publico (token_publico),
  KEY idx_verif_token (verif_token_hash),
  KEY idx_usuarios_cliente (id_cliente),
  KEY idx_usuarios_grupo (grupo_id),
  CONSTRAINT fk_usuarios_cliente FOREIGN KEY (id_cliente) REFERENCES clientes(id_cliente) ON DELETE CASCADE,
  CONSTRAINT fk_usuarios_rol FOREIGN KEY (id_rol) REFERENCES roles(id_rol),
  CONSTRAINT fk_usuarios_grupo FOREIGN KEY (grupo_id) REFERENCES grupos(id) ON DELETE SET NULL,
  CONSTRAINT fk_usuarios_enlace FOREIGN KEY (id_enlace_registro) REFERENCES enlaces_registro(id_enlace) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE mqtt_brokers (
  id_broker int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  id_cliente int NULL COMMENT 'NULL = broker global',
  nombre varchar(100) NOT NULL,
  host varchar(255) NOT NULL,
  puerto int NOT NULL DEFAULT 8884,
  protocolo enum('ws','wss') NOT NULL DEFAULT 'wss',
  usuario_mqtt varchar(150) NULL,
  password_mqtt varchar(255) NULL COMMENT 'cifrado AES-256-CBC con APP_KEY',
  usa_tls tinyint(1) NOT NULL DEFAULT 1,
  ruta_certificado varchar(255) NULL,
  ruta_llave_privada varchar(255) NULL,
  estado tinyint NOT NULL DEFAULT 1,
  id_creado_por int NULL,
  fecha_creacion timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_mqtt_cliente (id_cliente),
  CONSTRAINT fk_mqtt_cliente FOREIGN KEY (id_cliente) REFERENCES clientes(id_cliente) ON DELETE CASCADE,
  CONSTRAINT fk_mqtt_creador FOREIGN KEY (id_creado_por) REFERENCES usuarios(id_usuario) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE configuracion_correo (
  id_config tinyint NOT NULL DEFAULT 1 PRIMARY KEY,
  host varchar(255) NULL,
  puerto int NOT NULL DEFAULT 587,
  usuario varchar(150) NULL,
  password varchar(255) NULL COMMENT 'cifrado AES-256-CBC con APP_KEY',
  remitente_nombre varchar(150) NULL DEFAULT 'InduRobotic',
  remitente_email varchar(150) NULL,
  seguridad enum('tls','ssl','ninguna') NOT NULL DEFAULT 'tls',
  fecha_actualizacion timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT chk_config_correo_singleton CHECK (id_config = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- Datos iniciales ----------
INSERT INTO roles VALUES (1,'superadmin'),(2,'viewer'),(3,'docente'),(4,'creator');
INSERT INTO configuracion_correo (id_config) VALUES (1);
INSERT INTO mqtt_brokers (id_cliente,nombre,host,puerto,protocolo,usa_tls) VALUES (NULL,'EMQX Público (solo pruebas)','broker.emqx.io',8084,'wss',1);
-- Superadmin: usuario "admin" / contraseña "Indu#2026admin"  (CÁMBIALA al primer ingreso)
INSERT INTO usuarios (id_cliente,id_rol,nombre,usuario,password_hash,email,email_verificado,estado,tema,token_publico)
VALUES (NULL,1,'Administrador','admin','$2y$10$UJ.iOThVQXbhUX4rWQQAWePTPPHQLmrdSfMKBs0ahUo.D2RI0/U2K','admin@indurobotic.local',1,1,'oscuro',SHA1(UUID()));
