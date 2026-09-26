<?php
/**
 * Zeko Freelance front-end AJAX handlers.
 *
 * Handles project create/edit/delete and bookmarking from the public
 * templates. All handlers require a logged-in user and verify the shared
 * freelance nonce.
 *
 * @package Zeko_Freelance
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}



require_once __DIR__ . '/class-zeko-freelance-ajax-halt.php';

/** Class Zeko_Freelance_Ajax. */
class Zeko_Freelance_Ajax {

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

		add_action( 'wp_ajax_zeko_freelance_save_project', array( $this, 'handle_save_project' ) );
		add_action( 'wp_ajax_zeko_freelance_delete_project', array( $this, 'handle_delete_project' ) );
		add_action( 'wp_ajax_zeko_freelance_bookmark_project', array( $this, 'handle_bookmark_project' ) );
		add_action( 'wp_ajax_nopriv_zeko_freelance_bookmark_project', array( $this, 'denied' ) );

		add_action( 'wp_ajax_zeko_freelance_submit_bid', array( $this, 'handle_submit_bid' ) );
		add_action( 'wp_ajax_zeko_freelance_withdraw_bid', array( $this, 'handle_withdraw_bid' ) );
		add_action( 'wp_ajax_zeko_freelance_award_bid', array( $this, 'handle_award_bid' ) );
		add_action( 'wp_ajax_nopriv_zeko_freelance_submit_bid', array( $this, 'denied' ) );
		add_action( 'wp_ajax_nopriv_zeko_freelance_withdraw_bid', array( $this, 'denied' ) );
		add_action( 'wp_ajax_nopriv_zeko_freelance_award_bid', array( $this, 'denied' ) );

		add_action( 'wp_ajax_zeko_freelance_create_milestone', array( $this, 'handle_create_milestone' ) );
		add_action( 'wp_ajax_zeko_freelance_delete_milestone', array( $this, 'handle_delete_milestone' ) );
		add_action( 'wp_ajax_zeko_freelance_fund_milestone', array( $this, 'handle_fund_milestone' ) );
		add_action( 'wp_ajax_zeko_freelance_submit_milestone', array( $this, 'handle_submit_milestone' ) );
		add_action( 'wp_ajax_zeko_freelance_approve_milestone', array( $this, 'handle_approve_milestone' ) );
		add_action( 'wp_ajax_zeko_freelance_reject_milestone', array( $this, 'handle_reject_milestone' ) );
		add_action( 'wp_ajax_zeko_freelance_cancel_contract', array( $this, 'handle_cancel_contract' ) );
		add_action( 'wp_ajax_zeko_freelance_open_dispute', array( $this, 'handle_open_dispute' ) );
		add_action( 'wp_ajax_nopriv_zeko_freelance_create_milestone', array( $this, 'denied' ) );

		add_action( 'wp_ajax_zeko_freelance_save_portfolio', array( $this, 'handle_save_portfolio' ) );
		add_action( 'wp_ajax_zeko_freelance_delete_portfolio', array( $this, 'handle_delete_portfolio' ) );
		add_action( 'wp_ajax_zeko_freelance_save_profile', array( $this, 'handle_save_profile' ) );
		add_action( 'wp_ajax_zeko_freelance_suggest_skills', array( $this, 'handle_suggest_skills' ) );
		add_action( 'wp_ajax_nopriv_zeko_freelance_suggest_skills', array( $this, 'denied' ) );
		add_action( 'wp_ajax_nopriv_zeko_freelance_save_portfolio', array( $this, 'denied' ) );
		add_action( 'wp_ajax_nopriv_zeko_freelance_delete_portfolio', array( $this, 'denied' ) );
		add_action( 'wp_ajax_nopriv_zeko_freelance_save_profile', array( $this, 'denied' ) );

		add_action( 'wp_ajax_zeko_freelance_apply_verification', array( $this, 'handle_apply_verification' ) );
		add_action( 'wp_ajax_nopriv_zeko_freelance_apply_verification', array( $this, 'denied' ) );
		add_action( 'wp_ajax_zeko_freelance_submit_review', array( $this, 'handle_submit_review' ) );
		add_action( 'wp_ajax_nopriv_zeko_freelance_submit_review', array( $this, 'denied' ) );
		add_action( 'wp_ajax_nopriv_zeko_freelance_delete_milestone', array( $this, 'denied' ) );
		add_action( 'wp_ajax_nopriv_zeko_freelance_fund_milestone', array( $this, 'denied' ) );
		add_action( 'wp_ajax_nopriv_zeko_freelance_submit_milestone', array( $this, 'denied' ) );
		add_action( 'wp_ajax_nopriv_zeko_freelance_approve_milestone', array( $this, 'denied' ) );
		add_action( 'wp_ajax_nopriv_zeko_freelance_reject_milestone', array( $this, 'denied' ) );
		add_action( 'wp_ajax_nopriv_zeko_freelance_cancel_contract', array( $this, 'denied' ) );
		add_action( 'wp_ajax_nopriv_zeko_freelance_open_dispute', array( $this, 'denied' ) );
	}

	/**
	 * Shortlist of allowed project categories (mirrors the seeded skill
	 * taxonomy so filters and forms stay consistent).
	 *
	 * @return string[]
	 */
	public static function allowed_categories(): array {
		return array( 'development', 'design', 'writing', 'marketing', 'admin', 'media', 'business', 'other' );
	}

	/**
	 * Allowed listing durations.
	 *
	 * @return array<string,array{label:string,days:int}>
	 */
	public static function durations(): array {
		return array(
			'less-than-week' => array(
				'label' => __( 'Less than a week', 'zeko-freelance' ),
				'days'  => 7,
			),
			'1-4-weeks'      => array(
				'label' => __( '1–4 weeks', 'zeko-freelance' ),
				'days'  => 30,
			),
			'1-3-months'     => array(
				'label' => __( '1–3 months', 'zeko-freelance' ),
				'days'  => 90,
			),
			'3-plus-months'  => array(
				'label' => __( 'More than 3 months', 'zeko-freelance' ),
				'days'  => 180,
			),
			'flexible'       => array(
				'label' => __( 'Flexible', 'zeko-freelance' ),
				'days'  => 180,
			),
		);
	}

	/**
	 * Register the shared public nonce key + action used by templates.
	 */
	public static function nonce_action(): string {
		return 'zeko_freelance_public';
	}

	// ═══════════════════════════════════════════════════════════════.
	// SAVE PROJECT.
	// ═══════════════════════════════════════════════════════════════.

	/**
	 * Handle save project.
	 */
	public function handle_save_project(): void {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			$this->respond( array( 'error' => __( 'You must be logged in to post a project.', 'zeko-freelance' ) ), 401 );
		}

		check_ajax_referer( self::nonce_action(), 'nonce' );

		$project_id = isset( $_POST['zf_project_id'] ) ? absint( $_POST['zf_project_id'] ) : 0;
		$existing   = $project_id ? $this->db->get_project( $project_id ) : null;

		if ( $existing && ! $this->can_manage_project( $existing, $user_id ) ) {
			$this->respond( array( 'error' => __( 'You cannot edit this project.', 'zeko-freelance' ) ), 403 );
		}

		$errors = array();

		$title = isset( $_POST['zf_title'] ) ? sanitize_text_field( wp_unslash( $_POST['zf_title'] ) ) : '';
		if ( mb_strlen( $title ) < 4 || mb_strlen( $title ) > 200 ) {
			$errors[] = __( 'Title must be between 4 and 200 characters.', 'zeko-freelance' );
		}

		$description = isset( $_POST['zf_description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['zf_description'] ) ) : '';
		if ( mb_strlen( $description ) < 20 ) {
			$errors[] = __( 'Description must be at least 20 characters.', 'zeko-freelance' );
		}

		$budget_min = isset( $_POST['zf_budget_min'] ) ? (float) sanitize_text_field( wp_unslash( $_POST['zf_budget_min'] ) ) : 0.0;
		$budget_max = isset( $_POST['zf_budget_max'] ) ? (float) sanitize_text_field( wp_unslash( $_POST['zf_budget_max'] ) ) : 0.0;
		$budget_min = round( max( 0, $budget_min ), 2 );
		$budget_max = round( max( 0, $budget_max ), 2 );
		if ( $budget_min <= 0 || $budget_max < $budget_min ) {
			$errors[] = __( 'Enter a valid budget range (minimum must be greater than 0 and no larger than the maximum).', 'zeko-freelance' );
		}

		$category = isset( $_POST['zf_category'] ) ? sanitize_key( wp_unslash( $_POST['zf_category'] ) ) : '';
		if ( ! in_array( $category, self::allowed_categories(), true ) ) {
			$errors[] = __( 'Please choose a valid category.', 'zeko-freelance' );
		}

		$duration = isset( $_POST['zf_duration'] ) ? sanitize_key( wp_unslash( $_POST['zf_duration'] ) ) : '';
		if ( ! isset( self::durations()[ $duration ] ) ) {
			$errors[] = __( 'Please choose a valid project duration.', 'zeko-freelance' );
		}

		$location = isset( $_POST['zf_location'] ) ? sanitize_text_field( wp_unslash( $_POST['zf_location'] ) ) : '';
		if ( mb_strlen( $location ) > 150 ) {
			$errors[] = __( 'Location must be 150 characters or fewer.', 'zeko-freelance' );
		}

		$raw_skills = isset( $_POST['zf_skills'] ) ? sanitize_text_field( wp_unslash( $_POST['zf_skills'] ) ) : '';
		$skills     = array();
		foreach ( array_filter( array_map( 'trim', explode( ',', $raw_skills ) ) ) as $skill ) {
			$skills[] = substr( sanitize_text_field( $skill ), 0, 50 );
		}
		$skills = array_values( array_unique( $skills ) );

		if ( $errors ) {
			$this->respond( array( 'error' => implode( ' ', $errors ) ), 400 );
		}

		$settings  = zeko_freelance_get_settings();
		$is_create = null === $existing;

		if ( $is_create ) {
			$active = $this->db->count_active_projects_for_user( $user_id );
			$cap    = (int) apply_filters( 'zeko_freelance_limit_active_projects', (int) $settings['max_active_projects'], $user_id );
			if ( $active >= $cap ) {
				$this->respond(
					array(
						'error' => sprintf(
						/* translators: %d: active project limit */
							__( 'You already have %d active projects. Close or delete one before posting another.', 'zeko-freelance' ),
							$cap
						),
					),
					400
				);
			}
		}

		$needs_review = ! empty( $settings['require_approval'] )
			|| ( ! empty( $settings['verify_required'] ) && ! $this->db->is_user_verified( $user_id ) );

		if ( $is_create ) {
			$days    = (int) self::durations()[ $duration ]['days'];
			$expires = date( 'Y-m-d H:i:s', time() + $days * DAY_IN_SECONDS ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date

			$project_id = $this->db->insert_project(
				array(
					'user_id'     => $user_id,
					'title'       => $title,
					'description' => $description,
					'budget_min'  => $budget_min,
					'budget_max'  => $budget_max,
					'currency'    => $settings['currency'],
					'category'    => $category,
					'skills'      => implode( ',', $skills ),
					'location'    => $location,
					'duration'    => $duration,
					'status'      => $needs_review ? 'pending' : 'open',
					'expires_at'  => $expires,
				)
			);
		} else {
			$this->db->update_project(
				$project_id,
				array(
					'title'       => $title,
					'description' => $description,
					'budget_min'  => $budget_min,
					'budget_max'  => $budget_max,
					'category'    => $category,
					'skills'      => implode( ',', $skills ),
					'location'    => $location,
					'duration'    => $duration,
				)
			);
		}

		if ( $is_create && 'open' === $this->db->get_project( $project_id )->status ) {
			do_action( 'zeko_freelance_project_created', (int) $project_id, (int) $user_id );
		}

		$this->respond(
			array(
				'project_id' => (int) $project_id,
				'status'     => $this->db->get_project( $project_id )->status,
				'message'    => $is_create
					? ( 'pending' === $this->db->get_project( $project_id )->status
						? __( 'Project submitted for review.', 'zeko-freelance' )
						: __( 'Project published!', 'zeko-freelance' ) )
					: __( 'Project updated.', 'zeko-freelance' ),
			)
		);
	}

	// ═══════════════════════════════════════════════════════════════.
	// DELETE PROJECT.
	// ═══════════════════════════════════════════════════════════════.

	/**
	 * Handle delete project.
	 */
	public function handle_delete_project(): void {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			$this->respond( array( 'error' => __( 'You must be logged in.', 'zeko-freelance' ) ), 401 );
		}

		check_ajax_referer( self::nonce_action(), 'nonce' );

		$project_id = isset( $_POST['zf_project_id'] ) ? absint( $_POST['zf_project_id'] ) : 0;
		$project    = $this->db->get_project( $project_id );

		if ( ! $project || ! $this->can_manage_project( $project, $user_id ) ) {
			$this->respond( array( 'error' => __( 'You cannot delete this project.', 'zeko-freelance' ) ), 403 );
		}

		$this->db->delete_project( $project_id );
		$this->respond( array( 'ok' => true ) );
	}

	// ═══════════════════════════════════════════════════════════════.
	// BOOKMARKS.
	// ═══════════════════════════════════════════════════════════════.

	/**
	 * Handle bookmark project.
	 */
	public function handle_bookmark_project(): void {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			$this->respond( array( 'error' => __( 'You must be logged in to bookmark projects.', 'zeko-freelance' ) ), 401 );
		}

		check_ajax_referer( self::nonce_action(), 'nonce' );

		$project_id = isset( $_POST['zf_project_id'] ) ? absint( $_POST['zf_project_id'] ) : 0;
		if ( ! $this->db->get_project( $project_id ) ) {
			$this->respond( array( 'error' => __( 'Project not found.', 'zeko-freelance' ) ), 404 );
		}

		$bookmarks = zeko_freelance()->get_bookmarked_project_ids( $user_id );
		$index     = array_search( $project_id, $bookmarks, true );

		if ( false !== $index ) {
			unset( $bookmarks[ $index ] );
			$bookmarked = false;
		} else {
			$bookmarks[] = $project_id;
			$bookmarked  = true;
		}

		update_user_meta( $user_id, 'zeko_bookmarked_projects', array_values( array_unique( array_map( 'absint', $bookmarks ) ) ) );

		$this->respond(
			array(
				'bookmarked' => $bookmarked,
				'count'      => count( $bookmarks ),
			)
		);
	}

	// ═══════════════════════════════════════════════════════════════.
	// BIDS.
	// ═══════════════════════════════════════════════════════════════.

	/**
	 * Handle submit bid.
	 */
	public function handle_submit_bid(): void {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			$this->respond( array( 'error' => __( 'You must be logged in to place a bid.', 'zeko-freelance' ) ), 401 );
		}

		check_ajax_referer( self::nonce_action(), 'nonce' );

		$project_id = isset( $_POST['zf_project_id'] ) ? absint( $_POST['zf_project_id'] ) : 0;
		$project    = $this->db->get_project( $project_id );

		if ( ! $project ) {
			$this->respond( array( 'error' => __( 'Project not found.', 'zeko-freelance' ) ), 404 );
		}
		if ( 'open' !== $project->status ) {
			$this->respond( array( 'error' => __( 'This project is no longer accepting bids.', 'zeko-freelance' ) ), 400 );
		}
		if ( (int) $project->user_id === $user_id ) {
			$this->respond( array( 'error' => __( 'You cannot bid on your own project.', 'zeko-freelance' ) ), 400 );
		}

		$amount        = isset( $_POST['zf_amount'] ) ? round( (float) sanitize_text_field( wp_unslash( $_POST['zf_amount'] ) ), 2 ) : 0.0;
		$delivery_days = isset( $_POST['zf_delivery_days'] ) ? absint( wp_unslash( $_POST['zf_delivery_days'] ) ) : 0;
		$proposal      = isset( $_POST['zf_proposal'] ) ? sanitize_textarea_field( wp_unslash( $_POST['zf_proposal'] ) ) : '';

		$errors = array();
		if ( $amount <= 0 ) {
			$errors[] = __( 'Enter a bid amount greater than zero.', 'zeko-freelance' );
		}
		if ( $delivery_days < 1 || $delivery_days > 365 ) {
			$errors[] = __( 'Delivery time must be between 1 and 365 days.', 'zeko-freelance' );
		}
		if ( mb_strlen( $proposal ) < 20 ) {
			$errors[] = __( 'Proposal must be at least 20 characters.', 'zeko-freelance' );
		}
		if ( mb_strlen( $proposal ) > 2000 ) {
			$errors[] = __( 'Proposal must be 2000 characters or fewer.', 'zeko-freelance' );
		}

		$settings = zeko_freelance_get_settings();
		if ( ! empty( $settings['verify_required'] ) && ! $this->db->is_user_verified( $user_id ) ) {
			$errors[] = __( 'Only verified freelancers can place bids.', 'zeko-freelance' );
		}

		if ( $errors ) {
			$this->respond( array( 'error' => implode( ' ', $errors ) ), 400 );
		}

		$existing = $this->db->get_bid_for( $project_id, $user_id );
		if ( $existing && 'accepted' === $existing->status ) {
			$this->respond( array( 'error' => __( 'This project already has a contract.', 'zeko-freelance' ) ), 400 );
		}

		// Track the previous lowest bidder so a newly placed/updated bid can.
		// notify whoever it displaces.
		$before          = $this->db->get_bids( $project_id, 'pending' );
		$previous_lowest = $before ? (int) $before[0]->user_id : 0;

		$is_new = ! $existing;
		if ( $is_new ) {
			$bid_id = $this->db->insert_bid(
				array(
					'project_id'    => $project_id,
					'user_id'       => $user_id,
					'amount'        => $amount,
					'delivery_days' => $delivery_days,
					'proposal'      => $proposal,
					'status'        => 'pending',
				)
			);
		} else {
			$this->db->update_bid(
				(int) $existing->id,
				array(
					'amount'        => $amount,
					'delivery_days' => $delivery_days,
					'proposal'      => $proposal,
					'status'        => 'pending',
				)
			);
			$bid_id = (int) $existing->id;
		}

		if ( $is_new ) {
			do_action( 'zeko_freelance_bid_placed', (int) $bid_id, (int) $project_id, (int) $user_id );
			zeko_freelance()->get_emails()->notify_owner_new_bid( (int) $project_id, (int) $bid_id );
		}

		$after      = $this->db->get_bids( $project_id, 'pending' );
		$new_lowest = $after ? (int) $after[0]->user_id : 0;
		if ( $previous_lowest && $new_lowest && $previous_lowest !== $new_lowest && $previous_lowest !== $user_id ) {
			zeko_freelance()->get_emails()->notify_freelancer_outbid( (int) $before[0]->id );
		}

		$this->respond(
			array(
				'bid_id'  => (int) $bid_id,
				'status'  => 'pending',
				'message' => $is_new ? __( 'Bid submitted.', 'zeko-freelance' ) : __( 'Bid updated.', 'zeko-freelance' ),
			)
		);
	}

	/**
	 * Handle withdraw bid.
	 */
	public function handle_withdraw_bid(): void {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			$this->respond( array( 'error' => __( 'You must be logged in.', 'zeko-freelance' ) ), 401 );
		}

		check_ajax_referer( self::nonce_action(), 'nonce' );

		$bid_id = isset( $_POST['zf_bid_id'] ) ? absint( $_POST['zf_bid_id'] ) : 0;
		$bid    = $this->db->get_bid( $bid_id );

		if ( ! $bid || (int) $bid->user_id !== $user_id ) {
			$this->respond( array( 'error' => __( 'Bid not found.', 'zeko-freelance' ) ), 404 );
		}
		if ( 'pending' !== $bid->status || ! $this->db->withdraw_bid( $bid_id ) ) {
			$this->respond( array( 'error' => __( 'This bid cannot be withdrawn.', 'zeko-freelance' ) ), 400 );
		}

		$this->respond(
			array(
				'ok'      => true,
				'message' => __( 'Bid withdrawn.', 'zeko-freelance' ),
			)
		);
	}

	/**
	 * Handle award bid.
	 */
	public function handle_award_bid(): void {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			$this->respond( array( 'error' => __( 'You must be logged in.', 'zeko-freelance' ) ), 401 );
		}

		check_ajax_referer( self::nonce_action(), 'nonce' );

		$bid_id = isset( $_POST['zf_bid_id'] ) ? absint( $_POST['zf_bid_id'] ) : 0;
		$bid    = $this->db->get_bid( $bid_id );

		if ( ! $bid ) {
			$this->respond( array( 'error' => __( 'Bid not found.', 'zeko-freelance' ) ), 404 );
		}

		$project = $this->db->get_project( (int) $bid->project_id );
		if ( ! $project || ! $this->can_manage_project( $project, $user_id ) ) {
			$this->respond( array( 'error' => __( 'Only the project owner can accept a bid.', 'zeko-freelance' ) ), 403 );
		}
		if ( 'open' !== $project->status ) {
			$this->respond( array( 'error' => __( 'This project is no longer accepting bids.', 'zeko-freelance' ) ), 400 );
		}
		if ( 'pending' !== $bid->status ) {
			$this->respond( array( 'error' => __( 'This bid can no longer be accepted.', 'zeko-freelance' ) ), 400 );
		}

		$contract_id = $this->db->award_bid( $bid_id );
		if ( ! $contract_id ) {
			$this->respond( array( 'error' => __( 'Could not create the contract. Please try again.', 'zeko-freelance' ) ), 500 );
		}

		do_action( 'zeko_freelance_bid_awarded', (int) $bid_id, (int) $contract_id, (int) $project->id, (int) $bid->user_id );
		zeko_freelance()->get_emails()->notify_freelancer_awarded( (int) $bid_id );

		$this->respond(
			array(
				'ok'          => true,
				'contract_id' => (int) $contract_id,
				'message'     => __( 'Bid accepted. Contract created.', 'zeko-freelance' ),
			)
		);
	}

	// ═══════════════════════════════════════════════════════════════.
	// MILESTONES & CONTRACTS.
	// ═══════════════════════════════════════════════════════════════.

	/**
	 * Handle create milestone.
	 */
	public function handle_create_milestone(): void {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			$this->respond( array( 'error' => __( 'You must be logged in.', 'zeko-freelance' ) ), 401 );
		}

		check_ajax_referer( self::nonce_action(), 'nonce' );

		$contract_id = isset( $_POST['zf_contract_id'] ) ? absint( $_POST['zf_contract_id'] ) : 0;
		$contract    = $this->db->get_contract( $contract_id );
		if ( ! $contract ) {
			$this->respond( array( 'error' => __( 'Contract not found.', 'zeko-freelance' ) ), 404 );
		}
		if ( (int) $contract->client_id !== $user_id && ! user_can( $user_id, 'manage_options' ) ) {
			$this->respond( array( 'error' => __( 'Only the client can manage milestones on this contract.', 'zeko-freelance' ) ), 403 );
		}
		if ( ! in_array( $contract->status, array( 'pending', 'active' ), true ) ) {
			$this->respond( array( 'error' => __( 'This contract is not active.', 'zeko-freelance' ) ), 400 );
		}

		$title       = isset( $_POST['zf_milestone_title'] ) ? sanitize_text_field( wp_unslash( $_POST['zf_milestone_title'] ) ) : '';
		$description = isset( $_POST['zf_milestone_description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['zf_milestone_description'] ) ) : '';
		$amount      = isset( $_POST['zf_milestone_amount'] ) ? round( (float) sanitize_text_field( wp_unslash( $_POST['zf_milestone_amount'] ) ), 2 ) : 0.0;

		$errors = array();
		if ( mb_strlen( $title ) < 2 || mb_strlen( $title ) > 200 ) {
			$errors[] = __( 'Milestone title must be between 2 and 200 characters.', 'zeko-freelance' );
		}
		if ( mb_strlen( $description ) > 2000 ) {
			$errors[] = __( 'Milestone description must be 2000 characters or fewer.', 'zeko-freelance' );
		}
		if ( $amount <= 0 ) {
			$errors[] = __( 'Milestone amount must be greater than zero.', 'zeko-freelance' );
		}

		$due_at = null;
		if ( ! empty( $_POST['zf_milestone_due'] ) ) {
			$due_raw = strtotime( sanitize_text_field( wp_unslash( $_POST['zf_milestone_due'] ) ) );
			$due_at  = $due_raw ? date( 'Y-m-d H:i:s', $due_raw ) : null; // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
			if ( ! $due_at ) {
				$errors[] = __( 'Please enter a valid due date.', 'zeko-freelance' );
			}
		}

		if ( $errors ) {
			$this->respond( array( 'error' => implode( ' ', $errors ) ), 400 );
		}

		$totals = $this->db->milestone_totals( $contract_id );
		if ( $totals['total'] + $amount > (float) $contract->escrow_amount ) {
			$this->respond( array( 'error' => __( 'Milestones cannot exceed the contract escrow amount.', 'zeko-freelance' ) ), 400 );
		}

		$milestone_id = $this->db->insert_milestone(
			array(
				'contract_id' => $contract_id,
				'title'       => $title,
				'description' => $description,
				'amount'      => $amount,
				'due_at'      => $due_at,
			)
		);

		$this->respond(
			array(
				'milestone_id' => (int) $milestone_id,
				'message'      => __( 'Milestone added.', 'zeko-freelance' ),
			)
		);
	}

	/**
	 * Handle delete milestone.
	 */
	public function handle_delete_milestone(): void {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			$this->respond( array( 'error' => __( 'You must be logged in.', 'zeko-freelance' ) ), 401 );
		}

		check_ajax_referer( self::nonce_action(), 'nonce' );

		$milestone_id = isset( $_POST['zf_milestone_id'] ) ? absint( $_POST['zf_milestone_id'] ) : 0;
		$milestone    = $this->db->get_milestone( $milestone_id );
		if ( ! $milestone ) {
			$this->respond( array( 'error' => __( 'Milestone not found.', 'zeko-freelance' ) ), 404 );
		}

		$contract = $this->db->get_contract( (int) $milestone->contract_id );
		if ( ! $contract || ( (int) $contract->client_id !== $user_id && ! user_can( $user_id, 'manage_options' ) ) ) {
			$this->respond( array( 'error' => __( 'Only the client can manage milestones on this contract.', 'zeko-freelance' ) ), 403 );
		}
		if ( 'pending' !== $milestone->status || ! $this->db->delete_milestone( $milestone_id ) ) {
			$this->respond( array( 'error' => __( 'Only unfunded milestones can be removed.', 'zeko-freelance' ) ), 400 );
		}

		$this->respond(
			array(
				'ok'      => true,
				'message' => __( 'Milestone removed.', 'zeko-freelance' ),
			)
		);
	}

	/**
	 * Handle fund milestone.
	 */
	public function handle_fund_milestone(): void {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			$this->respond( array( 'error' => __( 'You must be logged in.', 'zeko-freelance' ) ), 401 );
		}

		check_ajax_referer( self::nonce_action(), 'nonce' );

		$milestone_id = isset( $_POST['zf_milestone_id'] ) ? absint( $_POST['zf_milestone_id'] ) : 0;
		$milestone    = $this->db->get_milestone( $milestone_id );
		if ( ! $milestone ) {
			$this->respond( array( 'error' => __( 'Milestone not found.', 'zeko-freelance' ) ), 404 );
		}

		$contract = $this->db->get_contract( (int) $milestone->contract_id );
		if ( ! $contract || ( (int) $contract->client_id !== $user_id && ! user_can( $user_id, 'manage_options' ) ) ) {
			$this->respond( array( 'error' => __( 'Only the client can fund milestones.', 'zeko-freelance' ) ), 403 );
		}

		$result = $this->db->fund_milestone( $milestone_id );
		if ( empty( $result['success'] ) ) {
			$this->respond( array( 'error' => $result['message'] ?? __( 'Could not fund the milestone.', 'zeko-freelance' ) ), 400 );
		}

		$emails = zeko_freelance()->get_emails();
		$emails->notify_client_milestone_funded( (int) $contract->id, $milestone_id );
		$emails->notify_freelancer_milestone_funded( (int) $contract->id, $milestone_id );

		$this->respond(
			array(
				'ok'              => true,
				'milestone_id'    => $milestone_id,
				'contract_status' => $this->db->get_contract( (int) $contract->id )->status,
				'message'         => $result['message'],
			)
		);
	}

	/**
	 * Handle submit milestone.
	 */
	public function handle_submit_milestone(): void {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			$this->respond( array( 'error' => __( 'You must be logged in.', 'zeko-freelance' ) ), 401 );
		}

		check_ajax_referer( self::nonce_action(), 'nonce' );

		$milestone_id = isset( $_POST['zf_milestone_id'] ) ? absint( $_POST['zf_milestone_id'] ) : 0;
		$milestone    = $this->db->get_milestone( $milestone_id );
		if ( ! $milestone ) {
			$this->respond( array( 'error' => __( 'Milestone not found.', 'zeko-freelance' ) ), 404 );
		}

		$contract = $this->db->get_contract( (int) $milestone->contract_id );
		if ( ! $contract || ( (int) $contract->freelancer_id !== $user_id && ! user_can( $user_id, 'manage_options' ) ) ) {
			$this->respond( array( 'error' => __( 'Only the assigned freelancer can submit work.', 'zeko-freelance' ) ), 403 );
		}
		if ( ! $this->db->submit_milestone_for_review( $milestone_id ) ) {
			$this->respond( array( 'error' => __( 'This milestone cannot be submitted for review.', 'zeko-freelance' ) ), 400 );
		}

		zeko_freelance()->get_emails()->notify_client_milestone_submitted( (int) $contract->id, $milestone_id );

		$this->respond(
			array(
				'ok'           => true,
				'milestone_id' => $milestone_id,
				'status'       => 'in_review',
				'message'      => __( 'Milestone submitted for review.', 'zeko-freelance' ),
			)
		);
	}

	/**
	 * Handle approve milestone.
	 */
	public function handle_approve_milestone(): void {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			$this->respond( array( 'error' => __( 'You must be logged in.', 'zeko-freelance' ) ), 401 );
		}

		check_ajax_referer( self::nonce_action(), 'nonce' );

		$milestone_id = isset( $_POST['zf_milestone_id'] ) ? absint( $_POST['zf_milestone_id'] ) : 0;
		$milestone    = $this->db->get_milestone( $milestone_id );
		if ( ! $milestone ) {
			$this->respond( array( 'error' => __( 'Milestone not found.', 'zeko-freelance' ) ), 404 );
		}

		$contract = $this->db->get_contract( (int) $milestone->contract_id );
		if ( ! $contract || ( (int) $contract->client_id !== $user_id && ! user_can( $user_id, 'manage_options' ) ) ) {
			$this->respond( array( 'error' => __( 'Only the client can approve milestones.', 'zeko-freelance' ) ), 403 );
		}

		$result = $this->db->approve_milestone( $milestone_id );
		if ( empty( $result['success'] ) ) {
			$this->respond( array( 'error' => $result['message'] ?? __( 'Could not approve the milestone.', 'zeko-freelance' ) ), 400 );
		}

		$emails   = zeko_freelance()->get_emails();
		$contract = $this->db->get_contract( (int) $contract->id );
		$emails->notify_freelancer_milestone_approved( (int) $contract->id, $milestone_id );
		if ( 'completed' === $contract->status ) {
			$emails->notify_contract_completed( (int) $contract->id );
		}

		$this->respond(
			array(
				'ok'              => true,
				'milestone_id'    => $milestone_id,
				'contract_status' => $contract->status,
				'message'         => $result['message'],
			)
		);
	}

	/**
	 * Handle reject milestone.
	 */
	public function handle_reject_milestone(): void {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			$this->respond( array( 'error' => __( 'You must be logged in.', 'zeko-freelance' ) ), 401 );
		}

		check_ajax_referer( self::nonce_action(), 'nonce' );

		$milestone_id = isset( $_POST['zf_milestone_id'] ) ? absint( $_POST['zf_milestone_id'] ) : 0;
		$milestone    = $this->db->get_milestone( $milestone_id );
		if ( ! $milestone ) {
			$this->respond( array( 'error' => __( 'Milestone not found.', 'zeko-freelance' ) ), 404 );
		}

		$contract = $this->db->get_contract( (int) $milestone->contract_id );
		if ( ! $contract || ( (int) $contract->client_id !== $user_id && ! user_can( $user_id, 'manage_options' ) ) ) {
			$this->respond( array( 'error' => __( 'Only the client can reject milestones.', 'zeko-freelance' ) ), 403 );
		}
		if ( ! $this->db->reject_milestone( $milestone_id ) ) {
			$this->respond( array( 'error' => __( 'This milestone is not awaiting review.', 'zeko-freelance' ) ), 400 );
		}

		zeko_freelance()->get_emails()->notify_freelancer_milestone_rejected( (int) $contract->id, $milestone_id );

		$this->respond(
			array(
				'ok'           => true,
				'milestone_id' => $milestone_id,
				'status'       => 'rejected',
				'message'      => __( 'Milestone sent back for revisions.', 'zeko-freelance' ),
			)
		);
	}

	/**
	 * Handle cancel contract.
	 */
	public function handle_cancel_contract(): void {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			$this->respond( array( 'error' => __( 'You must be logged in.', 'zeko-freelance' ) ), 401 );
		}

		check_ajax_referer( self::nonce_action(), 'nonce' );

		$contract_id = isset( $_POST['zf_contract_id'] ) ? absint( $_POST['zf_contract_id'] ) : 0;
		$contract    = $this->db->get_contract( $contract_id );
		if ( ! $contract ) {
			$this->respond( array( 'error' => __( 'Contract not found.', 'zeko-freelance' ) ), 404 );
		}
		if ( ! $this->can_manage_contract( $contract, $user_id ) ) {
			$this->respond( array( 'error' => __( 'You cannot cancel this contract.', 'zeko-freelance' ) ), 403 );
		}

		$result = $this->db->cancel_contract( $contract_id );
		if ( empty( $result['success'] ) ) {
			$this->respond( array( 'error' => $result['message'] ?? __( 'Could not cancel the contract.', 'zeko-freelance' ) ), 400 );
		}

		zeko_freelance()->get_emails()->notify_contract_cancelled( $contract_id );

		$this->respond(
			array(
				'ok'              => true,
				'contract_status' => 'cancelled',
				'message'         => $result['message'],
			)
		);
	}

	/**
	 * Handle open dispute.
	 */
	public function handle_open_dispute(): void {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			$this->respond( array( 'error' => __( 'You must be logged in.', 'zeko-freelance' ) ), 401 );
		}

		check_ajax_referer( self::nonce_action(), 'nonce' );

		$contract_id = isset( $_POST['zf_contract_id'] ) ? absint( $_POST['zf_contract_id'] ) : 0;
		$contract    = $this->db->get_contract( $contract_id );
		if ( ! $contract ) {
			$this->respond( array( 'error' => __( 'Contract not found.', 'zeko-freelance' ) ), 404 );
		}
		if ( ! $this->can_manage_contract( $contract, $user_id ) ) {
			$this->respond( array( 'error' => __( 'You cannot open a dispute on this contract.', 'zeko-freelance' ) ), 403 );
		}

		$subject     = isset( $_POST['zf_dispute_subject'] ) ? sanitize_text_field( wp_unslash( $_POST['zf_dispute_subject'] ) ) : '';
		$description = isset( $_POST['zf_dispute_description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['zf_dispute_description'] ) ) : '';

		$errors = array();
		if ( mb_strlen( $subject ) < 3 || mb_strlen( $subject ) > 200 ) {
			$errors[] = __( 'Dispute subject must be between 3 and 200 characters.', 'zeko-freelance' );
		}
		if ( mb_strlen( $description ) < 10 || mb_strlen( $description ) > 3000 ) {
			$errors[] = __( 'Dispute details must be between 10 and 3000 characters.', 'zeko-freelance' );
		}
		if ( $errors ) {
			$this->respond( array( 'error' => implode( ' ', $errors ) ), 400 );
		}

		$dispute_id = $this->db->open_dispute( $contract_id, $user_id, $subject, $description );
		if ( ! $dispute_id ) {
			$this->respond( array( 'error' => __( 'A dispute is already open on this contract.', 'zeko-freelance' ) ), 400 );
		}

		zeko_freelance()->get_emails()->notify_admins_dispute_opened( $dispute_id );

		$this->respond(
			array(
				'ok'              => true,
				'dispute_id'      => $dispute_id,
				'contract_status' => 'disputed',
				'message'         => __( 'Dispute opened. An administrator will review it shortly.', 'zeko-freelance' ),
			)
		);
	}

	// ═══════════════════════════════════════════════════════════════.
	// PORTFOLIOS.
	// ═══════════════════════════════════════════════════════════════.

	/**
	 * Handle save portfolio.
	 */
	public function handle_save_portfolio(): void {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			$this->respond( array( 'error' => __( 'You must be logged in to manage your portfolio.', 'zeko-freelance' ) ), 401 );
		}

		check_ajax_referer( self::nonce_action(), 'nonce' );

		$portfolio_id = isset( $_POST['zf_portfolio_id'] ) ? absint( $_POST['zf_portfolio_id'] ) : 0;
		$existing     = $portfolio_id ? $this->db->get_portfolio( $portfolio_id ) : null;

		if ( $existing && (int) $existing->user_id !== $user_id && ! user_can( $user_id, 'manage_options' ) ) {
			$this->respond( array( 'error' => __( 'You cannot edit this portfolio item.', 'zeko-freelance' ) ), 403 );
		}

		$errors = array();

		$title = isset( $_POST['zf_portfolio_title'] ) ? sanitize_text_field( wp_unslash( $_POST['zf_portfolio_title'] ) ) : '';
		if ( mb_strlen( $title ) < 2 || mb_strlen( $title ) > 200 ) {
			$errors[] = __( 'Portfolio title must be between 2 and 200 characters.', 'zeko-freelance' );
		}

		$description = isset( $_POST['zf_portfolio_description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['zf_portfolio_description'] ) ) : '';
		if ( mb_strlen( $description ) > 3000 ) {
			$errors[] = __( 'Portfolio description must be 3000 characters or fewer.', 'zeko-freelance' );
		}

		$category = isset( $_POST['zf_portfolio_category'] ) ? sanitize_key( wp_unslash( $_POST['zf_portfolio_category'] ) ) : '';
		if ( ! in_array( $category, self::allowed_categories(), true ) ) {
			$errors[] = __( 'Please choose a valid category.', 'zeko-freelance' );
		}

		$skills_raw = isset( $_POST['zf_portfolio_skills'] ) ? sanitize_text_field( wp_unslash( $_POST['zf_portfolio_skills'] ) ) : '';
		$skills     = array_values( array_filter( array_map( 'trim', explode( ',', $skills_raw ) ) ) );
		if ( count( $skills ) > 20 ) {
			$errors[] = __( 'You can list up to 20 skills.', 'zeko-freelance' );
		}

		$link = isset( $_POST['zf_portfolio_link'] ) ? esc_url_raw( wp_unslash( $_POST['zf_portfolio_link'] ) ) : '';
		if ( '' !== $link ) {
			$link_parts = wp_parse_url( $link );
			if ( ! isset( $link_parts['scheme'] ) || ! in_array( $link_parts['scheme'], array( 'http', 'https' ), true ) ) {
				$errors[] = __( 'Portfolio link must be a valid http(s) URL.', 'zeko-freelance' );
			}
		}

		$image_input = isset( $_POST['zf_portfolio_image'] ) ? sanitize_text_field( wp_unslash( $_POST['zf_portfolio_image'] ) ) : '';
		$image_url   = '';
		if ( ! empty( $_FILES['zf_portfolio_image']['name'] ) && ! empty( $_FILES['zf_portfolio_image']['tmp_name'] ) ) {
			$image_url = $this->handle_portfolio_image_upload( $user_id );
			if ( ! $image_url ) {
				$errors[] = __( 'Could not upload the portfolio image. Try a different file.', 'zeko-freelance' );
			}
		} elseif ( '' !== trim( $image_input ) ) {
			$image_url = esc_url_raw( $image_input );
			if ( ! $image_url ) {
				$errors[] = __( 'Portfolio image must be a valid URL.', 'zeko-freelance' );
			}
		} elseif ( $existing ) {
			$image_url = (string) $existing->image_url;
		}

		if ( $errors ) {
			$this->respond( array( 'error' => implode( ' ', $errors ) ), 400 );
		}

		$data = array(
			'user_id'     => $user_id,
			'title'       => $title,
			'description' => $description,
			'category'    => $category,
			'skills'      => implode( ',', $skills ),
			'link'        => $link,
			'image_url'   => $image_url,
		);

		if ( $existing ) {
			unset( $data['user_id'] );
			$this->db->update_portfolio( $existing->id, $data );
			$this->respond(
				array(
					'ok'           => true,
					'portfolio_id' => (int) $existing->id,
					'message'      => __( 'Portfolio item updated.', 'zeko-freelance' ),
				)
			);
		}

		$portfolio_id = $this->db->insert_portfolio( $data );
		$this->respond(
			array(
				'ok'           => true,
				'portfolio_id' => (int) $portfolio_id,
				'message'      => __( 'Portfolio item added.', 'zeko-freelance' ),
			)
		);
	}

	/**
	 * Handle delete portfolio.
	 */
	public function handle_delete_portfolio(): void {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			$this->respond( array( 'error' => __( 'You must be logged in.', 'zeko-freelance' ) ), 401 );
		}

		check_ajax_referer( self::nonce_action(), 'nonce' );

		$portfolio_id = isset( $_POST['zf_portfolio_id'] ) ? absint( $_POST['zf_portfolio_id'] ) : 0;
		$portfolio    = $this->db->get_portfolio( $portfolio_id );
		if ( ! $portfolio ) {
			$this->respond( array( 'error' => __( 'Portfolio item not found.', 'zeko-freelance' ) ), 404 );
		}
		if ( (int) $portfolio->user_id !== $user_id && ! user_can( $user_id, 'manage_options' ) ) {
			$this->respond( array( 'error' => __( 'You cannot delete this portfolio item.', 'zeko-freelance' ) ), 403 );
		}

		$this->db->delete_portfolio( $portfolio_id );
		$this->respond(
			array(
				'ok'      => true,
				'message' => __( 'Portfolio item removed.', 'zeko-freelance' ),
			)
		);
	}

	// ═══════════════════════════════════════════════════════════════.
	// SKILL SUGGESTIONS.
	// ═══════════════════════════════════════════════════════════════.

	/**
	 * Autocomplete endpoint for skill entry fields. Searches the managed
	 * skill taxonomy and lets plugins seed additional matches via the
	 * `zeko_freelance_skills_suggest` filter.
	 */
	public function handle_suggest_skills(): void {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			$this->respond( array( 'error' => __( 'You must be logged in.', 'zeko-freelance' ) ), 401 );
		}

		check_ajax_referer( self::nonce_action(), 'nonce' );

		$query = isset( $_POST['term'] ) ? sanitize_text_field( wp_unslash( $_POST['term'] ) ) : '';
		$query = mb_substr( $query, 0, 100 );

		$skills = $this->db->search_skills( $query, 10 );

		$suggested = array();
		foreach ( $skills as $skill ) {
			$suggested[] = array(
				'name'     => (string) $skill->name,
				'slug'     => (string) $skill->slug,
				'category' => (string) $skill->category,
			);
		}

		$suggested = apply_filters( 'zeko_freelance_skills_suggest', $suggested, $query );

		// De-duplicate by slug and keep it shaped for a datalist.
		$seen   = array();
		$result = array();
		foreach ( $suggested as $item ) {
			$name = is_array( $item ) ? (string) ( $item['name'] ?? '' ) : (string) $item;
			$slug = is_array( $item ) ? (string) ( $item['slug'] ?? sanitize_title( $name ) ) : sanitize_title( $name );
			if ( '' === $name || isset( $seen[ $slug ] ) ) {
				continue;
			}
			$seen[ $slug ] = true;
			$result[]      = $name;
		}

		$this->respond( array( 'skills' => array_slice( $result, 0, 10 ) ) );
	}

	// ═══════════════════════════════════════════════════════════════.
	// PROFILE.
	// ═══════════════════════════════════════════════════════════════.

	/**
	 * Handle save profile.
	 */
	public function handle_save_profile(): void {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			$this->respond( array( 'error' => __( 'You must be logged in.', 'zeko-freelance' ) ), 401 );
		}

		check_ajax_referer( self::nonce_action(), 'nonce' );

		$errors = array();

		$skills_raw = isset( $_POST['zf_skills'] ) ? sanitize_text_field( wp_unslash( $_POST['zf_skills'] ) ) : '';
		$skills     = array_values( array_filter( array_map( 'trim', explode( ',', $skills_raw ) ) ) );
		if ( count( $skills ) > 20 ) {
			$errors[] = __( 'You can list up to 20 skills.', 'zeko-freelance' );
		}
		$this->db->save_user_skills( $user_id, $skills );

		$bio = isset( $_POST['zf_bio'] ) ? sanitize_textarea_field( wp_unslash( $_POST['zf_bio'] ) ) : '';
		if ( mb_strlen( $bio ) > 1000 ) {
			$errors[] = __( 'Bio must be 1000 characters or fewer.', 'zeko-freelance' );
		}

		$location = isset( $_POST['zf_location'] ) ? sanitize_text_field( wp_unslash( $_POST['zf_location'] ) ) : '';
		$location = mb_substr( $location, 0, 150 );

		if ( $errors ) {
			$this->respond( array( 'error' => implode( ' ', $errors ) ), 400 );
		}

		if ( '' !== $bio ) {
			$updated = wp_update_user(
				array(
					'ID'          => $user_id,
					'description' => $bio,
				)
			);
			if ( is_wp_error( $updated ) ) {
				$this->respond( array( 'error' => __( 'Could not save your bio.', 'zeko-freelance' ) ), 500 );
			}
		}

		if ( '' !== $location ) {
			update_user_meta( $user_id, 'zeko_freelance_location', $location );
		} else {
			delete_user_meta( $user_id, 'zeko_freelance_location' );
		}

		$this->respond(
			array(
				'ok'      => true,
				'message' => __( 'Profile updated.', 'zeko-freelance' ),
			)
		);
	}

	// ═══════════════════════════════════════════════════════════════.
	// VERIFICATION.
	// ═══════════════════════════════════════════════════════════════.

	/**
	 * Handle apply verification.
	 */
	public function handle_apply_verification(): void {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			$this->respond( array( 'error' => __( 'You must be logged in.', 'zeko-freelance' ) ), 401 );
		}

		check_ajax_referer( self::nonce_action(), 'nonce' );

		$type = isset( $_POST['zf_verification_type'] ) ? sanitize_key( wp_unslash( $_POST['zf_verification_type'] ) ) : '';
		if ( ! in_array( $type, array( 'id', 'business' ), true ) ) {
			$this->respond( array( 'error' => __( 'Please choose a valid verification type.', 'zeko-freelance' ) ), 400 );
		}

		$status = $this->db->user_verification_status( $user_id, $type );
		if ( 'approved' === $status ) {
			$this->respond( array( 'error' => __( 'You are already verified.', 'zeko-freelance' ) ), 409 );
		}
		if ( 'pending' === $status ) {
			$this->respond( array( 'error' => __( 'You already have a pending verification request.', 'zeko-freelance' ) ), 409 );
		}

		$verification_id = $this->db->insert_verification(
			array(
				'user_id' => $user_id,
				'type'    => $type,
				'status'  => 'pending',
			)
		);

		zeko_freelance()->get_emails()->notify_admins_verification_applied( (int) $verification_id );

		$this->respond(
			array(
				'ok'              => true,
				'verification_id' => (int) $verification_id,
				'status'          => 'pending',
				'message'         => __( 'Verification request submitted. An administrator will review it shortly.', 'zeko-freelance' ),
			)
		);
	}

	// ═══════════════════════════════════════════════════════════════.
	// REVIEWS.
	// ═══════════════════════════════════════════════════════════════.

	/**
	 * Handle submit review.
	 */
	public function handle_submit_review(): void {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			$this->respond( array( 'error' => __( 'You must be logged in.', 'zeko-freelance' ) ), 401 );
		}

		check_ajax_referer( self::nonce_action(), 'nonce' );

		$contract_id = isset( $_POST['zf_contract_id'] ) ? absint( $_POST['zf_contract_id'] ) : 0;
		$contract    = $this->db->get_contract( $contract_id );
		if ( ! $contract ) {
			$this->respond( array( 'error' => __( 'Contract not found.', 'zeko-freelance' ) ), 404 );
		}

		$is_client = (int) $contract->client_id === $user_id;
		$is_worker = (int) $contract->freelancer_id === $user_id;
		if ( ! $is_client && ! $is_worker && ! user_can( $user_id, 'manage_options' ) ) {
			$this->respond( array( 'error' => __( 'Only the parties to this contract can leave a review.', 'zeko-freelance' ) ), 403 );
		}
		if ( 'completed' !== $contract->status ) {
			$this->respond( array( 'error' => __( 'Reviews can only be left after the contract is completed.', 'zeko-freelance' ) ), 400 );
		}

		$reviewee_id = $is_client ? (int) $contract->freelancer_id : (int) $contract->client_id;
		if ( ! $reviewee_id ) {
			$this->respond( array( 'error' => __( 'The other party on this contract no longer exists.', 'zeko-freelance' ) ), 400 );
		}

		$existing = $this->db->get_review_for_contract( $contract_id, $user_id );
		if ( $existing ) {
			$this->respond( array( 'error' => __( 'You already reviewed this contract.', 'zeko-freelance' ) ), 409 );
		}

		$rating = isset( $_POST['zf_review_rating'] ) ? absint( $_POST['zf_review_rating'] ) : 0;
		if ( $rating < 1 || $rating > 5 ) {
			$this->respond( array( 'error' => __( 'Rating must be between 1 and 5 stars.', 'zeko-freelance' ) ), 400 );
		}

		$comment = isset( $_POST['zf_review_comment'] ) ? sanitize_textarea_field( wp_unslash( $_POST['zf_review_comment'] ) ) : '';
		if ( mb_strlen( $comment ) > 1000 ) {
			$this->respond( array( 'error' => __( 'Review comment must be 1000 characters or fewer.', 'zeko-freelance' ) ), 400 );
		}

		$review_id = $this->db->insert_review(
			array(
				'contract_id' => $contract_id,
				'reviewer_id' => $user_id,
				'reviewee_id' => $reviewee_id,
				'rating'      => $rating,
				'comment'     => $comment,
			)
		);

		zeko_freelance()->get_emails()->notify_review_received( (int) $review_id );

		$this->respond(
			array(
				'ok'        => true,
				'review_id' => (int) $review_id,
				'rating'    => $rating,
				'message'   => __( 'Thank you! Your review has been published.', 'zeko-freelance' ),
			)
		);
	}

	// ═══════════════════════════════════════════════════════════════.
	// HELPERS.
	// ═══════════════════════════════════════════════════════════════.

	/**
	 * Can manage project.
	 *
	 * @param object $project Project.
	 * @param int    $user_id User id.
	 */
	private function can_manage_project( object $project, int $user_id ): bool {
		return (int) $project->user_id === $user_id || user_can( $user_id, 'manage_options' );
	}

	/**
	 * Handle a portfolio image upload through the WP media pipeline.
	 * Returns the attachment URL, or '' when the upload fails.
	 *
	 * @param int $user_id User id.
	 */
	private function handle_portfolio_image_upload( int $user_id ): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce verified by the calling AJAX handler (check_ajax_referer) before save; file re-validated via Zeko_Core_Upload + media_handle_upload below.
		if ( ! isset( $_FILES['zf_portfolio_image'] ) ) {
			return '';
		}

		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		if ( class_exists( 'Zeko_Core_Upload' ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- $_FILES validated via nonce-protected caller + Zeko_Core_Upload::validate_file immediately on this line.
			$valid = Zeko_Core_Upload::validate_file( $_FILES['zf_portfolio_image'], 'image' );
			if ( is_wp_error( $valid ) ) {
				return '';
			}
		}

		$attachment_id = media_handle_upload( 'zf_portfolio_image', 0, array(), array( 'test_form' => false ) );
		if ( is_wp_error( $attachment_id ) ) {
			return '';
		}

		$url = wp_get_attachment_url( (int) $attachment_id );
		if ( ! $url ) {
			wp_delete_attachment( (int) $attachment_id, true );
			return '';
		}

		update_post_meta( (int) $attachment_id, '_zeko_freelance_portfolio_owner', (int) $user_id );
		return (string) $url;
	}

	/**
	 * Can manage contract.
	 *
	 * @param object $contract Contract.
	 * @param int    $user_id User id.
	 */
	private function can_manage_contract( object $contract, int $user_id ): bool {
		return (int) $contract->client_id === $user_id
			|| (int) $contract->freelancer_id === $user_id
			|| user_can( $user_id, 'manage_options' );
	}

	/**
	 * Respond.
	 *
	 * @param array $payload Payload.
	 * @param int   $status Status.
	 * @throws Zeko_Freelance_Ajax_Halt When an error occurs.
	 */
	private function respond( array $payload, int $status = 200 ): void {
		if ( apply_filters( 'zeko_freelance_ajax_exit', true ) ) {
			wp_send_json( $payload, $status );
		}
		echo wp_json_encode( $payload ); // phpcs:ignore WordPress.Security.EscapeOutput
		throw new Zeko_Freelance_Ajax_Halt();
	}

	/**
	 * Nopriv fallback for guests.
	 */
	public function denied(): void {
		$this->respond( array( 'error' => __( 'You must be logged in.', 'zeko-freelance' ) ), 401 );
	}
}
