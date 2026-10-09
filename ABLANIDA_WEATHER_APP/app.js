document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('weather-form');
    const cityInput = document.getElementById('city-input');
    const locationBtn = document.getElementById('location-btn');
    const display = document.getElementById('weather-display');
    const loader = document.getElementById('loader');
    const errorMsg = document.getElementById('error-message');
    const forecastDeck = document.getElementById('forecast-deck');

    // Global Leaflet map instances
    let map = null;
    let marker = null;
    let layerControl = null;

    // Initial weather search on load
    fetchWeather({ city: 'Manila' });

    form.addEventListener('submit', (e) => {
        e.preventDefault();
        const city = cityInput.value.trim();
        if (city) fetchWeather({ city });
    });

    locationBtn.addEventListener('click', () => {
        if (!navigator.geolocation) {
            showError('Geolocation is not supported by your browser.');
            return;
        }

        showLoading();
        navigator.geolocation.getCurrentPosition(
            (pos) => {
                fetchWeather({
                    lat: pos.coords.latitude,
                    lon: pos.coords.longitude
                });
            },
            () => showError('Unable to retrieve your location.')
        );
    });

    async function fetchWeather(params) {
        showLoading();

        try {
            const query = new URLSearchParams(params).toString();
            const response = await fetch(`api.php?${query}`);
            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.error || 'Failed to fetch weather data.');
            }

            renderCurrentWeather(data.current);
            renderForecast(data.forecast);

            hideLoading();
            display.classList.remove('hidden');

            // Render/update interactive Leaflet map and overlays
            updateMap(
                data.current.coord.lat, 
                data.current.coord.lon, 
                data.current.name, 
                Math.round(data.current.main.temp)
            );
        } catch (err) {
            showError(err.message);
        }
    }

    function renderCurrentWeather(curr) {
        document.getElementById('city-name').textContent = `${curr.name}, ${curr.sys.country}`;
        document.getElementById('current-date').textContent = new Date().toLocaleDateString('en-US', {
            weekday: 'short', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit'
        });
        document.getElementById('weather-condition').textContent = curr.weather[0].description;
        document.getElementById('temp-value').textContent = Math.round(curr.main.temp);
        document.getElementById('feels-like').textContent = `${Math.round(curr.main.feels_like)}°C`;
        document.getElementById('humidity').textContent = `${curr.main.humidity}%`;
        document.getElementById('wind-speed').textContent = `${curr.wind.speed} m/s`;
        document.getElementById('pressure').textContent = `${curr.main.pressure} hPa`;

        const iconCode = curr.weather[0].icon;
        document.getElementById('weather-icon').src = `https://openweathermap.org/img/wn/${iconCode}@2x.png`;
    }

    function updateMap(lat, lon, cityName, temp) {
        const apiKey = 'e668829988eecf9eb4bce59b6401ddb6';

        if (!map) {
            map = L.map('map').setView([lat, lon], 10);

            // Base OpenStreetMap tile layer
            const baseMap = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 18,
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);

            // OpenWeatherMap radar overlay tiles
            const rainLayer = L.tileLayer(`https://tile.openweathermap.org/map/precipitation_new/{z}/{x}/{y}.png?appid=${apiKey}`, {
                maxZoom: 18,
                opacity: 0.6,
                attribution: '&copy; OpenWeatherMap'
            });

            const cloudsLayer = L.tileLayer(`https://tile.openweathermap.org/map/clouds_new/{z}/{x}/{y}.png?appid=${apiKey}`, {
                maxZoom: 18,
                opacity: 0.5,
                attribution: '&copy; OpenWeatherMap'
            });

            const tempLayer = L.tileLayer(`https://tile.openweathermap.org/map/temp_new/{z}/{x}/{y}.png?appid=${apiKey}`, {
                maxZoom: 18,
                opacity: 0.5,
                attribution: '&copy; OpenWeatherMap'
            });

            const windLayer = L.tileLayer(`https://tile.openweathermap.org/map/wind_new/{z}/{x}/{y}.png?appid=${apiKey}`, {
                maxZoom: 18,
                opacity: 0.5,
                attribution: '&copy; OpenWeatherMap'
            });

            // Set rain layer active by default
            rainLayer.addTo(map);

            const baseLayers = {
                "Standard Map": baseMap
            };

            const overlays = {
                "🌧️ Rain / Precipitation": rainLayer,
                "☁️ Clouds": cloudsLayer,
                "🌡️ Temperature": tempLayer,
                "💨 Wind Speed": windLayer
            };

            layerControl = L.control.layers(baseLayers, overlays, { collapsed: true }).addTo(map);
            marker = L.marker([lat, lon]).addTo(map);
        } else {
            map.setView([lat, lon], 10);
            marker.setLatLng([lat, lon]);
        }

        marker.bindPopup(`<b>${cityName}</b><br>${temp}°C`).openPopup();

        setTimeout(() => {
            map.invalidateSize();
        }, 200);
    }

    function renderForecast(forecastData) {
        forecastDeck.innerHTML = '';

        const dailyMap = {};
        forecastData.list.forEach((item) => {
            const dateStr = item.dt_txt.split(' ')[0];

            if (!dailyMap[dateStr]) {
                dailyMap[dateStr] = {
                    tempsMax: [],
                    tempsMin: [],
                    icon: item.weather[0].icon,
                    dt: item.dt
                };
            }
            dailyMap[dateStr].tempsMax.push(item.main.temp_max);
            dailyMap[dateStr].tempsMin.push(item.main.temp_min);

            if (item.dt_txt.includes('12:00:00')) {
                dailyMap[dateStr].icon = item.weather[0].icon;
            }
        });

        const days = Object.keys(dailyMap).slice(0, 5);

        days.forEach((dateKey) => {
            const dayData = dailyMap[dateKey];
            const dateObj = new Date(dayData.dt * 1000);
            const dayName = dateObj.toLocaleDateString('en-US', { weekday: 'short' });

            const maxTemp = Math.round(Math.max(...dayData.tempsMax));
            const minTemp = Math.round(Math.min(...dayData.tempsMin));

            const card = document.createElement('div');
            card.className = 'forecast-card';
            card.innerHTML = `
                <div class="forecast-day">${dayName}</div>
                <img class="forecast-icon" src="https://openweathermap.org/img/wn/${dayData.icon}.png" alt="forecast icon">
                <div class="forecast-temp">${maxTemp}° <span class="low">${minTemp}°</span></div>
            `;
            forecastDeck.appendChild(card);
        });
    }

    function showLoading() {
        loader.classList.remove('hidden');
        display.classList.add('hidden');
        errorMsg.classList.add('hidden');
    }

    function hideLoading() {
        loader.classList.add('hidden');
    }

    function showError(message) {
        hideLoading();
        display.classList.add('hidden');
        errorMsg.textContent = message;
        errorMsg.classList.remove('hidden');
    }
});
// BAD - might look at the root domain on live hosts:
fetch('/process_form.php', { ... });

// GOOD - looks relative to the current directory:
fetch('process_form.php', { ... })
  .then(response => {
    // Check if the server actually returned a 200 OK status before parsing JSON
    if (!response.ok) {
      throw new Error(`Server returned status ${response.status}`);
    }
    return response.json();
  })
  .then(data => {
    console.log('Success:', data);
  })
  .catch(error => {
    console.error('Fetch error:', error);
  });
