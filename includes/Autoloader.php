<?php
/**
 * Simple PSR-4-compatible autoloader for the plugin.
 * Maps the "Newsblenda\\Editorial\\" namespace to the includes/ directory.
 */

namespace Newsblenda\Editorial;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Autoloader {
    /**
     * Register the autoloader.
     */
    public static function register() {
        spl_autoload_register( [ __CLASS__, 'loader' ] );
    }

    /**
     * PSR-4-like loader implementation.
     *
     * @param string $class Fully-qualified class name.
     */
    public static function loader( $class ) {
        $prefix = __NAMESPACE__ . '\\';
        if ( 0 !== strpos( $class, $prefix ) ) {
            return;
        }

        $relative_class = substr( $class, strlen( $prefix ) );
        $file = plugin_dir_path( dirname( __FILE__ ) ) . 'includes/' . str_replace( '\\', '/', $relative_class ) . '.php';

        if ( file_exists( $file ) ) {
            require_once $file;
        }
    }
}

Autoloader::register();
