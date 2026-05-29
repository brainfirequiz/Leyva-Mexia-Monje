<?php
session_start();
include 'conexion.php';

// Validar que solo entren maestros o admins
if (!isset($_SESSION['user_id']) || ($_SESSION['rol'] !== 'maestro' && $_SESSION['rol'] !== 'admin')) {
    header("Location: semestres.php");
    exit();
}

$id_profesor = $_SESSION['user_id'];

// 1. PROCESAR FORMULARIO DE ASIGNACION DE MATERIAS
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_materias'])) {
    
    // Borramos las materias anteriores de este profesor para poner las nuevas
    $stmt_del = $conn->prepare("DELETE FROM profesor_materia WHERE id_profesor = ?");
    $stmt_del->bind_param("i", $id_profesor);
    $stmt_del->execute();
    
    if (isset($_POST['materias_seleccionadas']) && is_array($_POST['materias_seleccionadas'])) {
        $stmt_ins = $conn->prepare("INSERT INTO profesor_materia (id_profesor, id_materia) VALUES (?, ?)");
        foreach ($_POST['materias_seleccionadas'] as $id_mat) {
            $id_m = intval($id_mat);
            $stmt_ins->bind_param("ii", $id_profesor, $id_m);
            $stmt_ins->execute();
        }
    }
    
    echo "<script>alert('Tus materias han sido actualizadas correctamente.'); window.location.href='panel_profesor.php';</script>";
    exit();
}

// 2. OBTENER TODAS LAS MATERIAS PARA EL CHECKLIST
$query_todas = "SELECT m.id_materia, m.nombre_materia, s.nombre_semestre 
                FROM materias m 
                JOIN semestres s ON m.id_semestre = s.id_semestre 
                ORDER BY s.id_semestre, m.id_materia";
$res_todas = $conn->query($query_todas);

// 3. OBTENER LAS MATERIAS QUE EL PROFESOR YA TIENE MARCADAS
$mis_materias = [];
$stmt_mis = $conn->prepare("SELECT id_materia FROM profesor_materia WHERE id_profesor = ?");
$stmt_mis->bind_param("i", $id_profesor);
$stmt_mis->execute();
$res_mis = $stmt_mis->get_result();
while ($row = $res_mis->fetch_assoc()) {
    $mis_materias[] = $row['id_materia'];
}
$stmt_mis->close();

// 4. OBTENER EL PROGRESO DE LOS ALUMNOS (Calculando el maximo real de la materia)
$query_alumnos = "SELECT 
    u.username,
    m.nombre_materia,
    (SELECT COUNT(p2.id_pregunta) * 125 
     FROM quices q2 
     JOIN preguntas p2 ON q2.id_quiz = p2.id_quiz 
     WHERE q2.id_materia = m.id_materia AND p2.estado = 'aprobada') AS max_puntos,
    COALESCE(SUM(uqs.best_score), 0) AS puntos_alumno
FROM profesor_materia pm
JOIN materias m ON pm.id_materia = m.id_materia
JOIN quices q ON m.id_materia = q.id_materia
JOIN preguntas p ON q.id_quiz = p.id_quiz AND p.estado = 'aprobada'
JOIN user_question_scores uqs ON p.id_pregunta = uqs.id_pregunta
JOIN users u ON uqs.id_usuario = u.id
WHERE pm.id_profesor = ?
GROUP BY u.id, u.username, m.id_materia, m.nombre_materia
ORDER BY m.nombre_materia, puntos_alumno DESC";

$stmt_alumnos = $conn->prepare($query_alumnos);
$stmt_alumnos->bind_param("i", $id_profesor);
$stmt_alumnos->execute();
$res_alumnos = $stmt_alumnos->get_result();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>BRAIN FIRE - Panel de Profesor</title>
    <link rel="stylesheet" href="style-inicio.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .panel-container { max-width: 1000px; margin: 40px auto; padding: 30px; background: white; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .seccion-panel { margin-bottom: 40px; }
        .titulo-seccion { color: #612766; border-bottom: 2px solid #612766; padding-bottom: 10px; margin-bottom: 20px; font-size: 24px; }
        .checklist-materias { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 15px; }
        .item-materia { background: #fdfdfd; border: 1px solid #ccc; padding: 15px; border-radius: 8px; display: flex; align-items: center; gap: 10px; cursor: pointer; transition: 0.2s; }
        .item-materia:hover { background: #fdf6fd; border-color: #8a3592; }
        input[type="checkbox"] { width: 20px; height: 20px; accent-color: #8a3592; cursor: pointer; }
        
        .tabla-alumnos { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .tabla-alumnos th, .tabla-alumnos td { padding: 15px; text-align: left; border-bottom: 1px solid #ddd; }
        .tabla-alumnos th { background-color: #fdf6fd; color: #612766; font-size: 16px; }
        .btn-guardar { background: #28a745; color: white; border: none; padding: 15px; font-size: 18px; border-radius: 8px; cursor: pointer; margin-top: 20px; font-weight: bold; width: 100%; transition: 0.2s; }
        .btn-guardar:hover { background: #218838; }
        
        .progreso-contenedor { width: 100%; background-color: #e9ecef; border-radius: 6px; overflow: hidden; height: 12px; margin-top: 5px; }
        .progreso-barra { height: 100%; transition: width 0.8s ease; }
    </style>
</head>
<body>
    <header class="header-index">
        <div class="logo">
            <span class="brain">Brain</span><span class="fire">Fire</span>
        </div>
        <a href="semestres.php" class="help-icon" style="color: white; text-decoration: none;">
            <i class="fa-solid fa-arrow-left"></i> Volver
        </a>
    </header>

    <main class="panel-container">
        <h1 style="text-align: center; color: #4a2450; margin-bottom: 40px;">Panel de Control Academico</h1>

        <div class="seccion-panel">
            <h2 class="titulo-seccion">Mis Materias Impartidas</h2>
            <p style="color: #666; margin-bottom: 20px; font-size: 16px;">Selecciona del catalogo las materias que impartes. Cualquier materia nueva que crees con IA aparecera aqui automaticamente.</p>
            
            <form action="panel_profesor.php" method="POST">
                <div class="checklist-materias">
                    <?php while ($m = $res_todas->fetch_assoc()): ?>
                        <?php $checked = in_array($m['id_materia'], $mis_materias) ? 'checked' : ''; ?>
                        <label class="item-materia">
                            <input type="checkbox" name="materias_seleccionadas[]" value="<?php echo $m['id_materia']; ?>" <?php echo $checked; ?>>
                            <div>
                                <strong style="color: #333;"><?php echo htmlspecialchars($m['nombre_materia']); ?></strong><br>
                                <small style="color: #888;"><?php echo htmlspecialchars($m['nombre_semestre']); ?></small>
                            </div>
                        </label>
                    <?php endwhile; ?>
                </div>
                <button type="submit" name="guardar_materias" class="btn-guardar">Guardar y Actualizar mis materias</button>
            </form>
        </div>

        <div class="seccion-panel">
            <h2 class="titulo-seccion">Rendimiento de los Estudiantes</h2>
            <?php if ($res_alumnos->num_rows > 0): ?>
                <table class="tabla-alumnos">
                    <thead>
                        <tr>
                            <th>Estudiante</th>
                            <th>Materia</th>
                            <th>Puntaje Obtenido</th>
                            <th>Progreso Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($alumno = $res_alumnos->fetch_assoc()): ?>
                            <?php 
                                $max = $alumno['max_puntos'];
                                $user_pts = $alumno['puntos_alumno'];
                                $pct = ($max > 0) ? round(($user_pts / $max) * 100) : 0;
                                $color = ($pct == 100) ? '#8a3592' : '#28a745';
                            ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($alumno['username']); ?></strong></td>
                                <td><?php echo htmlspecialchars($alumno['nombre_materia']); ?></td>
                                <td><?php echo $user_pts; ?> / <?php echo $max; ?> pts</td>
                                <td style="width: 30%;">
                                    <div style="font-size: 14px; font-weight: bold; color: <?php echo $color; ?>;"><?php echo $pct; ?>%</div>
                                    <div class="progreso-contenedor">
                                        <div class="progreso-barra" style="width: <?php echo $pct; ?>%; background-color: <?php echo $color; ?>;"></div>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="color: #666; text-align: center; padding: 25px; background: #fdf6fd; border-radius: 8px; border: 1px dashed #8a3592;">Aun no hay estudiantes con progreso registrado en las materias que seleccionaste, o no has seleccionado ninguna materia.</p>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>