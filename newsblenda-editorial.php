<?php
/**
 * Plugin Name: Newsblenda Editorial
 * Plugin URI:  https://example.com/newsblenda-editorial
 * Description: Editorial system foundation for Newsblenda (Phase 1).
 * Version:     0.1.0
 * Author:      Newsblenda
 * Text Domain: newsblenda-editorial
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Require the PSR-4 autoloader that lives in includes/
require_once __DIR__ . '/includes/Autoloader.php';

// Bootstrap the plugin
\Newsblenda\Editorial\Plugin::instance( __FILE__ )->run();
