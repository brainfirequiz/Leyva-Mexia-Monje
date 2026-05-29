<?php
session_start();
include 'conexion.php';

// Consultamos los 10 mejores puntajes
$query_ranking = "SELECT username, total_score, color_nombre, icono_perfil FROM users ORDER BY total_score DESC LIMIT 10";
$result_ranking = $conn->query($query_ranking);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ranking - Brain Fire</title>
    <link rel="stylesheet" href="style-inicio.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body style="background-color: #fae6f6;">

    <header class="header-index">
        <div class="logo">
            <span class="brain">Brain</span><span class="fire">Fire</span>
        </div>
        <a href="semestres.php" style="color: white; text-decoration: none;">Volver</a>
    </header>

    <div class="ranking-container" style="max-width: 600px; margin: 50px auto; background: white; padding: 30px; border-radius: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
        <h2 style="text-align: center; color: #612766; margin-bottom: 20px;">Top 10 Estudiantes</h2>
        
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 2px solid #612766;">
                    <th style="padding: 10px; text-align: left;">Posicion</th>
                    <th style="padding: 10px; text-align: left;">Usuario</th>
                    <th style="padding: 10px; text-align: right;">Puntaje</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $pos = 1; 
                while($row = $result_ranking->fetch_assoc()): 
                ?>
                    <tr>
                        <td style="padding: 10px; font-weight: bold; color: #333;">
                            #<?php echo $pos; ?>
                        </td>
                        
                        <td style="padding: 10px; color: <?php echo $row['color_nombre']; ?>; font-weight: bold;">
                            <i class="fa-solid <?php echo $row['icono_perfil']; ?>" style="margin-right: 10px; font-size: 20px;"></i>
                            <?php echo htmlspecialchars($row['username']); ?>
                        </td>
                        
                        <td style="padding: 10px; text-align: right;">
                            <?php echo $row['total_score']; ?> pts
                        </td>
                    </tr>
                <?php 
                    $pos++; // Aumenta el numero de posicion para el siguiente alumno
                endwhile; 
                ?>
            </tbody>
        </table>
    </div>
</body>
</html>