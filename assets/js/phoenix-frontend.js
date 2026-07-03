(function () {
    var state = {
        activeTripId: null,
        watchId: null,
        map: null,
        routeLine: null,
        routePoints: [],
    };

    function setMapTrip(text) {
        var node = document.querySelector('[data-phoenix-map-trip]');
        if (!node) {
            return;
        }
        node.textContent = text;
    }

    function setMapPointCount(count) {
        var node = document.querySelector('[data-phoenix-map-points]');
        if (!node) {
            return;
        }
        node.textContent = String(count);
    }

    function setMapDistance(distanceKm) {
        var node = document.querySelector('[data-phoenix-map-distance]');
        if (!node) {
            return;
        }

        var unit = window.bsoPhoenix && window.bsoPhoenix.distanceUnit ? window.bsoPhoenix.distanceUnit : 'km';
        var distance = distanceKm;
        if (unit === 'nm') {
            distance = distanceKm / 1.852;
        }

        node.textContent = distance.toFixed(2) + ' ' + unit;
    }

    function haversineKm(fromLat, fromLng, toLat, toLng) {
        var earthRadiusKm = 6371;
        var dLat = (toLat - fromLat) * Math.PI / 180;
        var dLng = (toLng - fromLng) * Math.PI / 180;
        var a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
            Math.cos(fromLat * Math.PI / 180) * Math.cos(toLat * Math.PI / 180) *
            Math.sin(dLng / 2) * Math.sin(dLng / 2);
        var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

        return earthRadiusKm * c;
    }

    function ensureMap() {
        if (state.map || !window.L) {
            return;
        }

        var mapNode = document.querySelector('[data-phoenix-map]');
        if (!mapNode) {
            return;
        }

        state.map = L.map(mapNode).setView([53.1748, 5.4146], 11);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap-bijdragers',
        }).addTo(state.map);

        state.routeLine = L.polyline([], {
            color: '#0a6a4a',
            weight: 4,
            opacity: 0.9,
        }).addTo(state.map);
    }

    function renderRoute(points) {
        ensureMap();
        if (!state.map || !state.routeLine) {
            return;
        }

        state.routePoints = (points || []).map(function (point) {
            return [parseFloat(point.latitude), parseFloat(point.longitude)];
        }).filter(function (point) {
            return !Number.isNaN(point[0]) && !Number.isNaN(point[1]);
        });

        state.routeLine.setLatLngs(state.routePoints);
        setMapPointCount(state.routePoints.length);
        setMapDistance(calculateRouteDistanceKm(state.routePoints));

        if (state.routePoints.length === 1) {
            state.map.setView(state.routePoints[0], 14);
        }

        if (state.routePoints.length > 1) {
            state.map.fitBounds(state.routeLine.getBounds(), {
                padding: [20, 20],
            });
        }
    }

    function appendRoutePoint(latitude, longitude) {
        ensureMap();
        if (!state.map || !state.routeLine) {
            return;
        }

        var nextPoint = [parseFloat(latitude), parseFloat(longitude)];
        if (Number.isNaN(nextPoint[0]) || Number.isNaN(nextPoint[1])) {
            return;
        }

        state.routePoints.push(nextPoint);
        state.routeLine.addLatLng(nextPoint);
        setMapPointCount(state.routePoints.length);
        setMapDistance(calculateRouteDistanceKm(state.routePoints));

        if (state.routePoints.length === 1) {
            state.map.setView(nextPoint, 14);
        } else {
            state.map.panTo(nextPoint);
        }
    }

    function loadTripRoute(tripId) {
        if (!tripId) {
            return;
        }

        ajaxRequest('bso_phoenix_get_trip_trackpoints', {
            trip_id: tripId,
        }).then(function (result) {
            if (!result || !result.success || !result.data) {
                return;
            }

            renderRoute(result.data.trackpoints || []);
            setMapTrip('Trip #' + tripId + ' (' + ((result.data.trip && result.data.trip.status) || 'onbekend') + ')');
        }).catch(function () {
            setFeedback('Laatste route laden mislukt.');
        });
    }

    function calculateRouteDistanceKm(points) {
        var distance = 0;
        var index;

        for (index = 1; index < points.length; index += 1) {
            distance += haversineKm(points[index - 1][0], points[index - 1][1], points[index][0], points[index][1]);
        }

        return distance;
    }

    function setFeedback(text) {
        var node = document.querySelector('[data-phoenix-feedback]');
        if (!node) {
            return;
        }
        node.textContent = text;
    }

    function setStatus(nextStatus) {
        var node = document.querySelector('[data-phoenix-status]');
        if (!node) {
            return;
        }
        node.textContent = nextStatus;
    }

    function ajaxRequest(action, payload) {
        if (!window.bsoPhoenix || !window.bsoPhoenix.ajaxUrl) {
            return Promise.reject(new Error('Missing bsoPhoenix config'));
        }

        var formData = new URLSearchParams();
        formData.append('action', action);
        formData.append('nonce', window.bsoPhoenix && window.bsoPhoenix.nonce ? window.bsoPhoenix.nonce : '');

        Object.keys(payload || {}).forEach(function (key) {
            if (payload[key] !== null && payload[key] !== undefined) {
                formData.append(key, String(payload[key]));
            }
        });

        return fetch(window.bsoPhoenix.ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            },
            body: formData.toString(),
            credentials: 'same-origin',
        }).then(function (response) {
            return response.json();
        });
    }

    function sendTrackpoint(position) {
        if (!state.activeTripId) {
            return;
        }

        var coords = position.coords;
        appendRoutePoint(coords.latitude, coords.longitude);

        ajaxRequest('bso_phoenix_trackpoint', {
            trip_id: state.activeTripId,
            latitude: coords.latitude,
            longitude: coords.longitude,
            altitude: coords.altitude,
            speed: coords.speed ? coords.speed * 3.6 : null,
            accuracy: coords.accuracy,
            recorded_at: Date.now(),
        }).catch(function () {
            setFeedback('Trackpoint opslaan mislukt. Controleer verbinding.');
        });
    }

    function startGeolocation() {
        if (!('geolocation' in navigator)) {
            setFeedback('GPS is niet beschikbaar op dit apparaat.');
            return;
        }

        state.watchId = navigator.geolocation.watchPosition(
            sendTrackpoint,
            function () {
                setFeedback('Geen GPS-signaal. Controleer locatiepermissies.');
            },
            {
                enableHighAccuracy: true,
                maximumAge: window.bsoPhoenix && window.bsoPhoenix.gpsIntervalMs ? window.bsoPhoenix.gpsIntervalMs : 10000,
                timeout: 10000,
            }
        );
    }

    function stopGeolocation() {
        if (state.watchId !== null && 'geolocation' in navigator) {
            navigator.geolocation.clearWatch(state.watchId);
            state.watchId = null;
        }
    }

    function handleStart() {
        ajaxRequest('bso_phoenix_start_trip', {
            boat_id: window.bsoPhoenix && window.bsoPhoenix.defaultBoatId ? window.bsoPhoenix.defaultBoatId : 1,
        }).then(function (result) {
            if (!result || !result.success) {
                setFeedback('Start route mislukt.');
                return;
            }

            state.activeTripId = result.data.trip_id;
            state.routePoints = [];
            renderRoute([]);
            setStatus('Actief');
            setMapTrip('Trip #' + state.activeTripId + ' (actief)');
            setFeedback('Route gestart. GPS tracking is actief.');
            startGeolocation();
        }).catch(function () {
            setFeedback('Start route mislukt. Controleer sessie of permissies.');
        });
    }

    function handleStop() {
        if (!state.activeTripId) {
            setFeedback('Er is geen actieve route om te stoppen.');
            return;
        }

        ajaxRequest('bso_phoenix_stop_trip', {
            trip_id: state.activeTripId,
        }).then(function (result) {
            if (!result || !result.success) {
                setFeedback('Stop route mislukt.');
                return;
            }

            stopGeolocation();
            loadTripRoute(state.activeTripId);
            state.activeTripId = null;
            setStatus('Gestopt');
            setFeedback('Route gestopt en opgeslagen.');
        }).catch(function () {
            setFeedback('Stop route mislukt. Controleer verbinding.');
        });
    }

    function setLogFeedback(text) {
        var node = document.querySelector('[data-phoenix-log-feedback]');
        if (!node) {
            return;
        }
        node.textContent = text;
    }

    function handleLogSubmit(event) {
        event.preventDefault();

        var textNode = document.querySelector('[data-phoenix-log-text]');
        var fileNode = document.querySelector('[data-phoenix-log-photos]');
        var text = textNode ? textNode.value.trim() : '';

        if (!text) {
            setLogFeedback('Vul een notitie in voor het opslaan.');
            return;
        }

        if (!window.bsoPhoenix || !window.bsoPhoenix.ajaxUrl) {
            setLogFeedback('Configuratie ontbreekt.');
            return;
        }

        var formData = new FormData();
        formData.append('action', 'bso_phoenix_create_log');
        formData.append('nonce', window.bsoPhoenix.logNonce || '');
        formData.append('entry_text', text);
        formData.append('boat_id', String(window.bsoPhoenix.defaultBoatId || 1));
        formData.append('trip_id', String(state.activeTripId || ''));

        if (fileNode && fileNode.files) {
            Array.prototype.forEach.call(fileNode.files, function (file) {
                formData.append('log_photos[]', file);
            });
        }

        fetch(window.bsoPhoenix.ajaxUrl, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
        }).then(function (response) {
            return response.json();
        }).then(function (result) {
            if (!result || !result.success) {
                setLogFeedback('Opslaan mislukt.');
                return;
            }

            if (textNode) {
                textNode.value = '';
            }
            if (fileNode) {
                fileNode.value = '';
            }
            setLogFeedback('Notitie opgeslagen' + ((result.data && result.data.attachment_ids && result.data.attachment_ids.length) ? ' met foto\'s.' : '.'));
        }).catch(function () {
            setLogFeedback('Opslaan mislukt. Controleer verbinding.');
        });
    }

    function setTodoFeedback(text) {
        var node = document.querySelector('[data-phoenix-todo-feedback]');
        if (!node) {
            return;
        }
        node.textContent = text;
    }

    function setCostFeedback(text) {
        var node = document.querySelector('[data-phoenix-cost-feedback]');
        if (!node) {
            return;
        }
        node.textContent = text;
    }

    function handleCostSubmit(event) {
        event.preventDefault();

        var typeNode = document.querySelector('[data-phoenix-cost-type]');
        var amountNode = document.querySelector('[data-phoenix-cost-amount]');
        var dateNode = document.querySelector('[data-phoenix-cost-date]');

        var cost_type = typeNode ? typeNode.value : 'other';
        var amount = amountNode ? amountNode.value.trim() : '';
        var cost_date = dateNode ? dateNode.value : '';

        if (!amount || parseFloat(amount) <= 0) {
            setCostFeedback('Vul een geldig bedrag in.');
            return;
        }

        if (!cost_date) {
            setCostFeedback('Vul een datum in.');
            return;
        }

        if (!window.bsoPhoenix || !window.bsoPhoenix.ajaxUrl) {
            setCostFeedback('Configuratie ontbreekt.');
            return;
        }

        ajaxRequest('bso_phoenix_create_cost', {
            nonce: window.bsoPhoenix.costNonce || '',
            cost_type: cost_type,
            amount: amount,
            cost_date: cost_date,
            boat_id: window.bsoPhoenix.defaultBoatId || 1,
            trip_id: state.activeTripId || '',
        }).then(function (result) {
            if (!result || !result.success) {
                setCostFeedback('Opslaan mislukt.');
                return;
            }

            if (amountNode) {
                amountNode.value = '';
            }
            setCostFeedback('Kostenpost opgeslagen.');
        }).catch(function () {
            setCostFeedback('Opslaan mislukt. Controleer verbinding.');
        });
    }

    function handleTodoSubmit(event) {
        event.preventDefault();

        var titleNode = document.querySelector('[data-phoenix-todo-title]');
        var priorityNode = document.querySelector('[data-phoenix-todo-priority]');
        var title = titleNode ? titleNode.value.trim() : '';
        var priority = priorityNode ? priorityNode.value : 'normal';

        if (!title) {
            setTodoFeedback('Vul een taakomschrijving in.');
            return;
        }

        if (!window.bsoPhoenix || !window.bsoPhoenix.ajaxUrl) {
            setTodoFeedback('Configuratie ontbreekt.');
            return;
        }

        ajaxRequest('bso_phoenix_create_todo', {
            nonce: window.bsoPhoenix.todoNonce || '',
            title: title,
            priority: priority,
            boat_id: window.bsoPhoenix.defaultBoatId || 1,
        }).then(function (result) {
            if (!result || !result.success) {
                setTodoFeedback('Opslaan mislukt.');
                return;
            }

            if (titleNode) {
                titleNode.value = '';
            }
            setTodoFeedback('Taak toegevoegd.');
        }).catch(function () {
            setTodoFeedback('Opslaan mislukt. Controleer verbinding.');
        });
    }

    document.addEventListener('submit', function (event) {
        var target = event.target;
        if (!(target instanceof Element)) {
            return;
        }

        if (target.closest('[data-phoenix-log-form]')) {
            handleLogSubmit(event);
        }

        if (target.closest('[data-phoenix-todo-form]')) {
            handleTodoSubmit(event);
        }

        if (target.closest('[data-phoenix-cost-form]')) {
            handleCostSubmit(event);
        }
    });

    document.addEventListener('click', function (event) {
        var target = event.target;
        if (!(target instanceof Element)) {
            return;
        }

        if (target.closest('[data-phoenix-start]')) {
            handleStart();
        }

        if (target.closest('[data-phoenix-stop]')) {
            handleStop();
        }
    });

    ensureMap();
    if (window.bsoPhoenix && window.bsoPhoenix.latestTripId) {
        loadTripRoute(window.bsoPhoenix.latestTripId);
    }
})();
