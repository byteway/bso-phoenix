<?php

if (! defined('ABSPATH')) {
    exit;
}

class BSO_Phoenix_Plugin
{
    public function init(): void
    {
        $ajax = new BSO_Phoenix_Ajax();
        $ajax->init();

        $log_ajax = new BSO_Phoenix_Log_Ajax();
        $log_ajax->init();

        $todo_ajax = new BSO_Phoenix_Todo_Ajax();
        $todo_ajax->init();

        $cost_ajax = new BSO_Phoenix_Cost_Ajax();
        $cost_ajax->init();

        if (is_admin()) {
            $admin = new BSO_Phoenix_Admin_Page();
            $admin->init();

            $log_admin = new BSO_Phoenix_Log_Admin();
            $log_admin->init();

            $todo_admin = new BSO_Phoenix_Todo_Admin();
            $todo_admin->init();

            $cost_admin = new BSO_Phoenix_Cost_Admin();
            $cost_admin->init();

            $boat_admin = new BSO_Phoenix_Boat_Admin();
            $boat_admin->init();

            $settings_admin = new BSO_Phoenix_Settings_Admin();
            $settings_admin->init();

            $reports_admin = new BSO_Phoenix_Reports_Admin();
            $reports_admin->init();
        }

        $frontend = new BSO_Phoenix_Frontend();
        $frontend->init();
    }
}
