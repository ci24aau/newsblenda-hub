<div class="nbe-login-wrap">
    <?php if ( ! empty( $errors ) ) : ?>
        <div class="nbe-login-errors">
            <?php foreach ( $errors as $e ) : ?>
                <div class="nbe-login-error"><?php echo esc_html( $e ); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ( ! empty( $messages ) ) : ?>
        <div class="nbe-login-messages">
            <?php foreach ( $messages as $m ) : ?>
                <div class="nbe-login-message"><?php echo esc_html( $m ); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="post" action="<?php echo esc_url( $form_action ); ?>" class="nbe-login-form">
        <?php wp_nonce_field( 'nbe_login', 'nbe_login_nonce' ); ?>
        <input type="hidden" name="nbe_login_action" value="1" />

        <p>
            <label for="nbe_user"><?php esc_html_e( 'Username or Email', 'newsblenda-editorial' ); ?></label>
            <input name="nbe_user" id="nbe_user" type="text" value="<?php echo $login_value; ?>" required />
        </p>

        <p>
            <label for="nbe_pass"><?php esc_html_e( 'Password', 'newsblenda-editorial' ); ?></label>
            <input name="nbe_pass" id="nbe_pass" type="password" value="" required />
            <button type="button" class="nbe-toggle-password" aria-label="Show password"><?php esc_html_e( 'Show', 'newsblenda-editorial' ); ?></button>
        </p>

        <p class="nbe-remember">
            <label>
                <input name="nbe_remember" type="checkbox" value="1" /> <?php esc_html_e( 'Remember Me', 'newsblenda-editorial' ); ?>
            </label>
        </p>

        <p>
            <button type="submit" class="button button-primary"><?php esc_html_e( 'Log In', 'newsblenda-editorial' ); ?></button>
        </p>

        <p class="nbe-forgot">
            <a href="<?php echo esc_url( wp_lostpassword_url() ); ?>"><?php esc_html_e( 'Forgot your password?', 'newsblenda-editorial' ); ?></a>
        </p>
    </form>
</div>
