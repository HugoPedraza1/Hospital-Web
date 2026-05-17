<?php
require_once __DIR__ . '/config/config.php';
if (isLoggedIn()) {
    redirect('pages/' . $_SESSION['rol'] . '/dashboard.php');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Hospital Web — Cuidamos tu salud</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --blue: #2563eb;
      --blue-dark: #1d4ed8;
      --blue-light: #eff6ff;
      --blue-mid: #3b82f6;
      --text: #0f172a;
      --muted: #64748b;
      --border: #e2e8f0;
      --bg: #f8fafc;
      --white: #ffffff;
    }

    html { scroll-behavior: smooth; }

    body {
      font-family: 'Plus Jakarta Sans', sans-serif;
      color: var(--text);
      background: var(--white);
      overflow-x: hidden;
    }

    a { text-decoration: none; color: inherit; }

    /* ── NAV ── */
    nav {
      position: fixed; top: 0; left: 0; right: 0; z-index: 100;
      background: rgba(255,255,255,0.92);
      backdrop-filter: blur(12px);
      border-bottom: 1px solid var(--border);
      padding: 0 40px;
      height: 64px;
      display: flex; align-items: center; justify-content: space-between;
    }

    .nav-brand {
      display: flex; align-items: center; gap: 10px;
      font-size: 22px; font-weight: 800; color: var(--text);
    }
    .nav-brand .logo-icon {
      width: 36px; height: 36px; background: var(--blue);
      border-radius: 10px; display: flex; align-items: center;
      justify-content: center; font-size: 18px;
    }

    .nav-links {
      display: flex; align-items: center; gap: 32px;
      list-style: none;
    }
    .nav-links a {
      font-size: 14px; font-weight: 500; color: var(--muted);
      transition: color .2s;
    }
    .nav-links a:hover { color: var(--text); }

    .nav-actions { display: flex; align-items: center; gap: 12px; }

    .btn {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 10px 20px; border-radius: 10px;
      font-size: 14px; font-weight: 600; cursor: pointer;
      transition: all .2s; border: none; font-family: inherit;
    }
    .btn-ghost { background: transparent; color: var(--muted); }
    .btn-ghost:hover { color: var(--text); background: var(--bg); }
    .btn-primary { background: var(--blue); color: #fff; }
    .btn-primary:hover { background: var(--blue-dark); transform: translateY(-1px); box-shadow: 0 4px 12px rgba(37,99,235,.35); }
    .btn-outline { background: transparent; border: 1.5px solid var(--border); color: var(--text); }
    .btn-outline:hover { border-color: var(--blue); color: var(--blue); }
    .btn-lg { padding: 14px 28px; font-size: 15px; border-radius: 12px; }

    /* ── HERO ── */
    .hero {
      min-height: 100vh;
      padding: 64px 40px 0;
      display: flex; align-items: center;
      background: linear-gradient(135deg, #f0f7ff 0%, #ffffff 50%, #f0fdf4 100%);
      position: relative; overflow: hidden;
    }

    .hero::before {
      content: '';
      position: absolute; top: -200px; right: -200px;
      width: 600px; height: 600px;
      background: radial-gradient(circle, rgba(37,99,235,.08) 0%, transparent 70%);
      border-radius: 50%;
    }

    .hero-inner {
      max-width: 1200px; margin: 0 auto; width: 100%;
      display: grid; grid-template-columns: 1fr 1fr;
      gap: 60px; align-items: center;
      padding: 80px 0;
    }


    @keyframes pulse {
      0%, 100% { opacity: 1; transform: scale(1); }
      50% { opacity: .5; transform: scale(1.3); }
    }

    .hero h1 {
      font-size: clamp(36px, 5vw, 56px);
      font-weight: 800; line-height: 1.1;
      letter-spacing: -1.5px;
      margin-bottom: 20px;
      color: var(--text);
    }

    .hero h1 .highlight { color: var(--blue); }

    .hero p {
      font-size: 17px; color: var(--muted);
      line-height: 1.7; margin-bottom: 36px;
      max-width: 480px;
    }

    .hero-actions { display: flex; gap: 14px; flex-wrap: wrap; }

    .hero-visual {
      position: relative; display: flex;
      justify-content: center; align-items: center;
    }

    .hero-img-wrap {
      width: 100%; max-width: 620px;
      aspect-ratio: 4/3;
      border-radius: 24px; overflow: hidden;
      box-shadow: 0 24px 60px rgba(0,0,0,.15);
      position: relative;
    }

    .hero-img-wrap img {
      width: 100%; height: 100%; object-fit: cover;
    }

    .hero-img-placeholder {
      width: 100%; height: 100%;
      background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 50%, #93c5fd 100%);
      display: flex; align-items: center; justify-content: center;
      font-size: 80px;
    }

    .floating-card {
      position: absolute;
      background: white;
      border-radius: 14px;
      padding: 14px 18px;
      box-shadow: 0 8px 30px rgba(0,0,0,.12);
      display: flex; align-items: center; gap: 10px;
      font-size: 13px; font-weight: 600;
      animation: float 3s ease-in-out infinite;
    }

    .floating-card.card-1 { bottom: -20px; left: -20px; animation-delay: 0s; }
    .floating-card.card-2 { top: -20px; right: -20px; animation-delay: 1.5s; }

    @keyframes float {
      0%, 100% { transform: translateY(0); }
      50% { transform: translateY(-8px); }
    }

    .fc-icon {
      width: 36px; height: 36px; border-radius: 10px;
      display: flex; align-items: center; justify-content: center;
      font-size: 18px;
    }
    .fc-icon.green { background: #dcfce7; }
    .fc-icon.blue  { background: #dbeafe; }

    /* ── SERVICES ── */
    .section {
      padding: 96px 40px;
      max-width: 1200px; margin: 0 auto;
    }

    .section-header { text-align: center; margin-bottom: 56px; }
    .section-tag {
      display: inline-block;
      background: var(--while); color: var(--blue);
      padding: 4px 14px; border-radius: 20px;
      font-size: 33px; font-weight: 600;
      margin-bottom: 14px;
    }
    .section-header h2 {
      font-size: clamp(28px, 4vw, 40px);
      font-weight: 800; letter-spacing: -1px;
      color: var(--text);
    }
    .section-header p {
      font-size: 16px; color: var(--muted);
      margin-top: 12px; max-width: 500px; margin-left: auto; margin-right: auto;
    }

    .services-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
      gap: 24px;
    }

    .service-card {
      background: var(--white);
      border: 1.5px solid var(--border);
      border-radius: 18px;
      padding: 28px;
      transition: all .25s;
      cursor: default;
    }
    .service-card:hover {
      border-color: var(--blue);
      box-shadow: 0 8px 30px rgba(37,99,235,.1);
      transform: translateY(-4px);
    }

    .service-icon {
      width: 52px; height: 52px;
      border-radius: 14px;
      display: flex; align-items: center; justify-content: center;
      font-size: 24px; margin-bottom: 18px;
    }

    .service-card h3 { font-size: 17px; font-weight: 700; margin-bottom: 8px; }
    .service-card p  { font-size: 14px; color: var(--muted); line-height: 1.6; }

    /* ── DOCTORS ── */
    .doctors-section {
      background: var(--bg);
      padding: 96px 40px;
    }
    .doctors-inner { max-width: 1200px; margin: 0 auto; }

    .doctors-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
      gap: 24px;
      margin-top: 56px;
    }

    .doctor-card {
      background: var(--white);
      border-radius: 18px;
      padding: 28px 20px;
      text-align: center;
      border: 1.5px solid var(--border);
      transition: all .25s;
    }
    .doctor-card:hover {
      border-color: var(--blue);
      box-shadow: 0 8px 30px rgba(37,99,235,.1);
      transform: translateY(-4px);
    }

    .doctor-avatar {
      width: 72px; height: 72px;
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      font-size: 28px; font-weight: 800; color: white;
      margin: 0 auto 14px;
    }

    .doctor-card h3 { font-size: 16px; font-weight: 700; margin-bottom: 4px; }
    .doctor-card .spec { font-size: 13px; color: var(--muted); margin-bottom: 12px; }
    .doctor-card .avail {
      display: inline-flex; align-items: center; gap: 6px;
      font-size: 12px; font-weight: 600; color: #000000;
      background: #ffffff; padding: 4px 10px; border-radius: 20px;
    }
    .doctor-card .avail::before { content: ''; width: 6px; height: 6px; background: #000000; border-radius: 50%; }

    /* ── CTA ── */
    .cta-section {
      padding: 96px 40px;
      text-align: center;
      background: linear-gradient(135deg, #1e40af 0%, #2563eb 60%, #0ea5e9 100%);
      color: white;
    }
    .cta-section h2 {
      font-size: clamp(28px, 4vw, 42px);
      font-weight: 800; letter-spacing: -1px;
      margin-bottom: 16px;
    }
    .cta-section p {
      font-size: 17px; opacity: .85;
      margin-bottom: 36px; max-width: 500px; margin-left: auto; margin-right: auto;
    }
    .btn-white { background: white; color: var(--blue); font-weight: 700; }
    .btn-white:hover { background: #f0f7ff; transform: translateY(-1px); box-shadow: 0 4px 20px rgba(0,0,0,.2); }

    /* ── FOOTER ── */
    footer {
      background: #0f172a; color: #94a3b8;
      padding: 40px;
      text-align: center;
      font-size: 14px;
    }
    footer .footer-brand {
      font-size: 18px; font-weight: 800; color: white; margin-bottom: 8px;
    }

    /* ── RESPONSIVE ── */
    @media (max-width: 768px) {
      nav { padding: 0 20px; }
      .nav-links { display: none; }
      .hero-inner { grid-template-columns: 1fr; padding: 40px 0; gap: 40px; }
      .hero-visual { display: none; }
      .hero, .section, .doctors-section, .cta-section { padding-left: 20px; padding-right: 20px; }
      .hero-stats { gap: 20px; }
    }
  </style>
</head>
<body>

<!-- NAV -->
<nav>
  <div class="nav-brand">
        Hospital Palacio de la salud
  </div>
  <ul class="nav-links">
    <li><a href="#servicios">Servicios</a></li>
    <li><a href="#doctores">Doctores</a></li>
    <li><a href="#contacto">Contacto</a></li>
  </ul>
  <div class="nav-actions">
    <a href="<?= BASE_URL ?>/login.php" class="btn btn-ghost">Iniciar sesión</a>
    <a href="<?= BASE_URL ?>/login.php" class="btn btn-primary">Agendar Cita</a>
  </div>
</nav>

<!-- HERO -->
<section class="hero">
  <div class="hero-inner">
    <div class="hero-content">
      <h1>
        Cuidamos tu salud<br>
        con <span class="highlight">tecnología</span><br>
        y confianza
      </h1>
      <p>
        Atención médica de excelencia con los mejores especialistas y
        tecnología de última generación. Tu salud es nuestra prioridad.
      </p>
      <div class="hero-actions">
        <a href="<?= BASE_URL ?>/login.php" class="btn btn-primary btn-lg">
          Reservar Cita
        </a>
        <a href="#servicios" class="btn btn-outline btn-lg">
          Conocer Servicios
        </a>
      </div>
    </div>

    <div class="hero-visual">
      <div class="hero-img-wrap">
        <img src="<?= BASE_URL ?>/assets/img/landing.jpg" alt="Imagen de hospital">
      </div>
    </div>
  </div>
</section>

<!-- SERVICIOS -->
<div id="servicios">
<div class="section">
  <div class="section-header">
    <div class="section-tag">Nuestros Servicios</div>
    <h2>Todo lo que necesitas en un solo lugar</h2>
    <p>Contamos con las especialidades médicas más importantes para cuidar tu salud.</p>
  </div>
  <div class="services-grid">
    <?php
    $services = [
      ['<img src="'. BASE_URL .'/assets/img/svg/heart.svg" width="28">','Cardiología','Diagnóstico y tratamiento de enfermedades del corazón y sistema cardiovascular.'],
      ['<img src="'. BASE_URL .'/assets/img/svg/brain.svg" width="28">','Neurología','Atención especializada en enfermedades del sistema nervioso central y periférico.'],
      ['<img src="'. BASE_URL .'/assets/img/svg/baby.svg" width="28">','Pediatría','Cuidado integral de la salud de niños y adolescentes con especialistas dedicados.'],
      ['<img src="'. BASE_URL .'/assets/img/svg/bone.svg" width="28">','Traumatología','Diagnóstico y tratamiento de lesiones y enfermedades del sistema músculo-esquelético.'],
      ['<img src="'. BASE_URL .'/assets/img/svg/smile.svg" width="28">','Dermatología','Diagnóstico y tratamiento de enfermedades de la piel, cabello y uñas.'],
      ['<img src="'. BASE_URL .'/assets/img/svg/stethoscope.svg" width="28">','Medicina General','Atención primaria y preventiva para toda la familia en un solo lugar.'],
    ];
    foreach ($services as [$icon, $name, $desc]):
    ?>
    <div class="service-card">
      <div class="service-icon"><?= $icon ?></div>
      <h3><?= $name ?></h3>
      <p><?= $desc ?></p>
    </div>
    <?php endforeach; ?>
  </div>
</div>
</div>

<!-- DOCTORES -->
<div id="doctores">
<div class="doctors-section">
  <div class="doctors-inner">
    <div class="section-header">
      <div class="section-tag">Nuestro Equipo</div>
      <h2>Especialistas de confianza</h2>
      <p>Contamos con médicos certificados y con amplia experiencia en sus especialidades.</p>
    </div>
    <div class="doctors-grid">
      <?php
      $colors = ['#2563eb','#7c3aed','#db2777','#059669','#d97706','#dc2626'];
      $doctors = [];
      require_once __DIR__ . '/includes/db.php';
      $res = $conn->query("SELECT u.nombre, e.nombre AS especialidad FROM doctores d JOIN usuarios u ON d.usuario_id = u.id LEFT JOIN especialidades e ON d.especialidad_id = e.id WHERE d.disponible = 1 LIMIT 6");
      if ($res) while ($r = $res->fetch_assoc()) $doctors[] = $r;

      if (empty($doctors)) {
        $doctors = [
          ['nombre'=>'Dr. Carlos Ruiz','especialidad'=>'Cardiología'],
          ['nombre'=>'Dra. Ana López','especialidad'=>'Neurología'],
          ['nombre'=>'Dr. Mario Pérez','especialidad'=>'Pediatría'],
          ['nombre'=>'Dra. Sofía García','especialidad'=>'Dermatología'],
        ];
      }

      foreach ($doctors as $i => $d):
        $inicial = strtoupper(substr($d['nombre'], 0, 2));
        $color = $colors[$i % count($colors)];
      ?>
      <div class="doctor-card">
        <div class="doctor-avatar" style="background:<?= $color ?>;"><?= $inicial ?></div>
        <h3><?= htmlspecialchars($d['nombre']) ?></h3>
        <div class="spec"><?= htmlspecialchars($d['especialidad'] ?? 'Medicina General') ?></div>
        <div class="avail">Disponible</div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
</div>

<!-- CTA -->
<div id="contacto">
<div class="cta-section">
  <h2>¿Listo para cuidar tu salud?</h2>
  <p>Agenda tu cita en minutos y recibe atención médica de primera calidad.</p>
  <a href="<?= BASE_URL ?>/login.php" class="btn btn-white btn-lg">
    Agendar mi cita ahora
  </a>
</div>
</div>

<!-- FOOTER -->
<footer>
  <div class="footer-brand">Hospital Palacio de la salud</div>
  <p>Hospital Palacio de la salud. Desarrollado por: Valeria Salinas, Aylin Lobato, Hugo Pedraza.</p>
</footer>

</body>
</html>