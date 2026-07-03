<?php

if (! defined('ABSPATH')) {
    exit;
}

class BSO_Phoenix_Settings_Service
{
    private const DEFAULTS = array(
        'gps_interval_seconds'      => 10,
        'fuel_use_lph'              => 5.0,
        'currency'                  => 'EUR',
        'distance_unit'             => 'km',
        'delete_data_on_uninstall'  => '0',
    );

    public function get(string $key)
    {
        global $wpdb;

        if (! array_key_exists($key, self::DEFAULTS)) {
            return null;
        }

        $table = $wpdb->prefix . 'phoenix_settings';
        $value = $wpdb->get_var(
            $wpdb->prepare("SELECT option_value FROM {$table} WHERE option_key = %s", $key)
        );

        return $value !== null ? $value : self::DEFAULTS[$key];
    }

    public function get_all(): array
    {
        $result = array();
        foreach (array_keys(self::DEFAULTS) as $key) {
            $result[$key] = $this->get($key);
        }

        return $result;
    }

    public function set(string $key, string $value): bool
    {
        global $wpdb;

        if (! array_key_exists($key, self::DEFAULTS)) {
            return false;
        }

        $table = $wpdb->prefix . 'phoenix_settings';
        $existing = $wpdb->get_var(
            $wpdb->prepare("SELECT id FROM {$table} WHERE option_key = %s", $key)
        );

        if ($existing) {
            $result = $wpdb->update(
                $table,
                array('option_value' => $value),
                array('option_key' => $key),
                array('%s'),
                array('%s')
            );
        } else {
            $result = $wpdb->insert(
                $table,
                array('option_key' => $key, 'option_value' => $value),
                array('%s', '%s')
            );
        }

        return $result !== false;
    }

    public function save_all(array $data): void
    {
        foreach (array_keys(self::DEFAULTS) as $key) {
            if (array_key_exists($key, $data)) {
                $this->set($key, (string) $data[$key]);
            }
        }
    }

    public static function defaults(): array
    {
        return self::DEFAULTS;
    }
}
