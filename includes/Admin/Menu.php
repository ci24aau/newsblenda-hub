<?php

namespace Newsblenda\Editorial\Admin;

use Newsblenda\Editorial\Admin\Settings;

/**
 * Admin menu and page registration.
 */
class Menu {
    /**
     * Initialise admin menu.
     */
    public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'register_menu' ] );
        add_action( 'admin_init', [ Settings::class, 'register' ] );
    }

    /**
     * Register the top-level menu and submenus.
     */
    public static function register_menu() {
        $capability = 'manage_options';
        $slug = 'newsblenda-editorial';

        add_menu_page( 'Newsblenda', 'Newsblenda', $capability, $slug, [ Pages\Dashboard::class, 'render' ], 'dashicons-admin-site' );

        $pages = [
            'writers' => Pages\Writers::class,
            'editorial' => Pages\Editorial::class,
            'articles' => Pages\Articles::class,
            'earnings' => Pages\Earnings::class,
            'payments' => Pages\Payments::class,
            'reports' => Pages\Reports::class,
            'settings' => Settings::class,
            'tools' => Pages\Tools::class,
        ];

        foreach ( $pages as $slug_suffix => $class ) {
            $page_title = ucfirst( $slug_suffix );
            add_submenu_page( $slug, $page_title, $page_title, $capability, $slug . '-' . $slug_suffix, is_callable( [ $class, 'render' ] ) ? [ $class, 'render' ] : [ $class, 'page' ] );
        }
    }
}
