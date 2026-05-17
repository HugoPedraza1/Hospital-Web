<?php
$pageTitle = 'Panel Administrativo';
require_once __DIR__ . '/../../config/config.php';
requireRole('admin');
require_once __DIR__ . '/../../includes/db.php';

// Stats
$stats = [];
foreach (['usuarios','doctores','pacientes','citas'] as $t) {
    $r = $conn->query("SELECT COUNT(*) c FROM $t");
    $stats[$t] = $r ? $r->fetch_assoc()['c'] : 0;
}

// Citas hoy
$hoy = $conn->query("SELECT COUNT(*) c FROM citas WHERE fecha = CURDATE()")->fetch_assoc()['c'];

// Ultimas citas
$citasRecientes = $conn->query("
    SELECT c.id, c.fecha, c.hora, c.estado,
           up.nombre AS paciente, ud.nombre AS doctor,
           e.nombre AS especialidad
    FROM citas c
    JOIN pacientes p ON c.paciente_id = p.id
    JOIN usuarios up ON p.usuario_id = up.id
    JOIN doctores d ON c.doctor_id = d.id
    JOIN usuarios ud ON d.usuario_id = ud.id
    LEFT JOIN especialidades e ON d.especialidad_id = e.id
    ORDER BY c.created_at DESC LIMIT 8
");

// Pacientes recientes
$pacientesRecientes = $conn->query("
    SELECT u.nombre, u.email, u.created_at
    FROM usuarios u
    WHERE u.rol = 'paciente'
    ORDER BY u.created_at DESC LIMIT 5
");

$colores = ['#2563eb','#7c3aed','#db2777','#059669','#d97706','#dc2626'];
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>

<div class="layout">
  <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

  <div class="main">

    <!-- Topbar -->
    <div class="topbar">
      <div class="topbar-left">
        <h1>Panel Administrativo</h1>
        <p>Administrador General</p>
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
            <div class="stat-card-icon blue">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </div>
            <div class="stat-value"><?= $stats['usuarios'] ?></div>
            <div class="stat-label">Total Usuarios</div>
          </div>
        </div>

        <div class="stat-card">
          <div>
            <div class="stat-card-icon green">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4.8 2.3A.3.3 0 1 0 5 2H4a2 2 0 0 0-2 2v5a6 6 0 0 0 6 6 6 6 0 0 0 6-6V4a2 2 0 0 0-2-2h-1a.2.2 0 1 0 .3.3"/><path d="M8 15v1a6 6 0 0 0 6 6v0a6 6 0 0 0 6-6v-4"/><circle cx="20" cy="10" r="2"/></svg>
            </div>
            <div class="stat-value"><?= $stats['doctores'] ?></div>
            <div class="stat-label">Total Doctores</div>
          </div>
        </div>

        <div class="stat-card">
          <div>
            <div class="stat-card-icon purple">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#7c3aed" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            </div>
            <div class="stat-value"><?= $stats['pacientes'] ?></div>
            <div class="stat-label">Total Pacientes</div>
          </div>
        </div>

        <div class="stat-card">
          <div>
            <div class="stat-card-icon amber">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            </div>
            <div class="stat-value"><?= $hoy ?></div>
            <div class="stat-label">Citas del Dia</div>
          </div>
        </div>
      </div>

      <!-- Grid de tablas -->
      <div class="card-grid">

        <!-- Citas recientes -->
        <div class="card">
          <div class="card-header">
            <div>
              <h2>Citas recientes</h2>
              <p>Ultimas citas registradas</p>
            </div>
            <a href="<?= BASE_URL ?>/pages/citas/ver.php" class="btn btn-outline btn-sm">Ver todas</a>
          </div>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Paciente</th>
                  <th>Doctor</th>
                  <th>Fecha</th>
                  <th>Estado</th>
                </tr>
              </thead>
              <tbody>
              <?php while ($c = $citasRecientes->fetch_assoc()):
                $ini = strtoupper(substr($c['paciente'], 0, 2));
                $color = $colores[array_search($c['paciente'], array_column([], 'paciente')) % count($colores)];
              ?>
                <tr>
                  <td>
                    <div class="td-user">
                      <div class="table-avatar" style="background:<?= $colores[crc32($c['paciente']) % count($colores)] ?>;"><?= $ini ?></div>
                      <div>
                        <div class="td-primary"><?= htmlspecialchars($c['paciente']) ?></div>
                        <div class="td-muted"><?= htmlspecialchars($c['especialidad'] ?? '') ?></div>
                      </div>
                    </div>
                  </td>
                  <td><?= htmlspecialchars($c['doctor']) ?></td>
                  <td>
                    <div class="td-primary"><?= date('d/m/Y', strtotime($c['fecha'])) ?></div>
                    <div class="td-muted"><?= $c['hora'] ?></div>
                  </td>
                  <td>
                    <?php
                    $map = ['pendiente'=>'badge-warning','confirmada'=>'badge-success',
                            'cancelada'=>'badge-danger','completada'=>'badge-info'];
                    ?>
                    <span class="badge <?= $map[$c['estado']] ?? 'badge-gray' ?>">
                      <?= ucfirst($c['estado']) ?>
                    </span>
                  </td>
                </tr>
              <?php endwhile; ?>
              <?php if ($stats['citas'] == 0): ?>
                <tr><td colspan="4">
                  <div class="empty-state">
                    <div class="empty-icon">
                      <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    </div>
                    <p>No hay citas registradas</p>
                  </div>
                </td></tr>
              <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Pacientes recientes -->
        <div class="card">
          <div class="card-header">
            <div>
              <h2>Pacientes recientes</h2>
              <p>Ultimos registros</p>
            </div>
            <a href="<?= BASE_URL ?>/pages/admin/users.php" class="btn btn-outline btn-sm">Ver todos</a>
          </div>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Paciente</th>
                  <th>Registro</th>
                </tr>
              </thead>
              <tbody>
              <?php while ($p = $pacientesRecientes->fetch_assoc()):
                $ini = strtoupper(substr($p['nombre'], 0, 2));
              ?>
                <tr>
                  <td>
                    <div class="td-user">
                      <div class="table-avatar" style="background:<?= $colores[crc32($p['nombre']) % count($colores)] ?>;"><?= $ini ?></div>
                      <div>
                        <div class="td-primary"><?= htmlspecialchars($p['nombre']) ?></div>
                        <div class="td-muted"><?= htmlspecialchars($p['email']) ?></div>
                      </div>
                    </div>
                  </td>
                  <td>
                    <div class="td-muted"><?= date('d/m/Y', strtotime($p['created_at'])) ?></div>
                  </td>
                </tr>
              <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>