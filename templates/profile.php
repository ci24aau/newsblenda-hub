<div class="nbe-profile-wrap">
    <?php if ( ! empty( $errors ) ) : ?>
        <div class="nbe-profile-errors">
            <?php foreach ( $errors as $e ) : ?>
                <div class="nbe-profile-error"><?php echo esc_html( $e ); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ( ! empty( $messages ) ) : ?>
        <div class="nbe-profile-messages">
            <?php foreach ( $messages as $m ) : ?>
                <div class="nbe-profile-message"><?php echo esc_html( $m ); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="post" action="<?php echo esc_url( get_permalink() ); ?>" class="nbe-profile-form" enctype="multipart/form-data">
        <?php wp_nonce_field( 'nbe_profile', 'nbe_profile_nonce' ); ?>
        <input type="hidden" name="nbe_profile_action" value="1" />

        <p>
            <label for="first_name"><?php esc_html_e( 'First Name', 'newsblenda-editorial' ); ?></label>
            <input name="first_name" id="first_name" type="text" value="<?php echo esc_attr( $first_name ); ?>" />
        </p>

        <p>
            <label for="last_name"><?php esc_html_e( 'Last Name', 'newsblenda-editorial' ); ?></label>
            <input name="last_name" id="last_name" type="text" value="<?php echo esc_attr( $last_name ); ?>" />
        </p>

        <p>
            <label for="bio"><?php esc_html_e( 'Biography', 'newsblenda-editorial' ); ?></label>
            <textarea name="bio" id="bio"><?php echo esc_textarea( $bio ); ?></textarea>
        </p>

        <p>
            <label for="phone"><?php esc_html_e( 'Phone', 'newsblenda-editorial' ); ?></label>
            <input name="phone" id="phone" type="text" value="<?php echo esc_attr( $phone ); ?>" />
        </p>

        <p>
            <label for="preferred_category"><?php esc_html_e( 'Preferred Category', 'newsblenda-editorial' ); ?></label>
            <input name="preferred_category" id="preferred_category" type="text" value="<?php echo esc_attr( $preferred_category ); ?>" />
        </p>

        <p>
            <label for="profile_photo"><?php esc_html_e( 'Profile Photo', 'newsblenda-editorial' ); ?></label>
            <input name="profile_photo" id="profile_photo" type="file" accept="image/*" />
            <?php if ( ! empty( $profile_photo_url ) ) : ?>
                <div class="nbe-photo-preview"><img src="<?php echo esc_url( $profile_photo_url ); ?>" alt="<?php esc_attr_e( 'Profile photo', 'newsblenda-editorial' ); ?>" style="max-width:120px;display:block;margin-top:8px;"/></div>
            <?php endif; ?>
        </p>

        <fieldset>
            <legend><?php esc_html_e( 'Change password', 'newsblenda-editorial' ); ?></legend>
            <p>
                <label for="password"><?php esc_html_e( 'New Password', 'newsblenda-editorial' ); ?></label>
                <input name="password" id="password" type="password" value="" />
            </p>
            <p>
                <label for="password_confirm"><?php esc_html_e( 'Confirm New Password', 'newsblenda-editorial' ); ?></label>
                <input name="password_confirm" id="password_confirm" type="password" value="" />
            </p>
        </fieldset>

        <p>
            <button type="submit" class="button button-primary"><?php esc_html_e( 'Save profile', 'newsblenda-editorial' ); ?></button>
        </p>
    </form>
</div>
