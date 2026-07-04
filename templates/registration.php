<div class="nbe-register-wrap">
    <?php if ( ! empty( $errors ) ) : ?>
        <div class="nbe-register-errors">
            <?php foreach ( $errors as $e ) : ?>
                <div class="nbe-register-error"><?php echo esc_html( $e ); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ( ! empty( $messages ) ) : ?>
        <div class="nbe-register-messages">
            <?php foreach ( $messages as $m ) : ?>
                <div class="nbe-register-message"><?php echo esc_html( $m ); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="post" action="<?php echo esc_url( $form_action ); ?>" class="nbe-register-form" enctype="multipart/form-data">
        <?php wp_nonce_field( 'nbe_register', 'nbe_register_nonce' ); ?>
        <input type="hidden" name="nbe_register_action" value="1" />

        <p>
            <label for="first_name"><?php esc_html_e( 'First Name', 'newsblenda-editorial' ); ?></label>
            <input name="first_name" id="first_name" type="text" value="<?php echo isset( $first_name ) ? esc_attr( $first_name ) : ''; ?>" required />
        </p>

        <p>
            <label for="last_name"><?php esc_html_e( 'Last Name', 'newsblenda-editorial' ); ?></label>
            <input name="last_name" id="last_name" type="text" value="<?php echo isset( $last_name ) ? esc_attr( $last_name ) : ''; ?>" required />
        </p>

        <p>
            <label for="username"><?php esc_html_e( 'Username', 'newsblenda-editorial' ); ?></label>
            <input name="username" id="username" type="text" value="<?php echo isset( $user_login ) ? esc_attr( $user_login ) : ''; ?>" required />
        </p>

        <p>
            <label for="email"><?php esc_html_e( 'Email', 'newsblenda-editorial' ); ?></label>
            <input name="email" id="email" type="email" value="<?php echo isset( $user_email ) ? esc_attr( $user_email ) : ''; ?>" required />
        </p>

        <p>
            <label for="password"><?php esc_html_e( 'Password', 'newsblenda-editorial' ); ?></label>
            <input name="password" id="password" type="password" value="" required />
        </p>

        <p>
            <label for="password_confirm"><?php esc_html_e( 'Confirm Password', 'newsblenda-editorial' ); ?></label>
            <input name="password_confirm" id="password_confirm" type="password" value="" required />
        </p>

        <p>
            <label for="phone"><?php esc_html_e( 'Phone', 'newsblenda-editorial' ); ?></label>
            <input name="phone" id="phone" type="text" value="<?php echo isset( $phone ) ? esc_attr( $phone ) : ''; ?>" />
        </p>

        <p>
            <label for="country"><?php esc_html_e( 'Country', 'newsblenda-editorial' ); ?></label>
            <input name="country" id="country" type="text" value="<?php echo isset( $country ) ? esc_attr( $country ) : ''; ?>" />
        </p>

        <p>
            <label for="preferred_category"><?php esc_html_e( 'Preferred Category', 'newsblenda-editorial' ); ?></label>
            <input name="preferred_category" id="preferred_category" type="text" value="<?php echo isset( $preferred_category ) ? esc_attr( $preferred_category ) : ''; ?>" />
        </p>

        <p>
            <label for="bio"><?php esc_html_e( 'Biography', 'newsblenda-editorial' ); ?></label>
            <textarea name="bio" id="bio"><?php echo isset( $bio ) ? esc_textarea( $bio ) : ''; ?></textarea>
        </p>

        <p>
            <label for="profile_photo"><?php esc_html_e( 'Profile Photo', 'newsblenda-editorial' ); ?></label>
            <input name="profile_photo" id="profile_photo" type="file" accept="image/*" />
        </p>

        <p>
            <label>
                <input name="terms" type="checkbox" value="1" required /> <?php esc_html_e( 'I agree to the terms and conditions', 'newsblenda-editorial' ); ?>
            </label>
        </p>

        <p>
            <button type="submit" class="button button-primary"><?php esc_html_e( 'Register', 'newsblenda-editorial' ); ?></button>
        </p>
    </form>
</div>
