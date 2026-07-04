<div class="nbe-article-editor">
    <?php if ( ! empty( $errors ) ) : ?>
        <div class="nbe-article-errors">
            <?php foreach ( $errors as $e ) : ?>
                <div class="nbe-article-error"><?php echo esc_html( $e ); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ( ! empty( $messages ) ) : ?>
        <div class="nbe-article-messages">
            <?php foreach ( $messages as $m ) : ?>
                <div class="nbe-article-message"><?php echo esc_html( $m ); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="post" action="<?php echo esc_url( $form_action ); ?>" class="nbe-article-form" enctype="multipart/form-data">
        <?php wp_nonce_field( 'nbe_article', 'nbe_article_nonce' ); ?>

        <p>
            <label for="title"><?php esc_html_e( 'Title', 'newsblenda-editorial' ); ?></label>
            <input name="title" id="title" type="text" value="<?php echo isset( $title ) ? esc_attr( $title ) : ''; ?>" required />
        </p>

        <p>
            <label for="seo_title"><?php esc_html_e( 'SEO Title', 'newsblenda-editorial' ); ?></label>
            <input name="seo_title" id="seo_title" type="text" value="<?php echo isset( $seo_title ) ? esc_attr( $seo_title ) : ''; ?>" />
        </p>

        <p>
            <label for="meta_description"><?php esc_html_e( 'Meta Description', 'newsblenda-editorial' ); ?></label>
            <textarea name="meta_description" id="meta_description"><?php echo isset( $meta_description ) ? esc_textarea( $meta_description ) : ''; ?></textarea>
        </p>

        <p>
            <label for="category"><?php esc_html_e( 'Category', 'newsblenda-editorial' ); ?></label>
            <input name="category" id="category" type="text" value="<?php echo isset( $category ) ? esc_attr( $category ) : ''; ?>" />
        </p>

        <p>
            <label for="tags"><?php esc_html_e( 'Tags (comma-separated)', 'newsblenda-editorial' ); ?></label>
            <input name="tags" id="tags" type="text" value="<?php echo isset( $tags ) ? esc_attr( $tags ) : ''; ?>" />
        </p>

        <p>
            <label for="featured_image"><?php esc_html_e( 'Featured Image', 'newsblenda-editorial' ); ?></label>
            <input name="featured_image" id="featured_image" type="file" accept="image/*" />
        </p>

        <p>
            <label for="content"><?php esc_html_e( 'Article Content', 'newsblenda-editorial' ); ?></label>
            <textarea name="content" id="content" rows="12"><?php echo isset( $content ) ? esc_textarea( $content ) : ''; ?></textarea>
        </p>

        <p>
            <label for="sources"><?php esc_html_e( 'Sources (one per line)', 'newsblenda-editorial' ); ?></label>
            <textarea name="sources" id="sources" rows="4"><?php echo isset( $sources_raw ) ? esc_textarea( $sources_raw ) : ''; ?></textarea>
        </p>

        <p class="nbe-article-actions">
            <button type="submit" name="nbe_article_action" value="save" class="button"><?php esc_html_e( 'Save Draft', 'newsblenda-editorial' ); ?></button>
            <button type="submit" name="nbe_article_action" value="submit" class="button button-primary"><?php esc_html_e( 'Submit for Review', 'newsblenda-editorial' ); ?></button>
        </p>
    </form>
</div>
