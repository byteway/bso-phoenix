<?php

if (! defined('ABSPATH')) {
    exit;
}

class BSO_Phoenix_Trip_Service
{
    public function start_trip(int $boat_id): int
    {
        global $wpdb;

        $table = $wpdb->prefix . 'phoenix_trips';
        $now = current_time('mysql');

        $wpdb->insert(
            $table,
            array(
                'boat_id' => $boat_id,
                'started_at' => $now,
                'status' => 'active',
                'created_at' => $now,
            ),
            array('%d', '%s', '%s', '%s')
        );

        return (int) $wpdb->insert_id;
    }

    public function stop_trip(int $trip_id): bool
    {
        global $wpdb;

        $table = $wpdb->prefix . 'phoenix_trips';

        $updated = $wpdb->update(
            $table,
            array(
                'ended_at' => current_time('mysql'),
                'status' => 'completed',
            ),
            array('id' => $trip_id),
            array('%s', '%s'),
            array('%d')
        );

        return $updated !== false;
    }
}
