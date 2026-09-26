<?php
/**
 * Zeko Freelance milestone SLA timers.
 *
 * Runs on the daily cron and enforces the two milestone deadlines:
 *   - review SLA: submitted milestones auto-approve (and escrow is released)
 *     when the client does not review them before the deadline.
 *   - submission SLA: funded milestones that are never submitted by the
 *     deadline are escalated into a dispute.
 *
 * Reminder emails are sent ahead of each deadline.
 *
 * @package Zeko_Freelance
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Class Zeko_Freelance_SLA. */
class Zeko_Freelance_SLA {

	/**
	 * Db.
	 *
	 * @var Zeko_Freelance_DB Db.
	 */
	private Zeko_Freelance_DB $db;
	/**
	 * Emails.
	 *
	 * @var Zeko_Freelance_Emails Emails.
	 */
	private Zeko_Freelance_Emails $emails;

	private const CRON_HOOK = 'zeko_freelance_daily';
	private const LOCK_KEY  = 'zeko_freelance_sla_lock';

	/**
	 * Construct.
	 *
	 * @param Zeko_Freelance_DB     $db Db.
	 * @param Zeko_Freelance_Emails $emails Emails.
	 */
	public function __construct( Zeko_Freelance_DB $db, Zeko_Freelance_Emails $emails ) {
		$this->db     = $db;
		$this->emails = $emails;

		add_action( 'init', array( $this, 'ensure_cron' ), 20 );
		add_action( self::CRON_HOOK, array( $this, 'run_daily' ) );
	}

	/**
	 * Ensure cron.
	 */
	public function ensure_cron(): void {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::CRON_HOOK );
		}
	}

	/**
	 * Clear cron.
	 */
	public function clear_cron(): void {
		$timestamp = wp_next_scheduled( self::CRON_HOOK );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::CRON_HOOK );
		}
	}

	/**
	 * Entry point for the daily cron. A transient guard prevents parallel
	 * (or overlapping) cron runs from processing the same milestones twice.
	 */
	public function run_daily(): void {
		if ( get_transient( self::LOCK_KEY ) ) {
			return;
		}
		set_transient( self::LOCK_KEY, 1, 30 * MINUTE_IN_SECONDS );

		$this->process_review_slas();
		$this->process_submission_slas();
		$this->send_review_reminders();
		$this->send_submission_reminders();

		delete_transient( self::LOCK_KEY );
	}

	/**
	 * Auto-approve in-review milestones whose review window has elapsed.
	 *
	 * @return int Number of milestones auto-approved.
	 */
	public function process_review_slas(): int {
		$settings = zeko_freelance_get_settings();
		if ( empty( $settings['auto_approve'] ) ) {
			return 0;
		}

		$approved = 0;
		foreach ( $this->db->get_review_overdue_milestones() as $milestone ) {
			$contract = $this->db->get_contract( (int) $milestone->contract_id );
			if ( ! $contract || 'disputed' === $contract->status ) {
				continue;
			}

			$result = $this->db->approve_milestone( (int) $milestone->id );
			if ( empty( $result['success'] ) ) {
				continue;
			}

			$this->emails->notify_client_milestone_auto_approved( (int) $contract->id, (int) $milestone->id );
			$this->emails->notify_freelancer_milestone_auto_approved( (int) $contract->id, (int) $milestone->id );
			do_action( 'zeko_freelance_milestone_auto_approved', (int) $milestone->id, (int) $contract->id );
			++$approved;
		}

		return $approved;
	}

	/**
	 * Escalate funded milestones whose submission window has elapsed into a
	 * dispute (raised by the freelancer account on their own contract).
	 *
	 * @return int Number of disputes opened.
	 */
	public function process_submission_slas(): int {
		$settings = zeko_freelance_get_settings();
		if ( empty( $settings['auto_dispute'] ) ) {
			return 0;
		}

		$opened = 0;
		foreach ( $this->db->get_submission_overdue_milestones() as $milestone ) {
			$contract = $this->db->get_contract( (int) $milestone->contract_id );
			if ( ! $contract ) {
				continue;
			}

			$subject = sprintf(
				/* translators: %s: milestone title */
				__( 'Milestone submission overdue: %s', 'zeko-freelance' ),
				$milestone->title
			);
			$description = sprintf(
				/* translators: %1$s: milestone title */
				__( 'This milestone was funded but never submitted within the SLA window. Escrow held for "%1$s" needs admin review.', 'zeko-freelance' ),
				$milestone->title
			);

			$dispute_id = $this->db->open_dispute( (int) $contract->id, (int) $contract->freelancer_id, $subject, $description );
			if ( $dispute_id > 0 ) {
				$this->emails->notify_admins_dispute_opened( $dispute_id );
				do_action( 'zeko_freelance_milestone_auto_disputed', (int) $milestone->id, (int) $contract->id, $dispute_id );
				++$opened;
			}
		}

		return $opened;
	}

	/**
	 * Email clients about in-review milestones approaching their review
	 * deadline. Each milestone is reminded at most once.
	 *
	 * @return int Number of reminders sent.
	 */
	public function send_review_reminders(): int {
		$settings = zeko_freelance_get_settings();
		$hours    = max( 1, (int) $settings['review_reminder_hours'] );

		$now = time();
		$end = date( 'Y-m-d H:i:s', $now + $hours * HOUR_IN_SECONDS ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date

		$sent = 0;
		foreach ( $this->db->get_review_due_soon( current_time( 'mysql' ), $end ) as $milestone ) {
			$contract = $this->db->get_contract( (int) $milestone->contract_id );
			if ( ! $contract || 'disputed' === $contract->status ) {
				continue;
			}

			$this->emails->notify_client_milestone_review_due( (int) $contract->id, (int) $milestone->id );
			$this->db->update_milestone(
				(int) $milestone->id,
				array(
					'review_reminded_at' => current_time( 'mysql' ),
				)
			);
			++$sent;
		}

		return $sent;
	}

	/**
	 * Email freelancers about funded milestones approaching their submission
	 * deadline. Each milestone is reminded at most once.
	 *
	 * @return int Number of reminders sent.
	 */
	public function send_submission_reminders(): int {
		$settings = zeko_freelance_get_settings();
		$hours    = max( 1, (int) $settings['submit_reminder_hours'] );

		$now = time();
		$end = date( 'Y-m-d H:i:s', $now + $hours * HOUR_IN_SECONDS ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date

		$sent = 0;
		foreach ( $this->db->get_submission_due_soon( current_time( 'mysql' ), $end ) as $milestone ) {
			$contract = $this->db->get_contract( (int) $milestone->contract_id );
			if ( ! $contract || 'disputed' === $contract->status ) {
				continue;
			}

			$this->emails->notify_freelancer_milestone_submission_due( (int) $contract->id, (int) $milestone->id );
			$this->db->update_milestone(
				(int) $milestone->id,
				array(
					'submit_reminded_at' => current_time( 'mysql' ),
				)
			);
			++$sent;
		}

		return $sent;
	}
}
