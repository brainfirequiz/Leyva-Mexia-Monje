<?php
session_start();
include 'conexion.php';

if (!isset($_SESSION['ia_preguntas_preview']) || count($_SESSION['ia_preguntas_preview']) === 0) {
    echo "<script>alert('No hay preguntas pendientes de revision.'); window.location.href='importar_quiz.php';</script>";
    exit();
}

// 1. ACCION: Eliminar una pregunta individual del listado temporal
if (isset($_GET['eliminar_index'])) {
    $index = intval($_GET['eliminar_index']);
    if (isset($_SESSION['ia_preguntas_preview'][$index])) {
        unset($_SESSION['ia_preguntas_preview'][$index]);
        // Reindexamos el arreglo para evitar huecos en los indices
        $_SESSION['ia_preguntas_preview'] = array_values($_SESSION['ia_preguntas_preview']);
    }
    header("Location: revisar_importacion.php");
    exit();
}

// 2. ACCION: Cancelar y borrar todo de una vez
if (isset($_POST['rechazar_todo'])) {
    unset($_SESSION['ia_preguntas_preview']);
    unset($_SESSION['ia_materia_tipo']);
    unset($_SESSION['ia_materia_nombre']);
    unset($_SESSION['ia_materia_semestre']);
    unset($_SESSION['ia_materia_id']);
    
    echo "<script>alert('Proceso cancelado. Preguntas eliminadas.'); window.location.href='importar_quiz.php';</script>";
    exit();
}

// 3. ACCION: Guardar e integrar todo a la Base de Datos
if (isset($_POST['aceptar_todo'])) {
    $conn->begin_transaction(); // Usamos transacciones para seguridad absoluta

    try {
        $id_materia_final = 0;

        // Si es una materia nueva, la registramos primero
        if ($_SESSION['ia_materia_tipo'] === 'NUEVA') {
            $nombre_mat = $_SESSION['ia_materia_nombre'];
            $semestre_id = $_SESSION['ia_materia_semestre'];

            $stmt_m = $conn->prepare("INSERT INTO materias (nombre_materia, id_semestre) VALUES (?, ?)");
            $stmt_m->bind_param("si", $nombre_mat, $semestre_id);
            $stmt_m->execute();
            $id_materia_final = $stmt_m->insert_id;
            $stmt_m->close();
        } else {
            $id_materia_final = $_SESSION['ia_materia_id'];
        }

        // Verificamos o creamos el cuestionario para esa materia
        $stmt_q = $conn->prepare("SELECT id_quiz FROM quices WHERE id_materia = ? LIMIT 1");
        $stmt_q->bind_param("i", $id_materia_final);
        $stmt_q->execute();
        $res_q = $stmt_q->get_result();

        if ($res_q->num_rows > 0) {
            $row_q = $res_q->fetch_assoc();
            $id_quiz = $row_q['id_quiz'];
        } else {
            $titulo_t = "Cuestionario Oficial Automatizado";
            $stmt_nq = $conn->prepare("INSERT INTO quices (titulo_quiz, id_materia) VALUES (?, ?)");
            $stmt_nq->bind_param("si", $titulo_t, $id_materia_final);
            $stmt_nq->execute();
            $id_quiz = $stmt_nq->insert_id;
            $stmt_nq->close();
        }
        $stmt_q->close();

        // Insertamos cada una de las preguntas aprobadas
        $stmt_p = $conn->prepare("INSERT INTO preguntas (texto_pregunta, id_quiz, estado) VALUES (?, ?, 'aprobada')");
        $stmt_o = $conn->prepare("INSERT INTO opciones (texto_opcion, es_correcta, id_pregunta) VALUES (?, ?, ?)");

        foreach ($_SESSION['ia_preguntas_preview'] as $item) {
            $stmt_p->bind_param("si", $item['pregunta'], $id_quiz);
            $stmt_p->execute();
            $id_pregunta = $stmt_p->insert_id;

            foreach ($item['opciones'] as $index => $texto_opcion) {
                $es_correcta = ($index === $item['correcta']) ? 1 : 0;
                $stmt_o->bind_param("sii", $texto_opcion, $es_correcta, $id_pregunta);
                $stmt_o->execute();
            }
        }

        $stmt_p->close();
        $stmt_o->close();
        
        $conn->commit(); // Confirmamos los cambios en la BD

        // Limpiamos variables de sesion del modulo
        unset($_SESSION['ia_preguntas_preview']);
        unset($_SESSION['ia_materia_tipo']);
        
        echo "<script>alert('Cuestionario guardado con exito en la base de datos.'); window.location.href='semestres.php';</script>";
        exit();

    } catch (Exception $e) {
        $conn->rollback(); // Cancelamos todo si ocurre un error inesperado
        echo "<script>alert('Error al guardar los datos de importacion.'); window.location.href='importar_quiz.php';</script>";
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>BRAIN FIRE - Filtro de Evaluacion</title>
    <link rel="stylesheet" href="style-inicio.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .preview-container { max-width: 900px; margin: 40px auto; background: white; padding: 40px; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .card-ia { border: 1px solid #ccc; padding: 20px; border-radius: 10px; margin-bottom: 20px; position: relative; background: #fafafa; }
        .opciones-ia { margin-top: 12px; padding-left: 20px; }
        .opciones-ia li { margin-bottom: 6px; font-size: 15px; }
        .correcta { color: #155724; font-weight: bold; }
        .btn-delete-item { position: absolute; top: 20px; right: 20px; background: #dc3545; color: white; border: none; padding: 6px 12px; border-radius: 5px; cursor: pointer; }
        .acciones-bloque { display: flex; gap: 20px; margin-top: 30px; }
    </style>
</head>
<body>
    <header class="header-index">
        <div class="logo">
            <span class="brain">Brain</span><span class="fire">Fire</span>
        </div>
    </header>

    <main class="preview-container">
        <h2 style="color: #612766; text-align: center;">Filtro de Validacion IA</h2>
        <p style="text-align: center; color: #666; margin-bottom: 30px;">
            Destino: <?php echo ($_SESSION['ia_materia_tipo'] === 'NUEVA') ? "Nueva Materia [ " . htmlspecialchars($_SESSION['ia_materia_nombre']) . " ]" : "Materia Registrada ID: " . $_SESSION['ia_materia_id']; ?>
        </p>

        <?php foreach ($_SESSION['ia_preguntas_preview'] as $index => $item): ?>
            <div class="card-ia">
                <a href="revisar_importacion.php?eliminar_index=<?php echo $index; ?>" class="btn-delete-item" onclick="return confirm('¿Remover esta pregunta del bloque?')">
                    <i class="fa-solid fa-trash-can"></i> Quitar
                </a>
                <strong>Pregunta <?php echo $index + 1; ?>:</strong>
                <p style="font-size: 17px; margin-top: 8px; font-weight: 500;"><?php echo htmlspecialchars($item['pregunta']); ?></p>
                
                <ul class="opciones-ia">
                    <?php foreach ($item['opciones'] as $o_index => $opcion): ?>
                        <li class="<?php echo ($o_index === $item['correcta']) ? 'correcta' : ''; ?>">
                            <?php if($o_index === $item['correcta']): ?>
                                <i class="fa-solid fa-circle-check"></i>
                            <?php else: ?>
                                <i class="fa-solid fa-circle"></i>
                            <?php endif; ?>
                            <?php echo htmlspecialchars($opcion); ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endforeach; ?>

        <div class="acciones-bloque">
            <form action="revisar_importacion.php" method="POST" style="background:transparent; border:none; box-shadow:none; padding:0; margin:0; width:100%; display:flex; gap:20px;">
                <button type="submit" name="aceptar_todo" class="btn-ingresar" style="flex: 1; padding: 15px; font-size: 16px; background-color: #28a745;">Aceptar y Publicar Todo</button>
                <button type="submit" name="rechazar_todo" class="btn-ingresar" style="flex: 1; padding: 15px; font-size: 16px; background-color: #dc3545;" onclick="return confirm('¿Seguro que quieres descartar todo el bloque?')">Rechazar y Borrar Todo</button>
            </form>
        </div>
    </main>
</body>
</html>