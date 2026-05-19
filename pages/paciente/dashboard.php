<?php
$pageTitle = 'Panel de Paciente';
require_once __DIR__ . '/../../config/config.php';
requireRole('paciente');
require_once __DIR__ . '/../../includes/db.php';

$pacienteRow = $conn->prepare("SELECT id FROM pacientes WHERE usuario_id = ?");
$pacienteRow->bind_param('i', $_SESSION['usuario_id']);
$pacienteRow->execute();
$pac = $pacienteRow->get_result()->fetch_assoc();
$pid = $pac['id'] ?? 0;

// Stats
$totalCitas = $conn->prepare("SELECT COUNT(*) c FROM citas WHERE paciente_id = ?");
$totalCitas->bind_param('i', $pid); $totalCitas->execute();
$nCitas = $totalCitas->get_result()->fetch_assoc()['c'];

$proximasCitas = $conn->prepare("SELECT COUNT(*) c FROM citas WHERE paciente_id = ? AND fecha >= CURDATE() AND estado != 'cancelada'");
$proximasCitas->bind_param('i', $pid); $proximasCitas->execute();
$nProximas = $proximasCitas->get_result()->fetch_assoc()['c'];

$totalHistorial = $conn->prepare("SELECT COUNT(*) c FROM historial WHERE paciente_id = ?");
$totalHistorial->bind_param('i', $pid); $totalHistorial->execute();
$nHistorial = $totalHistorial->get_result()->fetch_assoc()['c'];

// Proximas citas
$citas = $conn->prepare("
    SELECT c.id, c.fecha, c.hora, c.estado, c.motivo,
           u.nombre AS doctor, e.nombre AS especialidad
    FROM citas c
    JOIN doctores d ON c.doctor_id = d.id
    JOIN usuarios u ON d.usuario_id = u.id
    LEFT JOIN especialidades e ON d.especialidad_id = e.id
    WHERE c.paciente_id = ? AND c.fecha >= CURDATE()
    ORDER BY c.fecha ASC, c.hora ASC
    LIMIT 5
");
$citas->bind_param('i', $pid);
$citas->execute();
$citasResult = $citas->get_result();

$colores = ['#2563eb','#7c3aed','#db2777','#059669','#d97706','#dc2626'];
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>

<div class="layout">
  <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

  <div class="main">

    <!-- Topbar -->
    <div class="topbar">
      <div class="topbar-left">
        <h1>Panel de Paciente</h1>
        <p>Bienvenido de nuevo, <?= htmlspecialchars($_SESSION['nombre']) ?></p>
      </div>
      <div class="topbar-right">
        <div class="notif-btn">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
          <span class="notif-dot"></span>
        </div>
        <div class="topbar-avatar"><?= strtoupper(substr($_SESSION['nombre'],0,2)) ?></div>
      </div>
    </div>

    <div class="content">

      <!-- Stats -->
      <div class="stats-grid">
        <div class="stat-card">
          <div>
            <div class="stat-card-icon">
              <img src="<?= BASE_URL ?>/assets/img/svg/calendar.svg" width="22" height="22" alt="">
            </div>
            <div class="stat-value"><?= $nProximas ?></div>
            <div class="stat-label">Proximas Citas</div>
          </div>
        </div>

        <div class="stat-card">
          <div>
            <div class="stat-card-icon">
              <img src="<?= BASE_URL ?>/assets/img/svg/consultas-totales.svg" width="22" height="22" alt="">
            </div>
            <div class="stat-value"><?= $nHistorial ?></div>
            <div class="stat-label">Consultas Totales</div>
          </div>
        </div>

        <div class="stat-card">
          <div>
            <div class="stat-card-icon">
              <img src="<?= BASE_URL ?>/assets/img/svg/sidebar-historial.svg" width="22" height="22" alt="">
            </div>
            <div class="stat-value"><?= $nCitas ?></div>
            <div class="stat-label">Total Citas</div>
          </div>
        </div>
      </div>

      <!-- Acciones rapidas -->
      <div class="card" style="margin-bottom:20px;">
        <div class="card-body" style="display:flex;gap:12px;flex-wrap:wrap;">
          <a href="<?= BASE_URL ?>/pages/citas/agendar.php" class="btn btn-primary">
            <img src="<?= BASE_URL ?>/assets/img/svg/sidebar-citas.svg" width="16" height="16" style="filter:brightness(0) invert(1);">
            Agendar Cita
          </a>
          <a href="<?= BASE_URL ?>/pages/paciente/historial.php" class="btn btn-outline">
            <img src="<?= BASE_URL ?>/assets/img/svg/sidebar-historial.svg" width="16" height="16">
            Ver Historial
          </a>
          <a href="<?= BASE_URL ?>/pages/doctores/listar.php" class="btn btn-outline">
            <img src="<?= BASE_URL ?>/assets/img/svg/sidebar-doctor.svg" width="16" height="16">
            Ver Doctores
          </a>
        </div>
      </div>

      <!-- Proximas citas -->
      <div class="card">
        <div class="card-header">
          <div>
            <h2>Proximas Citas</h2>
            <p>Tus citas programadas</p>
          </div>
          <a href="<?= BASE_URL ?>/pages/citas/agendar.php" class="btn btn-primary btn-sm">+ Nueva Cita</a>
        </div>

        <?php $count = 0; while ($c = $citasResult->fetch_assoc()): $count++;
          $ini = strtoupper(substr($c['doctor'], 0, 2));
          $color = $colores[crc32($c['doctor']) % count($colores)];
          $hora12 = date('h:i A', strtotime($c['hora']));
          $map = ['pendiente'=>'badge-warning','confirmada'=>'badge-success','cancelada'=>'badge-danger','completada'=>'badge-info'];
          $estadoLabel = ['pendiente'=>'Pendiente','confirmada'=>'Confirmada','cancelada'=>'Cancelada','completada'=>'Completada'];
        ?>
        <div class="cita-row">
          <div class="cita-avatar" style="background:<?= $color ?>;"><?= $ini ?></div>
          <div class="cita-info">
            <div class="nombre"><?= htmlspecialchars($c['doctor']) ?></div>
            <div class="sub"><?= htmlspecialchars($c['especialidad'] ?? 'Medicina General') ?></div>
          </div>
          <div class="cita-meta">
            <span>
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
              <?= date('d/m/Y', strtotime($c['fecha'])) ?>
            </span>
            <span>
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
              <?= $hora12 ?>
            </span>
          </div>
          <span class="badge <?= $map[$c['estado']] ?? 'badge-gray' ?>"><?= $estadoLabel[$c['estado']] ?? ucfirst($c['estado']) ?></span>
        </div>
        <?php endwhile; ?>

        <?php if ($count === 0): ?>
        <div class="empty-state">
          <div class="empty-icon">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
          </div>
          <p>No tienes citas proximas. <a href="<?= BASE_URL ?>/pages/citas/agendar.php">Agenda una ahora</a></p>
        </div>
        <?php endif; ?>

      </div>

    </div>
  </div>
</div>

<style>
  .stat-card-icon {
    width: 44px; height: 44px;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    margin-bottom: 14px;
    background: #fff;
    border: 1.5px solid #e2e8f0;
  }
</style>

<?php include __DIR__ . '/../../includes/footer.php'; ?>