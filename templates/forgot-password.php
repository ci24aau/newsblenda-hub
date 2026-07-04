<div class="nbe-forgot-wrap">
    <?php if ( ! empty( $errors ) ) : ?>
        <div class="nbe-forgot-errors">
            <?php foreach ( $errors as $e ) : ?>
                <div class="nbe-forgot-error"><?php echo esc_html( $e ); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ( ! empty( $messages ) ) : ?>
        <div class="nbe-forgot-messages">
            <?php foreach ( $messages as $m ) : ?>
                <div class="nbe-forgot-message"><?php echo esc_html( $m ); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="post" action="<?php echo esc_url( $form_action ); ?>" class="nbe-forgot-form">
        <?php wp_nonce_field( 'nbe_forgot', 'nbe_forgot_nonce' ); ?>
        <input type="hidden" name="nbe_forgot_action" value="1" />

        <p>
            <label for="nbe_forgot_email"><?php esc_html_e( 'Email', 'newsblenda-editorial' ); ?></label>
            <input name="nbe_forgot_email" id="nbe_forgot_email" type="email" value="" required />
        </p>

        <p>
            <button type="submit" class="button button-primary"><?php esc_html_e( 'Send password reset email', 'newsblenda-editorial' ); ?></button>
        </p>
    </form>
</div>
