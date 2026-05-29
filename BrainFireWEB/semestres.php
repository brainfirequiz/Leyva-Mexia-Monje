<?php
session_start();
include 'conexion.php'; // Agregamos la conexion necesaria para hacer los calculos

if (!isset($_SESSION['user_id'])) {
    header("Location: inicio.html");
    exit();
}

$id_usuario = $_SESSION['user_id'];

// Consultamos el progreso general de cada semestre
$query = "SELECT 
    s.id_semestre,
    COUNT(p.id_pregunta) * 125 AS max_puntos,
    COALESCE(SUM(uqs.best_score), 0) AS puntos_usuario
FROM semestres s
LEFT JOIN materias m ON s.id_semestre = m.id_semestre
LEFT JOIN quices q ON m.id_materia = q.id_materia
LEFT JOIN preguntas p ON q.id_quiz = p.id_quiz AND p.estado = 'aprobada'
LEFT JOIN user_question_scores uqs ON p.id_pregunta = uqs.id_pregunta AND uqs.id_usuario = ?
GROUP BY s.id_semestre";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$result = $stmt->get_result();

$progreso = [];
// Inicializamos los 8 semestres en 0
for ($i = 1; $i <= 8; $i++) {
    $progreso[$i] = ['max' => 0, 'user' => 0, 'porcentaje' => 0];
}

while ($row = $result->fetch_assoc()) {
    $id_sem = $row['id_semestre'];
    $max = $row['max_puntos'];
    $user = $row['puntos_usuario'];
    $porcentaje = ($max > 0) ? round(($user / $max) * 100) : 0;
    
    $progreso[$id_sem] = [
        'max' => $max, 
        'user' => $user, 
        'porcentaje' => $porcentaje
    ];
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>BRAIN FIRE - Perfil de Usuario</title>
    <link rel="stylesheet" href="style-inicio.css">
    <link rel="icon" type="image/png" href="recursos/icono.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* Nuevos estilos para las barras de progreso integradas a tu diseño */
        .progreso-contenedor { 
            width: 100%; 
            background-color: #e9ecef; 
            border-radius: 6px; 
            overflow: hidden; 
            margin-top: 15px; 
            height: 12px; 
        }
        .progreso-barra { 
            height: 100%; 
            transition: width 0.8s ease; 
        }
        .progreso-texto { 
            font-size: 14px; 
            color: #612766; 
            margin-top: 5px; 
            font-weight: bold; 
        }
    </style>
</head>

<body>

<header class="header-index" style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
    
    <div style="display: flex; align-items: center; gap: 30px;">
        <div class="logo">
            <span class="brain">Brain</span><span class="fire">Fire</span>
        </div>
        <a href="tienda.php" style="color: #ffc107; text-decoration: none; font-weight: bold; font-size: 18px; display: flex; align-items: center; gap: 8px; transition: 0.3s;">
            <i class="fa-solid fa-store"></i> Tienda
        </a>
    </div>
    
    <div class="nav-header" style="display: flex; align-items: center;">
        <a href="ranking.php" style="color: white; text-decoration: none; margin-right: 20px; font-weight: bold;">
            Ranking Global
        </a>
        <a href="config.php" class="help-icon" style="color: white;">
            <i class="fa-solid fa-user-gear"></i>
        </a>
    </div>

</header>

    <main class="semester-container">
        
        <a href="materias.php?id=1" style="text-decoration: none; color: inherit; width: 100%; max-width: 900px; display: block;">
            <div class="semester-card">
                <div class="semester-text-content">
                    <h3>Semestre 1</h3>
                    <p>Sumérgete en el mundo de la lógica de programación y los algoritmos fundamentales. Domina las bases matemáticas necesarias para tu carrera de ingeniería.</p>
                    
                    <?php 
                        $pct = $progreso[1]['porcentaje'];
                        $color = ($pct == 100) ? '#8a3592' : '#28a745';
                    ?>
                    <div class="progreso-contenedor">
                        <div class="progreso-barra" style="width: <?php echo $pct; ?>%; background-color: <?php echo $color; ?>;"></div>
                    </div>
                    <div class="progreso-texto">Progreso: <?php echo $pct; ?>% (<?php echo $progreso[1]['user']; ?> / <?php echo $progreso[1]['max']; ?> pts)</div>
                </div>
                <div class="semester-image-container">
                    <img src="recursos/1.png" alt="Estudio de Semestre 1">
                </div>
            </div>
        </a>

        <a href="materias.php?id=2" style="text-decoration: none; color: inherit; width: 100%; max-width: 900px; display: block;">
            <div class="semester-card">
                <div class="semester-text-content">
                    <h3>Semestre 2</h3>
                    <p>Aprende a gestionar y organizar datos eficientemente. Refuerza tus habilidades matemáticas y físicas aplicadas a la computación y la electrónica básica.</p>
                    
                    <?php 
                        $pct = $progreso[2]['porcentaje'];
                        $color = ($pct == 100) ? '#8a3592' : '#28a745';
                    ?>
                    <div class="progreso-contenedor">
                        <div class="progreso-barra" style="width: <?php echo $pct; ?>%; background-color: <?php echo $color; ?>;"></div>
                    </div>
                    <div class="progreso-texto">Progreso: <?php echo $pct; ?>% (<?php echo $progreso[2]['user']; ?> / <?php echo $progreso[2]['max']; ?> pts)</div>
                </div>
                <div class="semester-image-container">
                    <img src="recursos/2.png" alt="Estudio de Semestre 2">
                </div>
            </div>
        </a>

        <a href="materias.php?id=3" style="text-decoration: none; color: inherit; width: 100%; max-width: 900px; display: block;">
            <div class="semester-card">
                <div class="semester-text-content">
                    <h3>Semestre 3</h3>
                    <p>Diseña sistemas de software robustos y modulares con la programación orientada a objetos. Domina el modelado y la gestión de bases de datos relacionales.</p>
                    
                    <?php 
                        $pct = $progreso[3]['porcentaje'];
                        $color = ($pct == 100) ? '#8a3592' : '#28a745';
                    ?>
                    <div class="progreso-contenedor">
                        <div class="progreso-barra" style="width: <?php echo $pct; ?>%; background-color: <?php echo $color; ?>;"></div>
                    </div>
                    <div class="progreso-texto">Progreso: <?php echo $pct; ?>% (<?php echo $progreso[3]['user']; ?> / <?php echo $progreso[3]['max']; ?> pts)</div>
                </div>
                <div class="semester-image-container">
                    <img src="recursos/3.jpg" alt="Estudio de Semestre 3">
                </div>
            </div>
        </a>

        <a href="materias.php?id=4" style="text-decoration: none; color: inherit; width: 100%; max-width: 900px; display: block;">
            <div class="semester-card">
                <div class="semester-text-content">
                    <h3>Semestre 4</h3>
                    <p>Desafía tu mente con algoritmos complejos y eficiencia computacional. Conéctate con el mundo a través de las redes de datos y la comunicación.</p>
                    
                    <?php 
                        $pct = $progreso[4]['porcentaje'];
                        $color = ($pct == 100) ? '#8a3592' : '#28a745';
                    ?>
                    <div class="progreso-contenedor">
                        <div class="progreso-barra" style="width: <?php echo $pct; ?>%; background-color: <?php echo $color; ?>;"></div>
                    </div>
                    <div class="progreso-texto">Progreso: <?php echo $pct; ?>% (<?php echo $progreso[4]['user']; ?> / <?php echo $progreso[4]['max']; ?> pts)</div>
                </div>
                <div class="semester-image-container">
                    <img src="recursos/4.jfif" alt="Estudio de Semestre 4">
                </div>
            </div>
        </a>

        <a href="materias.php?id=5" style="text-decoration: none; color: inherit; width: 100%; max-width: 900px; display: block;">
            <div class="semester-card">
                <div class="semester-text-content">
                    <h3>Semestre 5</h3>
                    <p>Descubre cómo funciona el hardware por dentro con la arquitectura de computadoras. Aplica metodologías profesionales para el desarrollo de software de calidad.</p>
                    
                    <?php 
                        $pct = $progreso[5]['porcentaje'];
                        $color = ($pct == 100) ? '#8a3592' : '#28a745';
                    ?>
                    <div class="progreso-contenedor">
                        <div class="progreso-barra" style="width: <?php echo $pct; ?>%; background-color: <?php echo $color; ?>;"></div>
                    </div>
                    <div class="progreso-texto">Progreso: <?php echo $pct; ?>% (<?php echo $progreso[5]['user']; ?> / <?php echo $progreso[5]['max']; ?> pts)</div>
                </div>
                <div class="semester-image-container">
                    <img src="recursos/5.jpg" alt="Estudio de Semestre 5">
                </div>
            </div>
        </a>

        <a href="materias.php?id=6" style="text-decoration: none; color: inherit; width: 100%; max-width: 900px; display: block;">
            <div class="semester-card">
                <div class="semester-text-content">
                    <h3>Semestre 6</h3>
                    <p>Lidera equipos y proyectos de software exitosos. Protege tus sistemas de las amenazas cibernéticas más modernas y profundiza en la inteligencia artificial básica.</p>
                    
                    <?php 
                        $pct = $progreso[6]['porcentaje'];
                        $color = ($pct == 100) ? '#8a3592' : '#28a745';
                    ?>
                    <div class="progreso-contenedor">
                        <div class="progreso-barra" style="width: <?php echo $pct; ?>%; background-color: <?php echo $color; ?>;"></div>
                    </div>
                    <div class="progreso-texto">Progreso: <?php echo $pct; ?>% (<?php echo $progreso[6]['user']; ?> / <?php echo $progreso[6]['max']; ?> pts)</div>
                </div>
                <div class="semester-image-container">
                    <img src="recursos/6.jfif" alt="Estudio de Semestre 6">
                </div>
            </div>
        </a>

        <a href="materias.php?id=7" style="text-decoration: none; color: inherit; width: 100%; max-width: 900px; display: block;">
            <div class="semester-card">
                <div class="semester-text-content">
                    <h3>Semestre 7</h3>
                    <p>Domina la creación de sistemas a gran escala y la computación en la nube. Reflexiona sobre el impacto ético y legal de la tecnología en la sociedad.</p>
                    
                    <?php 
                        $pct = $progreso[7]['porcentaje'];
                        $color = ($pct == 100) ? '#8a3592' : '#28a745';
                    ?>
                    <div class="progreso-contenedor">
                        <div class="progreso-barra" style="width: <?php echo $pct; ?>%; background-color: <?php echo $color; ?>;"></div>
                    </div>
                    <div class="progreso-texto">Progreso: <?php echo $pct; ?>% (<?php echo $progreso[7]['user']; ?> / <?php echo $progreso[7]['max']; ?> pts)</div>
                </div>
                <div class="semester-image-container">
                    <img src="recursos/7.jpg" alt="Estudio de Semestre 7">
                </div>
            </div>
        </a>

        <a href="materias.php?id=8" style="text-decoration: none; color: inherit; width: 100%; max-width: 900px; display: block;">
            <div class="semester-card">
                <div class="semester-text-content">
                    <h3>Semestre 8</h3>
                    <p>Culmina tu formación con un proyecto de tesis o prácticas profesionales. Prepárate para el mundo laboral, el emprendimiento tecnológico y la gestión de calidad.</p>
                    
                    <?php 
                        $pct = $progreso[8]['porcentaje'];
                        $color = ($pct == 100) ? '#8a3592' : '#28a745';
                    ?>
                    <div class="progreso-contenedor">
                        <div class="progreso-barra" style="width: <?php echo $pct; ?>%; background-color: <?php echo $color; ?>;"></div>
                    </div>
                    <div class="progreso-texto">Progreso: <?php echo $pct; ?>% (<?php echo $progreso[8]['user']; ?> / <?php echo $progreso[8]['max']; ?> pts)</div>
                </div>
                <div class="semester-image-container">
                    <img src="recursos/8.jpg" alt="Estudio de Semestre 8">
                </div>
            </div>
        </a>

    </main>

</body>
</html>