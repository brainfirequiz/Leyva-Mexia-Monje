<?php
session_start();
include 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: importar_quiz.php");
    exit();
}

// --- CONFIGURACION DE GEMINI API ---
$api_key = 'AIzaSyCqXOaoWcl0wqZtEvuVjUY3T-zF55Ve9uA'; // Reemplaza esto con tu clave real
// -----------------------------------

$materia_opcion = $_POST['materia_opcion'];
$cantidad = intval($_POST['cantidad_preguntas']);

if ($materia_opcion === 'NUEVA_MATERIA') {
    $_SESSION['ia_materia_tipo'] = 'NUEVA';
    $_SESSION['ia_materia_nombre'] = trim($_POST['nueva_materia_nombre']);
    $_SESSION['ia_materia_semestre'] = intval($_POST['nueva_materia_semestre']);
} else {
    $_SESSION['ia_materia_tipo'] = 'EXISTENTE';
    $_SESSION['ia_materia_id'] = intval($materia_opcion);
}

// Preparamos el archivo PDF para enviarlo a Gemini
$archivo_tmp = $_FILES['documento_ia']['tmp_name'];
$tipo_archivo = $_FILES['documento_ia']['type'];

if ($tipo_archivo !== 'application/pdf') {
    die("<script>alert('Por favor, sube un archivo PDF.'); window.history.back();</script>");
}

$archivo_base64 = base64_encode(file_get_contents($archivo_tmp));

// Instruccion estricta para la IA
$prompt = "Actua como un profesor experto. Analiza el documento PDF adjunto y genera EXACTAMENTE $cantidad preguntas de opcion multiple basadas en los hechos y conceptos de su contenido. REGLA ESTRICTA: Formula las preguntas de forma directa y universal. ESTA PROHIBIDO usar frases como 'segun el texto', 'de acuerdo al documento', 'en la lectura' o mencionar que la informacion viene de un archivo. La estructura debe ser exactamente esta: [{\"pregunta\": \"Texto de la pregunta\", \"opciones\": [\"Opcion 1\", \"Opcion 2\", \"Opcion 3\", \"Opcion 4\"], \"correcta\": 0}] donde 'correcta' es el numero de indice (del 0 al 3) que corresponde a la respuesta correcta. No incluyas ningun otro texto, ni saludos, ni formato markdown.";
$data = [
    "contents" => [
        [
            "parts" => [
                ["text" => $prompt],
                [
                    "inline_data" => [
                        "mime_type" => "application/pdf",
                        "data" => $archivo_base64
                    ]
                ]
            ]
        ]
    ],
    "generationConfig" => [
        "response_mime_type" => "application/json",
        "temperature" => 0.2 // Temperatura baja para que sea preciso y no invente cosas
    ]
];

$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-3.1-flash-lite:generateContent?key=" . $api_key;

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

// IMPORTANTE: Descomenta la siguiente linea quitando las dos diagonales 
// si XAMPP te da un error en blanco o error de certificado SSL
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 

$response = curl_exec($ch);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    die("Error de conexion con Gemini: " . $error);
}

$resultado = json_decode($response, true);

// Verificamos si Gemini respondio correctamente
if (isset($resultado['candidates'][0]['content']['parts'][0]['text'])) {
    
    $texto_json = $resultado['candidates'][0]['content']['parts'][0]['text'];
    $preguntas_generadas = json_decode($texto_json, true);

    if (json_last_error() === JSON_ERROR_NONE && is_array($preguntas_generadas)) {
        // Todo salio bien, mandamos al sandbox
        $_SESSION['ia_preguntas_preview'] = $preguntas_generadas;
        header("Location: revisar_importacion.php");
        exit();
    } else {
        die("Error: Gemini no pudo formatear las preguntas. Respuesta: " . htmlspecialchars($texto_json));
    }
} else {
    // Extraemos el error exacto que devuelve Google
    $mensaje_error = isset($resultado['error']['message']) ? $resultado['error']['message'] : "Error desconocido";
    die("<strong>Error real de Google:</strong> " . $mensaje_error . "<br><br><strong>Respuesta de la API:</strong> " . htmlspecialchars($response));
}