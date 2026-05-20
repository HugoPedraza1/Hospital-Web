<?php
$pageTitle = 'Cancelar Cita';
require_once __DIR__ . '/../../config/config.php';
requireLogin();
require_once __DIR__ . '/../../includes/db.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) redirect('pages/citas/ver.php');

// Verificar que la cita existe y pertenece al usuario
$rol = $_SESSION['rol'];
if ($rol === 'paciente') {
    $pacRow = $conn->prepare("SELECT id FROM pacientes WHERE usuario_id = ?");
    $pacRow->bind_param('i', $_SESSION['usuario_id']);
    $pacRow->execute();
    $pac = $pacRow->get_result()->fetch_assoc();
    $pid = $pac['id'] ?? 0;
    $stmt = $conn->prepare("SELECT c.*, u.nombre AS doctor FROM citas c JOIN doctores d ON c.doctor_id = d.id JOIN usuarios u ON d.usuario_id = u.id WHERE c.id = ? AND c.paciente_id = ?");
    $stmt->bind_param('ii', $id, $pid);
} else {
    $stmt = $conn->prepare("SELECT c.*, u.nombre AS doctor FROM citas c JOIN doctores d ON c.doctor_id = d.id JOIN usuarios u ON d.usuario_id = u.id WHERE c.id = ?");
    $stmt->bind_param('i', $id);
}
$stmt->execute();
$cita = $stmt->get_result()->fetch_assoc();

if (!$cita) redirect('pages/citas/ver.php');

// Procesar cancelacion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $upd = $conn->prepare("UPDATE citas SET estado = 'cancelada' WHERE id = ?");
    $upd->bind_param('i', $id);
    $upd->execute();
    redirect('pages/citas/ver.php');
}
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>

<div class="layout">
  <?php include __DIR__ . '/../../includes/sidebar.php'; ?>
  <div class="main">
    <div class="topbar">
      <div class="topbar-left">
        <h1>Cancelar Cita</h1>
        <p>Confirma la cancelacion</p>
      </div>
      <div class="topbar-right">
        <div class="topbar-avatar"><?= strtoupper(substr($_SESSION['nombre'],0,2)) ?></div>
      </div>
    </div>
    <div class="content">
      <div class="card" style="max-width:500px;">
        <div class="card-body" style="text-align:center;padding:40px 20px;">
          <div style="width:64px;height:64px;background:#fef2f2;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
          </div>
          <h2 style="font-size:20px;font-weight:800;margin-bottom:8px;">Cancelar cita</h2>
          <p style="color:#64748b;font-size:14px;margin-bottom:24px;">
            Esta seguro que deseas cancelar la cita con<br>
            <strong><?= htmlspecialchars($cita['doctor']) ?></strong><br>
            el <?= date('d/m/Y', strtotime($cita['fecha'])) ?> a las <?= date('h:i A', strtotime($cita['hora'])) ?>
          </p>
          <form method="POST" style="display:flex;gap:10px;justify-content:center;">
            <button type="submit" class="btn btn-danger">Si, cancelar cita</button>
            <a href="<?= BASE_URL ?>/pages/citas/ver.php" class="btn btn-outline">No, volver</a>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>