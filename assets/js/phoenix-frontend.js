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
        logPhotoFiles: [],
        galleryPhotos: [],
        lightboxIndex: -1,
        syncInProgress: false,
        liveStatsTimer: null,
        logSubmitInProgress: false,
        todoSubmitInProgress: false,
        costSubmitInProgress: false,
        feedbackQueue: [],
        feedbackVisibleCount: 0,
    };

    var FEEDBACK_MAX_VISIBLE = 3;
    var FEEDBACK_TOAST_TIMEOUT_MS = 4500;
    var QUEUE_MAX_ATTEMPTS = 5;

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

    function pad2(value) {
        return String(value).padStart(2, '0');
    }

    function currentLogDate() {
        var now = new Date();
        return now.getFullYear() + '-' + pad2(now.getMonth() + 1) + '-' + pad2(now.getDate());
    }

    function currentLogTime() {
        var now = new Date();
        return pad2(now.getHours()) + ':' + pad2(now.getMinutes()) + ':' + pad2(now.getSeconds());
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
    }

    function renderSelectedLogPhotos() {
        var listNode = document.querySelector('[data-phoenix-log-photo-list]');
        var emptyNode = document.querySelector('[data-phoenix-log-photo-empty]');

        if (!listNode || !emptyNode) {
            return;
        }

        listNode.innerHTML = '';

        if (!state.logPhotoFiles.length) {
            emptyNode.style.display = '';
            return;
        }

        emptyNode.style.display = 'none';

        state.logPhotoFiles.forEach(function (entry, index) {
            var item = document.createElement('li');
            item.className = 'phoenix-log-photos__item';

            var order = document.createElement('span');
            order.className = 'phoenix-log-photos__order';
            order.textContent = String(index + 1);

            var name = document.createElement('span');
            name.className = 'phoenix-log-photos__name';
            name.textContent = entry.file.name;
            name.title = entry.file.name;

            var caption = document.createElement('input');
            caption.type = 'text';
            caption.className = 'phoenix-log-photos__caption';
            caption.placeholder = 'Bijschrift (optioneel)';
            caption.setAttribute('data-phoenix-log-photo-caption', String(index));
            caption.value = entry.caption || '';

            var actions = document.createElement('div');
            actions.className = 'phoenix-log-photos__actions';

            var upButton = document.createElement('button');
            upButton.type = 'button';
            upButton.className = 'phoenix-btn phoenix-btn--ghost phoenix-btn--small';
            upButton.textContent = 'Omhoog';
            upButton.setAttribute('data-phoenix-log-photo-up', String(index));
            upButton.disabled = index === 0;

            var downButton = document.createElement('button');
            downButton.type = 'button';
            downButton.className = 'phoenix-btn phoenix-btn--ghost phoenix-btn--small';
            downButton.textContent = 'Omlaag';
            downButton.setAttribute('data-phoenix-log-photo-down', String(index));
            downButton.disabled = index === state.logPhotoFiles.length - 1;

            var removeButton = document.createElement('button');
            removeButton.type = 'button';
            removeButton.className = 'phoenix-btn phoenix-btn--ghost phoenix-btn--small';
            removeButton.textContent = 'Verwijder';
            removeButton.setAttribute('data-phoenix-log-photo-remove', String(index));

            actions.appendChild(upButton);
            actions.appendChild(downButton);
            actions.appendChild(removeButton);

            var content = document.createElement('div');
            content.className = 'phoenix-log-photos__content';
            content.appendChild(name);
            content.appendChild(caption);

            item.appendChild(order);
            item.appendChild(content);
            item.appendChild(actions);
            listNode.appendChild(item);
        });
    }

    function setSelectedLogPhotos(fileList) {
        state.logPhotoFiles = Array.prototype.slice.call(fileList || []).filter(function (file) {
            return file && typeof file.type === 'string' && file.type.indexOf('image/') === 0;
        }).map(function (file) {
            return {
                file: file,
                caption: '',
            };
        });
        renderSelectedLogPhotos();
    }

    function moveSelectedLogPhoto(fromIndex, toIndex) {
        if (fromIndex < 0 || toIndex < 0 || fromIndex >= state.logPhotoFiles.length || toIndex >= state.logPhotoFiles.length) {
            return;
        }

        var moved = state.logPhotoFiles.splice(fromIndex, 1)[0];
        state.logPhotoFiles.splice(toIndex, 0, moved);
        renderSelectedLogPhotos();
    }

    function removeSelectedLogPhoto(index) {
        if (index < 0 || index >= state.logPhotoFiles.length) {
            return;
        }

        state.logPhotoFiles.splice(index, 1);
        renderSelectedLogPhotos();
    }

    function updateSelectedLogPhotoCaption(index, value) {
        if (index < 0 || index >= state.logPhotoFiles.length) {
            return;
        }

        state.logPhotoFiles[index].caption = value;
    }

    function initLogPhotoInput() {
        var fileNode = document.querySelector('[data-phoenix-log-photos]');
        if (!fileNode) {
            return;
        }

        fileNode.addEventListener('change', function () {
            setSelectedLogPhotos(fileNode.files);
        });

        renderSelectedLogPhotos();
    }

    function renderLogGallery() {
        var listNode = document.querySelector('[data-phoenix-log-gallery-list]');
        var emptyNode = document.querySelector('[data-phoenix-log-gallery-empty]');
        var canEditGallery = !!(window.bsoPhoenix && window.bsoPhoenix.canWrite);

        if (!listNode || !emptyNode) {
            return;
        }

        listNode.innerHTML = '';

        if (!state.galleryPhotos.length) {
            emptyNode.style.display = '';
            return;
        }

        emptyNode.style.display = 'none';

        state.galleryPhotos.forEach(function (photo, index) {
            var item = document.createElement('li');
            item.className = 'phoenix-log-gallery__item';
            item.setAttribute('data-phoenix-gallery-photo-id', String(photo.id || 0));

            var button = document.createElement('button');
            button.type = 'button';
            button.className = 'phoenix-log-gallery__button';
            button.setAttribute('data-phoenix-open-lightbox', String(index));

            var image = document.createElement('img');
            image.className = 'phoenix-log-gallery__image';
            image.src = photo.thumbnail_url || photo.url || '';
            image.alt = photo.caption || 'Logfoto';
            image.loading = 'lazy';

            var caption = document.createElement('span');
            caption.className = 'phoenix-log-gallery__caption';
            caption.textContent = photo.caption || 'Foto';

            var meta = document.createElement('span');
            meta.className = 'phoenix-log-gallery__meta';
            meta.textContent = 'Log #' + String(photo.log_id || '-') + ' · #' + String((photo.sort_order || index + 1));

            button.appendChild(image);
            button.appendChild(caption);
            button.appendChild(meta);
            item.appendChild(button);

            if (canEditGallery && photo.id) {
                var editor = document.createElement('div');
                editor.className = 'phoenix-log-gallery__editor';

                var input = document.createElement('input');
                input.type = 'text';
                input.className = 'phoenix-log-gallery__input';
                input.value = photo.caption || '';
                input.placeholder = 'Bijschrift';
                input.setAttribute('data-phoenix-gallery-caption-input', String(photo.id));

                var controls = document.createElement('div');
                controls.className = 'phoenix-log-gallery__controls';

                var saveButton = document.createElement('button');
                saveButton.type = 'button';
                saveButton.className = 'phoenix-btn phoenix-btn--ghost phoenix-btn--small';
                saveButton.textContent = 'Bewaar';
                saveButton.setAttribute('data-phoenix-gallery-save-caption', String(photo.id));

                var upButton = document.createElement('button');
                upButton.type = 'button';
                upButton.className = 'phoenix-btn phoenix-btn--ghost phoenix-btn--small';
                upButton.textContent = 'Omhoog';
                upButton.setAttribute('data-phoenix-gallery-move', String(photo.id));
                upButton.setAttribute('data-phoenix-gallery-step', '-1');
                upButton.disabled = !hasAdjacentGalleryPhoto(photo.id, -1);

                var downButton = document.createElement('button');
                downButton.type = 'button';
                downButton.className = 'phoenix-btn phoenix-btn--ghost phoenix-btn--small';
                downButton.textContent = 'Omlaag';
                downButton.setAttribute('data-phoenix-gallery-move', String(photo.id));
                downButton.setAttribute('data-phoenix-gallery-step', '1');
                downButton.disabled = !hasAdjacentGalleryPhoto(photo.id, 1);

                controls.appendChild(saveButton);
                controls.appendChild(upButton);
                controls.appendChild(downButton);

                editor.appendChild(input);
                editor.appendChild(controls);
                item.appendChild(editor);
            }

            listNode.appendChild(item);
        });
    }

    function setGalleryFromLogs(logs) {
        state.galleryPhotos = (logs || []).reduce(function (all, log) {
            var photos = Array.isArray(log.photos) ? log.photos : [];
            photos.forEach(function (photo) {
                if (!photo || (!photo.url && !photo.thumbnail_url)) {
                    return;
                }

                all.push({
                    id: parseInt(photo.id || 0, 10) || 0,
                    log_id: parseInt(log.id || 0, 10) || 0,
                    caption: photo.caption || '',
                    sort_order: parseInt(photo.sort_order || 0, 10) || 0,
                    url: photo.url || photo.thumbnail_url || '',
                    thumbnail_url: photo.thumbnail_url || photo.url || '',
                    log_date: log.log_date || '',
                    log_time: log.log_time || '',
                });
            });

            return all;
        }, []);

        renderLogGallery();
    }

    function requestLogJson(action, payload) {
        return requestJson(action, payload, window.bsoPhoenix && window.bsoPhoenix.logNonce ? window.bsoPhoenix.logNonce : '').then(function (response) {
            return ensureSuccessfulResponse(response, 'Verzoek afgewezen door server.');
        });
    }

    function gallerySiblingsFor(photoId) {
        var current = state.galleryPhotos.find(function (photo) {
            return photo.id === photoId;
        });

        if (!current) {
            return [];
        }

        return state.galleryPhotos.filter(function (photo) {
            return photo.log_id === current.log_id;
        }).sort(function (a, b) {
            var aOrder = a.sort_order || 0;
            var bOrder = b.sort_order || 0;
            if (aOrder === bOrder) {
                return a.id - b.id;
            }
            return aOrder - bOrder;
        });
    }

    function hasAdjacentGalleryPhoto(photoId, direction) {
        var siblings = gallerySiblingsFor(photoId);
        var currentIndex = siblings.findIndex(function (photo) {
            return photo.id === photoId;
        });

        if (currentIndex < 0) {
            return false;
        }

        var targetIndex = currentIndex + direction;
        return targetIndex >= 0 && targetIndex < siblings.length;
    }

    function readGalleryCaptionInput(photoId) {
        var node = document.querySelector('[data-phoenix-gallery-caption-input="' + String(photoId) + '"]');
        return node ? node.value.trim() : '';
    }

    function updateExistingLogPhoto(photoId, caption, sortOrder) {
        return requestLogJson('bso_phoenix_update_log_photo', {
            photo_id: photoId,
            caption: caption,
            sort_order: sortOrder || '',
        }).then(function () {
            return loadLogGallery();
        });
    }

    function moveExistingGalleryPhoto(photoId, direction) {
        var siblings = gallerySiblingsFor(photoId);
        var currentIndex = siblings.findIndex(function (photo) {
            return photo.id === photoId;
        });
        if (currentIndex < 0) {
            return Promise.resolve();
        }

        var targetIndex = currentIndex + direction;
        if (targetIndex < 0 || targetIndex >= siblings.length) {
            return Promise.resolve();
        }

        var current = siblings[currentIndex];
        var target = siblings[targetIndex];
        var caption = readGalleryCaptionInput(photoId);
        var targetSortOrder = target.sort_order || (targetIndex + 1);

        return updateExistingLogPhoto(photoId, caption || current.caption || '', targetSortOrder).then(function () {
            setLogFeedback('Volgorde bijgewerkt.', 'success', { toast: true });
        }).catch(function (error) {
            setLogFeedback(error && error.message ? error.message : 'Volgorde bijwerken mislukt.', 'error');
        });
    }

    function saveExistingGalleryCaption(photoId) {
        var siblings = gallerySiblingsFor(photoId);
        var current = siblings.find(function (photo) {
            return photo.id === photoId;
        });
        if (!current) {
            return Promise.resolve();
        }

        return updateExistingLogPhoto(photoId, readGalleryCaptionInput(photoId), current.sort_order || '').then(function () {
            setLogFeedback('Bijschrift opgeslagen.', 'success', { toast: true });
        }).catch(function (error) {
            setLogFeedback(error && error.message ? error.message : 'Bijschrift opslaan mislukt.', 'error');
        });
    }

    function loadLogGallery() {
        return queueOrSendJson('bso_phoenix_get_logs', {
            limit: 20,
        }, window.bsoPhoenix.logNonce || '', 'log', 'queued').then(function (result) {
            if (!result || !result.success || !result.data) {
                return;
            }

            setGalleryFromLogs(result.data.logs || []);
        }).catch(function () {
            // Keep current gallery as-is when fetch fails.
        });
    }

    function getLightboxNodes() {
        return {
            root: document.querySelector('[data-phoenix-lightbox]'),
            image: document.querySelector('[data-phoenix-lightbox-image]'),
            caption: document.querySelector('[data-phoenix-lightbox-caption]'),
        };
    }

    function renderLightbox() {
        var nodes = getLightboxNodes();
        if (!nodes.root || !nodes.image || !nodes.caption) {
            return;
        }

        if (state.lightboxIndex < 0 || state.lightboxIndex >= state.galleryPhotos.length) {
            nodes.root.hidden = true;
            return;
        }

        var photo = state.galleryPhotos[state.lightboxIndex];
        nodes.image.src = photo.url || photo.thumbnail_url || '';
        nodes.image.alt = photo.caption || 'Logfoto';

        var dateLabel = [photo.log_date, photo.log_time].filter(Boolean).join(' ');
        nodes.caption.textContent = photo.caption
            ? (dateLabel ? photo.caption + ' - ' + dateLabel : photo.caption)
            : (dateLabel || 'Logfoto');
        nodes.root.hidden = false;
    }

    function openLightbox(index) {
        if (index < 0 || index >= state.galleryPhotos.length) {
            return;
        }

        state.lightboxIndex = index;
        renderLightbox();
    }

    function closeLightbox() {
        state.lightboxIndex = -1;
        renderLightbox();
    }

    function moveLightbox(step) {
        if (!state.galleryPhotos.length || state.lightboxIndex < 0) {
            return;
        }

        var length = state.galleryPhotos.length;
        state.lightboxIndex = (state.lightboxIndex + step + length) % length;
        renderLightbox();
    }

    function normalizeFeedbackType(type) {
        if (type === 'success' || type === 'info' || type === 'warning' || type === 'error') {
            return type;
        }

        return 'info';
    }

    function setFeedbackBanner(selector, text, type) {
        var node = document.querySelector(selector);
        if (!node) {
            return;
        }

        node.textContent = text;
        node.setAttribute('data-feedback-type', normalizeFeedbackType(type));
    }

    function removeFeedbackToast(toastNode) {
        if (toastNode && toastNode.parentNode) {
            toastNode.parentNode.removeChild(toastNode);
        }

        state.feedbackVisibleCount = Math.max(0, state.feedbackVisibleCount - 1);
        drainFeedbackQueue();
    }

    function drainFeedbackQueue() {
        var root = document.querySelector('[data-phoenix-feedback-stack]');
        if (!root) {
            state.feedbackQueue = [];
            state.feedbackVisibleCount = 0;
            return;
        }

        while (state.feedbackVisibleCount < FEEDBACK_MAX_VISIBLE && state.feedbackQueue.length > 0) {
            var nextToast = state.feedbackQueue.shift();
            var toastNode = document.createElement('div');
            toastNode.className = 'phoenix-feedback-toast';
            toastNode.setAttribute('data-feedback-type', nextToast.type);
            toastNode.setAttribute('role', nextToast.type === 'error' ? 'alert' : 'status');
            toastNode.textContent = nextToast.text;
            root.appendChild(toastNode);
            state.feedbackVisibleCount += 1;

            (function (node) {
                window.setTimeout(function () {
                    removeFeedbackToast(node);
                }, FEEDBACK_TOAST_TIMEOUT_MS);
            })(toastNode);
        }
    }

    function notify(text, type, options) {
        var nextType = normalizeFeedbackType(type);
        var nextOptions = options || {};
        if (!text) {
            return;
        }

        if (nextOptions.bannerSelector) {
            setFeedbackBanner(nextOptions.bannerSelector, text, nextType);
        }

        if (nextOptions.toast === false) {
            return;
        }

        state.feedbackQueue.push({
            text: text,
            type: nextType,
        });
        drainFeedbackQueue();
    }

    function setSyncFeedback(text, type, options) {
        var nextOptions = options || {};
        notify(text, type || 'info', {
            bannerSelector: '[data-phoenix-sync-feedback]',
            toast: nextOptions.toast === true,
        });
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
        var queuedEntry = normalizeQueueEntry(entry);
        return withStore('readwrite', function (store) {
            store.add(queuedEntry);
        }).then(function () {
            return updateQueuedCount();
        });
    }

    function normalizeQueueEntry(entry) {
        var normalized = entry || {};

        if (typeof normalized.attempts !== 'number' || Number.isNaN(normalized.attempts)) {
            normalized.attempts = 0;
        }
        if (!normalized.status) {
            normalized.status = 'queued';
        }
        if (typeof normalized.lastError !== 'string') {
            normalized.lastError = '';
        }
        if (typeof normalized.lastTriedAt !== 'number') {
            normalized.lastTriedAt = 0;
        }
        if (typeof normalized.createdAt !== 'number') {
            normalized.createdAt = Date.now();
        }

        return normalized;
    }

    function getQueuedRequests() {
        return withStore('readonly', function (store, resolve, reject) {
            var request = store.getAll();
            request.onsuccess = function () {
                resolve((request.result || []).map(function (entry) {
                    return normalizeQueueEntry(entry);
                }));
            };
            request.onerror = function () {
                reject(request.error || new Error('Queue read failed'));
            };
        });
    }

    function putQueuedRequest(entry) {
        var nextEntry = normalizeQueueEntry(entry);

        return withStore('readwrite', function (store) {
            store.put(nextEntry);
        });
    }

    function updateQueuedRequest(id, mutator) {
        return withStore('readwrite', function (store, resolve, reject) {
            var request = store.get(id);

            request.onsuccess = function () {
                var currentEntry = request.result;
                if (!currentEntry) {
                    resolve(null);
                    return;
                }

                var updatedEntry = mutator(normalizeQueueEntry(currentEntry)) || currentEntry;
                store.put(normalizeQueueEntry(updatedEntry));
                resolve(updatedEntry);
            };

            request.onerror = function () {
                reject(request.error || new Error('Queue update failed'));
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
            log_photo: 'Logfoto upload',
            todo: 'TODO',
            cost: 'Kostenpost'
        };

        return labels[queueKind] || 'Actie';
    }

    function createRequestUid(scope) {
        return [
            scope,
            String(Date.now()),
            Math.random().toString(36).slice(2, 10),
        ].join('_');
    }

    function extractAjaxError(response, fallback) {
        if (response && response.data && typeof response.data.message === 'string' && response.data.message.trim() !== '') {
            return response.data.message;
        }

        return fallback;
    }

    function ensureSuccessfulResponse(response, fallback) {
        if (!response || response.success !== true) {
            var error = new Error(extractAjaxError(response, fallback || 'Verzoek mislukt.'));
            error.isServerError = true;
            throw error;
        }

        return response;
    }

    function formatQueueTime(timestamp) {
        var date = new Date(timestamp);
        if (Number.isNaN(date.getTime())) {
            return '';
        }

        return date.toLocaleString();
    }

    function queueStatusLabel(status) {
        var labels = {
            queued: 'queued',
            retrying: 'retrying',
            failed: 'failed',
            synced: 'synced',
        };

        return labels[status] || 'queued';
    }

    function updateEntryStatus(id, status, errorMessage) {
        return updateQueuedRequest(id, function (entry) {
            entry.status = status;
            entry.lastTriedAt = Date.now();

            if (status === 'retrying') {
                entry.attempts = (entry.attempts || 0) + 1;
                entry.lastError = '';
            }

            if (status === 'failed') {
                entry.lastError = errorMessage || 'Synchronisatie mislukt.';
            }

            if (status === 'synced') {
                entry.lastError = '';
            }

            return entry;
        }).then(function () {
            return updateQueuedCount();
        });
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
            item.setAttribute('data-queue-status', queueStatusLabel(entry.status));

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

            var status = document.createElement('span');
            status.className = 'phoenix-queue__status';
            status.setAttribute('data-queue-status', queueStatusLabel(entry.status));
            status.textContent = queueStatusLabel(entry.status);
            meta.appendChild(status);

            var details = document.createElement('span');
            details.className = 'phoenix-queue__details';
            details.textContent = 'pogingen: ' + String(entry.attempts || 0);
            meta.appendChild(details);

            if (entry.lastError) {
                var error = document.createElement('span');
                error.className = 'phoenix-queue__error';
                error.textContent = entry.lastError;
                meta.appendChild(error);
            }

            var actions = document.createElement('div');
            actions.className = 'phoenix-queue__actions';

            var retryButton = document.createElement('button');
            retryButton.type = 'button';
            retryButton.className = 'phoenix-btn phoenix-btn--ghost phoenix-btn--small';
            retryButton.textContent = 'Opnieuw';
            retryButton.setAttribute('data-phoenix-queue-retry', String(entry.id));
            retryButton.disabled = entry.status === 'retrying' || entry.status === 'synced' || (entry.attempts || 0) >= QUEUE_MAX_ATTEMPTS;

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
            var waitingCount = entries.filter(function (entry) {
                return entry.status !== 'synced';
            }).length;

            if (!waitingCount) {
                setSyncFeedback('Synchronisatie gereed.');
                return 0;
            }

            setSyncFeedback(waitingCount + ' actie(s) wachten op synchronisatie.');
            return waitingCount;
        }).catch(function () {
            setSyncFeedback('Synchronisatiestatus niet beschikbaar.');
            return 0;
        });
    }

    function createQueuedLogPhotoRetry(originalEntry, logId) {
        return queueRequest({
            queueKind: 'log_photo',
            transport: 'form',
            action: 'bso_phoenix_add_log_photos',
            nonce: originalEntry.nonce,
            payload: {
                log_id: String(logId),
            },
            files: originalEntry.files || [],
            createdAt: Date.now(),
            attempts: 0,
            status: 'queued',
            lastError: '',
            lastTriedAt: 0,
        });
    }

    function maybeSplitLogPhotoRetry(entry, replayError) {
        if (entry.queueKind !== 'log' || entry.transport !== 'form' || !Array.isArray(entry.files) || !entry.files.length) {
            return Promise.reject(replayError);
        }

        return buildFormDataRequest(entry.action, entry.nonce, entry.payload, []).then(function (response) {
            return ensureSuccessfulResponse(response, 'Fallback zonder foto\'s mislukt.');
        }).then(function (response) {
            var logId = response && response.data && response.data.log_id ? parseInt(response.data.log_id, 10) : 0;
            if (!logId) {
                throw new Error('Log opgeslagen zonder foto\'s, maar log_id ontbreekt.');
            }

            return createQueuedLogPhotoRetry(entry, logId).then(function () {
                notify('Logtekst is opgeslagen; foto-upload staat apart in de wachtrij.', 'warning', { toast: true });
                return deleteQueuedRequest(entry.id);
            });
        }).catch(function () {
            return Promise.reject(replayError);
        });
    }

    function replayQueuedEntry(entry) {
        var sender = entry.transport === 'form'
            ? buildFormDataRequest(entry.action, entry.nonce, entry.payload, entry.files || [])
            : requestJson(entry.action, entry.payload, entry.nonce);

        return updateEntryStatus(entry.id, 'retrying').then(function () {
            return sender.then(function (response) {
                return ensureSuccessfulResponse(response, 'Synchronisatie mislukt.');
            }).then(function () {
                return updateEntryStatus(entry.id, 'synced').then(function () {
                    return deleteQueuedRequest(entry.id);
                });
            }).catch(function (error) {
                return maybeSplitLogPhotoRetry(entry, error).catch(function (nextError) {
                    var attempts = (entry.attempts || 0) + 1;
                    var message = nextError && nextError.message ? nextError.message : 'Synchronisatie mislukt.';
                    return updateEntryStatus(entry.id, 'failed', message).then(function () {
                        if (attempts >= QUEUE_MAX_ATTEMPTS) {
                            notify('Maximaal aantal retries bereikt voor een wachtrij-item.', 'error', { toast: true });
                        }
                        throw nextError;
                    });
                });
            });
        });
    }

    function buildQueuedFiles(fileList) {
        if (!fileList || !fileList.length) {
            return [];
        }

        return Array.prototype.map.call(fileList, function (entry) {
            var file = entry && entry.file ? entry.file : entry;
            var caption = entry && typeof entry.caption === 'string' ? entry.caption : '';

            return {
                name: file.name,
                type: file.type,
                lastModified: file.lastModified,
                blob: file,
                caption: caption,
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
            formData.append('log_photo_captions[]', file.caption || '');
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

        return requestJson(action, payload, nonceValue).then(function (response) {
            return ensureSuccessfulResponse(response, 'Verzoek afgewezen door server.');
        }).catch(function (error) {
            if (error && error.isServerError) {
                throw error;
            }

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

        return buildFormDataRequest(action, nonceValue, payload, files).then(function (response) {
            return ensureSuccessfulResponse(response, 'Verzoek afgewezen door server.');
        }).catch(function (error) {
            if (error && error.isServerError) {
                throw error;
            }

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
                    if (entry.status === 'failed' && (entry.attempts || 0) >= QUEUE_MAX_ATTEMPTS) {
                        return Promise.resolve();
                    }

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
            setFeedback('Laatste route laden mislukt.', 'error');
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

    function setFeedback(text, type, options) {
        var nextOptions = options || {};
        notify(text, type || 'info', {
            bannerSelector: '[data-phoenix-feedback]',
            toast: nextOptions.toast !== false,
        });
    }

    function setStatus(nextStatus) {
        var node = document.querySelector('[data-phoenix-status]');
        if (!node) {
            return;
        }
        node.textContent = nextStatus;
    }

    function normalizeTripId(value) {
        var tripId = parseInt(value, 10);
        return Number.isFinite(tripId) && tripId > 0 ? tripId : 0;
    }

    function ajaxRequest(action, payload) {
        return requestJson(action, payload, window.bsoPhoenix && window.bsoPhoenix.nonce ? window.bsoPhoenix.nonce : '').then(function (response) {
            return ensureSuccessfulResponse(response, 'Verzoek afgewezen door server.');
        });
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
            request_uid: createRequestUid('trackpoint'),
        }, window.bsoPhoenix && window.bsoPhoenix.nonce ? window.bsoPhoenix.nonce : '', 'trackpoint', 'queued').catch(function (error) {
            if (error && error.message === 'queued') {
                setFeedback('Geen verbinding. Trackpoint lokaal opgeslagen voor latere synchronisatie.', 'warning', { toast: false });
                return;
            }
            setFeedback(error && error.message ? error.message : 'Trackpoint opslaan mislukt. Controleer verbinding.', 'error');
        });
    }

    function startGeolocation() {
        if (!('geolocation' in navigator)) {
            setFeedback('GPS is niet beschikbaar op dit apparaat.', 'error');
            return;
        }

        if (window.isSecureContext !== true) {
            setFeedback('GPS vereist een beveiligde verbinding (HTTPS) of localhost. Open deze pagina via HTTPS.', 'warning');
            return;
        }

        if (state.watchId !== null) {
            return;
        }

        state.watchId = navigator.geolocation.watchPosition(
            sendTrackpoint,
            function (error) {
                if (error && error.code === 1) {
                    setFeedback('Locatietoegang geweigerd. Sta locatie toe in browser- en app-instellingen.', 'warning');
                    return;
                }

                if (error && error.code === 2) {
                    setFeedback('Locatie niet beschikbaar. Controleer GPS, netwerk of ga naar buiten voor beter signaal.', 'warning');
                    return;
                }

                if (error && error.code === 3) {
                    setFeedback('Locatie-opvraag timeout. Probeer opnieuw of verhoog het GPS-interval in de instellingen.', 'warning');
                    return;
                }

                setFeedback('Geen GPS-signaal. Controleer locatiepermissies.', 'error');
            },
            {
                enableHighAccuracy: true,
                maximumAge: window.bsoPhoenix && window.bsoPhoenix.gpsIntervalMs ? window.bsoPhoenix.gpsIntervalMs : 10000,
                timeout: window.bsoPhoenix && window.bsoPhoenix.gpsIntervalMs ? Math.max(15000, window.bsoPhoenix.gpsIntervalMs * 2) : 20000,
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
        if (!window.bsoPhoenix || !window.bsoPhoenix.canWrite) {
            setFeedback('Je hebt alleen-lezen rechten. Starten van routes is niet toegestaan.', 'warning');
            return;
        }

        var knownActiveTripId = normalizeTripId(state.activeTripId);
        if (knownActiveTripId > 0) {
            state.activeTripId = knownActiveTripId;
            setFeedback('Er is al een actieve route.', 'info');
            setStatus('Actief');
            startGeolocation();
            return;
        }

        state.activeTripId = null;

        if (navigator.onLine === false) {
            setFeedback('Een nieuwe route starten vereist verbinding met de server.', 'warning');
            return;
        }

        ajaxRequest('bso_phoenix_start_trip', {
            boat_id: window.bsoPhoenix && window.bsoPhoenix.defaultBoatId ? window.bsoPhoenix.defaultBoatId : 1,
            request_uid: createRequestUid('start_trip'),
        }).then(function (result) {
            if (!result || !result.success) {
                setFeedback('Start route mislukt.', 'error');
                return;
            }

            state.activeTripId = normalizeTripId(result.data.trip_id);
            state.activeTripStartedAt = result.data.started_at || new Date().toISOString();
            setStatus('Actief');
            setMapTrip('Trip #' + state.activeTripId + ' (actief)');

            if (result.data.already_active) {
                loadTripRoute(state.activeTripId);
                setFeedback('Bestaande actieve route hervat. GPS tracking blijft actief.', 'info');
            } else {
                state.routePoints = [];
                renderRoute([]);
                setFeedback('Route gestart. GPS tracking is actief.', 'success');
            }

            ensureLiveStatsTimer();
            updateLiveStats();
            startGeolocation();
            flushQueuedRequests();
        }).catch(function (error) {
            setFeedback(error && error.message ? error.message : 'Start route mislukt. Controleer sessie of permissies.', 'error');
        });
    }

    function handleStop() {
        if (!window.bsoPhoenix || !window.bsoPhoenix.canWrite) {
            setFeedback('Je hebt alleen-lezen rechten. Stoppen van routes is niet toegestaan.', 'warning');
            return;
        }

        var activeTripId = normalizeTripId(state.activeTripId);
        if (activeTripId <= 0) {
            state.activeTripId = null;
            setFeedback('Er is geen actieve route om te stoppen.', 'info');
            return;
        }

        state.activeTripId = activeTripId;

        ajaxRequest('bso_phoenix_stop_trip', {
            trip_id: activeTripId,
            request_uid: createRequestUid('stop_trip'),
        }).then(function (result) {
            if (!result || !result.success) {
                setFeedback('Stop route mislukt.', 'error');
                return;
            }

            stopGeolocation();
            loadTripRoute(activeTripId);
            state.activeTripId = null;
            state.activeTripStartedAt = null;
            setStatus('Gestopt');
            setFeedback('Route gestopt en opgeslagen.', 'success');
            resetLiveStats();
        }).catch(function (error) {
            setFeedback(error && error.message ? error.message : 'Stop route mislukt. Controleer verbinding.', 'error');
        });
    }

    function setLogFeedback(text, type, options) {
        var nextOptions = options || {};
        notify(text, type || 'info', {
            bannerSelector: '[data-phoenix-log-feedback]',
            toast: nextOptions.toast !== false,
        });
    }

    function setLogSubmitPending(isPending) {
        var submitButton = document.querySelector('[data-phoenix-log-form] button[type="submit"]');
        if (!submitButton) {
            return;
        }

        if (isPending) {
            submitButton.setAttribute('disabled', 'disabled');
            return;
        }

        submitButton.removeAttribute('disabled');
    }

    function handleLogSubmit(event) {
        event.preventDefault();

        if (state.logSubmitInProgress) {
            return;
        }

        if (!window.bsoPhoenix || !window.bsoPhoenix.canWrite) {
            setLogFeedback('Je hebt alleen-lezen rechten. Opslaan is niet toegestaan.', 'warning');
            return;
        }

        var textNode = document.querySelector('[data-phoenix-log-text]');
        var fileNode = document.querySelector('[data-phoenix-log-photos]');
        var text = textNode ? textNode.value.trim() : '';

        if (!text) {
            setLogFeedback('Vul een notitie in voor het opslaan.', 'warning');
            return;
        }

        if (!window.bsoPhoenix || !window.bsoPhoenix.ajaxUrl) {
            setLogFeedback('Configuratie ontbreekt.', 'error');
            return;
        }

        var orderedPhotos = state.logPhotoFiles.length
            ? state.logPhotoFiles
            : Array.prototype.slice.call(fileNode && fileNode.files ? fileNode.files : []).map(function (file) {
                return {
                    file: file,
                    caption: '',
                };
            });

        state.logSubmitInProgress = true;
        setLogSubmitPending(true);

        queueOrSendForm('bso_phoenix_create_log', {
            entry_text: text,
            boat_id: String(window.bsoPhoenix.defaultBoatId || 1),
            trip_id: String(state.activeTripId || ''),
            log_date: currentLogDate(),
            log_time: currentLogTime(),
            request_uid: createRequestUid('create_log'),
        }, window.bsoPhoenix.logNonce || '', buildQueuedFiles(orderedPhotos), 'log', 'queued').then(function (result) {
            if (!result || !result.success) {
                setLogFeedback('Opslaan mislukt.', 'error');
                return;
            }

            if (textNode) {
                textNode.value = '';
            }
            if (fileNode) {
                fileNode.value = '';
            }
            state.logPhotoFiles = [];
            renderSelectedLogPhotos();
            setLogFeedback('Notitie opgeslagen' + ((result.data && result.data.attachment_ids && result.data.attachment_ids.length) ? ' met foto\'s.' : '.'), 'success');
            loadLogGallery();
            flushQueuedRequests();
        }).catch(function (error) {
            if (error && error.message === 'queued') {
                if (textNode) {
                    textNode.value = '';
                }
                if (fileNode) {
                    fileNode.value = '';
                }
                state.logPhotoFiles = [];
                renderSelectedLogPhotos();
                setLogFeedback('Geen verbinding. Notitie lokaal in wachtrij geplaatst.', 'warning');
                return;
            }
            setLogFeedback(error && error.message ? error.message : 'Opslaan mislukt. Controleer verbinding.', 'error');
        }).finally(function () {
            state.logSubmitInProgress = false;
            setLogSubmitPending(false);
        });
    }

    function setTodoFeedback(text, type, options) {
        var nextOptions = options || {};
        notify(text, type || 'info', {
            bannerSelector: '[data-phoenix-todo-feedback]',
            toast: nextOptions.toast !== false,
        });
    }

    function setTodoSubmitPending(isPending) {
        var submitButton = document.querySelector('[data-phoenix-todo-form] button[type="submit"]');
        if (!submitButton) {
            return;
        }

        if (isPending) {
            submitButton.setAttribute('disabled', 'disabled');
            return;
        }

        submitButton.removeAttribute('disabled');
    }

    function setCostFeedback(text, type, options) {
        var nextOptions = options || {};
        notify(text, type || 'info', {
            bannerSelector: '[data-phoenix-cost-feedback]',
            toast: nextOptions.toast !== false,
        });
    }

    function setCostSubmitPending(isPending) {
        var submitButton = document.querySelector('[data-phoenix-cost-form] button[type="submit"]');
        if (!submitButton) {
            return;
        }

        if (isPending) {
            submitButton.setAttribute('disabled', 'disabled');
            return;
        }

        submitButton.removeAttribute('disabled');
    }

    function handleCostSubmit(event) {
        event.preventDefault();

        if (state.costSubmitInProgress) {
            return;
        }

        if (!window.bsoPhoenix || !window.bsoPhoenix.canWrite) {
            setCostFeedback('Je hebt alleen-lezen rechten. Opslaan is niet toegestaan.', 'warning');
            return;
        }

        var typeNode = document.querySelector('[data-phoenix-cost-type]');
        var amountNode = document.querySelector('[data-phoenix-cost-amount]');
        var dateNode = document.querySelector('[data-phoenix-cost-date]');

        var cost_type = typeNode ? typeNode.value : 'other';
        var amount = amountNode ? amountNode.value.trim() : '';
        var cost_date = dateNode ? dateNode.value : '';

        if (!amount || parseFloat(amount) <= 0) {
            setCostFeedback('Vul een geldig bedrag in.', 'warning');
            return;
        }

        if (!cost_date) {
            setCostFeedback('Vul een datum in.', 'warning');
            return;
        }

        if (!window.bsoPhoenix || !window.bsoPhoenix.ajaxUrl) {
            setCostFeedback('Configuratie ontbreekt.', 'error');
            return;
        }

        state.costSubmitInProgress = true;
        setCostSubmitPending(true);

        queueOrSendJson('bso_phoenix_create_cost', {
            nonce: window.bsoPhoenix.costNonce || '',
            cost_type: cost_type,
            amount: amount,
            cost_date: cost_date,
            boat_id: window.bsoPhoenix.defaultBoatId || 1,
            trip_id: state.activeTripId || '',
            request_uid: createRequestUid('create_cost'),
        }, window.bsoPhoenix.costNonce || '', 'cost', 'queued').then(function (result) {
            if (!result || !result.success) {
                setCostFeedback('Opslaan mislukt.', 'error');
                return;
            }

            if (amountNode) {
                amountNode.value = '';
            }
            setCostFeedback('Kostenpost opgeslagen.', 'success');
            flushQueuedRequests();
        }).catch(function (error) {
            if (error && error.message === 'queued') {
                if (amountNode) {
                    amountNode.value = '';
                }
                setCostFeedback('Geen verbinding. Kostenpost lokaal in wachtrij geplaatst.', 'warning');
                return;
            }
            setCostFeedback(error && error.message ? error.message : 'Opslaan mislukt. Controleer verbinding.', 'error');
        }).finally(function () {
            state.costSubmitInProgress = false;
            setCostSubmitPending(false);
        });
    }

    function handleTodoSubmit(event) {
        event.preventDefault();

        if (state.todoSubmitInProgress) {
            return;
        }

        if (!window.bsoPhoenix || !window.bsoPhoenix.canWrite) {
            setTodoFeedback('Je hebt alleen-lezen rechten. Opslaan is niet toegestaan.', 'warning');
            return;
        }

        var titleNode = document.querySelector('[data-phoenix-todo-title]');
        var priorityNode = document.querySelector('[data-phoenix-todo-priority]');
        var title = titleNode ? titleNode.value.trim() : '';
        var priority = priorityNode ? priorityNode.value : 'normal';

        if (!title) {
            setTodoFeedback('Vul een taakomschrijving in.', 'warning');
            return;
        }

        if (!window.bsoPhoenix || !window.bsoPhoenix.ajaxUrl) {
            setTodoFeedback('Configuratie ontbreekt.', 'error');
            return;
        }

        state.todoSubmitInProgress = true;
        setTodoSubmitPending(true);

        queueOrSendJson('bso_phoenix_create_todo', {
            nonce: window.bsoPhoenix.todoNonce || '',
            title: title,
            priority: priority,
            boat_id: window.bsoPhoenix.defaultBoatId || 1,
            request_uid: createRequestUid('create_todo'),
        }, window.bsoPhoenix.todoNonce || '', 'todo', 'queued').then(function (result) {
            if (!result || !result.success) {
                setTodoFeedback('Opslaan mislukt.', 'error');
                return;
            }

            if (titleNode) {
                titleNode.value = '';
            }
            setTodoFeedback('Taak toegevoegd.', 'success');
            flushQueuedRequests();
        }).catch(function (error) {
            if (error && error.message === 'queued') {
                if (titleNode) {
                    titleNode.value = '';
                }
                setTodoFeedback('Geen verbinding. Taak lokaal in wachtrij geplaatst.', 'warning');
                return;
            }
            setTodoFeedback(error && error.message ? error.message : 'Opslaan mislukt. Controleer verbinding.', 'error');
        }).finally(function () {
            state.todoSubmitInProgress = false;
            setTodoSubmitPending(false);
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
            setSyncFeedback('Opnieuw proberen gestart voor alle wachtrij-acties.', 'info', { toast: true });
            flushQueuedRequests();
            return;
        }

        if (target.closest('[data-phoenix-queue-remove]')) {
            deleteQueuedRequest(parseInt(target.closest('[data-phoenix-queue-remove]').getAttribute('data-phoenix-queue-remove'), 10)).then(function () {
                setSyncFeedback('Actie verwijderd uit wachtrij.', 'success', { toast: true });
            }).catch(function () {
                setSyncFeedback('Verwijderen uit wachtrij mislukt.', 'error', { toast: true });
            });
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

                replayQueuedEntry(entry).then(function () {
                    setSyncFeedback('Wachtrij-actie succesvol verzonden.', 'success', { toast: true });
                }).catch(function () {
                    setSyncFeedback('Opnieuw proberen mislukt. Actie blijft in wachtrij.', 'error', { toast: true });
                });
            });
            return;
        }

        if (target.closest('[data-phoenix-log-photo-up]')) {
            moveSelectedLogPhoto(
                parseInt(target.closest('[data-phoenix-log-photo-up]').getAttribute('data-phoenix-log-photo-up'), 10),
                parseInt(target.closest('[data-phoenix-log-photo-up]').getAttribute('data-phoenix-log-photo-up'), 10) - 1
            );
            return;
        }

        if (target.closest('[data-phoenix-log-photo-down]')) {
            moveSelectedLogPhoto(
                parseInt(target.closest('[data-phoenix-log-photo-down]').getAttribute('data-phoenix-log-photo-down'), 10),
                parseInt(target.closest('[data-phoenix-log-photo-down]').getAttribute('data-phoenix-log-photo-down'), 10) + 1
            );
            return;
        }

        if (target.closest('[data-phoenix-log-photo-remove]')) {
            removeSelectedLogPhoto(parseInt(target.closest('[data-phoenix-log-photo-remove]').getAttribute('data-phoenix-log-photo-remove'), 10));
            return;
        }

        if (target.closest('[data-phoenix-gallery-save-caption]')) {
            saveExistingGalleryCaption(parseInt(target.closest('[data-phoenix-gallery-save-caption]').getAttribute('data-phoenix-gallery-save-caption'), 10));
            return;
        }

        if (target.closest('[data-phoenix-gallery-move]')) {
            moveExistingGalleryPhoto(
                parseInt(target.closest('[data-phoenix-gallery-move]').getAttribute('data-phoenix-gallery-move'), 10),
                parseInt(target.closest('[data-phoenix-gallery-move]').getAttribute('data-phoenix-gallery-step'), 10)
            );
            return;
        }

        if (target.closest('[data-phoenix-open-lightbox]')) {
            openLightbox(parseInt(target.closest('[data-phoenix-open-lightbox]').getAttribute('data-phoenix-open-lightbox'), 10));
            return;
        }

        if (target.closest('[data-phoenix-lightbox-close]')) {
            closeLightbox();
            return;
        }

        if (target.closest('[data-phoenix-lightbox-prev]')) {
            moveLightbox(-1);
            return;
        }

        if (target.closest('[data-phoenix-lightbox-next]')) {
            moveLightbox(1);
            return;
        }

        if (target.matches('[data-phoenix-lightbox]')) {
            closeLightbox();
            return;
        }

        if (target.closest('[data-phoenix-start]')) {
            handleStart();
        }

        if (target.closest('[data-phoenix-stop]')) {
            handleStop();
        }
    });

    document.addEventListener('input', function (event) {
        var target = event.target;
        if (!(target instanceof Element)) {
            return;
        }

        if (target.matches('[data-phoenix-log-photo-caption]')) {
            updateSelectedLogPhotoCaption(
                parseInt(target.getAttribute('data-phoenix-log-photo-caption'), 10),
                target.value
            );
        }
    });

    document.addEventListener('keydown', function (event) {
        if (state.lightboxIndex < 0) {
            return;
        }

        if (event.key === 'Escape') {
            closeLightbox();
            return;
        }

        if (event.key === 'ArrowLeft') {
            moveLightbox(-1);
            return;
        }

        if (event.key === 'ArrowRight') {
            moveLightbox(1);
        }
    });

    ensureMap();
    initLogPhotoInput();
    loadLogGallery();

    if (!window.bsoPhoenix || !window.bsoPhoenix.canWrite) {
        Array.prototype.slice.call(document.querySelectorAll('[data-phoenix-start], [data-phoenix-stop], [data-phoenix-log-form] button[type="submit"], [data-phoenix-todo-form] button[type="submit"], [data-phoenix-cost-form] button[type="submit"]')).forEach(function (node) {
            node.setAttribute('disabled', 'disabled');
        });

        setFeedback('Alleen-lezen modus actief.', 'info', { toast: false });
    }

    updateQueuedCount();
    resetLiveStats();
    setConnectionStatus();
    renderLatestCompletedTrip();
    if (window.bsoPhoenix && window.bsoPhoenix.activeTripId) {
        state.activeTripId = normalizeTripId(window.bsoPhoenix.activeTripId);
        state.activeTripStartedAt = window.bsoPhoenix.activeTripStartedAt || null;
        if (state.activeTripId > 0) {
            setStatus('Actief');
            setFeedback('Actieve route hervat na herladen van de pagina.', 'info', { toast: false });
            loadTripRoute(state.activeTripId);
            ensureLiveStatsTimer();
            updateLiveStats();
            startGeolocation();
        }
    } else if (window.bsoPhoenix && window.bsoPhoenix.latestTripId) {
        loadTripRoute(window.bsoPhoenix.latestTripId);
    }

    window.addEventListener('online', function () {
        setConnectionStatus();
        setSyncFeedback('Verbinding hersteld. Synchronisatie gestart...', 'info', { toast: true });
        flushQueuedRequests();
    });

    window.addEventListener('offline', function () {
        setConnectionStatus();
        setSyncFeedback('Offline. Nieuwe acties worden lokaal in de wachtrij geplaatst.', 'warning', { toast: true });
    });

    if (navigator.onLine !== false) {
        flushQueuedRequests();
    }
})();
