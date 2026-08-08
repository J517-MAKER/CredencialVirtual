<?php
// generar_qr.php - versión con logging y uso compatible con endroid/qr-code v6
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

session_start();
require_once 'config.php';

// Comprueba que exista autoload de Composer antes de las declaraciones use (opcional)
$autoload = __DIR__ . '/vendor/autoload.php';
if (!file_exists($autoload)) {
    // Si falta, mostramos mensaje corto (el resto del script no se ejecutará)
    http_response_code(500);
    echo "Falta vendor/autoload.php. Ejecuta 'composer install'.";
    exit;
}
require_once $autoload;

// Declaraciones 'use' deben estar en el ámbito global (fuera de bloques)
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

// Ruta del log (mismo directorio)
$logFile = __DIR__ . '/qr_error.log';

function logErr($msg) {
    global $logFile;
    $line = date('[Y-m-d H:i:s] ') . $msg . PHP_EOL;
    file_put_contents($logFile, $line, FILE_APPEND);
}

try {
    // Comprueba sesión
    if (!isset($_SESSION['user_id'])) {
        http_response_code(403);
        echo "Acceso no autorizado. Inicia sesión primero.";
        logErr("Sin sesión: petición bloqueada.");
        exit;
    }

    $user_id = (int) $_SESSION['user_id'];

    // Obtener token actual del usuario
    if (!($stmt = $mysqli->prepare("SELECT qr_token, token_created_at FROM user_register WHERE id = ? LIMIT 1"))) {
        throw new Exception("Prepare falló: " . $mysqli->error);
    }
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
        try {
            $new_token = bin2hex(random_bytes(32));
        } catch (Exception $e) {
            throw new Exception("Error generando token: " . $e->getMessage());
        }
        $now = date('Y-m-d H:i:s');
        if (!($upd = $mysqli->prepare("UPDATE user_register SET qr_token = ?, token_created_at = ? WHERE id = ?"))) {
            throw new Exception("Prepare UPDATE falló: " . $mysqli->error);
        }
        $upd->bind_param('ssi', $new_token, $now, $user_id);
        if (!$upd->execute()) {
            $upd->close();
            throw new Exception("Execute UPDATE falló: " . $mysqli->error);
        }
        $upd->close();
        $qr_token = $new_token;
        $token_created_at = $now;
    }

    if (empty($qr_token)) {
        throw new Exception("Token vacío después de proceso.");
    }

    $validation_url = rtrim(BASE_URL, "/") . '/validar_credencial.php?token=' . urlencode($qr_token);

    // Generar QR usando PngWriter y QrCode (API compatible con endroid v6)
    try {
        $qrCode = QrCode::create($validation_url)
            ->setSize(350);

        $writer = new PngWriter();
        $result = $writer->write($qrCode);

        // Obtener PNG en string
        $pngData = $result->getString();
    } catch (Throwable $e) {
        throw new Exception("Error generando QR: " . $e->getMessage());
    }

    // Entregar PNG (descarga si ?download=1)
    $download = isset($_GET['download']) && $_GET['download'] == '1';
    header('Content-Type: image/png');
    if ($download) {
        $filename = 'credencial_' . $qr_token . '.png';
        header('Content-Disposition: attachment; filename="' . $filename . '"');
    } else {
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
    }
    echo $pngData;
    exit;

} catch (Throwable $e) {
    // Log y respuesta legible para depuración (no exponer esto en producción)
    $err = "Excepción: " . $e->getMessage();
    logErr($err . " | TRACE: " . $e->getTraceAsString());
    http_response_code(500);
    echo "Error interno al generar el QR. Revisa el archivo qr_error.log en el directorio del proyecto para detalles.";
    exit;
}
?>