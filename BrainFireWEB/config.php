<?php
// Iniciamos la sesion
session_start();
include 'conexion.php'; // Incluimos la conexion para traer los datos de la tienda

// Validamos si la sesion existe. Si no, lo pateamos al login
if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

$id_usuario = $_SESSION['user_id'];

// Consultamos los datos de personalizacion guardados en la base de datos
$stmt_user = $conn->prepare("SELECT color_nombre, icono_perfil FROM users WHERE id = ?");
$stmt_user->bind_param("i", $id_usuario);
$stmt_user->execute();
$res_user = $stmt_user->get_result();
$user_db = $res_user->fetch_assoc();

$mi_color = $user_db['color_nombre'];
$mi_icono = $user_db['icono_perfil'];

$stmt_user->close();

// Limpiamos la variable de rol para que no importen las mayusculas ni espacios
$rol_limpio = isset($_SESSION['rol']) ? strtolower(trim($_SESSION['rol'])) : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>BRAIN FIRE - Configuracion</title>
    <link rel="stylesheet" href="style-inicio.css">
    <link rel="icon" type="image/png" href="recursos/icono.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    
    <video autoplay muted loop id="bg-video">
        <source src="recursos/fondo3.mp4" type="video/mp4">
    </video>

    <header class="header-index">
        <div class="logo">
            <span class="brain">Brain</span><span class="fire">Fire</span>
        </div>
        <a href="semestres.php" class="help-icon">
        <i class="fa-solid fa-arrow-rotate-left"></i>
        </a>
    </header>
    <br>

<div class="perfil-container">
    <div class="info-actual">
        <p style="text-align: center; font-weight: bold; font-size: 1.17em;">Informacion personal de la cuenta</p>
        <br>
        
        <div class="perfil-foto-seccion" style="text-align: center; font-size: 70px; color: <?php echo $mi_color; ?>;">
            <i class="fa-solid <?php echo $mi_icono; ?>"></i>
        </div>
        <br>
        
        <p>Correo: <strong><?php echo $_SESSION['email']; ?></strong></p>
        <p>Nombre: <strong style="color: <?php echo $mi_color; ?>;"><?php echo $_SESSION['username']; ?></strong></p>
        <p>Contraseña: <strong>**********</strong></p>
        
        <a href="panel_preguntas.php"><button type="button" class="btn-ingresar" style="margin-bottom: 15px; width: fit-content; display: block;">Gestionar Preguntas</button></a>
       
        <?php if ($rol_limpio === 'admin'): ?>
            <a href="gestion_usuarios.php" style="width: 100%;"><button class="btn-ingresar" style="margin-top: 15px; margin-bottom: 5px; width: 100%;">Gestionar Usuarios</button></a>
        <?php endif; ?>

        <?php if ($rol_limpio === 'admin' || $rol_limpio === 'maestro' || $rol_limpio === 'profesor'): ?>
            <a href="moderacion_preguntas.php" style="width: 100%;"><button class="btn-ingresar" style="margin-top: 5px; margin-bottom: 5px; width: 100%; background-color: #8a3592;">Moderar Preguntas</button></a>
        <?php endif; ?>
        
        <?php if ($rol_limpio === 'admin' || $rol_limpio === 'maestro' || $rol_limpio === 'profesor'): ?>
            <a href="importar_quiz.php" style="width: 100%;"><button class="btn-ingresar" style="margin-top: 5px; margin-bottom: 5px; width: 100%; background-color: #4a2450;">Importar desde PDF / IA</button></a>
        <?php endif; ?>
        
        <?php if ($rol_limpio === 'admin' || $rol_limpio === 'maestro' || $rol_limpio === 'profesor'): ?>
            <a href="panel_profesor.php" style="width: 100%;"><button class="btn-ingresar" style="margin-top: 5px; margin-bottom: 15px; width: 100%; background-color: #28a745;">Ver Rendimiento de Alumnos</button></a>
        <?php endif; ?>
        
        <a href="logout.php"><button class="btn-secundario">Cerrar sesion</button></a>
    </div>

    <form class="formu-perfil" action="actualizar.php" method="POST">
        <h3>Modificar informacion</h3>
        <br>
        <label>Nuevo nombre de usuario</label>
        <input type="text" name="nuevo_nombre" placeholder="Introduce tu nuevo nombre...">
        
        <label>Nuevo correo electronico</label>
        <input type="email" name="nuevo_correo" placeholder="Introduce tu nuevo correo...">
        
        <label>Nueva contraseña</label>
        <input type="password" name="nueva_pass" placeholder="Introduce tu nueva contraseña...">
        
        <label>Confirmar nueva contraseña</label>
        <input type="password" name="confirma_pass" placeholder="Confirma tu nueva contraseña...">
        
        <button type="submit" class="btn-ingresar">Guardar cambios</button>
    </form>
</div>

</body>
</html>