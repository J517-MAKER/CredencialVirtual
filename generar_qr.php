<?php
session_start();
require_once 'config.php';

// Requiere composer autoload (endroid/qr-code)
require_once __DIR__ . '/vendor/autoload.php';

use Endroid\QrCode\Builder\Builder;

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo "Acceso no autorizado.";
    exit;
}

$user_id = (int) $_SESSION['user_id'];

// Obtener token actual del usuario
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

$need_new = true;
if (!empty($qr_token) && !empty($token_created_at)) {
    $ts = strtotime($token_created_at);
    if ($ts !== false && ($ts + QR_TOKEN_LIFETIME_SECONDS) > time()) {
        $need_new = false;
    }
}

if ($need_new) {
    // Generar nuevo token seguro
    try {
        $new_token = bin2hex(random_bytes(32)); // 64 hex chars
    } catch (Exception $e) {
        http_response_code(500);
        echo "Error generando token.";
        exit;
    }
    $now = date('Y-m-d H:i:s');
    $upd = $mysqli->prepare("UPDATE user_register SET qr_token = ?, token_created_at = ? WHERE id = ?");
    $upd->bind_param('ssi', $new_token, $now, $user_id);
    if (!$upd->execute()) {
        http_response_code(500);
        echo "Error al guardar token.";
        exit;
    }
    $upd->close();
    $qr_token = $new_token;
    $token_created_at = $now;
}

// Construir la URL de validación dentro del QR
$validation_url = rtrim(BASE_URL, "/") . '/validar_credencial.php?token=' . urlencode($qr_token);

// Generar QR con endroid/qr-code
$result = Builder::create()
    ->data($validation_url)
    ->size(350)
    ->margin(10)
    ->build();

$pngData = $result->getString();

// Entregar PNG (descarga si ?download=1)
$download = isset($_GET['download']) && $_GET['download'] == '1';
header('Content-Type: image/png');
if ($download) {
    $filename = 'credencial_' . $qr_token . '.png';
    header('Content-Disposition: attachment; filename="' . $filename . '"');
} else {
    // evitar cache para ver siempre vigencia actual
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
}

echo $pngData;
exit;
?>