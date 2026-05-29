<?php
session_start();
include 'conexion.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $id = $_SESSION['user_id'];
    $nuevo_nombre = $_POST['nuevo_nombre'];
    $nuevo_correo = $_POST['nuevo_correo'];
    $nueva_pass = $_POST['nueva_pass'];
    $confirma_pass = $_POST['confirma_pass'];

    $actualizado = false;

    // 1. Logica para actualizar el nombre
    if (!empty($nuevo_nombre)) {
        $stmt = $conn->prepare("UPDATE users SET username = ? WHERE id = ?");
        $stmt->bind_param("si", $nuevo_nombre, $id);
        if ($stmt->execute()) {
            $_SESSION['username'] = $nuevo_nombre;
            $actualizado = true;
        }
        $stmt->close();
    }

    // 2. Logica para actualizar el correo
    if (!empty($nuevo_correo)) {
        $stmt = $conn->prepare("UPDATE users SET email = ? WHERE id = ?");
        $stmt->bind_param("si", $nuevo_correo, $id);
        if ($stmt->execute()) {
            $_SESSION['email'] = $nuevo_correo;
            $actualizado = true;
        }
        $stmt->close();
    }

    // 3. Logica para actualizar la contrasena
    if (!empty($nueva_pass) && !empty($confirma_pass)) {
        if ($nueva_pass === $confirma_pass) {
            $pass_hash = password_hash($nueva_pass, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $stmt->bind_param("si", $pass_hash, $id);
            if ($stmt->execute()) {
                $actualizado = true;
            }
            $stmt->close();
        } else {
            echo "<script>
                    alert('Las contrasenas no coinciden. Intenta de nuevo.');
                    window.history.back();
                  </script>";
            exit();
        }
    }

    // 4. Mensaje final
    if ($actualizado) {
        echo "<script>
                alert('Informacion actualizada correctamente.');
                window.location.href = 'config.php';
              </script>";
    } else {
        echo "<script>
                alert('No se realizo ningun cambio.');
                window.location.href = 'config.php';
              </script>";
    }

    $conn->close();
}
?>