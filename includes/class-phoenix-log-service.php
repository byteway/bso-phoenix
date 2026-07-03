<?php

if (! defined('ABSPATH')) {
    exit;
}

class BSO_Phoenix_Log_Service
{
    public function create_log(int $boat_id, string $entry_text, ?int $trip_id, ?string $log_date, ?string $log_time): int
    {
        global $wpdb;

        $table = $wpdb->prefix . 'phoenix_captains_logs';
        $now = current_time('mysql');

        $wpdb->insert(
            $table,
            array(
                'boat_id' => $boat_id,
                'trip_id' => $trip_id,
                'log_date' => $log_date ?: current_time('Y-m-d'),
                'log_time' => $log_time ?: current_time('H:i:s'),
                'entry_text' => $entry_text,
                'created_at' => $now,
                'updated_at' => $now,
            ),
            array('%d', '%d', '%s', '%s', '%s', '%s', '%s')
        );

        return (int) $wpdb->insert_id;
    }

    public function update_log(int $log_id, string $entry_text): bool
    {
        global $wpdb;

        $table = $wpdb->prefix . 'phoenix_captains_logs';

        $updated = $wpdb->update(
            $table,
            array(
                'entry_text' => $entry_text,
                'updated_at' => current_time('mysql'),
            ),
            array('id' => $log_id),
            array('%s', '%s'),
            array('%d')
        );

        return $updated !== false;
    }

    public function delete_log(int $log_id): bool
    {
        global $wpdb;

        $table = $wpdb->prefix . 'phoenix_captains_logs';
        $wpdb->delete($wpdb->prefix . 'phoenix_log_photos', array('log_id' => $log_id), array('%d'));
        $deleted = $wpdb->delete($table, array('id' => $log_id), array('%d'));

        return $deleted !== false;
    }

    public function get_log_by_id(int $log_id): ?array
    {
        global $wpdb;

        $table = $wpdb->prefix . 'phoenix_captains_logs';
        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id, boat_id, trip_id, log_date, log_time, entry_text, created_at, updated_at
                FROM {$table} WHERE id = %d",
                $log_id
            ),
            ARRAY_A
        );

        return is_array($row) ? $row : null;
    }

    public function get_logs(string $date_from = '', string $date_to = '', int $limit = 50): array
    {
        global $wpdb;

        $table = $wpdb->prefix . 'phoenix_captains_logs';
        $sql = "SELECT id, boat_id, trip_id, log_date, log_time, entry_text, created_at, updated_at FROM {$table}";

        $where = array();
        $args = array();

        if ($date_from !== '') {
            $where[] = 'log_date >= %s';
            $args[] = $date_from;
        }

        if ($date_to !== '') {
            $where[] = 'log_date <= %s';
            $args[] = $date_to;
        }

        if (! empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY log_date DESC, log_time DESC LIMIT %d';
        $args[] = max(1, min(1000, $limit));

        $rows = $wpdb->get_results($wpdb->prepare($sql, ...$args), ARRAY_A);

        return is_array($rows) ? $rows : array();
    }
}
