<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';

if (isLoggedIn()) redirect('pages/' . $_SESSION['rol'] . '/dashboard.php');

$error = $success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email  = trim($_POST['email'] ?? '');
    $pass   = $_POST['password'] ?? '';
    $pass2  = $_POST['password2'] ?? '';

    if (!$nombre || !$email || !$pass) {
        $error = 'Todos los campos son obligatorios.';
    } elseif ($pass !== $pass2) {
        $error = 'Las contraseñas no coinciden.';
    } elseif (strlen($pass) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres.';
    } else {
        $check = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
        $check->bind_param('s', $email);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $error = 'Este correo ya está registrado.';
        } else {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO usuarios (nombre, email, password, rol) VALUES (?, ?, ?, 'paciente')");
            $stmt->bind_param('sss', $nombre, $email, $hash);
            if ($stmt->execute()) {
                $uid = $conn->insert_id;
                $p = $conn->prepare("INSERT INTO pacientes (usuario_id) VALUES (?)");
                $p->bind_param('i', $uid);
                $p->execute();
                $success = true;
            } else {
                $error = 'Error al registrar. Intenta de nuevo.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Registro — Hospital Web</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --blue: #2563eb;
      --blue-dark: #1d4ed8;
      --text: #0f172a;
      --muted: #64748b;
      --border: #e2e8f0;
      --bg: #f8fafc;
    }

    body {
      font-family: 'Plus Jakarta Sans', sans-serif;
      min-height: 100vh;
      display: flex;
      background: var(--bg);
    }

    /* ── LADO IZQUIERDO ── */
    .left-panel {
      flex: 1;
      background: #000;
      display: flex;
      flex-direction: column;
      justify-content: flex-start;
      padding: 48px;
      position: relative;
      overflow: hidden;
      min-height: 100vh;
    }

    .left-panel::before {
      content: '';
      position: absolute; inset: 0;
      background: url('<?= BASE_URL ?>/assets/img/img-register.jpg') center/cover no-repeat;
      opacity: 1;
    }

    .left-panel::after {
      content: '';
      position: absolute; inset: 0;
      background: rgba(0, 0, 0, 0.45);
    }

    .left-content {
      position: relative;
      z-index: 1;
      display: flex;
      flex-direction: column;
      justify-content: flex-start;
      height: 100%;
    }

    .left-brand {
      display: flex; align-items: center; gap: 12px;
      color: white; font-size: 18px; font-weight: 800;
      margin-bottom: 32px;
    }

    .left-hero {
      position: relative; z-index: 1;
      color: white;
    }
    .left-hero h2 {
      font-size: clamp(28px, 3vw, 42px);
      font-weight: 800; line-height: 1.15;
      letter-spacing: -1px;
      margin-bottom: 16px;
    }
    .left-hero p {
      font-size: 16px; opacity: .8;
      line-height: 1.6; max-width: 380px;
    }

    /* ── LADO DERECHO ── */
    .right-panel {
      width: 480px;
      flex-shrink: 0;
      background: white;
      display: flex;
      flex-direction: column;
      justify-content: center;
      padding: 48px;
      overflow-y: auto;
    }

    .back-link {
      display: inline-flex; align-items: center; gap: 6px;
      font-size: 13px; color: var(--muted);
      margin-bottom: 40px;
      transition: color .2s; text-decoration: none;
    }
    .back-link:hover { color: var(--blue); }

    .form-header { margin-bottom: 32px; }
    .form-header h1 {
      font-size: 28px; font-weight: 800;
      letter-spacing: -.5px; color: var(--text); margin-bottom: 6px;
    }
    .form-header p { font-size: 14px; color: var(--muted); }

    .alert {
      padding: 12px 16px; border-radius: 10px;
      font-size: 13px; margin-bottom: 20px;
    }
    .alert-danger  { background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; }
    .alert-success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #15803d; }

    .form-group { margin-bottom: 18px; }
    .form-group label {
      display: block; font-size: 13px; font-weight: 600;
      color: var(--text); margin-bottom: 7px;
    }

    .input-wrap { position: relative; }

    .form-control {
      width: 100%;
      padding: 12px 14px;
      border: 1.5px solid var(--border);
      border-radius: 10px;
      font-size: 14px; font-family: inherit;
      color: var(--text); background: var(--bg);
      transition: border-color .2s, box-shadow .2s, background .2s;
    }
    .form-control:focus {
      outline: none; border-color: var(--blue);
      background: white;
      box-shadow: 0 0 0 3px rgba(37,99,235,.12);
    }

    /* Indicador de fortaleza */
    .strength-bar {
      height: 4px; background: var(--border);
      border-radius: 2px; overflow: hidden;
      margin-top: 8px;
    }
    .strength-fill {
      height: 100%; border-radius: 2px;
      transition: width .3s, background .3s;
      width: 0%;
    }
    .strength-text { font-size: 11px; color: var(--muted); margin-top: 4px; }

    .btn-register {
      width: 100%; padding: 13px;
      background: linear-gradient(135deg, var(--blue) 0%, #0ea5e9 100%);
      color: white; border: none; border-radius: 10px;
      font-size: 15px; font-weight: 700; font-family: inherit;
      cursor: pointer; transition: all .2s;
      margin-bottom: 20px; margin-top: 8px;
    }
    .btn-register:hover {
      transform: translateY(-1px);
      box-shadow: 0 6px 20px rgba(37,99,235,.4);
    }

    .divider {
      display: flex; align-items: center; gap: 12px;
      margin-bottom: 20px; font-size: 13px; color: var(--muted);
    }
    .divider::before, .divider::after {
      content: ''; flex: 1; height: 1px; background: var(--border);
    }

    .login-link {
      text-align: center; font-size: 13px; color: var(--muted);
      padding-top: 20px; border-top: 1px solid var(--border);
    }
    .login-link a { color: var(--blue); font-weight: 700; text-decoration: none; }
    .login-link a:hover { text-decoration: underline; }

    /* Success */
    .success-state { text-align: center; padding: 20px 0; }
    .success-icon {
      width: 64px; height: 64px;
      background: #f0fdf4; border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      margin: 0 auto 20px;
    }
    .success-icon svg { width: 32px; height: 32px; stroke: #16a34a; }
    .success-state h2 { font-size: 22px; font-weight: 800; margin-bottom: 8px; }
    .success-state p { font-size: 14px; color: var(--muted); margin-bottom: 24px; }
    .btn-goto {
      display: inline-flex; align-items: center; gap: 8px;
      padding: 12px 24px; background: var(--blue); color: white;
      border-radius: 10px; font-size: 14px; font-weight: 700;
      text-decoration: none; transition: all .2s;
    }
    .btn-goto:hover { background: var(--blue-dark); transform: translateY(-1px); }

    @media (max-width: 900px) {
      .left-panel { display: none; }
      .right-panel { width: 100%; min-height: 100vh; }
    }
  </style>
</head>
<body>

<!-- PANEL IZQUIERDO -->
<div class="left-panel">
  <div class="left-content">
    <div class="left-brand">
      Hospital Palacio de la salud
    </div>
  </div>
  <div class="left-hero left-content">
    <h2>Únete a nuestra comunidad de salud</h2>
    <p>Crea tu cuenta y accede a todos nuestros servicios médicos desde cualquier lugar.</p>
  </div>
</div>

<!-- PANEL DERECHO -->
<div class="right-panel">
  <a href="<?= BASE_URL ?>/login.php" class="back-link">← Volver al inicio de sesión</a>

  <?php if ($success): ?>
  <div class="success-state">
    <div class="success-icon">
      <svg viewBox="0 0 24 24" fill="none" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="20 6 9 17 4 12"/>
      </svg>
    </div>
    <h2>Cuenta creada</h2>
    <p>Tu cuenta ha sido registrada exitosamente. Ya puedes iniciar sesión.</p>
    <a href="<?= BASE_URL ?>/login.php" class="btn-goto">Ir al inicio de sesión</a>
  </div>

  <?php else: ?>

  <div class="form-header">
    <h1>Crear cuenta</h1>
    <p>Regístrate como paciente</p>
  </div>

  <?php if ($error): ?>
  <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST">
    <div class="form-group">
      <label>Nombre completo</label>
      <input type="text" name="nombre" class="form-control"
             placeholder="Jose Hugo Ortiz Pedraza" required
             value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>">
    </div>

    <div class="form-group">
      <label>Correo electrónico</label>
      <input type="email" name="email" class="form-control"
             placeholder="tu@correo.com" required
             value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
    </div>

    <div class="form-group">
      <label>Contraseña</label>
      <input type="password" name="password" id="password" class="form-control"
             placeholder="Minimo 6 caracteres" required>
      <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
      <div class="strength-text" id="strengthText"></div>
    </div>

    <div class="form-group">
      <label>Confirmar contraseña</label>
      <input type="password" name="password2" class="form-control"
             placeholder="Repite tu contraseña" required>
    </div>

    <button type="submit" class="btn-register">Crear cuenta</button>
  </form>
  <div class="login-link">
    Ya tienes cuenta? <a href="<?= BASE_URL ?>/login.php">Inicia sesion</a>
  </div>

  <?php endif; ?>
</div>

<script>
const pwd = document.getElementById('password');
const fill = document.getElementById('strengthFill');
const text = document.getElementById('strengthText');

pwd.addEventListener('input', () => {
  const val = pwd.value;
  let score = 0;
  if (val.length >= 6) score++;
  if (val.length >= 10) score++;
  if (/[A-Z]/.test(val)) score++;
  if (/[0-9]/.test(val)) score++;
  if (/[^A-Za-z0-9]/.test(val)) score++;

  const levels = [
    { w: '0%',   bg: 'transparent', t: '' },
    { w: '25%',  bg: '#ef4444', t: 'Muy debil' },
    { w: '50%',  bg: '#f97316', t: 'Debil' },
    { w: '75%',  bg: '#eab308', t: 'Regular' },
    { w: '90%',  bg: '#22c55e', t: 'Fuerte' },
    { w: '100%', bg: '#16a34a', t: 'Muy fuerte' },
  ];

  const l = levels[Math.min(score, 5)];
  fill.style.width = l.w;
  fill.style.background = l.bg;
  text.textContent = l.t;
  text.style.color = l.bg;
});
</script>

</body>
</html>