<?php
/**
 * Uninstall handler for Newsblenda Editorial.
 * Must be a procedural file as required by WordPress.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Load plugin includes to access uninstall class
$autoload = __DIR__ . '/includes/Autoloader.php';
if ( file_exists( $autoload ) ) {
    require_once $autoload;
}

if ( class_exists( '\\Newsblenda\\Editorial\\Uninstaller' ) ) {
    \Newsblenda\Editorial\Uninstaller::uninstall();
}
