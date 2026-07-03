<?php

if (! defined('ABSPATH')) {
    exit;
}

class BSO_Phoenix_Plugin
{
    public function init(): void
    {
        if (is_admin()) {
            $admin = new BSO_Phoenix_Admin_Page();
            $admin->init();
        }

        $frontend = new BSO_Phoenix_Frontend();
        $frontend->init();
    }
}
