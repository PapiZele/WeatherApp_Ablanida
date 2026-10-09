<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Weather Dashboard & Interactive Map</title>
    <link rel="stylesheet" href="style.css">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Leaflet Map CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
</head>
<body>
    <div class="weather-wrapper">
        <header class="search-box">
            <form id="weather-form">
                <input type="text" id="city-input" placeholder="Search city or location..." autocomplete="off">
                <button type="submit" aria-label="Search">🔍</button>
            </form>
            <button id="location-btn" title="Use current location">📍</button>
        </header>

        <div id="loader" class="loader hidden"></div>
        <div id="error-message" class="error-card hidden"></div>

        <main id="weather-display" class="hidden">
            <!-- Main Hero Card -->
            <section class="card current-weather-card">
                <div class="card-header">
                    <div>
                        <h2 id="city-name">--</h2>
                        <p id="current-date" class="subtitle">--</p>
                    </div>
                    <span class="badge" id="weather-condition">--</span>
                </div>

                <div class="hero-body">
                    <img id="weather-icon" src="" alt="Weather condition icon">
                    <div class="temp-group">
                        <span id="temp-value" class="temp-num">--</span>
                        <span class="temp-unit">°C</span>
                    </div>
                </div>

                <div class="metrics-grid">
                    <div class="metric-item">
                        <span class="metric-label">Feels Like</span>
                        <span id="feels-like" class="metric-val">--°C</span>
                    </div>
                    <div class="metric-item">
                        <span class="metric-label">Humidity</span>
                        <span id="humidity" class="metric-val">--%</span>
                    </div>
                    <div class="metric-item">
                        <span class="metric-label">Wind</span>
                        <span id="wind-speed" class="metric-val">-- m/s</span>
                    </div>
                    <div class="metric-item">
                        <span class="metric-label">Pressure</span>
                        <span id="pressure" class="metric-val">-- hPa</span>
                    </div>
                </div>
            </section>

            <!-- Location Map Card -->
            <section class="card map-card">
                <h3 class="section-title">Location Map</h3>
                <div id="map"></div>
            </section>

            <!-- 5-Day Extended Forecast Deck -->
            <section class="forecast-section">
                <h3 class="section-title">5-Day Forecast</h3>
                <div id="forecast-deck" class="forecast-deck">
                    <!-- Dynamic forecast cards injected here -->
                </div>
            </section>
        </main>
    </div>

    <!-- Leaflet Map JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script src="app.js"></script>
</body>
</html>