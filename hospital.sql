-- base de datos

CREATE DATABASE IF NOT EXISTS hospital_web CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hospital_web;

-- Usuarios (auth)
CREATE TABLE usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  email VARCHAR(150) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  rol ENUM('admin','doctor','paciente') DEFAULT 'paciente',
  activo TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Especialidades
CREATE TABLE especialidades (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL
);

-- Doctores
CREATE TABLE doctores (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  especialidad_id INT,
  cedula VARCHAR(30),
  telefono VARCHAR(20),
  disponible TINYINT(1) DEFAULT 1,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
  FOREIGN KEY (especialidad_id) REFERENCES especialidades(id)
);

-- Pacientes
CREATE TABLE pacientes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  fecha_nacimiento DATE,
  telefono VARCHAR(20),
  direccion TEXT,
  tipo_sangre VARCHAR(5),
  alergias TEXT,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- Citas
CREATE TABLE citas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  paciente_id INT NOT NULL,
  doctor_id INT NOT NULL,
  fecha DATE NOT NULL,
  hora TIME NOT NULL,
  motivo TEXT,
  estado ENUM('pendiente','confirmada','cancelada','completada') DEFAULT 'pendiente',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (paciente_id) REFERENCES pacientes(id),
  FOREIGN KEY (doctor_id) REFERENCES doctores(id)
);

-- Historial clínico
CREATE TABLE historial (
  id INT AUTO_INCREMENT PRIMARY KEY,
  cita_id INT,
  paciente_id INT NOT NULL,
  doctor_id INT NOT NULL,
  diagnostico TEXT,
  tratamiento TEXT,
  medicamentos TEXT,
  notas TEXT,
  fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (cita_id) REFERENCES citas(id),
  FOREIGN KEY (paciente_id) REFERENCES pacientes(id),
  FOREIGN KEY (doctor_id) REFERENCES doctores(id)
);

-- Datos de prueba
INSERT INTO especialidades (nombre) VALUES
  ('Medicina General'),('Cardiología'),('Pediatría'),
  ('Dermatología'),('Neurología'),('Traumatología');

INSERT INTO usuarios (nombre, email, password, rol) VALUES
  ('Admin Hospital', 'admin@hospital.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
  ('Dr. Carlos Ruiz', 'doctor@hospital.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'doctor'),
  ('Juan Pérez', 'paciente@hospital.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'paciente');
-- Password: password
