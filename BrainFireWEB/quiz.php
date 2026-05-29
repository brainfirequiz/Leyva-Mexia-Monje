<?php
session_start();
include 'conexion.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: inicio.html");
    exit();
}

// 1. INICIALIZAR QUIZ
if (isset($_GET['quiz'])) {
    $id_quiz = intval($_GET['quiz']);

    // SISTEMA INTELIGENTE: Prioriza preguntas nulas (-1), luego puntajes bajos, luego maximos de este quiz especifico.
    $query_quiz = "SELECT p.id_pregunta 
                   FROM preguntas p 
                   LEFT JOIN user_question_scores uqs ON p.id_pregunta = uqs.id_pregunta AND uqs.id_usuario = ? 
                   WHERE p.id_quiz = ? AND p.estado = 'aprobada' 
                   ORDER BY COALESCE(uqs.best_score, -1) ASC, RAND() 
                   LIMIT 10";
                   
    $stmt = $conn->prepare($query_quiz);    
    $stmt->bind_param("ii", $_SESSION['user_id'], $id_quiz);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
        $preguntas = [];
        
        while ($row = $res->fetch_assoc()) {
            $preguntas[] = $row['id_pregunta'];
        }
        
        $_SESSION['quiz_preguntas'] = $preguntas;
        $_SESSION['quiz_indice'] = 0;
        $_SESSION['quiz_puntos'] = 0;
        $_SESSION['quiz_id_actual'] = $id_quiz;

        header("Location: quiz.php"); 
        exit();
    } else {
        echo "<script>alert('Este cuestionario no cuenta con preguntas aprobadas aun.'); window.location.href='semestres.php';</script>";
        exit();
    }
}
// 2. PROCESAR RESPUESTA
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['quiz_preguntas'])) {
    $indice_actual = $_SESSION['quiz_indice'];
    $id_pregunta = $_SESSION['quiz_preguntas'][$indice_actual];
    $id_usuario = $_SESSION['user_id'];

    $opcion_elegida = isset($_POST['opcion']) ? intval($_POST['opcion']) : 0;
    $tiempo_sobrante = isset($_POST['tiempo']) ? intval($_POST['tiempo']) : 0;

    if ($opcion_elegida > 0) {
        $stmt_check = $conn->prepare("SELECT es_correcta FROM opciones WHERE id_opcion = ? AND id_pregunta = ?");
        $stmt_check->bind_param("ii", $opcion_elegida, $id_pregunta);
        $stmt_check->execute();
        $res_check = $stmt_check->get_result();

        if ($res_check->num_rows > 0) {
            $row = $res_check->fetch_assoc();
            if ($row['es_correcta'] == 1) {
                
                $puntos_ganados = 50 + ($tiempo_sobrante * 5);
                $_SESSION['quiz_puntos'] += $puntos_ganados; 

                $stmt_score = $conn->prepare("SELECT best_score FROM user_question_scores WHERE id_usuario = ? AND id_pregunta = ?");
                $stmt_score->bind_param("ii", $id_usuario, $id_pregunta);
                $stmt_score->execute();
                $res_score = $stmt_score->get_result();

                if ($res_score->num_rows > 0) {
                    $row_score = $res_score->fetch_assoc();
                    $best_score = $row_score['best_score'];

                    if ($puntos_ganados > $best_score) {
                        $diferencia = $puntos_ganados - $best_score;
                        $stmt_upd = $conn->prepare("UPDATE user_question_scores SET best_score = ? WHERE id_usuario = ? AND id_pregunta = ?");
                        $stmt_upd->bind_param("iii", $puntos_ganados, $id_usuario, $id_pregunta);
                        $stmt_upd->execute();
                        
                        // SE SUMAN PUNTOS AL RANKING Y AL WALLET
                        $stmt_total = $conn->prepare("UPDATE users SET total_score = total_score + ?, wallet = wallet + ? WHERE id = ?");
                        $stmt_total->bind_param("iii", $diferencia, $diferencia, $id_usuario);
                        $stmt_total->execute();
                    }
                } else {
                    $stmt_ins = $conn->prepare("INSERT INTO user_question_scores (id_usuario, id_pregunta, best_score) VALUES (?, ?, ?)");
                    $stmt_ins->bind_param("iii", $id_usuario, $id_pregunta, $puntos_ganados);
                    $stmt_ins->execute();
                    
                    // SE SUMAN PUNTOS AL RANKING Y AL WALLET
                    $stmt_total = $conn->prepare("UPDATE users SET total_score = total_score + ?, wallet = wallet + ? WHERE id = ?");
                    $stmt_total->bind_param("iii", $puntos_ganados, $puntos_ganados, $id_usuario);
                    $stmt_total->execute();
                }
            }
        }
    }

    $_SESSION['quiz_indice']++;
    header("Location: quiz.php");
    exit();
}

// 3. PANTALLA DE RESULTADOS FINALES
if (isset($_SESSION['quiz_preguntas']) && $_SESSION['quiz_indice'] >= count($_SESSION['quiz_preguntas'])) {
    $puntos_totales = $_SESSION['quiz_puntos'];
    $id_usuario = $_SESSION['user_id'];
    $id_quiz = $_SESSION['quiz_id_actual'];

    $stmt_save = $conn->prepare("INSERT INTO puntajes (id_usuario, id_quiz, puntuacion_obtenida) VALUES (?, ?, ?)");
    $stmt_save->bind_param("iii", $id_usuario, $id_quiz, $puntos_totales);
    $stmt_save->execute();
    $stmt_save->close();

    unset($_SESSION['quiz_preguntas']);
    unset($_SESSION['quiz_indice']);
    unset($_SESSION['quiz_puntos']);
    unset($_SESSION['quiz_id_actual']);

    $html_resultado = true;

} else if (isset($_SESSION['quiz_preguntas'])) {
    
    $indice_actual = $_SESSION['quiz_indice'];
    $id_pregunta = $_SESSION['quiz_preguntas'][$indice_actual];

    $stmt_p = $conn->prepare("SELECT texto_pregunta FROM preguntas WHERE id_pregunta = ?");
    $stmt_p->bind_param("i", $id_pregunta);
    $stmt_p->execute();
    $res_p = $stmt_p->get_result();
    $row_p = $res_p->fetch_assoc();
    $texto_pregunta = $row_p['texto_pregunta'];
    $stmt_p->close();

    $stmt_o = $conn->prepare("SELECT id_opcion, texto_opcion, es_correcta FROM opciones WHERE id_pregunta = ? ORDER BY RAND()");
    $stmt_o->bind_param("i", $id_pregunta);
    $stmt_o->execute();
    $res_o = $stmt_o->get_result();
    $opciones = [];
    while($o = $res_o->fetch_assoc()){
        $opciones[] = $o;
    }
    $stmt_o->close();

    $html_pregunta = true;
} else {
    header("Location: semestres.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>BRAIN FIRE - Jugar Quiz</title>
    <link rel="stylesheet" href="style-inicio.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .quiz-container { 
            max-width: 800px; 
            margin: 40px auto; 
            background: white; 
            padding: 40px; 
            border-radius: 15px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.1); 
        }
        .timer { 
            font-size: 28px; 
            font-weight: bold; 
            color: #d9534f; 
            margin-bottom: 20px; 
            text-align: center;
        }
        .pregunta { 
            font-size: 24px; 
            margin-bottom: 30px; 
            color: #333; 
            font-weight: bold;
            text-align: center;
        }
        .opcion-btn { 
            display: block; 
            width: 100%; 
            padding: 18px 25px; 
            margin-bottom: 15px; 
            border: 2px solid #612766; 
            background: white; 
            color: #612766; 
            font-size: 18px; 
            border-radius: 10px; 
            cursor: pointer; 
            transition: all 0.3s ease; 
            text-align: left !important; 
            font-weight: 500;
            white-space: normal; 
            word-wrap: break-word; 
            height: auto; 
        }
        .opcion-btn:hover { 
            background: #612766; 
            color: white; 
            padding-left: 35px; 
        }
        .opcion-btn:disabled {
            cursor: not-allowed;
            opacity: 0.9;
        }
        
        .btn-correcta { background-color: #28a745 !important; color: white !important; border-color: #1e7e34 !important; }
        .btn-incorrecta { background-color: #dc3545 !important; color: white !important; border-color: #bd2130 !important; }

        @keyframes flotarPuntos {
            0% { opacity: 0; transform: translateY(0) scale(1); }
            20% { opacity: 1; transform: translateY(-20px) scale(1.2); }
            80% { opacity: 1; transform: translateY(-40px) scale(1); }
            100% { opacity: 0; transform: translateY(-60px); }
        }
        .puntos-animacion {
            position: fixed;
            font-weight: bold;
            font-size: 32px;
            color: #28a745;
            animation: flotarPuntos 1.5s ease-out forwards;
            pointer-events: none;
            z-index: 9999;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .resultado-puntos { 
            font-size: 50px; 
            color: #8a3592; 
            font-weight: bold; 
            margin: 20px 0; 
            text-align: center;
        }
        
        #form_quiz {
            width: 100% !important;
            background-color: transparent !important;
            border: none !important;
            box-shadow: none !important;
            padding: 0 !important;
            margin: 0 !important;
        }
    </style>
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

    <main>
        <?php if(isset($html_resultado)): ?>
            <div class="quiz-container">
                <h2>Cuestionario Completado</h2>
                <p style="font-size: 20px; margin-top:10px;">Tu puntuacion en este intento es:</p>
                <div class="resultado-puntos"><?php echo $puntos_totales; ?> pts</div>
                <br>
                <a href="semestres.php" class="btn-ingresar" style="text-decoration:none; display:inline-block; padding: 15px 30px;">Volver a Semestres</a>
            </div>

        <?php elseif(isset($html_pregunta)): ?>
            <div class="quiz-container">
                <div class="timer" id="tiempo_visual">15s</div>
                <div class="pregunta"><?php echo htmlspecialchars($texto_pregunta); ?></div>

                <form id="form_quiz" method="POST" action="quiz.php">
                    <input type="hidden" id="tiempo_oculto" name="tiempo" value="15">
                    <input type="hidden" id="opcion_elegida" name="opcion" value="0">

                    <?php foreach($opciones as $op): ?>
                        <button type="button" class="opcion-btn" data-correcta="<?php echo $op['es_correcta']; ?>" onclick="seleccionarOpcion(<?php echo $op['id_opcion']; ?>, this, event)">
                            <?php echo htmlspecialchars($op['texto_opcion']); ?>
                        </button>
                    <?php endforeach; ?>
                </form>
            </div>

            <script>
                let tiempo = 15;
                let timerElement = document.getElementById('tiempo_visual');
                let inputTiempo = document.getElementById('tiempo_oculto');
                let inputOpcion = document.getElementById('opcion_elegida');
                let form = document.getElementById('form_quiz');

                let cronometro = setInterval(function() {
                    tiempo--;
                    if(timerElement) timerElement.innerText = tiempo + "s";
                    if(inputTiempo) inputTiempo.value = tiempo;

                    if (tiempo <= 0) {
                        clearInterval(cronometro);
                        if(form) form.submit(); 
                    }
                }, 1000);

                function seleccionarOpcion(id_opcion, botonClickeado, evento) {
                    clearInterval(cronometro); 
                    
                    if(inputOpcion) inputOpcion.value = id_opcion;
                    
                    let botones = document.getElementsByClassName('opcion-btn');
                    let esCorrecta = botonClickeado.getAttribute('data-correcta');
                    let puntosGanados = 0;

                    for(let i = 0; i < botones.length; i++) {
                        botones[i].disabled = true;
                    }

                    if (esCorrecta == '1') {
                        botonClickeado.classList.add('btn-correcta');
                        puntosGanados = 50 + (tiempo * 5); 
                    } else {
                        botonClickeado.classList.add('btn-incorrecta');
                        for(let i = 0; i < botones.length; i++) {
                            if(botones[i].getAttribute('data-correcta') == '1') {
                                botones[i].classList.add('btn-correcta');
                            }
                        }
                    }

                    let animacion = document.createElement('div');
                    animacion.className = 'puntos-animacion';
                    
                    if (puntosGanados > 0) {
                        animacion.innerText = '+' + puntosGanados + ' pts';
                    } else {
                        animacion.innerText = '0 pts';
                        animacion.style.color = '#dc3545'; 
                    }

                    let posX = evento && evento.clientX ? evento.clientX - 20 : window.innerWidth / 2;
                    let posY = evento && evento.clientY ? evento.clientY - 30 : window.innerHeight / 2;

                    animacion.style.left = posX + 'px';
                    animacion.style.top = posY + 'px';
                    document.body.appendChild(animacion);

                    setTimeout(function() {
                        if(form) form.submit();
                    }, 1500);
                }
            </script>
        <?php endif; ?>
    </main>
</body>
</html>