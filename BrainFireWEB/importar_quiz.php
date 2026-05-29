<?php
session_start();
include 'conexion.php';

// Validar acceso para Admin o Maestro
if (!isset($_SESSION['user_id']) || ($_SESSION['rol'] !== 'admin' && $_SESSION['rol'] !== 'maestro')) {
    header("Location: semestres.php");
    exit();
}

// Consultar materias actuales para el selector
$query_m = "SELECT m.id_materia, m.nombre_materia, s.nombre_semestre FROM materias m JOIN semestres s ON m.id_semestre = s.id_semestre ORDER BY s.id_semestre, m.id_materia";
$result_m = $conn->query($query_m);

// Consultar quices existentes para el candado de prerrequisitos
$query_quices = "SELECT q.id_quiz, q.nombre_quiz, m.nombre_materia FROM quices q JOIN materias m ON q.id_materia = m.id_materia ORDER BY m.nombre_materia, q.nombre_quiz";
$res_quices_existentes = $conn->query($query_quices);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>BRAIN FIRE - Importar Quiz con IA</title>
    <link rel="stylesheet" href="style-inicio.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .import-container { max-width: 700px; margin: 40px auto; background: white; padding: 40px; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 22px; }
        .form-group label { display: block; font-weight: bold; margin-bottom: 8px; color: #4a2450; }
        .form-control { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 8px; font-size: 16px; font-family: inherit; box-sizing: border-box; }
        .seccion-nueva-materia { display: none; background: #fdf6fd; padding: 20px; border-radius: 10px; border: 1px dashed #8a3592; margin-top: 15px; }
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

    <main class="import-container">
        <h2 style="text-align: center; color: #612766; margin-bottom: 10px;">Generador de Quizzes con IA</h2>
        <p style="text-align: center; color: #666; margin-bottom: 30px;">Sube un documento PDF o DOCX para extraer preguntas automaticamente.</p>

        <form action="procesar_ia.php" method="POST" enctype="multipart/form-data" style="background:transparent; border:none; box-shadow:none; padding:0; margin:0; width:100%;">
            
            <div class="form-group">
                <label>Selecciona la Materia destino:</label>
                <select name="materia_opcion" id="materia_opcion" class="form-control" onchange="evaluarMateria(this.value)" required>
                    <option value="" disabled selected>-- Selecciona una opcion --</option>
                    <option value="NUEVA_MATERIA">-- CREAR NUEVA MATERIA --</option>
                    <?php while($row = $result_m->fetch_assoc()): ?>
                        <option value="<?php echo $row['id_materia']; ?>">
                            <?php echo $row['nombre_semestre'] . " - " . $row['nombre_materia']; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="seccion-nueva-materia" id="bloque_nueva_materia">
                <h4 style="color: #612766; margin-top: 0; margin-bottom: 15px;">Datos de la Nueva Materia</h4>
                <div class="form-group">
                    <label>Nombre de la Materia:</label>
                    <input type="text" name="nueva_materia_nombre" id="nueva_materia_nombre" class="form-control" placeholder="Ej. Arquitectura de Software">
                </div>
                <div class="form-group">
                    <label>Asignar al Semestre:</label>
                    <select name="nueva_materia_semestre" id="nueva_materia_semestre" class="form-control">
                        <?php for($i=1; $i<=8; $i++): ?>
                            <option value="<?php echo $i; ?>">Semestre <?php echo $i; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>

            <div style="background-color: #fdf6fd; border: 1px solid #8a3592; padding: 15px; border-radius: 8px; margin-bottom: 22px;">
                <h4 style="color: #612766; margin-top: 0; margin-bottom: 15px;"><i class="fa-solid fa-cogs"></i> Configuracion del Quiz / Parcial</h4>
                
                <div class="form-group" style="margin-bottom: 10px;">
                    <label style="font-size: 14px;">Nombre del Cuestionario:</label>
                    <input type="text" name="nombre_quiz" class="form-control" placeholder="Ej: Segundo Parcial" required>
                </div>

                <div class="form-group" style="margin-bottom: 10px;">
                    <label style="font-size: 14px;">¿Depende de aprobar un quiz anterior? (Candado):</label>
                    <select name="id_quiz_previo" class="form-control">
                        <option value="0">Ninguno (Acceso libre)</option>
                        <?php while($q = $res_quices_existentes->fetch_assoc()): ?>
                            <option value="<?php echo $q['id_quiz']; ?>">
                                [<?php echo htmlspecialchars($q['nombre_materia']); ?>] - <?php echo htmlspecialchars($q['nombre_quiz']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label style="font-size: 14px;">Porcentaje minimo requerido (%):</label>
                    <input type="number" name="porcentaje_requerido" class="form-control" min="0" max="100" value="0">
                </div>
            </div>
            <div class="form-group">
                <label>Cantidad de preguntas a generar:</label>
                <input type="number" name="cantidad_preguntas" class="form-control" min="1" max="20" value="5" required>
            </div>

            <div class="form-group">
                <label>Archivo de Origen (PDF o DOCX):</label>
                <input type="file" name="documento_ia" class="form-control" accept=".pdf,.docx" required>
            </div>

            <button type="submit" class="btn-ingresar" style="width: 100%; padding: 15px; font-size: 18px;">Procesar Documento</button>
        </form>
    </main>

    <script>
        function evaluarMateria(valor) {
            var bloque = document.getElementById('bloque_nueva_materia');
            var txtNombre = document.getElementById('nueva_materia_nombre');
            
            if(valor === 'NUEVA_MATERIA') {
                bloque.style.display = 'block';
                txtNombre.required = true;
            } else {
                bloque.style.display = 'none';
                txtNombre.required = false;
            }
        }
    </script>
</body>
</html>