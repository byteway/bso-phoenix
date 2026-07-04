<?php

if (! defined('ABSPATH')) {
    exit;
}

class BSO_Phoenix_Todo_Service
{
    private const VALID_STATUSES = array('open', 'in_progress', 'done');
    private const VALID_PRIORITIES = array('low', 'normal', 'high');

    public function find_recent_duplicate_todo_id(int $boat_id, string $title, string $priority, ?string $due_date, int $window_seconds = 25, int $exclude_todo_id = 0): int
    {
        global $wpdb;

        $table = $wpdb->prefix . 'phoenix_todos';
        $threshold = date('Y-m-d H:i:s', current_time('timestamp') - max(5, $window_seconds));
        $normalized_priority = in_array($priority, self::VALID_PRIORITIES, true) ? $priority : 'normal';

        $sql = "SELECT id FROM {$table} WHERE boat_id = %d AND title = %s AND priority = %s AND status = 'open' AND created_at >= %s";
        $args = array($boat_id, $title, $normalized_priority, $threshold);

        if ($due_date !== null && $due_date !== '') {
            $sql .= ' AND due_date = %s';
            $args[] = $due_date;
        } else {
            $sql .= " AND (due_date IS NULL OR due_date = '')";
        }

        if ($exclude_todo_id > 0) {
            $sql .= ' AND id <> %d';
            $args[] = $exclude_todo_id;
        }

        $sql .= ' ORDER BY id ASC LIMIT 1';

        $duplicate_id = (int) $wpdb->get_var($wpdb->prepare($sql, ...$args));

        return $duplicate_id > 0 ? $duplicate_id : 0;
    }

    public function create_todo(int $boat_id, string $title, string $description, string $priority, ?string $due_date): int
    {
        global $wpdb;

        $table = $wpdb->prefix . 'phoenix_todos';
        $now = current_time('mysql');

        $wpdb->insert(
            $table,
            array(
                'boat_id' => $boat_id,
                'title' => $title,
                'description' => $description,
                'status' => 'open',
                'priority' => in_array($priority, self::VALID_PRIORITIES, true) ? $priority : 'normal',
                'due_date' => $due_date,
                'completed_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );

        return (int) $wpdb->insert_id;
    }

    public function update_status(int $todo_id, string $status): bool
    {
        global $wpdb;

        if (! in_array($status, self::VALID_STATUSES, true)) {
            return false;
        }

        $table = $wpdb->prefix . 'phoenix_todos';
        $now = current_time('mysql');

        $updated = $wpdb->update(
            $table,
            array(
                'status' => $status,
                'completed_at' => $status === 'done' ? $now : null,
                'updated_at' => $now,
            ),
            array('id' => $todo_id),
            array('%s', '%s', '%s'),
            array('%d')
        );

        return $updated !== false;
    }

    public function update_todo(int $todo_id, string $title, string $description, string $priority, string $status, ?string $due_date): bool
    {
        global $wpdb;

        if (! in_array($priority, self::VALID_PRIORITIES, true)) {
            $priority = 'normal';
        }

        if (! in_array($status, self::VALID_STATUSES, true)) {
            $status = 'open';
        }

        $table = $wpdb->prefix . 'phoenix_todos';
        $now = current_time('mysql');

        $updated = $wpdb->update(
            $table,
            array(
                'title' => $title,
                'description' => $description,
                'priority' => $priority,
                'status' => $status,
                'due_date' => $due_date,
                'completed_at' => $status === 'done' ? $now : null,
                'updated_at' => $now,
            ),
            array('id' => $todo_id),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s'),
            array('%d')
        );

        return $updated !== false;
    }

    public function delete_todo(int $todo_id): bool
    {
        global $wpdb;

        $deleted = $wpdb->delete(
            $wpdb->prefix . 'phoenix_todos',
            array('id' => $todo_id),
            array('%d')
        );

        return $deleted !== false;
    }

    public function get_todo_by_id(int $todo_id): ?array
    {
        global $wpdb;

        $table = $wpdb->prefix . 'phoenix_todos';
        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id, boat_id, title, description, status, priority, due_date, completed_at, created_at, updated_at
                FROM {$table} WHERE id = %d",
                $todo_id
            ),
            ARRAY_A
        );

        return is_array($row) ? $row : null;
    }

    public function get_todos(string $status = '', string $priority = '', int $limit = 100): array
    {
        global $wpdb;

        $table = $wpdb->prefix . 'phoenix_todos';
        $sql = "SELECT id, boat_id, title, description, status, priority, due_date, completed_at, created_at, updated_at FROM {$table}";

        $where = array();
        $args = array();

        if ($status !== '' && in_array($status, self::VALID_STATUSES, true)) {
            $where[] = 'status = %s';
            $args[] = $status;
        }

        if ($priority !== '' && in_array($priority, self::VALID_PRIORITIES, true)) {
            $where[] = 'priority = %s';
            $args[] = $priority;
        }

        if (! empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY FIELD(priority,"high","normal","low"), due_date ASC, id ASC LIMIT %d';
        $args[] = max(1, min(1000, $limit));

        $rows = $wpdb->get_results($wpdb->prepare($sql, ...$args), ARRAY_A);

        return is_array($rows) ? $rows : array();
    }

    public function get_open_count(): int
    {
        global $wpdb;

        $table = $wpdb->prefix . 'phoenix_todos';
        return (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$table} WHERE status != 'done'"
        );
    }
}
