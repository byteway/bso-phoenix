<?php

if (! defined('ABSPATH')) {
    exit;
}

class BSO_Phoenix_Cost_Service
{
    private const VALID_TYPES = array('fuel', 'maintenance', 'mooring', 'insurance', 'parts', 'other');

    public function create_cost(int $boat_id, string $cost_type, float $amount, string $cost_date, string $currency, string $supplier, string $notes, ?int $trip_id): int
    {
        global $wpdb;

        $table = $wpdb->prefix . 'phoenix_costs';
        $now = current_time('mysql');

        $wpdb->insert(
            $table,
            array(
                'boat_id' => $boat_id,
                'trip_id' => $trip_id,
                'cost_type' => in_array($cost_type, self::VALID_TYPES, true) ? $cost_type : 'other',
                'amount' => $amount,
                'currency' => $currency !== '' ? strtoupper(substr($currency, 0, 10)) : 'EUR',
                'cost_date' => $cost_date,
                'supplier' => $supplier,
                'notes' => $notes,
                'created_at' => $now,
            ),
            array('%d', '%d', '%s', '%f', '%s', '%s', '%s', '%s', '%s')
        );

        return (int) $wpdb->insert_id;
    }

    public function update_cost(int $cost_id, string $cost_type, float $amount, string $cost_date, string $currency, string $supplier, string $notes): bool
    {
        global $wpdb;

        $updated = $wpdb->update(
            $wpdb->prefix . 'phoenix_costs',
            array(
                'cost_type' => in_array($cost_type, self::VALID_TYPES, true) ? $cost_type : 'other',
                'amount' => $amount,
                'currency' => $currency !== '' ? strtoupper(substr($currency, 0, 10)) : 'EUR',
                'cost_date' => $cost_date,
                'supplier' => $supplier,
                'notes' => $notes,
            ),
            array('id' => $cost_id),
            array('%s', '%f', '%s', '%s', '%s', '%s'),
            array('%d')
        );

        return $updated !== false;
    }

    public function delete_cost(int $cost_id): bool
    {
        global $wpdb;

        $deleted = $wpdb->delete(
            $wpdb->prefix . 'phoenix_costs',
            array('id' => $cost_id),
            array('%d')
        );

        return $deleted !== false;
    }

    public function get_cost_by_id(int $cost_id): ?array
    {
        global $wpdb;

        $table = $wpdb->prefix . 'phoenix_costs';
        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id, boat_id, trip_id, cost_type, amount, currency, cost_date, supplier, notes, created_at
                FROM {$table} WHERE id = %d",
                $cost_id
            ),
            ARRAY_A
        );

        return is_array($row) ? $row : null;
    }

    public function get_costs(string $date_from = '', string $date_to = '', string $cost_type = '', int $limit = 100): array
    {
        global $wpdb;

        $table = $wpdb->prefix . 'phoenix_costs';
        $sql = "SELECT id, boat_id, trip_id, cost_type, amount, currency, cost_date, supplier, notes, created_at FROM {$table}";

        $where = array();
        $args = array();

        if ($date_from !== '') {
            $where[] = 'cost_date >= %s';
            $args[] = $date_from;
        }

        if ($date_to !== '') {
            $where[] = 'cost_date <= %s';
            $args[] = $date_to;
        }

        if ($cost_type !== '' && in_array($cost_type, self::VALID_TYPES, true)) {
            $where[] = 'cost_type = %s';
            $args[] = $cost_type;
        }

        if (! empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY cost_date DESC, id DESC LIMIT %d';
        $args[] = max(1, min(1000, $limit));

        $rows = $wpdb->get_results($wpdb->prepare($sql, ...$args), ARRAY_A);

        return is_array($rows) ? $rows : array();
    }

    public function get_summary(string $date_from = '', string $date_to = ''): array
    {
        global $wpdb;

        $table = $wpdb->prefix . 'phoenix_costs';
        $where = array();
        $args = array();

        if ($date_from !== '') {
            $where[] = 'cost_date >= %s';
            $args[] = $date_from;
        }

        if ($date_to !== '') {
            $where[] = 'cost_date <= %s';
            $args[] = $date_to;
        }

        $where_sql = ! empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT cost_type, SUM(amount) AS total FROM {$table} {$where_sql} GROUP BY cost_type ORDER BY total DESC";
        $rows = ! empty($args)
            ? $wpdb->get_results($wpdb->prepare($sql, ...$args), ARRAY_A)
            : $wpdb->get_results($sql, ARRAY_A);

        $total_sql = "SELECT COALESCE(SUM(amount), 0) AS grand_total FROM {$table} {$where_sql}";
        $grand_total = ! empty($args)
            ? (float) $wpdb->get_var($wpdb->prepare($total_sql, ...$args))
            : (float) $wpdb->get_var($total_sql);

        return array(
            'by_type' => is_array($rows) ? $rows : array(),
            'grand_total' => $grand_total,
        );
    }

    public static function valid_types(): array
    {
        return self::VALID_TYPES;
    }
}
