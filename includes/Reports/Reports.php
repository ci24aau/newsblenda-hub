<?php
namespace Newsblenda\Editorial\Reports;

class Reports {
    protected $wpdb;
    protected $articles_table;
    protected $use_articles_table = false;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->articles_table = $wpdb->prefix . 'nbe_articles';
        // detect if plugin articles table exists
        $this->use_articles_table = (bool) $this->wpdb->get_var( $this->wpdb->prepare( "SHOW TABLES LIKE %s", $this->articles_table ) );
    }

    public function author_statistics( $start, $end, $author = 0 ) {
        // Returns array of objects per author: display_name, total, pending, approved, rejected, approval_rate, rejection_rate, views, earnings
        $authors = $this->get_authors( $author );
        $out = [];
        foreach ( $authors as $a ) {
            $stats = $this->submission_counts( $a->ID, $start, $end );
            $views = $this->views_for_author( $a->ID, $start, $end );
            $earnings = $this->earnings_for_author( $a->ID, $start, $end );
            $approval_rate = 0;
            $rejection_rate = 0;
            $total = intval( $stats['total'] );
            if ( $total > 0 ) {
                $approval_rate = ( intval( $stats['approved'] ) / $total ) * 100;
                $rejection_rate = ( intval( $stats['rejected'] ) / $total ) * 100;
            }
            $row = (object) [
                'ID' => $a->ID,
                'display_name' => $a->display_name,
                'total' => $total,
                'pending' => intval( $stats['pending'] ),
                'approved' => intval( $stats['approved'] ),
                'rejected' => intval( $stats['rejected'] ),
                'approval_rate' => $approval_rate,
                'rejection_rate' => $rejection_rate,
                'views' => intval( $views ),
                'earnings' => floatval( $earnings ),
            ];
            $out[] = $row;
        }
        return $out;
    }

    public function earnings_report( $start, $end, $author = 0 ) {
        // Aggregate earnings table between dates
        $earnings_table = $this->wpdb->prefix . 'nbe_earnings';
        $where = ' WHERE earning_date BETWEEN %s AND %s ';
        $params = [ $start, $end ];
        if ( $author ) {
            $where .= ' AND author_id = %d ';
            $params[] = $author;
        }
        $sql = "SELECT author_id, SUM(views) as views, SUM(amount) as amount FROM {$earnings_table} " . $where . " GROUP BY author_id ORDER BY amount DESC";
        $prepared = call_user_func_array( [ $this->wpdb, 'prepare' ], array_merge( [ $sql ], $params ) );
        $rows = $this->wpdb->get_results( $prepared );
        $out = [];
        foreach ( $rows as $r ) {
            $user = get_user_by( 'id', $r->author_id );
            $out[] = (object) [ 'author_id' => $r->author_id, 'display_name' => $user ? $user->display_name : 'User ' . $r->author_id, 'views' => intval( $r->views ), 'amount' => floatval( $r->amount ) ];
        }
        return $out;
    }

    public function submission_report( $start, $end, $author = 0 ) {
        $authors = $this->get_authors( $author );
        $out = [];
        foreach ( $authors as $a ) {
            $stats = $this->submission_counts( $a->ID, $start, $end );
            $out[] = (object) [ 'ID' => $a->ID, 'display_name' => $a->display_name, 'total' => intval( $stats['total'] ), 'pending' => intval( $stats['pending'] ), 'approved' => intval( $stats['approved'] ), 'rejected' => intval( $stats['rejected'] ) ];
        }
        return $out;
    }

    protected function get_authors( $author = 0 ) {
        if ( $author ) {
            $u = get_user_by( 'id', $author );
            return $u ? [ $u ] : [];
        }
        return get_users( [ 'role__in' => [ 'nbe_author', 'author' ], 'number' => 200 ] );
    }

    protected function submission_counts( $author_id, $start, $end ) {
        // If plugin articles table exists, use that; else fall back to wp_posts and post_status mapping
        if ( $this->use_articles_table ) {
            $table = $this->articles_table;
            $sql = $this->wpdb->prepare( "SELECT
                SUM(status = 'pending') as pending,
                SUM(status = 'approved') as approved,
                SUM(status = 'rejected') as rejected,
                COUNT(*) as total
                FROM {$table} WHERE author_id = %d AND created_at BETWEEN %s AND %s", $author_id, $start . ' 00:00:00', $end . ' 23:59:59' );
            $row = $this->wpdb->get_row( $sql );
            return [ 'pending' => intval( $row->pending ), 'approved' => intval( $row->approved ), 'rejected' => intval( $row->rejected ), 'total' => intval( $row->total ) ];
        } else {
            // Use wp_posts: pending -> 'pending', approved -> 'publish', rejected -> 'trash' or 'rejected' custom? We'll map:
            $pending = $this->wpdb->get_var( $this->wpdb->prepare( "SELECT COUNT(*) FROM {$this->wpdb->posts} WHERE post_author = %d AND post_status = 'pending' AND post_date BETWEEN %s AND %s", $author_id, $start . ' 00:00:00', $end . ' 23:59:59' ) );
            $approved = $this->wpdb->get_var( $this->wpdb->prepare( "SELECT COUNT(*) FROM {$this->wpdb->posts} WHERE post_author = %d AND post_status = 'publish' AND post_date BETWEEN %s AND %s", $author_id, $start . ' 00:00:00', $end . ' 23:59:59' ) );
            $rejected = $this->wpdb->get_var( $this->wpdb->prepare( "SELECT COUNT(*) FROM {$this->wpdb->posts} WHERE post_author = %d AND post_status IN ('trash','draft','private') AND post_date BETWEEN %s AND %s", $author_id, $start . ' 00:00:00', $end . ' 23:59:59' ) );
            $total = intval( $pending ) + intval( $approved ) + intval( $rejected );
            return [ 'pending' => intval( $pending ), 'approved' => intval( $approved ), 'rejected' => intval( $rejected ), 'total' => intval( $total ) ];
        }
    }

    protected function views_for_author( $author_id, $start, $end ) {
        $views_table = $this->wpdb->prefix . 'nbe_earnings';
        $sql = $this->wpdb->prepare( "SELECT SUM(views) FROM {$views_table} WHERE author_id = %d AND earning_date BETWEEN %s AND %s", $author_id, $start, $end );
        $v = $this->wpdb->get_var( $sql );
        return intval( $v );
    }

    protected function earnings_for_author( $author_id, $start, $end ) {
        $earnings_table = $this->wpdb->prefix . 'nbe_earnings';
        $sql = $this->wpdb->prepare( "SELECT SUM(amount) FROM {$earnings_table} WHERE author_id = %d AND earning_date BETWEEN %s AND %s", $author_id, $start, $end );
        $v = $this->wpdb->get_var( $sql );
        return floatval( $v );
    }
}
