<?php
namespace Newsblenda\Editorial\Earnings;

class Payouts {
    public static function create_payout( int $author_id, float $amount, string $notes = '' ) {
        global $wpdb;
        $table = $wpdb->prefix . 'nbe_payouts';
        $wpdb->insert( $table, [ 'author_id' => $author_id, 'amount' => $amount, 'status' => 'pending', 'notes' => $notes ], [ '%d', '%f', '%s', '%s' ] );
        return $wpdb->insert_id;
    }

    public static function get_payouts( $limit = 200 ) {
        global $wpdb;
        $table = $wpdb->prefix . 'nbe_payouts';
        return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} ORDER BY created_at DESC LIMIT %d", $limit ) );
    }

    public static function mark_paid( int $payout_id ) {
        global $wpdb;
        $table = $wpdb->prefix . 'nbe_payouts';
        $wpdb->update( $table, [ 'status' => 'paid', 'paid_at' => current_time( 'mysql' ) ], [ 'id' => $payout_id ], [ '%s', '%s' ], [ '%d' ] );
    }
}
