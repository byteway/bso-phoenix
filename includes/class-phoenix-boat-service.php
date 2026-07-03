<?php

if (! defined('ABSPATH')) {
    exit;
}

class BSO_Phoenix_Boat_Service
{
    public function get_boat(): ?array
    {
        global $wpdb;

        $table = $wpdb->prefix . 'phoenix_boat';
        $row = $wpdb->get_row(
            "SELECT id, name, boat_type, length_m, width_m, draft_m, height_m, fuel_type,
                    top_speed_kmh, weight_kg, bridge_clearance_m, notes, created_at, updated_at
             FROM {$table} LIMIT 1",
            ARRAY_A
        );

        return is_array($row) ? $row : null;
    }

    public function update_boat(array $data): bool
    {
        global $wpdb;

        $table = $wpdb->prefix . 'phoenix_boat';
        $boat = $this->get_boat();

        if (! is_array($boat)) {
            return false;
        }

        $updated = $wpdb->update(
            $table,
            array(
                'name'               => $data['name'],
                'boat_type'          => $data['boat_type'],
                'length_m'           => $data['length_m'],
                'width_m'            => $data['width_m'],
                'draft_m'            => $data['draft_m'],
                'height_m'           => $data['height_m'],
                'fuel_type'          => $data['fuel_type'],
                'top_speed_kmh'      => $data['top_speed_kmh'],
                'weight_kg'          => $data['weight_kg'],
                'bridge_clearance_m' => $data['bridge_clearance_m'],
                'notes'              => $data['notes'],
                'updated_at'         => current_time('mysql'),
            ),
            array('id' => (int) $boat['id']),
            array('%s', '%s', '%f', '%f', '%f', '%f', '%s', '%f', '%f', '%f', '%s', '%s'),
            array('%d')
        );

        return $updated !== false;
    }
}
