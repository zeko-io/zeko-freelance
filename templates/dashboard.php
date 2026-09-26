<?php
/**
 * Template: freelance dashboard.
 *
 * Available: $freelance (Zeko_Freelance), $db (Zeko_Freelance_DB),
 * $user_id (int).
 *
 * @package Zeko_Freelance
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings    = zeko_freelance_get_settings();
$my_projects = $db->get_projects(
	array(
		'user_id'  => $user_id,
		'per_page' => 10,
	)
);
$my_bids     = $db->get_user_bids( $user_id );
$contracts   = $db->get_contracts_for_user( $user_id );
$portfolios  = $db->get_portfolios( $user_id );
$rating      = $db->average_rating( $user_id );
$verified    = $db->is_user_verified( $user_id );

$bookmarked_ids = $freelance->get_bookmarked_project_ids( $user_id );
$bookmarked     = array();
if ( $bookmarked_ids ) {
	foreach ( array_slice( $bookmarked_ids, 0, 10 ) as $bookmark_id ) {
		$bookmark_project = $db->get_project( $bookmark_id );
		if ( $bookmark_project ) {
			$bookmarked[] = $bookmark_project;
		}
	}
}

$categories = Zeko_Freelance_Ajax::allowed_categories();

$edit_portfolio_id = isset( $_GET['zf_edit_portfolio'] ) ? absint( $_GET['zf_edit_portfolio'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
$edit_portfolio    = $edit_portfolio_id ? $db->get_portfolio( $edit_portfolio_id ) : null;
if ( $edit_portfolio && (int) $edit_portfolio->user_id !== $user_id ) {
	$edit_portfolio = null;
}
?>
<div class="zf-dashboard">
	<nav class="zf-tabs" aria-label="<?php esc_attr_e( 'Freelance pages', 'zeko-freelance' ); ?>">
		<a class="zf-tab zf-tab-active" href="<?php echo esc_url( zeko_freelance_page_url( 'freelance' ) ); ?>"><?php esc_html_e( 'Dashboard', 'zeko-freelance' ); ?></a>
		<a class="zf-tab" href="<?php echo esc_url( zeko_freelance_page_url( 'freelance-projects' ) ); ?>"><?php esc_html_e( 'Marketplace', 'zeko-freelance' ); ?></a>
		<a class="zf-tab" href="<?php echo esc_url( zeko_freelance_page_url( 'freelance-post-project' ) ); ?>"><?php esc_html_e( 'Post a Project', 'zeko-freelance' ); ?></a>
		<a class="zf-tab" href="<?php echo esc_url( zeko_freelance_page_url( 'freelance-portfolios' ) ); ?>"><?php esc_html_e( 'Portfolios', 'zeko-freelance' ); ?></a>
	</nav>

	<p class="zf-cta" style="margin-bottom:16px;">
		<a class="zf-btn" href="<?php echo esc_url( zeko_freelance_page_url( 'freelance-post-project' ) ); ?>"><?php esc_html_e( 'Post a project', 'zeko-freelance' ); ?></a>
	</p>

	<div class="zf-summary">
		<div class="zf-card">
			<span class="zf-card-label"><?php esc_html_e( 'My projects', 'zeko-freelance' ); ?></span>
			<span class="zf-card-value"><?php echo esc_html( count( $my_projects ) ); ?></span>
		</div>
		<div class="zf-card">
			<span class="zf-card-label"><?php esc_html_e( 'My bids', 'zeko-freelance' ); ?></span>
			<span class="zf-card-value"><?php echo esc_html( count( $my_bids ) ); ?></span>
		</div>
		<div class="zf-card">
			<span class="zf-card-label"><?php esc_html_e( 'Contracts', 'zeko-freelance' ); ?></span>
			<span class="zf-card-value"><?php echo esc_html( count( $contracts ) ); ?></span>
		</div>
		<div class="zf-card">
			<span class="zf-card-label"><?php esc_html_e( 'Rating', 'zeko-freelance' ); ?></span>
			<span class="zf-card-value"><?php echo esc_html( $rating > 0 ? number_format_i18n( $rating, 1 ) . ' ★' : '-' ); ?></span>
		</div>
	</div>

	<?php if ( $verified ) : ?>
		<p class="zf-verified">
			<span class="dashicons dashicons-yes-alt"></span>
			<?php esc_html_e( 'Verified freelancer', 'zeko-freelance' ); ?>
		</p>
	<?php endif; ?>

	<div class="zf-columns">
		<div class="zf-column">
			<h3><?php esc_html_e( 'My projects', 'zeko-freelance' ); ?></h3>
			<?php if ( $my_projects ) : ?>
				<ul class="zf-list">
					<?php foreach ( $my_projects as $project ) : ?>
						<li class="zf-list-item">
							<span class="zf-list-title">
								<a href="<?php echo esc_url( add_query_arg( 'zf_pid', $project->id, zeko_freelance_page_url( 'freelance-project' ) ) ); ?>"><?php echo esc_html( $project->title ); ?></a>
							</span>
							<span class="zf-status zf-status-<?php echo esc_attr( $project->status ); ?>"><?php echo esc_html( $project->status ); ?></span>
							<span class="zf-list-date"><?php echo esc_html( mysql2date( 'M j, Y', $project->created_at ) ); ?></span>
							<?php if ( 'open' === $project->status ) : ?>
								<a class="zf-link" href="<?php echo esc_url( add_query_arg( 'zf_edit', $project->id, zeko_freelance_page_url( 'freelance-post-project' ) ) ); ?>"><?php esc_html_e( 'Edit', 'zeko-freelance' ); ?></a>
								<button type="button" class="zf-link zf-link-danger zf-delete-project" data-project-id="<?php echo esc_attr( (string) $project->id ); ?>" data-title="<?php echo esc_attr( $project->title ); ?>"><?php esc_html_e( 'Delete', 'zeko-freelance' ); ?></button>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php else : ?>
				<p class="zf-empty"><?php esc_html_e( 'No projects yet.', 'zeko-freelance' ); ?></p>
			<?php endif; ?>

			<h3><?php esc_html_e( 'My bids', 'zeko-freelance' ); ?></h3>
			<?php if ( $my_bids ) : ?>
				<ul class="zf-list">
					<?php foreach ( $my_bids as $bid ) : ?>
						<li class="zf-list-item">
							<span class="zf-list-title">
								<?php echo esc_html( sprintf( '%s %s', number_format_i18n( (float) $bid->amount, 2 ), (string) $settings['currency'] ) ); ?>
							</span>
							<span class="zf-status zf-status-<?php echo esc_attr( $bid->status ); ?>"><?php echo esc_html( $bid->status ); ?></span>
							<span class="zf-list-date"><?php echo esc_html( mysql2date( 'M j, Y', $bid->created_at ) ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php else : ?>
				<p class="zf-empty"><?php esc_html_e( 'You have not placed any bids yet.', 'zeko-freelance' ); ?></p>
			<?php endif; ?>
		</div>

		<div class="zf-column">
			<h3><?php esc_html_e( 'Bookmarked projects', 'zeko-freelance' ); ?></h3>
			<?php if ( $bookmarked ) : ?>
				<ul class="zf-list">
					<?php foreach ( $bookmarked as $bookmark ) : ?>
						<li class="zf-list-item">
							<span class="zf-list-title">
								<a href="<?php echo esc_url( add_query_arg( 'zf_pid', $bookmark->id, zeko_freelance_page_url( 'freelance-project' ) ) ); ?>"><?php echo esc_html( $bookmark->title ); ?></a>
							</span>
							<span class="zf-status zf-status-<?php echo esc_attr( $bookmark->status ); ?>"><?php echo esc_html( $bookmark->status ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php else : ?>
				<p class="zf-empty"><?php esc_html_e( 'Projects you bookmark will appear here.', 'zeko-freelance' ); ?></p>
			<?php endif; ?>

			<h3><?php esc_html_e( 'Portfolio items', 'zeko-freelance' ); ?></h3>
			<?php if ( $portfolios ) : ?>
				<ul class="zf-list">
					<?php foreach ( $portfolios as $item ) : ?>
						<li class="zf-list-item">
							<span class="zf-list-title"><?php echo esc_html( $item->title ); ?></span>
							<span class="zf-list-date"><?php echo esc_html( mysql2date( 'M j, Y', $item->created_at ) ); ?></span>
							<a class="zf-link" href="<?php echo esc_url( add_query_arg( 'zf_edit_portfolio', $item->id, zeko_freelance_page_url( 'freelance' ) ) ); ?>"><?php esc_html_e( 'Edit', 'zeko-freelance' ); ?></a>
							<button type="button" class="zf-link zf-link-danger zf-delete-portfolio" data-portfolio-id="<?php echo esc_attr( (string) $item->id ); ?>" data-title="<?php echo esc_attr( $item->title ); ?>"><?php esc_html_e( 'Delete', 'zeko-freelance' ); ?></button>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php else : ?>
				<p class="zf-empty"><?php esc_html_e( 'No portfolio items yet.', 'zeko-freelance' ); ?></p>
			<?php endif; ?>

			<form class="zf-form" id="zf-portfolio-form">
				<h3><?php echo $edit_portfolio ? esc_html__( 'Edit portfolio item', 'zeko-freelance' ) : esc_html__( 'Add a portfolio item', 'zeko-freelance' ); ?></h3>
				<input type="hidden" name="zf_portfolio_id" value="<?php echo $edit_portfolio ? esc_attr( (string) $edit_portfolio->id ) : ''; ?>" />
				<div class="zf-field">
					<label for="zf_portfolio_title"><?php esc_html_e( 'Title', 'zeko-freelance' ); ?> <span class="zf-required">*</span></label>
					<input type="text" id="zf_portfolio_title" name="zf_portfolio_title" maxlength="200" value="<?php echo $edit_portfolio ? esc_attr( $edit_portfolio->title ) : ''; ?>" required />
				</div>
				<div class="zf-field">
					<label for="zf_portfolio_category"><?php esc_html_e( 'Category', 'zeko-freelance' ); ?> <span class="zf-required">*</span></label>
					<select id="zf_portfolio_category" name="zf_portfolio_category" required>
						<option value=""><?php esc_html_e( '— Select —', 'zeko-freelance' ); ?></option>
						<?php foreach ( $categories as $key ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $edit_portfolio ? $edit_portfolio->category : '', $key ); ?>><?php echo esc_html( ucfirst( $key ) ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="zf-field">
					<label for="zf_portfolio_description"><?php esc_html_e( 'Description', 'zeko-freelance' ); ?></label>
					<textarea id="zf_portfolio_description" name="zf_portfolio_description" rows="4" maxlength="3000"><?php echo $edit_portfolio ? esc_textarea( (string) $edit_portfolio->description ) : ''; ?></textarea>
				</div>
				<div class="zf-field">
					<label for="zf_portfolio_skills"><?php esc_html_e( 'Skills (comma separated)', 'zeko-freelance' ); ?></label>
					<input type="text" id="zf_portfolio_skills" name="zf_portfolio_skills" value="<?php echo $edit_portfolio ? esc_attr( (string) $edit_portfolio->skills ) : ''; ?>" maxlength="400" list="zf-skill-options" data-zf-skill-suggest="1" />
					<datalist id="zf-skill-options"></datalist>
				</div>
				<div class="zf-field">
					<label for="zf_portfolio_link"><?php esc_html_e( 'Link (optional)', 'zeko-freelance' ); ?></label>
					<input type="url" id="zf_portfolio_link" name="zf_portfolio_link" value="<?php echo $edit_portfolio ? esc_attr( (string) $edit_portfolio->link ) : ''; ?>" placeholder="https://…" />
				</div>
				<div class="zf-field">
					<label for="zf_portfolio_image"><?php esc_html_e( 'Image (optional)', 'zeko-freelance' ); ?></label>
					<input type="file" id="zf_portfolio_image" name="zf_portfolio_image" accept="image/*" />
					<?php if ( $edit_portfolio && $edit_portfolio->image_url ) : ?>
						<span class="zf-hint"><?php esc_html_e( 'Leave empty to keep the current image.', 'zeko-freelance' ); ?></span>
					<?php endif; ?>
				</div>
				<div class="zf-form-actions">
					<button type="submit" class="zf-btn"><?php echo $edit_portfolio ? esc_html__( 'Update item', 'zeko-freelance' ) : esc_html__( 'Add item', 'zeko-freelance' ); ?></button>
				</div>
				<div class="zf-notice" id="zf-portfolio-notice"></div>
			</form>

			<h3><?php esc_html_e( 'Open contracts', 'zeko-freelance' ); ?></h3>
			<?php if ( $contracts ) : ?>
				<ul class="zf-list">
					<?php foreach ( $contracts as $contract ) : ?>
						<li class="zf-list-item">
							<span class="zf-list-title">
								<?php
								echo esc_html(
									sprintf(
										'%s %s (client %d · freelancer %d)',
										number_format_i18n( (float) $contract->budget, 2 ),
										$contract->currency,
										(int) $contract->client_id,
										(int) $contract->freelancer_id
									)
								);
								?>
							</span>
							<span class="zf-status zf-status-<?php echo esc_attr( $contract->status ); ?>"><?php echo esc_html( $contract->status ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php else : ?>
				<p class="zf-empty"><?php esc_html_e( 'No contracts yet.', 'zeko-freelance' ); ?></p>
			<?php endif; ?>
		</div>
	</div>
</div>
