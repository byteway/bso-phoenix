<?php

if (! defined('ABSPATH')) {
    exit;
}

class BSO_Phoenix_Access
{
    public const CAP_READ = 'bso_phoenix_read';
    public const CAP_WRITE = 'bso_phoenix_write';
    public const CAP_MANAGE = 'bso_phoenix_manage';

    public static function init(): void
    {
        add_action('init', array(__CLASS__, 'register_roles_and_caps'));
    }

    public static function activate(): void
    {
        self::register_roles_and_caps();
    }

    public static function register_roles_and_caps(): void
    {
        self::upsert_role(
            'phoenix_owner',
            'Phoenix eigenaar',
            array(
                'read' => true,
                self::CAP_READ => true,
                self::CAP_WRITE => true,
                self::CAP_MANAGE => true,
            )
        );

        self::upsert_role(
            'phoenix_crew',
            'Phoenix bemanning',
            array(
                'read' => true,
                self::CAP_READ => true,
                self::CAP_WRITE => true,
            )
        );

        self::upsert_role(
            'phoenix_reader',
            'Phoenix alleen-lezen',
            array(
                'read' => true,
                self::CAP_READ => true,
            )
        );

        self::grant_caps_to_role(
            'administrator',
            array(self::CAP_READ, self::CAP_WRITE, self::CAP_MANAGE)
        );

        self::grant_caps_to_role(
            'editor',
            array(self::CAP_READ, self::CAP_WRITE)
        );

        self::grant_caps_to_role(
            'author',
            array(self::CAP_READ, self::CAP_WRITE)
        );

        self::grant_caps_to_role(
            'subscriber',
            array(self::CAP_READ)
        );
    }

    private static function upsert_role(string $slug, string $label, array $caps): void
    {
        $role = get_role($slug);

        if (! $role instanceof WP_Role) {
            add_role($slug, $label, $caps);
            return;
        }

        foreach ($caps as $cap => $grant) {
            if ($grant) {
                $role->add_cap($cap);
            }
        }
    }

    private static function grant_caps_to_role(string $role_name, array $caps): void
    {
        $role = get_role($role_name);
        if (! $role instanceof WP_Role) {
            return;
        }

        foreach ($caps as $cap) {
            $role->add_cap($cap);
        }
    }
}