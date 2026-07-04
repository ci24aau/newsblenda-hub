<?php

namespace Newsblenda\Editorial;

use Newsblenda\Editorial\Admin\Menu as AdminMenu;

/**
 * Main plugin bootstrapper.
 */
class Plugin {
    /**
     * Singleton instance.
     *
     * @var Plugin|null
     */
    private static $instance = null;

    /**
     * Main plugin file path.
     *
     * @var string
     */
    private $file;

    /**
     * Get singleton instance.
     *
     * @param string $file Plugin main file.
     * @return Plugin
     */
    public static function instance( $file ) {
        if ( null === self::$instance ) {
            self::$instance = new self( $file );
        }
        return self::$instance;
    }

    /**
     * Constructor.
     *
     * @param string $file
     */
    private function __construct( $file ) {
        $this->file = $file;
    }

    /**
     * Run the plugin: register hooks.
     */
    public function run() {
        register_activation_hook( $this->file, [ '\\Newsblenda\\Editorial\\Activator', 'activate' ] );
        register_deactivation_hook( $this->file, [ '\\Newsblenda\\Editorial\\Deactivator', 'deactivate' ] );

        add_action( 'plugins_loaded', [ $this, 'loaded' ] );
    }

    /**
     * Initialize runtime hooks.
     */
    public function loaded() {
        // Admin-only initialisation
        if ( is_admin() ) {
            AdminMenu::init();
            add_action( 'admin_init', [ $this, 'restrict_admin_access' ] );
        }

        // Frontend hooks can be registered here for Phase 1 as needed.
    }

    /**
     * Prevent non-administrator roles from accessing wp-admin.
     * Authors and editors should use the frontend dashboard.
     */
    public function restrict_admin_access() {
        if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
            return;
        }

        if ( ! current_user_can( 'manage_options' ) && is_admin() ) {
            // Allow access to admin-ajax.php for authorised usage
            wp_safe_redirect( home_url() );
            exit;
        }
    }
}
