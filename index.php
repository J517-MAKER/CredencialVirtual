<?php
session_start();
require_once 'config.php';

// Mensajes para mostrar al usuario
$errors = [];
$success = '';

// Registro
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name  = trim($_POST['last_name'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $password   = $_POST['password'] ?? '';
    $carrera    = trim($_POST['carrera'] ?? '');

    // Validaciones básicas
    if ($first_name === '' || $last_name === '' || $email === '' || $password === '') {
        $errors[] = "Todos los campos obligatorios deben llenarse.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Correo electrónico no válido.";
    } elseif (strlen($password) < 6) {
        $errors[] = "La contraseña debe tener al menos 6 caracteres.";
    }

    if (empty($errors)) {
        // Revisar si el email ya existe
        $stmt = $mysqli->prepare("SELECT id FROM user_register WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors[] = "Ya existe una cuenta con ese correo.";
            $stmt->close();
        } else {
            $stmt->close();
            // Insertar usuario con password hash
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $mysqli->prepare("INSERT INTO user_register (first_name, last_name, email, password_hash, carrera) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param('sssss', $first_name, $last_name, $email, $password_hash, $carrera);
            if ($stmt->execute()) {
                $success = "Registro exitoso. Puedes iniciar sesión ahora.";
            } else {
                $errors[] = "Error al registrar. Intenta de nuevo.";
            }
            $stmt->close();
        }
    }
}

// Inicio de sesión
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $email    = trim($_POST['email_login'] ?? '');
    $password = $_POST['password_login'] ?? '';

    if ($email === '' || $password === '') {
        $errors[] = "Ingresa correo y contraseña.";
    } else {
        $stmt = $mysqli->prepare("SELECT id, first_name, last_name, password_hash FROM user_register WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows === 1) {
            $stmt->bind_result($id, $first_name, $last_name, $password_hash);
            $stmt->fetch();
            if (password_verify($password, $password_hash)) {
                // Login correcto: crear sesión
                $_SESSION['user_id'] = $id;
                $_SESSION['user_name'] = $first_name;
                $_SESSION['user_lastname'] = $last_name;
                // redirigir al dashboard
                header("Location: dashboard.php");
                exit;
            } else {
                $errors[] = "Credenciales incorrectas.";
            }
        } else {
            $errors[] = "Credenciales incorrectas.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Registro / Iniciar sesión</title>
</head>
<body>
  <h1>Registro / Iniciar sesión</h1>

  <?php if (!empty($errors)): ?>
    <div style="color: red;">
      <ul>
        <?php foreach ($errors as $e): ?>
          <li><?php echo htmlspecialchars($e); ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <?php if ($success): ?>
    <div style="color: green;"><?php echo htmlspecialchars($success); ?></div>
  <?php endif; ?>

  <h2>Registro</h2>
  <form method="post" action="index.php">
    <input type="hidden" name="action" value="register">
    <label>Nombre:<br><input type="text" name="first_name" required></label><br>
    <label>Apellidos:<br><input type="text" name="last_name" required></label><br>
    <label>Correo electrónico:<br><input type="email" name="email" required></label><br>
    <label>Contraseña:<br><input type="password" name="password" required></label><br>
    <label>Carrera universitaria:<br><input type="text" name="carrera"></label><br>
    <button type="submit">Registrarse</button>
  </form>

  <hr>

  <h2>Iniciar sesión</h2>
  <form method="post" action="index.php">
    <input type="hidden" name="action" value="login">
    <label>Correo electrónico:<br><input type="email" name="email_login" required></label><br>
    <label>Contraseña:<br><input type="password" name="password_login" required></label><br>
    <button type="submit">Iniciar sesión</button>
  </form>
</body>
</html>