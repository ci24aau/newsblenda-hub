<?php
namespace Newsblenda\Editorial\Earnings;

use WP_Error;

// Manager sets up hooks, ensures tables and schedules cron.
class Manager {
    public static function init() {
        add_action( 'init', [ __CLASS__, 'setup' ] );
        // shortcode
        add_shortcode( 'nbe_author_earnings', [ __CLASS__, 'author_earnings_shortcode' ] );
    }

    public static function setup() {
        self::maybe_create_tables();
        // Track single post views
        add_action( 'template_redirect', [ __CLASS__, 'maybe_track_view' ] );
        // Cron hook
        add_action( 'nbe_daily_earnings', [ __CLASS__, 'run_daily_earnings' ] );
        // Ensure cron scheduled
        if ( ! wp_next_scheduled( 'nbe_daily_earnings' ) ) {
            // schedule daily at midnight server time
            wp_schedule_event( time(), 'daily', 'nbe_daily_earnings' );
        }
        // Admin menu for payouts and settings
        add_action( 'admin_menu', [ __CLASS__, 'admin_menu' ] );
    }

    public static function admin_menu() {
        add_menu_page( 'Newsblenda Earnings', 'Newsblenda Earnings', 'manage_options', 'nbe-earnings', [ __CLASS__, 'admin_page' ], 'dashicons-chart-line', 26 );
        add_submenu_page( 'nbe-earnings', 'Payouts', 'Payouts', 'manage_options', 'nbe-earnings-payouts', [ __CLASS__, 'payouts_page' ] );
    }

    public static function admin_page() {
        // simple settings page for RPM
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Insufficient permissions' );
        }
        if ( isset( $_POST['nbe_rpm'] ) && check_admin_referer( 'nbe_earnings_settings', 'nbe_earnings_nonce' ) ) {
            $rpm = floatval( sanitize_text_field( wp_unslash( $_POST['nbe_rpm'] ) ) );
            update_option( 'nbe_rpm', $rpm );
            echo '<div class="updated"><p>Settings saved.</p></div>';
        }
        $rpm = floatval( get_option( 'nbe_rpm', 5 ) );
        ?>
        <div class="wrap">
            <h1>Newsblenda Earnings Settings</h1>
            <form method="post">
                <?php wp_nonce_field( 'nbe_earnings_settings', 'nbe_earnings_nonce' ); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="nbe_rpm">RPM (revenue per 1000 views)</label></th>
                        <td><input name="nbe_rpm" type="number" step="0.01" min="0" value="<?php echo esc_attr( $rpm ); ?>" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public static function payouts_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Insufficient permissions' );
        }
        // Handle create payout
        if ( isset( $_POST['create_payout'] ) && check_admin_referer( 'nbe_create_payout', 'nbe_create_payout_nonce' ) ) {
            $author_id = intval( $_POST['author_id'] );
            $amount = floatval( $_POST['amount'] );
            $notes = sanitize_text_field( wp_unslash( $_POST['notes'] ) );
            Payouts::create_payout( $author_id, $amount, $notes );
            echo '<div class="updated"><p>Payout recorded.</p></div>';
        }

        $payouts = Payouts::get_payouts();
        $users = get_users( [ 'role' => 'nbe_author' ] );
        ?>
        <div class="wrap">
            <h1>Author Payouts</h1>
            <h2>New Payout</h2>
            <form method="post">
                <?php wp_nonce_field( 'nbe_create_payout', 'nbe_create_payout_nonce' ); ?>
                <table class="form-table">
                    <tr>
                        <th><label for="author_id">Author</label></th>
                        <td>
                            <select name="author_id">
                                <?php foreach ( $users as $u ) : ?>
                                    <option value="<?php echo esc_attr( $u->ID ); ?>"><?php echo esc_html( $u->display_name ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="amount">Amount</label></th>
                        <td><input name="amount" type="number" step="0.01" /></td>
                    </tr>
                    <tr>
                        <th><label for="notes">Notes</label></th>
                        <td><input name="notes" type="text" /></td>
                    </tr>
                </table>
                <?php submit_button( 'Record Payout', 'primary', 'create_payout' ); ?>
            </form>

            <h2>Past Payouts</h2>
            <table class="widefat fixed">
                <thead><tr><th>ID</th><th>Author</th><th>Amount</th><th>Status</th><th>Notes</th><th>Created</th><th>Paid At</th></tr></thead>
                <tbody>
                <?php foreach ( $payouts as $p ) : ?>
                    <tr>
                        <td><?php echo esc_html( $p->id ); ?></td>
                        <td><?php echo esc_html( get_the_author_meta( 'display_name', $p->author_id ) ); ?></td>
                        <td><?php echo esc_html( number_format_i18n( $p->amount, 2 ) ); ?></td>
                        <td><?php echo esc_html( $p->status ); ?></td>
                        <td><?php echo esc_html( $p->notes ); ?></td>
                        <td><?php echo esc_html( $p->created_at ); ?></td>
                        <td><?php echo esc_html( $p->paid_at ); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public static function maybe_create_tables() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charset_collate = $wpdb->get_charset_collate();
        $views_table = $wpdb->prefix . 'nbe_views';
        $earnings_table = $wpdb->prefix . 'nbe_earnings';
        $payouts_table = $wpdb->prefix . 'nbe_payouts';

        $sql = "CREATE TABLE {$views_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            post_id BIGINT UNSIGNED NOT NULL,
            author_id BIGINT UNSIGNED DEFAULT 0,
            ip VARCHAR(45) DEFAULT NULL,
            user_agent TEXT,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY post_id (post_id),
            KEY author_id (author_id),
            KEY created_at (created_at)
        ) {$charset_collate};";
        dbDelta( $sql );

        $sql = "CREATE TABLE {$earnings_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            earning_date DATE NOT NULL,
            author_id BIGINT UNSIGNED NOT NULL,
            views BIGINT UNSIGNED NOT NULL DEFAULT 0,
            rpm DECIMAL(10,4) NOT NULL DEFAULT 0,
            amount DECIMAL(10,2) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY author_date (author_id, earning_date)
        ) {$charset_collate};";
        dbDelta( $sql );

        $sql = "CREATE TABLE {$payouts_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            author_id BIGINT UNSIGNED NOT NULL,
            amount DECIMAL(12,2) NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            notes TEXT,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            paid_at DATETIME NULL,
            PRIMARY KEY (id),
            KEY author_id (author_id)
        ) {$charset_collate};";
        dbDelta( $sql );
    }

    public static function maybe_track_view() {
        if ( ! is_singular() ) {
            return;
        }
        $post_id = get_queried_object_id();
        if ( ! $post_id ) {
            return;
        }
        // Only track for posts authored by plugin authors (or any post?) We'll track for posts with an author.
        $author_id = (int) get_post_field( 'post_author', $post_id );
        if ( $author_id <= 0 ) {
            return;
        }
        // Avoid tracking for previews or admin
        if ( is_preview() || is_admin() ) {
            return;
        }
        // Record view
        ViewTracker::record_view( $post_id, $author_id );
    }

    public static function run_daily_earnings() {
        // calculate for yesterday
        $yesterday = date( 'Y-m-d', strtotime( 'yesterday' ) );
        EarningsCalculator::calculate_daily( $yesterday );
    }

    public static function author_earnings_shortcode( $atts ) {
        if ( ! is_user_logged_in() ) {
            return '<p>Please log in to view your earnings.</p>';
        }
        $user_id = get_current_user_id();
        $summary = EarningsCalculator::get_summary_for_author( $user_id );
        ob_start();
        ?>
        <div class="nbe-earnings">
            <h2>Your Earnings</h2>
            <p>Total Earnings: <strong><?php echo esc_html( number_format_i18n( $summary['total'] ?? 0, 2 ) ); ?></strong></p>
            <p>Total Views: <strong><?php echo esc_html( number_format_i18n( $summary['views'] ?? 0 ) ); ?></strong></p>
            <h3>Recent Days</h3>
            <table>
                <thead><tr><th>Date</th><th>Views</th><th>RPM</th><th>Amount</th></tr></thead>
                <tbody>
                <?php foreach ( $summary['recent'] as $row ) : ?>
                    <tr>
                        <td><?php echo esc_html( $row->earning_date ); ?></td>
                        <td><?php echo esc_html( number_format_i18n( $row->views ) ); ?></td>
                        <td><?php echo esc_html( number_format_i18n( $row->rpm, 2 ) ); ?></td>
                        <td><?php echo esc_html( number_format_i18n( $row->amount, 2 ) ); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
        return ob_get_clean();
    }
}

// Bootstrap
Manager::init();
