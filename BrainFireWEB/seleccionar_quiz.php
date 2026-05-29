<?php
session_start();
include 'conexion.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: inicio.html");
    exit();
}

$id_usuario = $_SESSION['user_id'];
$id_materia = isset($_GET['materia']) ? intval($_GET['materia']) : 0;

// Obtener nombre de la materia
$stmt_mat = $conn->prepare("SELECT nombre_materia FROM materias WHERE id_materia = ?");
$stmt_mat->bind_param("i", $id_materia);
$stmt_mat->execute();
$res_mat = $stmt_mat->get_result();
if ($res_mat->num_rows === 0) {
    header("Location: semestres.php");
    exit();
}
$materia_data = $res_mat->fetch_assoc();
$nombre_materia = $materia_data['nombre_materia'];
$stmt_mat->close();

// Consultar todos los quices de esta materia con sus preguntas y el puntaje acumulado del usuario
$query = "SELECT 
    q.id_quiz,
    q.nombre_quiz,
    q.id_quiz_previo,
    q.porcentaje_requerido,
    COUNT(p.id_pregunta) * 125 AS max_puntos,
    COALESCE(SUM(uqs.best_score), 0) AS puntos_usuario
FROM quices q
LEFT JOIN preguntas p ON q.id_quiz = p.id_quiz AND p.estado = 'aprobada'
LEFT JOIN user_question_scores uqs ON p.id_pregunta = uqs.id_pregunta AND uqs.id_usuario = ?
WHERE q.id_materia = ?
GROUP BY q.id_quiz";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $id_usuario, $id_materia);
$stmt->execute();
$result = $stmt->get_result();

$quices = [];
while ($row = $result->fetch_assoc()) {
    $quices[$row['id_quiz']] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>BrainFire - Seleccionar Quiz</title>
    <link rel="stylesheet" href="style-inicio.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .quiz-list-container { max-width: 700px; margin: 40px auto; padding: 20px; }
        .quiz-card { background: white; padding: 20px; border-radius: 12px; margin-bottom: 15px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center; border-left: 6px solid #8a3592; }
        .quiz-card.bloqueado { border-left-color: #6c757d; opacity: 0.7; }
        .btn-entrar { background: #8a3592; color: white; padding: 10px 20px; text-decoration: none; border-radius: 6px; font-weight: bold; }
        .btn-bloqueado { background: #6c757d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 6px; font-weight: bold; cursor: not-allowed; }
    </style>
</head>
<body>
    <header class="header-index">
        <div class="logo"><span class="brain">Brain</span><span class="fire">Fire</span></div>
        <a href="semestres.php" class="help-icon"><i class="fa-solid fa-arrow-left"></i> Volver</a>
    </header>

    <main class="quiz-list-container">
        <h2 style="text-align: center; color: #4a2450; margin-bottom: 30px;">Cuestionarios de <?php echo htmlspecialchars($nombre_materia); ?></h2>

        <?php 
        foreach ($quices as $quiz): 
            $max = $quiz['max_puntos'];
            $puntos = $quiz['puntos_usuario'];
            $porcentaje_actual = ($max > 0) ? round(($puntos / $max) * 100) : 0;
            
            $bloqueado = false;
            $motivo = "";

            // Validacion de Prerrequisito
            if (!empty($quiz['id_quiz_previo']) && isset($quices[$quiz['id_quiz_previo']])) {
                $quiz_previo = $quices[$quiz['id_quiz_previo']];
                $max_previo = $quiz_previo['max_puntos'];
                $puntos_previo = $quiz_previo['puntos_usuario'];
                $porcentaje_previo = ($max_previo > 0) ? round(($puntos_previo / $max_previo) * 100) : 0;

                if ($porcentaje_previo < $quiz['porcentaje_requerido']) {
                    $bloqueado = true;
                    $motivo = "Requiere " . $quiz['porcentaje_requerido'] . "% en " . $quiz_previo['nombre_quiz'] . " (Llevas " . $porcentaje_previo . "%)";
                }
            }
        ?>
            <div class="quiz-card <?php echo $bloqueado ? 'bloqueado' : ''; ?>">
                <div>
                    <h3 style="margin: 0; color: #333;"><?php echo htmlspecialchars($quiz['nombre_quiz']); ?></h3>
                    <?php if ($bloqueado): ?>
                        <p style="margin: 5px 0 0 0; color: #dc3545; font-size: 14px; font-weight: bold;"><i class="fa-solid fa-lock"></i> <?php echo $motivo; ?></p>
                    <?php else: ?>
                        <p style="margin: 5px 0 0 0; color: #28a745; font-size: 14px; font-weight: bold;"><i class="fa-solid fa-unlock"></i> Disponible (Progreso: <?php echo $porcentaje_actual; ?>%)</p>
                    <?php endif; ?>
                </div>

                <div>
                    <?php if ($bloqueado): ?>
                        <button class="btn-bloqueado" disabled>Bloqueado</button>
                    <?php else: ?>
                        <a href="quiz.php?quiz=<?php echo $quiz['id_quiz']; ?>" class="btn-entrar">Responder</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </main>
</body>
</html>