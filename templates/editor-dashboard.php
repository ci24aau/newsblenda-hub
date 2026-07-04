<div class="nbe-editor-dashboard">
    <h2><?php esc_html_e( 'Editorial Queue', 'newsblenda-editorial' ); ?></h2>

    <div class="nbe-editor-controls">
        <form method="get" class="nbe-editor-filter-form">
            <input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search title or content', 'newsblenda-editorial' ); ?>" />
            <select name="status">
                <option value="pending" <?php selected( $filter_status, 'pending' ); ?>><?php esc_html_e( 'Pending', 'newsblenda-editorial' ); ?> (<?php echo esc_html( $pending_count ); ?>)</option>
                <option value="reviewed" <?php selected( $filter_status, 'reviewed' ); ?>><?php esc_html_e( 'Reviewed', 'newsblenda-editorial' ); ?> (<?php echo esc_html( $reviewed_count ); ?>)</option>
                <option value="revision" <?php selected( $filter_status, 'revision' ); ?>><?php esc_html_e( 'Revision Requests', 'newsblenda-editorial' ); ?> (<?php echo esc_html( $revision_count ); ?>)</option>
            </select>
            <button type="submit" class="button"><?php esc_html_e( 'Filter', 'newsblenda-editorial' ); ?></button>
        </form>
    </div>

    <?php if ( ! empty( $errors ) ) : ?>
        <div class="nbe-editor-errors">
            <?php foreach ( $errors as $e ) : ?>
                <div class="nbe-editor-error"><?php echo esc_html( $e ); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ( ! empty( $messages ) ) : ?>
        <div class="nbe-editor-messages">
            <?php foreach ( $messages as $m ) : ?>
                <div class="nbe-editor-message"><?php echo esc_html( $m ); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="nbe-editor-summary">
        <span class="tab pending <?php echo 'pending' === $filter_status ? 'active' : ''; ?>"><?php esc_html_e( 'Pending', 'newsblenda-editorial' ); ?> (<?php echo esc_html( $pending_count ); ?>)</span>
        <span class="tab reviewed <?php echo 'reviewed' === $filter_status ? 'active' : ''; ?>"><?php esc_html_e( 'Reviewed', 'newsblenda-editorial' ); ?> (<?php echo esc_html( $reviewed_count ); ?>)</span>
        <span class="tab revision <?php echo 'revision' === $filter_status ? 'active' : ''; ?>"><?php esc_html_e( 'Revision Requests', 'newsblenda-editorial' ); ?> (<?php echo esc_html( $revision_count ); ?>)</span>
    </div>

    <?php if ( empty( $rows ) ) : ?>
        <p><?php esc_html_e( 'No submissions match your criteria.', 'newsblenda-editorial' ); ?></p>
    <?php else : ?>
        <ul class="nbe-submissions">
            <?php foreach ( $rows as $p ) : ?>
                <li class="submission" data-id="<?php echo esc_attr( $p->id ); ?>">
                    <div class="submission-head">
                        <h3><?php echo esc_html( wp_trim_words( $p->title, 18, '...' ) ); ?></h3>
                        <div class="meta">
                            <span><?php printf( esc_html__( 'Author ID: %d', 'newsblenda-editorial' ), intval( $p->writer_id ) ); ?></span>
                            <span><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $p->created_at ) ) ); ?></span>
                            <span class="status"><?php echo esc_html( ucfirst( $p->status ) ); ?></span>
                        </div>
                    </div>

                    <div class="submission-body">
                        <div class="content-preview"><?php echo wp_kses_post( wp_trim_words( $p->content, 50, '...' ) ); ?></div>
                        <div class="full-content" style="display:none;" id="full-content-<?php echo esc_attr( $p->id ); ?>"><?php echo wp_kses_post( $p->content ); ?></div>
                    </div>

                    <div class="submission-actions">
                        <button type="button" class="button preview-button" data-target="#full-content-<?php echo esc_attr( $p->id ); ?>"><?php esc_html_e( 'Preview', 'newsblenda-editorial' ); ?></button>

                        <form method="post" class="nbe-review-form-inline" style="display:inline-block;">
                            <?php wp_nonce_field( 'nbe_review', 'nbe_review_nonce' ); ?>
                            <input type="hidden" name="article_id" value="<?php echo esc_attr( $p->id ); ?>" />
                            <input type="hidden" name="nbe_review_action" value="approve" />
                            <button type="submit" class="button button-primary" onclick="return confirm('<?php echo esc_js( __( 'Approve this article?', 'newsblenda-editorial' ) ); ?>');"><?php esc_html_e( 'Approve', 'newsblenda-editorial' ); ?></button>
                        </form>

                        <button type="button" class="button request-revision-button" data-id="<?php echo esc_attr( $p->id ); ?>"><?php esc_html_e( 'Request Revision', 'newsblenda-editorial' ); ?></button>

                        <button type="button" class="button button-secondary reject-button" data-id="<?php echo esc_attr( $p->id ); ?>"><?php esc_html_e( 'Reject', 'newsblenda-editorial' ); ?></button>

                    </div>
                </li>
            <?php endforeach; ?>
        </ul>

        <?php if ( $total_pages > 1 ) : ?>
            <nav class="nbe-pagination">
                <?php for ( $i = 1; $i <= $total_pages; $i++ ) : ?>
                    <a class="page-link <?php echo $i === $page ? 'current' : ''; ?>" href="<?php echo esc_url( add_query_arg( [ 's' => $search, 'status' => $filter_status, 'page' => $i ] ) ); ?>"><?php echo esc_html( $i ); ?></a>
                <?php endfor; ?>
            </nav>
        <?php endif; ?>

    <?php endif; ?>

</div>

<!-- Modal for preview and review actions -->
<div id="nbe-modal" class="nbe-modal" style="display:none;">
    <div class="nbe-modal-content">
        <button class="nbe-modal-close">&times;</button>
        <div class="nbe-modal-body"></div>
    </div>
</div>

<!-- Hidden inline revision/reject form template -->
<div id="nbe-review-forms" style="display:none;">
    <form method="post" id="nbe-review-template" class="nbe-review-form">
        <?php wp_nonce_field( 'nbe_review', 'nbe_review_nonce' ); ?>
        <input type="hidden" name="article_id" value="" />
        <p>
            <label for="review_comment"><?php esc_html_e( 'Comment (required)', 'newsblenda-editorial' ); ?></label>
            <textarea name="review_comment" rows="4" required></textarea>
        </p>
        <p class="actions">
            <button type="submit" name="nbe_review_action" value="revision" class="button"><?php esc_html_e( 'Request Revision', 'newsblenda-editorial' ); ?></button>
            <button type="submit" name="nbe_review_action" value="reject" class="button button-secondary"><?php esc_html_e( 'Reject', 'newsblenda-editorial' ); ?></button>
        </p>
    </form>
</div>
