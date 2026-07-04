<?php

if (! defined('ABSPATH')) {
    exit;
}

class BSO_Phoenix_Log_Service
{
    public function find_recent_duplicate_log_id(int $boat_id, ?int $trip_id, string $entry_text, string $log_date, int $window_seconds = 25, int $exclude_log_id = 0): int
    {
        global $wpdb;

        $table = $wpdb->prefix . 'phoenix_captains_logs';
        $threshold = date('Y-m-d H:i:s', current_time('timestamp') - max(5, $window_seconds));

        $sql = "SELECT id FROM {$table} WHERE boat_id = %d AND log_date = %s AND entry_text = %s AND created_at >= %s";
        $args = array($boat_id, $log_date, $entry_text, $threshold);

        if ($trip_id !== null && $trip_id > 0) {
            $sql .= ' AND trip_id = %d';
            $args[] = $trip_id;
        } else {
            $sql .= ' AND (trip_id IS NULL OR trip_id = 0)';
        }

        if ($exclude_log_id > 0) {
            $sql .= ' AND id <> %d';
            $args[] = $exclude_log_id;
        }

        $sql .= ' ORDER BY id ASC LIMIT 1';

        $duplicate_id = (int) $wpdb->get_var($wpdb->prepare($sql, ...$args));

        return $duplicate_id > 0 ? $duplicate_id : 0;
    }

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

    public function attach_photo_to_log(int $log_id, int $attachment_id, string $caption = ''): bool
    {
        global $wpdb;

        $inserted = $wpdb->insert(
            $wpdb->prefix . 'phoenix_log_photos',
            array(
                'log_id' => $log_id,
                'attachment_id' => $attachment_id,
                'caption' => $caption,
                'sort_order' => $this->get_next_photo_sort_order( $log_id ),
                'created_at' => current_time('mysql'),
            ),
            array( '%d', '%d', '%s', '%d', '%s' )
        );

        return $inserted !== false;
    }

    public function get_log_photos(int $log_id): array
    {
        global $wpdb;

		$this->ensure_photo_sort_order_column();

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}phoenix_log_photos WHERE log_id = %d ORDER BY sort_order ASC, id ASC",
                $log_id
            ),
            ARRAY_A
        );

        if (! is_array($rows)) {
            return array();
        }

        return array_map(
            static function (array $row): array {
                $attachment_id = isset($row['attachment_id']) ? (int) $row['attachment_id'] : 0;
                $row['attachment_id'] = $attachment_id;
                $row['sort_order'] = isset($row['sort_order']) ? (int) $row['sort_order'] : 0;
                $row['url'] = $attachment_id > 0 ? wp_get_attachment_url($attachment_id) : '';
                $row['thumbnail_url'] = $attachment_id > 0 ? wp_get_attachment_image_url($attachment_id, 'medium') : '';
                if (! is_string($row['thumbnail_url']) || $row['thumbnail_url'] === '') {
                    $row['thumbnail_url'] = $row['url'];
                }

                return $row;
            },
            $rows
        );
    }

    public function get_photo_by_id(int $photo_id): ?array
    {
        global $wpdb;

        $this->ensure_photo_sort_order_column();

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id, log_id, attachment_id, caption, sort_order, created_at FROM {$wpdb->prefix}phoenix_log_photos WHERE id = %d",
                $photo_id
            ),
            ARRAY_A
        );

        return is_array($row) ? $row : null;
    }

    /**
     * Ensure the photo sort column exists before reading or writing order data.
     */
    private function ensure_photo_sort_order_column() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'phoenix_log_photos';
        $column     = $wpdb->get_var(
            $wpdb->prepare(
                "SHOW COLUMNS FROM {$table_name} LIKE %s",
                'sort_order'
            )
        );

        if ( null === $column ) {
            $wpdb->query( "ALTER TABLE {$table_name} ADD COLUMN sort_order INT NOT NULL DEFAULT 0 AFTER caption" );
        }

		$wpdb->query( "UPDATE {$table_name} SET sort_order = id WHERE sort_order = 0" );
    }

    /**
     * Get the next photo position within a single captain log entry.
     *
     * @param int $log_id Log identifier.
     * @return int
     */
    private function get_next_photo_sort_order( $log_id ) {
        global $wpdb;

        $this->ensure_photo_sort_order_column();

        $table_name = $wpdb->prefix . 'phoenix_log_photos';
        $max_order  = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT MAX(sort_order) FROM {$table_name} WHERE log_id = %d",
                $log_id
            )
        );

        return $max_order + 1;
    }

    /**
     * Normalize photo positions after updates or deletions.
     *
     * @param int $log_id Log identifier.
     * @return void
     */
    private function normalize_photo_sort_order( $log_id ) {
        global $wpdb;

        $this->ensure_photo_sort_order_column();

        $table_name = $wpdb->prefix . 'phoenix_log_photos';
        $photo_ids  = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT id FROM {$table_name} WHERE log_id = %d ORDER BY sort_order ASC, id ASC",
                $log_id
            )
        );

        if ( empty( $photo_ids ) ) {
            return;
        }

        foreach ( $photo_ids as $index => $photo_id ) {
            $wpdb->update(
                $table_name,
                array( 'sort_order' => $index + 1 ),
                array( 'id' => (int) $photo_id ),
                array( '%d' ),
                array( '%d' )
            );
        }
    }

    public function update_photo_caption(int $photo_id, string $caption): bool
    {
        return $this->update_photo_details( $photo_id, $caption, null );
    }

    public function update_photo_details(int $photo_id, string $caption, ?int $sort_order = null): bool
    {
        global $wpdb;

        $this->ensure_photo_sort_order_column();

        $photo = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id, log_id, sort_order FROM {$wpdb->prefix}phoenix_log_photos WHERE id = %d",
                $photo_id
            ),
            ARRAY_A
        );

        if ( ! is_array( $photo ) ) {
            return false;
        }

        $current_order = max( 1, (int) $photo['sort_order'] );
        $max_order     = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}phoenix_log_photos WHERE log_id = %d",
                (int) $photo['log_id']
            )
        );
        $target_order  = null === $sort_order ? $current_order : max( 1, min( $max_order, $sort_order ) );

        if ( $target_order < $current_order ) {
            $wpdb->query(
                $wpdb->prepare(
                    "UPDATE {$wpdb->prefix}phoenix_log_photos
                    SET sort_order = sort_order + 1
                    WHERE log_id = %d AND id != %d AND sort_order >= %d AND sort_order < %d",
                    (int) $photo['log_id'],
                    $photo_id,
                    $target_order,
                    $current_order
                )
            );
        } elseif ( $target_order > $current_order ) {
            $wpdb->query(
                $wpdb->prepare(
                    "UPDATE {$wpdb->prefix}phoenix_log_photos
                    SET sort_order = sort_order - 1
                    WHERE log_id = %d AND id != %d AND sort_order <= %d AND sort_order > %d",
                    (int) $photo['log_id'],
                    $photo_id,
                    $target_order,
                    $current_order
                )
            );
        }

        $updated = $wpdb->update(
            $wpdb->prefix . 'phoenix_log_photos',
            array(
                'caption' => sanitize_text_field( $caption ),
                'sort_order' => $target_order,
            ),
            array( 'id' => $photo_id ),
            array( '%s', '%d' ),
            array( '%d' )
        );

        if ( false === $updated ) {
            return false;
        }

        $this->normalize_photo_sort_order( (int) $photo['log_id'] );

        return true;
    }

    public function delete_photo(int $photo_id): bool
    {
        global $wpdb;

        $this->ensure_photo_sort_order_column();

        $photo = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id, log_id, attachment_id FROM {$wpdb->prefix}phoenix_log_photos WHERE id = %d",
                $photo_id
            ),
            ARRAY_A
        );

        if (is_array($photo) && ! empty($photo['attachment_id'])) {
            wp_delete_attachment((int) $photo['attachment_id'], true);
        }

        $deleted = $wpdb->delete(
            $wpdb->prefix . 'phoenix_log_photos',
            array('id' => $photo_id),
            array('%d')
        );

		if ( $deleted !== false && is_array( $photo ) && ! empty( $photo['log_id'] ) ) {
			$this->normalize_photo_sort_order( (int) $photo['log_id'] );
		}

        return $deleted !== false;
    }

    public function store_uploaded_photos(int $log_id, array $file_input, array $captions = array()): array
    {
        $attachment_ids = array();

        if (empty($file_input['name'])) {
            return $attachment_ids;
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $normalized_files = $this->normalize_uploaded_files($file_input);

        foreach ($normalized_files as $index => $file) {
            if (! isset($file['error']) || (int) $file['error'] !== UPLOAD_ERR_OK) {
                continue;
            }

            $overrides = array(
                'test_form' => false,
            );

            $uploaded = wp_handle_upload($file, $overrides);
            if (! is_array($uploaded) || isset($uploaded['error'])) {
                continue;
            }

            $attachment = array(
                'post_mime_type' => $uploaded['type'],
                'post_title' => sanitize_file_name(pathinfo($uploaded['file'], PATHINFO_FILENAME)),
                'post_content' => '',
                'post_status' => 'inherit',
            );

            $attachment_id = wp_insert_attachment($attachment, $uploaded['file']);
            if (! $attachment_id || is_wp_error($attachment_id)) {
                continue;
            }

            $metadata = wp_generate_attachment_metadata($attachment_id, $uploaded['file']);
            if (is_array($metadata)) {
                wp_update_attachment_metadata($attachment_id, $metadata);
            }

            $caption = isset($captions[$index]) ? sanitize_text_field((string) $captions[$index]) : '';

            if ($this->attach_photo_to_log($log_id, (int) $attachment_id, $caption)) {
                $attachment_ids[] = (int) $attachment_id;
            }
        }

        return $attachment_ids;
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

    private function normalize_uploaded_files(array $file_input): array
    {
        if (! is_array($file_input['name'])) {
            return array($file_input);
        }

        $normalized = array();
        $count = count($file_input['name']);

        for ($index = 0; $index < $count; $index++) {
            $normalized[] = array(
                'name' => $file_input['name'][$index] ?? '',
                'type' => $file_input['type'][$index] ?? '',
                'tmp_name' => $file_input['tmp_name'][$index] ?? '',
                'error' => $file_input['error'][$index] ?? UPLOAD_ERR_NO_FILE,
                'size' => $file_input['size'][$index] ?? 0,
            );
        }

        return $normalized;
    }
}
