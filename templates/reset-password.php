<div class="nbe-reset-wrap">
    <?php if ( ! empty( $errors ) ) : ?>
        <div class="nbe-reset-errors">
            <?php foreach ( $errors as $e ) : ?>
                <div class="nbe-reset-error"><?php echo esc_html( $e ); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ( ! empty( $messages ) ) : ?>
        <div class="nbe-reset-messages">
            <?php foreach ( $messages as $m ) : ?>
                <div class="nbe-reset-message"><?php echo esc_html( $m ); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="post" action="<?php echo esc_url( $form_action ); ?>" class="nbe-reset-form">
        <?php wp_nonce_field( 'nbe_reset', 'nbe_reset_nonce' ); ?>
        <input type="hidden" name="nbe_reset_action" value="1" />
        <input type="hidden" name="nbe_reset_uid" value="<?php echo esc_attr( isset( $uid ) ? $uid : 0 ); ?>" />
        <input type="hidden" name="nbe_reset_token" value="<?php echo esc_attr( isset( $token ) ? $token : '' ); ?>" />

        <p>
            <label for="nbe_new_password"><?php esc_html_e( 'New Password', 'newsblenda-editorial' ); ?></label>
            <input name="nbe_new_password" id="nbe_new_password" type="password" value="" required />
        </p>

        <p>
            <label for="nbe_new_password_confirm"><?php esc_html_e( 'Confirm New Password', 'newsblenda-editorial' ); ?></label>
            <input name="nbe_new_password_confirm" id="nbe_new_password_confirm" type="password" value="" required />
        </p>

        <p>
            <button type="submit" class="button button-primary"><?php esc_html_e( 'Reset Password', 'newsblenda-editorial' ); ?></button>
        </p>
    </form>
</div>
