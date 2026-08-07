<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];

// Obtener token y fecha para mostrar estado
$stmt = $mysqli->prepare("SELECT qr_token, token_created_at FROM user_register WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->store_result();
$qr_token = null;
$token_created_at = null;
if ($stmt->num_rows === 1) {
    $stmt->bind_result($qr_token, $token_created_at);
    $stmt->fetch();
}
$stmt->close();

$is_valid = false;
$expires_at = null;
if (!empty($qr_token) && !empty($token_created_at)) {
    $ts = strtotime($token_created_at);
    if ($ts !== false) {
        $expires_at_ts = $ts + QR_TOKEN_LIFETIME_SECONDS;
        $is_valid = ($expires_at_ts > time());
        $expires_at = date('Y-m-d H:i:s', $expires_at_ts);
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Dashboard - Credencial Virtual</title>
</head>
<body>
  <h1>Bienvenido, <?php echo htmlspecialchars($_SESSION['user_name'] . ' ' . $_SESSION['user_lastname']); ?></h1>

  <h2>Tu credencial QR</h2>

  <p>
    <?php if ($qr_token): ?>
      Estado: <strong><?php echo $is_valid ? 'Válido' : 'Expirado / generar nuevo'; ?></strong><br>
      <?php if ($expires_at): ?>
        Vigencia hasta: <strong><?php echo htmlspecialchars($expires_at); ?></strong><br>
      <?php endif; ?>
      <div style="margin-top: 10px;">
        <!-- Imagen embebida: esto llamará generar_qr.php y regenerará el token si expiró -->
        <img src="generar_qr.php" alt="QR de la credencial" style="max-width:350px;">
      </div>
      <p>
        <a href="generar_qr.php?download=1">Descargar QR (PNG)</a>
      </p>
    <?php else: ?>
      <p>No tienes un token QR todavía. La imagen se generará cuando cargues esta página.</p>
      <div>
        <img src="generar_qr.php" alt="Generar QR">
      </div>
      <p><a href="generar_qr.php?download=1">Descargar QR (PNG)</a></p>
    <?php endif; ?>
  </p>

  <p><a href="logout.php">Cerrar sesión</a></p>
</body>
</html>