<?php
/**
 * Uninstall script for Zeko Freelance.
 *
 * Runs when the plugin is deleted via WordPress admin.
 * Cleans up freelance-owned tables, user meta, plugin options, and scheduled
 * cron events. Shared ecosystem data is kept.
 *
 * @package Zeko_Freelance
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

$prefix = $wpdb->prefix;

// Drop freelance-owned tables.
$tables = array(
	$prefix . 'zeko_freelance_projects',
	$prefix . 'zeko_freelance_bids',
	$prefix . 'zeko_freelance_contracts',
	$prefix . 'zeko_freelance_milestones',
	$prefix . 'zeko_freelance_disputes',
	$prefix . 'zeko_freelance_portfolios',
	$prefix . 'zeko_freelance_reviews',
	$prefix . 'zeko_freelance_verifications',
	$prefix . 'zeko_freelance_skills',
);

foreach ( $tables as $table ) {
	$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
}

// Delete freelance-owned user meta. Never a bare `zeko_%` wildcard, which
// would wipe other ecosystem modules' meta.
$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	'DELETE FROM ' . $wpdb->usermeta . " WHERE meta_key LIKE 'zeko_freelance\_%'" // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
);
$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	'DELETE FROM ' . $wpdb->usermeta . " WHERE meta_key = 'zeko_bookmarked_projects'" // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
);

// Delete plugin options.
$options = array(
	'zeko_freelance_settings',
	'zeko_freelance_db_version',
	'zeko_freelance_menu_version',
	'zeko_freelance_pages_version',
	'zeko_freelance_demo_seeded',
	'zeko_freelance_expiry_reminded',
);

foreach ( $options as $option ) {
	delete_option( $option );
}

// Clear all scheduled cron events.
wp_clear_scheduled_hook( 'zeko_freelance_daily' );
