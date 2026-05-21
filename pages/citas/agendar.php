<?php
$pageTitle = 'Agendar Cita';
require_once __DIR__ . '/../../config/config.php';
requireLogin();
require_once __DIR__ . '/../../includes/db.php';

$pacienteRow = $conn->prepare("SELECT id FROM pacientes WHERE usuario_id = ?");
$pacienteRow->bind_param('i', $_SESSION['usuario_id']);
$pacienteRow->execute();
$pac = $pacienteRow->get_result()->fetch_assoc();

if(!$pac){
    $crear = $conn->prepare("INSERT INTO pacientes (usuario_id) VALUES (?)");
    $crear->bind_param('i', $_SESSION['usuario_id']);
    $crear->execute();

    $pacienteRow->execute();
    $pac = $pacienteRow->get_result()->fetch_assoc();
}

$pid = $pac['id'] ?? null;

$doctores = $conn->query("
    SELECT d.id, u.nombre, e.nombre AS especialidad
    FROM doctores d
    JOIN usuarios u ON d.usuario_id = u.id
    LEFT JOIN especialidades e ON d.especialidad_id = e.id
    WHERE d.disponible = 1
    ORDER BY u.nombre
");

$especialidades = $conn->query("SELECT * FROM especialidades ORDER BY nombre");

$error = $success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pid) {
    $doctor_id = (int)$_POST['doctor_id'];
    $fecha     = $_POST['fecha'] ?? '';
    $hora      = $_POST['hora'] ?? '';
    $motivo    = trim($_POST['motivo'] ?? '');

    if (!$doctor_id || !$fecha || !$hora) {
        $error = 'Completa todos los campos obligatorios.';
    } elseif (strtotime($fecha) < strtotime('today')) {
        $error = 'La fecha no puede ser en el pasado.';
    } else {
        $check = $conn->prepare("SELECT id FROM citas WHERE doctor_id=? AND fecha=? AND hora=? AND estado != 'cancelada'");
        $check->bind_param('iss', $doctor_id, $fecha, $hora);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $error = 'Ese horario ya esta ocupado. Elige otra hora.';
        } else {
            $ins = $conn->prepare("INSERT INTO citas (paciente_id, doctor_id, fecha, hora, motivo) VALUES (?,?,?,?,?)");
            $ins->bind_param('iisss', $pid, $doctor_id, $fecha, $hora, $motivo);
            if ($ins->execute()) {
                $success = true;
            } else {
                $error = 'Error al guardar la cita.';
            }
        }
    }
}
$preDoctor = (int)($_GET['doctor_id'] ?? 0);
$horas = ['08:00','08:30','09:00','09:30','10:00','10:30','11:00','11:30',
          '12:00','12:30','14:00','14:30','15:00','15:30','16:00','16:30','17:00'];
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>

<div class="layout">
  <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

  <div class="main">
    <div class="topbar">
      <div class="topbar-left">
        <h1>Agendar Cita</h1>
        <p>Programa una nueva cita medica</p>
      </div>
      <div class="topbar-right">
        <div class="topbar-avatar"><?= strtoupper(substr($_SESSION['nombre'],0,2)) ?></div>
      </div>
    </div>

    <div class="content">
      <?php if ($success): ?>
      <div class="card" style="max-width:560px;">
        <div class="card-body" style="text-align:center;padding:48px 20px;">
          <div style="width:64px;height:64px;background:#f0fdf4;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
          </div>
          <h2 style="font-size:20px;font-weight:800;margin-bottom:8px;">Cita agendada</h2>
          <p style="color:#64748b;font-size:14px;margin-bottom:24px;">Tu cita ha sido registrada exitosamente.</p>
          <div style="display:flex;gap:10px;justify-content:center;">
            <a href="<?= BASE_URL ?>/pages/citas/ver.php" class="btn btn-primary">Ver mis citas</a>
            <a href="<?= BASE_URL ?>/pages/citas/agendar.php" class="btn btn-outline">Agendar otra</a>
          </div>
        </div>
      </div>

      <?php else: ?>
      <div class="card-grid" style="grid-template-columns:1.2fr 0.8fr;">

        <!-- Formulario -->
        <div class="card">
          <div class="card-header">
            <div>
              <h2>Nueva cita</h2>
              <p>Completa los datos para agendar</p>
            </div>
          </div>
          <div class="card-body">
            <?php if (!$pid): ?>
            <div class="alert alert-danger">Tu cuenta no tiene perfil de paciente. Contacta al administrador.</div>
            <?php else: ?>

            <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST">
              <div class="form-group">
                <label>Doctor *</label>
                <select name="doctor_id" class="form-control" required>
                  <option value="">— Selecciona un doctor —</option>
                  <?php
                  $doctores->data_seek(0);
                  while ($d = $doctores->fetch_assoc()):
                  ?>
                  <option value="<?= $d['id'] ?>" <?= $d['id'] == $preDoctor ? 'selected' : '' ?>>
                    <?= htmlspecialchars($d['nombre']) ?>
                    <?= $d['especialidad'] ? '(' . $d['especialidad'] . ')' : '' ?>
                  </option>
                  <?php endwhile; ?>
                </select>
              </div>

              <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div class="form-group">
                  <label>Fecha *</label>
                  <input type="date" name="fecha" class="form-control"
                         min="<?= date('Y-m-d') ?>" required
                         value="<?= htmlspecialchars($_POST['fecha'] ?? '') ?>">
                </div>
                <div class="form-group">
                  <label>Hora *</label>
                  <select name="hora" class="form-control" required>
                    <option value="">— Hora —</option>
                    <?php foreach ($horas as $h): ?>
                    <option value="<?= $h ?>" <?= ($_POST['hora'] ?? '') === $h ? 'selected' : '' ?>>
                      <?= date('h:i A', strtotime($h)) ?>
                    </option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>

              <div class="form-group">
                <label>Motivo de consulta</label>
                <textarea name="motivo" class="form-control" rows="3"
                          placeholder="Describe brevemente tu motivo de consulta..."><?= htmlspecialchars($_POST['motivo'] ?? '') ?></textarea>
              </div>

              <div style="display:flex;gap:10px;">
                <button type="submit" class="btn btn-primary">Confirmar cita</button>
                <a href="<?= BASE_URL ?>/pages/paciente/dashboard.php" class="btn btn-outline">Cancelar</a>
              </div>
            </form>
            <?php endif; ?>
          </div>
        </div>

        <!-- Info lateral -->
        <div class="card">
          <div class="card-header">
            <div><h2>Doctores disponibles</h2></div>
          </div>
          <div style="overflow-y:auto;max-height:420px;">
            <?php
            $doctores->data_seek(0);
            $colores = ['#2563eb','#7c3aed','#db2777','#059669','#d97706','#dc2626'];
            $i = 0;
            while ($d = $doctores->fetch_assoc()):
              $ini = strtoupper(substr($d['nombre'], 0, 2));
              $color = $colores[$i++ % count($colores)];
            ?>
            <div class="cita-row">
              <div class="cita-avatar" style="background:<?= $color ?>;"><?= $ini ?></div>
              <div class="cita-info">
                <div class="nombre"><?= htmlspecialchars($d['nombre']) ?></div>
                <div class="sub"><?= htmlspecialchars($d['especialidad'] ?? 'General') ?></div>
              </div>
              <span class="badge badge-success">Disponible</span>
            </div>
            <?php endwhile; ?>
          </div>
        </div>

      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>