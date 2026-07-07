<?php
if (! defined('ABSPATH')) {
    exit;
}
?>
<section class="phoenix-dashboard" aria-label="Phoenix dashboard">
    <header class="phoenix-dashboard__header">
        <h2>Phoenix Dashboard</h2>
        <div class="phoenix-dashboard__statusbar">
            <p>Status: <strong data-phoenix-status>Inactief</strong></p>
            <span class="phoenix-status-pill" data-phoenix-connection-status>Online</span>
        </div>
    </header>

    <div class="phoenix-dashboard__actions">
        <button type="button" class="phoenix-btn" data-phoenix-start>Start route</button>
        <button type="button" class="phoenix-btn phoenix-btn--ghost" data-phoenix-stop>Stop route</button>
    </div>
    <div class="phoenix-feedback-stack" data-phoenix-feedback-stack aria-live="polite" aria-atomic="false"></div>
    <p class="phoenix-dashboard__feedback phoenix-feedback-banner" data-feedback-type="info" data-phoenix-feedback>Geen actieve route.</p>
    <p class="phoenix-dashboard__feedback phoenix-feedback-banner" data-feedback-type="info" data-phoenix-sync-feedback>Synchronisatie gereed.</p>
    <section class="phoenix-live-stats" aria-label="Live vaarinformatie">
        <article class="phoenix-live-stat">
            <span class="phoenix-live-stat__label">Duur</span>
            <strong class="phoenix-live-stat__value" data-phoenix-stat-duration>00:00:00</strong>
        </article>
        <article class="phoenix-live-stat">
            <span class="phoenix-live-stat__label">Afstand</span>
            <strong class="phoenix-live-stat__value" data-phoenix-stat-distance>0.00</strong>
        </article>
        <article class="phoenix-live-stat">
            <span class="phoenix-live-stat__label">Snelheid</span>
            <strong class="phoenix-live-stat__value" data-phoenix-stat-speed>0.00</strong>
        </article>
        <article class="phoenix-live-stat">
            <span class="phoenix-live-stat__label">Brandstof</span>
            <strong class="phoenix-live-stat__value" data-phoenix-stat-fuel>0.00 l</strong>
        </article>
        <article class="phoenix-live-stat">
            <span class="phoenix-live-stat__label">Laatste update</span>
            <strong class="phoenix-live-stat__value" data-phoenix-stat-updated>-</strong>
        </article>
    </section>
    <section class="phoenix-trip-summary" aria-label="Tochtsamenvattingen">
        <article class="phoenix-card phoenix-trip-summary__card">
            <h3>Actieve tocht</h3>
            <p class="phoenix-trip-summary__empty" data-phoenix-active-trip-empty>Geen actieve tocht.</p>
            <dl class="phoenix-trip-summary__list" data-phoenix-active-trip-list>
                <div><dt>Trip</dt><dd data-phoenix-active-trip-id>-</dd></div>
                <div><dt>Start</dt><dd data-phoenix-active-trip-start>-</dd></div>
                <div><dt>Afstand</dt><dd data-phoenix-active-trip-distance>-</dd></div>
                <div><dt>Brandstof</dt><dd data-phoenix-active-trip-fuel>-</dd></div>
            </dl>
        </article>
        <article class="phoenix-card phoenix-trip-summary__card">
            <h3>Laatste afgeronde tocht</h3>
            <p class="phoenix-trip-summary__empty" data-phoenix-latest-trip-empty>Nog geen afgeronde tocht beschikbaar.</p>
            <dl class="phoenix-trip-summary__list" data-phoenix-latest-trip-list>
                <div><dt>Trip</dt><dd data-phoenix-latest-trip-id>-</dd></div>
                <div><dt>Einde</dt><dd data-phoenix-latest-trip-end>-</dd></div>
                <div><dt>Afstand</dt><dd data-phoenix-latest-trip-distance>-</dd></div>
                <div><dt>Duur</dt><dd data-phoenix-latest-trip-duration>-</dd></div>
            </dl>
        </article>
    </section>

    <section class="phoenix-trip-list" aria-label="Recente tochten">
        <div class="phoenix-queue__header">
            <h3>Recente tochten</h3>
        </div>
        <p class="phoenix-queue__empty" data-phoenix-trip-list-empty>Nog geen afgeronde tochten beschikbaar.</p>
        <ul class="phoenix-trip-list__items" data-phoenix-trip-list></ul>
    </section>

    <section class="phoenix-queue" aria-label="Offline wachtrij">
        <div class="phoenix-queue__header">
            <h3>Offline wachtrij</h3>
            <button type="button" class="phoenix-btn phoenix-btn--ghost phoenix-btn--small" data-phoenix-queue-retry-all>Probeer alles opnieuw</button>
        </div>
        <p class="phoenix-queue__empty" data-phoenix-queue-empty>Geen acties in de wachtrij.</p>
        <ul class="phoenix-queue__list" data-phoenix-queue-list></ul>
    </section>

    <div class="phoenix-dashboard__grid">
        <article class="phoenix-card" data-phoenix-map-card>
            <div class="phoenix-map__header">
                <h3>Live route</h3>
                <button type="button" class="phoenix-btn phoenix-btn--ghost phoenix-btn--small" data-phoenix-map-fullscreen-toggle aria-pressed="false" aria-label="<?php esc_attr_e('Schakel kaart naar volledig scherm', 'bso-phoenix'); ?>"><?php esc_html_e('Volledig scherm', 'bso-phoenix'); ?></button>
            </div>
            <div data-phoenix-map-home>
                <div class="phoenix-map" data-phoenix-map aria-label="Routekaart"></div>
            </div>
            <div class="phoenix-map__meta">
                <p><strong><?php esc_html_e('Trip', 'bso-phoenix'); ?>:</strong> <span data-phoenix-map-trip><?php esc_html_e('Nog geen route geladen', 'bso-phoenix'); ?></span></p>
                <p><strong><?php esc_html_e('Trackpoints', 'bso-phoenix'); ?>:</strong> <span data-phoenix-map-points>0</span></p>
                <p><strong><?php esc_html_e('Afstand', 'bso-phoenix'); ?>:</strong> <span data-phoenix-map-distance>0</span></p>
            </div>
        </article>

        <div class="phoenix-fullscreen" data-phoenix-map-fullscreen hidden>
            <button type="button" class="phoenix-fullscreen__close" data-phoenix-map-fullscreen-close aria-label="<?php esc_attr_e('Sluit volledig scherm', 'bso-phoenix'); ?>"><?php esc_html_e('Sluiten', 'bso-phoenix'); ?></button>
            <div class="phoenix-fullscreen__map" data-phoenix-map-fullscreen-content></div>
        </div>

        <article class="phoenix-card">
            <h3><?php esc_html_e("Captain's log", 'bso-phoenix'); ?></h3>
            <form data-phoenix-log-form style="display:flex;flex-direction:column;gap:8px;margin-top:8px;" enctype="multipart/form-data">
                <textarea data-phoenix-log-text rows="3" placeholder="<?php esc_attr_e('Notitie...', 'bso-phoenix'); ?>" style="width:100%;resize:vertical;"></textarea>
                <input type="file" data-phoenix-log-photos accept="image/*" multiple />
                <p class="phoenix-log-photos__hint" data-phoenix-log-photo-empty><?php esc_html_e('Geen foto\'s geselecteerd.', 'bso-phoenix'); ?></p>
                <ul class="phoenix-log-photos" data-phoenix-log-photo-list></ul>
                <button type="submit" class="phoenix-btn"><?php esc_html_e('Opslaan', 'bso-phoenix'); ?></button>
            </form>
            <p class="phoenix-feedback-banner phoenix-feedback-banner--small" data-feedback-type="info" data-phoenix-log-feedback style="margin-top:8px;"></p>

            <div class="phoenix-log-gallery" data-phoenix-log-gallery-wrap>
                <h4><?php esc_html_e('Recente logfoto\'s', 'bso-phoenix'); ?></h4>
                <p class="phoenix-log-gallery__empty" data-phoenix-log-gallery-empty><?php esc_html_e('Nog geen logfoto\'s beschikbaar.', 'bso-phoenix'); ?></p>
                <ul class="phoenix-log-gallery__list" data-phoenix-log-gallery-list></ul>
            </div>
        </article>

        <article class="phoenix-card" id="phoenix-todo-card">
            <h3><?php esc_html_e('TODO', 'bso-phoenix'); ?></h3>
            <form data-phoenix-todo-form style="display:flex;flex-direction:column;gap:8px;margin-top:8px;">
                <input type="text" data-phoenix-todo-title placeholder="<?php esc_attr_e('Taakomschrijving...', 'bso-phoenix'); ?>" style="width:100%;" />
                <select data-phoenix-todo-priority>
                    <option value="high"><?php esc_html_e('Hoog', 'bso-phoenix'); ?></option>
                    <option value="normal" selected><?php esc_html_e('Normaal', 'bso-phoenix'); ?></option>
                    <option value="low"><?php esc_html_e('Laag', 'bso-phoenix'); ?></option>
                </select>
                <button type="submit" class="phoenix-btn"><?php esc_html_e('Taak toevoegen', 'bso-phoenix'); ?></button>
            </form>
            <p class="phoenix-feedback-banner phoenix-feedback-banner--small" data-feedback-type="info" data-phoenix-todo-feedback style="margin-top:8px;"></p>
        </article>

        <article class="phoenix-card" id="phoenix-cost-card">
            <h3><?php esc_html_e('Kosten', 'bso-phoenix'); ?></h3>
            <form data-phoenix-cost-form style="display:flex;flex-direction:column;gap:8px;margin-top:8px;">
                <select data-phoenix-cost-type>
                    <option value="fuel"><?php esc_html_e('Brandstof', 'bso-phoenix'); ?></option>
                    <option value="maintenance"><?php esc_html_e('Onderhoud', 'bso-phoenix'); ?></option>
                    <option value="mooring"><?php esc_html_e('Ligplaats', 'bso-phoenix'); ?></option>
                    <option value="insurance"><?php esc_html_e('Verzekering', 'bso-phoenix'); ?></option>
                    <option value="parts"><?php esc_html_e('Onderdelen', 'bso-phoenix'); ?></option>
                    <option value="other"><?php esc_html_e('Overig', 'bso-phoenix'); ?></option>
                </select>
                <input type="number" data-phoenix-cost-amount min="0.01" step="0.01" placeholder="<?php esc_attr_e('Bedrag (€)', 'bso-phoenix'); ?>" style="width:100%;" />
                <input type="date" data-phoenix-cost-date value="<?php echo esc_attr(current_time('Y-m-d')); ?>" style="width:100%;" />
                <button type="submit" class="phoenix-btn"><?php esc_html_e('Kostenpost opslaan', 'bso-phoenix'); ?></button>
            </form>
            <p class="phoenix-feedback-banner phoenix-feedback-banner--small" data-feedback-type="info" data-phoenix-cost-feedback style="margin-top:8px;"></p>
        </article>
    </div>

    <div class="phoenix-lightbox" data-phoenix-lightbox hidden>
        <button type="button" class="phoenix-lightbox__close" data-phoenix-lightbox-close aria-label="<?php esc_attr_e('Sluiten', 'bso-phoenix'); ?>">&times;</button>
        <button type="button" class="phoenix-lightbox__nav phoenix-lightbox__nav--prev" data-phoenix-lightbox-prev aria-label="<?php esc_attr_e('Vorige foto', 'bso-phoenix'); ?>">&#8249;</button>
        <figure class="phoenix-lightbox__figure">
            <img src="" alt="" data-phoenix-lightbox-image />
            <figcaption data-phoenix-lightbox-caption></figcaption>
        </figure>
        <button type="button" class="phoenix-lightbox__nav phoenix-lightbox__nav--next" data-phoenix-lightbox-next aria-label="<?php esc_attr_e('Volgende foto', 'bso-phoenix'); ?>">&#8250;</button>
    </div>
</section>
