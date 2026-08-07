<?php
// config.php - configura tus credenciales de MySQL y ajustes
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'credencial_virtual';

// Base URL de la app (ajusta si tu ruta es diferente)
define('BASE_URL', 'http://localhost/CredencialVirtualWeb');

// Vigencia del token en segundos (por defecto 15 minutos). Cambia a 24*3600 para 24 horas.
define('QR_TOKEN_LIFETIME_SECONDS', 15 * 60);

// Conexión mysqli
$mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($mysqli->connect_errno) {
    error_log("MySQL connection failed: " . $mysqli->connect_error);
    die("Error de conexión a la base de datos.");
}
$mysqli->set_charset("utf8mb4");
?>