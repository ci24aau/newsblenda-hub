<?php

namespace Newsblenda\Editorial\Frontend;

use WP_User;

/**
 * Frontend Editor Dashboard for reviewers.
 */
class EditorDashboard {
    /**
     * Initialize shortcode and assets.
     */
    public static function init() {
        add_shortcode( 'nbe_editor_dashboard', [ __CLASS__, 'render' ] );
    }

    /**
     * Register assets for the editor dashboard.
     */
    public static function enqueue_assets() {
        $dir = plugin_dir_url( dirname( dirname( __DIR__ ) ) );
        wp_register_style( 'nbe-editor-dashboard-style', $dir . 'assets/css/editor-dashboard.css', [], '0.1.0' );
        wp_register_script( 'nbe-editor-dashboard-script', $dir . 'assets/js/editor-dashboard.js', [ 'jquery' ], '0.1.0', true );
    }

    /**
     * Render dashboard and handle review actions.
     *
     * @return string
     */
    public static function render() {
        self::enqueue_assets();

        if ( ! is_user_logged_in() ) {
            wp_safe_redirect( wp_login_url( get_permalink() ) );
            exit;
        }

        $user = wp_get_current_user();
        if ( ! $user instanceof WP_User ) {
            wp_safe_redirect( wp_login_url() );
            exit;
        }

        // Only editors (nbe_editor or editor role) may access
        $roles = (array) $user->roles;
        $is_editor_role = in_array( 'nbe_editor', $roles, true ) || in_array( 'editor', $roles, true );
        if ( ! $is_editor_role ) {
            wp_safe_redirect( site_url() );
            exit;
        }

        // Ensure status is active/approved
        $status = get_user_meta( $user->ID, 'nbe_status', true );
        if ( empty( $status ) ) {
            $status = 'active';
        }
        if ( ! in_array( $status, [ 'active', 'approved' ], true ) ) {
            wp_safe_redirect( site_url() );
            exit;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'nbe_articles';
        $reviews_table = $wpdb->prefix . 'nbe_article_reviews';

        $errors = [];
        $messages = [];

        // Handle POST actions: approve, reject, revision
        if ( 'POST' === strtoupper( $_SERVER['REQUEST_METHOD'] ) && isset( $_POST['nbe_review_action'] ) ) {
            if ( ! isset( $_POST['nbe_review_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nbe_review_nonce'] ) ), 'nbe_review' ) ) {
                $errors[] = __( 'Security check failed. Please try again.', 'newsblenda-editorial' );
            } else {
                $action = sanitize_text_field( wp_unslash( $_POST['nbe_review_action'] ) );
                $article_id = isset( $_POST['article_id'] ) ? intval( wp_unslash( $_POST['article_id'] ) ) : 0;
                $comment = isset( $_POST['review_comment'] ) ? sanitize_textarea_field( wp_unslash( $_POST['review_comment'] ) ) : '';

                if ( $article_id <= 0 ) {
                    $errors[] = __( 'Invalid article specified.', 'newsblenda-editorial' );
                } else {
                    // Fetch article and author
                    $article = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $article_id ) );
                    if ( ! $article ) {
                        $errors[] = __( 'Article not found.', 'newsblenda-editorial' );
                    } else {
                        $new_status = '';
                        if ( 'approve' === $action ) {
                            $new_status = 'approved';
                        } elseif ( 'reject' === $action ) {
                            $new_status = 'rejected';
                        } elseif ( 'revision' === $action ) {
                            $new_status = 'revision';
                        } else {
                            $errors[] = __( 'Unknown action.', 'newsblenda-editorial' );
                        }

                        if ( empty( $errors ) ) {
                            // Update article status
                            $updated = $wpdb->update( $table, [ 'status' => $new_status ], [ 'id' => $article_id ], [ '%s' ], [ '%d' ] );

                            // Record review history
                            $review_data = [
                                'article_id' => $article_id,
                                'reviewer_id' => $user->ID,
                                'action' => $new_status,
                                'comment' => $comment,
                            ];
                            $wpdb->insert( $reviews_table, $review_data, [ '%d', '%d', '%s', '%s' ] );

                            // Notify author via email
                            $author_id = intval( $article->writer_id );
                            $author = get_user_by( 'id', $author_id );
                            if ( $author ) {
                                $subject = sprintf( __( '[%s] Your article has been %s', 'newsblenda-editorial' ), wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ), esc_html( $new_status ) );
                                $message = '';
                                $message .= sprintf( __( 'Hi %s,', 'newsblenda-editorial' ), esc_html( $author->display_name ) ) . "\n\n";
                                if ( 'approved' === $new_status ) {
                                    $message .= __( 'Good news — your article has been approved by an editor.', 'newsblenda-editorial' ) . "\n\n";
                                } elseif ( 'rejected' === $new_status ) {
                                    $message .= __( 'We are sorry — your article has been rejected.', 'newsblenda-editorial' ) . "\n\n";
                                } else {
                                    $message .= __( 'An editor has requested revisions for your article.', 'newsblenda-editorial' ) . "\n\n";
                                }
                                if ( ! empty( $comment ) ) {
                                    $message .= __( 'Editor comments:', 'newsblenda-editorial' ) . "\n" . esc_html( $comment ) . "\n\n";
                                }
                                $message .= __( 'You can view the article and respond via your author dashboard.', 'newsblenda-editorial' ) . "\n";

                                $headers = [ 'Content-Type: text/plain; charset=UTF-8' ];
                                wp_mail( $author->user_email, $subject, $message, $headers );
                            }

                            $messages[] = __( 'Review action recorded successfully.', 'newsblenda-editorial' );
                        }
                    }
                }
            }
        }

        // Fetch pending submissions for review
        $pending = $wpdb->get_results( "SELECT * FROM {$table} WHERE status = 'pending' ORDER BY created_at DESC" );

        ob_start();
        include plugin_dir_path( dirname( __DIR__ ) ) . 'templates/editor-dashboard.php';
        return ob_get_clean();
    }
}
