<?php

if (! defined('ABSPATH')) {
    exit;
}

class BSO_Phoenix_Access_Admin
{
    private const META_OVERRIDE_KEY = 'bso_phoenix_access_override';

    private const OVERRIDES = array(
        'inherit' => 'Overnemen van WordPress rol',
        'owner' => 'Phoenix eigenaar',
        'crew' => 'Phoenix bemanning',
        'reader' => 'Phoenix alleen-lezen',
        'none' => 'Geen Phoenix toegang',
    );

    public function init(): void
    {
        add_action('admin_menu', array($this, 'register_submenu'));
        add_action('admin_post_bso_phoenix_save_access_overrides', array($this, 'handle_save_overrides'));
    }

    public function register_submenu(): void
    {
        add_submenu_page(
            'bso-phoenix',
            __('Toegang en rollen', 'bso-phoenix'),
            __('Toegang', 'bso-phoenix'),
            BSO_PHOENIX_CAP_MANAGE,
            'bso-phoenix-access',
            array($this, 'render_page')
        );
    }

    public function render_page(): void
    {
        if (! current_user_can(BSO_PHOENIX_CAP_MANAGE)) {
            wp_die(esc_html__('Je hebt geen rechten om deze pagina te bekijken.', 'bso-phoenix'));
        }

        $users = get_users(
            array(
                'number' => 500,
                'orderby' => 'display_name',
                'order' => 'ASC',
            )
        );

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Toegang en rollen', 'bso-phoenix') . '</h1>';
        echo '<p>' . esc_html__('Koppel gebruikers aan Phoenix-toegangsniveaus zonder de standaard WordPress-rol te wijzigen.', 'bso-phoenix') . '</p>';

        if (isset($_GET['saved'])) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Toegangsinstellingen opgeslagen.', 'bso-phoenix') . '</p></div>';
        }

        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        echo '<input type="hidden" name="action" value="bso_phoenix_save_access_overrides" />';
        wp_nonce_field('bso_phoenix_save_access_overrides', 'bso_phoenix_access_nonce');

        echo '<table class="widefat striped">';
        echo '<thead><tr>';
        echo '<th>' . esc_html__('Gebruiker', 'bso-phoenix') . '</th>';
        echo '<th>' . esc_html__('WordPress rollen', 'bso-phoenix') . '</th>';
        echo '<th>' . esc_html__('Effectieve Phoenix toegang', 'bso-phoenix') . '</th>';
        echo '<th>' . esc_html__('Override', 'bso-phoenix') . '</th>';
        echo '</tr></thead><tbody>';

        if (empty($users)) {
            echo '<tr><td colspan="4">' . esc_html__('Geen gebruikers gevonden.', 'bso-phoenix') . '</td></tr>';
        } else {
            foreach ($users as $user) {
                $override = $this->get_user_override((int) $user->ID);
                $roles = array_map('translate_user_role', (array) $user->roles);

                echo '<tr>';
                echo '<td><strong>' . esc_html($user->display_name) . '</strong><br /><code>' . esc_html($user->user_email) . '</code></td>';
                echo '<td>' . esc_html(! empty($roles) ? implode(', ', $roles) : '-') . '</td>';
                echo '<td>' . esc_html($this->format_effective_access($user)) . '</td>';
                echo '<td>';
                echo '<select name="phoenix_override[' . esc_attr((string) $user->ID) . ']">';
                foreach (self::OVERRIDES as $value => $label) {
                    echo '<option value="' . esc_attr($value) . '"' . selected($override, $value, false) . '>' . esc_html($label) . '</option>';
                }
                echo '</select>';
                echo '</td>';
                echo '</tr>';
            }
        }

        echo '</tbody></table>';
        submit_button(__('Toegang opslaan', 'bso-phoenix'));
        echo '</form>';
        echo '</div>';
    }

    public function handle_save_overrides(): void
    {
        if (! current_user_can(BSO_PHOENIX_CAP_MANAGE)) {
            wp_die(esc_html__('Je hebt geen rechten om toegang te wijzigen.', 'bso-phoenix'));
        }

        check_admin_referer('bso_phoenix_save_access_overrides', 'bso_phoenix_access_nonce');

        $overrides = isset($_POST['phoenix_override']) && is_array($_POST['phoenix_override'])
            ? wp_unslash($_POST['phoenix_override'])
            : array();

        foreach ($overrides as $user_id_raw => $override_raw) {
            $user_id = (int) $user_id_raw;
            if ($user_id <= 0 || ! get_user_by('id', $user_id)) {
                continue;
            }

            $override = sanitize_key((string) $override_raw);
            if (! isset(self::OVERRIDES[$override])) {
                $override = 'inherit';
            }

            $this->apply_user_override($user_id, $override);
        }

        wp_safe_redirect(admin_url('admin.php?page=bso-phoenix-access&saved=1'));
        exit;
    }

    private function apply_user_override(int $user_id, string $override): void
    {
        $user = new WP_User($user_id);
        if (! $user->exists()) {
            return;
        }

        $user->remove_cap(BSO_PHOENIX_CAP_READ);
        $user->remove_cap(BSO_PHOENIX_CAP_WRITE);
        $user->remove_cap(BSO_PHOENIX_CAP_MANAGE);

        if ($override === 'inherit') {
            delete_user_meta($user_id, self::META_OVERRIDE_KEY);
            return;
        }

        update_user_meta($user_id, self::META_OVERRIDE_KEY, $override);

        if ($override === 'owner') {
            $user->add_cap(BSO_PHOENIX_CAP_READ);
            $user->add_cap(BSO_PHOENIX_CAP_WRITE);
            $user->add_cap(BSO_PHOENIX_CAP_MANAGE);
            return;
        }

        if ($override === 'crew') {
            $user->add_cap(BSO_PHOENIX_CAP_READ);
            $user->add_cap(BSO_PHOENIX_CAP_WRITE);
            return;
        }

        if ($override === 'reader') {
            $user->add_cap(BSO_PHOENIX_CAP_READ);
        }
    }

    private function get_user_override(int $user_id): string
    {
        $stored = get_user_meta($user_id, self::META_OVERRIDE_KEY, true);
        $value = is_string($stored) ? sanitize_key($stored) : 'inherit';

        return isset(self::OVERRIDES[$value]) ? $value : 'inherit';
    }

    private function format_effective_access(WP_User $user): string
    {
        $can_manage = user_can($user, BSO_PHOENIX_CAP_MANAGE);
        $can_write = user_can($user, BSO_PHOENIX_CAP_WRITE);
        $can_read = user_can($user, BSO_PHOENIX_CAP_READ);

        if ($can_manage) {
            return __('Eigenaar (beheer)', 'bso-phoenix');
        }

        if ($can_write) {
            return __('Bemanning (schrijven)', 'bso-phoenix');
        }

        if ($can_read) {
            return __('Alleen-lezen', 'bso-phoenix');
        }

        return __('Geen toegang', 'bso-phoenix');
    }
}