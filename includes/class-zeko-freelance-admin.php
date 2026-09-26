<?php
/**
 * Zeko Freelance admin page: platform settings, verification queue,
 * skills overview.
 *
 * @package Zeko_Freelance
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Class Zeko_Freelance_Admin. */
class Zeko_Freelance_Admin {

	/**
	 * Db.
	 *
	 * @var Zeko_Freelance_DB Db.
	 */
	private Zeko_Freelance_DB $db;

	/**
	 * Construct.
	 *
	 * @param Zeko_Freelance_DB $db Db.
	 */
	public function __construct( Zeko_Freelance_DB $db ) {
		$this->db = $db;

		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );

		add_action( 'admin_post_zeko_freelance_verification_action', array( $this, 'handle_verification_action' ) );
		add_action( 'admin_post_zeko_freelance_project_action', array( $this, 'handle_project_action' ) );
		add_action( 'admin_post_zeko_freelance_bid_action', array( $this, 'handle_bid_action' ) );
		add_action( 'admin_post_zeko_freelance_dispute_action', array( $this, 'handle_dispute_action' ) );
		add_action( 'admin_post_zeko_freelance_skill_action', array( $this, 'handle_skill_action' ) );
		add_action( 'admin_post_zeko_freelance_demo_action', array( $this, 'handle_demo_action' ) );
	}

	/**
	 * Menu.
	 */
	public function register_menu(): void {
		add_menu_page(
			__( 'Zeko Freelance', 'zeko-freelance' ),
			__( 'Freelance', 'zeko-freelance' ),
			'manage_options',
			'zeko-freelance',
			array( $this, 'render_settings' ),
			'dashicons-briefcase',
			58
		);

		add_submenu_page(
			'zeko-freelance',
			__( 'Project Moderation', 'zeko-freelance' ),
			__( 'Projects', 'zeko-freelance' ),
			'manage_options',
			'zeko-freelance-projects',
			array( $this, 'render_projects' )
		);

		add_submenu_page(
			'zeko-freelance',
			__( 'Freelancer Verifications', 'zeko-freelance' ),
			__( 'Verifications', 'zeko-freelance' ),
			'manage_options',
			'zeko-freelance-verifications',
			array( $this, 'render_verifications' )
		);

		add_submenu_page(
			'zeko-freelance',
			__( 'Bid Management', 'zeko-freelance' ),
			__( 'Bids', 'zeko-freelance' ),
			'manage_options',
			'zeko-freelance-bids',
			array( $this, 'render_bids' )
		);

		add_submenu_page(
			'zeko-freelance',
			__( 'Contract Management', 'zeko-freelance' ),
			__( 'Contracts', 'zeko-freelance' ),
			'manage_options',
			'zeko-freelance-contracts',
			array( $this, 'render_contracts' )
		);

		add_submenu_page(
			'zeko-freelance',
			__( 'Dispute Resolution', 'zeko-freelance' ),
			__( 'Disputes', 'zeko-freelance' ),
			'manage_options',
			'zeko-freelance-disputes',
			array( $this, 'render_disputes' )
		);

		add_submenu_page(
			'zeko-freelance',
			__( 'Skill Taxonomy', 'zeko-freelance' ),
			__( 'Skills', 'zeko-freelance' ),
			'manage_options',
			'zeko-freelance-skills',
			array( $this, 'render_skills' )
		);
	}

	/**
	 * Settings.
	 */
	public function register_settings(): void {
		register_setting( 'zeko_freelance_settings_group', 'zeko_freelance_settings', array( $this, 'sanitize_settings' ) );
	}

	/**
	 * Sanitize settings.
	 *
	 * @param mixed $input Input.
	 */
	public function sanitize_settings( $input ): array {
		$defaults = zeko_freelance_get_settings();
		$input    = is_array( $input ) ? $input : array();

		return array(
			'platform_fee'          => isset( $input['platform_fee'] ) ? min( 90, max( 0, absint( $input['platform_fee'] ) ) ) : $defaults['platform_fee'],
			'currency'              => isset( $input['currency'] ) ? strtoupper( substr( sanitize_text_field( $input['currency'] ), 0, 8 ) ) : $defaults['currency'],
			'max_active_projects'   => isset( $input['max_active_projects'] ) ? max( 1, absint( $input['max_active_projects'] ) ) : $defaults['max_active_projects'],
			'milestone_payment'     => empty( $input['milestone_payment'] ) ? 0 : 1,
			'verify_required'       => empty( $input['verify_required'] ) ? 0 : 1,
			'require_approval'      => empty( $input['require_approval'] ) ? 0 : 1,
			'review_sla_days'       => isset( $input['review_sla_days'] ) ? max( 1, absint( $input['review_sla_days'] ) ) : $defaults['review_sla_days'],
			'review_reminder_hours' => isset( $input['review_reminder_hours'] ) ? max( 1, absint( $input['review_reminder_hours'] ) ) : $defaults['review_reminder_hours'],
			'submit_sla_days'       => isset( $input['submit_sla_days'] ) ? max( 1, absint( $input['submit_sla_days'] ) ) : $defaults['submit_sla_days'],
			'submit_reminder_hours' => isset( $input['submit_reminder_hours'] ) ? max( 1, absint( $input['submit_reminder_hours'] ) ) : $defaults['submit_reminder_hours'],
			'auto_approve'          => empty( $input['auto_approve'] ) ? 0 : 1,
			'auto_dispute'          => empty( $input['auto_dispute'] ) ? 0 : 1,
		);
	}

	/**
	 * Render settings.
	 */
	public function render_settings(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings = zeko_freelance_get_settings();
		$stats    = $this->db->admin_overview_stats();
		$skills   = $this->db->get_skills();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Zeko Freelance', 'zeko-freelance' ); ?></h1>

			<h2><?php esc_html_e( 'Marketplace overview', 'zeko-freelance' ); ?></h2>
			<table class="widefat striped" style="max-width:560px;">
				<tbody>
					<tr>
						<th><?php esc_html_e( 'Open projects', 'zeko-freelance' ); ?></th>
						<td><?php echo esc_html( number_format_i18n( $stats['open_projects'] ) ); ?></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Pending bids', 'zeko-freelance' ); ?></th>
						<td><?php echo esc_html( number_format_i18n( $stats['pending_bids'] ) ); ?></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Active contracts', 'zeko-freelance' ); ?></th>
						<td><?php echo esc_html( number_format_i18n( $stats['active_contracts'] ) ); ?></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Open disputes', 'zeko-freelance' ); ?></th>
						<td><?php echo esc_html( number_format_i18n( $stats['open_disputes'] ) ); ?></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Pending verifications', 'zeko-freelance' ); ?></th>
						<td><?php echo esc_html( number_format_i18n( $stats['pending_verifications'] ) ); ?></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Milestones paid out', 'zeko-freelance' ); ?></th>
						<td><?php echo esc_html( sprintf( '%s %s', number_format_i18n( $stats['paid_milestones'], 2 ), $settings['currency'] ) ); ?></td>
					</tr>
				</tbody>
			</table>

			<h2><?php esc_html_e( 'Settings', 'zeko-freelance' ); ?></h2>
			<form method="post" action="options.php">
				<?php settings_fields( 'zeko_freelance_settings_group' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="zeko-freelance-platform-fee"><?php esc_html_e( 'Platform fee (%)', 'zeko-freelance' ); ?></label></th>
						<td>
							<input name="zeko_freelance_settings[platform_fee]" type="number" id="zeko-freelance-platform-fee" value="<?php echo esc_attr( (string) $settings['platform_fee'] ); ?>" min="0" max="90" class="small-text" />
							<p class="description"><?php esc_html_e( 'Percentage withheld from each completed milestone.', 'zeko-freelance' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="zeko-freelance-currency"><?php esc_html_e( 'Default currency', 'zeko-freelance' ); ?></label></th>
						<td>
							<input name="zeko_freelance_settings[currency]" type="text" id="zeko-freelance-currency" value="<?php echo esc_attr( $settings['currency'] ); ?>" maxlength="8" class="small-text" />
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="zeko-freelance-max-projects"><?php esc_html_e( 'Max active projects per client', 'zeko-freelance' ); ?></label></th>
						<td>
							<input name="zeko_freelance_settings[max_active_projects]" type="number" id="zeko-freelance-max-projects" value="<?php echo esc_attr( (string) $settings['max_active_projects'] ); ?>" min="1" class="small-text" />
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Milestone-based payments', 'zeko-freelance' ); ?></th>
						<td>
							<label><input type="checkbox" name="zeko_freelance_settings[milestone_payment]" value="1" <?php checked( 1, $settings['milestone_payment'] ); ?> /> <?php esc_html_e( 'Require milestone-based payment plans on contracts', 'zeko-freelance' ); ?></label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Verification requirement', 'zeko-freelance' ); ?></th>
						<td>
							<label><input type="checkbox" name="zeko_freelance_settings[verify_required]" value="1" <?php checked( 1, $settings['verify_required'] ); ?> /> <?php esc_html_e( 'Require freelancers to be verified before bidding', 'zeko-freelance' ); ?></label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Moderation', 'zeko-freelance' ); ?></th>
						<td>
							<label><input type="checkbox" name="zeko_freelance_settings[require_approval]" value="1" <?php checked( 1, $settings['require_approval'] ); ?> /> <?php esc_html_e( 'Require admin approval before new projects are published', 'zeko-freelance' ); ?></label>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>

			<h2><?php esc_html_e( 'Milestone SLA timers', 'zeko-freelance' ); ?></h2>
			<p class="description">
				<?php esc_html_e( 'Submitted milestones are auto-approved (and escrow released) when the client does not respond within the review window. Funded milestones that are never submitted within the submission window are escalated into a dispute.', 'zeko-freelance' ); ?>
			</p>
			<form method="post" action="options.php">
				<?php settings_fields( 'zeko_freelance_settings_group' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="zeko-freelance-review-sla"><?php esc_html_e( 'Review window (days)', 'zeko-freelance' ); ?></label></th>
						<td>
							<input name="zeko_freelance_settings[review_sla_days]" type="number" id="zeko-freelance-review-sla" value="<?php echo esc_attr( (string) $settings['review_sla_days'] ); ?>" min="1" class="small-text" />
							<p class="description"><?php esc_html_e( 'How long a client has to approve or reject a submitted milestone before it is auto-approved.', 'zeko-freelance' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="zeko-freelance-review-reminder"><?php esc_html_e( 'Review reminder (hours before deadline)', 'zeko-freelance' ); ?></label></th>
						<td>
							<input name="zeko_freelance_settings[review_reminder_hours]" type="number" id="zeko-freelance-review-reminder" value="<?php echo esc_attr( (string) $settings['review_reminder_hours'] ); ?>" min="1" class="small-text" />
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="zeko-freelance-submit-sla"><?php esc_html_e( 'Submission window (days)', 'zeko-freelance' ); ?></label></th>
						<td>
							<input name="zeko_freelance_settings[submit_sla_days]" type="number" id="zeko-freelance-submit-sla" value="<?php echo esc_attr( (string) $settings['submit_sla_days'] ); ?>" min="1" class="small-text" />
							<p class="description"><?php esc_html_e( 'How long a funded milestone may sit unsubmitted before it is escalated into a dispute.', 'zeko-freelance' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="zeko-freelance-submit-reminder"><?php esc_html_e( 'Submission reminder (hours before deadline)', 'zeko-freelance' ); ?></label></th>
						<td>
							<input name="zeko_freelance_settings[submit_reminder_hours]" type="number" id="zeko-freelance-submit-reminder" value="<?php echo esc_attr( (string) $settings['submit_reminder_hours'] ); ?>" min="1" class="small-text" />
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Auto-approve overdue reviews', 'zeko-freelance' ); ?></th>
						<td>
							<label><input type="checkbox" name="zeko_freelance_settings[auto_approve]" value="1" <?php checked( 1, $settings['auto_approve'] ); ?> /> <?php esc_html_e( 'Release escrow for milestones left in review past their deadline', 'zeko-freelance' ); ?></label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Escalate stale milestones', 'zeko-freelance' ); ?></th>
						<td>
							<label><input type="checkbox" name="zeko_freelance_settings[auto_dispute]" value="1" <?php checked( 1, $settings['auto_dispute'] ); ?> /> <?php esc_html_e( 'Open a dispute for funded milestones never submitted within the window', 'zeko-freelance' ); ?></label>
						</td>
					</tr>
				</table>
				<?php submit_button( __( 'Save SLA timers', 'zeko-freelance' ) ); ?>
			</form>

			<h2><?php esc_html_e( 'Skill taxonomy', 'zeko-freelance' ); ?></h2>
			<p>
				<?php
				printf(
					/* translators: %d: skill count */
					esc_html__( '%d skills in the taxonomy.', 'zeko-freelance' ),
					esc_html( (string) count( $skills ) )
				);
				?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=zeko-freelance-skills' ) ); ?>"><?php esc_html_e( 'Manage skills', 'zeko-freelance' ); ?></a>
			</p>

			<h2><?php esc_html_e( 'Demo data', 'zeko-freelance' ); ?></h2>
			<p class="description">
				<?php esc_html_e( 'Generate realistic demo content (clients, freelancers, projects, bids, contracts, milestones, a dispute, portfolios, verifications and reviews) to test the marketplace, or wipe it to start fresh.', 'zeko-freelance' ); ?>
			</p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
				<input type="hidden" name="action" value="zeko_freelance_demo_action" />
				<input type="hidden" name="demo_action" value="seed" />
				<?php wp_nonce_field( 'zeko_freelance_demo_action' ); ?>
				<button class="button button-primary"><?php esc_html_e( 'Generate demo data', 'zeko-freelance' ); ?></button>
			</form>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;" onsubmit="return confirm('<?php esc_attr_e( 'Delete all demo users and freelance demo data? Continue?', 'zeko-freelance' ); ?>');">
				<input type="hidden" name="action" value="zeko_freelance_demo_action" />
				<input type="hidden" name="demo_action" value="clear" />
				<?php wp_nonce_field( 'zeko_freelance_demo_action' ); ?>
				<button class="button" style="color:#b32d2e;border-color:#b32d2e;"><?php esc_html_e( 'Clear demo data', 'zeko-freelance' ); ?></button>
			</form>
		</div>
			<?php
	}

	/**
	 * Render projects.
	 */
	public function render_projects(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$status = isset( $_GET['zf_status'] ) ? sanitize_key( wp_unslash( $_GET['zf_status'] ) ) : 'pending'; // phpcs:ignore WordPress.Security.NonceVerification
		if ( ! in_array( $status, array( 'pending', 'open', 'awarded', 'expired', 'completed', 'cancelled' ), true ) ) {
			$status = 'pending';
		}

		$projects = $this->db->get_projects(
			array(
				'status'   => $status,
				'per_page' => 200,
				'orderby'  => 'created_at DESC',
			)
		);
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Project Moderation', 'zeko-freelance' ); ?></h1>

			<nav class="nav-tab-wrapper" style="margin:12px 0;">
				<?php
				foreach ( array(
					'pending'   => __( 'Pending', 'zeko-freelance' ),
					'open'      => __( 'Open', 'zeko-freelance' ),
					'awarded'   => __( 'Awarded', 'zeko-freelance' ),
					'expired'   => __( 'Expired', 'zeko-freelance' ),
					'completed' => __( 'Completed', 'zeko-freelance' ),
					'cancelled' => __( 'Cancelled', 'zeko-freelance' ),
				) as $key => $label ) :
					?>
					<a class="nav-tab <?php echo $status === $key ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'zf_status', $key, admin_url( 'admin.php?page=zeko-freelance-projects' ) ) ); ?>"><?php echo esc_html( $label ); ?></a>
				<?php endforeach; ?>
			</nav>

			<table class="widefat striped" style="margin-top:12px;">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Project', 'zeko-freelance' ); ?></th>
						<th><?php esc_html_e( 'Client', 'zeko-freelance' ); ?></th>
						<th><?php esc_html_e( 'Budget', 'zeko-freelance' ); ?></th>
						<th><?php esc_html_e( 'Category', 'zeko-freelance' ); ?></th>
						<th><?php esc_html_e( 'Created', 'zeko-freelance' ); ?></th>
						<th><?php esc_html_e( 'Status', 'zeko-freelance' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'zeko-freelance' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $projects ) ) : ?>
						<tr><td colspan="7"><?php esc_html_e( 'No projects in this state.', 'zeko-freelance' ); ?></td></tr>
					<?php else : ?>
						<?php foreach ( $projects as $project ) : ?>
							<tr>
								<td>
									<strong><?php echo esc_html( $project->title ); ?></strong>
									<?php if ( $project->featured ) : ?>
										<span class="dashicons dashicons-star-filled" style="color:#f59e0b;margin-left:4px;" title="<?php esc_attr_e( 'Featured', 'zeko-freelance' ); ?>"></span>
									<?php endif; ?>
								</td>
								<td><?php echo esc_html( get_the_author_meta( 'display_name', (int) $project->user_id ) ); ?></td>
								<td><?php echo esc_html( number_format_i18n( (float) $project->budget_min, 2 ) ); ?> – <?php echo esc_html( number_format_i18n( (float) $project->budget_max, 2 ) ); ?></td>
								<td><?php echo esc_html( $project->category ); ?></td>
								<td><?php echo esc_html( mysql2date( 'M j, Y', $project->created_at ) ); ?></td>
								<td><?php echo esc_html( $project->status ); ?></td>
								<td>
									<?php if ( 'pending' === $project->status ) : ?>
										<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
											<input type="hidden" name="action" value="zeko_freelance_project_action" />
											<input type="hidden" name="project_id" value="<?php echo esc_attr( (string) $project->id ); ?>" />
											<input type="hidden" name="project_status" value="open" />
											<?php wp_nonce_field( 'zeko_freelance_project_action' ); ?>
											<button class="button button-primary"><?php esc_html_e( 'Approve', 'zeko-freelance' ); ?></button>
										</form>
										<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
											<input type="hidden" name="action" value="zeko_freelance_project_action" />
											<input type="hidden" name="project_id" value="<?php echo esc_attr( (string) $project->id ); ?>" />
											<input type="hidden" name="project_status" value="rejected" />
											<?php wp_nonce_field( 'zeko_freelance_project_action' ); ?>
											<button class="button"><?php esc_html_e( 'Reject', 'zeko-freelance' ); ?></button>
										</form>
									<?php endif; ?>

									<?php if ( in_array( $project->status, array( 'open', 'pending' ), true ) ) : ?>
										<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
											<input type="hidden" name="action" value="zeko_freelance_project_action" />
											<input type="hidden" name="project_id" value="<?php echo esc_attr( (string) $project->id ); ?>" />
											<input type="hidden" name="project_status" value="<?php echo $project->featured ? 'unfeature' : 'feature'; ?>" />
											<?php wp_nonce_field( 'zeko_freelance_project_action' ); ?>
											<button class="button"><?php echo $project->featured ? esc_html__( 'Unfeature', 'zeko-freelance' ) : esc_html__( 'Feature', 'zeko-freelance' ); ?></button>
										</form>
									<?php endif; ?>

									<?php if ( 'open' === $project->status ) : ?>
										<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
											<input type="hidden" name="action" value="zeko_freelance_project_action" />
											<input type="hidden" name="project_id" value="<?php echo esc_attr( (string) $project->id ); ?>" />
											<input type="hidden" name="project_status" value="expired" />
											<?php wp_nonce_field( 'zeko_freelance_project_action' ); ?>
											<button class="button"><?php esc_html_e( 'Expire', 'zeko-freelance' ); ?></button>
										</form>
									<?php endif; ?>

									<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;" onsubmit="return confirm('<?php esc_attr_e( 'Delete this project permanently?', 'zeko-freelance' ); ?>');">
										<input type="hidden" name="action" value="zeko_freelance_project_action" />
										<input type="hidden" name="project_id" value="<?php echo esc_attr( (string) $project->id ); ?>" />
										<input type="hidden" name="project_status" value="delete" />
										<?php wp_nonce_field( 'zeko_freelance_project_action' ); ?>
										<button class="button button-link-delete"><?php esc_html_e( 'Delete', 'zeko-freelance' ); ?></button>
									</form>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
			<?php
	}

	/**
	 * Handle project action.
	 */
	public function handle_project_action(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized', 'zeko-freelance' ) );
		}

		check_admin_referer( 'zeko_freelance_project_action' );

		$project_id = isset( $_POST['project_id'] ) ? absint( $_POST['project_id'] ) : 0;
		$status     = isset( $_POST['project_status'] ) ? sanitize_key( wp_unslash( $_POST['project_status'] ) ) : '';
		$project    = $this->db->get_project( $project_id );

		$redirect = admin_url( 'admin.php?page=zeko-freelance-projects' );

		if ( ! $project ) {
			$this->redirect( $redirect );
			return;
		}

		switch ( $status ) {
			case 'open':
				if ( 'pending' === $project->status ) {
					$this->db->update_project( $project_id, array( 'status' => 'open' ) );
					do_action( 'zeko_freelance_project_created', (int) $project_id, (int) $project->user_id );
					zeko_freelance()->get_emails()->notify_owner_published( $project_id );
				}
				break;
			case 'rejected':
				$this->db->update_project( $project_id, array( 'status' => 'rejected' ) );
				break;
			case 'expired':
				$this->db->update_project( $project_id, array( 'status' => 'expired' ) );
				break;
			case 'feature':
				$this->db->update_project( $project_id, array( 'featured' => 1 ) );
				break;
			case 'unfeature':
				$this->db->update_project( $project_id, array( 'featured' => 0 ) );
				break;
			case 'delete':
				$this->db->delete_project( $project_id );
				break;
		}

		$this->redirect( $redirect );
	}

	/**
	 * Redirect after an admin-post action unless tests disable the exit via
	 * the `zeko_freelance_admin_exit` filter (mirrors the AJAX exit seam).
	 *
	 * @param string $url Url.
	 * @param bool   $safe Safe.
	 */
	private function redirect( string $url, bool $safe = true ): void {
		if ( ! apply_filters( 'zeko_freelance_admin_exit', true ) ) {
			return;
		}
		if ( $safe ) {
			wp_safe_redirect( $url );
		} else {
			wp_redirect( $url ); // phpcs:ignore WordPress.Security.SafeRedirect
		}
		exit;
	}

	/**
	 * Render verifications.
	 */
	public function render_verifications(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$verifications = $this->db->get_verifications();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Freelancer Verifications', 'zeko-freelance' ); ?></h1>

			<table class="widefat striped" style="margin-top:12px;">
				<thead>
					<tr>
						<th><?php esc_html_e( 'User', 'zeko-freelance' ); ?></th>
						<th><?php esc_html_e( 'Type', 'zeko-freelance' ); ?></th>
						<th><?php esc_html_e( 'Status', 'zeko-freelance' ); ?></th>
						<th><?php esc_html_e( 'Submitted', 'zeko-freelance' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'zeko-freelance' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $verifications ) ) : ?>
						<tr><td colspan="5"><?php esc_html_e( 'No verification requests yet.', 'zeko-freelance' ); ?></td></tr>
					<?php else : ?>
						<?php foreach ( $verifications as $v ) : ?>
							<tr>
								<td><?php echo esc_html( get_the_author_meta( 'display_name', $v->user_id ) ); ?></td>
								<td><?php echo esc_html( $v->type ); ?></td>
								<td><?php echo esc_html( $v->status ); ?></td>
								<td><?php echo esc_html( $v->submitted_at ); ?></td>
								<td>
									<?php if ( 'pending' === $v->status ) : ?>
										<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
											<input type="hidden" name="action" value="zeko_freelance_verification_action" />
											<input type="hidden" name="verification_id" value="<?php echo esc_attr( (string) $v->id ); ?>" />
											<input type="hidden" name="verification_status" value="approved" />
											<?php wp_nonce_field( 'zeko_freelance_verification_action' ); ?>
											<button class="button button-primary"><?php esc_html_e( 'Approve', 'zeko-freelance' ); ?></button>
										</form>
										<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
											<input type="hidden" name="action" value="zeko_freelance_verification_action" />
											<input type="hidden" name="verification_id" value="<?php echo esc_attr( (string) $v->id ); ?>" />
											<input type="hidden" name="verification_status" value="rejected" />
											<?php wp_nonce_field( 'zeko_freelance_verification_action' ); ?>
											<button class="button"><?php esc_html_e( 'Reject', 'zeko-freelance' ); ?></button>
										</form>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Handle verification action.
	 */
	public function handle_verification_action(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized', 'zeko-freelance' ) );
		}

		check_admin_referer( 'zeko_freelance_verification_action' );

		$id     = isset( $_POST['verification_id'] ) ? absint( $_POST['verification_id'] ) : 0;
		$status = isset( $_POST['verification_status'] ) ? sanitize_key( $_POST['verification_status'] ) : '';

		if ( ! in_array( $status, array( 'approved', 'rejected' ), true ) ) {
			$this->redirect( wp_get_referer() ? wp_get_referer() : admin_url( 'admin.php?page=zeko-freelance-verifications' ), false );
			return;
		}

		$verification = $this->db->get_verification( $id );
		if ( $verification ) {
			$this->db->update_verification(
				$id,
				array(
					'status'      => $status,
					'reviewed_at' => current_time( 'mysql' ),
					'reviewed_by' => get_current_user_id(),
				)
			);
		}

		$this->redirect( admin_url( 'admin.php?page=zeko-freelance-verifications' ) );
	}

	/**
	 * Render bids.
	 */
	public function render_bids(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$status = isset( $_GET['zf_status'] ) ? sanitize_key( wp_unslash( $_GET['zf_status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		if ( ! in_array( $status, array( 'pending', 'accepted', 'rejected', 'withdrawn' ), true ) ) {
			$status = '';
		}

		$bids = $this->db->get_all_bids( '' === $status ? null : $status, 300 );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Bid Management', 'zeko-freelance' ); ?></h1>

			<nav class="nav-tab-wrapper" style="margin:12px 0;">
				<?php
				foreach ( array(
					''          => __( 'All', 'zeko-freelance' ),
					'pending'   => __( 'Pending', 'zeko-freelance' ),
					'accepted'  => __( 'Accepted', 'zeko-freelance' ),
					'rejected'  => __( 'Rejected', 'zeko-freelance' ),
					'withdrawn' => __( 'Withdrawn', 'zeko-freelance' ),
				) as $key => $label ) :
					?>
					<a class="nav-tab <?php echo $status === $key ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'zf_status', $key, admin_url( 'admin.php?page=zeko-freelance-bids' ) ) ); ?>"><?php echo esc_html( $label ); ?></a>
				<?php endforeach; ?>
			</nav>

			<table class="widefat striped" style="margin-top:12px;">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Project', 'zeko-freelance' ); ?></th>
						<th><?php esc_html_e( 'Freelancer', 'zeko-freelance' ); ?></th>
						<th><?php esc_html_e( 'Amount', 'zeko-freelance' ); ?></th>
						<th><?php esc_html_e( 'Delivery', 'zeko-freelance' ); ?></th>
						<th><?php esc_html_e( 'Status', 'zeko-freelance' ); ?></th>
						<th><?php esc_html_e( 'Submitted', 'zeko-freelance' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'zeko-freelance' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $bids ) ) : ?>
						<tr><td colspan="7"><?php esc_html_e( 'No bids in this state.', 'zeko-freelance' ); ?></td></tr>
					<?php else : ?>
						<?php
						foreach ( $bids as $bid ) :
							$bid_project = $this->db->get_project( (int) $bid->project_id );
							?>
							<tr>
								<td>
									<?php if ( $bid_project ) : ?>
										<strong><?php echo esc_html( $bid_project->title ); ?></strong>
									<?php else : ?>
										#<?php echo esc_html( (string) $bid->project_id ); ?>
									<?php endif; ?>
								</td>
								<td><?php echo esc_html( get_the_author_meta( 'display_name', (int) $bid->user_id ) ); ?></td>
								<td><?php echo esc_html( number_format_i18n( (float) $bid->amount, 2 ) ); ?></td>
								<td><?php /* translators: %d: delivery time in days */ echo esc_html( sprintf( _n( '%d day', '%d days', (int) $bid->delivery_days, 'zeko-freelance' ), (int) $bid->delivery_days ) ); ?></td>
								<td><?php echo esc_html( $bid->status ); ?></td>
								<td><?php echo esc_html( mysql2date( 'M j, Y', $bid->created_at ) ); ?></td>
								<td>
									<?php if ( 'pending' === $bid->status ) : ?>
										<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
											<input type="hidden" name="action" value="zeko_freelance_bid_action" />
											<input type="hidden" name="bid_id" value="<?php echo esc_attr( (string) $bid->id ); ?>" />
											<input type="hidden" name="bid_status" value="rejected" />
											<?php wp_nonce_field( 'zeko_freelance_bid_action' ); ?>
											<button class="button"><?php esc_html_e( 'Reject', 'zeko-freelance' ); ?></button>
										</form>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
			<?php
	}

	/**
	 * Handle bid action.
	 */
	public function handle_bid_action(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized', 'zeko-freelance' ) );
		}

		check_admin_referer( 'zeko_freelance_bid_action' );

		$bid_id = isset( $_POST['bid_id'] ) ? absint( $_POST['bid_id'] ) : 0;
		$status = isset( $_POST['bid_status'] ) ? sanitize_key( wp_unslash( $_POST['bid_status'] ) ) : '';

		if ( 'rejected' === $status ) {
			$this->db->reject_bid( $bid_id );
		}

		$this->redirect( admin_url( 'admin.php?page=zeko-freelance-bids' ) );
	}

	/**
	 * Render contracts.
	 */
	public function render_contracts(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$status = isset( $_GET['zf_status'] ) ? sanitize_key( wp_unslash( $_GET['zf_status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		if ( ! in_array( $status, array( 'pending', 'active', 'disputed', 'completed', 'cancelled' ), true ) ) {
			$status = '';
		}

		$contracts = $this->db->get_contracts( '' === $status ? null : $status, 200 );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Contract Management', 'zeko-freelance' ); ?></h1>

			<nav class="nav-tab-wrapper" style="margin:12px 0;">
				<?php
				foreach ( array(
					''          => __( 'All', 'zeko-freelance' ),
					'pending'   => __( 'Pending', 'zeko-freelance' ),
					'active'    => __( 'Active', 'zeko-freelance' ),
					'disputed'  => __( 'Disputed', 'zeko-freelance' ),
					'completed' => __( 'Completed', 'zeko-freelance' ),
					'cancelled' => __( 'Cancelled', 'zeko-freelance' ),
				) as $key => $label ) :
					?>
					<a class="nav-tab <?php echo $status === $key ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'zf_status', $key, admin_url( 'admin.php?page=zeko-freelance-contracts' ) ) ); ?>"><?php echo esc_html( $label ); ?></a>
				<?php endforeach; ?>
			</nav>

			<table class="widefat striped" style="margin-top:12px;">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Contract', 'zeko-freelance' ); ?></th>
						<th><?php esc_html_e( 'Client', 'zeko-freelance' ); ?></th>
						<th><?php esc_html_e( 'Freelancer', 'zeko-freelance' ); ?></th>
						<th><?php esc_html_e( 'Escrow', 'zeko-freelance' ); ?></th>
						<th><?php esc_html_e( 'Milestones', 'zeko-freelance' ); ?></th>
						<th><?php esc_html_e( 'Status', 'zeko-freelance' ); ?></th>
						<th><?php esc_html_e( 'Created', 'zeko-freelance' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $contracts ) ) : ?>
						<tr><td colspan="7"><?php esc_html_e( 'No contracts in this state.', 'zeko-freelance' ); ?></td></tr>
					<?php else : ?>
						<?php
						foreach ( $contracts as $contract ) :
							$totals = $this->db->milestone_totals( (int) $contract->id );
							?>
							<tr>
								<td>
									#<?php echo esc_html( (string) $contract->id ); ?>
									<?php $c_project = $this->db->get_project( (int) $contract->project_id ); ?>
									<?php if ( $c_project ) : ?>
										<br /><small><?php echo esc_html( $c_project->title ); ?></small>
									<?php endif; ?>
								</td>
								<td><?php echo esc_html( get_the_author_meta( 'display_name', (int) $contract->client_id ) ); ?></td>
								<td><?php echo esc_html( get_the_author_meta( 'display_name', (int) $contract->freelancer_id ) ); ?></td>
								<td><?php echo esc_html( number_format_i18n( (float) $contract->escrow_amount, 2 ) ); ?></td>
								<td>
									<?php echo esc_html( (string) $totals['count'] ); ?>
									(<?php echo esc_html( (string) $totals['paid_count'] ); ?> paid)
								</td>
								<td><?php echo esc_html( $contract->status ); ?></td>
								<td><?php echo esc_html( mysql2date( 'M j, Y', $contract->created_at ) ); ?></td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
			<?php
	}

	/**
	 * Render disputes.
	 */
	public function render_disputes(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$status = isset( $_GET['zf_status'] ) ? sanitize_key( wp_unslash( $_GET['zf_status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		if ( ! in_array( $status, array( 'open', 'resolved' ), true ) ) {
			$status = '';
		}

		$disputes = $this->db->get_disputes( $status, 200 );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Dispute Resolution', 'zeko-freelance' ); ?></h1>

			<nav class="nav-tab-wrapper" style="margin:12px 0;">
				<?php
				foreach ( array(
					''         => __( 'All', 'zeko-freelance' ),
					'open'     => __( 'Open', 'zeko-freelance' ),
					'resolved' => __( 'Resolved', 'zeko-freelance' ),
				) as $key => $label ) :
					?>
					<a class="nav-tab <?php echo $status === $key ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'zf_status', $key, admin_url( 'admin.php?page=zeko-freelance-disputes' ) ) ); ?>"><?php echo esc_html( $label ); ?></a>
				<?php endforeach; ?>
			</nav>

			<table class="widefat striped" style="margin-top:12px;">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Dispute', 'zeko-freelance' ); ?></th>
						<th><?php esc_html_e( 'Contract', 'zeko-freelance' ); ?></th>
						<th><?php esc_html_e( 'Opened by', 'zeko-freelance' ); ?></th>
						<th><?php esc_html_e( 'Status', 'zeko-freelance' ); ?></th>
						<th><?php esc_html_e( 'Opened', 'zeko-freelance' ); ?></th>
						<th><?php esc_html_e( 'Resolution', 'zeko-freelance' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $disputes ) ) : ?>
						<tr><td colspan="6"><?php esc_html_e( 'No disputes in this state.', 'zeko-freelance' ); ?></td></tr>
					<?php else : ?>
						<?php foreach ( $disputes as $dispute ) : ?>
							<tr>
								<td>
									<strong>#<?php echo esc_html( (string) $dispute->id ); ?> — <?php echo esc_html( $dispute->subject ); ?></strong>
									<br /><small><?php echo esc_html( wp_trim_words( $dispute->description, 24 ) ); ?></small>
								</td>
								<td>#<?php echo esc_html( (string) $dispute->contract_id ); ?></td>
								<td><?php echo esc_html( get_the_author_meta( 'display_name', (int) $dispute->user_id ) ); ?></td>
								<td><?php echo esc_html( $dispute->status ); ?></td>
								<td><?php echo esc_html( mysql2date( 'M j, Y', $dispute->created_at ) ); ?></td>
								<td>
									<?php if ( 'open' === $dispute->status ) : ?>
										<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
											<input type="hidden" name="action" value="zeko_freelance_dispute_action" />
											<input type="hidden" name="dispute_id" value="<?php echo esc_attr( (string) $dispute->id ); ?>" />
											<input type="hidden" name="dispute_action" value="refund_client" />
											<input type="hidden" name="dispute_resolution" value="<?php esc_attr_e( 'Resolved in favor of the client. Escrow refunded.', 'zeko-freelance' ); ?>" />
											<?php wp_nonce_field( 'zeko_freelance_dispute_action' ); ?>
											<button class="button button-primary"><?php esc_html_e( 'Refund client', 'zeko-freelance' ); ?></button>
										</form>
										<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
											<input type="hidden" name="action" value="zeko_freelance_dispute_action" />
											<input type="hidden" name="dispute_id" value="<?php echo esc_attr( (string) $dispute->id ); ?>" />
											<input type="hidden" name="dispute_action" value="release_freelancer" />
											<input type="hidden" name="dispute_resolution" value="<?php esc_attr_e( 'Resolved in favor of the freelancer. Escrow released.', 'zeko-freelance' ); ?>" />
											<?php wp_nonce_field( 'zeko_freelance_dispute_action' ); ?>
											<button class="button"><?php esc_html_e( 'Release to freelancer', 'zeko-freelance' ); ?></button>
										</form>
									<?php else : ?>
										<?php echo esc_html( $dispute->resolution ); ?>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
			<?php
			/** Zeko PRO hook: dispute SLA escalation panel (renders only when a premium module is active). */
			do_action( 'zeko_freelance_disputes_after_table' );
			?>
		</div>
			<?php
	}

	/**
	 * Handle dispute action.
	 */
	public function handle_dispute_action(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized', 'zeko-freelance' ) );
		}

		check_admin_referer( 'zeko_freelance_dispute_action' );

		$dispute_id = isset( $_POST['dispute_id'] ) ? absint( $_POST['dispute_id'] ) : 0;
		$action     = isset( $_POST['dispute_action'] ) ? sanitize_key( wp_unslash( $_POST['dispute_action'] ) ) : '';
		$resolution = isset( $_POST['dispute_resolution'] ) ? sanitize_textarea_field( wp_unslash( $_POST['dispute_resolution'] ) ) : '';

		if ( in_array( $action, array( 'refund_client', 'release_freelancer' ), true ) ) {
			$resolved = $this->db->resolve_dispute( $dispute_id, $action, $resolution );
			if ( $resolved ) {
				zeko_freelance()->get_emails()->notify_dispute_resolved( $dispute_id );
			}
		}

		$this->redirect( admin_url( 'admin.php?page=zeko-freelance-disputes' ) );
	}

	/**
	 * Render skills.
	 */
	public function render_skills(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$categories = Zeko_Freelance_Ajax::allowed_categories();
		$skills     = $this->db->get_skills();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Skill Taxonomy', 'zeko-freelance' ); ?></h1>
			<p class="description">
				<?php esc_html_e( 'Skills power the autocomplete suggestions in project, portfolio and profile forms, and drive marketplace filtering.', 'zeko-freelance' ); ?>
			</p>

			<h2><?php esc_html_e( 'Add a skill', 'zeko-freelance' ); ?></h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="validate">
				<input type="hidden" name="action" value="zeko_freelance_skill_action" />
				<input type="hidden" name="skill_action" value="add" />
				<?php wp_nonce_field( 'zeko_freelance_skill_action' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="zf-skill-name"><?php esc_html_e( 'Skill name', 'zeko-freelance' ); ?></label></th>
						<td>
							<input name="skill_name" type="text" id="zf-skill-name" value="" maxlength="100" class="regular-text" required />
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="zf-skill-category"><?php esc_html_e( 'Category', 'zeko-freelance' ); ?></label></th>
						<td>
							<select name="skill_category" id="zf-skill-category">
								<?php foreach ( $categories as $cat ) : ?>
									<option value="<?php echo esc_attr( $cat ); ?>"><?php echo esc_html( ucfirst( $cat ) ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
				</table>
				<?php submit_button( __( 'Add skill', 'zeko-freelance' ) ); ?>
			</form>

			<h2><?php esc_html_e( 'Existing skills', 'zeko-freelance' ); ?></h2>
			<?php if ( empty( $skills ) ) : ?>
				<p><?php esc_html_e( 'No skills yet.', 'zeko-freelance' ); ?></p>
			<?php else : ?>
				<table class="widefat striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Name', 'zeko-freelance' ); ?></th>
							<th><?php esc_html_e( 'Slug', 'zeko-freelance' ); ?></th>
							<th><?php esc_html_e( 'Category', 'zeko-freelance' ); ?></th>
							<th><?php esc_html_e( 'Actions', 'zeko-freelance' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $skills as $skill ) : ?>
							<tr>
								<td><strong><?php echo esc_html( $skill->name ); ?></strong></td>
								<td><code><?php echo esc_html( $skill->slug ); ?></code></td>
								<td><?php echo esc_html( ucfirst( $skill->category ) ); ?></td>
								<td>
									<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;" onsubmit="return confirm('<?php esc_attr_e( 'Delete this skill? Existing listings will keep it as free text.', 'zeko-freelance' ); ?>');">
										<input type="hidden" name="action" value="zeko_freelance_skill_action" />
										<input type="hidden" name="skill_action" value="delete" />
										<input type="hidden" name="skill_id" value="<?php echo esc_attr( (string) $skill->id ); ?>" />
										<?php wp_nonce_field( 'zeko_freelance_skill_action' ); ?>
										<button class="button button-link-delete"><?php esc_html_e( 'Delete', 'zeko-freelance' ); ?></button>
									</form>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:16px;">
				<input type="hidden" name="action" value="zeko_freelance_skill_action" />
				<input type="hidden" name="skill_action" value="reseed" />
				<?php wp_nonce_field( 'zeko_freelance_skill_action' ); ?>
				<p>
					<button class="button"><?php esc_html_e( 'Restore missing default skills', 'zeko-freelance' ); ?></button>
					<span class="description"><?php esc_html_e( 'Adds back any seeded defaults that were deleted. Custom skills are left untouched.', 'zeko-freelance' ); ?></span>
				</p>
			</form>
		</div>
		<?php
	}

	/**
	 * Handle skill action.
	 */
	public function handle_skill_action(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized', 'zeko-freelance' ) );
		}

		check_admin_referer( 'zeko_freelance_skill_action' );

		$action = isset( $_POST['skill_action'] ) ? sanitize_key( wp_unslash( $_POST['skill_action'] ) ) : '';

		switch ( $action ) {
			case 'add':
				$name     = isset( $_POST['skill_name'] ) ? sanitize_text_field( wp_unslash( $_POST['skill_name'] ) ) : '';
				$category = isset( $_POST['skill_category'] ) ? sanitize_key( wp_unslash( $_POST['skill_category'] ) ) : '';
				if ( '' !== $name && in_array( $category, Zeko_Freelance_Ajax::allowed_categories(), true ) ) {
					$this->db->insert_skill( mb_substr( $name, 0, 100 ), $category );
				}
				break;

			case 'delete':
				$id = isset( $_POST['skill_id'] ) ? absint( $_POST['skill_id'] ) : 0;
				if ( $id > 0 ) {
					$this->db->delete_skill( $id );
				}
				break;

			case 'reseed':
				$this->db->seed_default_skills( true );
				break;
		}

		$this->redirect( admin_url( 'admin.php?page=zeko-freelance-skills' ) );
	}

	/**
	 * Seed or clear the freelance demo data.
	 */
	public function handle_demo_action(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized', 'zeko-freelance' ) );
		}

		check_admin_referer( 'zeko_freelance_demo_action' );

		$action = isset( $_POST['demo_action'] ) ? sanitize_key( wp_unslash( $_POST['demo_action'] ) ) : '';

		require_once ZEKO_FREELANCE_PLUGIN_PATH . 'includes/admin/class-zeko-freelance-demo-generator.php';
		$generator = new Zeko_Freelance_Demo_Generator( $this->db );

		if ( 'seed' === $action ) {
			$generator->seed();
			update_option( 'zeko_freelance_demo_seeded', ZEKO_FREELANCE_VERSION );
		} elseif ( 'clear' === $action ) {
			$generator->clear();
			delete_option( 'zeko_freelance_demo_seeded' );
		}

		$this->redirect( admin_url( 'admin.php?page=zeko-freelance' ) );
	}
}
