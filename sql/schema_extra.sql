-- Ejecutar DESPUÉS de importar clientes, grupos, usuarios, mqtt_brokers y configuracion_correo.
SET NAMES utf8mb4;
CREATE TABLE IF NOT EXISTS roles (
  id_rol int NOT NULL PRIMARY KEY, nombre varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT IGNORE INTO roles VALUES (1,'superadmin'),(2,'viewer'),(3,'docente'),(4,'creator');

CREATE TABLE IF NOT EXISTS enlaces_registro (
  id_enlace int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  id_cliente int NOT NULL, id_grupo int NOT NULL,
  token char(48) NOT NULL UNIQUE,
  vence_en date NULL, max_usos int NULL, usos int NOT NULL DEFAULT 0,
  activo tinyint(1) NOT NULL DEFAULT 1,
  fecha_creacion timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_cliente) REFERENCES clientes(id_cliente) ON DELETE CASCADE,
  FOREIGN KEY (id_grupo) REFERENCES grupos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS verificaciones_email (
  id int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  id_usuario int NOT NULL,
  tipo enum('registro','activacion','cambio_email') NOT NULL,
  email_nuevo varchar(150) NULL,
  token_hash char(64) NOT NULL UNIQUE,
  expira datetime NOT NULL, usado_en datetime NULL,
  FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
