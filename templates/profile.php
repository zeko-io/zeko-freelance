<?php
/**
 * Template: public freelancer profile.
 *
 * Available: $freelance (Zeko_Freelance), $db (Zeko_Freelance_DB),
 * $user_id (int), $atts (array) containing the WP_User under 'profile'.
 *
 * @package Zeko_Freelance
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$profile = $atts['profile'] ?? null;
if ( ! $profile ) {
	return;
}

$profile_id = (int) $profile->ID;
$settings   = zeko_freelance_get_settings();

$skills       = $db->get_user_skills( $profile_id );
$portfolios   = $db->get_portfolios( $profile_id, array( 'per_page' => 6 ) );
$reviews      = $db->get_reviews_for( $profile_id, 20 );
$rating       = $db->average_rating( $profile_id );
$review_count = $db->review_count( $profile_id );
$verified     = $db->is_user_verified( $profile_id );
$location     = get_user_meta( $profile_id, 'zeko_freelance_location', true );
$is_own       = (int) $user_id === $profile_id;
$member_since = $profile->user_registered ? mysql2date( 'M Y', $profile->user_registered ) : '';
$bio          = (string) $profile->description;

$id_status          = $db->user_verification_status( $profile_id, 'id' );
$business_status    = $db->user_verification_status( $profile_id, 'business' );
$verification_types = array(
	'id'       => __( 'ID verification', 'zeko-freelance' ),
	'business' => __( 'Business verification', 'zeko-freelance' ),
);
?>
<div class="zf-profile">
	<nav class="zf-tabs" aria-label="<?php esc_attr_e( 'Freelance pages', 'zeko-freelance' ); ?>">
		<a class="zf-tab" href="<?php echo esc_url( zeko_freelance_page_url( 'freelance' ) ); ?>"><?php esc_html_e( 'Dashboard', 'zeko-freelance' ); ?></a>
		<a class="zf-tab" href="<?php echo esc_url( zeko_freelance_page_url( 'freelance-projects' ) ); ?>"><?php esc_html_e( 'Marketplace', 'zeko-freelance' ); ?></a>
		<a class="zf-tab" href="<?php echo esc_url( zeko_freelance_page_url( 'freelance-portfolios' ) ); ?>"><?php esc_html_e( 'Portfolios', 'zeko-freelance' ); ?></a>
	</nav>

	<header class="zf-profile-head">
		<div class="zf-avatar"><?php echo get_avatar( $profile_id, 96 ); ?></div>
		<div class="zf-profile-id">
			<h1 class="zf-profile-name">
				<?php echo esc_html( $profile->display_name ); ?>
				<?php if ( $verified ) : ?>
					<span class="zf-verified" title="<?php esc_attr_e( 'Verified member', 'zeko-freelance' ); ?>">
						<span class="dashicons dashicons-yes-alt"></span>
						<?php esc_html_e( 'Verified', 'zeko-freelance' ); ?>
					</span>
				<?php endif; ?>
				<?php echo apply_filters( 'zeko_freelance_profile_badges', '', (int) $profile_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Premium badge markup is escaped by the callback (empty by default). ?>
			</h1>
			<p class="zf-profile-meta">
				<?php if ( $location ) : ?>
					<span class="zf-meta-item"><?php echo esc_html( $location ); ?></span>
				<?php endif; ?>
				<?php if ( $member_since ) : ?>
					<span class="zf-meta-item">
						<?php
						echo esc_html(
							sprintf(
							/* translators: %s: join month/year */
								__( 'Member since %s', 'zeko-freelance' ),
								$member_since
							)
						);
						?>
					</span>
				<?php endif; ?>
				<span class="zf-meta-item">
					<?php
					echo esc_html(
						sprintf(
						/* translators: %1$d: review count, %2$s: average rating */
							_n( '%1$d review · %2$s', '%1$d reviews · %2$s', $review_count, 'zeko-freelance' ),
							$review_count,
							$rating > 0 ? number_format_i18n( $rating, 1 ) . ' ★' : '—'
						)
					);
					?>
				</span>
			</p>
		</div>
	</header>

	<?php if ( $skills ) : ?>
		<div class="zf-tags">
			<?php foreach ( $skills as $skill ) : ?>
				<span class="zf-tag"><?php echo esc_html( $skill ); ?></span>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<?php if ( $bio ) : ?>
		<div class="zf-profile-bio">
			<h2 class="zf-section-title"><?php esc_html_e( 'About', 'zeko-freelance' ); ?></h2>
			<?php echo wp_kses_post( wpautop( esc_textarea( $bio ) ) ); ?>
		</div>
	<?php endif; ?>

	<?php if ( $is_own ) : ?>
		<section class="zf-profile-verify">
			<h2 class="zf-section-title"><?php esc_html_e( 'Verification', 'zeko-freelance' ); ?></h2>
			<?php
			foreach ( $verification_types as $type_key => $type_label ) :
				$type_status = 'id' === $type_key ? $id_status : $business_status;
				?>
				<div class="zf-verify-row">
					<span class="zf-verify-label"><?php echo esc_html( $type_label ); ?></span>
					<?php if ( 'approved' === $type_status ) : ?>
						<span class="zf-status zf-status-paid"><?php esc_html_e( 'Verified', 'zeko-freelance' ); ?></span>
					<?php elseif ( 'pending' === $type_status ) : ?>
						<span class="zf-status zf-status-pending"><?php esc_html_e( 'Pending review', 'zeko-freelance' ); ?></span>
					<?php elseif ( 'rejected' === $type_status ) : ?>
						<span class="zf-status zf-status-rejected"><?php esc_html_e( 'Rejected — reapply', 'zeko-freelance' ); ?></span>
					<?php else : ?>
						<span class="zf-status zf-status-cancelled"><?php esc_html_e( 'Not applied', 'zeko-freelance' ); ?></span>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>

			<?php if ( 'approved' !== $id_status || 'approved' !== $business_status ) : ?>
				<form class="zf-form" id="zf-verification-form">
					<h3><?php esc_html_e( 'Apply for verification', 'zeko-freelance' ); ?></h3>
					<div class="zf-field">
						<label for="zf_verification_type"><?php esc_html_e( 'Verification type', 'zeko-freelance' ); ?> <span class="zf-required">*</span></label>
						<select id="zf_verification_type" name="zf_verification_type" required>
							<?php
							foreach ( $verification_types as $type_key => $type_label ) :
								$type_status = 'id' === $type_key ? $id_status : $business_status;
								?>
								<option value="<?php echo esc_attr( $type_key ); ?>" <?php disabled( in_array( $type_status, array( 'approved', 'pending' ), true ) ); ?>><?php echo esc_html( $type_label ); ?></option>
							<?php endforeach; ?>
						</select>
						<span class="zf-hint"><?php esc_html_e( 'ID verification confirms your identity; business verification confirms your company. An administrator reviews each request.', 'zeko-freelance' ); ?></span>
					</div>
					<div class="zf-form-actions">
						<button type="submit" class="zf-btn"><?php esc_html_e( 'Submit request', 'zeko-freelance' ); ?></button>
					</div>
					<div class="zf-notice" id="zf-verification-notice"></div>
				</form>
			<?php endif; ?>
		</section>

		<form class="zf-form" id="zf-profile-form">
			<h2 class="zf-section-title"><?php esc_html_e( 'Edit your profile', 'zeko-freelance' ); ?></h2>
			<div class="zf-field">
				<label for="zf_skills"><?php esc_html_e( 'Skills (comma separated)', 'zeko-freelance' ); ?></label>
				<input type="text" id="zf_skills" name="zf_skills" value="<?php echo esc_attr( implode( ', ', $skills ) ); ?>" maxlength="400" list="zf-skill-options" data-zf-skill-suggest="1" />
				<datalist id="zf-skill-options"></datalist>
				<span class="zf-hint"><?php esc_html_e( 'Up to 20 skills, e.g. WordPress, PHP, Logo Design.', 'zeko-freelance' ); ?></span>
			</div>
			<div class="zf-field">
				<label for="zf_bio"><?php esc_html_e( 'Bio', 'zeko-freelance' ); ?></label>
				<textarea id="zf_bio" name="zf_bio" rows="4" maxlength="1000"><?php echo esc_textarea( $bio ); ?></textarea>
			</div>
			<div class="zf-field">
				<label for="zf_location"><?php esc_html_e( 'Location', 'zeko-freelance' ); ?></label>
				<input type="text" id="zf_location" name="zf_location" value="<?php echo esc_attr( (string) $location ); ?>" maxlength="150" />
			</div>
			<div class="zf-form-actions">
				<button type="submit" class="zf-btn"><?php esc_html_e( 'Save profile', 'zeko-freelance' ); ?></button>
			</div>
			<div class="zf-notice" id="zf-profile-notice"></div>
		</form>
	<?php endif; ?>

	<?php if ( $portfolios ) : ?>
		<section class="zf-profile-portfolio">
			<h2 class="zf-section-title"><?php esc_html_e( 'Portfolio', 'zeko-freelance' ); ?></h2>
			<div class="zf-grid">
				<?php foreach ( $portfolios as $item ) : ?>
					<article class="zf-project-card">
						<?php if ( $item->image_url ) : ?>
							<div class="zf-portfolio-thumb">
								<img src="<?php echo esc_url( $item->image_url ); ?>" alt="<?php echo esc_attr( $item->title ); ?>" loading="lazy" />
							</div>
						<?php endif; ?>
						<h3 class="zf-project-title"><?php echo esc_html( $item->title ); ?></h3>
						<p class="zf-project-excerpt"><?php echo esc_html( wp_trim_words( (string) $item->description, 18 ) ); ?></p>
						<div class="zf-project-meta">
							<span class="zf-meta-item"><?php echo esc_html( ucfirst( $item->category ) ); ?></span>
						</div>
						<?php if ( $item->link ) : ?>
							<a class="zf-btn zf-btn-sm" href="<?php echo esc_url( $item->link ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'View project', 'zeko-freelance' ); ?></a>
						<?php endif; ?>
					</article>
				<?php endforeach; ?>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( $reviews ) : ?>
		<section class="zf-profile-reviews">
			<h2 class="zf-section-title"><?php esc_html_e( 'Reviews', 'zeko-freelance' ); ?></h2>
			<ul class="zf-list">
				<?php
				foreach ( $reviews as $review ) :
					$reviewer = get_userdata( (int) $review->reviewer_id );
					?>
					<li class="zf-list-item zf-review-item">
						<div class="zf-review-main">
							<span class="zf-review-rating"><?php echo esc_html( str_repeat( '★', (int) $review->rating ) ); ?></span>
							<span class="zf-review-meta">
								<?php echo esc_html( $reviewer ? $reviewer->display_name : __( 'Member', 'zeko-freelance' ) ); ?>
								· <?php echo esc_html( mysql2date( 'M j, Y', $review->created_at ) ); ?>
							</span>
						</div>
						<?php if ( $review->comment ) : ?>
							<p class="zf-review-comment"><?php echo esc_html( $review->comment ); ?></p>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
		</section>
	<?php endif; ?>
</div>
