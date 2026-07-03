(function () {
    var state = {
        activeTripId: null,
        watchId: null,
    };

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
                maximumAge: 5000,
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
            setStatus('Actief');
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
            state.activeTripId = null;
            setStatus('Gestopt');
            setFeedback('Route gestopt en opgeslagen.');
        }).catch(function () {
            setFeedback('Stop route mislukt. Controleer verbinding.');
        });
    }

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
})();
