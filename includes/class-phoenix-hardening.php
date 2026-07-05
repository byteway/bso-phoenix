<?php

if (! defined('ABSPATH')) {
    exit;
}

class BSO_Phoenix_Hardening
{
    public static function normalize_date(string $value, int $min_year = 2000, int $max_year = 2100): string
    {
        if (! preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $matches)) {
            return '';
        }

        $year = (int) $matches[1];
        $month = (int) $matches[2];
        $day = (int) $matches[3];

        if ($year < $min_year || $year > $max_year) {
            return '';
        }

        if (! checkdate($month, $day, $year)) {
            return '';
        }

        return sprintf('%04d-%02d-%02d', $year, $month, $day);
    }

    public static function normalize_time(string $value): ?string
    {
        if ($value === '') {
            return null;
        }

        if (! preg_match('/^(\d{2}):(\d{2})(?::(\d{2}))?$/', $value, $matches)) {
            return null;
        }

        $hours = (int) $matches[1];
        $minutes = (int) $matches[2];
        $seconds = isset($matches[3]) ? (int) $matches[3] : 0;

        if ($hours > 23 || $minutes > 59 || $seconds > 59) {
            return null;
        }

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
    }

    public static function is_valid_date_range(string $date_from, string $date_to): bool
    {
        if ($date_from === '' || $date_to === '') {
            return true;
        }

        return strcmp($date_from, $date_to) <= 0;
    }

    public static function is_duplicate_submission(string $scope, array $payload, int $ttl_seconds = 20): bool
    {
        $user_id = get_current_user_id();
        $fingerprint = md5(wp_json_encode(array($scope, $user_id, $payload)) ?: '');
        $transient_key = 'bso_phx_dup_' . $fingerprint;

        if (get_transient($transient_key)) {
            return true;
        }

        set_transient($transient_key, 1, max(5, $ttl_seconds));

        return false;
    }
}