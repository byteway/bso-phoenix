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

    public function get_currency_code(): string
    {
        $currency = strtoupper((string) $this->get('currency'));

        return in_array($currency, array('EUR', 'USD', 'GBP'), true) ? $currency : 'EUR';
    }

    public function get_currency_symbol(): string
    {
        $map = array(
            'EUR' => 'EUR',
            'USD' => 'USD',
            'GBP' => 'GBP',
        );

        return $map[$this->get_currency_code()] ?? 'EUR';
    }

    public function get_distance_unit(): string
    {
        $unit = (string) $this->get('distance_unit');

        return in_array($unit, array('km', 'nm'), true) ? $unit : 'km';
    }

    public function convert_distance_from_km(float $distance_km): float
    {
        if ($this->get_distance_unit() === 'nm') {
            return $distance_km / 1.852;
        }

        return $distance_km;
    }

    public function format_distance(float $distance_km, int $decimals = 2): string
    {
        $unit = $this->get_distance_unit();
        $distance = $this->convert_distance_from_km($distance_km);

        return number_format_i18n($distance, $decimals) . ' ' . $unit;
    }

    public function format_money(float $amount, ?string $currency = null, int $decimals = 2): string
    {
        $code = $currency !== null && $currency !== '' ? strtoupper($currency) : $this->get_currency_code();

        return $code . ' ' . number_format_i18n($amount, $decimals);
    }

    public function get_speed_unit(): string
    {
        return $this->get_distance_unit() === 'nm' ? 'kn' : 'km/u';
    }

    public function convert_speed_from_kmh(float $speed_kmh): float
    {
        if ($this->get_distance_unit() === 'nm') {
            return $speed_kmh / 1.852;
        }

        return $speed_kmh;
    }

    public function format_speed(float $speed_kmh, int $decimals = 2): string
    {
        return number_format_i18n($this->convert_speed_from_kmh($speed_kmh), $decimals) . ' ' . $this->get_speed_unit();
    }
}
