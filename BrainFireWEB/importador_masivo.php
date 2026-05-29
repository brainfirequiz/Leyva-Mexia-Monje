<?php
include 'conexion.php';

$ruta = 'banco.txt';
if(!file_exists($ruta)) {
    die("Error: No se encontro el archivo banco.txt");
}

$texto = file_get_contents($ruta);
$texto = str_replace("\r\n", "\n", $texto);
$texto = str_replace("\r", "\n", $texto);
$lineas = explode("\n", $texto);

$id_semestre_actual = 1;
$id_quiz_actual = 0;
$pregunta_actual = "";
$opciones = [];
$preguntas_insertadas = 0;

$patrones_materias = [
    '/^Matem.*ticas Discretas$/i',
    '/^Algoritmos y L.*gica Computacional$/i',
    '/^Programaci.*n$/i',
    '/^Sistemas Operativos$/i',
    '/^Ensamblador \(Lenguaje Ensamblador\)$/i',
    '/^Estructura de Datos$/i',
    '/^Fundamentos de Base de Datos$/i',
    '/^Programaci.*n Orientada a Objetos \(POO\)$/i',
    '/^Redes y Transmisi.*n de Datos$/i',
    '/^Base de Datos Distribuidas$/i',
    '/^Administraci.*n de Sistemas$/i',
    '/^Introducci.*n al Desarrollo Web$/i'
];

$nombres_oficiales = [
    "Matematicas Discretas",
    "Algoritmos y Logica Computacional",
    "Programacion",
    "Sistemas Operativos",
    "Ensamblador",
    "Estructura de Datos",
    "Fundamentos de Base de Datos",
    "POO",
    "Redes y Transmision de Datos",
    "Base de Datos Distribuidas",
    "Administracion de Sistemas",
    "Introduccion al Desarrollo Web"
];

foreach($lineas as $linea) {
    $linea = trim($linea);
    if($linea === '') continue;

    if (strpos($linea, 'o') === 0 && (substr($linea, 1, 1) === "\t" || substr($linea, 1, 1) === ' ')) {
        $linea = trim(substr($linea, 1));
    }

    if(preg_match('/^([1-8])(er|do|er|to|mo|vo|no|vo)\s+Semestre/i', $linea, $matches)) {
        $id_semestre_actual = intval($matches[1]);
        continue;
    }

    $es_materia = false;
    $nombre_oficial = "";
    
    foreach($patrones_materias as $index => $patron) {
        if(preg_match($patron, $linea)) {
            $es_materia = true;
            $nombre_oficial = $nombres_oficiales[$index];
            break;
        }
    }

    if($es_materia) {
        $stmt_m = $conn->prepare("SELECT id_materia FROM materias WHERE nombre_materia = ? AND id_semestre = ?");
        $stmt_m->bind_param("si", $nombre_oficial, $id_semestre_actual);
        $stmt_m->execute();
        $res_m = $stmt_m->get_result();
        
        if($res_m->num_rows > 0) {
            $id_materia = $res_m->fetch_assoc()['id_materia'];
        } else {
            $stmt_im = $conn->prepare("INSERT INTO materias (id_semestre, nombre_materia) VALUES (?, ?)");
            $stmt_im->bind_param("is", $id_semestre_actual, $nombre_oficial);
            $stmt_im->execute();
            $id_materia = $stmt_im->insert_id;
        }

        $stmt_q = $conn->prepare("SELECT id_quiz FROM quices WHERE id_materia = ?");
        $stmt_q->bind_param("i", $id_materia);
        $stmt_q->execute();
        $res_q = $stmt_q->get_result();
        
        if($res_q->num_rows > 0) {
            $id_quiz_actual = $res_q->fetch_assoc()['id_quiz'];
        } else {
            // AQUI ESTA LA SOLUCION: Ya no intentamos insertar en la columna 'titulo'
            $stmt_iq = $conn->prepare("INSERT INTO quices (id_materia) VALUES (?)");
            $stmt_iq->bind_param("i", $id_materia);
            $stmt_iq->execute();
            $id_quiz_actual = $stmt_iq->insert_id;
        }
        
        $pregunta_actual = "";
        $opciones = [];
        continue;
    }

    if(preg_match('/^([A-D])\)\s*(.*)/i', $linea, $matches)) {
        $opciones[strtoupper($matches[1])] = $matches[2];
        continue;
    }

    if(preg_match('/^Correcta:\s*([A-D])/i', $linea, $matches)) {
        $letra_correcta = strtoupper($matches[1]);

        if($id_quiz_actual > 0 && $pregunta_actual !== "") {
            $pregunta_limpia = preg_replace('/^\d+\.\s*/', '', trim($pregunta_actual));
            
            $stmt_p = $conn->prepare("INSERT INTO preguntas (id_quiz, texto_pregunta, estado) VALUES (?, ?, 'aprobada')");
            $stmt_p->bind_param("is", $id_quiz_actual, $pregunta_limpia);
            $stmt_p->execute();
            $id_pregunta = $stmt_p->insert_id;

            foreach(['A', 'B', 'C', 'D'] as $letra) {
                if(isset($opciones[$letra])) {
                    $es_corr = ($letra === $letra_correcta) ? 1 : 0;
                    $stmt_o = $conn->prepare("INSERT INTO opciones (id_pregunta, texto_opcion, es_correcta) VALUES (?, ?, ?)");
                    $stmt_o->bind_param("isi", $id_pregunta, $opciones[$letra], $es_corr);
                    $stmt_o->execute();
                }
            }
            $preguntas_insertadas++;
        }
        $pregunta_actual = "";
        $opciones = [];
        continue;
    }

    $pregunta_actual .= $linea . " ";
}

echo "<div style='font-family: Arial; text-align: center; margin-top: 50px;'>";
echo "<h1 style='color: #28a745;'>Importacion Completada Exitosamente</h1>";
echo "<p style='font-size: 18px;'>Se han procesado e insertado <strong>$preguntas_insertadas</strong> preguntas en tu base de datos.</p>";
echo "<br><a href='semestres.php' style='padding: 10px 20px; background: #8a3592; color: white; text-decoration: none; border-radius: 5px; font-weight: bold;'>Volver al Sistema</a>";
echo "</div>";
?>