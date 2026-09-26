<?php
/**
 * Template: freelance login prompt.
 *
 * Available: $freelance (Zeko_Freelance), $db (Zeko_Freelance_DB),
 * $user_id (int).
 *
 * @package Zeko_Freelance
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="zf-dashboard">
	<nav class="zf-tabs" aria-label="<?php esc_attr_e( 'Freelance pages', 'zeko-freelance' ); ?>">
		<a class="zf-tab" href="<?php echo esc_url( zeko_freelance_page_url( 'freelance' ) ); ?>"><?php esc_html_e( 'Dashboard', 'zeko-freelance' ); ?></a>
		<a class="zf-tab" href="<?php echo esc_url( zeko_freelance_page_url( 'freelance-projects' ) ); ?>"><?php esc_html_e( 'Marketplace', 'zeko-freelance' ); ?></a>
		<a class="zf-tab" href="<?php echo esc_url( zeko_freelance_page_url( 'freelance-post-project' ) ); ?>"><?php esc_html_e( 'Post a Project', 'zeko-freelance' ); ?></a>
		<a class="zf-tab" href="<?php echo esc_url( zeko_freelance_page_url( 'freelance-portfolios' ) ); ?>"><?php esc_html_e( 'Portfolios', 'zeko-freelance' ); ?></a>
	</nav>
	<div class="zf-card zf-login-card">
		<h3><?php esc_html_e( 'Join the freelance marketplace', 'zeko-freelance' ); ?></h3>
		<p><?php esc_html_e( 'Log in to post projects, place bids and manage your contracts.', 'zeko-freelance' ); ?></p>
		<p>
			<a class="zf-btn" href="<?php echo esc_url( wp_login_url( zeko_freelance_page_url( 'freelance' ) ) ); ?>"><?php esc_html_e( 'Log in', 'zeko-freelance' ); ?></a>
			<a class="zf-btn zf-btn-secondary" href="<?php echo esc_url( wp_registration_url() ); ?>"><?php esc_html_e( 'Create account', 'zeko-freelance' ); ?></a>
		</p>
	</div>
</div>
