<?php
session_start();
include 'conexion.php';

// Validar que haya sesion activa
if (!isset($_SESSION['user_id'])) {
    header("Location: inicio.html");
    exit();
}

$id_usuario = $_SESSION['user_id'];

// Recibir el ID del semestre por la URL (ej. materias.php?id=1)
$id_semestre = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Consultar el nombre del semestre para mostrarlo en el titulo
$stmt_semestre = $conn->prepare("SELECT nombre_semestre FROM semestres WHERE id_semestre = ?");
$stmt_semestre->bind_param("i", $id_semestre);
$stmt_semestre->execute();
$result_semestre = $stmt_semestre->get_result();

if ($result_semestre->num_rows === 0) {
    header("Location: semestres.php");
    exit();
}

$row_semestre = $result_semestre->fetch_assoc();
$nombre_semestre = $row_semestre['nombre_semestre'];
$stmt_semestre->close();

// Consultar las materias de este semestre JUNTO con el calculo de progreso del usuario
$query_materias = "SELECT 
    m.id_materia, 
    m.nombre_materia,
    COUNT(p.id_pregunta) * 125 AS max_puntos,
    COALESCE(SUM(uqs.best_score), 0) AS puntos_usuario
FROM materias m
LEFT JOIN quices q ON m.id_materia = q.id_materia
LEFT JOIN preguntas p ON q.id_quiz = p.id_quiz AND p.estado = 'aprobada'
LEFT JOIN user_question_scores uqs ON p.id_pregunta = uqs.id_pregunta AND uqs.id_usuario = ?
WHERE m.id_semestre = ?
GROUP BY m.id_materia, m.nombre_materia";

$stmt_materias = $conn->prepare($query_materias);
$stmt_materias->bind_param("ii", $id_usuario, $id_semestre);
$stmt_materias->execute();
$result_materias = $stmt_materias->get_result();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>BRAIN FIRE - Materias</title>
    <link rel="stylesheet" href="style-inicio.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <header class="header-index">
        <div class="logo">
            <span class="brain">Brain</span><span class="fire">Fire</span>
        </div>
        <a href="semestres.php" class="help-icon">
            <i class="fa-solid fa-arrow-rotate-left"></i>
        </a>
    </header>

    <main class="semester-container" style="display: flex; flex-direction: column; align-items: center;">
        <h2 style="text-align: center; color: #4a2450; font-size: 32px; margin-bottom: 20px;">
            Materias del <?php echo $nombre_semestre; ?>
        </h2>

        <?php if ($result_materias->num_rows > 0): ?>
            <?php while($materia = $result_materias->fetch_assoc()): ?>
                
                <?php 
                    // Calculo de porcentajes y colores para la barra
                    $max_mat = $materia['max_puntos'];
                    $puntos_mat = $materia['puntos_usuario'];
                    $porcentaje_mat = ($max_mat > 0) ? round(($puntos_mat / $max_mat) * 100) : 0;
                    $color_barra = ($porcentaje_mat == 100) ? '#8a3592' : '#28a745';
                ?>

                <a href="seleccionar_quiz.php?materia=<?php echo $materia['id_materia']; ?>" style="text-decoration: none; color: inherit; width: 100%; max-width: 600px; display: block;">
                    <div class="semester-card" style="padding: 25px; text-align: center; margin-bottom: 15px; border-left: 8px solid #8a3592; background: white;">
                        <h3 style="margin: 0; font-size: 24px; color: #333;"><?php echo htmlspecialchars($materia['nombre_materia']); ?></h3>
                        
                        <div style="width: 100%; background-color: #e9ecef; border-radius: 6px; overflow: hidden; margin-top: 15px; height: 12px; text-align: left;">
                            <div style="height: 100%; width: <?php echo $porcentaje_mat; ?>%; background-color: <?php echo $color_barra; ?>; transition: width 0.8s ease;"></div>
                        </div>
                        <div style="font-size: 14px; color: #666; margin-top: 8px; display: flex; justify-content: space-between; font-weight: bold;">
                            <span><?php echo $puntos_mat; ?> / <?php echo $max_mat; ?> pts</span>
                            <span style="color: <?php echo $color_barra; ?>;"><?php echo $porcentaje_mat; ?>%</span>
                        </div>

                        <p style="margin-top: 15px; color: #612766; font-weight: bold; background: #fdf6fd; padding: 10px; border-radius: 5px;">Jugar 10 preguntas de esta materia</p>
                    </div>
                </a>

            <?php endwhile; ?>
        <?php else: ?>
            <p style="text-align: center; color: #555; font-size: 18px;">Aun no hay materias registradas para este semestre en la base de datos.</p>
        <?php endif; ?>
    </main>
</body>
</html>
<?php
$stmt_materias->close();
$conn->close();
?>