(function () {
    'use strict';

    var headerWeather = document.getElementById('header-weather');

    if (!headerWeather) {
        return;
    }

    var apiUrl = headerWeather.getAttribute('data-api-url') || '';
    var geolocationEnabled = headerWeather.getAttribute('data-geolocation-enabled') === '1';

    var todayIcon = document.getElementById('header-weather-today-icon');
    var todayTemp = document.getElementById('header-weather-today-temp');
    var todayLink = document.getElementById('header-weather-today-link');
    var nextList = document.getElementById('header-weather-next');
    var supportedIconSlugs = {
        'wi-day-sunny': true,
        'wi-day-cloudy': true,
        'wi-cloudy': true,
        'wi-fog': true,
        'wi-sprinkle': true,
        'wi-rain': true,
        'wi-snow': true,
        'wi-showers': true,
        'wi-thunderstorm': true
    };

    if (!apiUrl || !geolocationEnabled || !navigator.geolocation) {
        return;
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function resolveWeekday(day) {
        if (day && typeof day.weekday === 'string' && day.weekday.trim() !== '') {
            return day.weekday.trim();
        }

        if (day && typeof day.date === 'string' && day.date.trim() !== '') {
            var date = new Date(day.date + 'T12:00:00');
            if (!Number.isNaN(date.getTime())) {
                return date.toLocaleDateString('nl-NL', { weekday: 'long' });
            }
        }

        return '';
    }

    function resolveIconSlug(day) {
        var slug = day && typeof day.weather_icon_slug === 'string' ? day.weather_icon_slug.trim() : '';

        if (supportedIconSlugs[slug]) {
            return slug;
        }

        return 'wi-cloudy';
    }

    function buildIconMarkup(day, size) {
        var slug = resolveIconSlug(day);

        return '<span class="weather-icon weather-icon--' + size + ' ' + slug + '" aria-hidden="true"></span>';
    }

    function renderForecast(days) {
        if (!Array.isArray(days) || days.length === 0) {
            return;
        }

        var today = days[0];
        var nextDays = days.slice(1, 4);

        if (todayIcon) {
            todayIcon.className = 'weather-icon weather-icon--today ' + resolveIconSlug(today);
            todayIcon.setAttribute('aria-hidden', 'true');
            todayIcon.textContent = '';
        }

        if (todayTemp) {
            todayTemp.textContent = String(Math.round(Number(today.max_temp))) + '℃';
        }

        if (nextList) {
            nextList.innerHTML = nextDays.map(function (day) {
                return [
                    '<li class="header-weather-next-item">',
                    '<span class="header-weather-next-day">' + escapeHtml(resolveWeekday(day)) + '</span>',
                    '<span class="header-weather-next-main">',
                    buildIconMarkup(day, 'next'),
                    '<span>' + escapeHtml(Math.round(Number(day.max_temp))) + '℃</span>',
                    '</span>',
                    '</li>'
                ].join('');
            }).join('');
        }
    }

    function fetchLocationWeather(latitude, longitude) {
        var params = new URLSearchParams({
            lat: String(latitude),
            lon: String(longitude),
            location_label: 'Jouw locatie'
        });

        fetch(apiUrl + '?' + params.toString(), {
            method: 'GET',
            headers: {
                Accept: 'application/json'
            },
            credentials: 'same-origin'
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Weather request failed with status ' + response.status);
                }

                return response.json();
            })
            .then(function (payload) {
                if (!payload || !payload.data) {
                    return;
                }

                renderForecast(payload.data.days || []);

                if (todayLink && payload.data.buienradar_url) {
                    todayLink.setAttribute('href', payload.data.buienradar_url);
                }
            })
            .catch(function () {
                // Keep fallback weather when geolocation weather fails.
            });
    }

    navigator.geolocation.getCurrentPosition(
        function (position) {
            fetchLocationWeather(position.coords.latitude, position.coords.longitude);
        },
        function () {
            // Keep fallback weather when user denies geolocation.
        },
        {
            enableHighAccuracy: false,
            timeout: 6000,
            maximumAge: 300000
        }
    );
})();
