<?php
/**
 * Zeko Freelance — WordPress personal-data exporter and eraser.
 *
 * Registers with Tools > Export Personal Data / Erase Personal Data so site
 * owners can fulfil data-protection requests for freelancer projects, bids,
 * contracts, milestones, disputes, portfolio items, reviews and identity
 * verifications. Two-sided records (contracts, milestones, reviews) are shared
 * between a client and a freelancer, so erasure deletes them outright when the
 * requesting user is a party; publicly-listed project posts are kept but their
 * author column is scrubbed to 0 so the marketplace feed keeps its structure.
 *
 * Table schemas byte-verified 2026-09-25 against class-zeko-freelance-db.php:
 *   {prefix}zeko_freelance_projects       user_id (scrubbed) / created_at
 *   {prefix}zeko_freelance_bids           user_id / project_id / proposal
 *   {prefix}zeko_freelance_contracts      client_id / freelancer_id / created_at
 *   {prefix}zeko_freelance_milestones     contract_id / title / amount / status
 *   {prefix}zeko_freelance_disputes       user_id / subject / description
 *   {prefix}zeko_freelance_portfolios     user_id / title / skills / link
 *   {prefix}zeko_freelance_reviews        reviewer_id / reviewee_id / rating
 *   {prefix}zeko_freelance_verifications  user_id / type / status / reviewed_by
 *   {prefix}zeko_freelance_skills         global taxonomy, no user linkage
 *
 * @package Zeko_Freelance
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the exporter, eraser and retention-table callbacks.
 */
function zeko_freelance_privacy_register(): void {
	add_filter( 'wp_privacy_personal_data_exporters', 'zeko_freelance_privacy_register_exporter' );
	add_filter( 'wp_privacy_personal_data_erasers', 'zeko_freelance_privacy_register_eraser' );
}
add_action( 'init', 'zeko_freelance_privacy_register', 11 );

/**
 * Register the personal-data exporter.
 *
 * @param array $exporters Exporters.
 */
function zeko_freelance_privacy_register_exporter( array $exporters ): array {
	$exporters['zeko-freelance'] = array(
		'exporter_friendly_name' => __( 'Zeko Freelance data', 'zeko-freelance' ),
		'callback'               => 'zeko_freelance_privacy_export',
	);
	return $exporters;
}

/**
 * Register the personal-data eraser.
 *
 * @param array $erasers Erasers.
 */
function zeko_freelance_privacy_register_eraser( array $erasers ): array {
	$erasers['zeko-freelance'] = array(
		'eraser_friendly_name' => __( 'Zeko Freelance data', 'zeko-freelance' ),
		'callback'             => 'zeko_freelance_privacy_erase',
	);
	return $erasers;
}

/**
 * Get a prepared DB instance (null when the plugin is not active).
 */
function zeko_freelance_privacy_db(): ?Zeko_Freelance_DB {
	if ( ! class_exists( 'Zeko_Freelance_DB' ) ) {
		return null;
	}
	return new Zeko_Freelance_DB();
}

/**
 * Whether a table exists (guards every touch of a table).
 *
 * @param string $table Table.
 */
function zeko_freelance_privacy_table_exists( string $table ): bool {
	global $wpdb;
	return $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table;
}

/**
 * Export a user's Zeko Freelance data, 20 rows per table per page.
 *
 * @return array{data: array, done: bool}
 * @param string $email_address User who requested the export.
 * @param int    $page Export page (batching).
 */
function zeko_freelance_privacy_export( string $email_address, int $page = 1 ): array {
	$user = get_user_by( 'email', $email_address );
	if ( ! $user ) {
		return array(
			'data' => array(),
			'done' => true,
		);
	}

	$db = zeko_freelance_privacy_db();
	if ( ! $db ) {
		return array(
			'data' => array(),
			'done' => true,
		);
	}

	global $wpdb;

	$user_id   = (int) $user->ID;
	$per_page  = 20;
	$offset    = ( max( 1, (int) $page ) - 1 ) * $per_page;
	$data      = array();
	$tables    = 0;
	$exhausted = 0;

	if ( zeko_freelance_privacy_table_exists( $db->get_table_projects() ) ) {
		++$tables;
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, title, description, budget_min, budget_max, currency, category, status, created_at FROM {$db->get_table_projects()} WHERE user_id = %d ORDER BY id ASC LIMIT %d OFFSET %d",
				$user_id,
				$per_page,
				$offset
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		foreach ( (array) $rows as $row ) {
			$data[] = array(
				'group_id'    => 'zeko-freelance-projects',
				'group_label' => __( 'Zeko Freelance — Projects', 'zeko-freelance' ),
				'item_id'     => 'zeko-freelance-project-' . (int) $row->id,
				'data'        => array(
					array(
						'name'  => __( 'Project ID', 'zeko-freelance' ),
						'value' => (string) $row->id,
					),
					array(
						'name'  => __( 'Title', 'zeko-freelance' ),
						'value' => (string) $row->title,
					),
					array(
						'name'  => __( 'Description', 'zeko-freelance' ),
						'value' => (string) $row->description,
					),
					array(
						'name'  => __( 'Budget', 'zeko-freelance' ),
						'value' => (string) $row->budget_min . ' — ' . $row->budget_max . ' ' . $row->currency,
					),
					array(
						'name'  => __( 'Category', 'zeko-freelance' ),
						'value' => (string) $row->category,
					),
					array(
						'name'  => __( 'Status', 'zeko-freelance' ),
						'value' => (string) $row->status,
					),
					array(
						'name'  => __( 'Created at', 'zeko-freelance' ),
						'value' => (string) $row->created_at,
					),
				),
			);
		}
		if ( count( $rows ) < $per_page ) {
			++$exhausted;
		}
	}

	if ( zeko_freelance_privacy_table_exists( $db->get_table_bids() ) ) {
		++$tables;
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, project_id, amount, delivery_days, proposal, status, created_at FROM {$db->get_table_bids()} WHERE user_id = %d ORDER BY id ASC LIMIT %d OFFSET %d",
				$user_id,
				$per_page,
				$offset
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		foreach ( (array) $rows as $row ) {
			$data[] = array(
				'group_id'    => 'zeko-freelance-bids',
				'group_label' => __( 'Zeko Freelance — Bids', 'zeko-freelance' ),
				'item_id'     => 'zeko-freelance-bid-' . (int) $row->id,
				'data'        => array(
					array(
						'name'  => __( 'Project ID', 'zeko-freelance' ),
						'value' => (string) $row->project_id,
					),
					array(
						'name'  => __( 'Amount', 'zeko-freelance' ),
						'value' => (string) $row->amount,
					),
					array(
						'name'  => __( 'Delivery days', 'zeko-freelance' ),
						'value' => (string) $row->delivery_days,
					),
					array(
						'name'  => __( 'Proposal', 'zeko-freelance' ),
						'value' => (string) $row->proposal,
					),
					array(
						'name'  => __( 'Status', 'zeko-freelance' ),
						'value' => (string) $row->status,
					),
					array(
						'name'  => __( 'Created at', 'zeko-freelance' ),
						'value' => (string) $row->created_at,
					),
				),
			);
		}
		if ( count( $rows ) < $per_page ) {
			++$exhausted;
		}
	}

	if ( zeko_freelance_privacy_table_exists( $db->get_table_contracts() ) ) {
		++$tables;
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, project_id, budget, currency, escrow_amount, status, created_at FROM {$db->get_table_contracts()} WHERE client_id = %d OR freelancer_id = %d ORDER BY id ASC LIMIT %d OFFSET %d",
				$user_id,
				$user_id,
				$per_page,
				$offset
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		foreach ( (array) $rows as $row ) {
			$data[] = array(
				'group_id'    => 'zeko-freelance-contracts',
				'group_label' => __( 'Zeko Freelance — Contracts', 'zeko-freelance' ),
				'item_id'     => 'zeko-freelance-contract-' . (int) $row->id,
				'data'        => array(
					array(
						'name'  => __( 'Project ID', 'zeko-freelance' ),
						'value' => (string) $row->project_id,
					),
					array(
						'name'  => __( 'Budget', 'zeko-freelance' ),
						'value' => (string) $row->budget,
					),
					array(
						'name'  => __( 'Currency', 'zeko-freelance' ),
						'value' => (string) $row->currency,
					),
					array(
						'name'  => __( 'Escrow amount', 'zeko-freelance' ),
						'value' => (string) $row->escrow_amount,
					),
					array(
						'name'  => __( 'Status', 'zeko-freelance' ),
						'value' => (string) $row->status,
					),
					array(
						'name'  => __( 'Created at', 'zeko-freelance' ),
						'value' => (string) $row->created_at,
					),
				),
			);
		}
		if ( count( $rows ) < $per_page ) {
			++$exhausted;
		}
	}

	if ( zeko_freelance_privacy_table_exists( $db->get_table_milestones() ) && zeko_freelance_privacy_table_exists( $db->get_table_contracts() ) ) {
		++$tables;
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT m.id, m.contract_id, m.title, m.amount, m.status, m.due_at FROM {$db->get_table_milestones()} m INNER JOIN {$db->get_table_contracts()} c ON c.id = m.contract_id WHERE c.client_id = %d OR c.freelancer_id = %d ORDER BY m.id ASC LIMIT %d OFFSET %d",
				$user_id,
				$user_id,
				$per_page,
				$offset
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		foreach ( (array) $rows as $row ) {
			$data[] = array(
				'group_id'    => 'zeko-freelance-milestones',
				'group_label' => __( 'Zeko Freelance — Milestones', 'zeko-freelance' ),
				'item_id'     => 'zeko-freelance-milestone-' . (int) $row->id,
				'data'        => array(
					array(
						'name'  => __( 'Contract ID', 'zeko-freelance' ),
						'value' => (string) $row->contract_id,
					),
					array(
						'name'  => __( 'Title', 'zeko-freelance' ),
						'value' => (string) $row->title,
					),
					array(
						'name'  => __( 'Amount', 'zeko-freelance' ),
						'value' => (string) $row->amount,
					),
					array(
						'name'  => __( 'Status', 'zeko-freelance' ),
						'value' => (string) $row->status,
					),
					array(
						'name'  => __( 'Due at', 'zeko-freelance' ),
						'value' => (string) $row->due_at,
					),
				),
			);
		}
		if ( count( $rows ) < $per_page ) {
			++$exhausted;
		}
	}

	if ( zeko_freelance_privacy_table_exists( $db->get_table_disputes() ) ) {
		++$tables;
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, contract_id, subject, description, status, resolution, created_at FROM {$db->get_table_disputes()} WHERE user_id = %d ORDER BY id ASC LIMIT %d OFFSET %d",
				$user_id,
				$per_page,
				$offset
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		foreach ( (array) $rows as $row ) {
			$data[] = array(
				'group_id'    => 'zeko-freelance-disputes',
				'group_label' => __( 'Zeko Freelance — Disputes', 'zeko-freelance' ),
				'item_id'     => 'zeko-freelance-dispute-' . (int) $row->id,
				'data'        => array(
					array(
						'name'  => __( 'Contract ID', 'zeko-freelance' ),
						'value' => (string) $row->contract_id,
					),
					array(
						'name'  => __( 'Subject', 'zeko-freelance' ),
						'value' => (string) $row->subject,
					),
					array(
						'name'  => __( 'Description', 'zeko-freelance' ),
						'value' => (string) $row->description,
					),
					array(
						'name'  => __( 'Status', 'zeko-freelance' ),
						'value' => (string) $row->status,
					),
					array(
						'name'  => __( 'Resolution', 'zeko-freelance' ),
						'value' => (string) $row->resolution,
					),
					array(
						'name'  => __( 'Created at', 'zeko-freelance' ),
						'value' => (string) $row->created_at,
					),
				),
			);
		}
		if ( count( $rows ) < $per_page ) {
			++$exhausted;
		}
	}

	if ( zeko_freelance_privacy_table_exists( $db->get_table_portfolios() ) ) {
		++$tables;
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, title, description, category, skills, link, created_at FROM {$db->get_table_portfolios()} WHERE user_id = %d ORDER BY id ASC LIMIT %d OFFSET %d",
				$user_id,
				$per_page,
				$offset
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		foreach ( (array) $rows as $row ) {
			$data[] = array(
				'group_id'    => 'zeko-freelance-portfolios',
				'group_label' => __( 'Zeko Freelance — Portfolio items', 'zeko-freelance' ),
				'item_id'     => 'zeko-freelance-portfolio-' . (int) $row->id,
				'data'        => array(
					array(
						'name'  => __( 'Title', 'zeko-freelance' ),
						'value' => (string) $row->title,
					),
					array(
						'name'  => __( 'Description', 'zeko-freelance' ),
						'value' => (string) $row->description,
					),
					array(
						'name'  => __( 'Category', 'zeko-freelance' ),
						'value' => (string) $row->category,
					),
					array(
						'name'  => __( 'Skills', 'zeko-freelance' ),
						'value' => (string) $row->skills,
					),
					array(
						'name'  => __( 'Link', 'zeko-freelance' ),
						'value' => (string) $row->link,
					),
					array(
						'name'  => __( 'Created at', 'zeko-freelance' ),
						'value' => (string) $row->created_at,
					),
				),
			);
		}
		if ( count( $rows ) < $per_page ) {
			++$exhausted;
		}
	}

	if ( zeko_freelance_privacy_table_exists( $db->get_table_reviews() ) ) {
		++$tables;
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, contract_id, rating, comment, created_at FROM {$db->get_table_reviews()} WHERE reviewer_id = %d OR reviewee_id = %d ORDER BY id ASC LIMIT %d OFFSET %d",
				$user_id,
				$user_id,
				$per_page,
				$offset
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		foreach ( (array) $rows as $row ) {
			$data[] = array(
				'group_id'    => 'zeko-freelance-reviews',
				'group_label' => __( 'Zeko Freelance — Reviews', 'zeko-freelance' ),
				'item_id'     => 'zeko-freelance-review-' . (int) $row->id,
				'data'        => array(
					array(
						'name'  => __( 'Contract ID', 'zeko-freelance' ),
						'value' => (string) $row->contract_id,
					),
					array(
						'name'  => __( 'Rating', 'zeko-freelance' ),
						'value' => (string) $row->rating,
					),
					array(
						'name'  => __( 'Comment', 'zeko-freelance' ),
						'value' => (string) $row->comment,
					),
					array(
						'name'  => __( 'Created at', 'zeko-freelance' ),
						'value' => (string) $row->created_at,
					),
				),
			);
		}
		if ( count( $rows ) < $per_page ) {
			++$exhausted;
		}
	}

	if ( zeko_freelance_privacy_table_exists( $db->get_table_verifications() ) ) {
		++$tables;
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, type, status, notes, submitted_at, reviewed_at FROM {$db->get_table_verifications()} WHERE user_id = %d ORDER BY id ASC LIMIT %d OFFSET %d",
				$user_id,
				$per_page,
				$offset
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		foreach ( (array) $rows as $row ) {
			$data[] = array(
				'group_id'    => 'zeko-freelance-verifications',
				'group_label' => __( 'Zeko Freelance — Identity verifications', 'zeko-freelance' ),
				'item_id'     => 'zeko-freelance-verification-' . (int) $row->id,
				'data'        => array(
					array(
						'name'  => __( 'Type', 'zeko-freelance' ),
						'value' => (string) $row->type,
					),
					array(
						'name'  => __( 'Status', 'zeko-freelance' ),
						'value' => (string) $row->status,
					),
					array(
						'name'  => __( 'Notes', 'zeko-freelance' ),
						'value' => (string) $row->notes,
					),
					array(
						'name'  => __( 'Submitted at', 'zeko-freelance' ),
						'value' => (string) $row->submitted_at,
					),
					array(
						'name'  => __( 'Reviewed at', 'zeko-freelance' ),
						'value' => (string) $row->reviewed_at,
					),
				),
			);
		}
		if ( count( $rows ) < $per_page ) {
			++$exhausted;
		}
	}

	return array(
		'data' => $data,
		'done' => $exhausted === $tables,
	);
}

/**
 * Erase a user's Zeko Freelance data.
 * Per-user rows (bids, portfolios, disputes, verifications) and two-sided
 * records (contracts, their milestones, reviews) are deleted; the requesting
 * user's public project listings survive with user_id scrubbed, and the
 * verifier attribution is scrubbed. Called repeatedly until done is true.
 *
 * @return array{items_removed: int, items_retained: int, messages: array, done: bool}
 * @param string $email_address User who requested erasure.
 * @param int    $_page page.
 */
function zeko_freelance_privacy_erase( string $email_address, int $_page = 1 ): array {
	$user = get_user_by( 'email', $email_address );
	if ( ! $user ) {
		return array(
			'items_removed'  => 0,
			'items_retained' => 0,
			'messages'       => array(),
			'done'           => true,
		);
	}

	$db = zeko_freelance_privacy_db();
	if ( ! $db ) {
		return array(
			'items_removed'  => 0,
			'items_retained' => 0,
			'messages'       => array(),
			'done'           => true,
		);
	}

	global $wpdb;

	$user_id = (int) $user->ID;
	$removed = 0;
	$paged   = 20;

	// Projects are public marketplace listings — scrub authorship but keep rows.
	// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	if ( zeko_freelance_privacy_table_exists( $db->get_table_projects() ) ) {
		$removed += (int) $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$db->get_table_projects()} SET user_id = 0 WHERE user_id = %d",
				$user_id
			)
		);
	}
	if ( zeko_freelance_privacy_table_exists( $db->get_table_verifications() ) ) {
		$removed += (int) $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$db->get_table_verifications()} SET reviewed_by = 0 WHERE reviewed_by = %d",
				$user_id
			)
		);
	}
	// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery

	// Milestones belong to contracts — resolve the user's contract ids first.
	$contract_ids = array();
	if ( zeko_freelance_privacy_table_exists( $db->get_table_contracts() ) ) {
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		$contract_ids = array_map(
			'intval',
			(array) $wpdb->get_col(
				$wpdb->prepare(
					"SELECT id FROM {$db->get_table_contracts()} WHERE client_id = %d OR freelancer_id = %d ORDER BY id ASC LIMIT %d",
					$user_id,
					$user_id,
					$paged
				)
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	if ( $contract_ids ) {
		$placeholders = implode( ', ', array_fill( 0, count( $contract_ids ), '%d' ) );

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		if ( zeko_freelance_privacy_table_exists( $db->get_table_milestones() ) ) {
			$removed += (int) $wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$db->get_table_milestones()} WHERE contract_id IN ({$placeholders})", // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders
					$contract_ids
				)
			);
		}
		$removed += (int) $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$db->get_table_contracts()} WHERE id IN ({$placeholders})", // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders
				$contract_ids
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	foreach ( array(
		$db->get_table_bids(),
		$db->get_table_disputes(),
		$db->get_table_portfolios(),
		$db->get_table_verifications(),
	) as $table ) {
		if ( ! zeko_freelance_privacy_table_exists( $table ) ) {
			continue;
		}
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		$removed += (int) $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$table} WHERE user_id = %d LIMIT %d",
				$user_id,
				$paged
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	// Reviews are two-sided social record tied to a contract.
	if ( zeko_freelance_privacy_table_exists( $db->get_table_reviews() ) ) {
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		$removed += (int) $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$db->get_table_reviews()} WHERE reviewer_id = %d OR reviewee_id = %d LIMIT %d",
				$user_id,
				$user_id,
				$paged
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	// Remove freelance-specific user meta.
	// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	$removed += (int) $wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->usermeta} WHERE user_id = %d AND meta_key LIKE %s",
			$user_id,
			'zeko_freelance_%'
		)
	);
	// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery

	$remaining = 0;
	if ( zeko_freelance_privacy_table_exists( $db->get_table_contracts() ) ) {
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		$remaining += (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$db->get_table_contracts()} WHERE client_id = %d OR freelancer_id = %d",
				$user_id,
				$user_id
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}
	foreach ( array(
		$db->get_table_bids(),
		$db->get_table_disputes(),
		$db->get_table_portfolios(),
		$db->get_table_verifications(),
	) as $table ) {
		if ( ! zeko_freelance_privacy_table_exists( $table ) ) {
			continue;
		}
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		$remaining += (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE user_id = %d",
				$user_id
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}
	if ( zeko_freelance_privacy_table_exists( $db->get_table_reviews() ) ) {
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
		$remaining += (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$db->get_table_reviews()} WHERE reviewer_id = %d OR reviewee_id = %d",
				$user_id,
				$user_id
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	}

	$messages = array();
	if ( $removed > 0 ) {
		$messages[] = __( 'Your Zeko Freelance bids, contracts, milestones, reviews, disputes and verifications were removed. Public project listings you posted were kept but detached from your account.', 'zeko-freelance' );
	}

	return array(
		'items_removed'  => $removed,
		'items_retained' => 0,
		'messages'       => $messages,
		'done'           => 0 === $remaining,
	);
}
