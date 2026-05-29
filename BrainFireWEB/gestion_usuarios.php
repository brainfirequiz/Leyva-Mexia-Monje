<?php
session_start();
include 'conexion.php';

// Validar de forma estricta que haya sesion activa y sea administrador
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: semestres.php");
    exit();
}

// Procesar la actualizacion de rol mediante POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_rol'])) {
    $id_usuario = intval($_POST['id_usuario']);
    $nuevo_rol = $_POST['nuevo_rol'];

    $roles_validos = ['admin', 'maestro', 'certificado', 'normal'];
    if (in_array($nuevo_rol, $roles_validos)) {
        $stmt_update = $conn->prepare("UPDATE users SET rol = ? WHERE id = ?");
        $stmt_update->bind_param("si", $nuevo_rol, $id_usuario);
        $stmt_update->execute();
        $stmt_update->close();
        
        // Si te cambias el rol a ti mismo, actualiza de inmediato tu sesion actual
        if ($id_usuario === $_SESSION['user_id']) {
            $_SESSION['rol'] = $nuevo_rol;
        }

        echo "<script>alert('Rol actualizado con exito.'); window.location.href='gestion_usuarios.php';</script>";
        exit();
    }
}

// Consultar el listado completo de usuarios registrados
$query_users = "SELECT id, username, email, rol FROM users ORDER BY id DESC";
$result_users = $conn->query($query_users);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>BRAIN FIRE - Gestion de Usuarios</title>
    <link rel="stylesheet" href="style-inicio.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .admin-container { max-width: 1000px; margin: 40px auto; background: white; padding: 40px; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .tabla-usuarios { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 16px; }
        .tabla-usuarios th, .tabla-usuarios td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        .tabla-usuarios th { background-color: #612766; color: white; font-weight: bold; }
        .tabla-usuarios tr:nth-child(even) { background-color: #f9f9f9; }
        .select-rol { padding: 6px 10px; border-radius: 5px; border: 1px solid #ccc; font-family: inherit; font-size: 14px; }
        .btn-guardar-rol { background-color: #8a3592; color: white; border: none; padding: 7px 14px; border-radius: 5px; cursor: pointer; font-weight: bold; transition: 0.2s; }
        .btn-guardar-rol:hover { background-color: #65276b; }
    </style>
</head>
<body>
    <header class="header-index">
        <div class="logo">
            <span class="brain">Brain</span><span class="fire">Fire</span>
        </div>
        <a href="config.php" class="help-icon">
            <i class="fa-solid fa-arrow-rotate-left"></i>
        </a>
    </header>

    <main class="admin-container">
        <h2 style="text-align: center; color: #612766; margin-bottom: 20px;">Control de Roles Academicos</h2>
        
        <table class="tabla-usuarios">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre de Usuario</th>
                    <th>Correo Electronico</th>
                    <th>Rol Actual</th>
                    <th>Asignar Nuevo Rol</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result_users->num_rows > 0): ?>
                    <?php while($user = $result_users->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td style="font-weight: bold; color: #8a3592; text-transform: uppercase;"><?php echo $user['rol']; ?></td>
                            <td>
                                <form action="gestion_usuarios.php" method="POST" style="background: transparent; border: none; box-shadow: none; padding: 0; margin: 0; width: auto; display: flex; gap: 10px; align-items: center;">
                                    <input type="hidden" name="id_usuario" value="<?php echo $user['id']; ?>">
                                    <select name="nuevo_rol" class="select-rol">
                                        <option value="normal" <?php echo ($user['rol'] === 'normal') ? 'selected' : ''; ?>>Normal (Alumno)</option>
                                        <option value="certificado" <?php echo ($user['rol'] === 'certificado') ? 'selected' : ''; ?>>Usuario Certificado</option>
                                        <option value="maestro" <?php echo ($user['rol'] === 'maestro') ? 'selected' : ''; ?>>Maestro</option>
                                        <option value="admin" <?php echo ($user['rol'] === 'admin') ? 'selected' : ''; ?>>Administrador</option>
                                    </select>
                                    <button type="submit" name="cambiar_rol" class="btn-guardar-rol">Actualizar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5" style="text-align:center;">No hay usuarios registrados en el sistema.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </main>
</body>
</html>
<?php
$conn->close();
?>