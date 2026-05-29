<?php
session_start();
include 'conexion.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: inicio.html");
    exit();
}

$rol_actual = isset($_SESSION['rol']) ? strtolower(trim($_SESSION['rol'])) : '';
$es_profesor = ($rol_actual === 'admin' || $rol_actual === 'maestro' || $rol_actual === 'profesor');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_materia = intval($_POST['materia']);
    $texto_pregunta = $_POST['pregunta'];
    $opciones = $_POST['opciones'];
    $correcta_index = intval($_POST['correcta']);

    $estado = (!$es_profesor) ? 'pendiente' : 'aprobada';
    $accion_quiz = isset($_POST['accion_quiz']) ? $_POST['accion_quiz'] : 'existente';

    // LOGICA DE PROFESORES: Crear nuevo quiz
    if ($es_profesor && $accion_quiz === 'nuevo') {
        $nombre_quiz = trim($_POST['nombre_quiz']);
        $id_previo = intval($_POST['id_quiz_previo']);
        $porcentaje = intval($_POST['porcentaje_requerido']);

        if ($id_previo === 0) {
            $stmt_nq = $conn->prepare("INSERT INTO quices (id_materia, nombre_quiz, id_quiz_previo, porcentaje_requerido) VALUES (?, ?, NULL, ?)");
            $stmt_nq->bind_param("isi", $id_materia, $nombre_quiz, $porcentaje);
        } else {
            $stmt_nq = $conn->prepare("INSERT INTO quices (id_materia, nombre_quiz, id_quiz_previo, porcentaje_requerido) VALUES (?, ?, ?, ?)");
            $stmt_nq->bind_param("isii", $id_materia, $nombre_quiz, $id_previo, $porcentaje);
        }
        $stmt_nq->execute();
        $id_quiz = $stmt_nq->insert_id;
        $stmt_nq->close();
        
    } else {
        // LOGICA ALUMNOS O EXISTENTE: Buscar el quiz actual o crearlo
        $stmt_q = $conn->prepare("SELECT id_quiz FROM quices WHERE id_materia = ? LIMIT 1");
        $stmt_q->bind_param("i", $id_materia);
        $stmt_q->execute();
        $res_q = $stmt_q->get_result();
        
        if ($res_q->num_rows > 0) {
            $row_q = $res_q->fetch_assoc();
            $id_quiz = $row_q['id_quiz'];
        } else {
            $nombre_quiz_gen = "Cuestionario Oficial";
            $stmt_nq = $conn->prepare("INSERT INTO quices (nombre_quiz, id_materia) VALUES (?, ?)");
            $stmt_nq->bind_param("si", $nombre_quiz_gen, $id_materia);
            $stmt_nq->execute();
            $id_quiz = $stmt_nq->insert_id;
            $stmt_nq->close();
        }
        $stmt_q->close();
    }

    // Insertamos la pregunta vinculada al quiz que haya resultado
    $stmt_p = $conn->prepare("INSERT INTO preguntas (texto_pregunta, id_quiz, estado) VALUES (?, ?, ?)");
    $stmt_p->bind_param("sis", $texto_pregunta, $id_quiz, $estado);
    $stmt_p->execute();
    $id_pregunta = $stmt_p->insert_id;
    $stmt_p->close();

    $stmt_o = $conn->prepare("INSERT INTO opciones (texto_opcion, es_correcta, id_pregunta) VALUES (?, ?, ?)");
    foreach ($opciones as $index => $texto_opcion) {
        if (!empty(trim($texto_opcion))) {
            $es_correcta = ($index === $correcta_index) ? 1 : 0;
            $stmt_o->bind_param("sii", $texto_opcion, $es_correcta, $id_pregunta);
            $stmt_o->execute();
        }
    }
    $stmt_o->close();

    if ($estado === 'pendiente') {
        echo "<script>alert('Tu pregunta ha sido enviada a revision por los administradores.'); window.location.href='panel_preguntas.php';</script>";
    } else {
        echo "<script>alert('Pregunta agregada y publicada con exito en el cuestionario.'); window.location.href='panel_preguntas.php';</script>";
    }
    exit();
}

$query_materias = "SELECT m.id_materia, m.nombre_materia, s.nombre_semestre FROM materias m JOIN semestres s ON m.id_semestre = s.id_semestre ORDER BY s.id_semestre, m.id_materia";
$result_materias = $conn->query($query_materias);

$query_quices = "SELECT q.id_quiz, q.nombre_quiz, m.nombre_materia FROM quices q JOIN materias m ON q.id_materia = m.id_materia ORDER BY m.nombre_materia, q.nombre_quiz";
$res_quices_dropdown = $conn->query($query_quices);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>BRAIN FIRE - Creador de Quizzes</title>
    <link rel="stylesheet" href="style-inicio.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .panel-container { max-width: 800px; margin: 40px auto; background: white; padding: 40px; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-weight: bold; margin-bottom: 8px; color: #4a2450; }
        .form-control { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 8px; font-size: 16px; font-family: inherit; box-sizing: border-box;}
        .opcion-row { display: flex; align-items: center; gap: 15px; margin-bottom: 10px; }
        .opcion-row input[type="text"] { flex-grow: 1; }
        .opcion-row input[type="radio"] { transform: scale(1.5); cursor: pointer; }
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

    <main class="panel-container">
        <h2 style="text-align: center; color: #612766; margin-bottom: 10px;">Proponer Pregunta</h2>
        <p style="text-align: center; color: #777; margin-bottom: 30px;">Si eres alumno normal, tu propuesta pasara por filtros de aprobacion.</p>
        
        <form action="panel_preguntas.php" method="POST">
            <div class="form-group">
                <label>Selecciona la Materia:</label>
                <select name="materia" class="form-control" required>
                    <option value="" disabled selected>-- Elige una materia --</option>
                    <?php while($row = $result_materias->fetch_assoc()): ?>
                        <option value="<?php echo $row['id_materia']; ?>">
                            <?php echo $row['nombre_semestre'] . " - " . $row['nombre_materia']; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <?php if ($es_profesor): ?>
            <div style="background-color: #fdf6fd; border: 1px solid #8a3592; padding: 15px; border-radius: 8px; margin-top: 15px; margin-bottom: 20px;">
                <h4 style="color: #612766; margin-top: 0; margin-bottom: 15px;"><i class="fa-solid fa-chalkboard-user"></i> Opciones Avanzadas de Docente</h4>
                
                <label style="font-weight: bold; font-size: 14px; margin-bottom: 8px; display: block;">Asignar a un Quiz Existente o Crear Uno Nuevo:</label>
                <select name="accion_quiz" id="accion_quiz" class="form-control" style="margin-bottom: 10px;" onchange="toggleNuevoQuiz()">
                    <option value="existente">Agregar al Quiz General / Existente</option>
                    <option value="nuevo">Crear un Nuevo Quiz / Parcial</option>
                </select>

                <div id="campos_nuevo_quiz" style="display: none; margin-top: 10px; padding-top: 15px; border-top: 1px dashed #ccc;">
                    <label style="font-size: 14px; font-weight: bold; margin-bottom: 5px; display: block;">Nombre del Nuevo Quiz:</label>
                    <input type="text" name="nombre_quiz" class="form-control" placeholder="Ej: Tercer Parcial" style="margin-bottom: 15px;">

                    <label style="font-size: 14px; font-weight: bold; margin-bottom: 5px; display: block;">¿Depende de un quiz anterior?</label>
                    <select name="id_quiz_previo" class="form-control" style="margin-bottom: 15px;">
                        <option value="0">Ninguno (Acceso Libre)</option>
                        <?php while($q = $res_quices_dropdown->fetch_assoc()): ?>
                            <option value="<?php echo $q['id_quiz']; ?>">
                                [<?php echo htmlspecialchars($q['nombre_materia']); ?>] - <?php echo htmlspecialchars($q['nombre_quiz']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>

                    <label style="font-size: 14px; font-weight: bold; margin-bottom: 5px; display: block;">Porcentaje requerido (%):</label>
                    <input type="number" name="porcentaje_requerido" class="form-control" value="0" min="0" max="100">
                </div>
            </div>

            <script>
                function toggleNuevoQuiz() {
                    var selector = document.getElementById('accion_quiz').value;
                    document.getElementById('campos_nuevo_quiz').style.display = (selector === 'nuevo') ? 'block' : 'none';
                }
            </script>
            <?php endif; ?>

            <div class="form-group">
                <label>Texto de la Pregunta:</label>
                <textarea name="pregunta" class="form-control" rows="3" placeholder="Escribe la pregunta aqui..." required></textarea>
            </div>

            <div class="form-group">
                <label>Opciones (Marca el circulo de la respuesta correcta):</label>
                <div class="opcion-row">
                    <input type="radio" name="correcta" value="0" required>
                    <input type="text" name="opciones[]" class="form-control" placeholder="Opcion 1..." required>
                </div>
                <div class="opcion-row">
                    <input type="radio" name="correcta" value="1">
                    <input type="text" name="opciones[]" class="form-control" placeholder="Opcion 2..." required>
                </div>
                <div class="opcion-row">
                    <input type="radio" name="correcta" value="2">
                    <input type="text" name="opciones[]" class="form-control" placeholder="Opcion 3..." required>
                </div>
                <div class="opcion-row">
                    <input type="radio" name="correcta" value="3">
                    <input type="text" name="opciones[]" class="form-control" placeholder="Opcion 4..." required>
                </div>
            </div>

            <button type="submit" class="btn-ingresar" style="width: 100%; padding: 15px; font-size: 18px;">Enviar Pregunta</button>
        </form>
    </main>
</body>
</html>
<?php
$conn->close();
?>