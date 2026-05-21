<?php
$pageTitle = 'Mi Perfil';

require_once __DIR__ . '/../../config/config.php';
requireRole('paciente');

require_once __DIR__ . '/../../includes/db.php';

/*
|--------------------------------------------------------------------------
| OBTENER PACIENTE
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT 
        p.*,
        u.nombre,
        u.email
    FROM pacientes p
    JOIN usuarios u ON p.usuario_id = u.id
    WHERE p.usuario_id = ?
");

$stmt->bind_param('i', $_SESSION['usuario_id']);
$stmt->execute();

$paciente = $stmt->get_result()->fetch_assoc();

/*
|--------------------------------------------------------------------------
| SI EL PACIENTE NO EXISTE, CREAR PERFIL AUTOMATICAMENTE
|--------------------------------------------------------------------------
*/

if(!$paciente){

    $crear = $conn->prepare("
        INSERT INTO pacientes (usuario_id)
        VALUES (?)
    ");

    $crear->bind_param('i', $_SESSION['usuario_id']);
    $crear->execute();

    // Volver a consultar
    $stmt->execute();
    $paciente = $stmt->get_result()->fetch_assoc();
}

?>

<?php include __DIR__ . '/../../includes/header.php'; ?>

<div class="layout">

    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <div class="main">

        <!-- TOPBAR -->
        <div class="topbar">

            <div class="topbar-left">
                <h1>Mi Perfil</h1>
                <p>Información personal del paciente</p>
            </div>

            <div class="topbar-right">
                <div class="topbar-avatar">
                    <?= strtoupper(substr($_SESSION['nombre'],0,2)) ?>
                </div>
            </div>

        </div>

        <div class="content">

            <div class="card">

                <!-- HEADER PERFIL -->
                <div class="perfil-header">

                    <div class="perfil-avatar">
                        <?= strtoupper(substr($paciente['nombre'],0,2)) ?>
                    </div>

                    <div>
                        <h2><?= htmlspecialchars($paciente['nombre']) ?></h2>
                        <p>Paciente registrado</p>
                    </div>

                </div>

                <!-- DATOS -->
                <div class="perfil-grid">

                    <div class="perfil-item">
                        <label>Nombre</label>
                        <span><?= htmlspecialchars($paciente['nombre']) ?></span>
                    </div>

                    <div class="perfil-item">
                        <label>Correo</label>
                        <span><?= htmlspecialchars($paciente['email']) ?></span>
                    </div>

                    <div class="perfil-item">
                        <label>Teléfono</label>
                        <span><?= htmlspecialchars($paciente['telefono'] ?? 'No registrado') ?></span>
                    </div>

                    <div class="perfil-item">
                        <label>Dirección</label>
                        <span><?= htmlspecialchars($paciente['direccion'] ?? 'No registrada') ?></span>
                    </div>

                    <div class="perfil-item">
                        <label>Fecha de nacimiento</label>
                        <span><?= htmlspecialchars($paciente['fecha_nacimiento'] ?? 'No registrada') ?></span>
                    </div>

                    <div class="perfil-item">
                        <label>Género</label>
                        <span><?= htmlspecialchars($paciente['genero'] ?? 'No registrado') ?></span>
                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

<style>

.perfil-header{
    display:flex;
    align-items:center;
    gap:20px;
    margin-bottom:30px;
}

.perfil-avatar{
    width:80px;
    height:80px;
    border-radius:50%;
    background:#2563eb;
    color:white;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:28px;
    font-weight:bold;
}

.perfil-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit, minmax(250px, 1fr));
    gap:20px;
}

.perfil-item{
    background:#f8fafc;
    border:1px solid #e2e8f0;
    border-radius:12px;
    padding:18px;
}

.perfil-item label{
    display:block;
    color:#64748b;
    font-size:14px;
    margin-bottom:8px;
}

.perfil-item span{
    color:#0f172a;
    font-weight:600;
}

</style>

<?php include __DIR__ . '/../../includes/footer.php'; ?>