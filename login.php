<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';

if (isLoggedIn()) redirect('pages/' . $_SESSION['rol'] . '/dashboard.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';

    if ($email && $pass) {
        $stmt = $conn->prepare("SELECT id, nombre, password, rol FROM usuarios WHERE email = ? AND activo = 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user && password_verify($pass, $user['password'])) {
            $_SESSION['usuario_id'] = $user['id'];
            $_SESSION['nombre']     = $user['nombre'];
            $_SESSION['rol']        = $user['rol'];
            redirect('pages/' . $user['rol'] . '/dashboard.php');
        } else {
            $error = 'Correo o contraseña incorrectos.';
        }
    } else {
        $error = 'Por favor completa todos los campos.';
    }
}

// Acceso rápido por rol
$quickEmail = '';
if (isset($_GET['rol'])) {
    $quickEmail = match($_GET['rol']) {
        'admin'    => 'admin@hospital.com',
        'doctor'   => 'doctor@hospital.com',
        'paciente' => 'paciente@hospital.com',
        default    => ''
    };
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Iniciar sesión — Hospital Palacio de la salud</title>
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
      background: url('<?= BASE_URL ?>/assets/img/img-login.jpg') center/cover no-repeat;
      opacity: 0.75;
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

    .left-panel::after {
      content: '';
      position: absolute; inset: 0;
      background: rgba(0, 0, 0, 0.35);
    }

    .left-brand .logo {
      width: 44px; height: 44px;
      background: rgba(255,255,255,.2);
      border-radius: 12px;
      display: flex; align-items: center; justify-content: center;
      font-size: 22px; backdrop-filter: blur(10px);
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
      font-size: 16px;
      opacity: .8;
      line-height: 1.6;
      max-width: 380px;
    }

    .left-stats {
      position: relative; z-index: 1;
      display: flex; gap: 24px;
    }
    .ls-item {
      background: rgba(255,255,255,.15);
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255,255,255,.2);
      border-radius: 14px;
      padding: 16px 20px;
      color: white;
    }
    .ls-item .num { font-size: 22px; font-weight: 800; }
    .ls-item .lbl { font-size: 12px; opacity: .75; margin-top: 2px; }

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
      transition: color .2s;
      text-decoration: none;
    }
    .back-link:hover { color: var(--blue); }

    .form-header { margin-bottom: 32px; }
    .form-header h1 {
      font-size: 28px; font-weight: 800;
      letter-spacing: -.5px; color: var(--text);
      margin-bottom: 6px;
    }
    .form-header p { font-size: 14px; color: var(--muted); }

    .alert {
      padding: 12px 16px;
      border-radius: 10px;
      font-size: 13px;
      margin-bottom: 20px;
      background: #fef2f2;
      border: 1px solid #fecaca;
      color: #b91c1c;
    }

    .form-group { margin-bottom: 18px; }
    .form-group label {
      display: block;
      font-size: 13px; font-weight: 600;
      color: var(--text); margin-bottom: 7px;
    }

    .input-wrap {
      position: relative;
    }
    .input-icon {
      position: absolute; left: 14px; top: 50%;
      transform: translateY(-50%);
      font-size: 16px; opacity: .4;
      pointer-events: none;
    }
    .form-control {
      width: 100%;
      padding: 12px 14px 12px 42px;
      border: 1.5px solid var(--border);
      border-radius: 10px;
      font-size: 14px;
      font-family: inherit;
      color: var(--text);
      background: var(--bg);
      transition: border-color .2s, box-shadow .2s, background .2s;
    }
    .form-control:focus {
      outline: none;
      border-color: var(--blue);
      background: white;
      box-shadow: 0 0 0 3px rgba(37,99,235,.12);
    }

    .form-extras {
      display: flex; align-items: center;
      justify-content: space-between;
      margin-bottom: 22px;
    }
    .remember {
      display: flex; align-items: center; gap: 8px;
      font-size: 13px; color: var(--muted); cursor: pointer;
    }
    .remember input { accent-color: var(--blue); }
    .forgot { font-size: 13px; color: var(--blue); font-weight: 600; text-decoration: none; }
    .forgot:hover { text-decoration: underline; }

    .btn-login {
      width: 100%;
      padding: 13px;
      background: linear-gradient(135deg, var(--blue) 0%, #0ea5e9 100%);
      color: white;
      border: none;
      border-radius: 10px;
      font-size: 15px;
      font-weight: 700;
      font-family: inherit;
      cursor: pointer;
      transition: all .2s;
      margin-bottom: 20px;
    }
    .btn-login:hover {
      transform: translateY(-1px);
      box-shadow: 0 6px 20px rgba(37,99,235,.4);
    }

    .divider {
      display: flex; align-items: center; gap: 12px;
      margin-bottom: 20px;
      font-size: 13px; color: var(--muted);
    }
    .divider::before, .divider::after {
      content: ''; flex: 1;
      height: 1px; background: var(--border);
    }

    /* Acceso rápido por rol */
    .quick-access { margin-bottom: 28px; }
    .quick-label {
      font-size: 12px; color: var(--muted);
      text-align: center; margin-bottom: 12px;
    }
    .quick-btns {
      display: grid; grid-template-columns: repeat(3, 1fr);
      gap: 10px;
    }
    .quick-btn {
      padding: 10px 8px;
      border: 1.5px solid var(--border);
      border-radius: 10px;
      background: white;
      font-size: 13px; font-weight: 600;
      color: var(--blue);
      cursor: pointer;
      font-family: inherit;
      transition: all .2s;
      text-align: center;
      text-decoration: none;
      display: block;
    }
    .quick-btn:hover {
      border-color: var(--blue);
      background: #eff6ff;
    }
    .quick-btn.active {
      border-color: var(--blue);
      background: #eff6ff;
    }

    .register-link {
      text-align: center;
      font-size: 13px; color: var(--muted);
      padding-top: 20px;
      border-top: 1px solid var(--border);
    }
    .register-link a { color: var(--blue); font-weight: 700; text-decoration: none; }
    .register-link a:hover { text-decoration: underline; }

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
    <h2>Tu salud en las mejores manos</h2>
    <p>Accede a tu cuenta y gestiona tus citas médicas, historial clínico y mucho más desde un solo lugar.</p>
  </div>
</div>

<!-- PANEL DERECHO -->
<div class="right-panel">
  <a href="<?= BASE_URL ?>/index.php" class="back-link">← Volver al inicio</a>

  <div class="form-header">
    <h1>Bienvenido</h1>
    <p>Ingresa a tu cuenta para continuar</p>
  </div>

  <?php if ($error): ?>
  <div class="alert"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST">
    <div class="form-group">
      <label for="email">Correo Electrónico</label>
      <div class="input-wrap">
        <input type="email" id="email" name="email" class="form-control"
               placeholder="correo@ejemplo.com" required
               value="<?= htmlspecialchars($quickEmail ?: ($_POST['email'] ?? '')) ?>">
      </div>
    </div>

    <div class="form-group">
      <label for="password">Contraseña</label>
      <div class="input-wrap">
        <input type="password" id="password" name="password" class="form-control"
               placeholder="••••••••" required>
      </div>
    </div>

    <div class="form-extras">
      <label class="remember">
        <input type="checkbox" name="remember"> Recordarme
      </label>
      <a href="#" class="forgot">¿Olvidaste tu contraseña?</a>
    </div>

    <button type="submit" class="btn-login">Iniciar Sesión</button>
  </form>

  <div class="divider">O continúa con</div>

  <div class="quick-access">
    <div class="quick-label">Acceso rápido:</div>
    <div class="quick-btns">
      <a href="?rol=paciente" class="quick-btn <?= ($_GET['rol'] ?? '') === 'paciente' ? 'active' : '' ?>">Paciente</a>
      <a href="?rol=doctor"   class="quick-btn <?= ($_GET['rol'] ?? '') === 'doctor'   ? 'active' : '' ?>">Doctor</a>
      <a href="?rol=admin"    class="quick-btn <?= ($_GET['rol'] ?? '') === 'admin'    ? 'active' : '' ?>">Admin</a>
    </div>
  </div>

  <div class="register-link">
    ¿No tienes una cuenta? <a href="<?= BASE_URL ?>/register.php">Regístrate aquí</a>
  </div>
</div>

</body>
</html>