<?php
/**
 * Zeko Freelance daily maintenance cron.
 *
 * Marks open projects whose listing period has elapsed as expired and sends
 * expiry reminders ahead of the deadline.
 *
 * @package Zeko_Freelance
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Class Zeko_Freelance_Expiration. */
class Zeko_Freelance_Expiration {

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
	 * Entry point for the daily cron.
	 */
	public function run_daily(): void {
		$this->process_expirations();
		$this->send_expiry_reminders();
	}

	/**
	 * Expire open projects past their deadline and notify their clients.
	 *
	 * @return int Number of projects expired.
	 */
	public function process_expirations(): int {
		$expired       = $this->db->get_expired_projects();
		$expired_count = 0;

		foreach ( $expired as $project ) {
			if ( $this->db->expire_project( (int) $project->id ) ) {
				$this->emails->notify_expired( (int) $project->id );
				do_action( 'zeko_freelance_project_expired', (int) $project->id );
				++$expired_count;
			}
		}

		return $expired_count;
	}

	/**
	 * Remind clients about listings expiring within the reminder window.
	 *
	 * @return int Number of reminders sent.
	 * @param int $within_days Reminder window (days).
	 */
	public function send_expiry_reminders( int $within_days = 3 ): int {
		$now = current_time( 'mysql' );
		$end = date( 'Y-m-d H:i:s', time() + $within_days * DAY_IN_SECONDS ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date

		$due      = $this->db->get_projects_expiring_soon( $now, $end );
		$reminded = (array) get_option( 'zeko_freelance_expiry_reminded', array() );
		$sent     = 0;

		foreach ( $due as $project ) {
			$project_id = (int) $project->id;
			if ( in_array( $project_id, $reminded, true ) ) {
				continue;
			}

			$this->emails->notify_expiring_soon( $project_id );
			$reminded[] = $project_id;
			++$sent;
		}

		update_option( 'zeko_freelance_expiry_reminded', array_values( array_unique( array_map( 'absint', $reminded ) ) ) );

		return $sent;
	}
}
