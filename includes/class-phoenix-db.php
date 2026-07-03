<?php

if (! defined('ABSPATH')) {
    exit;
}

class BSO_Phoenix_DB
{
    public static function activate(): void
    {
        self::create_tables();
    }

    public static function create_tables(): void
    {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();
        $prefix = $wpdb->prefix . 'phoenix_';

        $queries = array(
            "CREATE TABLE {$prefix}settings (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                option_key VARCHAR(191) NOT NULL,
                option_value LONGTEXT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY option_key (option_key)
            ) {$charset_collate};",
            "CREATE TABLE {$prefix}boat (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                name VARCHAR(191) NOT NULL,
                boat_type VARCHAR(191) NOT NULL,
                length_m DECIMAL(6,2) NULL,
                width_m DECIMAL(6,2) NULL,
                draft_m DECIMAL(6,2) NULL,
                height_m DECIMAL(6,2) NULL,
                fuel_type VARCHAR(50) NULL,
                top_speed_kmh DECIMAL(6,2) NULL,
                weight_kg DECIMAL(10,2) NULL,
                bridge_clearance_m DECIMAL(6,2) NULL,
                notes TEXT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                PRIMARY KEY (id)
            ) {$charset_collate};",
            "CREATE TABLE {$prefix}trips (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                boat_id BIGINT UNSIGNED NOT NULL,
                started_at DATETIME NOT NULL,
                ended_at DATETIME NULL,
                duration_minutes DECIMAL(10,2) NULL,
                distance_km DECIMAL(10,3) NULL,
                average_speed_kmh DECIMAL(10,3) NULL,
                average_fuel_use_lph DECIMAL(10,3) NULL,
                estimated_fuel_used_l DECIMAL(10,3) NULL,
                status VARCHAR(20) NOT NULL,
                notes TEXT NULL,
                created_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                KEY boat_id (boat_id),
                KEY status (status)
            ) {$charset_collate};",
            "CREATE TABLE {$prefix}trackpoints (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                trip_id BIGINT UNSIGNED NOT NULL,
                latitude DECIMAL(10,7) NOT NULL,
                longitude DECIMAL(10,7) NOT NULL,
                altitude_m DECIMAL(10,2) NULL,
                speed_kmh DECIMAL(10,3) NULL,
                accuracy_m DECIMAL(10,3) NULL,
                recorded_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                KEY trip_recorded_at (trip_id, recorded_at)
            ) {$charset_collate};",
            "CREATE TABLE {$prefix}captains_logs (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                boat_id BIGINT UNSIGNED NOT NULL,
                trip_id BIGINT UNSIGNED NULL,
                log_date DATE NOT NULL,
                log_time TIME NOT NULL,
                entry_text LONGTEXT NOT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                KEY boat_id (boat_id),
                KEY trip_id (trip_id),
                KEY log_date (log_date)
            ) {$charset_collate};",
            "CREATE TABLE {$prefix}log_photos (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                log_id BIGINT UNSIGNED NOT NULL,
                attachment_id BIGINT UNSIGNED NOT NULL,
                caption VARCHAR(191) NULL,
                created_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                KEY log_id (log_id)
            ) {$charset_collate};",
            "CREATE TABLE {$prefix}todos (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                boat_id BIGINT UNSIGNED NOT NULL,
                title VARCHAR(191) NOT NULL,
                description LONGTEXT NULL,
                status VARCHAR(20) NOT NULL,
                priority VARCHAR(20) NOT NULL,
                due_date DATE NULL,
                completed_at DATETIME NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                KEY boat_id (boat_id),
                KEY status (status)
            ) {$charset_collate};",
            "CREATE TABLE {$prefix}costs (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                boat_id BIGINT UNSIGNED NOT NULL,
                trip_id BIGINT UNSIGNED NULL,
                cost_type VARCHAR(30) NOT NULL,
                amount DECIMAL(10,2) NOT NULL,
                currency VARCHAR(10) NOT NULL DEFAULT 'EUR',
                cost_date DATE NOT NULL,
                supplier VARCHAR(191) NULL,
                notes TEXT NULL,
                created_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                KEY boat_id (boat_id),
                KEY trip_id (trip_id),
                KEY cost_type (cost_type)
            ) {$charset_collate};",
            "CREATE TABLE {$prefix}exports (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                trip_id BIGINT UNSIGNED NOT NULL,
                export_type VARCHAR(20) NOT NULL,
                file_name VARCHAR(191) NULL,
                export_payload LONGTEXT NULL,
                created_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                KEY trip_id (trip_id),
                KEY export_type (export_type)
            ) {$charset_collate};",
        );

        foreach ($queries as $sql) {
            dbDelta($sql);
        }

        self::seed_default_boat();
    }

    public static function drop_tables(): void
    {
        global $wpdb;

        $prefix = $wpdb->prefix . 'phoenix_';
        $tables = array(
            'exports',
            'costs',
            'todos',
            'log_photos',
            'captains_logs',
            'trackpoints',
            'trips',
            'boat',
            'settings',
        );

        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS {$prefix}{$table}");
        }
    }

    private static function seed_default_boat(): void
    {
        global $wpdb;

        $boat_table = $wpdb->prefix . 'phoenix_boat';
        $exists = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$boat_table}");
        if ($exists > 0) {
            return;
        }

        $now = current_time('mysql');
        $wpdb->insert(
            $boat_table,
            array(
                'name' => 'Phoenix',
                'boat_type' => 'Zelfgemaakt motorjacht',
                'length_m' => 7.00,
                'width_m' => 3.00,
                'draft_m' => 0.80,
                'height_m' => 2.35,
                'fuel_type' => 'Diesel',
                'top_speed_kmh' => 8.00,
                'weight_kg' => 4000.00,
                'bridge_clearance_m' => 2.40,
                'notes' => 'Standaard bootprofiel aangemaakt bij activatie.',
                'created_at' => $now,
                'updated_at' => $now,
            ),
            array('%s', '%s', '%f', '%f', '%f', '%f', '%s', '%f', '%f', '%f', '%s', '%s', '%s')
        );
    }
}
