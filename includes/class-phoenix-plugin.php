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

        if (is_admin()) {
            $admin = new BSO_Phoenix_Admin_Page();
            $admin->init();

            $log_admin = new BSO_Phoenix_Log_Admin();
            $log_admin->init();

            $todo_admin = new BSO_Phoenix_Todo_Admin();
            $todo_admin->init();
        }

        $frontend = new BSO_Phoenix_Frontend();
        $frontend->init();
    }
}
