<?php
// Destruir todas las variables de sesion
session_start();
session_unset();
session_destroy();

// Redirigir al inicio
header("Location: index.html");
exit();
?>