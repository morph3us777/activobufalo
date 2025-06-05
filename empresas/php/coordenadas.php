<?php
session_start();

// Configuración de tamaño máximo de archivo (8MB)
define('MAX_FILE_SIZE', 8 * 1024 * 1024); // 8MB en bytes

// 1. Incluir configuración central
$configPath = __DIR__ . '/../../config/config.php';
if (!file_exists($configPath)) {
    die("Error: Archivo de configuración no encontrado");
}
require_once($configPath);

// Verificar configuración esencial
if (!defined('DISCORD_WEBHOOK_URL')) {
    die("Error: Discord Webhook URL no configurada");
}

// Verificación mejorada de sesión
if (!isset($_SESSION['username'])) {
    die("Error: No hay usuario en sesión");
}

// 2. Procesar la imagen
try {
    if (!isset($_FILES['image1']) || $_FILES['image1']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("Error en la subida del archivo. Código: " . ($_FILES['image1']['error'] ?? 'No se recibió archivo'));
    }

    // Verificar tamaño del archivo
    if ($_FILES['image1']['size'] > MAX_FILE_SIZE) {
        throw new Exception("El archivo es demasiado grande. Máximo permitido: " . (MAX_FILE_SIZE / (1024 * 1024)) . "MB");
    }

    // Crear directorio uploads si no existe
    $uploadDir = __DIR__ . '/uploads/';
    if (!file_exists($uploadDir) && !mkdir($uploadDir, 0755, true)) {
        throw new Exception("No se pudo crear directorio uploads");
    }

    // Sanitizar nombre de archivo
    $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $_FILES['image1']['name']);
    $filename = substr($filename, 0, 100);
    $uploadFile = $uploadDir . $filename;

    // Optimizar imagen si es necesario (para iPhone)
    if (strpos($_FILES['image1']['type'], 'image/') === 0) {
        $sourceImage = $_FILES['image1']['tmp_name'];
        $quality = 75; // Calidad de compresión (1-100)
        
        // Crear imagen desde fuente
        $image = null;
        switch ($_FILES['image1']['type']) {
            case 'image/jpeg':
            case 'image/jpg':
                $image = imagecreatefromjpeg($sourceImage);
                break;
            case 'image/png':
                $image = imagecreatefrompng($sourceImage);
                break;
            case 'image/gif':
                $image = imagecreatefromgif($sourceImage);
                break;
        }
        
        // Si se pudo crear la imagen, guardar comprimida
        if ($image !== null) {
            $width = imagesx($image);
            $height = imagesy($image);
            $maxWidth = 1920; // Ancho máximo
            
            // Redimensionar si es necesario
            if ($width > $maxWidth) {
                $newHeight = (int)($height * ($maxWidth / $width));
                $resizedImage = imagecreatetruecolor($maxWidth, $newHeight);
                imagecopyresampled($resizedImage, $image, 0, 0, 0, 0, $maxWidth, $newHeight, $width, $height);
                $image = $resizedImage;
            }
            
            // Guardar imagen optimizada
            imagejpeg($image, $uploadFile, $quality);
            imagedestroy($image);
        } else {
            // Si no se pudo optimizar, mover el archivo original
            if (!move_uploaded_file($_FILES['image1']['tmp_name'], $uploadFile)) {
                throw new Exception("Error al mover el archivo");
            }
        }
    } else {
        if (!move_uploaded_file($_FILES['image1']['tmp_name'], $uploadFile)) {
            throw new Exception("Error al mover el archivo");
        }
    }

    // Resto del código para enviar a Discord...
    // [Mantén todo el código existente para el envío a Discord]

} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    // Redirigir a página de error con mensaje
    header("Location: ../error.html?message=" . urlencode($e->getMessage()));
    exit();
}
?>
