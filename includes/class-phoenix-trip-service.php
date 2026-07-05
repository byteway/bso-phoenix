<?php

if (! defined('ABSPATH')) {
    exit;
}

class BSO_Phoenix_Trip_Service
{
    public function get_dashboard_summary(): array
    {
        global $wpdb;

        $table = $wpdb->prefix . 'phoenix_trips';
        $row = $wpdb->get_row(
            "SELECT
                COUNT(*) AS total_trips,
                COALESCE(SUM(distance_km), 0) AS total_distance_km,
                COALESCE(SUM(duration_minutes), 0) AS total_duration_minutes,
                COALESCE(AVG(average_speed_kmh), 0) AS average_speed_kmh,
                COALESCE(SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END), 0) AS active_trips
            FROM {$table}",
            ARRAY_A
        );

        if (! is_array($row)) {
            return array(
                'total_trips' => 0,
                'total_distance_km' => 0.0,
                'total_duration_minutes' => 0.0,
                'average_speed_kmh' => 0.0,
                'active_trips' => 0,
            );
        }

        return array(
            'total_trips' => (int) $row['total_trips'],
            'total_distance_km' => (float) $row['total_distance_km'],
            'total_duration_minutes' => (float) $row['total_duration_minutes'],
            'average_speed_kmh' => (float) $row['average_speed_kmh'],
            'active_trips' => (int) $row['active_trips'],
        );
    }

    public function get_recent_trips(int $limit = 10): array
    {
        $rows = $this->get_trips_by_date_range(null, null, '', $limit);

        return is_array($rows) ? $rows : array();
    }

    public function get_active_trip(): ?array
    {
        global $wpdb;

        $table = $wpdb->prefix . 'phoenix_trips';
        $row = $wpdb->get_row(
            "SELECT id, started_at, ended_at, duration_minutes, distance_km, average_speed_kmh, status
            FROM {$table}
            WHERE status = 'active'
            ORDER BY id DESC
            LIMIT 1",
            ARRAY_A
        );

        return is_array($row) ? $row : null;
    }

    public function get_latest_completed_trip(): ?array
    {
        global $wpdb;

        $table = $wpdb->prefix . 'phoenix_trips';
        $row = $wpdb->get_row(
            "SELECT id, started_at, ended_at, duration_minutes, distance_km, average_speed_kmh, estimated_fuel_used_l, status
            FROM {$table}
            WHERE status = 'completed'
            ORDER BY ended_at DESC, id DESC
            LIMIT 1",
            ARRAY_A
        );

        return is_array($row) ? $row : null;
    }

    public function get_trips_by_date_range(?string $date_from, ?string $date_to, string $status = '', int $limit = 1000): array
    {
        global $wpdb;

        $table = $wpdb->prefix . 'phoenix_trips';
        $limit = max(1, min(1000, $limit));

        $sql = "SELECT id, started_at, ended_at, duration_minutes, distance_km, average_speed_kmh, estimated_fuel_used_l, status
                FROM {$table}";

        $where = array();
        $args = array();

        if ($date_from !== null && $date_from !== '') {
            $where[] = 'DATE(started_at) >= %s';
            $args[] = $date_from;
        }

        if ($date_to !== null && $date_to !== '') {
            $where[] = 'DATE(started_at) <= %s';
            $args[] = $date_to;
        }

        if ($status !== '') {
            $where[] = 'status = %s';
            $args[] = $status;
        }

        if (! empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY id DESC LIMIT %d';
        $args[] = $limit;

        $prepared = $wpdb->prepare($sql, ...$args);
        $rows = $wpdb->get_results($prepared, ARRAY_A);

        return is_array($rows) ? $rows : array();
    }

    public function get_trip_by_id(int $trip_id): ?array
    {
        global $wpdb;

        $table = $wpdb->prefix . 'phoenix_trips';
        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id, started_at, ended_at, duration_minutes, distance_km, average_speed_kmh, estimated_fuel_used_l, status
                FROM {$table}
                WHERE id = %d",
                $trip_id
            ),
            ARRAY_A
        );

        return is_array($row) ? $row : null;
    }

    public function get_trackpoints_for_trip(int $trip_id): array
    {
        global $wpdb;

        $table = $wpdb->prefix . 'phoenix_trackpoints';
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, trip_id, latitude, longitude, altitude_m, speed_kmh, accuracy_m, recorded_at
                FROM {$table}
                WHERE trip_id = %d
                ORDER BY recorded_at ASC, id ASC",
                $trip_id
            ),
            ARRAY_A
        );

        return is_array($rows) ? $rows : array();
    }

    public function get_trackpoints_for_trip_filtered(int $trip_id, ?string $recorded_from, ?string $recorded_to, int $limit = 500, int $offset = 0): array
    {
        global $wpdb;

        $table = $wpdb->prefix . 'phoenix_trackpoints';
        $limit = max(1, min(5000, $limit));
        $offset = max(0, $offset);

        $sql = "SELECT id, trip_id, latitude, longitude, altitude_m, speed_kmh, accuracy_m, recorded_at
                FROM {$table}
                WHERE trip_id = %d";
        $args = array($trip_id);

        if ($recorded_from !== null && $recorded_from !== '') {
            $sql .= ' AND recorded_at >= %s';
            $args[] = $recorded_from;
        }

        if ($recorded_to !== null && $recorded_to !== '') {
            $sql .= ' AND recorded_at <= %s';
            $args[] = $recorded_to;
        }

        $sql .= ' ORDER BY recorded_at ASC, id ASC LIMIT %d OFFSET %d';
        $args[] = $limit;
        $args[] = $offset;

        $prepared = $wpdb->prepare($sql, ...$args);
        $rows = $wpdb->get_results($prepared, ARRAY_A);

        return is_array($rows) ? $rows : array();
    }

    public function count_trackpoints_for_trip_filtered(int $trip_id, ?string $recorded_from, ?string $recorded_to): int
    {
        global $wpdb;

        $table = $wpdb->prefix . 'phoenix_trackpoints';

        $sql = "SELECT COUNT(*)
                FROM {$table}
                WHERE trip_id = %d";
        $args = array($trip_id);

        if ($recorded_from !== null && $recorded_from !== '') {
            $sql .= ' AND recorded_at >= %s';
            $args[] = $recorded_from;
        }

        if ($recorded_to !== null && $recorded_to !== '') {
            $sql .= ' AND recorded_at <= %s';
            $args[] = $recorded_to;
        }

        $prepared = $wpdb->prepare($sql, ...$args);
        $count = $wpdb->get_var($prepared);

        return max(0, (int) $count);
    }

    public function get_trackpoint_by_id(int $trackpoint_id): ?array
    {
        global $wpdb;

        $table = $wpdb->prefix . 'phoenix_trackpoints';
        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id, trip_id, latitude, longitude, altitude_m, speed_kmh, accuracy_m, recorded_at
                FROM {$table}
                WHERE id = %d",
                $trackpoint_id
            ),
            ARRAY_A
        );

        return is_array($row) ? $row : null;
    }

    public function update_trackpoint(int $trackpoint_id, array $data): bool
    {
        global $wpdb;

        $table = $wpdb->prefix . 'phoenix_trackpoints';
        $updated = $wpdb->update(
            $table,
            array(
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'altitude_m' => $data['altitude_m'],
                'speed_kmh' => $data['speed_kmh'],
                'accuracy_m' => $data['accuracy_m'],
                'recorded_at' => $data['recorded_at'],
            ),
            array('id' => $trackpoint_id),
            array('%f', '%f', '%f', '%f', '%f', '%s'),
            array('%d')
        );

        return $updated !== false;
    }

    public function delete_trackpoint(int $trackpoint_id): bool
    {
        global $wpdb;

        $table = $wpdb->prefix . 'phoenix_trackpoints';
        $deleted = $wpdb->delete($table, array('id' => $trackpoint_id), array('%d'));

        return $deleted !== false && $deleted > 0;
    }

    public function delete_invalid_trackpoints_for_trip(int $trip_id): int
    {
        $deleted_count = 0;
        foreach ($this->get_trackpoints_for_trip($trip_id) as $point) {
            $latitude = isset($point['latitude']) ? (float) $point['latitude'] : 0.0;
            $longitude = isset($point['longitude']) ? (float) $point['longitude'] : 0.0;

            if (! BSO_Phoenix_Hardening::is_valid_coordinate($latitude, $longitude)) {
                if ($this->delete_trackpoint((int) $point['id'])) {
                    $deleted_count++;
                }
            }
        }

        return $deleted_count;
    }

    public function recalculate_trip_metrics(int $trip_id): bool
    {
        global $wpdb;

        $table = $wpdb->prefix . 'phoenix_trips';
        $trip = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id, started_at, ended_at, average_fuel_use_lph, status
                FROM {$table}
                WHERE id = %d",
                $trip_id
            ),
            ARRAY_A
        );

        if (! is_array($trip) || empty($trip['ended_at']) || ($trip['status'] ?? '') !== 'completed') {
            return false;
        }

        $metrics = $this->calculate_trip_metrics($trip_id, (string) $trip['started_at'], (string) $trip['ended_at']);
        $duration_hours = $metrics['duration_minutes'] / 60;

        $fuel_per_hour = isset($trip['average_fuel_use_lph']) ? (float) $trip['average_fuel_use_lph'] : 0.0;
        if ($fuel_per_hour <= 0) {
            $fuel_per_hour = $this->get_default_fuel_use_lph();
        }

        $estimated_fuel = $fuel_per_hour > 0 ? $duration_hours * $fuel_per_hour : null;

        $updated = $wpdb->update(
            $table,
            array(
                'duration_minutes' => $metrics['duration_minutes'],
                'distance_km' => $metrics['distance_km'],
                'average_speed_kmh' => $metrics['average_speed_kmh'],
                'estimated_fuel_used_l' => $estimated_fuel,
            ),
            array('id' => $trip_id),
            array('%f', '%f', '%f', '%f'),
            array('%d')
        );

        return $updated !== false;
    }

    public function start_trip(int $boat_id): int
    {
        global $wpdb;

        $active_trip = $this->get_active_trip();
        if (is_array($active_trip) && ! empty($active_trip['id'])) {
            return (int) $active_trip['id'];
        }

        $table = $wpdb->prefix . 'phoenix_trips';
        $now = current_time('mysql');
        $default_fuel_use_lph = $this->get_default_fuel_use_lph();

        $wpdb->insert(
            $table,
            array(
                'boat_id' => $boat_id,
                'started_at' => $now,
                'average_fuel_use_lph' => $default_fuel_use_lph,
                'status' => 'active',
                'created_at' => $now,
            ),
            array('%d', '%s', '%f', '%s', '%s')
        );

        return (int) $wpdb->insert_id;
    }

    public function add_trackpoint(int $trip_id, float $latitude, float $longitude, ?float $altitude, ?float $speed, ?float $accuracy, ?string $recorded_at = null): bool
    {
        global $wpdb;

		$trip = $this->get_trip_by_id($trip_id);
		if (! is_array($trip) || ($trip['status'] ?? '') !== 'active') {
			return false;
		}

        $table = $wpdb->prefix . 'phoenix_trackpoints';

        $inserted = $wpdb->insert(
            $table,
            array(
                'trip_id' => $trip_id,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'altitude_m' => $altitude,
                'speed_kmh' => $speed,
                'accuracy_m' => $accuracy,
                'recorded_at' => $recorded_at ?: current_time('mysql'),
            ),
            array('%d', '%f', '%f', '%f', '%f', '%f', '%s')
        );

        return $inserted !== false;
    }

    public function stop_trip(int $trip_id): bool
    {
        global $wpdb;

        $table = $wpdb->prefix . 'phoenix_trips';
        $ended_at = current_time('mysql');

        $trip = $wpdb->get_row(
            $wpdb->prepare("SELECT id, started_at, average_fuel_use_lph FROM {$table} WHERE id = %d", $trip_id),
            ARRAY_A
        );

        if (! is_array($trip)) {
            return false;
        }

        $metrics = $this->calculate_trip_metrics($trip_id, (string) $trip['started_at'], $ended_at);

        $duration_hours = $metrics['duration_minutes'] / 60;
        $fuel_per_hour = isset($trip['average_fuel_use_lph']) ? (float) $trip['average_fuel_use_lph'] : 0.0;
        if ($fuel_per_hour <= 0) {
            $fuel_per_hour = $this->get_default_fuel_use_lph();
        }
        $estimated_fuel = $fuel_per_hour > 0 ? $duration_hours * $fuel_per_hour : null;

        $updated = $wpdb->update(
            $table,
            array(
                'ended_at' => $ended_at,
                'duration_minutes' => $metrics['duration_minutes'],
                'distance_km' => $metrics['distance_km'],
                'average_speed_kmh' => $metrics['average_speed_kmh'],
                'estimated_fuel_used_l' => $estimated_fuel,
                'status' => 'completed',
            ),
            array('id' => $trip_id),
            array('%s', '%f', '%f', '%f', '%f', '%s'),
            array('%d')
        );

        return $updated !== false;
    }

    private function calculate_trip_metrics(int $trip_id, string $started_at, string $ended_at): array
    {
        global $wpdb;

        $points_table = $wpdb->prefix . 'phoenix_trackpoints';
        $points = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT latitude, longitude FROM {$points_table} WHERE trip_id = %d ORDER BY recorded_at ASC, id ASC",
                $trip_id
            ),
            ARRAY_A
        );

        $distance_km = 0.0;
        if (is_array($points) && count($points) > 1) {
            for ($i = 1; $i < count($points); $i++) {
                $from = $points[$i - 1];
                $to = $points[$i];
                $distance_km += $this->haversine_km((float) $from['latitude'], (float) $from['longitude'], (float) $to['latitude'], (float) $to['longitude']);
            }
        }

        $duration_seconds = max(0, strtotime($ended_at) - strtotime($started_at));
        $duration_minutes = $duration_seconds / 60;
        $duration_hours = $duration_seconds / 3600;
        $average_speed_kmh = $duration_hours > 0 ? $distance_km / $duration_hours : 0.0;

        return array(
            'duration_minutes' => $duration_minutes,
            'distance_km' => $distance_km,
            'average_speed_kmh' => $average_speed_kmh,
        );
    }

    private function haversine_km(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earth_radius_km = 6371.0;

        $d_lat = deg2rad($lat2 - $lat1);
        $d_lon = deg2rad($lon2 - $lon1);

        $a = sin($d_lat / 2) * sin($d_lat / 2)
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
            * sin($d_lon / 2) * sin($d_lon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earth_radius_km * $c;
    }

    private function get_default_fuel_use_lph(): float
    {
        $settings_service = new BSO_Phoenix_Settings_Service();
        $fuel_use = (float) $settings_service->get('fuel_use_lph');

        return $fuel_use > 0 ? $fuel_use : 0.0;
    }
}
