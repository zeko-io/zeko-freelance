<?php
/**
 * Zeko Freelance transactional emails.
 *
 * Lightweight HTML emails for project lifecycle events: new project
 * submitted/reviewed, project published, expiry reminders and expired
 * listings. No HTML mailer dependency — uses wp_mail with inline styles.
 *
 * @package Zeko_Freelance
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Class Zeko_Freelance_Emails. */
class Zeko_Freelance_Emails {

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
	}

	/**
	 * Send an HTML email, filtered so tests can suppress delivery.
	 *
	 * @return bool
	 * @param string $to Recipient address.
	 * @param string $subject Email subject.
	 * @param string $body HTML body (without <html>/<body>).
	 */
	public function send( string $to, string $subject, string $body ): bool {
		if ( ! is_email( $to ) || ! apply_filters( 'zeko_freelance_send_email', true ) || $this->is_demo_recipient( $to ) ) {
			return false;
		}

		$headers = array( 'Content-Type: text/html; charset=UTF-8' );
		$site    = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
		$home    = home_url();

		$html = '<!DOCTYPE html><html><body style="margin:0;padding:0;background:#f1f5f9;font-family:Segoe UI,Arial,sans-serif;">'
			. '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="padding:24px 12px;">'
			. '<tr><td align="center">'
			. '<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;">'
			. '<tr><td style="background:#4f46e5;padding:20px 28px;text-align:center;">'
			. '<p style="margin:0;font-size:18px;font-weight:700;color:#ffffff;">' . esc_html( $site ) . ' — Freelance</p>'
			. '<p style="margin:6px 0 0;font-size:12px;color:rgba(255,255,255,0.85);">' . esc_html__( 'Projects, bids, and payments in one place', 'zeko-freelance' ) . '</p>'
			. '</td></tr>'
			. '<tr><td style="padding:28px;color:#334155;font-size:14px;line-height:1.6;">'
			. $body
			. '</td></tr>'
			. '<tr><td style="padding:16px 28px;background:#f8fafc;border-top:1px solid #e2e8f0;color:#64748b;font-size:12px;">'
			. esc_html__( 'You received this email because of your activity on the Zeko freelance marketplace.', 'zeko-freelance' )
			. '</td></tr>'
			. '</table>'
			. '</td></tr></table>'
			. '</body></html>';

		return (bool) wp_mail( $to, $subject, $html, $headers );
	}

	/**
	 * Notify site admins when a new project lands in the moderation queue.
	 *
	 * @param int $project_id Project id.
	 */
	public function notify_admins_new_project( int $project_id ): void {
		$project = $this->db->get_project( $project_id );
		if ( ! $project ) {
			return;
		}

		$subject = sprintf(
			/* translators: %s: project title */
			__( 'New project awaiting review: %s', 'zeko-freelance' ),
			$project->title
		);

		$body = '<h2 style="margin:0 0 16px;font-size:18px;color:#0f172a;">' . esc_html__( 'New project submitted', 'zeko-freelance' ) . '</h2>'
			. '<p>' . esc_html(
				sprintf(
				/* translators: %1$s: project title, %2$s: client display name */
					__( '"%1$s" was submitted by %2$s and is waiting for moderation.', 'zeko-freelance' ),
					$project->title,
					get_the_author_meta( 'display_name', (int) $project->user_id )
				)
			) . '</p>'
			. '<p><a href="' . esc_url( admin_url( 'admin.php?page=zeko-freelance-projects' ) ) . '" style="display:inline-block;background:#4f46e5;color:#ffffff;padding:10px 18px;border-radius:8px;text-decoration:none;font-weight:600;">' . esc_html__( 'Review in admin', 'zeko-freelance' ) . '</a></p>';

		foreach ( $this->admin_emails() as $admin_email ) {
			$this->send( $admin_email, $subject, $body );
		}
	}

	/**
	 * Notify the client that their project is live on the marketplace.
	 *
	 * @param int $project_id Project id.
	 */
	public function notify_owner_published( int $project_id ): void {
		$project = $this->db->get_project( $project_id );
		if ( ! $project ) {
			return;
		}

		$subject = sprintf(
			/* translators: %s: project title */
			__( 'Your project is live: %s', 'zeko-freelance' ),
			$project->title
		);

		$body = '<h2 style="margin:0 0 16px;font-size:18px;color:#0f172a;">' . esc_html__( 'Your project is live', 'zeko-freelance' ) . '</h2>'
			. '<p>' . esc_html__( 'Your project has been published to the marketplace and freelancers can now submit proposals.', 'zeko-freelance' ) . '</p>'
			. '<p><a href="' . esc_url( $this->project_url( $project_id ) ) . '" style="display:inline-block;background:#4f46e5;color:#ffffff;padding:10px 18px;border-radius:8px;text-decoration:none;font-weight:600;">' . esc_html__( 'View project', 'zeko-freelance' ) . '</a></p>';

		$this->send( get_the_author_meta( 'user_email', (int) $project->user_id ), $subject, $body );
	}

	/**
	 * Remind the client their listing expires soon.
	 *
	 * @param int $project_id Project id.
	 */
	public function notify_expiring_soon( int $project_id ): void {
		$project = $this->db->get_project( $project_id );
		if ( ! $project ) {
			return;
		}

		$subject = sprintf(
			/* translators: %s: project title */
			__( 'Your project listing expires soon: %s', 'zeko-freelance' ),
			$project->title
		);

		$body = '<h2 style="margin:0 0 16px;font-size:18px;color:#0f172a;">' . esc_html__( 'Listing expiring soon', 'zeko-freelance' ) . '</h2>'
			. '<p>' . esc_html(
				sprintf(
				/* translators: %1$s: project title, %2$s: expiry date */
					__( '"%1$s" will be marked as expired on %2$s. Extend the listing or close the project before then.', 'zeko-freelance' ),
					$project->title,
					$this->format_date( $project->expires_at )
				)
			) . '</p>'
			. '<p><a href="' . esc_url( zeko_freelance_page_url( 'freelance' ) ) . '" style="display:inline-block;background:#4f46e5;color:#ffffff;padding:10px 18px;border-radius:8px;text-decoration:none;font-weight:600;">' . esc_html__( 'Open dashboard', 'zeko-freelance' ) . '</a></p>';

		$this->send( get_the_author_meta( 'user_email', (int) $project->user_id ), $subject, $body );
	}

	/**
	 * Tell the client their listing has been marked expired.
	 *
	 * @param int $project_id Project id.
	 */
	public function notify_expired( int $project_id ): void {
		$project = $this->db->get_project( $project_id );
		if ( ! $project ) {
			return;
		}

		$subject = sprintf(
			/* translators: %s: project title */
			__( 'Your project listing has expired: %s', 'zeko-freelance' ),
			$project->title
		);

		$body = '<h2 style="margin:0 0 16px;font-size:18px;color:#0f172a;">' . esc_html__( 'Listing expired', 'zeko-freelance' ) . '</h2>'
			. '<p>' . esc_html(
				sprintf(
				/* translators: %s: project title */
					__( '"%s" is no longer accepting proposals. You can reopen it anytime from your dashboard.', 'zeko-freelance' ),
					$project->title
				)
			) . '</p>'
			. '<p><a href="' . esc_url( zeko_freelance_page_url( 'freelance' ) ) . '" style="display:inline-block;background:#4f46e5;color:#ffffff;padding:10px 18px;border-radius:8px;text-decoration:none;font-weight:600;">' . esc_html__( 'Open dashboard', 'zeko-freelance' ) . '</a></p>';

		$this->send( get_the_author_meta( 'user_email', (int) $project->user_id ), $subject, $body );
	}

	/**
	 * Tell the client a freelancer just placed a bid on their project.
	 *
	 * @param int $project_id Project id.
	 * @param int $bid_id Bid id.
	 */
	public function notify_owner_new_bid( int $project_id, int $bid_id ): void {
		$project = $this->db->get_project( $project_id );
		$bid     = $this->db->get_bid( $bid_id );
		if ( ! $project || ! $bid ) {
			return;
		}

		$subject = sprintf(
			/* translators: %s: project title */
			__( 'New bid on your project: %s', 'zeko-freelance' ),
			$project->title
		);

		$body = '<h2 style="margin:0 0 16px;font-size:18px;color:#0f172a;">' . esc_html__( 'You have a new bid', 'zeko-freelance' ) . '</h2>'
			. '<p>' . esc_html(
				sprintf(
				/* translators: %1$s: freelancer name, %2$s: amount, %3$s: currency, %4$d: delivery days */
					__( '%1$s bid %2$s %3$s with a %4$d day delivery.', 'zeko-freelance' ),
					get_the_author_meta( 'display_name', (int) $bid->user_id ),
					number_format_i18n( (float) $bid->amount, 2 ),
					(string) $project->currency,
					(int) $bid->delivery_days
				)
			) . '</p>'
			. '<p><a href="' . esc_url( $this->project_url( $project_id ) ) . '" style="display:inline-block;background:#4f46e5;color:#ffffff;padding:10px 18px;border-radius:8px;text-decoration:none;font-weight:600;">' . esc_html__( 'Review bids', 'zeko-freelance' ) . '</a></p>';

		$this->send( get_the_author_meta( 'user_email', (int) $project->user_id ), $subject, $body );
	}

	/**
	 * Tell a freelancer their bid is no longer the lowest on a project.
	 *
	 * @param int $bid_id Bid id.
	 */
	public function notify_freelancer_outbid( int $bid_id ): void {
		$bid     = $this->db->get_bid( $bid_id );
		$project = $bid ? $this->db->get_project( (int) $bid->project_id ) : null;
		if ( ! $bid || ! $project ) {
			return;
		}

		$subject = sprintf(
			/* translators: %s: project title */
			__( 'You have been outbid on %s', 'zeko-freelance' ),
			$project->title
		);

		$body = '<h2 style="margin:0 0 16px;font-size:18px;color:#0f172a;">' . esc_html__( 'You have been outbid', 'zeko-freelance' ) . '</h2>'
			. '<p>' . esc_html(
				sprintf(
				/* translators: %s: project title */
					__( 'A freelancer placed a lower bid on "%s". Update your bid to stay competitive.', 'zeko-freelance' ),
					$project->title
				)
			) . '</p>'
			. '<p><a href="' . esc_url( $this->project_url( (int) $project->id ) ) . '" style="display:inline-block;background:#4f46e5;color:#ffffff;padding:10px 18px;border-radius:8px;text-decoration:none;font-weight:600;">' . esc_html__( 'View project', 'zeko-freelance' ) . '</a></p>';

		$this->send( get_the_author_meta( 'user_email', (int) $bid->user_id ), $subject, $body );
	}

	/**
	 * Tell a freelancer their bid was accepted and a contract was created.
	 *
	 * @param int $bid_id Bid id.
	 */
	public function notify_freelancer_awarded( int $bid_id ): void {
		$bid     = $this->db->get_bid( $bid_id );
		$project = $bid ? $this->db->get_project( (int) $bid->project_id ) : null;
		if ( ! $bid || ! $project ) {
			return;
		}

		$subject = sprintf(
			/* translators: %s: project title */
			__( 'You won the project: %s', 'zeko-freelance' ),
			$project->title
		);

		$body = '<h2 style="margin:0 0 16px;font-size:18px;color:#0f172a;">' . esc_html__( 'Your bid was accepted', 'zeko-freelance' ) . '</h2>'
			. '<p>' . esc_html(
				sprintf(
				/* translators: %1$s: project title, %2$s: amount, %3$s: currency */
					__( 'The client accepted your bid of %2$s %3$s on "%1$s". A contract has been created — check your dashboard to get started.', 'zeko-freelance' ),
					$project->title,
					number_format_i18n( (float) $bid->amount, 2 ),
					(string) $project->currency
				)
			) . '</p>'
			. '<p><a href="' . esc_url( zeko_freelance_page_url( 'freelance' ) ) . '" style="display:inline-block;background:#4f46e5;color:#ffffff;padding:10px 18px;border-radius:8px;text-decoration:none;font-weight:600;">' . esc_html__( 'Open dashboard', 'zeko-freelance' ) . '</a></p>';

		$this->send( get_the_author_meta( 'user_email', (int) $bid->user_id ), $subject, $body );
	}

	/**
	 * Tell the client their milestone payment was pulled into escrow.
	 *
	 * @param int $contract_id Contract id.
	 * @param int $milestone_id Milestone id.
	 */
	public function notify_client_milestone_funded( int $contract_id, int $milestone_id ): void {
		$milestone = $this->db->get_milestone( $milestone_id );
		$contract  = $milestone ? $this->db->get_contract( (int) $milestone->contract_id ) : null;
		if ( ! $milestone || ! $contract ) {
			return;
		}

		$subject = sprintf(
			/* translators: %s: milestone title */
			__( 'Milestone funded: %s', 'zeko-freelance' ),
			$milestone->title
		);

		$body = '<h2 style="margin:0 0 16px;font-size:18px;color:#0f172a;">' . esc_html__( 'Milestone funded', 'zeko-freelance' ) . '</h2>'
			. '<p>' . esc_html(
				sprintf(
				/* translators: %1$s: milestone title, %2$s: amount, %3$s: currency */
					__( '"%1$s" is now funded — %2$s %3$s is being held in escrow until the work is approved.', 'zeko-freelance' ),
					$milestone->title,
					number_format_i18n( (float) $milestone->amount, 2 ),
					(string) $contract->currency
				)
			) . '</p>'
			. '<p><a href="' . esc_url( $this->contract_url( (int) $contract->project_id ) ) . '" style="display:inline-block;background:#4f46e5;color:#ffffff;padding:10px 18px;border-radius:8px;text-decoration:none;font-weight:600;">' . esc_html__( 'View contract', 'zeko-freelance' ) . '</a></p>';

		$this->send( get_the_author_meta( 'user_email', (int) $contract->client_id ), $subject, $body );
	}

	/**
	 * Tell the freelancer a milestone is funded and ready to work on.
	 *
	 * @param int $contract_id Contract id.
	 * @param int $milestone_id Milestone id.
	 */
	public function notify_freelancer_milestone_funded( int $contract_id, int $milestone_id ): void {
		$milestone = $this->db->get_milestone( $milestone_id );
		$contract  = $milestone ? $this->db->get_contract( (int) $milestone->contract_id ) : null;
		if ( ! $milestone || ! $contract ) {
			return;
		}

		$subject = sprintf(
			/* translators: %s: milestone title */
			__( 'Milestone ready to start: %s', 'zeko-freelance' ),
			$milestone->title
		);

		$body = '<h2 style="margin:0 0 16px;font-size:18px;color:#0f172a;">' . esc_html__( 'Milestone funded', 'zeko-freelance' ) . '</h2>'
			. '<p>' . esc_html(
				sprintf(
				/* translators: %1$s: milestone title, %2$s: amount, %3$s: currency */
					__( 'The client funded "%1$s" (%2$s %3$s). The payment is in escrow — submit your work for review when it is done.', 'zeko-freelance' ),
					$milestone->title,
					number_format_i18n( (float) $milestone->amount, 2 ),
					(string) $contract->currency
				)
			) . '</p>'
			. '<p><a href="' . esc_url( $this->contract_url( (int) $contract->project_id ) ) . '" style="display:inline-block;background:#4f46e5;color:#ffffff;padding:10px 18px;border-radius:8px;text-decoration:none;font-weight:600;">' . esc_html__( 'View contract', 'zeko-freelance' ) . '</a></p>';

		$this->send( get_the_author_meta( 'user_email', (int) $contract->freelancer_id ), $subject, $body );
	}

	/**
	 * Tell the client a milestone was submitted for review.
	 *
	 * @param int $contract_id Contract id.
	 * @param int $milestone_id Milestone id.
	 */
	public function notify_client_milestone_submitted( int $contract_id, int $milestone_id ): void {
		$milestone = $this->db->get_milestone( $milestone_id );
		$contract  = $milestone ? $this->db->get_contract( (int) $milestone->contract_id ) : null;
		if ( ! $milestone || ! $contract ) {
			return;
		}

		$subject = sprintf(
			/* translators: %s: milestone title */
			__( 'Milestone submitted for review: %s', 'zeko-freelance' ),
			$milestone->title
		);

		$body = '<h2 style="margin:0 0 16px;font-size:18px;color:#0f172a;">' . esc_html__( 'Milestone awaiting your review', 'zeko-freelance' ) . '</h2>'
			. '<p>' . esc_html(
				sprintf(
				/* translators: %s: milestone title */
					__( 'The freelancer submitted "%s" for review. Approve it to release the escrowed payment, or send it back for revisions.', 'zeko-freelance' ),
					$milestone->title
				)
			) . '</p>'
			. '<p><a href="' . esc_url( $this->contract_url( (int) $contract->project_id ) ) . '" style="display:inline-block;background:#4f46e5;color:#ffffff;padding:10px 18px;border-radius:8px;text-decoration:none;font-weight:600;">' . esc_html__( 'Review milestone', 'zeko-freelance' ) . '</a></p>';

		$this->send( get_the_author_meta( 'user_email', (int) $contract->client_id ), $subject, $body );
	}

	/**
	 * Tell the freelancer their milestone was approved and paid.
	 *
	 * @param int $contract_id Contract id.
	 * @param int $milestone_id Milestone id.
	 */
	public function notify_freelancer_milestone_approved( int $contract_id, int $milestone_id ): void {
		$milestone = $this->db->get_milestone( $milestone_id );
		$contract  = $milestone ? $this->db->get_contract( (int) $milestone->contract_id ) : null;
		if ( ! $milestone || ! $contract ) {
			return;
		}

		$subject = sprintf(
			/* translators: %s: milestone title */
			__( 'Milestone approved: %s', 'zeko-freelance' ),
			$milestone->title
		);

		$body = '<h2 style="margin:0 0 16px;font-size:18px;color:#0f172a;">' . esc_html__( 'Milestone approved and paid', 'zeko-freelance' ) . '</h2>'
			. '<p>' . esc_html(
				sprintf(
				/* translators: %1$s: milestone title, %2$s: amount, %3$s: currency */
					__( '"%1$s" was approved. %2$s %3$s has been released to your wallet.', 'zeko-freelance' ),
					$milestone->title,
					number_format_i18n( (float) $milestone->amount, 2 ),
					(string) $contract->currency
				)
			) . '</p>'
			. '<p><a href="' . esc_url( zeko_freelance_page_url( 'freelance' ) ) . '" style="display:inline-block;background:#4f46e5;color:#ffffff;padding:10px 18px;border-radius:8px;text-decoration:none;font-weight:600;">' . esc_html__( 'Open dashboard', 'zeko-freelance' ) . '</a></p>';

		$this->send( get_the_author_meta( 'user_email', (int) $contract->freelancer_id ), $subject, $body );
	}

	/**
	 * Tell the freelancer their milestone was sent back for revisions.
	 *
	 * @param int $contract_id Contract id.
	 * @param int $milestone_id Milestone id.
	 */
	public function notify_freelancer_milestone_rejected( int $contract_id, int $milestone_id ): void {
		$milestone = $this->db->get_milestone( $milestone_id );
		$contract  = $milestone ? $this->db->get_contract( (int) $milestone->contract_id ) : null;
		if ( ! $milestone || ! $contract ) {
			return;
		}

		$subject = sprintf(
			/* translators: %s: milestone title */
			__( 'Milestone sent back: %s', 'zeko-freelance' ),
			$milestone->title
		);

		$body = '<h2 style="margin:0 0 16px;font-size:18px;color:#0f172a;">' . esc_html__( 'Milestone sent back', 'zeko-freelance' ) . '</h2>'
			. '<p>' . esc_html(
				sprintf(
				/* translators: %s: milestone title */
					__( 'The client requested revisions on "%s". The payment stays in escrow — address the feedback and resubmit when ready.', 'zeko-freelance' ),
					$milestone->title
				)
			) . '</p>'
			. '<p><a href="' . esc_url( $this->contract_url( (int) $contract->project_id ) ) . '" style="display:inline-block;background:#4f46e5;color:#ffffff;padding:10px 18px;border-radius:8px;text-decoration:none;font-weight:600;">' . esc_html__( 'View contract', 'zeko-freelance' ) . '</a></p>';

		$this->send( get_the_author_meta( 'user_email', (int) $contract->freelancer_id ), $subject, $body );
	}

	/**
	 * Remind the client that a submitted milestone is approaching its review
	 * SLA deadline (auto-approved if no action is taken).
	 *
	 * @param int $contract_id Contract id.
	 * @param int $milestone_id Milestone id.
	 */
	public function notify_client_milestone_review_due( int $contract_id, int $milestone_id ): void {
		$milestone = $this->db->get_milestone( $milestone_id );
		$contract  = $milestone ? $this->db->get_contract( (int) $milestone->contract_id ) : null;
		if ( ! $milestone || ! $contract ) {
			return;
		}

		$subject = sprintf(
			/* translators: %s: milestone title */
			__( 'Review soon: %s', 'zeko-freelance' ),
			$milestone->title
		);

		$body = '<h2 style="margin:0 0 16px;font-size:18px;color:#0f172a;">' . esc_html__( 'Milestone review due', 'zeko-freelance' ) . '</h2>'
			. '<p>' . esc_html(
				sprintf(
				/* translators: %s: milestone title */
					__( '"%s" is awaiting your review. If you take no action before the deadline, the milestone will be auto-approved and the escrowed payment released.', 'zeko-freelance' ),
					$milestone->title
				)
			) . '</p>'
			. '<p><a href="' . esc_url( $this->contract_url( (int) $contract->project_id ) ) . '" style="display:inline-block;background:#4f46e5;color:#ffffff;padding:10px 18px;border-radius:8px;text-decoration:none;font-weight:600;">' . esc_html__( 'Review milestone', 'zeko-freelance' ) . '</a></p>';

		$this->send( get_the_author_meta( 'user_email', (int) $contract->client_id ), $subject, $body );
	}

	/**
	 * Tell the client their in-review milestone was auto-approved because the
	 * review SLA elapsed without action.
	 *
	 * @param int $contract_id Contract id.
	 * @param int $milestone_id Milestone id.
	 */
	public function notify_client_milestone_auto_approved( int $contract_id, int $milestone_id ): void {
		$milestone = $this->db->get_milestone( $milestone_id );
		$contract  = $milestone ? $this->db->get_contract( (int) $milestone->contract_id ) : null;
		if ( ! $milestone || ! $contract ) {
			return;
		}

		$subject = sprintf(
			/* translators: %s: milestone title */
			__( 'Auto-approved: %s', 'zeko-freelance' ),
			$milestone->title
		);

		$body = '<h2 style="margin:0 0 16px;font-size:18px;color:#0f172a;">' . esc_html__( 'Milestone auto-approved', 'zeko-freelance' ) . '</h2>'
			. '<p>' . esc_html(
				sprintf(
				/* translators: %1$s: milestone title, %2$s: amount, %3$s: currency */
					__( '"%1$s" was approved automatically because no review action was taken within the SLA window. %2$s %3$s has been released to the freelancer.', 'zeko-freelance' ),
					$milestone->title,
					number_format_i18n( (float) $milestone->amount, 2 ),
					(string) $contract->currency
				)
			) . '</p>';

		$this->send( get_the_author_meta( 'user_email', (int) $contract->client_id ), $subject, $body );
	}

	/**
	 * Tell the freelancer their submitted milestone was auto-approved and paid.
	 *
	 * @param int $contract_id Contract id.
	 * @param int $milestone_id Milestone id.
	 */
	public function notify_freelancer_milestone_auto_approved( int $contract_id, int $milestone_id ): void {
		$milestone = $this->db->get_milestone( $milestone_id );
		$contract  = $milestone ? $this->db->get_contract( (int) $milestone->contract_id ) : null;
		if ( ! $milestone || ! $contract ) {
			return;
		}

		$subject = sprintf(
			/* translators: %s: milestone title */
			__( 'Milestone paid: %s', 'zeko-freelance' ),
			$milestone->title
		);

		$body = '<h2 style="margin:0 0 16px;font-size:18px;color:#0f172a;">' . esc_html__( 'Milestone auto-approved and paid', 'zeko-freelance' ) . '</h2>'
			. '<p>' . esc_html(
				sprintf(
				/* translators: %1$s: milestone title, %2$s: amount, %3$s: currency */
					__( '"%1$s" was approved automatically after the review window elapsed. %2$s %3$s has been released to your wallet.', 'zeko-freelance' ),
					$milestone->title,
					number_format_i18n( (float) $milestone->amount, 2 ),
					(string) $contract->currency
				)
			) . '</p>';

		$this->send( get_the_author_meta( 'user_email', (int) $contract->freelancer_id ), $subject, $body );
	}

	/**
	 * Remind the freelancer that a funded milestone is approaching its
	 * submission SLA deadline (escalated to a dispute if never submitted).
	 *
	 * @param int $contract_id Contract id.
	 * @param int $milestone_id Milestone id.
	 */
	public function notify_freelancer_milestone_submission_due( int $contract_id, int $milestone_id ): void {
		$milestone = $this->db->get_milestone( $milestone_id );
		$contract  = $milestone ? $this->db->get_contract( (int) $milestone->contract_id ) : null;
		if ( ! $milestone || ! $contract ) {
			return;
		}

		$subject = sprintf(
			/* translators: %s: milestone title */
			__( 'Submission due soon: %s', 'zeko-freelance' ),
			$milestone->title
		);

		$body = '<h2 style="margin:0 0 16px;font-size:18px;color:#0f172a;">' . esc_html__( 'Milestone submission due', 'zeko-freelance' ) . '</h2>'
			. '<p>' . esc_html(
				sprintf(
				/* translators: %s: milestone title */
					__( 'The funded milestone "%s" has not been submitted yet. Submit it before the deadline, or the contract may be escalated to a dispute.', 'zeko-freelance' ),
					$milestone->title
				)
			) . '</p>'
			. '<p><a href="' . esc_url( $this->contract_url( (int) $contract->project_id ) ) . '" style="display:inline-block;background:#4f46e5;color:#ffffff;padding:10px 18px;border-radius:8px;text-decoration:none;font-weight:600;">' . esc_html__( 'View contract', 'zeko-freelance' ) . '</a></p>';

		$this->send( get_the_author_meta( 'user_email', (int) $contract->freelancer_id ), $subject, $body );
	}

	/**
	 * Tell both parties the contract was cancelled.
	 *
	 * @param int $contract_id Contract id.
	 */
	public function notify_contract_cancelled( int $contract_id ): void {
		$contract = $this->db->get_contract( $contract_id );
		if ( ! $contract ) {
			return;
		}

		$subject = sprintf(
			/* translators: %d: contract id */
			__( 'Contract #%d has been cancelled', 'zeko-freelance' ),
			$contract_id
		);

		$body = '<h2 style="margin:0 0 16px;font-size:18px;color:#0f172a;">' . esc_html__( 'Contract cancelled', 'zeko-freelance' ) . '</h2>'
			. '<p>' . esc_html__( 'The contract has been cancelled. Any funds held in escrow have been refunded to the client.', 'zeko-freelance' ) . '</p>'
			. '<p><a href="' . esc_url( zeko_freelance_page_url( 'freelance' ) ) . '" style="display:inline-block;background:#4f46e5;color:#ffffff;padding:10px 18px;border-radius:8px;text-decoration:none;font-weight:600;">' . esc_html__( 'Open dashboard', 'zeko-freelance' ) . '</a></p>';

		$this->send( get_the_author_meta( 'user_email', (int) $contract->client_id ), $subject, $body );
		$this->send( get_the_author_meta( 'user_email', (int) $contract->freelancer_id ), $subject, $body );
	}

	/**
	 * Tell both parties the contract was completed.
	 *
	 * @param int $contract_id Contract id.
	 */
	public function notify_contract_completed( int $contract_id ): void {
		$contract = $this->db->get_contract( $contract_id );
		if ( ! $contract ) {
			return;
		}

		$subject = sprintf(
			/* translators: %d: contract id */
			__( 'Contract #%d completed', 'zeko-freelance' ),
			$contract_id
		);

		$body = '<h2 style="margin:0 0 16px;font-size:18px;color:#0f172a;">' . esc_html__( 'Contract completed', 'zeko-freelance' ) . '</h2>'
			. '<p>' . esc_html__( 'All milestones are paid and the contract is complete. Thanks for using Zeko!', 'zeko-freelance' ) . '</p>'
			. '<p><a href="' . esc_url( zeko_freelance_page_url( 'freelance' ) ) . '" style="display:inline-block;background:#4f46e5;color:#ffffff;padding:10px 18px;border-radius:8px;text-decoration:none;font-weight:600;">' . esc_html__( 'Open dashboard', 'zeko-freelance' ) . '</a></p>';

		$this->send( get_the_author_meta( 'user_email', (int) $contract->client_id ), $subject, $body );
		$this->send( get_the_author_meta( 'user_email', (int) $contract->freelancer_id ), $subject, $body );
	}

	/**
	 * Notify site admins a dispute was opened on a contract.
	 *
	 * @param int $dispute_id Dispute id.
	 */
	public function notify_admins_dispute_opened( int $dispute_id ): void {
		$dispute  = $this->db->get_dispute( $dispute_id );
		$contract = $dispute ? $this->db->get_contract( (int) $dispute->contract_id ) : null;
		if ( ! $dispute || ! $contract ) {
			return;
		}

		$subject = sprintf(
			/* translators: %d: dispute id */
			__( 'New dispute #%1$d on contract #%2$d', 'zeko-freelance' ),
			$dispute_id,
			(int) $dispute->contract_id
		);

		$body = '<h2 style="margin:0 0 16px;font-size:18px;color:#0f172a;">' . esc_html__( 'A dispute was opened', 'zeko-freelance' ) . '</h2>'
			. '<p><strong>' . esc_html( $dispute->subject ) . '</strong></p>'
			. '<p>' . nl2br( esc_html( $dispute->description ) ) . '</p>'
			. '<p><a href="' . esc_url( admin_url( 'admin.php?page=zeko-freelance-disputes' ) ) . '" style="display:inline-block;background:#4f46e5;color:#ffffff;padding:10px 18px;border-radius:8px;text-decoration:none;font-weight:600;">' . esc_html__( 'Resolve in admin', 'zeko-freelance' ) . '</a></p>';

		foreach ( $this->admin_emails() as $admin_email ) {
			$this->send( $admin_email, $subject, $body );
		}
	}

	/**
	 * Tell both parties how a dispute was resolved.
	 *
	 * @param int $dispute_id Dispute id.
	 */
	public function notify_dispute_resolved( int $dispute_id ): void {
		$dispute  = $this->db->get_dispute( $dispute_id );
		$contract = $dispute ? $this->db->get_contract( (int) $dispute->contract_id ) : null;
		if ( ! $dispute || ! $contract ) {
			return;
		}

		$subject = sprintf(
			/* translators: %d: dispute id */
			__( 'Dispute #%d resolved', 'zeko-freelance' ),
			$dispute_id
		);

		$body = '<h2 style="margin:0 0 16px;font-size:18px;color:#0f172a;">' . esc_html__( 'Dispute resolved', 'zeko-freelance' ) . '</h2>'
			. '<p>' . esc_html(
				sprintf(
				/* translators: %1$s: dispute subject, %2$s: resolution note */
					__( '"%1$s" has been resolved by an administrator.', 'zeko-freelance' ),
					$dispute->subject
				)
			) . '</p>'
			. ( $dispute->resolution
				? '<p><strong>' . esc_html__( 'Resolution:', 'zeko-freelance' ) . '</strong> ' . nl2br( esc_html( $dispute->resolution ) ) . '</p>'
				: '' )
			. '<p><a href="' . esc_url( zeko_freelance_page_url( 'freelance' ) ) . '" style="display:inline-block;background:#4f46e5;color:#ffffff;padding:10px 18px;border-radius:8px;text-decoration:none;font-weight:600;">' . esc_html__( 'Open dashboard', 'zeko-freelance' ) . '</a></p>';

		$this->send( get_the_author_meta( 'user_email', (int) $contract->client_id ), $subject, $body );
		$this->send( get_the_author_meta( 'user_email', (int) $contract->freelancer_id ), $subject, $body );
	}

	/**
	 * Notify site admins that a freelancer applied for verification.
	 *
	 * @param int $verification_id Verification id.
	 */
	public function notify_admins_verification_applied( int $verification_id ): void {
		$verification = $this->db->get_verification( $verification_id );
		if ( ! $verification ) {
			return;
		}

		$subject = sprintf(
			/* translators: %s: user display name */
			__( 'New verification request from %s', 'zeko-freelance' ),
			get_the_author_meta( 'display_name', (int) $verification->user_id )
		);

		$body = '<h2 style="margin:0 0 16px;font-size:18px;color:#0f172a;">' . esc_html__( 'Verification requested', 'zeko-freelance' ) . '</h2>'
			. '<p>' . esc_html(
				sprintf(
				/* translators: %1$s: user display name, %2$s: verification type */
					__( '%1$s requested %2$s verification and is waiting for approval.', 'zeko-freelance' ),
					get_the_author_meta( 'display_name', (int) $verification->user_id ),
					'id' === $verification->type ? __( 'ID', 'zeko-freelance' ) : __( 'business', 'zeko-freelance' )
				)
			) . '</p>'
			. '<p><a href="' . esc_url( admin_url( 'admin.php?page=zeko-freelance-verifications' ) ) . '" style="display:inline-block;background:#4f46e5;color:#ffffff;padding:10px 18px;border-radius:8px;text-decoration:none;font-weight:600;">' . esc_html__( 'Review in admin', 'zeko-freelance' ) . '</a></p>';

		foreach ( $this->admin_emails() as $admin_email ) {
			$this->send( $admin_email, $subject, $body );
		}
	}

	/**
	 * Tell a user they received a new review.
	 *
	 * @param int $review_id Review id.
	 */
	public function notify_review_received( int $review_id ): void {
		$review = $this->db->get_review( $review_id );
		if ( ! $review ) {
			return;
		}

		$subject = sprintf(
			/* translators: %d: star rating */
			__( 'You received a %d-star review', 'zeko-freelance' ),
			(int) $review->rating
		);

		$body = '<h2 style="margin:0 0 16px;font-size:18px;color:#0f172a;">' . esc_html__( 'New review', 'zeko-freelance' ) . '</h2>'
			. '<p>' . esc_html(
				sprintf(
				/* translators: %1$s: reviewer display name, %2$d: star rating */
					__( '%1$s rated your work %2$d out of 5 stars.', 'zeko-freelance' ),
					get_the_author_meta( 'display_name', (int) $review->reviewer_id ),
					(int) $review->rating
				)
			) . '</p>'
			. ( $review->comment
				? '<blockquote style="margin:12px 0;padding:12px 16px;background:#f8fafc;border-left:3px solid #4f46e5;color:#334155;">' . esc_html( $review->comment ) . '</blockquote>'
				: '' )
			. '<p><a href="' . esc_url( add_query_arg( 'zf_uid', (int) $review->reviewee_id, zeko_freelance_page_url( 'freelance-profile' ) ) ) . '" style="display:inline-block;background:#4f46e5;color:#ffffff;padding:10px 18px;border-radius:8px;text-decoration:none;font-weight:600;">' . esc_html__( 'View your profile', 'zeko-freelance' ) . '</a></p>';

		$this->send( get_the_author_meta( 'user_email', (int) $review->reviewee_id ), $subject, $body );
	}

	// ═══════════════════════════════════════════════════════════════.
	// HELPERS.
	// ═══════════════════════════════════════════════════════════════.

	/**
	 * Whether a recipient is a demo account: their mailbox is on the demo
	 * domain or the address belongs to a user tagged `zeko_demo_user`.
	 * Demo notifications are suppressed so no real mail is dispatched to
	 * (or for) seeded demo users.
	 *
	 * @param string $to To.
	 */
	private function is_demo_recipient( string $to ): bool {
		$at = strrpos( $to, '@' );
		if ( false !== $at ) {
			$domain = strtolower( trim( substr( $to, $at + 1 ) ) );
			if ( 'zeko.test' === $domain ) {
				return true;
			}
		}

		$user = get_user_by( 'email', $to );
		return $user instanceof WP_User && zeko_freelance_is_demo_user( (int) $user->ID );
	}

	/**
	 * Project url.
	 *
	 * @param int $project_id Project id.
	 */
	private function project_url( int $project_id ): string {
		return add_query_arg( 'zf_pid', $project_id, zeko_freelance_page_url( 'freelance-project' ) );
	}

	/**
	 * Contract url.
	 *
	 * @param int $project_id Project id.
	 */
	private function contract_url( int $project_id ): string {
		return $this->project_url( $project_id );
	}

	/**
	 * Format date.
	 *
	 * @param ?string $date Date.
	 */
	private function format_date( ?string $date ): string {
		return $date ? mysql2date( 'M j, Y', $date ) : '—';
	}

	/**
	 * All admin notification email addresses (deduplicated).
	 *
	 * @return string[]
	 */
	private function admin_emails(): array {
		$emails   = array();
		$admin_id = 1;

		if ( get_userdata( $admin_id ) ) {
			$emails[] = get_the_author_meta( 'user_email', $admin_id );
		}

		$admins = get_users(
			array(
				'role__in' => array( 'administrator' ),
				'fields'   => array( 'ID' ),
			)
		);
		foreach ( $admins as $admin ) {
			$email = get_the_author_meta( 'user_email', (int) $admin->ID );
			if ( $email ) {
				$emails[] = $email;
			}
		}

		return array_values( array_unique( $emails ) );
	}
}
