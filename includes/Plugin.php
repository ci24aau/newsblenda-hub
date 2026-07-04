<?php

namespace Newsblenda\Editorial;

use Newsblenda\Editorial\Auth\Shortcodes;
use Newsblenda\Editorial\Auth\Registration;
use Newsblenda\Editorial\Auth\PasswordReset;
use Newsblenda\Editorial\Auth\EmailVerification;
use Newsblenda\Editorial\Frontend\AuthorDashboard;
use Newsblenda\Editorial\Frontend\Profile;
use Newsblenda\Editorial\Frontend\ArticleEditor;
use Newsblenda\Editorial\Frontend\EditorDashboard;

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
            Admin\Menu::init();
            add_action( 'admin_init', [ $this, 'restrict_admin_access' ] );
        }

        // Frontend shortcodes and assets
        add_action( 'init', [ Shortcodes::class, 'init' ] );
        add_action( 'wp_enqueue_scripts', [ Shortcodes::class, 'enqueue_assets' ] );

        // Registration shortcode and assets
        add_action( 'init', [ Registration::class, 'init' ] );
        add_action( 'wp_enqueue_scripts', [ Registration::class, 'enqueue_assets' ] );

        // Password reset
        add_action( 'init', [ PasswordReset::class, 'init' ] );
        add_action( 'wp_enqueue_scripts', [ PasswordReset::class, 'enqueue_assets' ] );

        // Email verification requests
        add_action( 'init', [ EmailVerification::class, 'init' ] );

        // Author dashboard
        add_action( 'init', [ AuthorDashboard::class, 'init' ] );
        add_action( 'wp_enqueue_scripts', [ AuthorDashboard::class, 'enqueue_assets' ] );

        // Profile management
        add_action( 'init', [ Profile::class, 'init' ] );
        add_action( 'wp_enqueue_scripts', [ Profile::class, 'enqueue_assets' ] );

        // Article submission
        add_action( 'init', [ ArticleEditor::class, 'init' ] );
        add_action( 'wp_enqueue_scripts', [ ArticleEditor::class, 'enqueue_assets' ] );

        // Editor dashboard
        add_action( 'init', [ EditorDashboard::class, 'init' ] );
        add_action( 'wp_enqueue_scripts', [ EditorDashboard::class, 'enqueue_assets' ] );

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

        if ( ! is_user_logged_in() ) {
            // let WP handle login redirect
            return;
        }

        if ( current_user_can( 'manage_options' ) ) {
            // administrators may access wp-admin
            return;
        }

        // For other roles, determine user status and map to frontend pages
        $user = wp_get_current_user();
        $status = get_user_meta( $user->ID, 'nbe_status', true );
        if ( empty( $status ) ) {
            $status = 'active';
        }

        switch ( $status ) {
            case 'pending':
                wp_safe_redirect( site_url( '/pending' ) );
                break;
            case 'restricted':
                wp_safe_redirect( site_url( '/restricted' ) );
                break;
            case 'blocked':
                wp_safe_redirect( site_url( '/suspended' ) );
                break;
            default:
                // active or unknown — send to their dashboard based on role
                if ( in_array( 'nbe_editor', (array) $user->roles, true ) || in_array( 'editor', (array) $user->roles, true ) ) {
                    wp_safe_redirect( site_url( '/editor-dashboard' ) );
                } else {
                    // authors and others
                    wp_safe_redirect( site_url( '/author-dashboard' ) );
                }
                break;
        }

        exit;
    }
}
