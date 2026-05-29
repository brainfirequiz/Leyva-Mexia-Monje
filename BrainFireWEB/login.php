<?php
session_start();
include 'conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $email = $_POST['email'];
    $pass = $_POST['password'];

    // Modificamos la consulta para traer tambien el campo rol de la base de datos
    $stmt = $conn->prepare("SELECT id, username, password_hash, rol FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        
        if (password_verify($pass, $row['password_hash'])) {
            
            // Guardamos los datos de identidad en la sesion
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['email'] = $email;
            $_SESSION['rol'] = $row['rol']; // Aqui el sistema guarda internamente quien eres
            
            header("Location: semestres.php");
            exit();
            
        } else {
            echo "<script>
                    alert('La contrasena es incorrecta.');
                    window.history.back();
                  </script>";
        }
    } else {
        echo "<script>
                alert('No existe ninguna cuenta con este correo.');
                window.history.back();
              </script>";
    }

    $stmt->close();
    $conn->close();
}
?>