<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Dashboard</title>
</head>
<body>
  <h1>Bienvenido, <?php echo htmlspecialchars($_SESSION['user_name'] . ' ' . $_SESSION['user_lastname']); ?></h1>
  <p>Aquí irá la lógica para generar el QR y permitir descargarlo (próximo paso).</p>
  <p><a href="logout.php">Cerrar sesión</a></p>
</body>
</html>