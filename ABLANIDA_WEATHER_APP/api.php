<?php
// Suppress inline HTML error formatting to protect JSON integrity
error_reporting(0);
ini_set('display_errors', '0');

header('Content-Type: application/json; charset=utf-8');

// --- CONFIGURATION ---
define('API_KEY', 'e668829988eecf9eb4bce59b6401ddb6');
define('CACHE_DIR', __DIR__ . '/cache/');
define('CACHE_TIME', 900); // 15 minutes cache

if (!is_dir(CACHE_DIR)) {
    @mkdir(CACHE_DIR, 0755, true);
}

// Extract and validate parameters
$city = isset($_GET['city']) ? trim($_GET['city']) : '';
$lat = isset($_GET['lat']) ? filter_var($_GET['lat'], FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE) : null;
$lon = isset($_GET['lon']) ? filter_var($_GET['lon'], FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE) : null;

if (empty($city) && ($lat === null || $lon === null)) {
    http_response_code(400);
    echo json_encode(['error' => 'Please provide a valid city name or coordinates.']);
    exit;
}

// Generate cache key
$queryParam = !empty($city) ? 'q=' . urlencode($city) : "lat={$lat}&lon={$lon}";
$cacheKey = 'weather_forecast_' . md5(strtolower($queryParam));
$cacheFile = CACHE_DIR . $cacheKey . '.json';

// Serve valid cache if fresh
if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < CACHE_TIME)) {
    $cachedData = file_get_contents($cacheFile);
    if ($cachedData !== false) {
        $json = json_decode($cachedData, true);
        if (isset($json['current']['cod']) && (int)$json['current']['cod'] === 200) {
            echo $cachedData;
            exit;
        } else {
            @unlink($cacheFile);
        }
    }
}

// OpenWeatherMap API URLs
$currentUrl = "https://api.openweathermap.org/data/2.5/weather?{$queryParam}&units=metric&appid=" . API_KEY;
$forecastUrl = "https://api.openweathermap.org/data/2.5/forecast?{$queryParam}&units=metric&appid=" . API_KEY;

// Multi-cURL execution for concurrent fetching
$mh = curl_multi_init();

$chCurrent = curl_init($currentUrl);
curl_setopt_array($chCurrent, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 8,
    CURLOPT_SSL_VERIFYPEER => true
]);
curl_multi_add_handle($mh, $chCurrent);

$chForecast = curl_init($forecastUrl);
curl_setopt_array($chForecast, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 8,
    CURLOPT_SSL_VERIFYPEER => true
]);
curl_multi_add_handle($mh, $chForecast);

$running = null;
do {
    $status = curl_multi_exec($mh, $running);
    if ($running > 0) {
        curl_multi_select($mh);
    }
} while ($running > 0 && $status === CURLM_OK);

$rawCurrent = curl_multi_getcontent($chCurrent);
$rawForecast = curl_multi_getcontent($chForecast);

curl_multi_remove_handle($mh, $chCurrent);
curl_multi_remove_handle($mh, $chForecast);
curl_multi_close($mh);

$resCurrent = json_decode($rawCurrent, true);
$resForecast = json_decode($rawForecast, true);

// Validate responses
$currentCode = (int)($resCurrent['cod'] ?? 500);
$forecastCode = (int)($resForecast['cod'] ?? 500);

if ($currentCode === 200 && $forecastCode === 200) {
    $combinedData = json_encode([
        'current' => $resCurrent,
        'forecast' => $resForecast
    ]);
    @file_put_contents($cacheFile, $combinedData);
    echo $combinedData;
    exit;
}

if (file_exists($cacheFile)) {
    @unlink($cacheFile);
}

$httpCode = ($currentCode !== 200) ? $currentCode : $forecastCode;
http_response_code($httpCode >= 400 && $httpCode < 600 ? $httpCode : 500);

$errorMsg = $resCurrent['message'] ?? $resForecast['message'] ?? 'Unable to fetch weather data.';
echo json_encode(['error' => ucfirst($errorMsg)]);
exit;
