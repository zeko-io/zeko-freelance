<?php
/**
 * Template: single project detail page.
 *
 * Available: $freelance (Zeko_Freelance), $db (Zeko_Freelance_DB),
 * $user_id (int), $atts (array) containing the project object under 'project'.
 *
 * @package Zeko_Freelance
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$project = $atts['project'] ?? null;
if ( ! $project ) {
	return;
}

$settings       = zeko_freelance_get_settings();
$project_skills = array_filter( array_map( 'trim', explode( ',', (string) $project->skills ) ) );
$client         = get_userdata( (int) $project->user_id );
$rating         = $db->average_rating( (int) $project->user_id );
$verified       = $db->is_user_verified( (int) $project->user_id );
$related        = $db->get_related_projects( (string) $project->category, (int) $project->id, 3 );
$bookmarked     = is_user_logged_in() && $freelance->is_project_bookmarked( $user_id, (int) $project->id );
$is_owner       = (int) $project->user_id === $user_id;
?>
<div class="zf-single-project">
	<nav class="zf-tabs" aria-label="<?php esc_attr_e( 'Freelance pages', 'zeko-freelance' ); ?>">
		<a class="zf-tab" href="<?php echo esc_url( zeko_freelance_page_url( 'freelance' ) ); ?>"><?php esc_html_e( 'Dashboard', 'zeko-freelance' ); ?></a>
		<a class="zf-tab" href="<?php echo esc_url( zeko_freelance_page_url( 'freelance-projects' ) ); ?>"><?php esc_html_e( 'Marketplace', 'zeko-freelance' ); ?></a>
		<a class="zf-tab" href="<?php echo esc_url( zeko_freelance_page_url( 'freelance-post-project' ) ); ?>"><?php esc_html_e( 'Post a Project', 'zeko-freelance' ); ?></a>
		<a class="zf-tab" href="<?php echo esc_url( zeko_freelance_page_url( 'freelance-portfolios' ) ); ?>"><?php esc_html_e( 'Portfolios', 'zeko-freelance' ); ?></a>
	</nav>

	<article class="zf-project-detail">
		<header class="zf-project-head">
			<h1 class="zf-project-title"><?php echo esc_html( $project->title ); ?></h1>
			<div class="zf-project-meta">
				<?php if ( $project->featured ) : ?>
					<span class="zf-badge"><?php esc_html_e( 'Featured', 'zeko-freelance' ); ?></span>
				<?php endif; ?>
				<span class="zf-status zf-status-<?php echo esc_attr( $project->status ); ?>"><?php echo esc_html( $project->status ); ?></span>
			</div>
		</header>

		<div class="zf-meta-grid">
			<span class="zf-meta-item"><strong><?php esc_html_e( 'Budget', 'zeko-freelance' ); ?>:</strong>
				<?php echo esc_html( number_format_i18n( (float) $project->budget_min, 2 ) ); ?> – <?php echo esc_html( number_format_i18n( (float) $project->budget_max, 2 ) ); ?> <?php echo esc_html( $project->currency ); ?>
			</span>
			<span class="zf-meta-item"><strong><?php esc_html_e( 'Category', 'zeko-freelance' ); ?>:</strong> <?php echo esc_html( ucfirst( $project->category ) ); ?></span>
			<?php if ( $project->location ) : ?>
				<span class="zf-meta-item"><strong><?php esc_html_e( 'Location', 'zeko-freelance' ); ?>:</strong> <?php echo esc_html( $project->location ); ?></span>
			<?php endif; ?>
			<?php if ( $project->duration ) : ?>
				<span class="zf-meta-item"><strong><?php esc_html_e( 'Duration', 'zeko-freelance' ); ?>:</strong> <?php echo esc_html( Zeko_Freelance_Ajax::durations()[ $project->duration ]['label'] ?? $project->duration ); ?></span>
			<?php endif; ?>
			<span class="zf-meta-item"><strong><?php esc_html_e( 'Posted', 'zeko-freelance' ); ?>:</strong> <?php echo esc_html( mysql2date( 'M j, Y', $project->created_at ) ); ?></span>
			<span class="zf-meta-item"><strong><?php esc_html_e( 'Views', 'zeko-freelance' ); ?>:</strong> <?php echo esc_html( number_format_i18n( (int) $project->views ) ); ?></span>
		</div>

		<div class="zf-project-description">
			<?php echo wp_kses_post( wpautop( esc_textarea( (string) $project->description ) ) ); ?>
		</div>

		<?php if ( $project_skills ) : ?>
			<div class="zf-tags">
				<?php foreach ( $project_skills as $skill ) : ?>
					<span class="zf-tag"><?php echo esc_html( $skill ); ?></span>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<div class="zf-actions">
			<?php if ( is_user_logged_in() ) : ?>
				<button
					type="button"
					class="zf-btn zf-bookmark-btn <?php echo $bookmarked ? 'zf-active' : ''; ?>"
					data-project-id="<?php echo esc_attr( (string) $project->id ); ?>"
					data-bookmarked="<?php echo $bookmarked ? '1' : '0'; ?>"
					aria-pressed="<?php echo $bookmarked ? 'true' : 'false'; ?>"
				>
					<?php echo $bookmarked ? esc_html__( 'Bookmarked', 'zeko-freelance' ) : esc_html__( 'Bookmark project', 'zeko-freelance' ); ?>
				</button>
			<?php else : ?>
				<a class="zf-btn" href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>"><?php esc_html_e( 'Log in to bookmark', 'zeko-freelance' ); ?></a>
			<?php endif; ?>

			<?php if ( $is_owner && 'open' === $project->status ) : ?>
				<a class="zf-btn zf-btn-secondary" href="<?php echo esc_url( add_query_arg( 'zf_edit', $project->id, zeko_freelance_page_url( 'freelance-post-project' ) ) ); ?>"><?php esc_html_e( 'Edit project', 'zeko-freelance' ); ?></a>
			<?php endif; ?>
		</div>

		<aside class="zf-client-card">
			<h3><?php esc_html_e( 'About the client', 'zeko-freelance' ); ?></h3>
			<p class="zf-client-name">
				<?php echo esc_html( $client ? $client->display_name : __( 'Member', 'zeko-freelance' ) ); ?>
				<?php if ( $verified ) : ?>
					<span class="zf-verified" title="<?php esc_attr_e( 'Verified member', 'zeko-freelance' ); ?>">
						<span class="dashicons dashicons-yes-alt"></span>
					</span>
				<?php endif; ?>
			</p>
			<p class="zf-client-rating">
				<?php echo $rating > 0 ? esc_html( number_format_i18n( $rating, 1 ) . ' ★' ) : esc_html__( 'No reviews yet', 'zeko-freelance' ); ?>
			</p>
			<?php if ( $is_owner && 'open' === $project->status ) : ?>
				<p class="zf-hint">
					<?php
					printf(
						/* translators: %s: expiry date */
						esc_html__( 'This listing expires on %s.', 'zeko-freelance' ),
						esc_html( $project->expires_at ? mysql2date( 'M j, Y', $project->expires_at ) : '—' )
					);
					?>
				</p>
			<?php endif; ?>
		</aside>
	</article>

	<section class="zf-bids-section">
		<?php
		$bid_order        = isset( $_GET['zf_order'] ) ? sanitize_key( wp_unslash( $_GET['zf_order'] ) ) : 'amount'; // phpcs:ignore WordPress.Security.NonceVerification
		$bids             = $db->get_bids( (int) $project->id, null, $bid_order );
		$bid_stats        = $db->bid_stats( (int) $project->id );
		$my_bid           = is_user_logged_in() ? $db->get_bid_for( (int) $project->id, $user_id ) : null;
		$verify_required  = ! empty( $settings['verify_required'] ) && ! $db->is_user_verified( $user_id );
		$bid_sort_options = array(
			'amount' => __( 'Lowest bid first', 'zeko-freelance' ),
			'newest' => __( 'Newest first', 'zeko-freelance' ),
			'rating' => __( 'Top rated first', 'zeko-freelance' ),
		);
		?>
		<h2 class="zf-section-title"><?php esc_html_e( 'Bids', 'zeko-freelance' ); ?></h2>
		<p class="zf-bid-stats">
			<?php
			printf(
				/* translators: %d: bid count */
				esc_html( _n( '%d proposal', '%d proposals', $bid_stats['count'], 'zeko-freelance' ) ),
				(int) $bid_stats['count']
			);
			?>
			<?php if ( $bid_stats['lowest'] > 0 ) : ?>
				· 
				<?php
				echo esc_html(
					sprintf(
					/* translators: %s: amount */
						__( 'lowest %s', 'zeko-freelance' ),
						number_format_i18n( $bid_stats['lowest'], 2 ) . ' ' . $project->currency
					)
				);
				?>
			<?php endif; ?>
		</p>

		<?php if ( is_user_logged_in() && ! $is_owner && 'open' === $project->status && ! $verify_required ) : ?>
			<form class="zf-form" id="zf-bid-form">
				<h3><?php echo $my_bid ? esc_html__( 'Your bid', 'zeko-freelance' ) : esc_html__( 'Place a bid', 'zeko-freelance' ); ?></h3>
				<input type="hidden" name="zf_project_id" value="<?php echo esc_attr( (string) $project->id ); ?>" />
				<div class="zf-row">
					<div class="zf-field">
						<label for="zf_amount"><?php esc_html_e( 'Your amount', 'zeko-freelance' ); ?> <span class="zf-required">*</span></label>
						<input type="number" id="zf_amount" name="zf_amount" min="0" step="0.01" value="<?php echo $my_bid ? esc_attr( (string) $my_bid->amount ) : ''; ?>" required />
						<span class="zf-hint">
						<?php
						echo esc_html(
							sprintf(
							/* translators: %1$s: budget min, %2$s: budget max, %3$s: currency */
								__( 'Budget range: %1$s – %2$s %3$s', 'zeko-freelance' ),
								number_format_i18n( (float) $project->budget_min, 2 ),
								number_format_i18n( (float) $project->budget_max, 2 ),
								$project->currency
							)
						);
						?>
						</span>
					</div>
					<div class="zf-field">
						<label for="zf_delivery_days"><?php esc_html_e( 'Delivery in (days)', 'zeko-freelance' ); ?> <span class="zf-required">*</span></label>
						<input type="number" id="zf_delivery_days" name="zf_delivery_days" min="1" max="365" value="<?php echo $my_bid ? esc_attr( (string) $my_bid->delivery_days ) : ''; ?>" required />
					</div>
				</div>
				<div class="zf-field">
					<label for="zf_proposal"><?php esc_html_e( 'Proposal', 'zeko-freelance' ); ?> <span class="zf-required">*</span></label>
					<textarea id="zf_proposal" name="zf_proposal" rows="4" minlength="20" required><?php echo $my_bid ? esc_textarea( (string) $my_bid->proposal ) : ''; ?></textarea>
					<span class="zf-hint"><?php esc_html_e( 'Explain your approach and relevant experience (at least 20 characters).', 'zeko-freelance' ); ?></span>
				</div>
				<div class="zf-form-actions">
					<button type="submit" class="zf-btn"><?php echo $my_bid ? esc_html__( 'Update bid', 'zeko-freelance' ) : esc_html__( 'Place bid', 'zeko-freelance' ); ?></button>
					<?php if ( $my_bid && 'pending' === $my_bid->status ) : ?>
						<button type="button" class="zf-btn zf-btn-secondary zf-withdraw-bid" data-bid-id="<?php echo esc_attr( (string) $my_bid->id ); ?>"><?php esc_html_e( 'Withdraw', 'zeko-freelance' ); ?></button>
					<?php endif; ?>
				</div>
				<div class="zf-notice" id="zf-bid-notice"></div>
			</form>
		<?php elseif ( is_user_logged_in() && ! $is_owner && 'open' === $project->status && $verify_required ) : ?>
			<p class="zf-hint"><?php esc_html_e( 'Only verified freelancers can bid on this project.', 'zeko-freelance' ); ?></p>
		<?php endif; ?>

		<form class="zf-filters" method="get">
			<input type="hidden" name="page_id" value="<?php echo esc_attr( (string) get_the_ID() ); ?>" />
			<input type="hidden" name="zf_pid" value="<?php echo esc_attr( (string) $project->id ); ?>" />
			<select name="zf_order">
				<?php foreach ( $bid_sort_options as $key => $label ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $bid_order, $key ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
			<button type="submit" class="zf-btn"><?php esc_html_e( 'Sort', 'zeko-freelance' ); ?></button>
		</form>

		<?php
		$visible_bids = array();
		foreach ( $bids as $bid ) {
			if ( in_array( $bid->status, array( 'withdrawn', 'rejected' ), true ) ) {
				continue;
			}
			$visible_bids[] = $bid;
		}
		?>
		<?php if ( empty( $visible_bids ) ) : ?>
			<p class="zf-empty"><?php esc_html_e( 'No bids yet. Be the first to propose.', 'zeko-freelance' ); ?></p>
		<?php else : ?>
			<ul class="zf-list">
				<?php
				foreach ( $visible_bids as $bid ) :
					$bidder = get_userdata( (int) $bid->user_id );
					?>
					<li class="zf-list-item zf-bid-item">
						<div class="zf-bid-main">
							<span class="zf-bid-amount"><?php echo esc_html( number_format_i18n( (float) $bid->amount, 2 ) . ' ' . $project->currency ); ?></span>
							<span class="zf-bid-meta">
								<?php echo esc_html( $bidder ? $bidder->display_name : __( 'Member', 'zeko-freelance' ) ); ?>
								<?php if ( (float) $bid->freelancer_rating > 0 ) : ?>
									· <?php echo esc_html( number_format_i18n( (float) $bid->freelancer_rating, 1 ) . ' ★' ); ?>
								<?php endif; ?>
								· 
								<?php
								echo esc_html(
									sprintf(
									/* translators: %d: delivery days */
										_n( '%d day', '%d days', (int) $bid->delivery_days, 'zeko-freelance' ),
										(int) $bid->delivery_days
									)
								);
								?>
							</span>
							<?php if ( $bid->proposal ) : ?>
								<p class="zf-bid-proposal"><?php echo esc_html( $bid->proposal ); ?></p>
							<?php endif; ?>
						</div>
						<span class="zf-status zf-status-<?php echo esc_attr( $bid->status ); ?>"><?php echo esc_html( $bid->status ); ?></span>
						<?php if ( $is_owner && 'open' === $project->status && 'pending' === $bid->status ) : ?>
							<button type="button" class="zf-btn zf-btn-sm zf-award-bid" data-bid-id="<?php echo esc_attr( (string) $bid->id ); ?>"><?php esc_html_e( 'Accept bid', 'zeko-freelance' ); ?></button>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</section>

	<?php
	$contract    = $db->get_contract_for_project( (int) $project->id );
	$milestones  = $contract ? $db->get_milestones( (int) $contract->id ) : array();
	$stat_totals      = $contract ? $db->milestone_totals( (int) $contract->id ) : null;
	$is_client   = $contract && (int) $contract->client_id === $user_id;
	$is_worker   = $contract && (int) $contract->freelancer_id === $user_id;
	$is_party    = $is_client || $is_worker || user_can( $user_id, 'manage_options' );
	$escrow_open = $contract && in_array( $contract->status, array( 'pending', 'active' ), true );
	?>

	<?php if ( $contract && $is_party ) : ?>
		<section class="zf-contract-section">
			<h2 class="zf-section-title"><?php esc_html_e( 'Contract & milestones', 'zeko-freelance' ); ?></h2>

			<div class="zf-contract-summary">
				<span class="zf-status zf-status-<?php echo esc_attr( $contract->status ); ?>"><?php echo esc_html( $contract->status ); ?></span>
				<span class="zf-meta-item">
					<strong><?php esc_html_e( 'Escrow', 'zeko-freelance' ); ?>:</strong>
					<?php echo esc_html( number_format_i18n( (float) $contract->escrow_amount, 2 ) . ' ' . $contract->currency ); ?>
				</span>
				<?php if ( $stat_totals ) : ?>
					<span class="zf-meta-item">
						<strong><?php esc_html_e( 'Milestones', 'zeko-freelance' ); ?>:</strong>
						<?php
						echo esc_html(
							sprintf(
							/* translators: %1$d: milestone count, %2$d: paid count */
								_n( '%1$d (%2$d paid)', '%1$d (%2$d paid)', (int) $stat_totals['count'], 'zeko-freelance' ),
								(int) $stat_totals['count'],
								(int) $stat_totals['paid_count']
							)
						);
						?>
					</span>
					<span class="zf-meta-item">
						<strong><?php esc_html_e( 'Held in escrow', 'zeko-freelance' ); ?>:</strong>
						<?php echo esc_html( number_format_i18n( (float) $stat_totals['held'], 2 ) . ' ' . $contract->currency ); ?>
					</span>
				<?php endif; ?>
			</div>

			<?php if ( $milestones ) : ?>
				<ul class="zf-list zf-milestone-list">
					<?php foreach ( $milestones as $milestone ) : ?>
						<li class="zf-list-item zf-milestone-item">
							<div class="zf-milestone-main">
								<span class="zf-milestone-title"><?php echo esc_html( $milestone->title ); ?></span>
								<span class="zf-milestone-amount"><?php echo esc_html( number_format_i18n( (float) $milestone->amount, 2 ) . ' ' . $contract->currency ); ?></span>
								<?php if ( $milestone->due_at ) : ?>
									<span class="zf-milestone-due">
									<?php
									echo esc_html(
										sprintf(
										/* translators: %s: due date */
											__( 'Due %s', 'zeko-freelance' ),
											mysql2date( 'M j, Y', $milestone->due_at )
										)
									);
									?>
									</span>
								<?php endif; ?>
								<?php if ( $milestone->description ) : ?>
									<p class="zf-milestone-description"><?php echo esc_html( $milestone->description ); ?></p>
								<?php endif; ?>
							</div>
							<span class="zf-status zf-status-<?php echo esc_attr( $milestone->status ); ?>"><?php echo esc_html( $milestone->status ); ?></span>

							<?php if ( $escrow_open && 'pending' === $milestone->status && $is_client ) : ?>
								<button type="button" class="zf-btn zf-btn-sm zf-fund-milestone" data-milestone-id="<?php echo esc_attr( (string) $milestone->id ); ?>"><?php esc_html_e( 'Fund', 'zeko-freelance' ); ?></button>
							<?php endif; ?>

							<?php if ( $escrow_open && in_array( $milestone->status, array( 'funded', 'rejected' ), true ) && $is_worker ) : ?>
								<button type="button" class="zf-btn zf-btn-sm zf-submit-milestone" data-milestone-id="<?php echo esc_attr( (string) $milestone->id ); ?>"><?php esc_html_e( 'Submit for review', 'zeko-freelance' ); ?></button>
							<?php endif; ?>

							<?php if ( $escrow_open && 'in_review' === $milestone->status && $is_client ) : ?>
								<button type="button" class="zf-btn zf-btn-sm zf-approve-milestone" data-milestone-id="<?php echo esc_attr( (string) $milestone->id ); ?>"><?php esc_html_e( 'Approve', 'zeko-freelance' ); ?></button>
								<button type="button" class="zf-btn zf-btn-sm zf-btn-secondary zf-reject-milestone" data-milestone-id="<?php echo esc_attr( (string) $milestone->id ); ?>"><?php esc_html_e( 'Request changes', 'zeko-freelance' ); ?></button>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php else : ?>
				<p class="zf-empty"><?php esc_html_e( 'No milestones yet.', 'zeko-freelance' ); ?></p>
			<?php endif; ?>

			<?php if ( $escrow_open && $is_client ) : ?>
				<form class="zf-form" id="zf-milestone-form">
					<h3><?php esc_html_e( 'Add a milestone', 'zeko-freelance' ); ?></h3>
					<input type="hidden" name="zf_contract_id" value="<?php echo esc_attr( (string) $contract->id ); ?>" />
					<div class="zf-row">
						<div class="zf-field">
							<label for="zf_milestone_title"><?php esc_html_e( 'Milestone title', 'zeko-freelance' ); ?> <span class="zf-required">*</span></label>
							<input type="text" id="zf_milestone_title" name="zf_milestone_title" maxlength="200" required />
						</div>
						<div class="zf-field">
							<label for="zf_milestone_amount"><?php esc_html_e( 'Amount', 'zeko-freelance' ); ?> <span class="zf-required">*</span></label>
							<input type="number" id="zf_milestone_amount" name="zf_milestone_amount" min="0.01" step="0.01" required />
							<?php if ( $stat_totals ) : ?>
								<span class="zf-hint">
									<?php
									echo esc_html(
										sprintf(
										/* translators: %s: remaining amount */
											__( 'Remaining escrow available: %s', 'zeko-freelance' ),
											number_format_i18n( (float) $contract->escrow_amount - (float) $stat_totals['total'], 2 ) . ' ' . $contract->currency
										)
									);
									?>
								</span>
							<?php endif; ?>
						</div>
					</div>
					<div class="zf-field">
						<label for="zf_milestone_due"><?php esc_html_e( 'Due date (optional)', 'zeko-freelance' ); ?></label>
						<input type="datetime-local" id="zf_milestone_due" name="zf_milestone_due" />
					</div>
					<div class="zf-field">
						<label for="zf_milestone_description"><?php esc_html_e( 'Description (optional)', 'zeko-freelance' ); ?></label>
						<textarea id="zf_milestone_description" name="zf_milestone_description" rows="3" maxlength="2000"></textarea>
					</div>
					<div class="zf-form-actions">
						<button type="submit" class="zf-btn"><?php esc_html_e( 'Add milestone', 'zeko-freelance' ); ?></button>
					</div>
					<div class="zf-notice" id="zf-milestone-notice"></div>
				</form>
			<?php endif; ?>

			<div class="zf-contract-actions">
				<?php if ( $escrow_open && ! in_array( $contract->status, array( 'completed', 'cancelled' ), true ) ) : ?>
					<button type="button" class="zf-btn zf-btn-secondary zf-cancel-contract" data-contract-id="<?php echo esc_attr( (string) $contract->id ); ?>"><?php esc_html_e( 'Cancel contract', 'zeko-freelance' ); ?></button>
				<?php endif; ?>

				<?php if ( $escrow_open ) : ?>
					<button type="button" class="zf-btn zf-btn-secondary zf-open-dispute" data-contract-id="<?php echo esc_attr( (string) $contract->id ); ?>"><?php esc_html_e( 'Open a dispute', 'zeko-freelance' ); ?></button>
				<?php endif; ?>
			</div>

			<form class="zf-form zf-dispute-form" id="zf-dispute-form" hidden>
				<h3><?php esc_html_e( 'Open a dispute', 'zeko-freelance' ); ?></h3>
				<input type="hidden" name="zf_contract_id" value="<?php echo esc_attr( (string) $contract->id ); ?>" />
				<div class="zf-field">
					<label for="zf_dispute_subject"><?php esc_html_e( 'Subject', 'zeko-freelance' ); ?> <span class="zf-required">*</span></label>
					<input type="text" id="zf_dispute_subject" name="zf_dispute_subject" maxlength="200" required />
				</div>
				<div class="zf-field">
					<label for="zf_dispute_description"><?php esc_html_e( 'Details', 'zeko-freelance' ); ?> <span class="zf-required">*</span></label>
					<textarea id="zf_dispute_description" name="zf_dispute_description" rows="4" minlength="10" maxlength="3000" required></textarea>
				</div>
				<div class="zf-form-actions">
					<button type="submit" class="zf-btn"><?php esc_html_e( 'Open dispute', 'zeko-freelance' ); ?></button>
				</div>
				<div class="zf-notice" id="zf-dispute-notice"></div>
			</form>

			<?php
			$review_target_id = $is_client ? (int) $contract->freelancer_id : (int) $contract->client_id;
			$my_review        = $db->get_review_for_contract( (int) $contract->id, $user_id );
			$review_target    = $review_target_id ? get_userdata( $review_target_id ) : null;
			?>
			<?php if ( 'completed' === $contract->status && ( $is_client || $is_worker ) && $review_target ) : ?>
				<section class="zf-review-form-section">
					<?php if ( $my_review ) : ?>
						<h3 class="zf-section-title"><?php esc_html_e( 'Your review', 'zeko-freelance' ); ?></h3>
						<p class="zf-review-rating"><?php echo esc_html( str_repeat( '★', (int) $my_review->rating ) ); ?></p>
						<?php if ( $my_review->comment ) : ?>
							<p class="zf-review-comment"><?php echo esc_html( $my_review->comment ); ?></p>
						<?php endif; ?>
					<?php else : ?>
						<form class="zf-form" id="zf-review-form">
							<h3 class="zf-section-title"><?php esc_html_e( 'Review this contract', 'zeko-freelance' ); ?></h3>
							<input type="hidden" name="zf_contract_id" value="<?php echo esc_attr( (string) $contract->id ); ?>" />
							<div class="zf-field">
								<label for="zf_review_rating"><?php esc_html_e( 'Rating', 'zeko-freelance' ); ?> <span class="zf-required">*</span></label>
								<select id="zf_review_rating" name="zf_review_rating" required>
									<option value=""><?php esc_html_e( '— Select —', 'zeko-freelance' ); ?></option>
									<option value="5">★★★★★</option>
									<option value="4">★★★★</option>
									<option value="3">★★★</option>
									<option value="2">★★</option>
									<option value="1">★</option>
								</select>
							</div>
							<div class="zf-field">
								<label for="zf_review_comment"><?php esc_html_e( 'Comment (optional)', 'zeko-freelance' ); ?></label>
								<textarea id="zf_review_comment" name="zf_review_comment" rows="4" maxlength="1000" placeholder="
								<?php
								echo esc_attr(
									sprintf(
									/* translators: %s: reviewee display name */
										__( 'Share feedback about working with %s…', 'zeko-freelance' ),
										$review_target->display_name
									)
								);
								?>
								"></textarea>
							</div>
							<div class="zf-form-actions">
								<button type="submit" class="zf-btn"><?php esc_html_e( 'Submit review', 'zeko-freelance' ); ?></button>
							</div>
							<div class="zf-notice" id="zf-review-notice"></div>
						</form>
					<?php endif; ?>
				</section>
			<?php endif; ?>
		</section>
	<?php endif; ?>

	<?php if ( $related ) : ?>
		<section class="zf-related">
			<h2 class="zf-section-title"><?php esc_html_e( 'Similar projects', 'zeko-freelance' ); ?></h2>
			<div class="zf-grid">
				<?php foreach ( $related as $rel ) : ?>
					<article class="zf-project-card">
						<?php if ( $rel->featured ) : ?>
							<span class="zf-badge"><?php esc_html_e( 'Featured', 'zeko-freelance' ); ?></span>
						<?php endif; ?>
						<h3 class="zf-project-title">
							<a href="<?php echo esc_url( add_query_arg( 'zf_pid', $rel->id, zeko_freelance_page_url( 'freelance-project' ) ) ); ?>"><?php echo esc_html( $rel->title ); ?></a>
						</h3>
						<p class="zf-project-excerpt"><?php echo esc_html( wp_trim_words( (string) $rel->description, 20 ) ); ?></p>
						<div class="zf-project-meta">
							<span class="zf-price">
								<?php echo esc_html( number_format_i18n( (float) $rel->budget_min, 0 ) ); ?> – <?php echo esc_html( number_format_i18n( (float) $rel->budget_max, 0 ) ); ?> <?php echo esc_html( $rel->currency ); ?>
							</span>
						</div>
					</article>
				<?php endforeach; ?>
			</div>
		</section>
	<?php endif; ?>
</div>
