(function () {
    var DB_NAME = 'bsoPhoenixOfflineQueue';
    var STORE_NAME = 'requests';
    var state = {
        activeTripId: null,
        activeTripStartedAt: null,
        watchId: null,
        map: null,
        routeLine: null,
        routePoints: [],
        syncInProgress: false,
        liveStatsTimer: null,
    };

    function statNode(selector) {
        return document.querySelector(selector);
    }

    function setStatValue(selector, value) {
        var node = statNode(selector);
        if (!node) {
            return;
        }
        node.textContent = value;
    }
    function setTripSummaryValue(selector, value) {
        var node = document.querySelector(selector);
        if (!node) {
            return;
        }
        node.textContent = value;
    }
    
    function setConnectionStatus() {
        var node = document.querySelector('[data-phoenix-connection-status]');
        if (!node) {
            return;
        }
        
        if (navigator.onLine === false) {
            node.textContent = 'Offline';
            node.classList.add('is-offline');
            return;
        }
        
        node.textContent = 'Online';
        node.classList.remove('is-offline');
    }

    function formatDuration(totalSeconds) {
        var seconds = Math.max(0, Math.floor(totalSeconds));
        var hours = Math.floor(seconds / 3600);
        var minutes = Math.floor((seconds % 3600) / 60);
        var remainder = seconds % 60;

        return String(hours).padStart(2, '0') + ':'
            + String(minutes).padStart(2, '0') + ':'
            + String(remainder).padStart(2, '0');
    }

    function currentDistanceUnit() {
        return window.bsoPhoenix && window.bsoPhoenix.distanceUnit ? window.bsoPhoenix.distanceUnit : 'km';
    }

    function distanceForDisplay(distanceKm) {
        return currentDistanceUnit() === 'nm' ? distanceKm / 1.852 : distanceKm;
    }

    function speedForDisplay(speedKmh) {
        return currentDistanceUnit() === 'nm' ? speedKmh / 1.852 : speedKmh;
    }

    function speedUnit() {
        return currentDistanceUnit() === 'nm' ? 'kn' : 'km/u';
    }

    function updateLiveStats(lastSpeedKmh) {
        var distanceKm = calculateRouteDistanceKm(state.routePoints);
        var distanceDisplay = distanceForDisplay(distanceKm);
        var speedKmh = typeof lastSpeedKmh === 'number' && !Number.isNaN(lastSpeedKmh)
            ? lastSpeedKmh
            : calculateAverageSpeedKmh(distanceKm);
        var fuelUseLph = window.bsoPhoenix && typeof window.bsoPhoenix.fuelUseLph === 'number' ? window.bsoPhoenix.fuelUseLph : 0;
        var durationHours = calculateTripDurationSeconds() / 3600;
        var estimatedFuel = durationHours > 0 ? durationHours * fuelUseLph : 0;

        setStatValue('[data-phoenix-stat-duration]', formatDuration(calculateTripDurationSeconds()));
        setStatValue('[data-phoenix-stat-distance]', distanceDisplay.toFixed(2) + ' ' + currentDistanceUnit());
        setStatValue('[data-phoenix-stat-speed]', speedForDisplay(speedKmh).toFixed(2) + ' ' + speedUnit());
        setStatValue('[data-phoenix-stat-fuel]', estimatedFuel.toFixed(2) + ' l');
        setStatValue('[data-phoenix-stat-updated]', new Date().toLocaleTimeString());
        renderActiveTripSummary();
    }

    function calculateTripDurationSeconds() {
        if (!state.activeTripStartedAt) {
            return 0;
        }

        var startedAt = Date.parse(state.activeTripStartedAt.replace(' ', 'T'));
        if (Number.isNaN(startedAt)) {
            return 0;
        }

        return Math.max(0, (Date.now() - startedAt) / 1000);
    }

    function calculateAverageSpeedKmh(distanceKm) {
        var durationSeconds = calculateTripDurationSeconds();
        var durationHours = durationSeconds / 3600;
        if (durationHours <= 0) {
            return 0;
        }

        return distanceKm / durationHours;
    }

    function ensureLiveStatsTimer() {
        if (state.liveStatsTimer !== null) {
            return;
        }

        state.liveStatsTimer = window.setInterval(function () {
            if (state.activeTripId) {
                updateLiveStats();
            }
        }, 1000);
    }

    function resetLiveStats() {
        setStatValue('[data-phoenix-stat-duration]', '00:00:00');
        setStatValue('[data-phoenix-stat-distance]', '0.00 ' + currentDistanceUnit());
        setStatValue('[data-phoenix-stat-speed]', '0.00 ' + speedUnit());
        setStatValue('[data-phoenix-stat-fuel]', '0.00 l');
        setStatValue('[data-phoenix-stat-updated]', '-');
        renderActiveTripSummary();
    }
    
    function setSummaryVisibility(emptySelector, listSelector, hasData) {
        var emptyNode = document.querySelector(emptySelector);
        var listNode = document.querySelector(listSelector);
        
        if (emptyNode) {
            emptyNode.style.display = hasData ? 'none' : '';
        }
        if (listNode) {
            listNode.style.display = hasData ? 'grid' : 'none';
        }
    }
    
    function formatDateTimeLabel(rawValue) {
        if (!rawValue) {
            return '-';
        }
        
        var parsed = Date.parse(String(rawValue).replace(' ', 'T'));
        if (Number.isNaN(parsed)) {
            return String(rawValue);
        }
        
        return new Date(parsed).toLocaleString();
    }
    
    function renderActiveTripSummary() {
        var hasActive = !!state.activeTripId;
        var distanceKm = calculateRouteDistanceKm(state.routePoints);
        var durationSeconds = calculateTripDurationSeconds();
        var durationHours = durationSeconds / 3600;
        var fuelUseLph = window.bsoPhoenix && typeof window.bsoPhoenix.fuelUseLph === 'number' ? window.bsoPhoenix.fuelUseLph : 0;
        var estimatedFuel = durationHours > 0 ? durationHours * fuelUseLph : 0;
        
        setSummaryVisibility('[data-phoenix-active-trip-empty]', '[data-phoenix-active-trip-list]', hasActive);
        if (!hasActive) {
            return;
        }
        
        setTripSummaryValue('[data-phoenix-active-trip-id]', '#' + state.activeTripId);
        setTripSummaryValue('[data-phoenix-active-trip-start]', formatDateTimeLabel(state.activeTripStartedAt));
        setTripSummaryValue('[data-phoenix-active-trip-distance]', distanceForDisplay(distanceKm).toFixed(2) + ' ' + currentDistanceUnit());
        setTripSummaryValue('[data-phoenix-active-trip-fuel]', estimatedFuel.toFixed(2) + ' l');
    }
    
    function renderLatestCompletedTrip() {
        var trip = window.bsoPhoenix && window.bsoPhoenix.latestCompletedTrip ? window.bsoPhoenix.latestCompletedTrip : null;
        var hasTrip = !!(trip && trip.id);
        
        setSummaryVisibility('[data-phoenix-latest-trip-empty]', '[data-phoenix-latest-trip-list]', hasTrip);
        if (!hasTrip) {
            return;
        }
        
        setTripSummaryValue('[data-phoenix-latest-trip-id]', '#' + trip.id);
        setTripSummaryValue('[data-phoenix-latest-trip-end]', formatDateTimeLabel(trip.ended_at));
        setTripSummaryValue('[data-phoenix-latest-trip-distance]', distanceForDisplay(parseFloat(trip.distance_km || 0)).toFixed(2) + ' ' + currentDistanceUnit());
        setTripSummaryValue('[data-phoenix-latest-trip-duration]', formatDuration((parseFloat(trip.duration_minutes || 0) * 60)));

    function setSyncFeedback(text) {
        var node = document.querySelector('[data-phoenix-sync-feedback]');
        if (!node) {
            return;
        }
        node.textContent = text;
    }

    function openQueueDb() {
        return new Promise(function (resolve, reject) {
            if (!window.indexedDB) {
                reject(new Error('IndexedDB unavailable'));
                return;
            }

            var request = window.indexedDB.open(DB_NAME, 1);
            request.onupgradeneeded = function () {
                var db = request.result;
                if (!db.objectStoreNames.contains(STORE_NAME)) {
                    db.createObjectStore(STORE_NAME, { keyPath: 'id', autoIncrement: true });
                }
            };
            request.onsuccess = function () {
                resolve(request.result);
            };
            request.onerror = function () {
                reject(request.error || new Error('IndexedDB open failed'));
            };
        });
    }

    function withStore(mode, callback) {
        return openQueueDb().then(function (db) {
            return new Promise(function (resolve, reject) {
                var transaction = db.transaction(STORE_NAME, mode);
                var store = transaction.objectStore(STORE_NAME);
                var result = callback(store, resolve, reject);

                transaction.onerror = function () {
                    reject(transaction.error || new Error('IndexedDB transaction failed'));
                };
                transaction.oncomplete = function () {
                    db.close();
                    if (result === undefined) {
                        resolve();
                    }
                };
            });
        });
    }

    function queueRequest(entry) {
        return withStore('readwrite', function (store) {
            store.add(entry);
        }).then(function () {
            return updateQueuedCount();
        });
    }

    function getQueuedRequests() {
        return withStore('readonly', function (store, resolve, reject) {
            var request = store.getAll();
            request.onsuccess = function () {
                resolve(request.result || []);
            };
            request.onerror = function () {
                reject(request.error || new Error('Queue read failed'));
            };
        });
    }

    function deleteQueuedRequest(id) {
        return withStore('readwrite', function (store) {
            store.delete(id);
        }).then(function () {
            return updateQueuedCount();
        });
    }

    function queueKindLabel(queueKind) {
        var labels = {
            trackpoint: 'GPS-trackpoint',
            log: 'Captain\'s log',
            todo: 'TODO',
            cost: 'Kostenpost'
        };

        return labels[queueKind] || 'Actie';
    }

    function formatQueueTime(timestamp) {
        var date = new Date(timestamp);
        if (Number.isNaN(date.getTime())) {
            return '';
        }

        return date.toLocaleString();
    }

    function renderQueuedList(entries) {
        var listNode = document.querySelector('[data-phoenix-queue-list]');
        var emptyNode = document.querySelector('[data-phoenix-queue-empty]');

        if (!listNode || !emptyNode) {
            return;
        }

        listNode.innerHTML = '';

        if (!entries.length) {
            emptyNode.style.display = '';
            return;
        }

        emptyNode.style.display = 'none';

        entries.forEach(function (entry) {
            var item = document.createElement('li');
            item.className = 'phoenix-queue__item';
            item.setAttribute('data-queue-id', String(entry.id));

            var meta = document.createElement('div');
            meta.className = 'phoenix-queue__meta';

            var title = document.createElement('span');
            title.className = 'phoenix-queue__title';
            title.textContent = queueKindLabel(entry.queueKind);
            meta.appendChild(title);

            var time = document.createElement('span');
            time.className = 'phoenix-queue__time';
            time.textContent = formatQueueTime(entry.createdAt);
            meta.appendChild(time);

            var actions = document.createElement('div');
            actions.className = 'phoenix-queue__actions';

            var retryButton = document.createElement('button');
            retryButton.type = 'button';
            retryButton.className = 'phoenix-btn phoenix-btn--ghost phoenix-btn--small';
            retryButton.textContent = 'Opnieuw';
            retryButton.setAttribute('data-phoenix-queue-retry', String(entry.id));

            var removeButton = document.createElement('button');
            removeButton.type = 'button';
            removeButton.className = 'phoenix-btn phoenix-btn--ghost phoenix-btn--small';
            removeButton.textContent = 'Verwijder';
            removeButton.setAttribute('data-phoenix-queue-remove', String(entry.id));

            actions.appendChild(retryButton);
            actions.appendChild(removeButton);

            item.appendChild(meta);
            item.appendChild(actions);
            listNode.appendChild(item);
        });
    }

    function updateQueuedCount() {
        return getQueuedRequests().then(function (entries) {
            renderQueuedList(entries);
            if (!entries.length) {
                setSyncFeedback('Synchronisatie gereed.');
                return 0;
            }

            setSyncFeedback(entries.length + ' actie(s) wachten op synchronisatie.');
            return entries.length;
        }).catch(function () {
            setSyncFeedback('Synchronisatiestatus niet beschikbaar.');
            return 0;
        });
    }

    function replayQueuedEntry(entry) {
        var sender = entry.transport === 'form'
            ? buildFormDataRequest(entry.action, entry.nonce, entry.payload, entry.files || [])
            : requestJson(entry.action, entry.payload, entry.nonce);

        return sender.then(function () {
            return deleteQueuedRequest(entry.id);
        });
    }

    function buildQueuedFiles(fileList) {
        if (!fileList || !fileList.length) {
            return [];
        }

        return Array.prototype.map.call(fileList, function (file) {
            return {
                name: file.name,
                type: file.type,
                lastModified: file.lastModified,
                blob: file,
            };
        });
    }

    function buildFormDataRequest(action, nonce, payload, files) {
        var formData = new FormData();
        var key;

        formData.append('action', action);
        formData.append('nonce', nonce);

        for (key in payload) {
            if (Object.prototype.hasOwnProperty.call(payload, key) && payload[key] !== null && payload[key] !== undefined) {
                formData.append(key, String(payload[key]));
            }
        }

        (files || []).forEach(function (file) {
            var restoredFile = new File([file.blob], file.name, {
                type: file.type,
                lastModified: file.lastModified,
            });
            formData.append('log_photos[]', restoredFile);
        });

        return fetch(window.bsoPhoenix.ajaxUrl, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
        }).then(function (response) {
            return response.json();
        });
    }

    function requestJson(action, payload, nonceValue) {
        if (!window.bsoPhoenix || !window.bsoPhoenix.ajaxUrl) {
            return Promise.reject(new Error('Missing bsoPhoenix config'));
        }

        var formData = new URLSearchParams();
        formData.append('action', action);
        formData.append('nonce', nonceValue);

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

    function queueOrSendJson(action, payload, nonceValue, queueKind, queuedMessage) {
        if (navigator.onLine === false) {
            return queueRequest({
                queueKind: queueKind,
                transport: 'json',
                action: action,
                nonce: nonceValue,
                payload: payload,
                createdAt: Date.now(),
            }).then(function () {
                throw new Error(queuedMessage || 'queued');
            });
        }

        return requestJson(action, payload, nonceValue).catch(function () {
            return queueRequest({
                queueKind: queueKind,
                transport: 'json',
                action: action,
                nonce: nonceValue,
                payload: payload,
                createdAt: Date.now(),
            }).then(function () {
                throw new Error(queuedMessage || 'queued');
            });
        });
    }

    function queueOrSendForm(action, payload, nonceValue, files, queueKind, queuedMessage) {
        if (navigator.onLine === false) {
            return queueRequest({
                queueKind: queueKind,
                transport: 'form',
                action: action,
                nonce: nonceValue,
                payload: payload,
                files: files,
                createdAt: Date.now(),
            }).then(function () {
                throw new Error(queuedMessage || 'queued');
            });
        }

        return buildFormDataRequest(action, nonceValue, payload, files).catch(function () {
            return queueRequest({
                queueKind: queueKind,
                transport: 'form',
                action: action,
                nonce: nonceValue,
                payload: payload,
                files: files,
                createdAt: Date.now(),
            }).then(function () {
                throw new Error(queuedMessage || 'queued');
            });
        });
    }

    function flushQueuedRequests() {
        if (state.syncInProgress || navigator.onLine === false) {
            return Promise.resolve();
        }

        state.syncInProgress = true;
        setSyncFeedback('Synchronisatie bezig...');

        return getQueuedRequests().then(function (entries) {
            return entries.reduce(function (promise, entry) {
                return promise.then(function () {
                    return replayQueuedEntry(entry).catch(function () {
                        return Promise.resolve();
                    });
                });
            }, Promise.resolve());
        }).finally(function () {
            state.syncInProgress = false;
            updateQueuedCount();
        });
    }

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
        updateLiveStats();

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
        updateLiveStats();

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
        return requestJson(action, payload, window.bsoPhoenix && window.bsoPhoenix.nonce ? window.bsoPhoenix.nonce : '');
    }

    function sendTrackpoint(position) {
        if (!state.activeTripId) {
            return;
        }

        var coords = position.coords;
        appendRoutePoint(coords.latitude, coords.longitude);
        updateLiveStats(coords.speed ? coords.speed * 3.6 : null);

        queueOrSendJson('bso_phoenix_trackpoint', {
            trip_id: state.activeTripId,
            latitude: coords.latitude,
            longitude: coords.longitude,
            altitude: coords.altitude,
            speed: coords.speed ? coords.speed * 3.6 : null,
            accuracy: coords.accuracy,
            recorded_at: Date.now(),
        }, window.bsoPhoenix && window.bsoPhoenix.nonce ? window.bsoPhoenix.nonce : '', 'trackpoint', 'queued').catch(function (error) {
            if (error && error.message === 'queued') {
                setFeedback('Geen verbinding. Trackpoint lokaal opgeslagen voor latere synchronisatie.');
                return;
            }
            setFeedback('Trackpoint opslaan mislukt. Controleer verbinding.');
        });
    }

    function startGeolocation() {
        if (!('geolocation' in navigator)) {
            setFeedback('GPS is niet beschikbaar op dit apparaat.');
            return;
        }

        if (state.watchId !== null) {
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
        if (state.activeTripId) {
            setFeedback('Er is al een actieve route.');
            setStatus('Actief');
            startGeolocation();
            return;
        }

        if (navigator.onLine === false) {
            setFeedback('Een nieuwe route starten vereist verbinding met de server.');
            return;
        }

        ajaxRequest('bso_phoenix_start_trip', {
            boat_id: window.bsoPhoenix && window.bsoPhoenix.defaultBoatId ? window.bsoPhoenix.defaultBoatId : 1,
        }).then(function (result) {
            if (!result || !result.success) {
                setFeedback('Start route mislukt.');
                return;
            }

            state.activeTripId = result.data.trip_id;
            state.activeTripStartedAt = new Date().toISOString();
            setStatus('Actief');
            setMapTrip('Trip #' + state.activeTripId + ' (actief)');

            if (result.data.already_active) {
                loadTripRoute(state.activeTripId);
                setFeedback('Bestaande actieve route hervat. GPS tracking blijft actief.');
            } else {
                state.routePoints = [];
                renderRoute([]);
                setFeedback('Route gestart. GPS tracking is actief.');
            }

            ensureLiveStatsTimer();
            updateLiveStats();
            startGeolocation();
            flushQueuedRequests();
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
            state.activeTripStartedAt = null;
            setStatus('Gestopt');
            setFeedback('Route gestopt en opgeslagen.');
            resetLiveStats();
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

        queueOrSendForm('bso_phoenix_create_log', {
            entry_text: text,
            boat_id: String(window.bsoPhoenix.defaultBoatId || 1),
            trip_id: String(state.activeTripId || ''),
        }, window.bsoPhoenix.logNonce || '', buildQueuedFiles(fileNode && fileNode.files ? fileNode.files : []), 'log', 'queued').then(function (result) {
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
            flushQueuedRequests();
        }).catch(function (error) {
            if (error && error.message === 'queued') {
                if (textNode) {
                    textNode.value = '';
                }
                if (fileNode) {
                    fileNode.value = '';
                }
                setLogFeedback('Geen verbinding. Notitie lokaal in wachtrij geplaatst.');
                return;
            }
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

        queueOrSendJson('bso_phoenix_create_cost', {
            nonce: window.bsoPhoenix.costNonce || '',
            cost_type: cost_type,
            amount: amount,
            cost_date: cost_date,
            boat_id: window.bsoPhoenix.defaultBoatId || 1,
            trip_id: state.activeTripId || '',
        }, window.bsoPhoenix.costNonce || '', 'cost', 'queued').then(function (result) {
            if (!result || !result.success) {
                setCostFeedback('Opslaan mislukt.');
                return;
            }

            if (amountNode) {
                amountNode.value = '';
            }
            setCostFeedback('Kostenpost opgeslagen.');
            flushQueuedRequests();
        }).catch(function (error) {
            if (error && error.message === 'queued') {
                if (amountNode) {
                    amountNode.value = '';
                }
                setCostFeedback('Geen verbinding. Kostenpost lokaal in wachtrij geplaatst.');
                return;
            }
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

        queueOrSendJson('bso_phoenix_create_todo', {
            nonce: window.bsoPhoenix.todoNonce || '',
            title: title,
            priority: priority,
            boat_id: window.bsoPhoenix.defaultBoatId || 1,
        }, window.bsoPhoenix.todoNonce || '', 'todo', 'queued').then(function (result) {
            if (!result || !result.success) {
                setTodoFeedback('Opslaan mislukt.');
                return;
            }

            if (titleNode) {
                titleNode.value = '';
            }
            setTodoFeedback('Taak toegevoegd.');
            flushQueuedRequests();
        }).catch(function (error) {
            if (error && error.message === 'queued') {
                if (titleNode) {
                    titleNode.value = '';
                }
                setTodoFeedback('Geen verbinding. Taak lokaal in wachtrij geplaatst.');
                return;
            }
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

        if (target.closest('[data-phoenix-queue-retry-all]')) {
            flushQueuedRequests();
            return;
        }

        if (target.closest('[data-phoenix-queue-remove]')) {
            deleteQueuedRequest(parseInt(target.closest('[data-phoenix-queue-remove]').getAttribute('data-phoenix-queue-remove'), 10));
            return;
        }

        if (target.closest('[data-phoenix-queue-retry]')) {
            getQueuedRequests().then(function (entries) {
                var id = parseInt(target.closest('[data-phoenix-queue-retry]').getAttribute('data-phoenix-queue-retry'), 10);
                var entry = entries.find(function (queuedEntry) {
                    return queuedEntry.id === id;
                });

                if (!entry) {
                    return;
                }

                replayQueuedEntry(entry).catch(function () {
                    setSyncFeedback('Opnieuw proberen mislukt. Actie blijft in wachtrij.');
                });
            });
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
    updateQueuedCount();
    resetLiveStats();
    setConnectionStatus();
    renderLatestCompletedTrip();
    if (window.bsoPhoenix && window.bsoPhoenix.activeTripId) {
        state.activeTripId = window.bsoPhoenix.activeTripId;
        state.activeTripStartedAt = window.bsoPhoenix.activeTripStartedAt || null;
        setStatus('Actief');
        setFeedback('Actieve route hervat na herladen van de pagina.');
        loadTripRoute(window.bsoPhoenix.activeTripId);
        ensureLiveStatsTimer();
        updateLiveStats();
        startGeolocation();
    } else if (window.bsoPhoenix && window.bsoPhoenix.latestTripId) {
        loadTripRoute(window.bsoPhoenix.latestTripId);
    }

    window.addEventListener('online', function () {
        setConnectionStatus();
        setSyncFeedback('Verbinding hersteld. Synchronisatie gestart...');
        flushQueuedRequests();
    });

    window.addEventListener('offline', function () {
        setConnectionStatus();
        setSyncFeedback('Offline. Nieuwe acties worden lokaal in de wachtrij geplaatst.');
    });

    if (navigator.onLine !== false) {
        flushQueuedRequests();
    }
})();
