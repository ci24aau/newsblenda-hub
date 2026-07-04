<?php
namespace Newsblenda\Editorial\Earnings;

class EarningsCalculator {
    /**
     * Calculate earnings for a given date (YYYY-MM-DD)
     */
    public static function calculate_daily( string $date ) : array {
        global $wpdb;
        $views_table = $wpdb->prefix . 'nbe_views';
        $earnings_table = $wpdb->prefix . 'nbe_earnings';

        $start = $date . ' 00:00:00';
        $end = $date . ' 23:59:59';

        // Get views grouped by author
        $sql = $wpdb->prepare( "SELECT author_id, COUNT(*) AS views FROM {$views_table} WHERE created_at BETWEEN %s AND %s GROUP BY author_id", $start, $end );
        $results = $wpdb->get_results( $sql );

        $rpm_default = floatval( get_option( 'nbe_rpm', 5 ) );
        $processed = [];

        foreach ( $results as $row ) {
            $author_id = intval( $row->author_id );
            $views = intval( $row->views );
            if ( $views <= 0 ) {
                continue;
            }
            // per-author RPM could be extended; for now use global
            $rpm = $rpm_default;
            $amount = ( $views / 1000 ) * $rpm;
            $amount = round( $amount, 2 );

            // Insert or update earnings table
            $existing = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$earnings_table} WHERE author_id = %d AND earning_date = %s", $author_id, $date ) );
            if ( $existing ) {
                $wpdb->update( $earnings_table, [ 'views' => $views, 'rpm' => $rpm, 'amount' => $amount ], [ 'id' => $existing ], [ '%d', '%f', '%f' ], [ '%d' ] );
            } else {
                $wpdb->insert( $earnings_table, [ 'earning_date' => $date, 'author_id' => $author_id, 'views' => $views, 'rpm' => $rpm, 'amount' => $amount ], [ '%s', '%d', '%d', '%f', '%f' ] );
            }
            $processed[] = [ 'author_id' => $author_id, 'views' => $views, 'rpm' => $rpm, 'amount' => $amount ];
        }

        return $processed;
    }

    public static function get_summary_for_author( int $author_id ) : array {
        global $wpdb;
        $earnings_table = $wpdb->prefix . 'nbe_earnings';
        $rows = $wpdb->get_results( $wpdb->prepare( "SELECT earning_date, views, rpm, amount FROM {$earnings_table} WHERE author_id = %d ORDER BY earning_date DESC LIMIT 30", $author_id ) );
        $total = 0;
        $views = 0;
        foreach ( $rows as $r ) {
            $total += floatval( $r->amount );
            $views += intval( $r->views );
        }
        return [ 'total' => round( $total, 2 ), 'views' => $views, 'recent' => $rows ];
    }
}
