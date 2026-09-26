<?php
/**
 * Zeko Freelance database layer.
 *
 * Owns the freelance marketplace schema: projects (listings), bids,
 * contracts, milestone-based payments, disputes, portfolios, reviews,
 * freelancer verifications and the skill taxonomy.
 *
 * @package Zeko_Freelance
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Class Zeko_Freelance_DB. */
class Zeko_Freelance_DB {

	/**
	 * Wpdb.
	 *
	 * @var mixed Wpdb.
	 */
	private $wpdb;

	/**
	 * Table projects.
	 *
	 * @var string Table projects.
	 */
	private string $table_projects;
	/**
	 * Table bids.
	 *
	 * @var string Table bids.
	 */
	private string $table_bids;
	/**
	 * Table contracts.
	 *
	 * @var string Table contracts.
	 */
	private string $table_contracts;
	/**
	 * Table milestones.
	 *
	 * @var string Table milestones.
	 */
	private string $table_milestones;
	/**
	 * Table disputes.
	 *
	 * @var string Table disputes.
	 */
	private string $table_disputes;
	/**
	 * Table portfolios.
	 *
	 * @var string Table portfolios.
	 */
	private string $table_portfolios;
	/**
	 * Table reviews.
	 *
	 * @var string Table reviews.
	 */
	private string $table_reviews;
	/**
	 * Table verifications.
	 *
	 * @var string Table verifications.
	 */
	private string $table_verifications;
	/**
	 * Table skills.
	 *
	 * @var string Table skills.
	 */
	private string $table_skills;

	/**
	 * Construct.
	 */
	public function __construct() {
		global $wpdb;
		$this->wpdb = $wpdb;

		$p = $wpdb->prefix;

		$this->table_projects      = $p . 'zeko_freelance_projects';
		$this->table_bids          = $p . 'zeko_freelance_bids';
		$this->table_contracts     = $p . 'zeko_freelance_contracts';
		$this->table_milestones    = $p . 'zeko_freelance_milestones';
		$this->table_disputes      = $p . 'zeko_freelance_disputes';
		$this->table_portfolios    = $p . 'zeko_freelance_portfolios';
		$this->table_reviews       = $p . 'zeko_freelance_reviews';
		$this->table_verifications = $p . 'zeko_freelance_verifications';
		$this->table_skills        = $p . 'zeko_freelance_skills';
	}

	/**
	 * Table projects.
	 */
	public function get_table_projects(): string {
		return $this->table_projects; }
	/**
	 * Table bids.
	 */
	public function get_table_bids(): string {
		return $this->table_bids; }
	/**
	 * Table contracts.
	 */
	public function get_table_contracts(): string {
		return $this->table_contracts; }
	/**
	 * Table milestones.
	 */
	public function get_table_milestones(): string {
		return $this->table_milestones; }
	/**
	 * Table disputes.
	 */
	public function get_table_disputes(): string {
		return $this->table_disputes; }
	/**
	 * Table portfolios.
	 */
	public function get_table_portfolios(): string {
		return $this->table_portfolios; }
	/**
	 * Table reviews.
	 */
	public function get_table_reviews(): string {
		return $this->table_reviews; }
	/**
	 * Table verifications.
	 */
	public function get_table_verifications(): string {
		return $this->table_verifications; }
	/**
	 * Table skills.
	 */
	public function get_table_skills(): string {
		return $this->table_skills; }

	// ═══════════════════════════════════════════════════════════════.
	// SCHEMA.
	// ═══════════════════════════════════════════════════════════════.

	/**
	 * Create tables.
	 */
	public function create_tables(): void {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$charset = $this->wpdb->get_charset_collate();

		$sql = array();

		$sql[] = "CREATE TABLE {$this->table_projects} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			title varchar(200) NOT NULL DEFAULT '',
			description text NULL,
			budget_min decimal(12,2) NOT NULL DEFAULT 0.00,
			budget_max decimal(12,2) NOT NULL DEFAULT 0.00,
			currency varchar(8) NOT NULL DEFAULT 'USD',
			category varchar(64) NOT NULL DEFAULT '',
			skills text NULL,
			location varchar(150) NOT NULL DEFAULT '',
			duration varchar(32) NOT NULL DEFAULT '',
			status varchar(32) NOT NULL DEFAULT 'open',
			views int(11) NOT NULL DEFAULT 0,
			featured tinyint(1) NOT NULL DEFAULT 0,
			expires_at datetime NULL DEFAULT NULL,
			created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			updated_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			KEY user_id (user_id),
			KEY status (status),
			KEY category (category),
			KEY status_created (status, created_at),
			PRIMARY KEY  (id)
		) {$charset};";

		$sql[] = "CREATE TABLE {$this->table_bids} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			project_id bigint(20) unsigned NOT NULL DEFAULT 0,
			user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			amount decimal(12,2) NOT NULL DEFAULT 0.00,
			delivery_days int(11) NOT NULL DEFAULT 0,
			proposal text NULL,
			status varchar(32) NOT NULL DEFAULT 'pending',
			created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			updated_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			KEY project_id (project_id),
			KEY user_id (user_id),
			KEY status (status),
			UNIQUE KEY project_user (project_id, user_id),
			PRIMARY KEY  (id)
		) {$charset};";

		$sql[] = "CREATE TABLE {$this->table_contracts} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			project_id bigint(20) unsigned NOT NULL DEFAULT 0,
			bid_id bigint(20) unsigned NOT NULL DEFAULT 0,
			client_id bigint(20) unsigned NOT NULL DEFAULT 0,
			freelancer_id bigint(20) unsigned NOT NULL DEFAULT 0,
			budget decimal(12,2) NOT NULL DEFAULT 0.00,
			currency varchar(8) NOT NULL DEFAULT 'USD',
			escrow_amount decimal(12,2) NOT NULL DEFAULT 0.00,
			status varchar(32) NOT NULL DEFAULT 'pending',
			funded_at datetime NULL DEFAULT NULL,
			created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			updated_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			KEY project_id (project_id),
			KEY client_id (client_id),
			KEY freelancer_id (freelancer_id),
			KEY status (status),
			PRIMARY KEY  (id)
		) {$charset};";

		$sql[] = "CREATE TABLE {$this->table_milestones} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			contract_id bigint(20) unsigned NOT NULL DEFAULT 0,
			title varchar(200) NOT NULL DEFAULT '',
			description text NULL,
			amount decimal(12,2) NOT NULL DEFAULT 0.00,
			status varchar(32) NOT NULL DEFAULT 'pending',
			due_at datetime NULL DEFAULT NULL,
			tx_id bigint(20) unsigned NULL DEFAULT NULL,
			release_tx_id bigint(20) unsigned NULL DEFAULT NULL,
			review_due_at datetime NULL DEFAULT NULL,
			review_reminded_at datetime NULL DEFAULT NULL,
			submit_due_at datetime NULL DEFAULT NULL,
			submit_reminded_at datetime NULL DEFAULT NULL,
			created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			updated_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			KEY contract_id (contract_id),
			KEY status (status),
			KEY review_due (status, review_due_at),
			KEY submit_due (status, submit_due_at),
			PRIMARY KEY  (id)
		) {$charset};";

		$sql[] = "CREATE TABLE {$this->table_disputes} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			contract_id bigint(20) unsigned NOT NULL DEFAULT 0,
			user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			subject varchar(200) NOT NULL DEFAULT '',
			description text NULL,
			status varchar(32) NOT NULL DEFAULT 'open',
			resolution text NULL,
			created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			updated_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			KEY contract_id (contract_id),
			KEY status (status),
			PRIMARY KEY  (id)
		) {$charset};";

		$sql[] = "CREATE TABLE {$this->table_portfolios} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			title varchar(200) NOT NULL DEFAULT '',
			description text NULL,
			category varchar(64) NOT NULL DEFAULT '',
			skills text NULL,
			link varchar(255) NOT NULL DEFAULT '',
			image_url varchar(255) NOT NULL DEFAULT '',
			created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			updated_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			KEY user_id (user_id),
			KEY category (category),
			PRIMARY KEY  (id)
		) {$charset};";

		$sql[] = "CREATE TABLE {$this->table_reviews} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			contract_id bigint(20) unsigned NOT NULL DEFAULT 0,
			reviewer_id bigint(20) unsigned NOT NULL DEFAULT 0,
			reviewee_id bigint(20) unsigned NOT NULL DEFAULT 0,
			rating tinyint(3) unsigned NOT NULL DEFAULT 5,
			comment text NULL,
			created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			KEY contract_id (contract_id),
			KEY reviewer_id (reviewer_id),
			KEY reviewee_id (reviewee_id),
			UNIQUE KEY contract_reviewer (contract_id, reviewer_id),
			PRIMARY KEY  (id)
		) {$charset};";

		$sql[] = "CREATE TABLE {$this->table_verifications} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			type varchar(32) NOT NULL DEFAULT 'id',
			status varchar(32) NOT NULL DEFAULT 'pending',
			notes text NULL,
			submitted_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			reviewed_at datetime NULL DEFAULT NULL,
			reviewed_by bigint(20) unsigned NOT NULL DEFAULT 0,
			KEY user_id (user_id),
			KEY status (status),
			KEY user_type (user_id, type),
			PRIMARY KEY  (id)
		) {$charset};";

		$sql[] = "CREATE TABLE {$this->table_skills} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(100) NOT NULL DEFAULT '',
			slug varchar(100) NOT NULL DEFAULT '',
			category varchar(64) NOT NULL DEFAULT '',
			UNIQUE KEY slug (slug),
			PRIMARY KEY  (id)
		) {$charset};";

		foreach ( $sql as $query ) {
			dbDelta( $query );
		}

		$this->seed_default_skills();
	}

	// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	/**
	 * Seed a starter skill taxonomy if the table is empty. When $force is
	 * true, restore any missing default skills without touching user-added
	 * rows (idempotent by slug).
	 *
	 * @param bool $force Force.
	 */
	public function seed_default_skills( bool $force = false ): void {
		if ( ! $force ) {
			$count = (int) $this->wpdb->get_var( "SELECT COUNT(*) FROM {$this->table_skills}" );
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
			if ( $count > 0 ) {
				return;
			}
		}

		$skills = array(
			'web-development'    => array(
				'name'     => 'Web Development',
				'category' => 'development',
			),
			'mobile-development' => array(
				'name'     => 'Mobile Development',
				'category' => 'development',
			),
			'ui-ux-design'       => array(
				'name'     => 'UI/UX Design',
				'category' => 'design',
			),
			'graphic-design'     => array(
				'name'     => 'Graphic Design',
				'category' => 'design',
			),
			'content-writing'    => array(
				'name'     => 'Content Writing',
				'category' => 'writing',
			),
			'copywriting'        => array(
				'name'     => 'Copywriting',
				'category' => 'writing',
			),
			'digital-marketing'  => array(
				'name'     => 'Digital Marketing',
				'category' => 'marketing',
			),
			'seo'                => array(
				'name'     => 'SEO',
				'category' => 'marketing',
			),
			'data-entry'         => array(
				'name'     => 'Data Entry',
				'category' => 'admin',
			),
			'virtual-assistant'  => array(
				'name'     => 'Virtual Assistant',
				'category' => 'admin',
			),
			'video-editing'      => array(
				'name'     => 'Video Editing',
				'category' => 'media',
			),
			'translation'        => array(
				'name'     => 'Translation',
				'category' => 'media',
			),
			'consulting'         => array(
				'name'     => 'Consulting',
				'category' => 'business',
			),
			'accounting'         => array(
				'name'     => 'Accounting',
				'category' => 'business',
			),
			'photography'        => array(
				'name'     => 'Photography',
				'category' => 'media',
			),
		);

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		foreach ( $skills as $slug => $skill ) {
			if ( $force ) {
				$exists = $this->wpdb->get_var(
					$this->wpdb->prepare(
						"SELECT id FROM {$this->table_skills} WHERE slug = %s",
						$slug
					)
				);
				// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
				if ( $exists ) {
					continue;
				}
			}
			$this->wpdb->insert(
				$this->table_skills,
				array(
					'name'     => $skill['name'],
					'slug'     => $slug,
					'category' => $skill['category'],
				),
				array( '%s', '%s', '%s' )
			);
		}
	}

	// ═══════════════════════════════════════════════════════════════.
	// PROJECTS.
	// ═══════════════════════════════════════════════════════════════.

	/**
	 * Insert project.
	 *
	 * @param array $data Data.
	 */
	public function insert_project( array $data ): int {
		$now = current_time( 'mysql' );
		$this->wpdb->insert(
			$this->table_projects,
			array_merge(
				array(
					'user_id'     => 0,
					'title'       => '',
					'description' => '',
					'budget_min'  => 0.00,
					'budget_max'  => 0.00,
					'currency'    => 'USD',
					'category'    => '',
					'skills'      => '',
					'location'    => '',
					'duration'    => '',
					'status'      => 'open',
					'expires_at'  => null,
					'created_at'  => $now,
					'updated_at'  => $now,
				),
				$data
			),
			array( '%d', '%s', '%s', '%f', '%f', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		return (int) $this->wpdb->insert_id;
	}

	// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	/**
	 * Project.
	 *
	 * @param int $id Id.
	 */
	public function get_project( int $id ): ?object {
		return $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT * FROM {$this->table_projects} WHERE id = %d",
				$id
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	/**
	 * Update project.
	 *
	 * @param int   $id Id.
	 * @param array $data Data.
	 */
	public function update_project( int $id, array $data ): bool {
		$data['updated_at'] = current_time( 'mysql' );
		return false !== $this->wpdb->update( $this->table_projects, $data, array( 'id' => $id ) );
	}

	/**
	 * Delete project.
	 *
	 * @param int $id Id.
	 */
	public function delete_project( int $id ): bool {
		return false !== $this->wpdb->delete( $this->table_projects, array( 'id' => $id ) );
	}

	/**
	 * List projects, filtered by status / user / category / search /
	 * budget range / skill, with a whitelisted sort order.
	 *
	 * @return object[]
	 * @param array $args Args.
	 */
	public function get_projects( array $args = array() ): array {
		$where = array( '1=1' );
		$vars  = array();

		if ( ! empty( $args['status'] ) ) {
			$where[] = 'status = %s';
			$vars[]  = $args['status'];
		}
		if ( ! empty( $args['user_id'] ) ) {
			$where[] = 'user_id = %d';
			$vars[]  = (int) $args['user_id'];
		}
		if ( ! empty( $args['category'] ) ) {
			$where[] = 'category = %s';
			$vars[]  = $args['category'];
		}
		if ( ! empty( $args['featured'] ) ) {
			$where[] = 'featured = 1';
		}
		if ( ! empty( $args['search'] ) ) {
			$where[] = '(title LIKE %s OR description LIKE %s OR skills LIKE %s)';
			$like    = '%' . $this->wpdb->esc_like( $args['search'] ) . '%';
			$vars[]  = $like;
			$vars[]  = $like;
			$vars[]  = $like;
		}
		if ( isset( $args['budget_min'] ) && $args['budget_min'] > 0 ) {
			$where[] = 'budget_max >= %f';
			$vars[]  = (float) $args['budget_min'];
		}
		if ( isset( $args['budget_max'] ) && $args['budget_max'] > 0 ) {
			$where[] = 'budget_min <= %f';
			$vars[]  = (float) $args['budget_max'];
		}
		if ( ! empty( $args['skill'] ) ) {
			$where[] = 'skills LIKE %s';
			$vars[]  = '%' . $this->wpdb->esc_like( $args['skill'] ) . '%';
		}

		$order_by = 'ORDER BY created_at DESC';
		if ( ! empty( $args['orderby'] ) ) {
			$map = array(
				'created_at'  => 'ORDER BY created_at DESC',
				'oldest'      => 'ORDER BY created_at ASC',
				'budget'      => 'ORDER BY budget_min ASC',
				'budget_desc' => 'ORDER BY budget_max DESC',
				'featured'    => 'ORDER BY featured DESC, created_at DESC',
			);
			$key = sanitize_key( $args['orderby'] );
			if ( isset( $map[ $key ] ) ) {
				$order_by = $map[ $key ];
			}
		}

		$per_page = isset( $args['per_page'] ) ? max( 1, (int) $args['per_page'] ) : 20;
		$page     = isset( $args['page'] ) ? max( 1, (int) $args['page'] ) : 1;
		$offset   = ( $page - 1 ) * $per_page;

		$sql = 'SELECT * FROM ' . $this->table_projects
			. ' WHERE ' . implode( ' AND ', $where )
			. " {$order_by} LIMIT %d OFFSET %d";

		$vars[] = $per_page;
		$vars[] = $offset;

		return (array) $this->wpdb->get_results( $this->wpdb->prepare( $sql, $vars ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	/**
	 * Count projects matching the same filters as get_projects(), for
	 * pagination. Ordering is irrelevant here.
	 *
	 * @param array $args Args.
	 */
	public function count_projects( array $args = array() ): int {
		$where = array( '1=1' );
		$vars  = array();

		if ( ! empty( $args['status'] ) ) {
			$where[] = 'status = %s';
			$vars[]  = $args['status'];
		}
		if ( ! empty( $args['user_id'] ) ) {
			$where[] = 'user_id = %d';
			$vars[]  = (int) $args['user_id'];
		}
		if ( ! empty( $args['category'] ) ) {
			$where[] = 'category = %s';
			$vars[]  = $args['category'];
		}
		if ( ! empty( $args['featured'] ) ) {
			$where[] = 'featured = 1';
		}
		if ( ! empty( $args['search'] ) ) {
			$where[] = '(title LIKE %s OR description LIKE %s OR skills LIKE %s)';
			$like    = '%' . $this->wpdb->esc_like( $args['search'] ) . '%';
			$vars[]  = $like;
			$vars[]  = $like;
			$vars[]  = $like;
		}
		if ( isset( $args['budget_min'] ) && $args['budget_min'] > 0 ) {
			$where[] = 'budget_max >= %f';
			$vars[]  = (float) $args['budget_min'];
		}
		if ( isset( $args['budget_max'] ) && $args['budget_max'] > 0 ) {
			$where[] = 'budget_min <= %f';
			$vars[]  = (float) $args['budget_max'];
		}
		if ( ! empty( $args['skill'] ) ) {
			$where[] = 'skills LIKE %s';
			$vars[]  = '%' . $this->wpdb->esc_like( $args['skill'] ) . '%';
		}

		$sql = 'SELECT COUNT(*) FROM ' . $this->table_projects . ' WHERE ' . implode( ' AND ', $where );

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		return (int) ( $vars
			? $this->wpdb->get_var( $this->wpdb->prepare( $sql, $vars ) )
			: $this->wpdb->get_var( $sql ) );
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	/**
	 * Count projects by status.
	 *
	 * @param string $status Status.
	 */
	public function count_projects_by_status( string $status ): int {
		return (int) $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->table_projects} WHERE status = %s",
				$status
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	/**
	 * Aggregate counts + revenue for the admin marketplace overview.
	 *
	 * @return array{open_projects:int, pending_bids:int, active_contracts:int, open_disputes:int, pending_verifications:int, paid_milestones:float}
	 */
	public function admin_overview_stats(): array {
		$count_bids          = (int) $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->table_bids} WHERE status = %s",
				'pending'
			)
		);
		$count_contracts     = (int) $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->table_contracts} WHERE status = %s",
				'active'
			)
		);
		$count_disputes      = (int) $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->table_disputes} WHERE status = %s",
				'open'
			)
		);
		$count_verifications = (int) $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->table_verifications} WHERE status = %s",
				'pending'
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		return array(
			'open_projects'         => $this->count_projects_by_status( 'open' ),
			'pending_bids'          => $count_bids,
			'active_contracts'      => $count_contracts,
			'open_disputes'         => $count_disputes,
			'pending_verifications' => $count_verifications,
			'paid_milestones'       => (float) $this->wpdb->get_var(
				"SELECT COALESCE(SUM(amount), 0) FROM {$this->table_milestones} WHERE status = 'paid'"
			),
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	/**
	 * Increment project views.
	 *
	 * @param int $id Id.
	 */
	public function increment_project_views( int $id ): void {
		$this->wpdb->query(
			$this->wpdb->prepare(
				"UPDATE {$this->table_projects} SET views = views + 1 WHERE id = %d",
				$id
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	/**
	 * Count a client's currently active (open) projects, used to enforce the
	 * "max active projects" posting limit.
	 *
	 * @param int $user_id User id.
	 */
	public function count_active_projects_for_user( int $user_id ): int {
		return (int) $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->table_projects} WHERE user_id = %d AND status = 'open'",
				$user_id
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	/**
	 * Open projects whose listing period has ended.
	 *
	 * @return object[]
	 */
	public function get_expired_projects(): array {
		return (array) $this->wpdb->get_results(
			"SELECT * FROM {$this->table_projects} WHERE status = 'open' AND expires_at IS NOT NULL AND expires_at < NOW()"
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	/**
	 * Open projects expiring within a window, for reminder emails.
	 *
	 * @return object[]
	 * @param string $start 'Y-m-d H:i:s' start of the window.
	 * @param string $end 'Y-m-d H:i:s' end of the window.
	 */
	public function get_projects_expiring_soon( string $start, string $end ): array {
		return (array) $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT * FROM {$this->table_projects} WHERE status = 'open' AND expires_at IS NOT NULL AND expires_at >= %s AND expires_at < %s",
				$start,
				$end
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	/**
	 * Mark an open project as expired (called by the daily cron).
	 *
	 * @param int $id Id.
	 */
	public function expire_project( int $id ): bool {
		return $this->update_project( $id, array( 'status' => 'expired' ) );
	}

	// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	/**
	 * Related open projects in the same category (featured first).
	 *
	 * @return object[]
	 * @param string $category Category.
	 * @param int    $exclude_id Exclude id.
	 * @param int    $limit Limit.
	 */
	public function get_related_projects( string $category, int $exclude_id, int $limit = 3 ): array {
		return (array) $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT * FROM {$this->table_projects} WHERE status = 'open' AND id != %d AND category = %s
			ORDER BY featured DESC, created_at DESC LIMIT %d",
				$exclude_id,
				$category,
				$limit
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	/**
	 * Pending projects awaiting moderation.
	 *
	 * @return object[]
	 */
	public function get_pending_projects(): array {
		return (array) $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT * FROM {$this->table_projects} WHERE status = 'pending' ORDER BY created_at ASC LIMIT %d",
				200
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	// ═══════════════════════════════════════════════════════════════.
	// BIDS.
	// ═══════════════════════════════════════════════════════════════.

	/**
	 * Insert bid.
	 *
	 * @param array $data Data.
	 */
	public function insert_bid( array $data ): int {
		$now = current_time( 'mysql' );
		$this->wpdb->insert(
			$this->table_bids,
			array_merge(
				array(
					'project_id'    => 0,
					'user_id'       => 0,
					'amount'        => 0.00,
					'delivery_days' => 0,
					'proposal'      => '',
					'status'        => 'pending',
					'created_at'    => $now,
					'updated_at'    => $now,
				),
				$data
			),
			array( '%d', '%d', '%f', '%d', '%s', '%s', '%s', '%s' )
		);

		return (int) $this->wpdb->insert_id;
	}

	// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	/**
	 * Bid.
	 *
	 * @param int $id Id.
	 */
	public function get_bid( int $id ): ?object {
		return $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT * FROM {$this->table_bids} WHERE id = %d",
				$id
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	/**
	 * Bid for.
	 *
	 * @param int $project_id Project id.
	 * @param int $user_id User id.
	 */
	public function get_bid_for( int $project_id, int $user_id ): ?object {
		return $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT * FROM {$this->table_bids} WHERE project_id = %d AND user_id = %d",
				$project_id,
				$user_id
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	/**
	 * Update bid.
	 *
	 * @param int   $id Id.
	 * @param array $data Data.
	 */
	public function update_bid( int $id, array $data ): bool {
		$data['updated_at'] = current_time( 'mysql' );
		return false !== $this->wpdb->update( $this->table_bids, $data, array( 'id' => $id ) );
	}

	/**
	 * Delete bid.
	 *
	 * @param int $id Id.
	 */
	public function delete_bid( int $id ): bool {
		return false !== $this->wpdb->delete( $this->table_bids, array( 'id' => $id ) );
	}

	/**
	 * List bids for a project with a whitelisted sort order. Every row
	 * carries a `freelancer_rating` (average review rating of the bidder,
	 * 0 when unrated) so the rating sort and the single-project list can
	 * rank bidders without a second query.
	 *
	 * @return object[]
	 * @param int     $project_id * @param string|null $status   Filter by bid status ('pending', 'accepted', ...).
	 * @param ?string $status Status.
	 * @param string  $orderby 'amount' (lowest first, default), 'newest', 'rating'.
	 */
	public function get_bids( int $project_id, ?string $status = null, string $orderby = 'amount' ): array {
		$sql  = "SELECT b.*, COALESCE(r.avg_rating, 0) AS freelancer_rating
			FROM {$this->table_bids} b
			LEFT JOIN (
				SELECT reviewee_id, AVG(rating) AS avg_rating
				FROM {$this->table_reviews}
				GROUP BY reviewee_id
			) r ON r.reviewee_id = b.user_id
			WHERE b.project_id = %d";
		$vars = array( $project_id );
		if ( null !== $status ) {
			$sql   .= ' AND b.status = %s';
			$vars[] = $status;
		}

		$map  = array(
			'amount' => 'ORDER BY b.amount ASC, b.created_at ASC',
			'newest' => 'ORDER BY b.created_at DESC',
			'rating' => 'ORDER BY freelancer_rating DESC, b.created_at ASC',
		);
		$key  = sanitize_key( $orderby );
		$sql .= ' ' . ( isset( $map[ $key ] ) ? $map[ $key ] : $map['amount'] );

		return (array) $this->wpdb->get_results( $this->wpdb->prepare( $sql, $vars ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	/**
	 * Aggregate bid stats for a project (active proposals by default).
	 *
	 * @return array{count:int, average:float, lowest:float}
	 * @param int    $project_id Project id.
	 * @param string $status Status.
	 */
	public function bid_stats( int $project_id, string $status = 'pending' ): array {
		$row = $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT COUNT(*) AS count, AVG(amount) AS average, MIN(amount) AS lowest
			FROM {$this->table_bids}
			WHERE project_id = %d AND status = %s",
				$project_id,
				$status
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery

		return array(
			'count'   => $row ? (int) $row->count : 0,
			'average' => $row ? (float) $row->average : 0.0,
			'lowest'  => $row ? (float) $row->lowest : 0.0,
		);
	}

	/**
	 * Withdraw a bid (only from 'pending').
	 *
	 * @param int $bid_id Bid id.
	 */
	public function withdraw_bid( int $bid_id ): bool {
		return $this->set_bid_status( $bid_id, 'withdrawn' );
	}

	/**
	 * Reject a bid (only from 'pending').
	 *
	 * @param int $bid_id Bid id.
	 */
	public function reject_bid( int $bid_id ): bool {
		return $this->set_bid_status( $bid_id, 'rejected' );
	}

	/**
	 * Move a pending bid to a terminal state. Returns false when the bid is
	 * not currently pending (already accepted/rejected/withdrawn).
	 *
	 * @param int    $bid_id Bid id.
	 * @param string $status Status.
	 */
	private function set_bid_status( int $bid_id, string $status ): bool {
		return $this->wpdb->update(
			$this->table_bids,
			array(
				'status'     => $status,
				'updated_at' => current_time( 'mysql' ),
			),
			array(
				'id'     => $bid_id,
				'status' => 'pending',
			)
		) > 0;
	}

	/**
	 * Award a bid to a freelancer: marks the winning bid accepted, rejects the
	 * remaining pending bids, marks the project awarded and creates a contract.
	 *
	 * @return int Contract id, or 0 when the bid/project cannot be awarded.
	 * @param int $bid_id Bid id.
	 */
	public function award_bid( int $bid_id ): int {
		$bid     = $this->get_bid( $bid_id );
		$project = $bid ? $this->get_project( (int) $bid->project_id ) : null;

		if ( ! $bid || ! $project || 'pending' !== $bid->status || 'open' !== $project->status ) {
			return 0;
		}

		$now = current_time( 'mysql' );

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		$this->wpdb->query(
			$this->wpdb->prepare(
				"UPDATE {$this->table_bids} SET status = 'rejected', updated_at = %s
			WHERE project_id = %d AND id <> %d AND status = 'pending'",
				$now,
				(int) $project->id,
				$bid_id
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery

		$this->update_bid( $bid_id, array( 'status' => 'accepted' ) );
		$this->update_project( (int) $project->id, array( 'status' => 'awarded' ) );

		return $this->insert_contract(
			array(
				'project_id'    => (int) $project->id,
				'bid_id'        => $bid_id,
				'client_id'     => (int) $project->user_id,
				'freelancer_id' => (int) $bid->user_id,
				'budget'        => (float) $bid->amount,
				'currency'      => $project->currency ?: 'USD',
				'escrow_amount' => (float) $bid->amount,
				'status'        => 'pending',
			)
		);
	}

	/**
	 * All bids across the platform (for the admin management screen).
	 *
	 * @return object[]
	 * @param ?string $status * @param int         $limit.
	 * @param int     $limit Limit.
	 */
	public function get_all_bids( ?string $status = null, int $limit = 200 ): array {
		$sql  = "SELECT * FROM {$this->table_bids}";
		$vars = array();
		if ( null !== $status ) {
			$sql   .= ' WHERE status = %s';
			$vars[] = $status;
		}
		$sql   .= ' ORDER BY created_at DESC LIMIT %d';
		$vars[] = $limit;

		return (array) $this->wpdb->get_results( $this->wpdb->prepare( $sql, $vars ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	/**
	 * User bids.
	 *
	 * @return object[]
	 * @param int    $user_id User id.
	 * @param string $status Status.
	 */
	public function get_user_bids( int $user_id, string $status = '' ): array {
		$sql  = "SELECT * FROM {$this->table_bids} WHERE user_id = %d";
		$vars = array( $user_id );
		if ( '' !== $status ) {
			$sql   .= ' AND status = %s';
			$vars[] = $status;
		}
		$sql .= ' ORDER BY created_at DESC';

		return (array) $this->wpdb->get_results( $this->wpdb->prepare( $sql, $vars ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	// ═══════════════════════════════════════════════════════════════.
	// CONTRACTS.
	// ═══════════════════════════════════════════════════════════════.

	/**
	 * Insert contract.
	 *
	 * @param array $data Data.
	 */
	public function insert_contract( array $data ): int {
		$now = current_time( 'mysql' );
		$this->wpdb->insert(
			$this->table_contracts,
			array_merge(
				array(
					'project_id'    => 0,
					'bid_id'        => 0,
					'client_id'     => 0,
					'freelancer_id' => 0,
					'budget'        => 0.00,
					'currency'      => 'USD',
					'escrow_amount' => 0.00,
					'status'        => 'pending',
					'created_at'    => $now,
					'updated_at'    => $now,
				),
				$data
			),
			array( '%d', '%d', '%d', '%d', '%f', '%s', '%f', '%s', '%s', '%s' )
		);

		return (int) $this->wpdb->insert_id;
	}

	// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	/**
	 * Contract.
	 *
	 * @param int $id Id.
	 */
	public function get_contract( int $id ): ?object {
		return $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT * FROM {$this->table_contracts} WHERE id = %d",
				$id
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	/**
	 * Update contract.
	 *
	 * @param int   $id Id.
	 * @param array $data Data.
	 */
	public function update_contract( int $id, array $data ): bool {
		$data['updated_at'] = current_time( 'mysql' );
		return false !== $this->wpdb->update( $this->table_contracts, $data, array( 'id' => $id ) );
	}

	/**
	 * Contracts for user.
	 *
	 * @return object[]
	 * @param int     $user_id Contract party (client or freelancer).
	 * @param ?string $status * @return object[].
	 */
	public function get_contracts_for_user( int $user_id, ?string $status = null ): array {
		$sql  = "SELECT * FROM {$this->table_contracts} WHERE client_id = %d OR freelancer_id = %d";
		$vars = array( $user_id, $user_id );
		if ( null !== $status ) {
			$sql   .= ' AND status = %s';
			$vars[] = $status;
		}
		$sql .= ' ORDER BY created_at DESC';

		return (array) $this->wpdb->get_results( $this->wpdb->prepare( $sql, $vars ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	/**
	 * All contracts across the platform (for the admin management screen).
	 *
	 * @return object[]
	 * @param ?string $status * @param int         $limit.
	 * @param int     $limit Limit.
	 */
	public function get_contracts( ?string $status = null, int $limit = 200 ): array {
		$sql  = "SELECT * FROM {$this->table_contracts}";
		$vars = array();
		if ( null !== $status ) {
			$sql   .= ' WHERE status = %s';
			$vars[] = $status;
		}
		$sql   .= ' ORDER BY created_at DESC LIMIT %d';
		$vars[] = $limit;

		return (array) $this->wpdb->get_results( $this->wpdb->prepare( $sql, $vars ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	/**
	 * The (single) contract linked to a project.
	 *
	 * @param int $project_id Project id.
	 */
	public function get_contract_for_project( int $project_id ): ?object {
		return $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT * FROM {$this->table_contracts} WHERE project_id = %d ORDER BY id ASC LIMIT 1",
				$project_id
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	// ═══════════════════════════════════════════════════════════════.
	// MILESTONES.
	// ═══════════════════════════════════════════════════════════════.

	/**
	 * Insert milestone.
	 *
	 * @param array $data Data.
	 */
	public function insert_milestone( array $data ): int {
		$now = current_time( 'mysql' );
		$this->wpdb->insert(
			$this->table_milestones,
			array_merge(
				array(
					'contract_id' => 0,
					'title'       => '',
					'description' => '',
					'amount'      => 0.00,
					'status'      => 'pending',
					'due_at'      => null,
					'created_at'  => $now,
					'updated_at'  => $now,
				),
				$data
			),
			array( '%d', '%s', '%s', '%f', '%s', '%s', '%s', '%s' )
		);

		return (int) $this->wpdb->insert_id;
	}

	// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	/**
	 * Milestone.
	 *
	 * @param int $id Id.
	 */
	public function get_milestone( int $id ): ?object {
		return $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT * FROM {$this->table_milestones} WHERE id = %d",
				$id
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	/**
	 * Update milestone.
	 *
	 * @param int   $id Id.
	 * @param array $data Data.
	 */
	public function update_milestone( int $id, array $data ): bool {
		$data['updated_at'] = current_time( 'mysql' );
		return false !== $this->wpdb->update( $this->table_milestones, $data, array( 'id' => $id ) );
	}

	/**
	 * Delete milestone.
	 *
	 * @param int $id Id.
	 */
	public function delete_milestone( int $id ): bool {
		return false !== $this->wpdb->delete( $this->table_milestones, array( 'id' => $id ) );
	}

	// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	/**
	 * Milestones.
	 *
	 * @return object[]
	 * @param int $contract_id Contract id.
	 */
	public function get_milestones( int $contract_id ): array {
		return (array) $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT * FROM {$this->table_milestones} WHERE contract_id = %d ORDER BY due_at ASC, id ASC",
				$contract_id
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	/**
	 * Start the review SLA clock on a milestone (client deadline to approve
	 * or reject once it lands in review). Deadline = now + review_sla_days.
	 *
	 * @param int $milestone_id Milestone id.
	 */
	public function set_milestone_review_sla( int $milestone_id ): void {
		$settings = zeko_freelance_get_settings();
		$days     = max( 1, (int) $settings['review_sla_days'] );

		$this->update_milestone(
			$milestone_id,
			array(
				'review_due_at'  => date( 'Y-m-d H:i:s', time() + $days * DAY_IN_SECONDS ), // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
			'review_reminded_at' => null,
			)
		);
	}

	/**
	 * Start the submission SLA clock on a milestone (freelancer deadline to
	 * submit funded work). Deadline = max(due_at, now) + submit_sla_days.
	 *
	 * @param int $milestone_id Milestone id.
	 */
	public function set_milestone_submit_sla( int $milestone_id ): void {
		$milestone = $this->get_milestone( $milestone_id );
		if ( ! $milestone ) {
			return;
		}

		$settings = zeko_freelance_get_settings();
		$days     = max( 1, (int) $settings['submit_sla_days'] );

		$base = strtotime( (string) $milestone->due_at );
		if ( ! $base || $base < time() ) {
			$base = time();
		}

		$this->update_milestone(
			$milestone_id,
			array(
				'submit_due_at'  => date( 'Y-m-d H:i:s', $base + $days * DAY_IN_SECONDS ), // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
			'submit_reminded_at' => null,
			)
		);
	}

	// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	/**
	 * Milestones in review whose SLA deadline has passed (candidates for the
	 * auto-approve timer).
	 *
	 * @return object[]
	 */
	public function get_review_overdue_milestones(): array {
		return (array) $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT * FROM {$this->table_milestones}
			WHERE status = 'in_review' AND review_due_at IS NOT NULL AND review_due_at < %s
			ORDER BY review_due_at ASC",
				current_time( 'mysql' )
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	/**
	 * In-review milestones whose SLA deadline falls inside a window and that
	 * have not yet been reminded (reminder emails).
	 *
	 * @return object[]
	 * @param string $start Start.
	 * @param string $end End.
	 */
	public function get_review_due_soon( string $start, string $end ): array {
		return (array) $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT * FROM {$this->table_milestones}
			WHERE status = 'in_review' AND review_due_at IS NOT NULL
				AND review_due_at > %s AND review_due_at <= %s AND review_reminded_at IS NULL
			ORDER BY review_due_at ASC",
				$start,
				$end
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	/**
	 * Funded milestones whose submission SLA deadline has passed (candidates
	 * for the auto-dispute escalation timer).
	 *
	 * @return object[]
	 */
	public function get_submission_overdue_milestones(): array {
		return (array) $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT * FROM {$this->table_milestones}
			WHERE status = 'funded' AND submit_due_at IS NOT NULL AND submit_due_at < %s
			ORDER BY submit_due_at ASC",
				current_time( 'mysql' )
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	/**
	 * Funded milestones whose submission SLA deadline falls inside a window
	 * and that have not yet been reminded.
	 *
	 * @return object[]
	 * @param string $start Start.
	 * @param string $end End.
	 */
	public function get_submission_due_soon( string $start, string $end ): array {
		return (array) $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT * FROM {$this->table_milestones}
			WHERE status = 'funded' AND submit_due_at IS NOT NULL
				AND submit_due_at > %s AND submit_due_at <= %s AND submit_reminded_at IS NULL
			ORDER BY submit_due_at ASC",
				$start,
				$end
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	/**
	 * Milestones holding funds (everything that has been pulled out of the
	 * client's escrow but not yet released).
	 *
	 * @return object[]
	 * @param int $contract_id Contract id.
	 */
	public function funded_milestones( int $contract_id ): array {
		return (array) $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT * FROM {$this->table_milestones} WHERE contract_id = %d AND status IN ('funded','in_review','rejected') ORDER BY due_at ASC, id ASC",
				$contract_id
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	/**
	 * Totals for a contract's milestones: count, total amount, how much has
	 * been funded (pulled into escrow), how much has been paid out and how
	 * much is currently held.
	 *
	 * @param int $contract_id Contract id.
	 */
	public function milestone_totals( int $contract_id ): array {
		$row = $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT
				COUNT(*) AS count,
				COALESCE(SUM(amount), 0) AS total,
				COALESCE(SUM(CASE WHEN status <> 'pending' THEN amount ELSE 0 END), 0) AS funded,
				COALESCE(SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END), 0) AS paid,
				COALESCE(SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END), 0) AS paid_count
			FROM {$this->table_milestones} WHERE contract_id = %d",
				$contract_id
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery

		if ( ! $row ) {
			return array(
				'count'      => 0,
				'total'      => 0.0,
				'funded'     => 0.0,
				'paid'       => 0.0,
				'held'       => 0.0,
				'paid_count' => 0,
			);
		}

		$funded = (float) $row->funded;
		$paid   = (float) $row->paid;

		return array(
			'count'      => (int) $row->count,
			'total'      => (float) $row->total,
			'funded'     => $funded,
			'paid'       => $paid,
			'held'       => $funded - $paid,
			'paid_count' => (int) $row->paid_count,
		);
	}

	/**
	 * Whether the Zeko Pay escrow bridge is available.
	 */
	public function escrow_enabled(): bool {
		return class_exists( 'Zeko_Pay_Integrations' ) && method_exists( 'Zeko_Pay_Integrations', 'freelance_fund_milestone' );
	}

	/**
	 * Pull a milestone amount from the client's Zeko Pay wallet into escrow
	 * and mark the milestone as funded. Returns a result array with a
	 * `success` key (plus `tx_id` and `message` when available).
	 *
	 * @param int $milestone_id Milestone id.
	 */
	public function fund_milestone( int $milestone_id ): array {
		$milestone = $this->get_milestone( $milestone_id );
		$contract  = $milestone ? $this->get_contract( (int) $milestone->contract_id ) : null;

		if ( ! $milestone || ! $contract ) {
			return array(
				'success' => false,
				'message' => __( 'Milestone not found.', 'zeko-freelance' ),
			);
		}

		if ( 'pending' !== $milestone->status ) {
			return array(
				'success' => false,
				'message' => __( 'This milestone has already been funded.', 'zeko-freelance' ),
			);
		}

		if ( ! in_array( $contract->status, array( 'pending', 'active' ), true ) ) {
			return array(
				'success' => false,
				'message' => __( 'This contract is not active.', 'zeko-freelance' ),
			);
		}

		$amount = (float) $milestone->amount;
		if ( $amount <= 0 ) {
			return array(
				'success' => false,
				'message' => __( 'Milestone amount must be greater than zero.', 'zeko-freelance' ),
			);
		}

		$totals = $this->milestone_totals( (int) $contract->id );
		if ( $totals['funded'] + $amount > (float) $contract->escrow_amount ) {
			return array(
				'success' => false,
				'message' => __( 'Funding this milestone would exceed the contract escrow.', 'zeko-freelance' ),
			);
		}

		$result = $this->escrow_hold( (int) $contract->client_id, (int) $contract->project_id, (int) $milestone->id, $amount );
		if ( empty( $result['success'] ) ) {
			return array(
				'success' => false,
				'message' => ! empty( $result['message'] ) ? $result['message'] : __( 'Could not fund the milestone.', 'zeko-freelance' ),
			);
		}

		$this->update_milestone(
			(int) $milestone->id,
			array(
				'status' => 'funded',
				'tx_id'  => ! empty( $result['tx_id'] ) ? absint( $result['tx_id'] ) : 0,
			)
		);
		$this->set_milestone_submit_sla( (int) $milestone->id );

		if ( 'pending' === $contract->status ) {
			$this->update_contract(
				(int) $contract->id,
				array(
					'status'    => 'active',
					'funded_at' => current_time( 'mysql' ),
				)
			);
		}

		do_action( 'zeko_freelance_milestone_funded', (int) $milestone->id, (int) $contract->id, (int) ( $result['tx_id'] ?? 0 ) );

		return array(
			'success' => true,
			'tx_id'   => ! empty( $result['tx_id'] ) ? (int) $result['tx_id'] : 0,
			'message' => __( 'Milestone funded.', 'zeko-freelance' ),
		);
	}

	/**
	 * Move a funded milestone into review (freelancer submission). Returns
	 * false when the milestone cannot be submitted.
	 *
	 * @param int $milestone_id Milestone id.
	 */
	public function submit_milestone_for_review( int $milestone_id ): bool {
		$milestone = $this->get_milestone( $milestone_id );
		if ( ! $milestone || ! in_array( $milestone->status, array( 'funded', 'rejected' ), true ) ) {
			return false;
		}

		$contract = $this->get_contract( (int) $milestone->contract_id );
		if ( ! $contract || in_array( $contract->status, array( 'cancelled', 'completed' ), true ) ) {
			return false;
		}

		$this->update_milestone(
			$milestone_id,
			array(
				'status'             => 'in_review',
				'submit_due_at'      => null,
				'submit_reminded_at' => null,
			)
		);
		$this->set_milestone_review_sla( $milestone_id );
		do_action( 'zeko_freelance_milestone_submitted', $milestone_id, (int) $milestone->contract_id );

		return true;
	}

	/**
	 * Client approves an in-review milestone: release the escrowed funds to
	 * the freelancer and mark the milestone paid. Completes the contract
	 * (and its project) when every milestone is paid.
	 *
	 * @param int $milestone_id Milestone id.
	 */
	public function approve_milestone( int $milestone_id ): array {
		$milestone = $this->get_milestone( $milestone_id );
		$contract  = $milestone ? $this->get_contract( (int) $milestone->contract_id ) : null;

		if ( ! $milestone || ! $contract ) {
			return array(
				'success' => false,
				'message' => __( 'Milestone not found.', 'zeko-freelance' ),
			);
		}

		if ( 'in_review' !== $milestone->status ) {
			return array(
				'success' => false,
				'message' => __( 'This milestone is not awaiting approval.', 'zeko-freelance' ),
			);
		}

		if ( 'disputed' === $contract->status ) {
			return array(
				'success' => false,
				'message' => __( 'This contract has an open dispute.', 'zeko-freelance' ),
			);
		}

		$result = $this->escrow_release( (int) $contract->freelancer_id, (int) $milestone->id, (float) $milestone->amount );
		if ( empty( $result['success'] ) ) {
			return array(
				'success' => false,
				'message' => ! empty( $result['message'] ) ? $result['message'] : __( 'Could not release the milestone payment.', 'zeko-freelance' ),
			);
		}

		$this->update_milestone(
			(int) $milestone->id,
			array(
				'status'             => 'paid',
				'release_tx_id'      => ! empty( $result['tx_id'] ) ? absint( $result['tx_id'] ) : 0,
				'review_due_at'      => null,
				'review_reminded_at' => null,
				'submit_due_at'      => null,
				'submit_reminded_at' => null,
			)
		);

		$totals     = $this->milestone_totals( (int) $contract->id );
		$all_paid   = $totals['count'] > 0 && $totals['count'] === $totals['paid_count'];
		$release_id = ! empty( $result['tx_id'] ) ? (int) $result['tx_id'] : 0;

		// A milestone is approved whether or not it is the last one, so fire.
		// the milestone hook in both cases before handling contract completion.
		do_action( 'zeko_freelance_milestone_approved', (int) $milestone->id, (int) $contract->id, $release_id, (int) $contract->freelancer_id );

		if ( $all_paid ) {
			$this->update_contract( (int) $contract->id, array( 'status' => 'completed' ) );
			$project = $this->get_project( (int) $contract->project_id );
			if ( $project ) {
				$this->update_project( (int) $project->id, array( 'status' => 'completed' ) );
			}
			do_action( 'zeko_freelance_contract_completed', (int) $contract->id, (int) $contract->project_id );

			return array(
				'success'       => true,
				'release_tx_id' => $release_id,
				'message'       => __( 'Milestone approved and contract completed.', 'zeko-freelance' ),
			);
		}

		return array(
			'success'       => true,
			'release_tx_id' => $release_id,
			'message'       => __( 'Milestone approved and payment released.', 'zeko-freelance' ),
		);
	}

	/**
	 * Client sends an in-review milestone back for more work. The funds stay
	 * in escrow and the freelancer can resubmit.
	 *
	 * @param int $milestone_id Milestone id.
	 */
	public function reject_milestone( int $milestone_id ): bool {
		$milestone = $this->get_milestone( $milestone_id );
		if ( ! $milestone || 'in_review' !== $milestone->status ) {
			return false;
		}

		$this->update_milestone(
			$milestone_id,
			array(
				'status'             => 'rejected',
				'review_due_at'      => null,
				'review_reminded_at' => null,
			)
		);
		do_action( 'zeko_freelance_milestone_rejected', $milestone_id, (int) $milestone->contract_id );

		return true;
	}

	/**
	 * Cancel a contract: refund every funded milestone back to the client's
	 * wallet and mark the contract (and its project) cancelled.
	 *
	 * @param int $contract_id Contract id.
	 */
	public function cancel_contract( int $contract_id ): array {
		$contract = $this->get_contract( $contract_id );
		if ( ! $contract ) {
			return array(
				'success' => false,
				'message' => __( 'Contract not found.', 'zeko-freelance' ),
			);
		}

		if ( in_array( $contract->status, array( 'completed', 'cancelled' ), true ) ) {
			return array(
				'success' => false,
				'message' => __( 'This contract can no longer be cancelled.', 'zeko-freelance' ),
			);
		}

		foreach ( $this->funded_milestones( (int) $contract->id ) as $milestone ) {
			$tx_id = (int) $milestone->tx_id;
			if ( $tx_id > 0 ) {
				$result = $this->escrow_refund( $tx_id, (int) $milestone->id );
				if ( empty( $result['success'] ) ) {
					return array(
						'success' => false,
						'message' => ! empty( $result['message'] ) ? $result['message'] : __( 'Could not refund a funded milestone.', 'zeko-freelance' ),
					);
				}
			}
			$this->update_milestone(
				(int) $milestone->id,
				array(
					'status'             => 'pending',
					'tx_id'              => 0,
					'release_tx_id'      => 0,
					'review_due_at'      => null,
					'review_reminded_at' => null,
					'submit_due_at'      => null,
					'submit_reminded_at' => null,
				)
			);
		}

		$this->update_contract( $contract_id, array( 'status' => 'cancelled' ) );
		$project = $this->get_project( (int) $contract->project_id );
		if ( $project && 'cancelled' !== $project->status ) {
			$this->update_project( (int) $project->id, array( 'status' => 'cancelled' ) );
		}

		do_action( 'zeko_freelance_contract_cancelled', $contract_id, (int) $contract->project_id );

		return array(
			'success' => true,
			'message' => __( 'Contract cancelled and escrow refunded.', 'zeko-freelance' ),
		);
	}

	// ═══════════════════════════════════════════════════════════════.
	// DISPUTES.
	// ═══════════════════════════════════════════════════════════════.

	/**
	 * Insert dispute.
	 *
	 * @param array $data Data.
	 */
	public function insert_dispute( array $data ): int {
		$now = current_time( 'mysql' );
		$this->wpdb->insert(
			$this->table_disputes,
			array_merge(
				array(
					'contract_id' => 0,
					'user_id'     => 0,
					'subject'     => '',
					'description' => '',
					'status'      => 'open',
					'resolution'  => null,
					'created_at'  => $now,
					'updated_at'  => $now,
				),
				$data
			),
			array( '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		return (int) $this->wpdb->insert_id;
	}

	// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	/**
	 * Dispute.
	 *
	 * @param int $id Id.
	 */
	public function get_dispute( int $id ): ?object {
		return $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT * FROM {$this->table_disputes} WHERE id = %d",
				$id
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	/**
	 * Update dispute.
	 *
	 * @param int   $id Id.
	 * @param array $data Data.
	 */
	public function update_dispute( int $id, array $data ): bool {
		$data['updated_at'] = current_time( 'mysql' );
		return false !== $this->wpdb->update( $this->table_disputes, $data, array( 'id' => $id ) );
	}

	// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	/**
	 * Disputes for contract.
	 *
	 * @return object[]
	 * @param int $contract_id Contract id.
	 */
	public function get_disputes_for_contract( int $contract_id ): array {
		return (array) $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT * FROM {$this->table_disputes} WHERE contract_id = %d ORDER BY created_at DESC",
				$contract_id
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	/**
	 * All disputes across the platform (for the admin management screen).
	 *
	 * @return object[]
	 * @param string $status Status.
	 * @param int    $limit Limit.
	 */
	public function get_disputes( string $status = '', int $limit = 200 ): array {
		$sql  = "SELECT * FROM {$this->table_disputes}";
		$vars = array();
		if ( '' !== $status ) {
			$sql   .= ' WHERE status = %s';
			$vars[] = $status;
		}
		$sql   .= ' ORDER BY created_at DESC LIMIT %d';
		$vars[] = $limit;

		return (array) $this->wpdb->get_results( $this->wpdb->prepare( $sql, $vars ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	/**
	 * Open a dispute on a contract (only one open dispute at a time) and put
	 * the contract in the disputed state. Returns the dispute id or 0.
	 *
	 * @param int    $contract_id Contract id.
	 * @param int    $user_id User id.
	 * @param string $subject Subject.
	 * @param string $description Description.
	 */
	public function open_dispute( int $contract_id, int $user_id, string $subject, string $description ): int {
		$contract = $this->get_contract( $contract_id );
		if ( ! $contract || in_array( $contract->status, array( 'completed', 'cancelled' ), true ) ) {
			return 0;
		}

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		$existing = (int) $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT id FROM {$this->table_disputes} WHERE contract_id = %d AND status = 'open' LIMIT 1",
				$contract_id
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		if ( $existing > 0 ) {
			return 0;
		}

		$dispute_id = $this->insert_dispute(
			array(
				'contract_id' => $contract_id,
				'user_id'     => $user_id,
				'subject'     => $subject,
				'description' => $description,
			)
		);
		if ( $dispute_id > 0 ) {
			$this->update_contract( $contract_id, array( 'status' => 'disputed' ) );
			do_action( 'zeko_freelance_dispute_opened', $dispute_id, $contract_id, $user_id );
		}

		return $dispute_id;
	}

	/**
	 * Admin resolves an open dispute. `$action` is either `refund_client` or
	 * `release_freelancer`:
	 * - refund_client: every funded milestone is refunded to the client and
	 * the contract is cancelled.
	 * - release_freelancer: every funded milestone is released to the
	 * freelancer and the contract is completed.
	 * The dispute is marked resolved with the admin's note.
	 *
	 * @param int    $dispute_id Dispute id.
	 * @param string $action Action.
	 * @param string $resolution Resolution.
	 */
	public function resolve_dispute( int $dispute_id, string $action, string $resolution ): bool {
		$dispute = $this->get_dispute( $dispute_id );
		if ( ! $dispute || 'open' !== $dispute->status ) {
			return false;
		}

		$contract = $this->get_contract( (int) $dispute->contract_id );
		if ( ! $contract ) {
			return false;
		}

		if ( 'refund_client' === $action ) {
			foreach ( $this->funded_milestones( (int) $contract->id ) as $milestone ) {
				$tx_id = (int) $milestone->tx_id;
				if ( $tx_id > 0 ) {
					$result = $this->escrow_refund( $tx_id, (int) $milestone->id );
					if ( empty( $result['success'] ) ) {
						return false;
					}
				}
				$this->update_milestone(
					(int) $milestone->id,
					array(
						'status'             => 'pending',
						'tx_id'              => 0,
						'release_tx_id'      => 0,
						'review_due_at'      => null,
						'review_reminded_at' => null,
						'submit_due_at'      => null,
						'submit_reminded_at' => null,
					)
				);
			}
			$this->update_contract( (int) $contract->id, array( 'status' => 'cancelled' ) );
			$project = $this->get_project( (int) $contract->project_id );
			if ( $project && 'cancelled' !== $project->status ) {
				$this->update_project( (int) $project->id, array( 'status' => 'cancelled' ) );
			}
		} elseif ( 'release_freelancer' === $action ) {
			foreach ( $this->funded_milestones( (int) $contract->id ) as $milestone ) {
				$result = $this->escrow_release( (int) $contract->freelancer_id, (int) $milestone->id, (float) $milestone->amount );
				if ( empty( $result['success'] ) ) {
					return false;
				}
				$this->update_milestone(
					(int) $milestone->id,
					array(
						'status'             => 'paid',
						'release_tx_id'      => ! empty( $result['tx_id'] ) ? absint( $result['tx_id'] ) : 0,
						'review_due_at'      => null,
						'review_reminded_at' => null,
						'submit_due_at'      => null,
						'submit_reminded_at' => null,
					)
				);
			}

			$totals   = $this->milestone_totals( (int) $contract->id );
			$all_done = $totals['count'] > 0 && $totals['count'] === $totals['paid_count'];
			$this->update_contract( (int) $contract->id, array( 'status' => $all_done ? 'completed' : 'active' ) );
			if ( $all_done ) {
				$project = $this->get_project( (int) $contract->project_id );
				if ( $project ) {
					$this->update_project( (int) $project->id, array( 'status' => 'completed' ) );
				}
				do_action( 'zeko_freelance_contract_completed', (int) $contract->id, (int) $contract->project_id );
			}
		} else {
			return false;
		}

		$this->update_dispute(
			$dispute_id,
			array(
				'status'     => 'resolved',
				'resolution' => $resolution,
			)
		);
		do_action( 'zeko_freelance_dispute_resolved', $dispute_id, (int) $contract->id, $action );

		return true;
	}

	/**
	 * Zeko Pay escrow seams. Each one is filterable so payment integrations
	 * can be stubbed in tests or swapped for another provider.
	 *
	 * @param int   $client_id Client id.
	 * @param int   $project_id Project id.
	 * @param int   $milestone_id Milestone id.
	 * @param float $amount Amount.
	 */
	private function escrow_hold( int $client_id, int $project_id, int $milestone_id, float $amount ): array {
		if ( zeko_freelance_is_demo_user( $client_id ) ) {
			return array(
				'success' => false,
				'message' => __( 'Demo accounts cannot fund milestones.', 'zeko-freelance' ),
			);
		}

		$result = apply_filters( 'zeko_freelance_escrow_hold', false, $client_id, $project_id, $milestone_id, $amount );
		if ( is_array( $result ) ) {
			return $result;
		}
		if ( ! $this->escrow_enabled() ) {
			return array(
				'success' => false,
				'message' => __( 'Payments are not enabled.', 'zeko-freelance' ),
			);
		}

		return Zeko_Pay_Integrations::instance()->freelance_fund_milestone( $client_id, $project_id, $milestone_id, $amount );
	}

	/**
	 * Escrow release.
	 *
	 * @param int   $freelancer_id Freelancer id.
	 * @param int   $milestone_id Milestone id.
	 * @param float $amount Amount.
	 */
	private function escrow_release( int $freelancer_id, int $milestone_id, float $amount ): array {
		if ( zeko_freelance_is_demo_user( $freelancer_id ) ) {
			return array(
				'success' => false,
				'message' => __( 'Demo accounts cannot receive milestone payments.', 'zeko-freelance' ),
			);
		}

		$result = apply_filters( 'zeko_freelance_escrow_release', false, $freelancer_id, $milestone_id, $amount );
		if ( is_array( $result ) ) {
			return $result;
		}
		if ( ! $this->escrow_enabled() ) {
			return array(
				'success' => false,
				'message' => __( 'Payments are not enabled.', 'zeko-freelance' ),
			);
		}

		return Zeko_Pay_Integrations::instance()->freelance_release_milestone( $freelancer_id, $milestone_id, $amount );
	}

	/**
	 * Escrow refund.
	 *
	 * @param int $tx_id Tx id.
	 * @param int $milestone_id Milestone id.
	 */
	private function escrow_refund( int $tx_id, int $milestone_id ): array {
		$result = apply_filters( 'zeko_freelance_escrow_refund', false, $tx_id, $milestone_id );
		if ( is_array( $result ) ) {
			return $result;
		}
		if ( ! class_exists( 'Zeko_Pay_SDK' ) ) {
			return array(
				'success' => false,
				'message' => __( 'Payments are not enabled.', 'zeko-freelance' ),
			);
		}

		return ( new Zeko_Pay_SDK() )->refund( $tx_id, 'Milestone ' . $milestone_id . ' refunded on contract cancellation' );
	}

	// ═══════════════════════════════════════════════════════════════.
	// PORTFOLIOS.
	// ═══════════════════════════════════════════════════════════════.

	/**
	 * Insert portfolio.
	 *
	 * @param array $data Data.
	 */
	public function insert_portfolio( array $data ): int {
		$now = current_time( 'mysql' );
		$this->wpdb->insert(
			$this->table_portfolios,
			array_merge(
				array(
					'user_id'     => 0,
					'title'       => '',
					'description' => '',
					'category'    => '',
					'skills'      => '',
					'link'        => '',
					'image_url'   => '',
					'created_at'  => $now,
					'updated_at'  => $now,
				),
				$data
			),
			array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		return (int) $this->wpdb->insert_id;
	}

	// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	/**
	 * Portfolio.
	 *
	 * @param int $id Id.
	 */
	public function get_portfolio( int $id ): ?object {
		return $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT * FROM {$this->table_portfolios} WHERE id = %d",
				$id
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	/**
	 * Update portfolio.
	 *
	 * @param int   $id Id.
	 * @param array $data Data.
	 */
	public function update_portfolio( int $id, array $data ): bool {
		$data['updated_at'] = current_time( 'mysql' );
		return false !== $this->wpdb->update( $this->table_portfolios, $data, array( 'id' => $id ) );
	}

	/**
	 * Delete portfolio.
	 *
	 * @param int $id Id.
	 */
	public function delete_portfolio( int $id ): bool {
		return false !== $this->wpdb->delete( $this->table_portfolios, array( 'id' => $id ) );
	}

	/**
	 * Portfolios.
	 *
	 * @return object[]
	 * @param int   $user_id User id.
	 * @param array $args Args.
	 */
	public function get_portfolios( int $user_id = 0, array $args = array() ): array {
		$where = array( '1=1' );
		$vars  = array();

		if ( $user_id > 0 ) {
			$where[] = 'user_id = %d';
			$vars[]  = $user_id;
		}
		if ( ! empty( $args['category'] ) ) {
			$where[] = 'category = %s';
			$vars[]  = $args['category'];
		}
		if ( ! empty( $args['search'] ) ) {
			$where[] = '(title LIKE %s OR description LIKE %s OR skills LIKE %s)';
			$like    = '%' . $this->wpdb->esc_like( $args['search'] ) . '%';
			$vars[]  = $like;
			$vars[]  = $like;
			$vars[]  = $like;
		}
		if ( ! empty( $args['skill'] ) ) {
			$where[] = 'skills LIKE %s';
			$vars[]  = '%' . $this->wpdb->esc_like( $args['skill'] ) . '%';
		}

		$per_page = isset( $args['per_page'] ) ? max( 1, (int) $args['per_page'] ) : 20;
		$page     = isset( $args['page'] ) ? max( 1, (int) $args['page'] ) : 1;
		$offset   = ( $page - 1 ) * $per_page;

		$sql = 'SELECT * FROM ' . $this->table_portfolios
			. ' WHERE ' . implode( ' AND ', $where )
			. ' ORDER BY created_at DESC LIMIT %d OFFSET %d';

		$vars[] = $per_page;
		$vars[] = $offset;

		return (array) $this->wpdb->get_results( $this->wpdb->prepare( $sql, $vars ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	/**
	 * Count portfolios matching the same filters as get_portfolios(), for
	 * pagination.
	 *
	 * @param int   $user_id User id.
	 * @param array $args Args.
	 */
	public function count_portfolios( int $user_id = 0, array $args = array() ): int {
		$where = array( '1=1' );
		$vars  = array();

		if ( $user_id > 0 ) {
			$where[] = 'user_id = %d';
			$vars[]  = $user_id;
		}
		if ( ! empty( $args['category'] ) ) {
			$where[] = 'category = %s';
			$vars[]  = $args['category'];
		}
		if ( ! empty( $args['search'] ) ) {
			$where[] = '(title LIKE %s OR description LIKE %s OR skills LIKE %s)';
			$like    = '%' . $this->wpdb->esc_like( $args['search'] ) . '%';
			$vars[]  = $like;
			$vars[]  = $like;
			$vars[]  = $like;
		}
		if ( ! empty( $args['skill'] ) ) {
			$where[] = 'skills LIKE %s';
			$vars[]  = '%' . $this->wpdb->esc_like( $args['skill'] ) . '%';
		}

		$sql = 'SELECT COUNT(*) FROM ' . $this->table_portfolios . ' WHERE ' . implode( ' AND ', $where );

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		return (int) ( $vars
			? $this->wpdb->get_var( $this->wpdb->prepare( $sql, $vars ) )
			: $this->wpdb->get_var( $sql ) );
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	// ═══════════════════════════════════════════════════════════════.
	// REVIEWS.
	// ═══════════════════════════════════════════════════════════════.

	/**
	 * Insert review.
	 *
	 * @param array $data Data.
	 */
	public function insert_review( array $data ): int {
		$now = current_time( 'mysql' );
		$this->wpdb->insert(
			$this->table_reviews,
			array_merge(
				array(
					'contract_id' => 0,
					'reviewer_id' => 0,
					'reviewee_id' => 0,
					'rating'      => 5,
					'comment'     => '',
					'created_at'  => $now,
				),
				$data
			),
			array( '%d', '%d', '%d', '%d', '%s', '%s' )
		);

		return (int) $this->wpdb->insert_id;
	}

	// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	/**
	 * Reviews for.
	 *
	 * @return object[]
	 * @param int $reviewee_id Reviewee id.
	 */
	public function get_reviews_for( int $reviewee_id ): array {
		return (array) $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT * FROM {$this->table_reviews} WHERE reviewee_id = %d ORDER BY created_at DESC",
				$reviewee_id
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	/**
	 * A single review by id (or null).
	 *
	 * @param int $id Id.
	 */
	public function get_review( int $id ): ?object {
		return $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT * FROM {$this->table_reviews} WHERE id = %d",
				$id
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	/**
	 * A review left by a specific reviewer on a specific contract (or null).
	 *
	 * @param int $contract_id Contract id.
	 * @param int $reviewer_id Reviewer id.
	 */
	public function get_review_for_contract( int $contract_id, int $reviewer_id ): ?object {
		return $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT * FROM {$this->table_reviews} WHERE contract_id = %d AND reviewer_id = %d",
				$contract_id,
				$reviewer_id
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	/**
	 * Average rating.
	 *
	 * @param int $reviewee_id Reviewee id.
	 */
	public function average_rating( int $reviewee_id ): float {
		$avg = $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT AVG(rating) FROM {$this->table_reviews} WHERE reviewee_id = %d",
				$reviewee_id
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery

		return $avg ? round( (float) $avg, 2 ) : 0.0;
	}

	// ═══════════════════════════════════════════════════════════════.
	// VERIFICATIONS.
	// ═══════════════════════════════════════════════════════════════.

	/**
	 * Insert verification.
	 *
	 * @param array $data Data.
	 */
	public function insert_verification( array $data ): int {
		$now = current_time( 'mysql' );
		$this->wpdb->insert(
			$this->table_verifications,
			array_merge(
				array(
					'user_id'      => 0,
					'type'         => 'id',
					'status'       => 'pending',
					'notes'        => null,
					'submitted_at' => $now,
					'reviewed_at'  => null,
					'reviewed_by'  => 0,
				),
				$data
			),
			array( '%d', '%s', '%s', '%s', '%s', '%s', '%d' )
		);

		return (int) $this->wpdb->insert_id;
	}

	// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	/**
	 * Verification.
	 *
	 * @param int $id Id.
	 */
	public function get_verification( int $id ): ?object {
		return $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT * FROM {$this->table_verifications} WHERE id = %d",
				$id
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	/**
	 * Update verification.
	 *
	 * @param int   $id Id.
	 * @param array $data Data.
	 */
	public function update_verification( int $id, array $data ): bool {
		return false !== $this->wpdb->update( $this->table_verifications, $data, array( 'id' => $id ) );
	}

	// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	/**
	 * User verified.
	 *
	 * @param int    $user_id User id.
	 * @param string $type Type.
	 */
	public function is_user_verified( int $user_id, string $type = 'id' ): bool {
		$status = $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT status FROM {$this->table_verifications} WHERE user_id = %d AND type = %s ORDER BY id DESC LIMIT 1",
				$user_id,
				$type
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery

		return 'approved' === $status;
	}

	/**
	 * Latest verification status for a user/type: 'approved', 'pending',
	 * 'rejected', or '' when none exists.
	 *
	 * @param int    $user_id User id.
	 * @param string $type Type.
	 */
	public function user_verification_status( int $user_id, string $type = 'id' ): string {
		if ( $user_id <= 0 ) {
			return '';
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		}
		$status = $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT status FROM {$this->table_verifications} WHERE user_id = %d AND type = %s ORDER BY id DESC LIMIT 1",
				$user_id,
				$type
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery

		return (string) $status;
	}

	/**
	 * Verifications.
	 *
	 * @return object[]
	 * @param string $status Status.
	 */
	public function get_verifications( string $status = '' ): array {
		$sql = "SELECT * FROM {$this->table_verifications}";
		if ( '' !== $status ) {
			$sql .= $this->wpdb->prepare( ' WHERE status = %s', $status );
		}
		$sql .= ' ORDER BY submitted_at DESC';

		return (array) $this->wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	// ═══════════════════════════════════════════════════════════════.
	// SKILLS.
	// ═══════════════════════════════════════════════════════════════.

	/**
	 * Skills.
	 *
	 * @return object[]
	 * @param string $category Optional filter.
	 */
	public function get_skills( string $category = '' ): array {
		$sql = "SELECT * FROM {$this->table_skills}";
		if ( '' !== $category ) {
			$sql .= $this->wpdb->prepare( ' WHERE category = %s', $category );
		}
		$sql .= ' ORDER BY name ASC';

		return (array) $this->wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	/**
	 * Insert skill.
	 *
	 * @param string $name Name.
	 * @param string $category Category.
	 */
	public function insert_skill( string $name, string $category = '' ): int {
		$slug = sanitize_title( $name );

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		$existing = $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT id FROM {$this->table_skills} WHERE slug = %s",
				$slug
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		if ( $existing ) {
			return (int) $existing;
		}

		$this->wpdb->insert(
			$this->table_skills,
			array(
				'name'     => $name,
				'slug'     => $slug,
				'category' => $category,
			),
			array( '%s', '%s', '%s' )
		);

		return (int) $this->wpdb->insert_id;
	}

	/**
	 * Case-insensitive skill search used by the autocomplete endpoint.
	 *
	 * @return object[]
	 * @param string $term Search term.
	 * @param int    $limit Max results.
	 */
	public function search_skills( string $term, int $limit = 10 ): array {
		$limit = max( 1, min( 50, $limit ) );
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		if ( '' === trim( $term ) ) {
			return (array) $this->wpdb->get_results(
				$this->wpdb->prepare(
					"SELECT * FROM {$this->table_skills} ORDER BY name ASC LIMIT %d",
					$limit
				)
			);
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		}

		$like = '%' . $this->wpdb->esc_like( $term ) . '%';
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		return (array) $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT * FROM {$this->table_skills}
				 WHERE name LIKE %s OR slug LIKE %s
				 ORDER BY name ASC LIMIT %d",
				$like,
				$like,
				$limit
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	/**
	 * A single skill by id (or null).
	 *
	 * @param int $id Id.
	 */
	public function get_skill( int $id ): ?object {
		return $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT * FROM {$this->table_skills} WHERE id = %d",
				$id
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	/**
	 * Delete skill.
	 *
	 * @param int $id Id.
	 */
	public function delete_skill( int $id ): bool {
		$deleted = $this->wpdb->delete( $this->table_skills, array( 'id' => $id ), array( '%d' ) );
		return false !== $deleted && $deleted > 0;
	}

	// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	/**
	 * Number of reviews a user has received.
	 *
	 * @param int $reviewee_id Reviewee id.
	 */
	public function review_count( int $reviewee_id ): int {
		return (int) $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->table_reviews} WHERE reviewee_id = %d",
				$reviewee_id
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	/**
	 * Number of portfolio items a user has published.
	 *
	 * @param int $user_id User id.
	 */
	public function portfolio_count( int $user_id ): int {
		return (int) $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->table_portfolios} WHERE user_id = %d",
				$user_id
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	/**
	 * A user's advertised skills (stored in user meta as a flat list).
	 *
	 * @return string[]
	 * @param int $user_id User id.
	 */
	public function get_user_skills( int $user_id ): array {
		if ( $user_id <= 0 ) {
			return array();
		}

		$skills = get_user_meta( $user_id, 'zeko_freelance_skills', true );
		if ( ! is_array( $skills ) ) {
			$skills = array_filter( array_map( 'trim', explode( ',', (string) $skills ) ) );
		}

		return array_values( array_unique( array_filter( array_map( 'sanitize_text_field', $skills ) ) ) );
	}

	/**
	 * Persist a user's advertised skills (user meta, max 20 skills).
	 *
	 * @param int   $user_id User id.
	 * @param array $skills Skill names.
	 */
	public function save_user_skills( int $user_id, array $skills ): void {
		if ( $user_id <= 0 ) {
			return;
		}

		$clean = array();
		foreach ( array_slice( $skills, 0, 20 ) as $skill ) {
			$name = sanitize_text_field( (string) $skill );
			$name = mb_substr( $name, 0, 50 );
			if ( '' !== $name ) {
				$clean[ mb_strtolower( $name ) ] = $clean[ mb_strtolower( $name ) ] ?? $name;
			}
		}

		update_user_meta( $user_id, 'zeko_freelance_skills', array_values( $clean ) );
	}
}
