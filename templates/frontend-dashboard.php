<?php
if (! defined('ABSPATH')) {
    exit;
}
?>
<section class="phoenix-dashboard" aria-label="Phoenix dashboard">
    <header class="phoenix-dashboard__header">
        <h2>Phoenix Dashboard</h2>
        <p>Status: <strong data-phoenix-status>Inactief</strong></p>
    </header>

    <div class="phoenix-dashboard__actions">
        <button type="button" class="phoenix-btn" data-phoenix-start>Start route</button>
        <button type="button" class="phoenix-btn phoenix-btn--ghost" data-phoenix-stop>Stop route</button>
    </div>
    <p class="phoenix-dashboard__feedback" data-phoenix-feedback>Geen actieve route.</p>
    <p class="phoenix-dashboard__feedback" data-phoenix-sync-feedback>Synchronisatie gereed.</p>
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
    <section class="phoenix-queue" aria-label="Offline wachtrij">
        <div class="phoenix-queue__header">
            <h3>Offline wachtrij</h3>
            <button type="button" class="phoenix-btn phoenix-btn--ghost phoenix-btn--small" data-phoenix-queue-retry-all>Probeer alles opnieuw</button>
        </div>
        <p class="phoenix-queue__empty" data-phoenix-queue-empty>Geen acties in de wachtrij.</p>
        <ul class="phoenix-queue__list" data-phoenix-queue-list></ul>
    </section>

    <div class="phoenix-dashboard__grid">
        <article class="phoenix-card">
            <h3>Live route</h3>
            <div class="phoenix-map" data-phoenix-map aria-label="Routekaart"></div>
            <div class="phoenix-map__meta">
                <p><strong><?php esc_html_e('Trip', 'bso-phoenix'); ?>:</strong> <span data-phoenix-map-trip><?php esc_html_e('Nog geen route geladen', 'bso-phoenix'); ?></span></p>
                <p><strong><?php esc_html_e('Trackpoints', 'bso-phoenix'); ?>:</strong> <span data-phoenix-map-points>0</span></p>
                <p><strong><?php esc_html_e('Afstand', 'bso-phoenix'); ?>:</strong> <span data-phoenix-map-distance>0</span></p>
            </div>
        </article>

        <article class="phoenix-card">
            <h3><?php esc_html_e("Captain's log", 'bso-phoenix'); ?></h3>
            <form data-phoenix-log-form style="display:flex;flex-direction:column;gap:8px;margin-top:8px;" enctype="multipart/form-data">
                <textarea data-phoenix-log-text rows="3" placeholder="<?php esc_attr_e('Notitie...', 'bso-phoenix'); ?>" style="width:100%;resize:vertical;"></textarea>
                <input type="file" data-phoenix-log-photos accept="image/*" multiple />
                <button type="submit" class="phoenix-btn"><?php esc_html_e('Opslaan', 'bso-phoenix'); ?></button>
            </form>
            <p data-phoenix-log-feedback style="margin-top:8px;font-size:13px;"></p>
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
            <p data-phoenix-todo-feedback style="margin-top:8px;font-size:13px;"></p>
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
            <p data-phoenix-cost-feedback style="margin-top:8px;font-size:13px;"></p>
        </article>
    </div>
</section>
