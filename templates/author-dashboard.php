<div class="nbe-author-dashboard">
    <nav class="nbe-dashboard-nav">
        <ul>
            <li><a href="<?php echo esc_url( site_url( '/author-dashboard' ) ); ?>" class="active"><?php esc_html_e( 'Dashboard', 'newsblenda-editorial' ); ?></a></li>
            <li><a href="<?php echo esc_url( site_url( '/author-dashboard/articles' ) ); ?>"><?php esc_html_e( 'My Articles', 'newsblenda-editorial' ); ?></a></li>
            <li><a href="<?php echo esc_url( site_url( '/author-dashboard/earnings' ) ); ?>"><?php esc_html_e( 'Earnings', 'newsblenda-editorial' ); ?></a></li>
            <li><a href="<?php echo esc_url( site_url( '/profile' ) ); ?>"><?php esc_html_e( 'Profile', 'newsblenda-editorial' ); ?></a></li>
            <li><?php echo do_shortcode( '[nbe_logout]' ); ?></li>
        </ul>
    </nav>

    <section class="nbe-dashboard-welcome">
        <h2><?php printf( esc_html__( 'Welcome, %s', 'newsblenda-editorial' ), esc_html( wp_get_current_user()->display_name ) ); ?></h2>
        <p class="nbe-account-status"><?php printf( esc_html__( 'Account status: %s', 'newsblenda-editorial' ), esc_html( $restriction ) ); ?></p>
    </section>

    <section class="nbe-dashboard-cards">
        <div class="card">
            <h3><?php esc_html_e( 'Total Articles', 'newsblenda-editorial' ); ?></h3>
            <p class="stat"><?php echo esc_html( $total_articles ); ?></p>
        </div>
        <div class="card">
            <h3><?php esc_html_e( 'Pending Articles', 'newsblenda-editorial' ); ?></h3>
            <p class="stat"><?php echo esc_html( $pending_articles ); ?></p>
        </div>
        <div class="card">
            <h3><?php esc_html_e( 'Approved Articles', 'newsblenda-editorial' ); ?></h3>
            <p class="stat"><?php echo esc_html( $approved_articles ); ?></p>
        </div>
        <div class="card">
            <h3><?php esc_html_e( 'Rejected Articles', 'newsblenda-editorial' ); ?></h3>
            <p class="stat"><?php echo esc_html( $rejected_articles ); ?></p>
        </div>
    </section>

    <section class="nbe-earnings">
        <h3><?php esc_html_e( 'Earnings Summary', 'newsblenda-editorial' ); ?></h3>
        <p class="earnings-amount"><?php echo esc_html( number_format_i18n( $earnings, 2 ) ); ?> <?php esc_html_e( 'credits', 'newsblenda-editorial' ); ?></p>
        <p class="earnings-details"><?php printf( esc_html__( 'Total words in approved articles: %s', 'newsblenda-editorial' ), esc_html( number_format_i18n( $total_words ) ) ); ?></p>
    </section>

    <section class="nbe-recent-articles">
        <h3><?php esc_html_e( 'Recent Articles', 'newsblenda-editorial' ); ?></h3>
        <?php if ( ! empty( $recent ) ) : ?>
            <ul>
                <?php foreach ( $recent as $r ) : ?>
                    <li>
                        <strong><?php echo esc_html( wp_trim_words( $r->title, 10, '...' ) ); ?></strong>
                        <span class="meta"><?php echo esc_html( $r->status ); ?> — <?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $r->created_at ) ) ); ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else : ?>
            <p><?php esc_html_e( 'No recent articles found.', 'newsblenda-editorial' ); ?></p>
        <?php endif; ?>
    </section>

    <section class="nbe-quick-links">
        <h3><?php esc_html_e( 'Quick Links', 'newsblenda-editorial' ); ?></h3>
        <ul>
            <li><a href="<?php echo esc_url( site_url( '/submit-article' ) ); ?>"><?php esc_html_e( 'Submit new article', 'newsblenda-editorial' ); ?></a></li>
            <li><a href="<?php echo esc_url( site_url( '/profile' ) ); ?>"><?php esc_html_e( 'Edit profile', 'newsblenda-editorial' ); ?></a></li>
            <li><a href="<?php echo esc_url( site_url( '/author-dashboard/articles' ) ); ?>"><?php esc_html_e( 'Manage my articles', 'newsblenda-editorial' ); ?></a></li>
        </ul>
    </section>
</div>
