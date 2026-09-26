<?php
/**
 * Demo data generator for Zeko Freelance.
 *
 * Seeds realistic marketplace content — demo clients/freelancers, projects,
 * bids, contracts with milestones, a dispute, portfolios, verifications and
 * reviews — so the full flow can be exercised immediately.
 *
 * @package Zeko_Freelance
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Class Zeko_Freelance_Demo_Generator. */
class Zeko_Freelance_Demo_Generator {

	/**
	 * Db.
	 *
	 * @var Zeko_Freelance_DB Db.
	 */
	private Zeko_Freelance_DB $db;

	/**
	 * Client ids.
	 *
	 * @var array Client ids.
	 */
	private array $client_ids = array();
	/**
	 * Freelancer ids.
	 *
	 * @var array Freelancer ids.
	 */
	private array $freelancer_ids = array();
	/**
	 * Project ids.
	 *
	 * @var array Project ids.
	 */
	private array $project_ids = array();
	/**
	 * Contract ids.
	 *
	 * @var array Contract ids.
	 */
	private array $contract_ids = array();
	/**
	 * Portfolio ids.
	 *
	 * @var array Portfolio ids.
	 */
	private array $portfolio_ids = array();
	/**
	 * Verification ids.
	 *
	 * @var array Verification ids.
	 */
	private array $verification_ids = array();

	/**
	 * Awarded bid id.
	 *
	 * @var int Awarded bid id.
	 */
	private int $awarded_bid_id = 0;
	/**
	 * Completed bid id.
	 *
	 * @var int Completed bid id.
	 */
	private int $completed_bid_id = 0;

	private const DEMO_CLIENT_PREFIX     = 'demo_client_';
	private const DEMO_FREELANCER_PREFIX = 'demo_freelancer_';

	private const CLIENT_NAMES = array(
		'Amara Chen',
		'Diego Ramirez',
		'Priya Sharma',
	);

	private const FREELANCER_NAMES = array(
		'Marcus Webb',
		'Sofia Novak',
		'James Osei',
		'Hannah Lee',
		'Rafael Costa',
		'Emily Nguyen',
	);

	private const FREELANCER_SKILLS = array(
		array( 'PHP', 'WordPress', 'JavaScript', 'MySQL' ),
		array( 'Graphic Design', 'Branding', 'Adobe Illustrator' ),
		array( 'SEO', 'Copywriting', 'Research' ),
		array( 'React', 'Node.js', 'REST APIs' ),
		array( 'Video Editing', 'Adobe Premiere', 'Motion Graphics' ),
		array( 'Data Entry', 'Excel', 'CRM', 'Market Research' ),
	);

	/**
	 * PROJECTS.
	 *
	 * @var mixed
	 */
	private const PROJECTS = array(
		array(
			'client'      => 0,
			'title'       => 'Build a custom WordPress plugin for event bookings',
			'description' => 'We need a plugin that lets visitors book event tickets, pick seats and receive confirmation emails. Admin panel with a calendar overview and past-booking reporting.',
			'min'         => 800,
			'max'         => 2000,
			'category'    => 'development',
			'skills'      => 'PHP, WordPress, JavaScript',
			'duration'    => '1-3-months',
			'featured'    => 1,
		),
		array(
			'client'      => 1,
			'title'       => 'Design a brand identity for a coffee startup',
			'description' => 'Full brand package: logo, color palette, typography, business cards and social media kit. We want a warm, artisan feel that stands out on a busy shelf.',
			'min'         => 400,
			'max'         => 1200,
			'category'    => 'design',
			'skills'      => 'Graphic Design, Branding, Adobe Illustrator',
			'duration'    => 'less-than-week',
			'featured'    => 0,
		),
		array(
			'client'      => 2,
			'title'       => 'Write 10 SEO blog posts for a SaaS blog',
			'description' => 'Ten 1,200-word articles on project management for a B2B SaaS. Research-driven, keyword-optimized, with an engaging but professional tone.',
			'min'         => 250,
			'max'         => 600,
			'category'    => 'writing',
			'skills'      => 'SEO, Copywriting, Research',
			'duration'    => '1-4-weeks',
			'featured'    => 0,
		),
		array(
			'client'      => 0,
			'title'       => 'Set up and run a Facebook ads campaign',
			'description' => 'Launch and manage a lead-gen campaign for our local fitness studio. Setup, audience testing, weekly reporting and a 30-day optimization plan.',
			'min'         => 500,
			'max'         => 1500,
			'category'    => 'marketing',
			'skills'      => 'Facebook Ads, Marketing, Analytics',
			'duration'    => '1-4-weeks',
			'featured'    => 0,
		),
		array(
			'client'      => 1,
			'title'       => 'Edit 12 YouTube videos with captions',
			'description' => 'Raw footage for a tech channel. Cut to tight edits, add motion captions, color-grade and export in 1080p with branded intro/outro.',
			'min'         => 600,
			'max'         => 1400,
			'category'    => 'media',
			'skills'      => 'Video Editing, Adobe Premiere, Captions',
			'duration'    => '1-3-months',
			'featured'    => 0,
		),
		array(
			'client'      => 2,
			'title'       => 'React dashboard with a Node.js REST API',
			'description' => 'A metrics dashboard consuming a Node.js/Express API backed by MySQL. Role-based login, charts, exportable reports and responsive layout.',
			'min'         => 1500,
			'max'         => 4000,
			'category'    => 'development',
			'skills'      => 'React, Node.js, MySQL',
			'duration'    => '3-plus-months',
			'featured'    => 1,
		),
		array(
			'client'      => 0,
			'title'       => 'Monthly data entry and CRM cleanup',
			'description' => 'Ongoing monthly task: import leads from CSV, deduplicate contacts and keep the CRM tags in order. Around 6 hours of work per month.',
			'min'         => 200,
			'max'         => 450,
			'category'    => 'admin',
			'skills'      => 'Data Entry, Excel, CRM',
			'duration'    => 'flexible',
			'featured'    => 0,
		),
		array(
			'client'      => 1,
			'title'       => 'Market research report for a fintech MVP',
			'description' => 'Competitive analysis and target-segment sizing for a budgeting app. Deliver a structured report with charts, sources and actionable recommendations.',
			'min'         => 700,
			'max'         => 1600,
			'category'    => 'business',
			'skills'      => 'Market Research, Finance, Business Analysis',
			'duration'    => '1-4-weeks',
			'featured'    => 0,
		),
	);

	/**
	 * BIDS.
	 *
	 * @var mixed
	 */
	private const BIDS = array(
		0 => array(
			array( 0, 950, 21, 'Full plugin build with ticket seating, admin calendar and email confirmations. Includes a week of post-launch support.' ),
			array( 2, 880, 18, 'I have built 30+ booking plugins. Clean, documented code with a composer-free build that survives theme updates.' ),
		),
		1 => array(
			array( 1, 500, 6, 'Brand designer with a focus on hospitality. Includes source files and a mini brand guide.' ),
			array( 3, 460, 7, 'Moodboards first, then three logo concepts with unlimited refinements.' ),
			array( 4, 620, 5, 'Full identity in under a week, ready for print and social.' ),
		),
		2 => array(
			array( 0, 320, 10, 'Ten researched, SEO-optimized posts with headings, meta titles and suggested internal links.' ),
			array( 5, 290, 12, 'Experienced SaaS writer; I can match your existing tone and adapt after the first sample.' ),
		),
		3 => array(
			array( 1, 900, 21, 'Certified Facebook Ads strategist. Setup, A/B testing and weekly reporting included.' ),
			array( 2, 1100, 28, 'Full-funnel campaign management with a focus on cost-per-lead reduction.' ),
		),
		4 => array(
			array( 4, 780, 30, 'Video editor for 40+ tech channels. Motion captions and color grading included.' ),
			array( 5, 850, 35, 'Clean cuts, brand-safe captions and fast turnaround on every batch.' ),
		),
		5 => array(
			array( 3, 2200, 60, 'Senior full-stack dev. React + Node + MySQL with tests and a deploy-ready setup.' ),
			array( 0, 2400, 55, 'Proven dashboard builds with auth, charts and exportable reports.' ),
		),
	);

	/**
	 * Construct.
	 *
	 * @param Zeko_Freelance_DB $db Db.
	 */
	public function __construct( Zeko_Freelance_DB $db ) {
		$this->db = $db;
	}

	/**
	 * Seed.
	 */
	public function seed(): array {
		$this->db->seed_default_skills();
		$this->create_clients();
		$this->create_freelancers();
		$this->create_projects();
		$this->create_bids();
		$this->create_active_contract();
		$this->create_completed_contract();
		$this->create_dispute();
		$this->create_verifications();
		$this->create_portfolios();

		return array(
			'clients'       => count( $this->client_ids ),
			'freelancers'   => count( $this->freelancer_ids ),
			'projects'      => count( $this->project_ids ),
			'bids'          => $this->count_table( $this->db->get_table_bids() ),
			'contracts'     => count( $this->contract_ids ),
			'milestones'    => $this->count_table( $this->db->get_table_milestones() ),
			'disputes'      => $this->count_table( $this->db->get_table_disputes() ),
			'portfolios'    => count( $this->portfolio_ids ),
			'reviews'       => $this->count_table( $this->db->get_table_reviews() ),
			'verifications' => count( $this->verification_ids ),
		);
	}

	/**
	 * Clear.
	 */
	public function clear(): void {
		global $wpdb;

		$demo_ids = $this->find_demo_user_ids();

		// Contract-scoped rows (reviews → disputes → milestones → contracts).
		$contract_ids = array_map( 'absint', $this->contract_ids );
		if ( $demo_ids ) {
			$in = implode( ',', array_fill( 0, count( $demo_ids ), '%d' ) );
			// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
			$sql          = $wpdb->prepare(
				"SELECT id FROM {$this->db->get_table_contracts()} WHERE client_id IN ({$in}) OR freelancer_id IN ({$in})", // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders
				array_merge( $demo_ids, $demo_ids )
			);
			$contract_ids = array_merge( $contract_ids, array_map( 'absint', (array) $wpdb->get_col( $sql ) ) );
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		}
		$contract_ids = array_values( array_unique( array_filter( $contract_ids ) ) );

		if ( $contract_ids ) {
			$in = implode( ',', array_fill( 0, count( $contract_ids ), '%d' ) );
			// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$this->db->get_table_reviews()} WHERE contract_id IN ({$in})", $contract_ids ) ); // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$this->db->get_table_disputes()} WHERE contract_id IN ({$in})", $contract_ids ) ); // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$this->db->get_table_milestones()} WHERE contract_id IN ({$in})", $contract_ids ) ); // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$this->db->get_table_contracts()} WHERE id IN ({$in})", $contract_ids ) ); // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders
		}

		// Project-scoped rows (bids → projects).
		$project_ids = array_map( 'absint', $this->project_ids );
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		if ( $demo_ids ) {
			$in = implode( ',', array_fill( 0, count( $demo_ids ), '%d' ) );
			// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
			$sql         = $wpdb->prepare(
				"SELECT id FROM {$this->db->get_table_projects()} WHERE user_id IN ({$in})", // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders
				$demo_ids
			);
			$project_ids = array_merge( $project_ids, array_map( 'absint', (array) $wpdb->get_col( $sql ) ) );
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		}
		$project_ids = array_values( array_unique( array_filter( $project_ids ) ) );

		if ( $project_ids ) {
			$in = implode( ',', array_fill( 0, count( $project_ids ), '%d' ) );
			// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$this->db->get_table_bids()} WHERE project_id IN ({$in})", $project_ids ) ); // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$this->db->get_table_projects()} WHERE id IN ({$in})", $project_ids ) ); // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders
		}

		// User-scoped rows + demo users.
		if ( $demo_ids ) {
			$in = implode( ',', array_fill( 0, count( $demo_ids ), '%d' ) );
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$this->db->get_table_portfolios()} WHERE user_id IN ({$in})", $demo_ids ) ); // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$this->db->get_table_verifications()} WHERE user_id IN ({$in})", $demo_ids ) ); // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders

			require_once ABSPATH . 'wp-admin/includes/user.php';
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
			$current_user_id = get_current_user_id();
			foreach ( $demo_ids as $user_id ) {
				$user_id = (int) $user_id;
				if ( $user_id === $current_user_id ) {
					continue;
				}
				wp_delete_user( $user_id );
			}
		}

		$this->client_ids       = array();
		$this->freelancer_ids   = array();
		$this->project_ids      = array();
		$this->contract_ids     = array();
		$this->portfolio_ids    = array();
		$this->verification_ids = array();
		$this->awarded_bid_id   = 0;
		$this->completed_bid_id = 0;
	}

	/**
	 * Create clients.
	 */
	private function create_clients(): void {
		$client_count = count( self::CLIENT_NAMES );
		for ( $i = 0; $i < $client_count; $i++ ) {
			$username           = self::DEMO_CLIENT_PREFIX . ( $i + 1 );
			$user_id            = $this->create_demo_user( $username, self::CLIENT_NAMES[ $i ], 'subscriber' );
			$this->client_ids[] = $user_id;
		}
	}

	/**
	 * Create freelancers.
	 */
	private function create_freelancers(): void {
		$freelancer_count = count( self::FREELANCER_NAMES );
		for ( $i = 0; $i < $freelancer_count; $i++ ) {
			$username               = self::DEMO_FREELANCER_PREFIX . ( $i + 1 );
			$user_id                = $this->create_demo_user( $username, self::FREELANCER_NAMES[ $i ], 'subscriber' );
			$this->freelancer_ids[] = $user_id;
			$this->db->save_user_skills( $user_id, self::FREELANCER_SKILLS[ $i ] );
		}
	}

	/**
	 * Create demo user.
	 *
	 * @param string $username Username.
	 * @param string $display_name Display name.
	 * @param string $role Role.
	 */
	private function create_demo_user( string $username, string $display_name, string $role ): int {
		$user_id = username_exists( $username );

		if ( ! $user_id ) {
			$user_id = wp_create_user( $username, 'demo123', $username . '@zeko.test' );
			$user    = new WP_User( $user_id );
			$user->set_role( $role );

			$name_parts = explode( ' ', $display_name, 2 );
			wp_update_user(
				array(
					'ID'           => $user_id,
					'display_name' => $display_name,
					'first_name'   => $name_parts[0],
					'last_name'    => isset( $name_parts[1] ) ? $name_parts[1] : '',
				)
			);
		}

		// Precise cleanup anchor: every demo user is tagged so clear() can.
		// delete by meta (not just by login/email lists). Idempotent re-seeds.
		// re-tag existing demo accounts without touching other users.
		$user_id = (int) $user_id;
		if ( $user_id && ! get_user_meta( $user_id, 'zeko_demo_user', true ) ) {
			update_user_meta( $user_id, 'zeko_demo_user', 1 );
		}

		return $user_id;
	}

	/**
	 * Create projects.
	 */
	private function create_projects(): void {
		foreach ( self::PROJECTS as $i => $p ) {
			$project_id = $this->db->insert_project(
				array(
					'user_id'     => $this->client_ids[ $p['client'] ],
					'title'       => $p['title'],
					'description' => $p['description'],
					'budget_min'  => $p['min'],
					'budget_max'  => $p['max'],
					'currency'    => 'USD',
					'category'    => $p['category'],
					'skills'      => $p['skills'],
					'location'    => '',
					'duration'    => $p['duration'],
					'status'      => 'open',
					'featured'    => $p['featured'],
					'expires_at'  => $this->expires_at( $p['duration'], $i ),
				)
			);

			if ( $project_id ) {
				$this->project_ids[] = $project_id;
			}
		}
	}

	/**
	 * Create bids.
	 */
	private function create_bids(): void {
		foreach ( self::BIDS as $project_index => $bids ) {
			$project_id = $this->project_ids[ $project_index ];
			foreach ( $bids as $bid ) {
				$bid_id = $this->db->insert_bid(
					array(
						'project_id'    => $project_id,
						'user_id'       => $this->freelancer_ids[ $bid[0] ],
						'amount'        => $bid[1],
						'delivery_days' => $bid[2],
						'proposal'      => $bid[3],
						'status'        => 'pending',
					)
				);

				if ( 0 === $project_index && ! $this->awarded_bid_id ) {
					$this->awarded_bid_id = $bid_id;
				}
				if ( 2 === $project_index && ! $this->completed_bid_id ) {
					$this->completed_bid_id = $bid_id;
				}
			}
		}
	}

	/**
	 * Create active contract.
	 */
	private function create_active_contract(): void {
		$contract_id = $this->db->award_bid( $this->awarded_bid_id );
		if ( ! $contract_id ) {
			return;
		}

		$this->contract_ids[] = $contract_id;

		$bid = $this->db->get_bid( $this->awarded_bid_id );
		$sum = (float) $bid->amount;
		$this->db->update_contract(
			$contract_id,
			array(
				'status'        => 'active',
				'escrow_amount' => $sum,
			)
		);

		$this->insert_milestone( $contract_id, 'Plugin skeleton + booking UI', 'Database schema, ticket booking form and the admin calendar overview.', round( $sum * 0.40, 2 ), 'paid', 14 );
		$this->insert_milestone( $contract_id, 'Seat selection + confirmations', 'Seat picker, payment confirmation flow and transactional emails.', round( $sum * 0.35, 2 ), 'in_review', 5, 2 );
		$this->insert_milestone( $contract_id, 'Reporting + launch support', 'Booking reports, final QA and a week of post-launch support.', round( $sum * 0.25, 2 ), 'pending', 9 );
	}

	/**
	 * Create completed contract.
	 */
	private function create_completed_contract(): void {
		$contract_id = $this->db->award_bid( $this->completed_bid_id );
		if ( ! $contract_id ) {
			return;
		}

		$this->contract_ids[] = $contract_id;

		$bid           = $this->db->get_bid( $this->completed_bid_id );
		$project       = $this->db->get_project( (int) $bid->project_id );
		$client_id     = (int) $project->user_id;
		$freelancer_id = (int) $bid->user_id;

		$this->db->update_contract(
			$contract_id,
			array(
				'status'        => 'completed',
				'escrow_amount' => (float) $bid->amount,
			)
		);
		$this->db->update_project( (int) $bid->project_id, array( 'status' => 'completed' ) );

		$this->insert_milestone( $contract_id, 'Ten SEO blog posts', 'Research, writing and on-page optimization for all ten articles.', (float) $bid->amount, 'paid', 12 );

		$this->db->insert_review(
			array(
				'contract_id' => $contract_id,
				'reviewer_id' => $client_id,
				'reviewee_id' => $freelancer_id,
				'rating'      => 5,
				'comment'     => 'Delivered on time and the articles ranked within weeks. Clear communication throughout.',
			)
		);
		$this->db->insert_review(
			array(
				'contract_id' => $contract_id,
				'reviewer_id' => $freelancer_id,
				'reviewee_id' => $client_id,
				'rating'      => 5,
				'comment'     => 'Great client with clear briefs and fast feedback. Would happily work together again.',
			)
		);
	}

	/**
	 * Create dispute.
	 */
	private function create_dispute(): void {
		if ( empty( $this->contract_ids ) ) {
			return;
		}

		$contract = $this->db->get_contract( $this->contract_ids[0] );
		if ( ! $contract ) {
			return;
		}

		$this->db->open_dispute(
			(int) $contract->id,
			(int) $contract->client_id,
			'Scope dispute on milestone 2',
			'The client believes the seat-selection milestone is missing the waitlist behavior described in the brief.'
		);
	}

	/**
	 * Create verifications.
	 */
	private function create_verifications(): void {
		foreach ( $this->freelancer_ids as $index => $user_id ) {
			$data = array(
				'user_id'     => $user_id,
				'type'        => 'id',
				'status'      => 'approved',
				'reviewed_at' => current_time( 'mysql' ),
				'reviewed_by' => 1,
			);
			if ( count( $this->freelancer_ids ) - 1 === $index ) {
				$data['status']      = 'pending';
				$data['reviewed_at'] = null;
				$data['reviewed_by'] = 0;
				$data['type']        = 'business';
			}

			$verification_id = $this->db->insert_verification( $data );
			if ( $verification_id ) {
				$this->verification_ids[] = $verification_id;
			}
		}
	}

	/**
	 * Create portfolios.
	 */
	private function create_portfolios(): void {
		$items = array(
			0 => array(
				array( 'Custom booking plugin for a ticketing startup', 'A WordPress plugin handling seat maps, email confirmations and admin reporting.', 'development', 'PHP, WordPress, MySQL', 'https://github.com/example/booking-plugin' ),
				array( 'Headless storefront powered by a custom API', 'React front-end consuming a Node.js REST API with role-based auth.', 'development', 'React, Node.js, REST APIs', 'https://example.com/portfolio/storefront' ),
			),
			1 => array(
				array( 'Artisan coffee brand identity', 'Logo, palette, typography and social kit for a specialty coffee roaster.', 'design', 'Graphic Design, Branding, Adobe Illustrator', 'https://dribbble.com/example/coffee-brand' ),
				array( 'Restaurant menu + signage system', 'Menu layout, signage and packaging artwork for a three-location restaurant group.', 'design', 'Graphic Design, Adobe Illustrator', 'https://dribbble.com/example/menu-system' ),
			),
			2 => array(
				array( 'SEO overhaul for a legal directory', 'Content strategy and 30 optimized pages that tripled organic traffic.', 'writing', 'SEO, Copywriting', 'https://example.com/portfolio/seo-overhaul' ),
				array( 'B2B SaaS launch blog', 'Twenty launch articles positioning a project-management tool.', 'writing', 'Copywriting, Research', 'https://example.com/portfolio/saas-blog' ),
			),
			3 => array(
				array( 'Analytics dashboard for a logistics firm', 'Live KPIs, charting and exportable reports on top of a Node.js API.', 'development', 'React, Node.js, MySQL', 'https://example.com/portfolio/analytics-dashboard' ),
				array( 'Team health-check app', 'A small React app for weekly team surveys with trend charts.', 'development', 'React, Node.js', 'https://example.com/portfolio/health-check' ),
			),
			4 => array(
				array( 'YouTube tech channel (12 episodes)', 'Edits with motion captions, color grading and branded intro/outro.', 'media', 'Video Editing, Adobe Premiere, Motion Graphics', 'https://vimeo.com/example/tech-channel' ),
				array( 'Product launch video', 'A 60-second promo cut from raw studio footage with kinetic type.', 'media', 'Video Editing, Motion Graphics', 'https://vimeo.com/example/launch-video' ),
			),
			5 => array(
				array( 'CRM cleanup for a real-estate agency', 'Deduplicated 8,000 contacts and standardized pipeline tags.', 'admin', 'Data Entry, Excel, CRM', 'https://example.com/portfolio/crm-cleanup' ),
				array( 'Lead import automation', 'CSV-to-CRM pipeline cutting monthly data-entry time by 70%.', 'admin', 'Excel, CRM', 'https://example.com/portfolio/lead-automation' ),
			),
		);

		foreach ( $items as $freelancer_index => $portfolios ) {
			foreach ( $portfolios as $item ) {
				$portfolio_id = $this->db->insert_portfolio(
					array(
						'user_id'     => $this->freelancer_ids[ $freelancer_index ],
						'title'       => $item[0],
						'description' => $item[1],
						'category'    => $item[2],
						'skills'      => $item[3],
						'link'        => $item[4],
						'image_url'   => '',
					)
				);

				if ( $portfolio_id ) {
					$this->portfolio_ids[] = $portfolio_id;
				}
			}
		}
	}

	/**
	 * Insert milestone.
	 *
	 * @param int    $contract_id Contract id.
	 * @param string $title Title.
	 * @param string $description Description.
	 * @param float  $amount Amount.
	 * @param string $status Status.
	 * @param int    $due_in_days Due in days.
	 * @param int    $review_due_in_days Review due in days.
	 */
	private function insert_milestone( int $contract_id, string $title, string $description, float $amount, string $status, int $due_in_days, int $review_due_in_days = 0 ): int {
		$id = (int) $this->db->insert_milestone(
			array(
				'contract_id' => $contract_id,
				'title'       => $title,
				'description' => $description,
				'amount'      => $amount,
				'status'      => $status,
				'due_at'      => gmdate( 'Y-m-d', strtotime( '+' . $due_in_days . ' days' ) ),
			)
		);
		if ( $id > 0 && $review_due_in_days > 0 ) {
			$this->db->update_milestone(
				$id,
				array(
					'review_due_at' => gmdate( 'Y-m-d H:i:s', time() + $review_due_in_days * DAY_IN_SECONDS ),
				)
			);
		}
		return $id;
	}

	/**
	 * Expires at.
	 *
	 * @param string $duration Duration.
	 * @param int    $offset_days Offset days.
	 */
	private function expires_at( string $duration, int $offset_days ): string {
		$days = Zeko_Freelance_Ajax::durations()[ $duration ]['days'] ?? 30;
		return gmdate( 'Y-m-d H:i:s', time() + ( $days + $offset_days ) * DAY_IN_SECONDS );
	}

	/**
	 * Find demo user ids.
	 */
	private function find_demo_user_ids(): array {
		$ids = array();
		for ( $i = 1; $i <= 20; $i++ ) {
			foreach ( array( self::DEMO_CLIENT_PREFIX, self::DEMO_FREELANCER_PREFIX ) as $prefix ) {
				$user = get_user_by( 'login', $prefix . $i );
				if ( $user ) {
					$ids[] = (int) $user->ID;
				}
			}
		}

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		// Authoritative cleanup source: every demo user carries.
		// `zeko_demo_user = 1`, so clear() also picks up any demo account the.
		// generator created (even demo user logins that no longer follow the.
		// historical prefixes). Non-demo users are never matched.
		$tagged = get_users(
			array(
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key,WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'meta_key'   => 'zeko_demo_user',
				'meta_value' => '1',
				'fields'     => 'ID',
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		foreach ( $tagged as $user_id ) {
			$ids[] = (int) $user_id;
		}

		return array_values( array_unique( array_filter( $ids ) ) );
	}

	/**
	 * Count table.
	 *
	 * @param string $table Table.
	 */
	private function count_table( string $table ): int {
		global $wpdb;
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}
}
