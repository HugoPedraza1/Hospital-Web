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
      --success: #16a34a;
      --success-bg: #dcfce7;
      --danger: #dc2626;
      --danger-bg: #fef2f2;
    }

    body {
      font-family: 'Plus Jakarta Sans', sans-serif;
      min-height: 100vh;
      display: flex;
      background: var(--bg);
    }

    /* ── PANEL IZQUIERDO ── */
    .left-panel {
      flex: 1;
      background: #0f172a;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      padding: 48px;
      position: relative;
      overflow: hidden;
      min-height: 100vh;
    }

    .left-panel::before {
      content: '';
      position: absolute; inset: 0;
      background: url('<?= BASE_URL ?>/assets/img/register-bg.jpg') center/cover no-repeat;
      opacity: 1;
    }

    .left-panel::after {
      content: '';
      position: absolute; inset: 0;
      background: rgba(0,0,0,.45);
    }

    .left-content { position: relative; z-index: 1; }

    .left-brand {
      display: flex; align-items: center; gap: 12px;
      color: white; font-size: 18px; font-weight: 800;
    }
    .left-brand .logo {
      width: 40px; height: 40px;
      background: rgba(255,255,255,.15);
      border-radius: 10px;
      display: flex; align-items: center; justify-content: center;
      font-size: 20px; backdrop-filter: blur(10px);
    }

    .left-hero { color: white; }
    .left-hero h2 {
      font-size: clamp(26px, 3vw, 38px);
      font-weight: 800; line-height: 1.2;
      letter-spacing: -1px; margin-bottom: 14px;
    }
    .left-hero p {
      font-size: 15px; opacity: .75;
      line-height: 1.6; max-width: 360px;
    }

    .left-steps { color: white; }
    .left-steps h3 {
      font-size: 13px; font-weight: 700;
      text-transform: uppercase; letter-spacing: .08em;
      opacity: .5; margin-bottom: 16px;
    }
    .step-item {
      display: flex; align-items: flex-start; gap: 12px;
      margin-bottom: 16px;
    }
    .step-num {
      width: 28px; height: 28px;
      background: rgba(37,99,235,.6);
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      font-size: 12px; font-weight: 700; color: white;
      flex-shrink: 0; margin-top: 1px;
    }
    .step-item .step-text { font-size: 13px; opacity: .8; line-height: 1.4; }
    .step-item .step-title { font-weight: 600; font-size: 14px; }

    /* ── PANEL DERECHO ── */
    .right-panel {
      width: 500px;
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
      margin-bottom: 36px;
      transition: color .2s; text-decoration: none;
    }
    .back-link:hover { color: var(--blue); }

    .form-header { margin-bottom: 28px; }
    .form-header h1 {
      font-size: 26px; font-weight: 800;
      letter-spacing: -.5px; color: var(--text); margin-bottom: 6px;
    }
    .form-header p { font-size: 14px; color: var(--muted); }

    .alert {
      padding: 12px 16px; border-radius: 10px;
      font-size: 13px; margin-bottom: 20px;
      display: flex; align-items: center; gap: 8px;
    }
    .alert-danger  { background: var(--danger-bg);  border: 1px solid #fecaca; color: #b91c1c; }
    .alert-success { background: var(--success-bg); border: 1px solid #bbf7d0; color: #15803d; }

    .form-row {
      display: grid; grid-template-columns: 1fr 1fr;
      gap: 14px;
    }

    .form-group { margin-bottom: 16px; }
    .form-group label {
      display: block; font-size: 13px; font-weight: 600;
      color: var(--text); margin-bottom: 7px;
    }

    .input-wrap { position: relative; }
    .input-icon {
      position: absolute; left: 13px; top: 50%;
      transform: translateY(-50%);
      font-size: 15px; opacity: .35; pointer-events: none;
    }

    .form-control {
      width: 100%;
      padding: 11px 14px 11px 40px;
      border: 1.5px solid var(--border);
      border-radius: 10px;
      font-size: 14px; font-family: inherit;
      color: var(--text); background: var(--bg);
      transition: border-color .2s, box-shadow .2s;
    }
    .form-control:focus {
      outline: none; border-color: var(--blue);
      background: white;
      box-shadow: 0 0 0 3px rgba(37,99,235,.1);
    }
    .form-control.error { border-color: var(--danger); }

    /* Indicador de fortaleza */
    .password-strength { margin-top: 8px; }
    .strength-bar {
      height: 4px; background: var(--border);
      border-radius: 2px; overflow: hidden; margin-bottom: 5px;
    }
    .strength-fill {
      height: 100%; border-radius: 2px;
      transition: width .3s, background .3s;
      width: 0%;
    }
    .strength-text { font-size: 11px; color: var(--muted); }

    .btn-register {
      width: 100%; padding: 13px;
      background: linear-gradient(135deg, var(--blue) 0%, #0ea5e9 100%);
      color: white; border: none; border-radius: 10px;
      font-size: 15px; font-weight: 700; font-family: inherit;
      cursor: pointer; transition: all .2s; margin-bottom: 20px;
      margin-top: 8px;
    }
    .btn-register:hover {
      transform: translateY(-1px);
      box-shadow: 0 6px 20px rgba(37,99,235,.4);
    }

    .terms {
      font-size: 12px; color: var(--muted);
      text-align: center; margin-bottom: 20px; line-height: 1.5;
    }
    .terms a { color: var(--blue); font-weight: 600; }

    .login-link {
      text-align: center; font-size: 13px; color: var(--muted);
      padding-top: 20px; border-top: 1px solid var(--border);
    }
    .login-link a { color: var(--blue); font-weight: 700; text-decoration: none; }
    .login-link a:hover { text-decoration: underline; }

    /* Success state */
    .success-state {
      text-align: center; padding: 20px 0;
    }
    .success-icon {
      width: 64px; height: 64px;
      background: var(--success-bg);
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      font-size: 28px; margin: 0 auto 20px;
    }
    .success-state h2 {
      font-size: 22px; font-weight: 800; margin-bottom: 8px;
    }
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
      .form-row { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>

<!-- PANEL IZQUIERDO -->
<div class="left-panel">
  <div class="left-content">
    <div class="left-brand">
      <div class="logo">🏥</div>
      Hospital Web
    </div>
  </div>

  <div class="left-hero left-content">
    <h2>Únete a nuestra comunidad de salud</h2>
    <p>Crea tu cuenta y accede a todos nuestros servicios médicos desde cualquier lugar.</p>
  </div>

  <div class="left-steps left-content">
    <h3>Cómo funciona</h3>
    <div class="step-item">
      <div class="step-num">1</div>
      <div>
        <div class="step-title">Crea tu cuenta</div>
        <div class="step-text">Regístrate en menos de un minuto</div>
      </div>
    </div>
    <div class="step-item">
      <div class="step-num">2</div>
      <div>
        <div class="step-title">Elige tu doctor</div>
        <div class="step-text">Selecciona la especialidad que necesitas</div>
      </div>
    </div>
    <div class="step-item">
      <div class="step-num">3</div>
      <div>
        <div class="step-title">Agenda tu cita</div>
        <div class="step-text">Elige fecha y hora disponible</div>
      </div>
    </div>
  </div>
</div>

<!-- PANEL DERECHO -->
<div class="right-panel">
  <a href="<?= BASE_URL ?>/login.php" class="back-link">← Volver al inicio de sesión</a>

  <?php if ($success): ?>
  <div class="success-state">
    <div class="success-icon">✅</div>
    <h2>¡Cuenta creada!</h2>
    <p>Tu cuenta ha sido registrada exitosamente. Ya puedes iniciar sesión.</p>
    <a href="<?= BASE_URL ?>/login.php" class="btn-goto">Ir al inicio de sesión →</a>
  </div>

  <?php else: ?>

  <div class="form-header">
    <h1>Crear cuenta</h1>
    <p>Regístrate como paciente gratuitamente</p>
  </div>

  <?php if ($error): ?>
  <div class="alert alert-danger">⚠️ <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST" id="registerForm">
    <div class="form-group">
      <label>Nombre completo</label>
      <div class="input-wrap">
        <span class="input-icon">👤</span>
        <input type="text" name="nombre" class="form-control"
               placeholder="Tu nombre completo" required
               value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>">
      </div>
    </div>

    <div class="form-group">
      <label>Correo electrónico</label>
      <div class="input-wrap">
        <span class="input-icon">✉️</span>
        <input type="email" name="email" class="form-control"
               placeholder="tu@correo.com" required
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
      </div>
    </div>

    <div class="form-group">
      <label>Contraseña</label>
      <div class="input-wrap">
        <span class="input-icon">🔒</span>
        <input type="password" name="password" id="password" class="form-control"
               placeholder="Mínimo 6 caracteres" required>
      </div>
      <div class="password-strength">
        <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
        <div class="strength-text" id="strengthText"></div>
      </div>
    </div>

    <div class="form-group">
      <label>Confirmar contraseña</label>
      <div class="input-wrap">
        <span class="input-icon">🔒</span>
        <input type="password" name="password2" id="password2" class="form-control"
               placeholder="Repite tu contraseña" required>
      </div>
    </div>

    <p class="terms">
      Al registrarte aceptas nuestros
      <a href="#">Términos de servicio</a> y
      <a href="#">Política de privacidad</a>
    </p>

    <button type="submit" class="btn-register">Crear cuenta gratis</button>
  </form>

  <div class="login-link">
    ¿Ya tienes cuenta? <a href="<?= BASE_URL ?>/login.php">Inicia sesión</a>
  </div>

  <?php endif; ?>
</div>

<script>
// Indicador de fortaleza de contraseña
const pwd = document.getElementById('password');
const fill = document.getElementById('strengthFill');
const text = document.getElementById('strengthText');

if (pwd) {
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
      { w: '25%',  bg: '#ef4444', t: 'Muy débil' },
      { w: '50%',  bg: '#f97316', t: 'Débil' },
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
}
</script>

</body>
</html>