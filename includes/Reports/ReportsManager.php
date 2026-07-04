<?php
namespace Newsblenda\Editorial\Reports;

use Newsblenda\Editorial\Earnings\EarningsCalculator;
use Newsblenda\Editorial\Earnings\Payouts;

class Manager {
    public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'admin_menu' ] );
        add_action( 'admin_post_nbe_export_report', [ __CLASS__, 'handle_export' ] );
    }

    public static function admin_menu() {
        // Add under existing Newsblenda Earnings menu if present
        add_submenu_page( 'nbe-earnings', 'Reports', 'Reports', 'manage_options', 'nbe-earnings-reports', [ __CLASS__, 'render_reports_page' ] );
    }

    public static function render_reports_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Insufficient permissions' );
        }

        $start = isset( $_GET['start'] ) ? sanitize_text_field( wp_unslash( $_GET['start'] ) ) : date( 'Y-m-01' );
        $end = isset( $_GET['end'] ) ? sanitize_text_field( wp_unslash( $_GET['end'] ) ) : date( 'Y-m-d' );
        $author = isset( $_GET['author'] ) ? intval( $_GET['author'] ) : 0;
        $type = isset( $_GET['type'] ) ? sanitize_text_field( wp_unslash( $_GET['type'] ) ) : 'author_stats';

        $authors = get_users( [ 'role__in' => [ 'nbe_author', 'author', 'editor', 'administrator' ] ] );

        // Render simple form
        ?>
        <div class="wrap">
            <h1>Newsblenda Reports</h1>
            <form method="get">
                <input type="hidden" name="page" value="nbe-earnings-reports" />
                <table class="form-table">
                    <tr>
                        <th><label for="start">Start Date</label></th>
                        <td><input type="date" name="start" value="<?php echo esc_attr( $start ); ?>" /></td>
                        <th><label for="end">End Date</label></th>
                        <td><input type="date" name="end" value="<?php echo esc_attr( $end ); ?>" /></td>
                    </tr>
                    <tr>
                        <th><label for="author">Author</label></th>
                        <td>
                            <select name="author">
                                <option value="0">All</option>
                                <?php foreach ( $authors as $a ) : ?>
                                    <option value="<?php echo esc_attr( $a->ID ); ?>" <?php selected( $author, $a->ID ); ?>><?php echo esc_html( $a->display_name ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <th><label for="type">Report Type</label></th>
                        <td>
                            <select name="type">
                                <option value="author_stats" <?php selected( $type, 'author_stats' ); ?>>Author Statistics</option>
                                <option value="earnings" <?php selected( $type, 'earnings' ); ?>>Earnings Report</option>
                                <option value="submissions" <?php selected( $type, 'submissions' ); ?>>Submission Report</option>
                            </select>
                        </td>
                    </tr>
                </table>
                <?php submit_button( 'View Report' ); ?>
                <?php echo '<a class="button" href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=nbe_export_report&start=' . rawurlencode( $start ) . '&end=' . rawurlencode( $end ) . '&author=' . intval( $author ) . '&type=' . rawurlencode( $type ) ), 'nbe_export_report' ) ) . '">Export CSV</a>'; ?>
            </form>

            <hr />
            <?php
            // Show report
            $reports = new Reports();
            if ( $type === 'author_stats' ) {
                $data = $reports->author_statistics( $start, $end, $author );
                // render table
                echo '<h2>Author Statistics</h2>'; 
                echo '<table class="widefat fixed"><thead><tr><th>Author</th><th>Total Articles</th><th>Pending</th><th>Approved</th><th>Rejected</th><th>Approval Rate</th><th>Rejection Rate</th><th>Views</th><th>Earnings</th></tr></thead><tbody>';
                foreach ( $data as $row ) {
                    echo '<tr>';
                    echo '<td>' . esc_html( $row->display_name ) . '</td>';
                    echo '<td>' . esc_html( $row->total ) . '</td>';
                    echo '<td>' . esc_html( $row->pending ) . '</td>';
                    echo '<td>' . esc_html( $row->approved ) . '</td>';
                    echo '<td>' . esc_html( $row->rejected ) . '</td>';
                    echo '<td>' . esc_html( number_format_i18n( $row->approval_rate, 2 ) ) . '%</td>';
                    echo '<td>' . esc_html( number_format_i18n( $row->rejection_rate, 2 ) ) . '%</td>';
                    echo '<td>' . esc_html( number_format_i18n( $row->views ) ) . '</td>';
                    echo '<td>' . esc_html( number_format_i18n( $row->earnings, 2 ) ) . '</td>';
                    echo '</tr>';
                }
                echo '</tbody></table>';
            } elseif ( $type === 'earnings' ) {
                $data = $reports->earnings_report( $start, $end, $author );
                echo '<h2>Earnings Report</h2>';
                echo '<table class="widefat fixed"><thead><tr><th>Author</th><th>Views</th><th>Earnings</th></tr></thead><tbody>';
                foreach ( $data as $row ) {
                    echo '<tr>';
                    echo '<td>' . esc_html( $row->display_name ) . '</td>';
                    echo '<td>' . esc_html( number_format_i18n( $row->views ) ) . '</td>';
                    echo '<td>' . esc_html( number_format_i18n( $row->amount, 2 ) ) . '</td>';
                    echo '</tr>';
                }
                echo '</tbody></table>';
            } else {
                $data = $reports->submission_report( $start, $end, $author );
                echo '<h2>Submission Report</h2>';
                echo '<table class="widefat fixed"><thead><tr><th>Author</th><th>Total</th><th>Pending</th><th>Approved</th><th>Rejected</th></tr></thead><tbody>';
                foreach ( $data as $row ) {
                    echo '<tr>';
                    echo '<td>' . esc_html( $row->display_name ) . '</td>';
                    echo '<td>' . esc_html( $row->total ) . '</td>';
                    echo '<td>' . esc_html( $row->pending ) . '</td>';
                    echo '<td>' . esc_html( $row->approved ) . '</td>';
                    echo '<td>' . esc_html( $row->rejected ) . '</td>';
                    echo '</tr>';
                }
                echo '</tbody></table>';
            }
            ?>
        </div>
        <?php
    }

    public static function handle_export() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Insufficient permissions' );
        }
        check_admin_referer( 'nbe_export_report' );

        $start = isset( $_GET['start'] ) ? sanitize_text_field( wp_unslash( $_GET['start'] ) ) : date( 'Y-m-01' );
        $end = isset( $_GET['end'] ) ? sanitize_text_field( wp_unslash( $_GET['end'] ) ) : date( 'Y-m-d' );
        $author = isset( $_GET['author'] ) ? intval( $_GET['author'] ) : 0;
        $type = isset( $_GET['type'] ) ? sanitize_text_field( wp_unslash( $_GET['type'] ) ) : 'author_stats';

        $reports = new Reports();
        $filename = 'nbe_report_' . $type . '_' . $start . '_' . $end . '.csv';

        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=' . $filename );

        $output = fopen( 'php://output', 'w' );
        if ( $type === 'author_stats' ) {
            fputcsv( $output, [ 'Author', 'Total Articles', 'Pending', 'Approved', 'Rejected', 'Approval Rate', 'Rejection Rate', 'Views', 'Earnings' ] );
            $data = $reports->author_statistics( $start, $end, $author );
            foreach ( $data as $row ) {
                fputcsv( $output, [ $row->display_name, $row->total, $row->pending, $row->approved, $row->rejected, number_format_i18n( $row->approval_rate, 2 ) . '%', number_format_i18n( $row->rejection_rate, 2 ) . '%', $row->views, number_format_i18n( $row->earnings, 2 ) ] );
            }
        } elseif ( $type === 'earnings' ) {
            fputcsv( $output, [ 'Author', 'Views', 'Earnings' ] );
            $data = $reports->earnings_report( $start, $end, $author );
            foreach ( $data as $row ) {
                fputcsv( $output, [ $row->display_name, $row->views, number_format_i18n( $row->amount, 2 ) ] );
            }
        } else {
            fputcsv( $output, [ 'Author', 'Total', 'Pending', 'Approved', 'Rejected' ] );
            $data = $reports->submission_report( $start, $end, $author );
            foreach ( $data as $row ) {
                fputcsv( $output, [ $row->display_name, $row->total, $row->pending, $row->approved, $row->rejected ] );
            }
        }
        fclose( $output );
        exit;
    }
}
