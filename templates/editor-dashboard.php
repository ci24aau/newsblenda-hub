<div class="nbe-editor-dashboard">
    <h2><?php esc_html_e( 'Editorial Queue', 'newsblenda-editorial' ); ?></h2>

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

    <?php if ( empty( $pending ) ) : ?>
        <p><?php esc_html_e( 'No pending submissions at the moment.', 'newsblenda-editorial' ); ?></p>
    <?php else : ?>
        <ul class="nbe-submissions">
            <?php foreach ( $pending as $p ) : ?>
                <li class="submission">
                    <h3><?php echo esc_html( wp_trim_words( $p->title, 12, '...' ) ); ?></h3>
                    <div class="meta">
                        <span><?php printf( esc_html__( 'Author ID: %d', 'newsblenda-editorial' ), intval( $p->writer_id ) ); ?></span>
                        <span><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $p->created_at ) ) ); ?></span>
                    </div>

                    <div class="content-preview">
                        <?php echo wp_kses_post( wp_trim_words( $p->content, 40, '...' ) ); ?>
                    </div>

                    <form method="post" class="nbe-review-form">
                        <?php wp_nonce_field( 'nbe_review', 'nbe_review_nonce' ); ?>
                        <input type="hidden" name="article_id" value="<?php echo esc_attr( $p->id ); ?>" />

                        <p>
                            <label for="review_comment_<?php echo esc_attr( $p->id ); ?>"><?php esc_html_e( 'Comment (optional)', 'newsblenda-editorial' ); ?></label>
                            <textarea id="review_comment_<?php echo esc_attr( $p->id ); ?>" name="review_comment" rows="3"></textarea>
                        </p>

                        <p class="actions">
                            <button type="submit" name="nbe_review_action" value="approve" class="button button-primary"><?php esc_html_e( 'Approve', 'newsblenda-editorial' ); ?></button>
                            <button type="submit" name="nbe_review_action" value="revision" class="button"><?php esc_html_e( 'Request Revision', 'newsblenda-editorial' ); ?></button>
                            <button type="submit" name="nbe_review_action" value="reject" class="button button-secondary"><?php esc_html_e( 'Reject', 'newsblenda-editorial' ); ?></button>
                        </p>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
