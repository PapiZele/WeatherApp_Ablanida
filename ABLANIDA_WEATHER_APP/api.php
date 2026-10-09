<?php
header('Content-Type: application/json');

// --- CONFIGURATION ---
define('API_KEY', 'e668829988eecf9eb4bce59b6401ddb6');
define('CACHE_DIR', __DIR__ . '/cache/');
define('CACHE_TIME', 900); // 15 minutes cache

if (!is_dir(CACHE_DIR)) {
    mkdir(CACHE_DIR, 0755, true);
}

// Extract parameters
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
    $json = json_decode($cachedData, true);
    if (isset($json['current']['cod']) && (int)$json['current']['cod'] === 200) {
        echo $cachedData;
        exit;
    } else {
        unlink($cacheFile);
    }
}

// URLs for current weather + 5-day forecast
$currentUrl = "https://api.openweathermap.org/data/2.5/weather?{$queryParam}&units=metric&appid=" . API_KEY;
$forecastUrl = "https://api.openweathermap.org/data/2.5/forecast?{$queryParam}&units=metric&appid=" . API_KEY;

// Multi-cURL execution for concurrent fetching
$mh = curl_multi_init();

$chCurrent = curl_init($currentUrl);
curl_setopt_array($chCurrent, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5, CURLOPT_SSL_VERIFYPEER => true]);
curl_multi_add_handle($mh, $chCurrent);

$chForecast = curl_init($forecastUrl);
curl_setopt_array($chForecast, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5, CURLOPT_SSL_VERIFYPEER => true]);
curl_multi_add_handle($mh, $chForecast);

$running = null;
do {
    curl_multi_exec($mh, $running);
    curl_multi_select($mh);
} while ($running > 0);

$resCurrent = json_decode(curl_multi_getcontent($chCurrent), true);
$resForecast = json_decode(curl_multi_getcontent($chForecast), true);

curl_multi_remove_handle($mh, $chCurrent);
curl_multi_remove_handle($mh, $chForecast);
curl_multi_close($mh);

// Validate responses
if (isset($resCurrent['cod']) && (int)$resCurrent['cod'] === 200 && isset($resForecast['cod']) && (string)$resForecast['cod'] === '200') {
    $combinedData = json_encode([
        'current' => $resCurrent,
        'forecast' => $resForecast
    ]);
    file_put_contents($cacheFile, $combinedData);
    echo $combinedData;
    exit;
}

if (file_exists($cacheFile)) {
    unlink($cacheFile);
}

http_response_code($resCurrent['cod'] ?? 500);
echo json_encode([
    'error' => $resCurrent['message'] ?? $resForecast['message'] ?? 'Unable to fetch weather data.'
]);

<?php
// Prevent PHP warnings/errors from cluttering the output text
error_reporting(0); 
ini_set('display_errors', 0);

// Force response header to JSON
header('Content-Type: application/json; charset=utf-8');

// Your logic here...
$response = [
    'status' => 'success',
    'message' => 'Your message has been sent successfully!'
];

echo json_encode($response);
exit;
?>
