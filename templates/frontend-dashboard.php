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
    </div>
</section>
