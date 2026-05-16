<?php
// menú lateral según rol tgengo, que agregar los svg de los iconos en cada item del menú
$rol = $_SESSION['rol'] ?? 'paciente';
$nombre = $_SESSION['nombre'] ?? 'Usuario';
$inicial = strtoupper(substr($nombre, 0, 1));

$navAdmin = [
  ['icon'=>'','label'=>'Dashboard','url'=>'pages/admin/dashboard.php'],
  ['icon'=>'','label'=>'Usuarios','url'=>'pages/admin/users.php'],
  ['icon'=>'','label'=>'Doctores','url'=>'pages/doctores/listar.php'],
  ['icon'=>'','label'=>'Citas','url'=>'pages/citas/ver.php'],
  ['icon'=>'','label'=>'Reportes','url'=>'pages/admin/reports.php'],
];
$navDoctor = [
  ['icon'=>'','label'=>'Dashboard','url'=>'pages/doctor/dashboard.php'],
  ['icon'=>'','label'=>'Mis citas','url'=>'pages/doctor/citas.php'],
  ['icon'=>'','label'=>'Mi perfil','url'=>'pages/doctor/perfil.php'],
];
$navPaciente = [
  ['icon'=>'','label'=>'Dashboard','url'=>'pages/paciente/dashboard.php'],
  ['icon'=>'','label'=>'Agendar cita','url'=>'pages/citas/agendar.php'],
  ['icon'=>'','label'=>'Mi historial','url'=>'pages/paciente/historial.php'],
];

$nav = match($rol) {
  'admin' => $navAdmin,
  'doctor' => $navDoctor,
  default => $navPaciente,
};
?>
<aside class="sidebar">
  <div class="sidebar-brand">
    <div class="brand-name"><?= APP_NAME ?></div>
    <div class="brand-role"><?= ucfirst($rol) ?>: <?= htmlspecialchars($nombre) ?></div>
  </div>
  <nav class="sidebar-nav">
    <div class="nav-section-label">Menú</div>
    <?php foreach ($nav as $item): ?>
    <a href="<?= BASE_URL ?>/<?= $item['url'] ?>"
       class="nav-item <?= str_contains($_SERVER['REQUEST_URI'], $item['url']) ? 'active' : '' ?>">
      <span class="nav-icon"><?= $item['icon'] ?></span>
      <?= $item['label'] ?>
    </a>
    <?php endforeach; ?>
  </nav>
  <div class="sidebar-footer">
    <a href="<?= BASE_URL ?>/logout.php" class="nav-item" style="padding:0;color:#94a3b8;">
      Cerrar sesión
    </a>
  </div>
</aside>
