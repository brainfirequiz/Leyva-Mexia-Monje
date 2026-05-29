<?php
session_start();
include 'conexion.php';

// Validar que solo entren Admin o Maestro
if (!isset($_SESSION['user_id']) || ($_SESSION['rol'] !== 'admin' && $_SESSION['rol'] !== 'maestro')) {
    header("Location: semestres.php");
    exit();
}

// Procesar Acciones (Aprobar o Rechazar)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_pregunta = intval($_POST['id_pregunta']);
    
    if (isset($_POST['aprobar'])) {
        $stmt = $conn->prepare("UPDATE preguntas SET estado = 'aprobada' WHERE id_pregunta = ?");
        $stmt->bind_param("i", $id_pregunta);
        $stmt->execute();
        $stmt->close();
        echo "<script>alert('Pregunta aprobada e integrada al quiz.'); window.location.href='moderacion_preguntas.php';</script>";
    } 
    
    if (isset($_POST['rechazar'])) {
        $stmt = $conn->prepare("DELETE FROM preguntas WHERE id_pregunta = ?");
        $stmt->bind_param("i", $id_pregunta);
        $stmt->execute();
        $stmt->close();
        echo "<script>alert('Pregunta rechazada y eliminada.'); window.location.href='moderacion_preguntas.php';</script>";
    }
    exit();
}

// Consultar preguntas pendientes junto con su materia
$query = "SELECT p.id_pregunta, p.texto_pregunta, m.nombre_materia 
          FROM preguntas p 
          JOIN quices q ON p.id_quiz = q.id_quiz 
          JOIN materias m ON q.id_materia = m.id_materia 
          WHERE p.estado = 'pendiente'";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>BRAIN FIRE - Moderacion</title>
    <link rel="stylesheet" href="style-inicio.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .mod-container { max-width: 900px; margin: 40px auto; background: white; padding: 40px; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .card-pendiente { border: 2px solid #612766; padding: 20px; border-radius: 10px; margin-bottom: 20px; background-color: #fff; }
        .materia-tag { background: #8a3592; color: white; padding: 4px 10px; border-radius: 5px; font-size: 12px; font-weight: bold; text-transform: uppercase; display: inline-block; margin-bottom: 10px;}
        .btn-mod { padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 15px;}
        .btn-aprobar { background-color: #28a745; color: white; margin-right: 10px; }
        .btn-rechazar { background-color: #dc3545; color: white; }
        
        /* Estilos nuevos para mostrar las opciones */
        .lista-opciones { list-style-type: none; padding: 0; margin: 15px 0; background: #f9f9f9; padding: 15px; border-radius: 8px; border: 1px solid #eee; }
        .lista-opciones li { margin-bottom: 8px; font-size: 16px; }
        .opcion-correcta { color: #155724; font-weight: bold; }
        .opcion-incorrecta { color: #666; }
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

    <main class="mod-container">
        <h2 style="text-align: center; color: #612766; margin-bottom: 30px;">Preguntas Pendientes de Validar</h2>

        <?php if ($result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <div class="card-pendiente">
                    <span class="materia-tag"><?php echo htmlspecialchars($row['nombre_materia']); ?></span>
                    <p style="font-size: 18px; font-weight: bold; color: #333; margin-bottom: 5px;"><?php echo htmlspecialchars($row['texto_pregunta']); ?></p>
                    
                    <?php
                    $id_preg = $row['id_pregunta'];
                    $stmt_op = $conn->prepare("SELECT texto_opcion, es_correcta FROM opciones WHERE id_pregunta = ?");
                    $stmt_op->bind_param("i", $id_preg);
                    $stmt_op->execute();
                    $res_op = $stmt_op->get_result();
                    ?>
                    <ul class="lista-opciones">
                        <?php while($op = $res_op->fetch_assoc()): ?>
                            <?php if($op['es_correcta'] == 1): ?>
                                <li class="opcion-correcta">
                                    <i class="fa-solid fa-check" style="margin-right: 8px;"></i> 
                                    <?php echo htmlspecialchars($op['texto_opcion']); ?> (Respuesta Correcta)
                                </li>
                            <?php else: ?>
                                <li class="opcion-incorrecta">
                                    <i class="fa-solid fa-xmark" style="margin-right: 8px; color: #dc3545;"></i> 
                                    <?php echo htmlspecialchars($op['texto_opcion']); ?>
                                </li>
                            <?php endif; ?>
                        <?php endwhile; ?>
                    </ul>
                    <?php $stmt_op->close(); ?>

                    <form action="moderacion_preguntas.php" method="POST" style="background:transparent; border:none; box-shadow:none; padding:0; margin:0; width:auto; display:inline-block;">
                        <input type="hidden" name="id_pregunta" value="<?php echo $row['id_pregunta']; ?>">
                        <button type="submit" name="aprobar" class="btn-mod btn-aprobar">Aprobar</button>
                        <button type="submit" name="rechazar" class="btn-mod btn-rechazar">Rechazar</button>
                    </form>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p style="text-align: center; color: #666; font-size: 18px;">No hay preguntas esperando aprobacion en este momento.</p>
        <?php endif; ?>
    </main>
</body>
</html>