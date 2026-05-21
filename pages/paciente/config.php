<?php
$pageTitle = 'Configuracion';
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

$pid = $paciente['id'];

$success = '';
$error = '';

if(isset($_POST['guardar'])){

    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $telefono = trim($_POST['telefono']);
    $direccion = trim($_POST['direccion']);
    $fecha_nacimiento = $_POST['fecha_nacimiento'];
    $tipo_sangre = trim($_POST['tipo_sangre']);
    $alergias = trim($_POST['alergias']);

    $upUser = $conn->prepare("
        UPDATE usuarios
        SET nombre = ?, email = ?
        WHERE id = ?
    ");

    $upUser->bind_param(
        'ssi',
        $nombre,
        $email,
        $_SESSION['usuario_id']
    );

    $upPac = $conn->prepare("
        UPDATE pacientes
        SET telefono = ?, direccion = ?, fecha_nacimiento = ?, tipo_sangre = ?, alergias = ?
        WHERE id = ?
    ");

    $upPac->bind_param(
        'sssssi',
        $telefono,
        $direccion,
        $fecha_nacimiento,
        $tipo_sangre,
        $alergias,
        $pid
    );

    if($upUser->execute() && $upPac->execute()){

        $_SESSION['nombre'] = $nombre;

        $success = 'Datos actualizados correctamente';

        $paciente['nombre'] = $nombre;
        $paciente['email'] = $email;
        $paciente['telefono'] = $telefono;
        $paciente['direccion'] = $direccion;
        $paciente['fecha_nacimiento'] = $fecha_nacimiento;
        $paciente['tipo_sangre'] = $tipo_sangre;
        $paciente['alergias'] = $alergias;

        if(!empty($fecha_nacimiento)){
            $nacimiento = new DateTime($fecha_nacimiento);
            $hoy = new DateTime();
            $edad = $hoy->diff($nacimiento)->y;
        }

    }else{
        $error = 'Error al actualizar';
    }
}

if(isset($_POST['password'])){

    $nueva = $_POST['nueva_password'];
    $confirmar = $_POST['confirmar_password'];

    if($nueva !== $confirmar){

        $error = 'Las contraseñas no coinciden';

    }else{

        $passHash = password_hash($nueva, PASSWORD_DEFAULT);

        $updatePass = $conn->prepare("
            UPDATE usuarios
            SET password = ?
            WHERE id = ?
        ");

        $updatePass->bind_param(
            'si',
            $passHash,
            $_SESSION['usuario_id']
        );

        if($updatePass->execute()){
            $success = 'Contraseña actualizada';
        }else{
            $error = 'Error al actualizar contraseña';
        }
    }
}

$totalCitas = $conn->prepare("
    SELECT COUNT(*) c
    FROM citas
    WHERE paciente_id = ?
");

$totalCitas->bind_param('i', $pid);
$totalCitas->execute();

$nCitas = $totalCitas->get_result()->fetch_assoc()['c'];

$proximas = $conn->prepare("
    SELECT COUNT(*) c
    FROM citas
    WHERE paciente_id = ?
    AND fecha >= CURDATE()
");

$proximas->bind_param('i', $pid);
$proximas->execute();

$nProximas = $proximas->get_result()->fetch_assoc()['c'];

$completadas = $conn->prepare("
    SELECT COUNT(*) c
    FROM citas
    WHERE paciente_id = ?
    AND estado = 'completada'
");

$completadas->bind_param('i', $pid);
$completadas->execute();

$nCompletadas = $completadas->get_result()->fetch_assoc()['c'];
?>

<?php include __DIR__ . '/../../includes/header.php'; ?>

<div class="layout">

<?php include __DIR__ . '/../../includes/sidebar.php'; ?>

<div class="main">

<div class="topbar">

    <div class="topbar-left">
        <h1>Configuracion</h1>
        <p>Administra tu cuenta</p>
    </div>

    <div class="topbar-right">
        <div class="topbar-avatar">
            <?= strtoupper(substr($_SESSION['nombre'],0,2)) ?>
        </div>
    </div>

</div>

<div class="content">

<div class="card-grid" style="grid-template-columns:2fr 1fr;align-items:start;">

<div class="card">

<div class="card-header">
    <div>
        <h2>Datos Personales</h2>
        <p>Informacion personal del paciente</p>
    </div>
</div>

<div class="card-body">

<?php if($success): ?>
<div class="alert alert-success">
    <?= $success ?>
</div>
<?php endif; ?>

<?php if($error): ?>
<div class="alert alert-danger">
    <?= $error ?>
</div>
<?php endif; ?>

<form method="POST">

<div class="form-group">
<label>Nombre completo</label>

<input type="text"
       name="nombre"
       class="form-control"
       value="<?= htmlspecialchars($paciente['nombre']) ?>">
</div>

<div class="form-group">
<label>Correo electronico</label>

<input type="email"
       name="email"
       class="form-control"
       value="<?= htmlspecialchars($paciente['email']) ?>">
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">

<div class="form-group">
<label>Telefono</label>

<input type="text"
       name="telefono"
       class="form-control"
       value="<?= htmlspecialchars($paciente['telefono'] ?? '') ?>">
</div>

<div class="form-group">
<label>Direccion</label>

<input type="text"
       name="direccion"
       class="form-control"
       value="<?= htmlspecialchars($paciente['direccion'] ?? '') ?>">
</div>

<div class="form-group">
<label>Fecha de nacimiento</label>

<input type="date"
       name="fecha_nacimiento"
       class="form-control"
       value="<?= htmlspecialchars($paciente['fecha_nacimiento'] ?? '') ?>">
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
       name="tipo_sangre"
       class="form-control"
       value="<?= htmlspecialchars($paciente['tipo_sangre'] ?? '') ?>">
</div>

<div class="form-group">
<label>Alergias</label>

<textarea name="alergias"
          class="form-control"
          rows="3"><?= htmlspecialchars($paciente['alergias'] ?? '') ?></textarea>
</div>

</div>

<button type="submit"
        name="guardar"
        class="btn btn-primary">

Guardar cambios

</button>

</form>

</div>

</div>

</div>

</div>

</div>

</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>