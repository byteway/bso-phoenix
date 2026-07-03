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

    <div class="phoenix-dashboard__grid">
        <article class="phoenix-card">
            <h3>Live route</h3>
            <p>Hier komt de live GPS-preview in volgende iteratie.</p>
        </article>

        <article class="phoenix-card">
            <h3><?php esc_html_e("Captain's log", 'bso-phoenix'); ?></h3>
            <form data-phoenix-log-form style="display:flex;flex-direction:column;gap:8px;margin-top:8px;">
                <textarea data-phoenix-log-text rows="3" placeholder="<?php esc_attr_e('Notitie...', 'bso-phoenix'); ?>" style="width:100%;resize:vertical;"></textarea>
                <button type="submit" class="phoenix-btn"><?php esc_html_e('Opslaan', 'bso-phoenix'); ?></button>
            </form>
            <p data-phoenix-log-feedback style="margin-top:8px;font-size:13px;"></p>
        </article>

        <article class="phoenix-card">
            <h3>TODO en kosten</h3>
            <p>Onderhoudstaken en kostenoverzicht verschijnen hier.</p>
        </article>
    </div>
</section>
