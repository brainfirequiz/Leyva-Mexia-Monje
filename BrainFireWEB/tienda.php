<?php
session_start();
include 'conexion.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: inicio.html");
    exit();
}

$id_usuario = $_SESSION['user_id'];

// Obtener datos actuales del usuario
$stmt = $conn->prepare("SELECT wallet, color_nombre, icono_perfil FROM users WHERE id = ?");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$res = $stmt->get_result();
$user_data = $res->fetch_assoc();
$mi_wallet = $user_data['wallet'];
$mi_color = $user_data['color_nombre'];
$mi_icono = $user_data['icono_perfil'];
$stmt->close();

// Procesar compra
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comprar'])) {
    $tipo = $_POST['tipo']; 
    $valor = $_POST['valor'];
    $precio = intval($_POST['precio']);

    if ($mi_wallet >= $precio) {
        $nuevo_wallet = $mi_wallet - $precio;
        
        if ($tipo === 'color') {
            $upd = $conn->prepare("UPDATE users SET wallet = ?, color_nombre = ? WHERE id = ?");
        } else {
            $upd = $conn->prepare("UPDATE users SET wallet = ?, icono_perfil = ? WHERE id = ?");
        }
        
        $upd->bind_param("isi", $nuevo_wallet, $valor, $id_usuario);
        $upd->execute();
        $upd->close();
        
        echo "<script>alert('Compra realizada con exito. Tus cambios se han guardado.'); window.location.href='tienda.php';</script>";
        exit();
    } else {
        echo "<script>alert('No tienes suficientes puntos en tu wallet para este articulo.'); window.location.href='tienda.php';</script>";
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>BRAIN FIRE - Tienda Virtual</title>
    <link rel="stylesheet" href="style-inicio.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .tienda-container { max-width: 1000px; margin: 40px auto; padding: 20px; }
        .wallet-banner { background: linear-gradient(135deg, #8a3592, #4a2450); color: white; padding: 25px; border-radius: 12px; text-align: center; margin-bottom: 40px; box-shadow: 0 4px 15px rgba(0,0,0,0.2); }
        .wallet-puntos { font-size: 36px; font-weight: bold; margin-top: 10px; }
        .seccion-tienda { margin-bottom: 40px; }
        .titulo-seccion { color: #612766; border-bottom: 2px solid #612766; padding-bottom: 10px; margin-bottom: 20px; font-size: 24px; }
        .items-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 20px; }
        
        .item-card { background: white; border-radius: 10px; padding: 20px; text-align: center; box-shadow: 0 4px 10px rgba(0,0,0,0.1); border: 2px solid transparent; transition: 0.3s; }
        .item-card:hover { border-color: #8a3592; transform: translateY(-5px); }
        
        .item-preview { font-size: 50px; margin-bottom: 15px; height: 60px; display: flex; align-items: center; justify-content: center; }
        .item-nombre { font-size: 18px; color: #333; font-weight: bold; margin-bottom: 5px; }
        .item-precio { color: #28a745; font-weight: bold; font-size: 16px; margin-bottom: 15px; }
        
        .btn-comprar { background: #28a745; color: white; border: none; padding: 10px 15px; width: 100%; border-radius: 5px; font-weight: bold; cursor: pointer; transition: 0.2s; }
        .btn-comprar:hover { background: #218838; }
        .btn-equipado { background: #6c757d; color: white; border: none; padding: 10px 15px; width: 100%; border-radius: 5px; font-weight: bold; cursor: not-allowed; }
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

    <main class="tienda-container">
        <div class="wallet-banner">
            <h2>Mi Billetera Virtual</h2>
            <div class="wallet-puntos"><i class="fa-solid fa-coins"></i> <?php echo $mi_wallet; ?> pts</div>
        </div>

        <div class="seccion-tienda">
            <h2 class="titulo-seccion">Colores Especiales para tu Nombre</h2>
            <div class="items-grid">
                
                <?php 
                $colores = [
                    ['nombre' => 'Rojo Carmesi', 'hex' => '#dc3545', 'precio' => 1000],
                    ['nombre' => 'Azul Oceano', 'hex' => '#007bff', 'precio' => 1000],
                    ['nombre' => 'Dorado Leyenda', 'hex' => '#ffc107', 'precio' => 2500],
                    ['nombre' => 'Verde Matrix', 'hex' => '#28a745', 'precio' => 1000]
                ];
                
                foreach ($colores as $col): 
                    $es_equipado = ($mi_color === $col['hex']);
                ?>
                <div class="item-card">
                    <div class="item-preview" style="color: <?php echo $col['hex']; ?>;">
                        <i class="fa-solid fa-font"></i>
                    </div>
                    <div class="item-nombre"><?php echo $col['nombre']; ?></div>
                    <div class="item-precio"><?php echo $col['precio']; ?> pts</div>
                    
                    <form method="POST" action="tienda.php">
                        <input type="hidden" name="tipo" value="color">
                        <input type="hidden" name="valor" value="<?php echo $col['hex']; ?>">
                        <input type="hidden" name="precio" value="<?php echo $col['precio']; ?>">
                        <?php if ($es_equipado): ?>
                            <button type="button" class="btn-equipado" disabled>Equipado</button>
                        <?php else: ?>
                            <button type="submit" name="comprar" class="btn-comprar">Comprar</button>
                        <?php endif; ?>
                    </form>
                </div>
                <?php endforeach; ?>
                
            </div>
        </div>

        <div class="seccion-tienda">
            <h2 class="titulo-seccion">Iconos de Perfil Exclusivos</h2>
            <div class="items-grid">
                
                <?php 
                $iconos = [
                    ['nombre' => 'Robot IA', 'clase' => 'fa-robot', 'precio' => 1500],
                    ['nombre' => 'Fantasma', 'clase' => 'fa-ghost', 'precio' => 1500],
                    ['nombre' => 'Dragon', 'clase' => 'fa-dragon', 'precio' => 3000],
                    ['nombre' => 'Ninja', 'clase' => 'fa-user-ninja', 'precio' => 2000]
                ];
                
                foreach ($iconos as $ico): 
                    $es_equipado = ($mi_icono === $ico['clase']);
                ?>
                <div class="item-card">
                    <div class="item-preview" style="color: #612766;">
                        <i class="fa-solid <?php echo $ico['clase']; ?>"></i>
                    </div>
                    <div class="item-nombre"><?php echo $ico['nombre']; ?></div>
                    <div class="item-precio"><?php echo $ico['precio']; ?> pts</div>
                    
                    <form method="POST" action="tienda.php">
                        <input type="hidden" name="tipo" value="icono">
                        <input type="hidden" name="valor" value="<?php echo $ico['clase']; ?>">
                        <input type="hidden" name="precio" value="<?php echo $ico['precio']; ?>">
                        <?php if ($es_equipado): ?>
                            <button type="button" class="btn-equipado" disabled>Equipado</button>
                        <?php else: ?>
                            <button type="submit" name="comprar" class="btn-comprar">Comprar</button>
                        <?php endif; ?>
                    </form>
                </div>
                <?php endforeach; ?>
                
            </div>
        </div>

    </main>
</body>
</html>