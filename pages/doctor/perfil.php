<?php
$pageTitle = 'Mi Perfil';
require_once __DIR__ . '/../../config/config.php';
requireRole('doctor');
require_once __DIR__ . '/../../includes/db.php';

$usuario_id = $_SESSION['usuario_id'];
$mensaje = '';
$error = '';

// Verificar si el usuario existe como doctor, si no, crearlo
$checkDoctor = $conn->prepare("SELECT d.id FROM doctores d WHERE d.usuario_id = ?");
$checkDoctor->bind_param('i', $usuario_id);
$checkDoctor->execute();
$existeDoctor = $checkDoctor->get_result()->fetch_assoc();

if (!$existeDoctor) {
    $crearDoctor = $conn->prepare("INSERT INTO doctores (usuario_id, disponible) VALUES (?, 1)");
    $crearDoctor->bind_param('i', $usuario_id);
    $crearDoctor->execute();
}

// Obtener datos actuales del doctor
$stmt = $conn->prepare("
    SELECT u.nombre, u.email,
           d.id, d.cedula, d.telefono, d.especialidad_id, d.disponible,
           e.nombre AS especialidad_nombre
    FROM usuarios u
    LEFT JOIN doctores d ON u.id = d.usuario_id
    LEFT JOIN especialidades e ON d.especialidad_id = e.id
    WHERE u.id = ?
");
$stmt->bind_param('i', $usuario_id);
$stmt->execute();
$resultado = $stmt->get_result();
$doctor = $resultado->fetch_assoc();

// Valores por defecto si hay campos nulos
if (!$doctor) {
    $doctor = [
        'nombre' => $_SESSION['nombre'],
        'email' => '',
        'id' => 0,
        'cedula' => '',
        'telefono' => '',
        'especialidad_id' => null,
        'disponible' => 1,
        'especialidad_nombre' => null
    ];
} else {
    $doctor['nombre'] = $doctor['nombre'] ?? $_SESSION['nombre'];
    $doctor['email'] = $doctor['email'] ?? '';
    $doctor['id'] = $doctor['id'] ?? 0;
    $doctor['cedula'] = $doctor['cedula'] ?? '';
    $doctor['telefono'] = $doctor['telefono'] ?? '';
    $doctor['especialidad_id'] = $doctor['especialidad_id'] ?? null;
    $doctor['disponible'] = $doctor['disponible'] ?? 1;
    $doctor['especialidad_nombre'] = $doctor['especialidad_nombre'] ?? null;
}

// Obtener todas las especialidades
$especialidades = $conn->query("SELECT id, nombre FROM especialidades ORDER BY nombre");

// Procesar actualizacion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $cedula = trim($_POST['cedula'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $especialidad_id = !empty($_POST['especialidad_id']) ? $_POST['especialidad_id'] : null;
    $disponible = isset($_POST['disponible']) ? 1 : 0;
    
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (!$nombre) {
        $error = 'El nombre es requerido';
    } else {
        // Actualizar usuario
        $updateUser = $conn->prepare("UPDATE usuarios SET nombre = ? WHERE id = ?");
        $updateUser->bind_param('si', $nombre, $usuario_id);
        $updateUser->execute();
        
        // Verificar si existe doctor para actualizar o insertar
        $checkExist = $conn->prepare("SELECT id FROM doctores WHERE usuario_id = ?");
        $checkExist->bind_param('i', $usuario_id);
        $checkExist->execute();
        $existe = $checkExist->get_result()->fetch_assoc();
        
        if ($existe) {
            $updateDoctor = $conn->prepare("UPDATE doctores SET cedula = ?, telefono = ?, especialidad_id = ?, disponible = ? WHERE usuario_id = ?");
            $updateDoctor->bind_param('ssiii', $cedula, $telefono, $especialidad_id, $disponible, $usuario_id);
            $updateDoctor->execute();
        } else {
            $insertDoctor = $conn->prepare("INSERT INTO doctores (usuario_id, cedula, telefono, especialidad_id, disponible) VALUES (?, ?, ?, ?, ?)");
            $insertDoctor->bind_param('issii', $usuario_id, $cedula, $telefono, $especialidad_id, $disponible);
            $insertDoctor->execute();
        }
        
        // Cambiar contrasena si se proporciono
        if ($new_password) {
            if (strlen($new_password) < 6) {
                $error = 'La contrasena debe tener al menos 6 caracteres';
            } elseif ($new_password !== $confirm_password) {
                $error = 'Las contrasenas no coinciden';
            } else {
                $hash = password_hash($new_password, PASSWORD_DEFAULT);
                $updatePass = $conn->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
                $updatePass->bind_param('si', $hash, $usuario_id);
                $updatePass->execute();
                $mensaje = 'Perfil y contrasena actualizados correctamente';
            }
        } else {
            $mensaje = 'Perfil actualizado correctamente';
        }
        
        if (!$error) {
            $_SESSION['nombre'] = $nombre;
            header("Location: perfil.php?success=1");
            exit;
        }
    }
}

$success = isset($_GET['success']);
?>

<?php include __DIR__ . '/../../includes/header.php'; ?>

<div class="layout">
  <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

  <div class="main">
    <div class="topbar">
      <div class="topbar-left">
        <h1>Mi Perfil Profesional</h1>
        <p>Gestiona tu informacion medica</p>
      </div>
      <div class="topbar-right">
        <div class="topbar-avatar"><?= strtoupper(substr($_SESSION['nombre'],0,2)) ?></div>
      </div>
    </div>

    <div class="content">
      <?php if ($success): ?>
        <div class="alert alert-success">Perfil actualizado correctamente</div>
      <?php endif; ?>
      <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <!-- Profile Layout mejorado -->
      <div class="profile-container">
        <!-- Columna izquierda - Datos Profesionales -->
        <div class="profile-card">
          <div class="profile-card-header">
            <div class="profile-card-icon">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                <circle cx="12" cy="7" r="4"/>
              </svg>
            </div>
            <div>
              <h2>Datos Profesionales</h2>
              <p>Informacion personal y laboral</p>
            </div>
          </div>
          
          <form method="POST" class="profile-form">
            <div class="form-row">
              <div class="form-group full-width">
                <label>Nombre completo</label>
                <input type="text" name="nombre" class="form-control" 
                       value="<?= htmlspecialchars($doctor['nombre']) ?>" required>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group full-width">
                <label>Correo electronico</label>
                <input type="email" class="form-control" 
                       value="<?= htmlspecialchars($doctor['email']) ?>" disabled>
                <span class="form-hint">El correo no se puede modificar</span>
              </div>
            </div>

            <div class="form-row two-columns">
              <div class="form-group">
                <label>Cedula profesional</label>
                <input type="text" name="cedula" class="form-control" 
                       value="<?= htmlspecialchars($doctor['cedula']) ?>" 
                       placeholder="Numero de cedula profesional">
              </div>

              <div class="form-group">
                <label>Telefono de contacto</label>
                <input type="tel" name="telefono" class="form-control" 
                       value="<?= htmlspecialchars($doctor['telefono']) ?>" 
                       placeholder="Ej: 555-123-4567">
              </div>
            </div>

            <div class="form-row">
              <div class="form-group full-width">
                <label>Especialidad</label>
                <select name="especialidad_id" class="form-control">
                  <option value="">Seleccionar especialidad</option>
                  <?php while ($e = $especialidades->fetch_assoc()): ?>
                    <option value="<?= $e['id'] ?>" 
                            <?= ($doctor['especialidad_id'] == $e['id']) ? 'selected' : '' ?>>
                      <?= htmlspecialchars($e['nombre']) ?>
                    </option>
                  <?php endwhile; ?>
                </select>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group full-width">
                <label class="checkbox-container">
                  <input type="checkbox" name="disponible" value="1" 
                         <?= $doctor['disponible'] ? 'checked' : '' ?>>
                  <span class="checkmark"></span>
                  Disponible para recibir pacientes
                </label>
              </div>
            </div>

            <div class="form-actions">
              <button type="submit" class="btn-save">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                  <polyline points="17 21 17 13 7 13 7 21"/>
                  <polyline points="7 3 7 8 15 8"/>
                </svg>
                Guardar cambios
              </button>
            </div>
          </form>
        </div>

        <!-- Columna derecha - Seguridad y Estadisticas -->
        <div class="profile-sidebar">
          <!-- Tarjeta de Seguridad -->
          <div class="profile-card">
            <div class="profile-card-header">
              <div class="profile-card-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                  <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                </svg>
              </div>
              <div>
                <h2>Seguridad</h2>
                <p>Cambia tu contrasena</p>
              </div>
            </div>
            
            <form method="POST" class="profile-form">
              <div class="form-group">
                <label>Nueva contrasena</label>
                <input type="password" name="new_password" class="form-control" 
                       placeholder="Minimo 6 caracteres">
              </div>

              <div class="form-group">
                <label>Confirmar contrasena</label>
                <input type="password" name="confirm_password" class="form-control" 
                       placeholder="Repite la nueva contrasena">
              </div>

              <div class="form-actions">
                <button type="submit" class="btn-secondary">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                    <circle cx="12" cy="12" r="3"/>
                  </svg>
                  Actualizar contrasena
                </button>
              </div>
            </form>
          </div>

          <!-- Tarjeta de Estadisticas -->
          <div class="stats-card">
            <div class="stats-card-header">
              <div class="profile-card-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                  <polyline points="3.29 7 12 12 20.71 7"/>
                  <line x1="12" y1="22" x2="12" y2="12"/>
                </svg>
              </div>
              <div>
                <h2>Estadisticas</h2>
                <p>Resumen de tu actividad</p>
              </div>
            </div>
            
            <?php
            // Obtener el ID del doctor actual
            $getDoctorId = $conn->prepare("SELECT id FROM doctores WHERE usuario_id = ?");
            $getDoctorId->bind_param('i', $usuario_id);
            $getDoctorId->execute();
            $docData = $getDoctorId->get_result()->fetch_assoc();
            $currentDoctorId = $docData['id'] ?? 0;
            
            // Contar pacientes del doctor
            $countPacientes = $conn->prepare("SELECT COUNT(DISTINCT paciente_id) as total FROM citas WHERE doctor_id = ?");
            $countPacientes->bind_param('i', $currentDoctorId);
            $countPacientes->execute();
            $totalPacientes = $countPacientes->get_result()->fetch_assoc()['total'] ?? 0;
            
            // Contar citas completadas
            $countCitas = $conn->prepare("SELECT COUNT(*) as total FROM citas WHERE doctor_id = ? AND estado = 'completada'");
            $countCitas->bind_param('i', $currentDoctorId);
            $countCitas->execute();
            $totalCitas = $countCitas->get_result()->fetch_assoc()['total'] ?? 0;
            
            // Contar citas pendientes
            $countPendientes = $conn->prepare("SELECT COUNT(*) as total FROM citas WHERE doctor_id = ? AND estado = 'pendiente'");
            $countPendientes->bind_param('i', $currentDoctorId);
            $countPendientes->execute();
            $totalPendientes = $countPendientes->get_result()->fetch_assoc()['total'] ?? 0;
            ?>
            
            <div class="stats-list">
              <div class="stat-item">
                <div class="stat-icon patients">
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                  </svg>
                </div>
                <div class="stat-info">
                  <span class="stat-value"><?= $totalPacientes ?></span>
                  <span class="stat-label">Pacientes atendidos</span>
                </div>
              </div>
              
              <div class="stat-item">
                <div class="stat-icon completed">
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"/>
                  </svg>
                </div>
                <div class="stat-info">
                  <span class="stat-value"><?= $totalCitas ?></span>
                  <span class="stat-label">Consultas realizadas</span>
                </div>
              </div>
              
              <div class="stat-item">
                <div class="stat-icon pending">
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <polyline points="12 6 12 12 16 14"/>
                  </svg>
                </div>
                <div class="stat-info">
                  <span class="stat-value"><?= $totalPendientes ?></span>
                  <span class="stat-label">Citas pendientes</span>
                </div>
              </div>
            </div>
            
            <div class="stats-footer">
              <div class="info-row">
                <span class="info-label">ID Doctor:</span>
                <span class="info-value"><?= $currentDoctorId ?></span>
              </div>
              <div class="info-row">
                <span class="info-label">Especialidad:</span>
                <span class="info-value"><?= htmlspecialchars($doctor['especialidad_nombre'] ?? 'No asignada') ?></span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
/* Profile Container */
.profile-container {
  display: grid;
  grid-template-columns: 1fr 380px;
  gap: 28px;
  max-width: 1400px;
  margin: 0 auto;
}

/* Profile Cards */
.profile-card {
  background: white;
  border-radius: 20px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
  overflow: hidden;
  margin-bottom: 28px;
  border: 1px solid #eef2f6;
  transition: box-shadow 0.3s ease;
}

.profile-card:hover {
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
}

.profile-card-header {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 24px 28px;
  background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
  border-bottom: 1px solid #eef2f6;
}

.profile-card-icon {
  width: 48px;
  height: 48px;
  background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%);
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
}

.profile-card-header h2 {
  font-size: 18px;
  font-weight: 700;
  color: #0f172a;
  margin: 0 0 4px 0;
}

.profile-card-header p {
  font-size: 13px;
  color: #64748b;
  margin: 0;
}

/* Form Styles */
.profile-form {
  padding: 28px;
}

.form-row {
  margin-bottom: 20px;
}

.form-row.two-columns {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
}

.form-group {
  margin-bottom: 0;
}

.form-group.full-width {
  width: 100%;
}

.form-group label {
  display: block;
  font-size: 13px;
  font-weight: 600;
  color: #334155;
  margin-bottom: 8px;
}

.form-control {
  width: 100%;
  padding: 12px 16px;
  border: 1.5px solid #e2e8f0;
  border-radius: 12px;
  font-size: 14px;
  font-family: inherit;
  color: #0f172a;
  background: #ffffff;
  transition: all 0.2s ease;
}

.form-control:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-control:disabled {
  background: #f8fafc;
  color: #94a3b8;
  cursor: not-allowed;
}

.form-hint {
  display: block;
  font-size: 11px;
  color: #94a3b8;
  margin-top: 6px;
}

/* Checkbox personalizado */
.checkbox-container {
  display: flex;
  align-items: center;
  gap: 12px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
  color: #334155;
  position: relative;
  padding-left: 0;
}

.checkbox-container input {
  width: 18px;
  height: 18px;
  cursor: pointer;
  accent-color: #3b82f6;
}

/* Botones */
.form-actions {
  margin-top: 28px;
  padding-top: 20px;
  border-top: 1px solid #eef2f6;
}

.btn-save, .btn-secondary {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  padding: 12px 24px;
  border-radius: 12px;
  font-size: 14px;
  font-weight: 600;
  font-family: inherit;
  cursor: pointer;
  transition: all 0.2s ease;
  border: none;
}

.btn-save {
  background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%);
  color: white;
}

.btn-save:hover {
  background: linear-gradient(135deg, #1d4ed8 0%, #2563eb 100%);
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
}

.btn-secondary {
  background: white;
  color: #2563eb;
  border: 1.5px solid #2563eb;
}

.btn-secondary:hover {
  background: #eff6ff;
  transform: translateY(-2px);
}

/* Stats Card */
.stats-card {
  background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
  border-radius: 20px;
  overflow: hidden;
}

.stats-card-header {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 24px 28px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.stats-card-header .profile-card-icon {
  background: rgba(255, 255, 255, 0.15);
}

.stats-card-header h2 {
  color: white;
}

.stats-card-header p {
  color: rgba(255, 255, 255, 0.7);
}

.stats-list {
  padding: 20px 28px;
}

.stat-item {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 14px 0;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.stat-item:last-child {
  border-bottom: none;
}

.stat-icon {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.stat-icon.patients { background: rgba(59, 130, 246, 0.2); color: #60a5fa; }
.stat-icon.completed { background: rgba(34, 197, 94, 0.2); color: #4ade80; }
.stat-icon.pending { background: rgba(245, 158, 11, 0.2); color: #fbbf24; }

.stat-info {
  flex: 1;
}

.stat-value {
  display: block;
  font-size: 28px;
  font-weight: 800;
  color: white;
  line-height: 1.2;
}

.stat-label {
  display: block;
  font-size: 12px;
  color: rgba(255, 255, 255, 0.6);
  margin-top: 4px;
}

.stats-footer {
  padding: 20px 28px;
  background: rgba(0, 0, 0, 0.2);
  border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.info-row {
  display: flex;
  justify-content: space-between;
  padding: 8px 0;
}

.info-label {
  font-size: 13px;
  color: rgba(255, 255, 255, 0.6);
}

.info-value {
  font-size: 13px;
  font-weight: 600;
  color: white;
}

/* Alertas */
.alert-success {
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  color: #15803d;
  padding: 14px 20px;
  border-radius: 12px;
  margin-bottom: 24px;
  font-size: 14px;
}

.alert-danger {
  background: #fef2f2;
  border: 1px solid #fecaca;
  color: #b91c1c;
  padding: 14px 20px;
  border-radius: 12px;
  margin-bottom: 24px;
  font-size: 14px;
}

/* Responsive */
@media (max-width: 1000px) {
  .profile-container {
    grid-template-columns: 1fr;
  }
  
  .profile-sidebar {
    max-width: 500px;
    margin: 0 auto;
  }
}

@media (max-width: 640px) {
  .form-row.two-columns {
    grid-template-columns: 1fr;
    gap: 16px;
  }
  
  .profile-card-header {
    padding: 18px 20px;
  }
  
  .profile-form {
    padding: 20px;
  }
}

.content {
  padding: 28px;
  background: #f8fafc;
  min-height: calc(100vh - 70px);
}
</style>

<?php include __DIR__ . '/../../includes/footer.php'; ?>