<?php
include 'conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $user = $_POST['username'];
    $email = $_POST['email'];
    $pass = $_POST['password'];
    $confirm_pass = $_POST['confirm_password'];

    // 1. Validar que no manden campos vacios
    if (empty($user) || empty($email) || empty($pass) || empty($confirm_pass)) {
        echo "<script>
                alert('Por favor, llena todos los campos.');
                window.history.back();
              </script>";
        exit();
    }

    // 2. Validar que las contrasenas coincidan
    if ($pass !== $confirm_pass) {
        echo "<script>
                alert('Las contrasenas no coinciden. Intenta de nuevo.');
                window.history.back();
              </script>";
        exit();
    }

    // 3. Encriptamos la contrasena
    $pass_hash = password_hash($pass, PASSWORD_BCRYPT);

    $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $user, $email, $pass_hash);

    if ($stmt->execute()) {
        echo "<script>
                alert('Registro exitoso. Ahora puedes iniciar sesion en Brain Fire.');
                window.location.href = 'inicio.html';
              </script>";
    } else {
        echo "<script>
                alert('Hubo un error al registrar. Es posible que el correo ya exista.');
                window.history.back();
              </script>";
    }

    $stmt->close();
    $conn->close();
}
?>