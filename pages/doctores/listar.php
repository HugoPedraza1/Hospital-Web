<?php
$pageTitle = 'Doctores';
require_once __DIR__ . '/../../config/config.php';
requireLogin();
require_once __DIR__ . '/../../includes/db.php';

$doctores = $conn->query("
    SELECT d.id, u.nombre, u.email, e.nombre AS especialidad,
           d.telefono, d.disponible
    FROM doctores d
    JOIN usuarios u ON d.usuario_id = u.id
    LEFT JOIN especialidades e ON d.especialidad_id = e.id
    ORDER BY u.nombre
");
$colores = ['#2563eb','#7c3aed','#db2777','#059669','#d97706','#dc2626'];
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>

<div class="layout">
  <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

  <div class="main">
    <div class="topbar">
      <div class="topbar-left">
        <h1>Doctores</h1>
        <p>Especialistas disponibles</p>
      </div>
      <div class="topbar-right">
        <div class="notif-btn">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
          <span class="notif-dot"></span>
        </div>
        <div class="topbar-avatar"><?= strtoupper(substr($_SESSION['nombre'],0,2)) ?></div>
      </div>
    </div>

    <div class="content">
      <?php if ($_SESSION['rol'] === 'admin'): ?>
      <div style="margin-bottom:20px;">
        <a href="<?= BASE_URL ?>/pages/doctores/registrar.php" class="btn btn-primary">+ Registrar Doctor</a>
      </div>
      <?php endif; ?>

      <div class="card">
        <div class="card-header">
          <div><h2>Listado de doctores</h2></div>
        </div>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Doctor</th>
                <th>Especialidad</th>
                <th>Email</th>
                <th>Telefono</th>
                <th>Estado</th>
                <?php if ($_SESSION['rol'] === 'paciente'): ?>
                <th>Accion</th>
                <?php endif; ?>
              </tr>
            </thead>
            <tbody>
            <?php $i = 0; while ($d = $doctores->fetch_assoc()):
              $ini = strtoupper(substr($d['nombre'], 0, 2));
              $color = $colores[$i++ % count($colores)];
            ?>
              <tr>
                <td>
                  <div class="td-user">
                    <div class="table-avatar" style="background:<?= $color ?>;"><?= $ini ?></div>
                    <div>
                      <div class="td-primary"><?= htmlspecialchars($d['nombre']) ?></div>
                      <div class="td-muted"><?= htmlspecialchars($d['email']) ?></div>
                    </div>
                  </div>
                </td>
                <td><?= htmlspecialchars($d['especialidad'] ?? 'Sin asignar') ?></td>
                <td><?= htmlspecialchars($d['email']) ?></td>
                <td><?= htmlspecialchars($d['telefono'] ?? '—') ?></td>
                <td>
                  <span class="badge <?= $d['disponible'] ? 'badge-success' : 'badge-danger' ?>">
                    <?= $d['disponible'] ? 'Disponible' : 'No disponible' ?>
                  </span>
                </td>
                <?php if ($_SESSION['rol'] === 'paciente'): ?>
                <td>
                  <a href="<?= BASE_URL ?>/pages/citas/agendar.php?doctor_id=<?= $d['id'] ?>"
                     class="btn btn-primary btn-sm">Agendar cita</a>
                </td>
                <?php endif; ?>
              </tr>
            <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>