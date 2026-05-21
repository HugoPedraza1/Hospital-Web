<?php
$pageTitle = 'Registrar Doctor';
require_once __DIR__ . '/../../config/config.php';
requireRole('admin');
require_once __DIR__ . '/../../includes/db.php';

$especialidades = $conn->query("SELECT * FROM especialidades ORDER BY nombre");
$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre   = trim($_POST['nombre'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $pass     = $_POST['password'] ?? '';
    $esp_id   = (int)$_POST['especialidad_id'];
    $telefono = trim($_POST['telefono'] ?? '');
    $cedula   = trim($_POST['cedula'] ?? '');

    if (!$nombre || !$email || !$pass) {
        $error = 'Nombre, email y contraseña son obligatorios.';
    } else {
        $check = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
        $check->bind_param('s', $email);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $error = 'Este correo ya esta registrado.';
        } else {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $ins = $conn->prepare("INSERT INTO usuarios (nombre, email, password, rol) VALUES (?, ?, ?, 'doctor')");
            $ins->bind_param('sss', $nombre, $email, $hash);
            if ($ins->execute()) {
                $uid = $conn->insert_id;
                $esp = $esp_id ?: null;
                $doc = $conn->prepare("INSERT INTO doctores (usuario_id, especialidad_id, telefono, cedula) VALUES (?, ?, ?, ?)");
                $doc->bind_param('iiss', $uid, $esp, $telefono, $cedula);
                $doc->execute();
                $success = true;
            } else {
                $error = 'Error al registrar el doctor.';
            }
        }
    }
}
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>

<div class="layout">
  <?php include __DIR__ . '/../../includes/sidebar.php'; ?>
  <div class="main">
    <div class="topbar">
      <div class="topbar-left">
        <h1>Registrar Doctor</h1>
        <p>Agregar nuevo medico al sistema</p>
      </div>
      <div class="topbar-right">
        <div class="topbar-avatar"><?= strtoupper(substr($_SESSION['nombre'],0,2)) ?></div>
      </div>
    </div>
    <div class="content">

      <?php if ($success): ?>
      <div class="card" style="max-width:500px;">
        <div class="card-body" style="text-align:center;padding:40px 20px;">
          <h2 style="font-size:20px;font-weight:800;margin-bottom:8px;">Doctor registrado</h2>
          <p style="color:#64748b;font-size:14px;margin-bottom:24px;">El doctor agregdo exitosamente al sistema.</p>
          <div style="display:flex;gap:10px;justify-content:center;">
            <a href="<?= BASE_URL ?>/pages/doctores/listar.php" class="btn btn-primary">Ver doctores</a>
            <a href="<?= BASE_URL ?>/pages/doctores/registrar.php" class="btn btn-outline">Registrar otro</a>
          </div>
        </div>
      </div>

      <?php else: ?>
      <div class="card" style="max-width:600px;">
        <div class="card-header">
          <div><h2>Datos del doctor</h2><p>Ingresa toda la informacion</p></div>
        </div>
        <div class="card-body">
          <?php if ($error): ?>
          <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
          <?php endif; ?>

          <form method="POST">
            <div class="form-group">
              <label>Nombre completo (Obligatorio)</label>
              <input type="text" name="nombre" class="form-control"
                     placeholder="Dr. Nombre Apellido" required
                     value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>">
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
              <div class="form-group">
                <label>Correo electronico (Obligatorio)</label>
                <input type="email" name="email" class="form-control"
                       placeholder="doctor@hospital.com" required
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
              </div>
              <div class="form-group">
                <label>Contrasena (Obligatorio)</label>
                <input type="password" name="password" class="form-control"
                       placeholder="Minimo 6 caracteres" required>
              </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
              <div class="form-group">
                <label>Especialidad</label>
                <select name="especialidad_id" class="form-control">
                  <option value="">— Sin especialidad —</option>
                  <?php while ($e = $especialidades->fetch_assoc()): ?>
                  <option value="<?= $e['id'] ?>" <?= ($_POST['especialidad_id'] ?? '') == $e['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($e['nombre']) ?>
                  </option>
                  <?php endwhile; ?>
                </select>
              </div>
              <div class="form-group">
                <label>Telefono</label>
                <input type="text" name="telefono" class="form-control"
                       placeholder="461 123 4567"
                       value="<?= htmlspecialchars($_POST['telefono'] ?? '') ?>">
              </div>
            </div>

            <div class="form-group">
              <label>Cedula profesional</label>
              <input type="text" name="cedula" class="form-control"
                     placeholder="Numero de cedula"
                     value="<?= htmlspecialchars($_POST['cedula'] ?? '') ?>">
            </div>

            <div style="display:flex;gap:10px;">
              <button type="submit" class="btn btn-primary">Registrar doctor</button>
              <a href="<?= BASE_URL ?>/pages/doctores/listar.php" class="btn btn-outline">Cancelar</a>
            </div>
          </form>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>