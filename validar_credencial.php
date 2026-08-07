<?php
require_once 'config.php';

$token = trim($_GET['token'] ?? '');

if ($token === '') {
    http_response_code(400);
    echo "Token no proporcionado.";
    exit;
}

// Opcional: validar formato (hex 64)
if (!preg_match('/^[0-9a-f]{64}$/i', $token)) {
    http_response_code(400);
    echo "Token inválido.";
    exit;
}

$stmt = $mysqli->prepare("SELECT first_name, last_name, carrera, token_created_at FROM user_register WHERE qr_token = ? LIMIT 1");
$stmt->bind_param('s', $token);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows !== 1) {
    $stmt->close();
    // Token no encontrado
    $valid = false;
    $message = "Credencial no válida (token no encontrado).";
} else {
    $stmt->bind_result($first_name, $last_name, $carrera, $token_created_at);
    $stmt->fetch();
    $stmt->close();

    $valid = false;
    if (!empty($token_created_at)) {
        $ts = strtotime($token_created_at);
        if ($ts !== false && ($ts + QR_TOKEN_LIFETIME_SECONDS) > time()) {
            $valid = true;
        }
    }

    if ($valid) {
        $message = "Credencial válida para " . htmlspecialchars($first_name . ' ' . $last_name) . " - " . htmlspecialchars($carrera);
    } else {
        $message = "Credencial expirada o inválida.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Validación de credencial</title>
</head>
<body>
  <h1>Validación de credencial</h1>
  <p><?php echo htmlspecialchars($message); ?></p>
</body>
</html>