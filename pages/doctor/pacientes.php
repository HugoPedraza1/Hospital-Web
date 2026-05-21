<?php
$pageTitle = 'Mis Pacientes';
require_once __DIR__ . '/../../config/config.php';
requireRole('doctor');
require_once __DIR__ . '/../../includes/db.php';

// Obtener ID del doctor
$docRow = $conn->prepare("SELECT d.id FROM doctores d WHERE d.usuario_id = ?");
$docRow->bind_param('i', $_SESSION['usuario_id']);
$docRow->execute();
$doctor = $docRow->get_result()->fetch_assoc();
$doctor_id = $doctor['id'] ?? 0;

// Busqueda
$search = $_GET['search'] ?? '';

$sql = "SELECT DISTINCT 
            p.id AS paciente_id,
            u.nombre,
            u.email,
            p.telefono,
            p.fecha_nacimiento,
            p.tipo_sangre,
            p.alergias,
            p.direccion,
            (SELECT COUNT(*) FROM citas WHERE paciente_id = p.id AND doctor_id = ?) AS total_citas,
            (SELECT MAX(fecha) FROM citas WHERE paciente_id = p.id AND doctor_id = ?) AS ultima_cita,
            (SELECT fecha FROM citas WHERE paciente_id = p.id AND doctor_id = ? AND estado != 'cancelada' ORDER BY fecha DESC LIMIT 1) AS ultima_consulta
        FROM pacientes p
        JOIN usuarios u ON p.usuario_id = u.id
        JOIN citas c ON c.paciente_id = p.id
        WHERE c.doctor_id = ?";

if ($search) {
    $sql .= " AND (u.nombre LIKE '%$search%' OR u.email LIKE '%$search%' OR p.telefono LIKE '%$search%')";
}
$sql .= " ORDER BY ultima_cita DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param('iiii', $doctor_id, $doctor_id, $doctor_id, $doctor_id);
$stmt->execute();
$pacientes = $stmt->get_result();

$colores = ['#2563eb','#7c3aed','#db2777','#059669','#d97706','#dc2626'];
?>

<?php include __DIR__ . '/../../includes/header.php'; ?>

<div class="layout">
  <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

  <div class="main">
    <div class="topbar">
      <div class="topbar-left">
        <h1>Mis Pacientes</h1>
        <p>Pacientes que has atendido</p>
      </div>
      <div class="topbar-right">
        <div class="topbar-avatar"><?= strtoupper(substr($_SESSION['nombre'],0,2)) ?></div>
      </div>
    </div>

    <div class="content">
      <!-- Busqueda -->
      <div class="card" style="margin-bottom: 24px;">
        <div class="card-header">
          <h2>Buscar paciente</h2>
        </div>
        <form method="GET" style="display: flex; gap: 12px;">
          <input type="text" name="search" class="form-control" style="flex: 1;" 
                 placeholder="Buscar por nombre, correo o telefono..." 
                 value="<?= htmlspecialchars($search) ?>">
          <button type="submit" class="btn btn-primary">Buscar</button>
          <?php if ($search): ?>
            <a href="pacientes.php" class="btn btn-ghost">Limpiar</a>
          <?php endif; ?>
        </form>
      </div>

      <!-- Estadisticas rapidas -->
      <div class="stats-grid" style="margin-bottom: 24px; grid-template-columns: repeat(3, 1fr);">
        <div class="stat-card">
          <div class="stat-value"><?= $pacientes->num_rows ?></div>
          <div class="stat-label">Total Pacientes</div>
        </div>
        <div class="stat-card">
          <div class="stat-value">
            <?php
            $recientes = 0;
            $pacientes->data_seek(0);
            while ($p = $pacientes->fetch_assoc()) {
                if ($p['ultima_consulta'] && strtotime($p['ultima_consulta']) > strtotime('-30 days')) {
                    $recientes++;
                }
            }
            echo $recientes;
            ?>
          </div>
          <div class="stat-label">Activos (30 dias)</div>
        </div>
        <div class="stat-card">
          <div class="stat-value">
            <?php
            $totalCitas = 0;
            $pacientes->data_seek(0);
            while ($p = $pacientes->fetch_assoc()) {
                $totalCitas += $p['total_citas'];
            }
            echo $totalCitas;
            ?>
          </div>
          <div class="stat-label">Total Consultas</div>
        </div>
      </div>

      <!-- Grid de pacientes -->
      <?php if ($pacientes->num_rows > 0): 
        $pacientes->data_seek(0);
      ?>
        <div class="pacientes-grid">
          <?php while ($p = $pacientes->fetch_assoc()): 
            $color = $colores[crc32($p['nombre']) % count($colores)];
            $edad = $p['fecha_nacimiento'] ? date('Y') - date('Y', strtotime($p['fecha_nacimiento'])) : 'N/A';
            $ultimaCita = $p['ultima_cita'] ? date('d/m/Y', strtotime($p['ultima_cita'])) : 'Sin citas';
          ?>
            <div class="paciente-card">
              <div class="paciente-avatar" style="background: <?= $color ?>">
                <?= strtoupper(substr($p['nombre'], 0, 2)) ?>
              </div>
              <div class="paciente-info">
                <h3><?= htmlspecialchars($p['nombre']) ?></h3>
                <p class="email"><?= htmlspecialchars($p['email']) ?></p>
                <?php if ($p['telefono']): ?>
                  <p class="phone"><?= htmlspecialchars($p['telefono']) ?></p>
                <?php endif; ?>
                <p class="details">
                  Edad: <?= $edad ?> anos | 
                  Sangre: <?= $p['tipo_sangre'] ?: 'No registrado' ?> |
                  Citas: <?= $p['total_citas'] ?>
                </p>
                <p class="last-visit">Ultima cita: <?= $ultimaCita ?></p>
                <?php if ($p['alergias']): ?>
                  <p class="alergies">Alergias: <?= htmlspecialchars(substr($p['alergias'], 0, 50)) ?></p>
                <?php endif; ?>
              </div>
              <div class="paciente-actions">
                <a href="<?= BASE_URL ?>/pages/historial/ver.php?paciente_id=<?= $p['paciente_id'] ?>&doctor_id=<?= $doctor_id ?>" 
                   class="btn btn-sm btn-primary">Historial</a>
                <a href="<?= BASE_URL ?>/pages/citas/agendar.php?paciente_id=<?= $p['paciente_id'] ?>" 
                   class="btn btn-sm btn-outline">Nueva Cita</a>
              </div>
            </div>
          <?php endwhile; ?>
        </div>
      <?php else: ?>
        <div class="empty-state">
          <div class="empty-icon">[Sin datos]</div>
          <p>No tienes pacientes registrados aun</p>
          <p class="sub">Las citas que atiendas apareceran aqui automaticamente</p>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<style>
.pacientes-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
  gap: 20px;
}

.paciente-card {
  background: white;
  border: 1.5px solid var(--border);
  border-radius: 16px;
  padding: 20px;
  display: flex;
  gap: 16px;
  transition: all 0.2s;
}

.paciente-card:hover {
  border-color: var(--blue);
  box-shadow: 0 4px 12px rgba(0,0,0,0.05);
  transform: translateY(-2px);
}

.paciente-avatar {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
  font-weight: 700;
  color: white;
  flex-shrink: 0;
}

.paciente-info {
  flex: 1;
}

.paciente-info h3 {
  font-size: 16px;
  font-weight: 700;
  margin-bottom: 4px;
  color: var(--text);
}

.paciente-info .email,
.paciente-info .phone {
  font-size: 12px;
  color: var(--muted);
  margin: 2px 0;
}

.paciente-info .details {
  font-size: 12px;
  color: var(--muted);
  margin-top: 8px;
}

.paciente-info .last-visit {
  font-size: 11px;
  color: var(--blue);
  margin-top: 4px;
}

.paciente-info .alergies {
  font-size: 11px;
  color: #dc2626;
  margin-top: 4px;
}

.paciente-actions {
  display: flex;
  flex-direction: column;
  gap: 8px;
  justify-content: center;
}

.btn-sm {
  padding: 6px 12px;
  font-size: 12px;
}

.stat-card {
  background: white;
  border: 1.5px solid var(--border);
  border-radius: 16px;
  padding: 20px;
  text-align: center;
}

.stat-value {
  font-size: 28px;
  font-weight: 800;
  color: var(--blue);
}

.stat-label {
  font-size: 13px;
  color: var(--muted);
  margin-top: 4px;
}

.empty-state .sub {
  font-size: 12px;
  color: var(--muted);
  margin-top: 8px;
}
</style>

<?php include __DIR__ . '/../../includes/footer.php'; ?>