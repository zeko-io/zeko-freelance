<?php
/**
 * Plugin Name:       Zeko Freelance
 * Plugin URI:        https://ozconsultz.com/zeko-freelance
 * Description:       Freelance marketplace for the Zeko ecosystem: project listings, bidding, contracts, milestone-based escrow payments, disputes, portfolios, freelancer verification, and reviews.
 * Version:           0.5.0
 * Author:            Zeko Team
 * Author URI:        https://ozconsultz.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       zeko-freelance
 * Domain Path:       /languages
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Tested up to:      7.1.2
 *
 * @package Zeko_ZEKO_FREELANCE
 **/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'ZEKO_FREELANCE_VERSION' ) ) {
	define( 'ZEKO_FREELANCE_VERSION', '0.5.0' );
}

if ( ! defined( 'ZEKO_FREELANCE_PLUGIN_PATH' ) ) {
	define( 'ZEKO_FREELANCE_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'ZEKO_FREELANCE_PLUGIN_URL' ) ) {
	define( 'ZEKO_FREELANCE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'ZEKO_FREELANCE_URL' ) ) {
	define( 'ZEKO_FREELANCE_URL', ZEKO_FREELANCE_PLUGIN_URL );
}

if ( ! defined( 'ZEKO_FREELANCE_PLUGIN_BASENAME' ) ) {
	define( 'ZEKO_FREELANCE_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
}

if ( ! defined( 'ZEKO_FREELANCE_DB_VERSION' ) ) {
	define( 'ZEKO_FREELANCE_DB_VERSION', '0.3.0' );
}

require_once ZEKO_FREELANCE_PLUGIN_PATH . 'includes/db/class-zeko-freelance-db.php';
require_once ZEKO_FREELANCE_PLUGIN_PATH . 'includes/class-zeko-freelance-ajax.php';
require_once ZEKO_FREELANCE_PLUGIN_PATH . 'includes/class-zeko-freelance-emails.php';
require_once ZEKO_FREELANCE_PLUGIN_PATH . 'includes/class-zeko-freelance-expiration.php';
require_once ZEKO_FREELANCE_PLUGIN_PATH . 'includes/class-zeko-freelance-sla.php';
require_once ZEKO_FREELANCE_PLUGIN_PATH . 'includes/class-zeko-freelance-ecosystem.php';
require_once ZEKO_FREELANCE_PLUGIN_PATH . 'includes/admin/class-zeko-freelance-demo-generator.php';
require_once ZEKO_FREELANCE_PLUGIN_PATH . 'includes/class-zeko-freelance-admin.php';
require_once ZEKO_FREELANCE_PLUGIN_PATH . 'includes/class-zeko-freelance.php';
require_once ZEKO_FREELANCE_PLUGIN_PATH . 'includes/privacy/class-zeko-freelance-privacy.php';

/**
 * Zeko freelance init.
 */
function zeko_freelance_init() {
	load_plugin_textdomain( 'zeko-freelance', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

	$instance = Zeko_Freelance::instance();

	// One-time schema upgrade (new installs, or upgrades after a version bump).
	$installed = get_option( 'zeko_freelance_db_version', '0' );
	if ( version_compare( $installed, ZEKO_FREELANCE_DB_VERSION, '<' ) ) {
		$instance->get_db()->create_tables();
		update_option( 'zeko_freelance_db_version', ZEKO_FREELANCE_DB_VERSION );
	}

	return $instance;
}
add_action( 'plugins_loaded', 'zeko_freelance_init' );

if ( ! function_exists( 'zeko_freelance' ) ) {
	/**
	 * Zeko freelance.
	 */
	function zeko_freelance() {
		return Zeko_Freelance::instance();
	}
}

/**
 * Zeko freelance activate.
 */
function zeko_freelance_activate() {
	require_once ZEKO_FREELANCE_PLUGIN_PATH . 'includes/db/class-zeko-freelance-db.php';
	$db = new Zeko_Freelance_DB();
	$db->create_tables();

	zeko_freelance_create_shortcode_pages();
	flush_rewrite_rules();
}

/**
 * Zeko freelance deactivate.
 */
function zeko_freelance_deactivate() {
	$instance   = Zeko_Freelance::instance();
	$emails     = $instance->get_emails();
	$expiration = new Zeko_Freelance_Expiration( $instance->get_db(), $emails );
	$expiration->clear_cron();
}

/**
 * Zeko freelance create shortcode pages.
 */
function zeko_freelance_create_shortcode_pages() {
	$pages = array(
		'freelance'              => array(
			'title'   => __( 'Freelance', 'zeko-freelance' ),
			'content' => '[zeko_freelance]',
		),
		'freelance-projects'     => array(
			'title'   => __( 'Freelance Marketplace', 'zeko-freelance' ),
			'content' => '[zeko_freelance_projects]',
		),
		'freelance-portfolios'   => array(
			'title'   => __( 'Freelance Portfolios', 'zeko-freelance' ),
			'content' => '[zeko_freelance_portfolios]',
		),
		'freelance-post-project' => array(
			'title'   => __( 'Post a Project', 'zeko-freelance' ),
			'content' => '[zeko_freelance_post_project]',
		),
		'freelance-project'      => array(
			'title'   => __( 'Project Details', 'zeko-freelance' ),
			'content' => '[zeko_freelance_project]',
		),
		'freelance-profile'      => array(
			'title'   => __( 'Freelancer Profile', 'zeko-freelance' ),
			'content' => '[zeko_freelance_profile]',
		),
	);

	foreach ( $pages as $slug => $page ) {
		$existing = class_exists( 'Zeko_Core_Helpers' )
			? Zeko_Core_Helpers::get_instance()->get_page_by_slug( $slug )
			: get_page_by_path( $slug );
		if ( ! $existing ) {
			$result = wp_insert_post(
				array(
					'post_title'   => $page['title'],
					'post_content' => $page['content'],
					'post_status'  => 'publish',
					'post_type'    => 'page',
					'post_name'    => $slug,
				)
			);
			if ( is_wp_error( $result ) ) {
				error_log( 'Zeko Freelance: Failed to create page "' . $slug . '": ' . $result->get_error_message() );
				continue;
			}
			if ( function_exists( 'zeko_mark_plugin_page' ) ) {
				zeko_mark_plugin_page( $result, 'freelance' );
			}
			$existing = get_post( $result );
		}

		if ( $existing ) {
			update_option( 'zeko_freelance_' . $slug . '_page_id', (int) $existing->ID );
		}
	}
}

/**
 * Resolve the permalink for a Zeko Freelance page by its slug.
 * Delegates to the ecosystem page-URL registry (Zeko Core) when present;
 * the local resolution is the fallback so freelance still works without it.
 *
 * @return string
 * @param string $slug Page slug (e.g. 'freelance-projects').
 */
function zeko_freelance_page_url( string $slug ): string {
	if ( class_exists( 'Zeko_Core_Helpers' ) && method_exists( 'Zeko_Core_Helpers', 'get_page_url' ) ) {
		return Zeko_Core_Helpers::get_instance()->get_page_url( 'freelance', $slug );
	}

	$page_id = (int) get_option( 'zeko_freelance_' . $slug . '_page_id', 0 );

	if ( $page_id && 'publish' === get_post_status( $page_id ) ) {
		return get_permalink( $page_id );
	}

	$page = class_exists( 'Zeko_Core_Helpers' )
		? Zeko_Core_Helpers::get_instance()->get_page_by_slug( $slug )
		: get_page_by_path( $slug );
	if ( $page ) {
		update_option( 'zeko_freelance_' . $slug . '_page_id', (int) $page->ID );
		return get_permalink( $page );
	}

	return home_url( '/' . $slug . '/' );
}

/**
 * Zeko freelance maybe create pages.
 */
function zeko_freelance_maybe_create_pages() {
	// Version-gated so upgrades create any newly added shortcode pages.
	// without re-running on every request. Falls back to '0' for installs.
	// that predate the versioned option, forcing one reconciliation pass.
	$created = get_option( 'zeko_freelance_pages_version', '0' );
	if ( version_compare( $created, ZEKO_FREELANCE_VERSION, '<' ) ) {
		zeko_freelance_create_shortcode_pages();
		update_option( 'zeko_freelance_pages_version', ZEKO_FREELANCE_VERSION );
	}
}

/**
 * Zeko Freelance settings.
 * Stored in the zeko_freelance_settings option, filterable via
 * 'zeko_freelance_settings'. Falls back to defaults when unset.
 *
 * @return array{platform_fee: int, currency: string, max_active_projects: int, milestone_payment: int, verify_required: int, require_approval: int}
 */
function zeko_freelance_get_settings(): array {
	$settings = get_option( 'zeko_freelance_settings', array() );

	$defaults = array(
		'platform_fee'          => 10,
		'currency'              => 'USD',
		'max_active_projects'   => 10,
		'milestone_payment'     => 1,
		'verify_required'       => 0,
		'require_approval'      => 0,
		'review_sla_days'       => 7,
		'review_reminder_hours' => 24,
		'submit_sla_days'       => 14,
		'submit_reminder_hours' => 24,
		'auto_approve'          => 1,
		'auto_dispute'          => 1,
	);

	return apply_filters( 'zeko_freelance_settings', wp_parse_args( is_array( $settings ) ? $settings : array(), $defaults ) );
}

/**
 * Currency symbol for a given ISO code. Falls back to the code itself so
 * non-USD currencies still render sensibly.
 * Delegates to the ecosystem's single currency map (Zeko Pay) when the pay
 * plugin is active; the local map is the fallback so freelance still works
 * without it.
 *
 * @return string
 * @param string $currency ISO 4217 code (e.g. 'USD', 'EUR', 'GBP').
 */
function zeko_freelance_currency_symbol( string $currency ): string {
	if ( class_exists( 'Zeko_Pay_Utils' ) && method_exists( 'Zeko_Pay_Utils', 'currency_symbol' ) ) {
		return Zeko_Pay_Utils::currency_symbol( $currency );
	}

	$symbols = apply_filters(
		'zeko_freelance_currency_symbols',
		array(
			'USD' => '$',
			'EUR' => '€',
			'GBP' => '£',
			'JPY' => '¥',
			'CNY' => '¥',
			'INR' => '₹',
			'BRL' => 'R$',
			'AUD' => 'A$',
			'CAD' => 'C$',
			'NZD' => 'NZ$',
			'CHF' => 'CHF ',
		)
	);

	$code = strtoupper( trim( $currency ) );

	return isset( $symbols[ $code ] ) ? $symbols[ $code ] : $code . ' ';
}

register_activation_hook( __FILE__, 'zeko_freelance_activate' );
register_deactivation_hook( __FILE__, 'zeko_freelance_deactivate' );

/**
 * Whether a user is a demo account created by the demo data seeder.
 * True when the user carries the `zeko_demo_user` meta marker, or when their
 * email is on the demo domain (@zeko.test). Used to keep demo accounts out of
 * real payment/escrow flows and to suppress transactional email delivery to
 * demo addresses.
 *
 * @return bool
 * @param int $user_id User ID.
 */
function zeko_freelance_is_demo_user( int $user_id ): bool {
	if ( ! $user_id ) {
		return false;
	}

	if ( get_user_meta( $user_id, 'zeko_demo_user', true ) ) {
		return true;
	}

	$user = get_userdata( $user_id );
	if ( $user instanceof WP_User && ! empty( $user->user_email ) ) {
		$domain = strtolower( (string) wp_parse_url( $user->user_email, PHP_URL_HOST ) );
		if ( 'zeko.test' === $domain ) {
			return true;
		}
	}

	return false;
}

/**
 * Uninstall: drop tables + clear cron + delete options + remove pages.
 */
function zeko_freelance_uninstall() {
	if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
		return;
	}

	global $wpdb;

	// Clear the daily expiration cron (deactivate clears it too; belt & braces).
	wp_clear_scheduled_hook( 'zeko_freelance_daily' );

	// Drop all freelance tables.
	$tables = array(
		'zeko_freelance_projects',
		'zeko_freelance_bids',
		'zeko_freelance_contracts',
		'zeko_freelance_milestones',
		'zeko_freelance_disputes',
		'zeko_freelance_portfolios',
		'zeko_freelance_reviews',
		'zeko_freelance_verifications',
		'zeko_freelance_skills',
	);
	// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	foreach ( $tables as $table ) {
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}{$table}" );
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	// Remove only pages created by this plugin — marker-verified via Zeko Core.
	// (with legacy slug + exact-shortcode-content adoption for pre-marker.
	// installs). An admin's unrelated page sharing a slug is never deleted.
	if ( function_exists( 'zeko_delete_plugin_pages' ) ) {
		zeko_delete_plugin_pages(
			'freelance',
			array( 'freelance', 'freelance-projects', 'freelance-portfolios', 'freelance-post-project', 'freelance-project', 'freelance-profile' )
		);
	}

	// Options + per-page IDs.
	foreach ( array(
		'zeko_freelance_db_version',
		'zeko_freelance_settings',
		'zeko_freelance_pages_version',
	) as $option ) {
		delete_option( $option );
	}
	$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", 'zeko_freelance_%_page_id' ) );
}

register_uninstall_hook( __FILE__, 'zeko_freelance_uninstall' );
