<?php
/**
 * Template: freelance portfolios gallery.
 *
 * Available: $freelance (Zeko_Freelance), $db (Zeko_Freelance_DB),
 * $user_id (int), $atts (array) shortcode attributes.
 *
 * @package Zeko_Freelance
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$category     = isset( $_GET['zf_category'] ) ? sanitize_key( $_GET['zf_category'] ) : $atts['category']; // phpcs:ignore WordPress.Security.NonceVerification
$skill        = isset( $_GET['zf_skill'] ) ? sanitize_text_field( wp_unslash( $_GET['zf_skill'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
$skill        = mb_substr( $skill, 0, 50 );
$current_page = isset( $_GET['zf_page'] ) ? max( 1, absint( $_GET['zf_page'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification

$portfolios = $db->get_portfolios(
	0,
	array(
		'category' => $category,
		'skill'    => $skill,
		'per_page' => $atts['per_page'],
		'page'     => $current_page,
	)
);

$total_portfolios = $db->count_portfolios(
	0,
	array(
		'category' => $category,
		'skill'    => $skill,
	)
);
$max_page         = (int) max( 1, ceil( $total_portfolios / max( 1, (int) $atts['per_page'] ) ) );
$current_page     = min( $current_page, $max_page );
?>
<div class="zf-portfolios">
	<nav class="zf-tabs" aria-label="<?php esc_attr_e( 'Freelance pages', 'zeko-freelance' ); ?>">
		<a class="zf-tab" href="<?php echo esc_url( zeko_freelance_page_url( 'freelance' ) ); ?>"><?php esc_html_e( 'Dashboard', 'zeko-freelance' ); ?></a>
		<a class="zf-tab" href="<?php echo esc_url( zeko_freelance_page_url( 'freelance-projects' ) ); ?>"><?php esc_html_e( 'Marketplace', 'zeko-freelance' ); ?></a>
		<a class="zf-tab zf-tab-active" href="<?php echo esc_url( zeko_freelance_page_url( 'freelance-portfolios' ) ); ?>"><?php esc_html_e( 'Portfolios', 'zeko-freelance' ); ?></a>
	</nav>

	<form class="zf-filters" method="get">
		<input type="hidden" name="page_id" value="<?php echo esc_attr( (string) get_the_ID() ); ?>" />
		<select name="zf_category">
			<option value=""><?php esc_html_e( 'All categories', 'zeko-freelance' ); ?></option>
			<?php foreach ( Zeko_Freelance_Ajax::allowed_categories() as $key ) : ?>
				<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $category, $key ); ?>><?php echo esc_html( ucfirst( $key ) ); ?></option>
			<?php endforeach; ?>
		</select>
		<input type="search" name="zf_skill" value="<?php echo esc_attr( $skill ); ?>" placeholder="<?php esc_attr_e( 'Filter by skill…', 'zeko-freelance' ); ?>" />
		<button type="submit" class="zf-btn"><?php esc_html_e( 'Filter', 'zeko-freelance' ); ?></button>
	</form>

	<div class="zf-grid">
		<?php if ( empty( $portfolios ) ) : ?>
			<p class="zf-empty"><?php esc_html_e( 'No portfolio items yet. Be the first to showcase your work.', 'zeko-freelance' ); ?></p>
		<?php else : ?>
			<?php foreach ( $portfolios as $item ) : ?>
				<article class="zf-project-card">
					<?php if ( $item->image_url ) : ?>
						<div class="zf-portfolio-thumb">
							<img src="<?php echo esc_url( $item->image_url ); ?>" alt="<?php echo esc_attr( $item->title ); ?>" loading="lazy" />
						</div>
					<?php endif; ?>
					<h3 class="zf-project-title"><?php echo esc_html( $item->title ); ?></h3>
					<p class="zf-project-excerpt">
						<?php echo esc_html( wp_trim_words( (string) $item->description, 24 ) ); ?>
					</p>
					<div class="zf-project-meta">
						<span class="zf-meta-item"><?php echo esc_html( ucfirst( $item->category ) ); ?></span>
						<span class="zf-meta-item">
							<a href="<?php echo esc_url( add_query_arg( 'zf_uid', $item->user_id, zeko_freelance_page_url( 'freelance-profile' ) ) ); ?>"><?php echo esc_html( get_the_author_meta( 'display_name', (int) $item->user_id ) ); ?></a>
							<?php echo apply_filters( 'zeko_freelance_profile_badges', '', (int) $item->user_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Premium badge markup is escaped by the callback (empty by default). ?>
						</span>
						<span class="zf-meta-item"><?php echo esc_html( mysql2date( 'M j, Y', $item->created_at ) ); ?></span>
					</div>
					<?php
					$item_skills = array_filter( array_map( 'trim', explode( ',', (string) $item->skills ) ) );
					if ( $item_skills ) :
						?>
						<div class="zf-tags">
							<?php foreach ( $item_skills as $item_skill ) : ?>
								<a class="zf-tag" href="
								<?php
								echo esc_url(
									add_query_arg(
										array(
											'zf_skill'    => $item_skill,
											'zf_category' => '',
										),
										get_permalink()
									)
								);
								?>
														"><?php echo esc_html( $item_skill ); ?></a>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
					<?php if ( $item->link ) : ?>
						<a class="zf-btn zf-btn-sm" href="<?php echo esc_url( $item->link ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'View project', 'zeko-freelance' ); ?></a>
					<?php endif; ?>
				</article>
			<?php endforeach; ?>
		<?php endif; ?>
	</div>

	<?php if ( $max_page > 1 ) : ?>
		<nav class="zf-pagination" aria-label="<?php esc_attr_e( 'Portfolio pages', 'zeko-freelance' ); ?>">
			<?php if ( $current_page > 1 ) : ?>
				<a class="zf-pagination-link" rel="prev" href="<?php echo esc_url( add_query_arg( 'zf_page', $current_page - 1 ) ); ?>"><?php esc_html_e( '← Newer', 'zeko-freelance' ); ?></a>
			<?php endif; ?>
			<span class="zf-pagination-current"><?php echo esc_html( sprintf( /* translators: %1$d: current page, %2$d: total pages */ __( 'Page %1$d of %2$d', 'zeko-freelance' ), $current_page, $max_page ) ); ?></span>
			<?php if ( $current_page < $max_page ) : ?>
				<a class="zf-pagination-link" rel="next" href="<?php echo esc_url( add_query_arg( 'zf_page', $current_page + 1 ) ); ?>"><?php esc_html_e( 'Older →', 'zeko-freelance' ); ?></a>
			<?php endif; ?>
		</nav>
	<?php endif; ?>
</div>
