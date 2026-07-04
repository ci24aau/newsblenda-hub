<?php

namespace Newsblenda\Editorial\Frontend;

use WP_User;
use Newsblenda\Editorial\DB\Tables;

/**
 * Frontend article submission/editor for authors.
 */
class ArticleEditor {
    /**
     * Initialize shortcode and assets.
     */
    public static function init() {
        add_shortcode( 'nbe_submit_article', [ __CLASS__, 'render' ] );
    }

    /**
     * Register assets.
     */
    public static function enqueue_assets() {
        $dir = plugin_dir_url( dirname( dirname( __DIR__ ) ) );
        wp_register_style( 'nbe-article-style', $dir . 'assets/css/article-editor.css', [], '0.1.0' );
        wp_register_script( 'nbe-article-script', $dir . 'assets/js/article-editor.js', [ 'jquery' ], '0.1.0', true );
    }

    /**
     * Render the article submission form and handle submissions.
     * Only approved authors may submit (nbe_status = 'active' or 'approved').
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

        $roles = (array) $user->roles;
        $is_author_role = in_array( 'nbe_author', $roles, true ) || in_array( 'author', $roles, true );
        if ( ! $is_author_role ) {
            wp_safe_redirect( site_url() );
            exit;
        }

        $status = get_user_meta( $user->ID, 'nbe_status', true );
        if ( empty( $status ) ) {
            $status = 'active';
        }
        if ( ! in_array( $status, [ 'active', 'approved' ], true ) ) {
            // not allowed to submit
            wp_safe_redirect( site_url( '/pending' ) );
            exit;
        }

        $errors = [];
        $messages = [];

        if ( 'POST' === strtoupper( $_SERVER['REQUEST_METHOD'] ) && isset( $_POST['nbe_article_action'] ) ) {
            if ( ! isset( $_POST['nbe_article_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nbe_article_nonce'] ) ), 'nbe_article' ) ) {
                $errors[] = __( 'Security check failed. Please try again.', 'newsblenda-editorial' );
            } else {
                // Gather and sanitize fields
                $title = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
                $seo_title = isset( $_POST['seo_title'] ) ? sanitize_text_field( wp_unslash( $_POST['seo_title'] ) ) : '';
                $meta_description = isset( $_POST['meta_description'] ) ? sanitize_text_field( wp_unslash( $_POST['meta_description'] ) ) : '';
                $category = isset( $_POST['category'] ) ? sanitize_text_field( wp_unslash( $_POST['category'] ) ) : '';
                $tags_raw = isset( $_POST['tags'] ) ? sanitize_text_field( wp_unslash( $_POST['tags'] ) ) : '';
                $tags = $tags_raw;
                $content = isset( $_POST['content'] ) ? wp_kses_post( wp_unslash( $_POST['content'] ) ) : '';
                $sources_raw = isset( $_POST['sources'] ) ? wp_kses_post( wp_unslash( $_POST['sources'] ) ) : '';

                $action = sanitize_text_field( wp_unslash( $_POST['nbe_article_action'] ) ); // 'save' or 'submit'

                // Validate required fields based on action
                // Word count
                $plain = wp_strip_all_tags( $content );
                $word_count = str_word_count( $plain );

                $settings = (array) get_option( 'nbe_settings', [] );
                $min_words = isset( $settings['min_words'] ) ? intval( $settings['min_words'] ) : 300;
                $max_words = isset( $settings['max_words'] ) ? intval( $settings['max_words'] ) : 2000;

                if ( empty( $title ) ) {
                    $errors[] = __( 'Title is required.', 'newsblenda-editorial' );
                }
                if ( 'submit' === $action ) {
                    if ( $word_count < $min_words ) {
                        $errors[] = sprintf( __( 'Article must be at least %d words to submit for review.', 'newsblenda-editorial' ), $min_words );
                    }
                    if ( $word_count > $max_words ) {
                        $errors[] = sprintf( __( 'Article must be no more than %d words.', 'newsblenda-editorial' ), $max_words );
                    }
                    if ( empty( $seo_title ) ) {
                        $errors[] = __( 'SEO title is required for submission.', 'newsblenda-editorial' );
                    }
                    if ( empty( $meta_description ) ) {
                        $errors[] = __( 'Meta description is required for submission.', 'newsblenda-editorial' );
                    }
                    if ( empty( $sources_raw ) ) {
                        $errors[] = __( 'Please provide sources for the article.', 'newsblenda-editorial' );
                    }
                    // Featured image required on submission
                    if ( empty( $_FILES['featured_image'] ) || empty( $_FILES['featured_image']['name'] ) ) {
                        $errors[] = __( 'Featured image is required for submission.', 'newsblenda-editorial' );
                    }
                }

                // If featured image provided (for save or submit), validate
                $featured_image_id = null;
                if ( ! empty( $_FILES['featured_image'] ) && ! empty( $_FILES['featured_image']['name'] ) ) {
                    $file = $_FILES['featured_image'];
                    $allowed_types = [ 'image/jpeg', 'image/png', 'image/gif', 'image/webp' ];
                    if ( ! in_array( $file['type'], $allowed_types, true ) ) {
                        $errors[] = __( 'Featured image must be an image (jpg, png, gif, webp).', 'newsblenda-editorial' );
                    } elseif ( $file['size'] > 5 * 1024 * 1024 ) { // 5MB limit
                        $errors[] = __( 'Featured image must be less than 5MB.', 'newsblenda-editorial' );
                    }
                }

                if ( empty( $errors ) ) {
                    // If there is an uploaded featured image, handle it
                    if ( ! empty( $_FILES['featured_image'] ) && ! empty( $_FILES['featured_image']['name'] ) ) {
                        require_once ABSPATH . 'wp-admin/includes/file.php';
                        require_once ABSPATH . 'wp-admin/includes/image.php';
                        require_once ABSPATH . 'wp-admin/includes/media.php';

                        $uploaded = wp_handle_upload( $_FILES['featured_image'], [ 'test_form' => false ] );
                        if ( isset( $uploaded['error'] ) ) {
                            $errors[] = esc_html( $uploaded['error'] );
                        } else {
                            $filetype = wp_check_filetype( basename( $uploaded['file'] ), null );
                            $attachment = [
                                'post_mime_type' => $filetype['type'],
                                'post_title'     => sanitize_file_name( basename( $uploaded['file'] ) ),
                                'post_content'   => '',
                                'post_status'    => 'inherit',
                            ];
                            $attach_id = wp_insert_attachment( $attachment, $uploaded['file'], 0 );
                            if ( ! is_wp_error( $attach_id ) ) {
                                $meta = wp_generate_attachment_metadata( $attach_id, $uploaded['file'] );
                                wp_update_attachment_metadata( $attach_id, $meta );
                                $featured_image_id = $attach_id;
                            }
                        }
                    }

                    global $wpdb;
                    $table = $wpdb->prefix . 'nbe_articles';

                    $data = [
                        'writer_id' => $user->ID,
                        'title' => $title,
                        'seo_title' => $seo_title,
                        'meta_description' => $meta_description,
                        'category' => $category,
                        'tags' => $tags,
                        'featured_image_id' => $featured_image_id,
                        'sources' => $sources_raw,
                        'content' => $content,
                        'word_count' => $word_count,
                        'status' => ( 'submit' === $action ? 'pending' : 'draft' ),
                    ];

                    $format = [ '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%d', '%s' ];

                    $inserted = $wpdb->insert( $table, $data, $format );
                    if ( false === $inserted ) {
                        $errors[] = __( 'Failed to save the article. Please try again.', 'newsblenda-editorial' );
                    } else {
                        if ( 'submit' === $action ) {
                            $messages[] = __( 'Article submitted for review. An editor will review it shortly.', 'newsblenda-editorial' );
                        } else {
                            $messages[] = __( 'Draft saved successfully.', 'newsblenda-editorial' );
                        }
                        // reset form values after save
                        $title = $seo_title = $meta_description = $category = $tags = $content = $sources_raw = '';
                        $word_count = 0;
                        $featured_image_id = null;
                    }
                }
            }
        }

        // Render template
        ob_start();
        $form_action = esc_url( get_permalink() ?: site_url( '/submit-article' ) );
        include plugin_dir_path( dirname( __DIR__ ) ) . 'templates/article-editor.php';
        return ob_get_clean();
    }
}
