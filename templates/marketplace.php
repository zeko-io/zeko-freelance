<?php
/**
 * Template: freelance marketplace (project listings).
 *
 * Available: $freelance (Zeko_Freelance), $db (Zeko_Freelance_DB),
 * $user_id (int), $atts (array) shortcode attributes.
 *
 * @package Zeko_Freelance
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings   = zeko_freelance_get_settings();
$current_page       = isset( $_GET['zf_page'] ) ? max( 1, absint( $_GET['zf_page'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification
$search_query     = isset( $_GET['zf_search'] ) ? sanitize_text_field( wp_unslash( $_GET['zf_search'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
$category   = isset( $_GET['zf_category'] ) ? sanitize_key( wp_unslash( $_GET['zf_category'] ) ) : $atts['category']; // phpcs:ignore WordPress.Security.NonceVerification
$budget_min = isset( $_GET['zf_budget_min'] ) ? (float) sanitize_text_field( wp_unslash( $_GET['zf_budget_min'] ) ) : 0.0; // phpcs:ignore WordPress.Security.NonceVerification
$budget_max = isset( $_GET['zf_budget_max'] ) ? (float) sanitize_text_field( wp_unslash( $_GET['zf_budget_max'] ) ) : 0.0; // phpcs:ignore WordPress.Security.NonceVerification
$skill      = isset( $_GET['zf_skill'] ) ? sanitize_text_field( wp_unslash( $_GET['zf_skill'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
$sort_orderby    = isset( $_GET['zf_orderby'] ) ? sanitize_key( wp_unslash( $_GET['zf_orderby'] ) ) : 'created_at'; // phpcs:ignore WordPress.Security.NonceVerification

$projects = $db->get_projects(
	array(
		'status'     => $atts['status'],
		'search'     => $search_query,
		'category'   => $category,
		'budget_min' => $budget_min,
		'budget_max' => $budget_max,
		'skill'      => $skill,
		'orderby'    => $sort_orderby,
		'per_page'   => $atts['per_page'],
		'page'       => $current_page,
	)
);

$total_projects = $db->count_projects(
	array(
		'status'     => $atts['status'],
		'search'     => $search_query,
		'category'   => $category,
		'budget_min' => $budget_min,
		'budget_max' => $budget_max,
		'skill'      => $skill,
	)
);
$max_page       = (int) max( 1, ceil( $total_projects / max( 1, (int) $atts['per_page'] ) ) );
$current_page           = min( $current_page, $max_page );

$skills = $db->get_skills();

$sort_options = array(
	'created_at'  => __( 'Newest first', 'zeko-freelance' ),
	'oldest'      => __( 'Oldest first', 'zeko-freelance' ),
	'featured'    => __( 'Featured first', 'zeko-freelance' ),
	'budget'      => __( 'Lowest budget', 'zeko-freelance' ),
	'budget_desc' => __( 'Highest budget', 'zeko-freelance' ),
);
?>
<div class="zf-marketplace">
	<nav class="zf-tabs" aria-label="<?php esc_attr_e( 'Freelance pages', 'zeko-freelance' ); ?>">
		<a class="zf-tab" href="<?php echo esc_url( zeko_freelance_page_url( 'freelance' ) ); ?>"><?php esc_html_e( 'Dashboard', 'zeko-freelance' ); ?></a>
		<a class="zf-tab zf-tab-active" href="<?php echo esc_url( zeko_freelance_page_url( 'freelance-projects' ) ); ?>"><?php esc_html_e( 'Marketplace', 'zeko-freelance' ); ?></a>
		<a class="zf-tab" href="<?php echo esc_url( zeko_freelance_page_url( 'freelance-post-project' ) ); ?>"><?php esc_html_e( 'Post a Project', 'zeko-freelance' ); ?></a>
		<a class="zf-tab" href="<?php echo esc_url( zeko_freelance_page_url( 'freelance-portfolios' ) ); ?>"><?php esc_html_e( 'Portfolios', 'zeko-freelance' ); ?></a>
	</nav>

	<form class="zf-filters" method="get">
		<input type="hidden" name="page_id" value="<?php echo esc_attr( (string) get_the_ID() ); ?>" />
		<input type="search" name="zf_search" aria-label="<?php esc_attr_e( 'Search projects', 'zeko-freelance' ); ?>" value="<?php echo esc_attr( $search_query ); ?>" placeholder="<?php esc_attr_e( 'Search projects…', 'zeko-freelance' ); ?>" />
		<select name="zf_category">
			<option value=""><?php esc_html_e( 'All categories', 'zeko-freelance' ); ?></option>
			<?php
			$seen = array();
			foreach ( $skills as $skill_row ) {
				if ( isset( $seen[ $skill_row->category ] ) ) {
					continue;
				}
				$seen[ $skill_row->category ] = true;
				printf(
					'<option value="%s" %s>%s</option>',
					esc_attr( $skill_row->category ),
					selected( $category, $skill_row->category, false ),
					esc_html( ucfirst( $skill_row->category ) )
				);
			}
			?>
		</select>
		<input type="number" name="zf_budget_min" aria-label="<?php esc_attr_e( 'Min budget', 'zeko-freelance' ); ?>" value="<?php echo esc_attr( $budget_min > 0 ? (string) $budget_min : '' ); ?>" placeholder="<?php esc_attr_e( 'Min budget', 'zeko-freelance' ); ?>" min="0" step="0.01" />
		<input type="number" name="zf_budget_max" aria-label="<?php esc_attr_e( 'Max budget', 'zeko-freelance' ); ?>" value="<?php echo esc_attr( $budget_max > 0 ? (string) $budget_max : '' ); ?>" placeholder="<?php esc_attr_e( 'Max budget', 'zeko-freelance' ); ?>" min="0" step="0.01" />
		<select name="zf_skill">
			<option value=""><?php esc_html_e( 'All skills', 'zeko-freelance' ); ?></option>
			<?php foreach ( $skills as $skill_row ) : ?>
				<option value="<?php echo esc_attr( $skill_row->name ); ?>" <?php selected( $skill, $skill_row->name ); ?>><?php echo esc_html( $skill_row->name ); ?></option>
			<?php endforeach; ?>
		</select>
		<select name="zf_orderby">
			<?php foreach ( $sort_options as $key => $label ) : ?>
				<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $sort_orderby, $key ); ?>><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
		<button type="submit" class="zf-btn"><?php esc_html_e( 'Filter', 'zeko-freelance' ); ?></button>
	</form>

	<div class="zf-grid">
		<?php if ( empty( $projects ) ) : ?>
			<p class="zf-empty"><?php esc_html_e( 'No open projects match your filters.', 'zeko-freelance' ); ?></p>
		<?php else : ?>
			<?php foreach ( $projects as $project ) : ?>
				<article class="zf-project-card">
					<?php if ( $project->featured ) : ?>
						<span class="zf-badge"><?php esc_html_e( 'Featured', 'zeko-freelance' ); ?></span>
					<?php endif; ?>
					<h3 class="zf-project-title">
						<a href="<?php echo esc_url( add_query_arg( 'zf_pid', $project->id, zeko_freelance_page_url( 'freelance-project' ) ) ); ?>"><?php echo esc_html( $project->title ); ?></a>
					</h3>
					<p class="zf-project-excerpt">
						<?php echo esc_html( wp_trim_words( (string) $project->description, 24 ) ); ?>
					</p>
					<div class="zf-project-meta">
						<span class="zf-price">
							<?php
							echo esc_html(
								sprintf(
									'%s%s - %s%s',
									zeko_freelance_currency_symbol( (string) $project->currency ),
									number_format_i18n( (float) $project->budget_min ),
									zeko_freelance_currency_symbol( (string) $project->currency ),
									number_format_i18n( (float) $project->budget_max )
								)
							);
							?>
						</span>
						<span class="zf-meta-item"><?php echo esc_html( ucfirst( $project->category ) ); ?></span>
						<span class="zf-meta-item"><?php echo esc_html( mysql2date( 'M j, Y', $project->created_at ) ); ?></span>
					</div>
					<?php
					$project_skills = array_filter( array_map( 'trim', explode( ',', (string) $project->skills ) ) );
					if ( $project_skills ) :
						?>
						<div class="zf-tags">
							<?php foreach ( $project_skills as $project_skill ) : ?>
								<span class="zf-tag"><?php echo esc_html( $project_skill ); ?></span>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</article>
			<?php endforeach; ?>
		<?php endif; ?>
	</div>

	<?php if ( is_user_logged_in() ) : ?>
		<p class="zf-cta">
			<a class="zf-btn" href="<?php echo esc_url( zeko_freelance_page_url( 'freelance-post-project' ) ); ?>"><?php esc_html_e( 'Post a project', 'zeko-freelance' ); ?></a>
		</p>
	<?php else : ?>
		<p class="zf-cta">
			<a class="zf-btn" href="<?php echo esc_url( wp_login_url( zeko_freelance_page_url( 'freelance-projects' ) ) ); ?>"><?php esc_html_e( 'Log in to post a project', 'zeko-freelance' ); ?></a>
		</p>
	<?php endif; ?>

	<?php if ( $max_page > 1 ) : ?>
		<nav class="zf-pagination" aria-label="<?php esc_attr_e( 'Project pages', 'zeko-freelance' ); ?>">
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
