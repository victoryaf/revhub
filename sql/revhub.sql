-- ============================================================
-- RevHub — Base de datos
-- Plataforma web para la gestión de quedadas del mundo del motor
-- Alumno: Victoria Ausín Fernández
-- ============================================================

CREATE DATABASE IF NOT EXISTS revhub;
USE revhub;

-- ============================================================
-- TABLA: usuarios
-- ============================================================
CREATE TABLE usuarios (
  id_usuario     INT AUTO_INCREMENT PRIMARY KEY,
  nombre         VARCHAR(100)  NOT NULL,
  apellidos      VARCHAR(150)  NOT NULL,
  username       VARCHAR(50)   NOT NULL UNIQUE,
  email          VARCHAR(150)  NOT NULL UNIQUE,
  contrasena     VARCHAR(255)  NOT NULL,
  foto_perfil    VARCHAR(255),
  descripcion    TEXT,
  rol            ENUM('usuario', 'organizador', 'admin', 'bloqueado') NOT NULL DEFAULT 'usuario',
  fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- TABLA: vehiculos
-- ============================================================
CREATE TABLE vehiculos (
  id_vehiculo   INT AUTO_INCREMENT PRIMARY KEY,
  id_usuario    INT          NOT NULL,
  marca         VARCHAR(100) NOT NULL,
  modelo        VARCHAR(100) NOT NULL,
  anio          YEAR         NOT NULL,
  color         VARCHAR(50)  NOT NULL,
  tipo_vehiculo VARCHAR(100)  NOT NULL,
  matricula     VARCHAR(20)  NOT NULL UNIQUE,
  descripcion   TEXT,
  modificaciones TEXT,
  imagen        VARCHAR(255),
  FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
);

-- ============================================================
-- TABLA: eventos
-- ============================================================
CREATE TABLE eventos (
  id_evento         INT AUTO_INCREMENT PRIMARY KEY,
  id_usuario        INT          NOT NULL,
  nombre            VARCHAR(200) NOT NULL,
  descripcion       TEXT,
  fecha             DATE         NOT NULL,
  hora              TIME         NOT NULL,
  ubicacion         VARCHAR(255) NOT NULL,
  max_participantes INT          NOT NULL DEFAULT 50,
  tipo_evento       VARCHAR(50)  NOT NULL,
  tipos_admitidos   VARCHAR(255),
  marcas_admitidas VARCHAR(500),
  cartel            VARCHAR(255),
  fecha_creacion    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
);

-- ============================================================
-- TABLA: inscripciones
-- ============================================================
CREATE TABLE inscripciones (
  id_inscripcion    INT AUTO_INCREMENT PRIMARY KEY,
  id_usuario        INT      NOT NULL,
  id_evento         INT      NOT NULL,
  id_vehiculo       INT      NOT NULL,
  fecha_inscripcion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_usuario)  REFERENCES usuarios(id_usuario),
  FOREIGN KEY (id_evento)   REFERENCES eventos(id_evento),
  FOREIGN KEY (id_vehiculo) REFERENCES vehiculos(id_vehiculo)
);

-- ============================================================
-- TABLA: comentarios
-- ============================================================
CREATE TABLE comentarios (
  id_comentario INT AUTO_INCREMENT PRIMARY KEY,
  id_usuario    INT      NOT NULL,
  id_evento     INT      NOT NULL,
  texto         TEXT     NOT NULL,
  fecha         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario),
  FOREIGN KEY (id_evento)  REFERENCES eventos(id_evento)
);

CREATE TABLE mensajes (
  id_mensaje    INT AUTO_INCREMENT PRIMARY KEY,
  id_remitente  INT      NOT NULL,
  id_destinatario INT    NOT NULL,
  id_evento     INT      DEFAULT NULL,
  asunto        VARCHAR(200) NOT NULL,
  texto         TEXT     NOT NULL,
  leido         TINYINT  NOT NULL DEFAULT 0,
  fecha         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_remitente)    REFERENCES usuarios(id_usuario),
  FOREIGN KEY (id_destinatario) REFERENCES usuarios(id_usuario),
  FOREIGN KEY (id_evento)       REFERENCES eventos(id_evento) ON DELETE SET NULL
);

-- ============================================================
-- DATOS DE PRUEBA
-- ============================================================

INSERT INTO usuarios (nombre, apellidos, username, email, contrasena, rol) VALUES
('Victoria', 'Ausín Fernández',  'victoria', 'victoria@revhub.es', '$2y$10$dHt41aYNkx3QO94u.1lqH.JjFbw2KxN6bb30gAEvCL5udXmfY.Qz.', 'admin'),
('Antonio',  'Martínez López',   'antonio',  'antonio@revhub.es',  '$2y$10$dHt41aYNkx3QO94u.1lqH.JjFbw2KxN6bb30gAEvCL5udXmfY.Qz.', 'organizador'),
('Lucía',    'Pérez García',     'lucia',    'lucia@revhub.es',    '$2y$10$dHt41aYNkx3QO94u.1lqH.JjFbw2KxN6bb30gAEvCL5udXmfY.Qz.', 'usuario'),
('Carlos',   'Rodríguez Vidal',  'carlos',   'carlos@revhub.es',   '$2y$10$dHt41aYNkx3QO94u.1lqH.JjFbw2KxN6bb30gAEvCL5udXmfY.Qz.', 'usuario'),
('Marta',    'Fernández Torres', 'marta',    'marta@revhub.es',    '$2y$10$dHt41aYNkx3QO94u.1lqH.JjFbw2KxN6bb30gAEvCL5udXmfY.Qz.', 'usuario');

INSERT INTO vehiculos (id_usuario, marca, modelo, anio, color, tipo_vehiculo, matricula, descripcion) VALUES
(2, 'Ford',     'Mustang',   1969, 'Rojo',    'clasico',   'GH-3421-B', 'Mustang clásico restaurado, motor V8 original'),
(3, 'Honda',    'Civic EK9', 1998, 'Plata',   'tuning',    'OR-1122-C', 'Civic con preparación JDM'),
(4, 'Porsche',  '911 GT3',   2018, 'Amarillo','deportivo', 'PO-5544-D', 'GT3 de pista'),
(5, 'Kawasaki', 'Z900',      2021, 'Negro',   'moto',      'MA-9988-A', 'Naked sport, escape Akrapovic'),
(1, 'SEAT',     'Ibiza',     2005, 'Azul',    'tuning',    'LU-7712-F', 'Preparación motorsport');

INSERT INTO eventos (id_usuario, nombre, descripcion, fecha, hora, ubicacion, max_participantes, tipo_evento) VALUES
(2, 'Quedada Clásicos Valdeorras 2026', 'Encuentro de vehículos clásicos en Petín.',      '2026-06-15', '10:00:00', 'Petín, Ourense',    50, 'quedada'),
(2, 'Ruta Motera Galicia 2026',         'Ruta de motos por la costa gallega.',             '2026-06-22', '09:00:00', 'A Coruña → Santiago', 40, 'ruta'),
(1, 'Exposición Tuning Vigo 2026',      'Exposición de vehículos tuning en Vigo.',         '2026-07-05', '11:00:00', 'Vigo, Pontevedra',  70, 'exposicion'),
(2, 'Concentración Deportivos Ferrol',  'Concentración de coches deportivos en Ferrol.',   '2026-07-20', '10:00:00', 'Ferrol, A Coruña',  60, 'quedada');

INSERT INTO inscripciones (id_usuario, id_evento, id_vehiculo) VALUES
(2, 1, 1),
(3, 1, 2),
(4, 1, 3),
(5, 2, 4),
(3, 3, 2),
(4, 3, 3);

INSERT INTO comentarios (id_usuario, id_evento, texto) VALUES
(3, 1, '¿Hay zona de aparcamiento para los vehículos participantes?'),
(4, 1, 'El año pasado fui y fue genial, muy buena organización.'),
(5, 2, '¿Se admiten motos de menos de 600cc?'),
(3, 3, 'Apuntada con el Civic, espero que haya muchos tuning este año.');
