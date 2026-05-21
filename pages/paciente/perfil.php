<?php
$pageTitle = 'Perfil del Paciente';
require_once __DIR__ . '/../../config/config.php';
requireRole('paciente');
require_once __DIR__ . '/../../includes/db.php';

$pacienteRow = $conn->prepare("
    SELECT 
        p.*,
        u.nombre,
        u.email
    FROM pacientes p
    JOIN usuarios u ON p.usuario_id = u.id
    WHERE p.usuario_id = ?
");

$pacienteRow->bind_param('i', $_SESSION['usuario_id']);
$pacienteRow->execute();

$paciente = $pacienteRow->get_result()->fetch_assoc();

if(!$paciente){
    die('No existe perfil de paciente');
}

$edad = '';

if(!empty($paciente['fecha_nacimiento'])){
    $nacimiento = new DateTime($paciente['fecha_nacimiento']);
    $hoy = new DateTime();
    $edad = $hoy->diff($nacimiento)->y;
}
?>

<?php include __DIR__ . '/../../includes/header.php'; ?>

<div class="layout">

<?php include __DIR__ . '/../../includes/sidebar.php'; ?>

<div class="main">

<div class="topbar">

    <div class="topbar-left">
        <h1>Perfil del Paciente</h1>
        <p>Informacion personal del paciente</p>
    </div>

    <div class="topbar-right">
        <div class="topbar-avatar">
            <?= strtoupper(substr($_SESSION['nombre'],0,2)) ?>
        </div>
    </div>

</div>

<div class="content">

<div class="card">

<div class="card-header">
    <div>
        <h2>Informacion Personal</h2>
        <p>Datos registrados del paciente</p>
    </div>
</div>

<div class="card-body">

<div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">

<div class="form-group">
<label>Nombre completo</label>

<input type="text"
       class="form-control"
       value="<?= htmlspecialchars($paciente['nombre']) ?>"
       readonly>
</div>

<div class="form-group">
<label>Correo electronico</label>

<input type="email"
       class="form-control"
       value="<?= htmlspecialchars($paciente['email']) ?>"
       readonly>
</div>

<div class="form-group">
<label>Telefono</label>

<input type="text"
       class="form-control"
       value="<?= htmlspecialchars($paciente['telefono'] ?? 'No registrado') ?>"
       readonly>
</div>

<div class="form-group">
<label>Direccion</label>

<input type="text"
       class="form-control"
       value="<?= htmlspecialchars($paciente['direccion'] ?? 'No registrada') ?>"
       readonly>
</div>

<div class="form-group">
<label>Fecha de nacimiento</label>

<input type="text"
       class="form-control"
       value="<?= htmlspecialchars($paciente['fecha_nacimiento'] ?? 'No registrada') ?>"
       readonly>
</div>

<div class="form-group">
<label>Edad</label>

<input type="text"
       class="form-control"
       value="<?= $edad ?> años"
       readonly>
</div>

<div class="form-group">
<label>Tipo de sangre</label>

<input type="text"
       class="form-control"
       value="<?= htmlspecialchars($paciente['tipo_sangre'] ?? 'No registrado') ?>"
       readonly>
</div>

<div class="form-group" style="grid-column:1 / span 2;">
<label>Alergias</label>

<textarea class="form-control"
          rows="3"
          readonly><?= htmlspecialchars($paciente['alergias'] ?? 'No registradas') ?></textarea>
</div>

</div>

</div>

</div>

</div>

</div>

</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>