<?php
/**
 * Zeko Freelance main class.
 *
 * Owns the module lifecycle: instantiates the DB layer, registers shortcodes,
 * ecosystem integration and the admin page, then exposes a small public API
 * for the theme and other modules.
 *
 * @package Zeko_Freelance
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Class Zeko_Freelance. */
class Zeko_Freelance {

	/**
	 * Instance.
	 *
	 * @var ?Zeko_Freelance Instance.
	 */
	private static ?Zeko_Freelance $instance = null;

	/**
	 * Db.
	 *
	 * @var ?Zeko_Freelance_DB Db.
	 */
	private ?Zeko_Freelance_DB $db = null;
	/**
	 * Ecosystem.
	 *
	 * @var ?Zeko_Freelance_Ecosystem Ecosystem.
	 */
	private ?Zeko_Freelance_Ecosystem $ecosystem = null;
	/**
	 * Ajax.
	 *
	 * @var ?Zeko_Freelance_Ajax Ajax.
	 */
	private ?Zeko_Freelance_Ajax $ajax = null;
	/**
	 * Emails.
	 *
	 * @var ?Zeko_Freelance_Emails Emails.
	 */
	private ?Zeko_Freelance_Emails $emails = null;
	/**
	 * Expiration.
	 *
	 * @var ?Zeko_Freelance_Expiration Expiration.
	 */
	private ?Zeko_Freelance_Expiration $expiration = null;
	/**
	 * Sla.
	 *
	 * @var ?Zeko_Freelance_SLA Sla.
	 */
	private ?Zeko_Freelance_SLA $sla = null;

	/**
	 * Instance.
	 */
	public static function instance(): Zeko_Freelance {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Construct.
	 */
	private function __construct() {
		$this->db = new Zeko_Freelance_DB();

		add_action( 'init', array( $this, 'init_components' ), 5 );
		add_filter( 'query_vars', array( $this, 'register_query_vars' ) );
	}

	/**
	 * Query vars.
	 *
	 * @param array $vars Vars.
	 */
	public function register_query_vars( array $vars ): array {
		$vars[] = 'zf_pid';
		$vars[] = 'zf_uid';
		return $vars;
	}

	/**
	 * Init components.
	 */
	public function init_components(): void {
		$this->ensure_schema();
		zeko_freelance_maybe_create_pages();

		// Shortcodes.
		add_shortcode( 'zeko_freelance', array( $this, 'shortcode_dashboard' ) );
		add_shortcode( 'zeko_freelance_projects', array( $this, 'shortcode_projects' ) );
		add_shortcode( 'zeko_freelance_portfolios', array( $this, 'shortcode_portfolios' ) );
		add_shortcode( 'zeko_freelance_post_project', array( $this, 'shortcode_post_project' ) );
		add_shortcode( 'zeko_freelance_project', array( $this, 'shortcode_project' ) );
		add_shortcode( 'zeko_freelance_profile', array( $this, 'shortcode_profile' ) );

		// Ecosystem integration (nav, admin bar, dashboard tab).
		$this->ecosystem = new Zeko_Freelance_Ecosystem( $this->db );

		// Front-end actions + maintenance cron.
		$this->emails     = new Zeko_Freelance_Emails( $this->db );
		$this->expiration = new Zeko_Freelance_Expiration( $this->db, $this->emails );
		$this->sla        = new Zeko_Freelance_SLA( $this->db, $this->emails );
		$this->ajax       = new Zeko_Freelance_Ajax( $this->db );

		// Assets + admin.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_public_assets' ) );
		if ( is_admin() ) {
			new Zeko_Freelance_Admin( $this->db );
		}
	}

	/**
	 * Db.
	 */
	public function get_db(): Zeko_Freelance_DB {
		return $this->db;
	}

	/**
	 * Emails.
	 */
	public function get_emails(): Zeko_Freelance_Emails {
		if ( ! $this->emails ) {
			$this->emails = new Zeko_Freelance_Emails( $this->db );
		}
		return $this->emails;
	}

	/**
	 * Ecosystem.
	 */
	public function get_ecosystem(): Zeko_Freelance_Ecosystem {
		if ( ! $this->ecosystem ) {
			$this->ecosystem = new Zeko_Freelance_Ecosystem( $this->db );
		}
		return $this->ecosystem;
	}

	/**
	 * Create/upgrade tables and seed skills when the stored schema version is
	 * behind the code version (idempotent, mirrors the plugins_loaded hook).
	 */
	public function ensure_schema(): void {
		$installed = get_option( 'zeko_freelance_db_version', '0' );
		if ( version_compare( $installed, ZEKO_FREELANCE_DB_VERSION, '<' ) ) {
			$this->db->create_tables();
			update_option( 'zeko_freelance_db_version', ZEKO_FREELANCE_DB_VERSION );
		}
	}

	// ═══════════════════════════════════════════════════════════════.
	// PUBLIC API (theme + other modules).
	// ═══════════════════════════════════════════════════════════════.

	/**
	 * Verified.
	 *
	 * @param int    $user_id User id.
	 * @param string $type Type.
	 */
	public function is_verified( int $user_id, string $type = 'id' ): bool {
		if ( $user_id <= 0 ) {
			return false;
		}
		return $this->db->is_user_verified( $user_id, $type );
	}

	/**
	 * Rating.
	 *
	 * @param int $user_id User id.
	 */
	public function get_rating( int $user_id ): float {
		return $this->db->average_rating( $user_id );
	}

	// ═══════════════════════════════════════════════════════════════.
	// BOOKMARKS.
	// ═══════════════════════════════════════════════════════════════.

	/**
	 * Bookmarked project ids for a user (stored in user meta).
	 *
	 * @return int[]
	 * @param int $user_id User id.
	 */
	public function get_bookmarked_project_ids( int $user_id ): array {
		if ( $user_id <= 0 ) {
			return array();
		}
		$bookmarks = get_user_meta( $user_id, 'zeko_bookmarked_projects', true );
		return is_array( $bookmarks )
			? array_values( array_unique( array_map( 'absint', $bookmarks ) ) )
			: array();
	}

	/**
	 * Project bookmarked.
	 *
	 * @param int $user_id User id.
	 * @param int $project_id Project id.
	 */
	public function is_project_bookmarked( int $user_id, int $project_id ): bool {
		return in_array( $project_id, $this->get_bookmarked_project_ids( $user_id ), true );
	}

	// ═══════════════════════════════════════════════════════════════.
	// SHORTCODES.
	// ═══════════════════════════════════════════════════════════════.

	/**
	 * Shortcode dashboard.
	 */
	public function shortcode_dashboard(): string {
		if ( ! is_user_logged_in() ) {
			return $this->render( 'login' );
		}
		return $this->render( 'dashboard' );
	}

	/**
	 * Shortcode projects.
	 *
	 * @param array $atts Atts.
	 */
	public function shortcode_projects( array $atts = array() ): string {
		$atts = shortcode_atts(
			array(
				'category' => '',
				'per_page' => 10,
				'status'   => 'open',
			),
			$atts,
			'zeko_freelance_projects'
		);

		return $this->render( 'marketplace', $atts );
	}

	/**
	 * Shortcode portfolios.
	 *
	 * @param array $atts Atts.
	 */
	public function shortcode_portfolios( array $atts = array() ): string {
		$atts = shortcode_atts(
			array(
				'category' => '',
				'per_page' => 12,
			),
			$atts,
			'zeko_freelance_portfolios'
		);

		return $this->render( 'portfolios', $atts );
	}

	/**
	 * Project posting form (create + edit).
	 *
	 * @param array $atts Atts.
	 */
	public function shortcode_post_project( array $atts = array() ): string {
		if ( ! is_user_logged_in() ) {
			return $this->render( 'login' );
		}

		$atts = shortcode_atts( array( 'edit' => 0 ), $atts, 'zeko_freelance_post_project' );

		return $this->render( 'post-project', $atts );
	}

	/**
	 * Single project page.
	 *
	 * @param array $atts Atts.
	 */
	public function shortcode_project( array $atts = array() ): string {
		$atts = shortcode_atts( array( 'id' => 0 ), $atts, 'zeko_freelance_project' );

		$project_id = $atts['id'] ? absint( $atts['id'] ) : absint( get_query_var( 'zf_pid' ) );
		if ( ! $project_id ) {
			$project_id = isset( $_GET['zf_pid'] ) ? absint( $_GET['zf_pid'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
		}

		$project = $project_id ? $this->db->get_project( $project_id ) : null;
		if ( ! $project ) {
			return '<div class="zf-empty">' . esc_html__( 'Project not found.', 'zeko-freelance' ) . '</div>';
		}

		$this->maybe_increment_views( $project_id );

		return $this->render( 'single-project', array( 'project' => $project ) );
	}

	/**
	 * Public freelancer profile page.
	 *
	 * @param array $atts Atts.
	 */
	public function shortcode_profile( array $atts = array() ): string {
		$atts = shortcode_atts( array( 'id' => 0 ), $atts, 'zeko_freelance_profile' );

		$profile_id = $atts['id'] ? absint( $atts['id'] ) : absint( get_query_var( 'zf_uid' ) );
		if ( ! $profile_id ) {
			$profile_id = isset( $_GET['zf_uid'] ) ? absint( $_GET['zf_uid'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
		}
		if ( ! $profile_id && is_user_logged_in() ) {
			$profile_id = get_current_user_id();
		}

		$user = $profile_id ? get_user_by( 'id', $profile_id ) : null;
		if ( ! $user ) {
			return '<div class="zf-empty">' . esc_html__( 'Profile not found.', 'zeko-freelance' ) . '</div>';
		}

		return $this->render( 'profile', array( 'profile' => $user ) );
	}

	/**
	 * Count a project view at most once per hour per visitor.
	 *
	 * @param int $project_id Project id.
	 */
	private function maybe_increment_views( int $project_id ): void {
		$key = 'zf_view_' . $project_id . '_' . md5( (string) get_current_user_id() . ( isset( $_SERVER['REMOTE_ADDR'] ) ? (string) $_SERVER['REMOTE_ADDR'] : '' ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		if ( get_transient( $key ) ) {
			return;
		}
		set_transient( $key, 1, HOUR_IN_SECONDS );
		$this->db->increment_project_views( $project_id );
	}

	// ═══════════════════════════════════════════════════════════════.
	// ASSETS.
	// ═══════════════════════════════════════════════════════════════.

	/**
	 * Enqueue public assets.
	 */
	public function enqueue_public_assets(): void {
		if ( ! is_singular() ) {
			return;
		}
		$post = get_post();
		if ( ! $post ) {
			return;
		}

		$has_shortcode = has_shortcode( $post->post_content, 'zeko_freelance' )
			|| has_shortcode( $post->post_content, 'zeko_freelance_projects' )
			|| has_shortcode( $post->post_content, 'zeko_freelance_portfolios' )
			|| has_shortcode( $post->post_content, 'zeko_freelance_post_project' )
			|| has_shortcode( $post->post_content, 'zeko_freelance_project' )
			|| has_shortcode( $post->post_content, 'zeko_freelance_profile' );

		if ( ! $has_shortcode ) {
			return;
		}

		wp_enqueue_style( 'zeko-freelance', ZEKO_FREELANCE_URL . 'assets/css/zeko-freelance.css', array( 'zeko-core' ), ZEKO_FREELANCE_VERSION );

		wp_enqueue_script( 'zeko-freelance', ZEKO_FREELANCE_URL . 'assets/js/zeko-freelance.js', array( 'jquery' ), ZEKO_FREELANCE_VERSION, true );
		wp_localize_script(
			'zeko-freelance',
			'ZekoFreelance',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( Zeko_Freelance_Ajax::nonce_action() ),
				'postUrl' => zeko_freelance_page_url( 'freelance-project' ),
				'i18n'    => array(
					'saving'      => __( 'Saving…', 'zeko-freelance' ),
					'saved'       => __( 'Saved', 'zeko-freelance' ),
					'bookmarked'  => __( 'Bookmarked', 'zeko-freelance' ),
					'bookmark'    => __( 'Bookmark', 'zeko-freelance' ),
					'placing'     => __( 'Placing bid…', 'zeko-freelance' ),
					'withdrawing' => __( 'Withdrawing…', 'zeko-freelance' ),
					'awarding'    => __( 'Accepting…', 'zeko-freelance' ),
					'funding'     => __( 'Funding…', 'zeko-freelance' ),
					'submitting'  => __( 'Submitting…', 'zeko-freelance' ),
					'approving'   => __( 'Approving…', 'zeko-freelance' ),
					'rejecting'   => __( 'Sending back…', 'zeko-freelance' ),
					'cancelling'  => __( 'Cancelling…', 'zeko-freelance' ),
					'opening'     => __( 'Opening…', 'zeko-freelance' ),
					'adding'      => __( 'Adding…', 'zeko-freelance' ),
					'updating'    => __( 'Updating…', 'zeko-freelance' ),
					'deleting'    => __( 'Deleting…', 'zeko-freelance' ),
				),
			)
		);
	}

	// ═══════════════════════════════════════════════════════════════.
	// TEMPLATES.
	// ═══════════════════════════════════════════════════════════════.

	/**
	 * Render a template part (plugin templates take priority, theme can
	 * override via zeko-freelance/{template}.php).
	 *
	 * @return string
	 * @param string $template * @param array  $atts.
	 * @param array  $atts Atts.
	 */
	public function render( string $template, array $atts = array() ): string {
		unset( $atts );
		$theme = locate_template( array( 'zeko-freelance/' . $template . '.php' ) );
		$path  = $theme ? $theme : ZEKO_FREELANCE_PLUGIN_PATH . 'templates/' . $template . '.php';

		if ( ! file_exists( $path ) ) {
			return '';
		}

		$freelance = $this;
		$db        = $this->db;
		$user_id   = get_current_user_id();

		ob_start();
		include $path;
		return (string) ob_get_clean();
	}
}

if ( ! function_exists( 'zeko_freelance' ) ) {
	/**
	 * Zeko freelance.
	 */
	function zeko_freelance(): Zeko_Freelance {
		return Zeko_Freelance::instance();
	}
}
