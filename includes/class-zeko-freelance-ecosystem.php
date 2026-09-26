<?php
/**
 * Zeko Freelance ecosystem integration.
 *
 * Wires the freelance module into the shared Zeko experience:
 *   - primary-nav "Freelance" menu (versioned reconciliation, self-healing URLs)
 *   - admin bar "Freelance" node
 *   - unified dashboard tab
 *
 * @package Zeko_Freelance
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Class Zeko_Freelance_Ecosystem. */
class Zeko_Freelance_Ecosystem {

	/**
	 * MENU VERSION.
	 *
	 * @var mixed
	 */
	private const MENU_VERSION = '0.1.3';

	private const PAGE_SLUGS = array( 'freelance', 'freelance-projects', 'freelance-portfolios', 'freelance-post-project', 'freelance-project' );

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

		add_action( 'init', array( $this, 'register_nav_menus' ) );
		add_action( 'admin_init', array( $this, 'maybe_create_nav_menu_items' ) );
		add_action( 'wp_before_admin_bar_render', array( $this, 'add_admin_bar_freelance_node' ) );

		// Dashboard tab.
		add_filter( 'zeko_dashboard_tabs', array( $this, 'add_dashboard_tab' ), 10, 1 );
		add_action( 'zeko_dashboard_tab_content_freelance', array( $this, 'render_dashboard_tab' ) );

		// Keep stored "Freelance" menu URLs in sync with the current scheme.
		add_filter( 'wp_nav_menu_objects', array( $this, 'fix_freelance_menu_urls' ) );

		// Nav items registry.
		add_filter( 'zeko_nav_items', array( $this, 'register_nav_items' ) );
	}

	/**
	 * Rewrite stored "Freelance" menu item URLs at render time so they always
	 * match the current site scheme (self-heals http URLs stored before SSL).
	 *
	 * @return array
	 * @param array $items Menu item objects.
	 */
	public function fix_freelance_menu_urls( $items ): array {
		foreach ( $items as $item ) {
			if ( ! is_object( $item ) || empty( $item->url ) ) {
				continue;
			}
			$path = trim( (string) wp_parse_url( (string) $item->url, PHP_URL_PATH ), '/' );
			if ( in_array( $path, self::PAGE_SLUGS, true ) ) {
				$item->url = zeko_freelance_page_url( $path );
			}
		}
		return $items;
	}

	/**
	 * Register Freelance nav items via the core registry.
	 *
	 * @return array
	 * @param array $locations keyed by location slug.
	 */
	public function register_nav_items( array $locations ): array {
		$locations['primary'][] = array(
			'title'    => __( 'Freelance', 'zeko-freelance' ),
			'url'      => zeko_freelance_page_url( '' ),
			'order'    => 7,
			'children' => array(
				array(
					'title' => __( 'Marketplace', 'zeko-freelance' ),
					'url'   => zeko_freelance_page_url( 'freelance-projects' ),
				),
				array(
					'title' => __( 'Portfolios', 'zeko-freelance' ),
					'url'   => zeko_freelance_page_url( 'freelance-portfolios' ),
				),
				array(
					'title' => __( 'Post a Project', 'zeko-freelance' ),
					'url'   => zeko_freelance_page_url( 'freelance-post-project' ),
				),
			),
		);
		return $locations;
	}

	/**
	 * Nav menus.
	 */
	public function register_nav_menus(): void {
		register_nav_menus(
			array(
				'zeko-freelance' => __( 'Zeko Freelance', 'zeko-freelance' ),
			)
		);
	}

	/**
	 * Append a "Freelance" item with child pages to the primary nav.
	 * Follows the same pattern as Dating (zeko-love), Shop and Mentorship.
	 */
	public function maybe_create_nav_menu_items(): void {
		if ( ! current_user_can( 'edit_theme_options' ) ) {
			return;
		}

		$done = get_option( 'zeko_freelance_menu_version', '' );
		if ( self::MENU_VERSION === $done ) {
			return;
		}

		$locations = get_nav_menu_locations();
		if ( empty( $locations['primary'] ) ) {
			return;
		}

		$menu_id = $locations['primary'];
		$items   = wp_get_nav_menu_items( $menu_id );
		if ( ! $items ) {
			return;
		}

		$parent_id = 0;
		$max_order = 0;

		foreach ( $items as $item ) {
			$order = (int) $item->menu_order;
			if ( $order > $max_order ) {
				$max_order = $order;
			}
			if ( 'Freelance' === $item->title && 0 === (int) $item->menu_item_parent ) {
				$parent_id = (int) $item->ID;
			}
		}

		$children = array(
			'Marketplace'    => 'freelance-projects',
			'Portfolios'     => 'freelance-portfolios',
			'Post a Project' => 'freelance-post-project',
		);

		if ( ! $parent_id ) {
			$parent_id = wp_update_nav_menu_item(
				$menu_id,
				0,
				array(
					'menu-item-title'  => __( 'Freelance', 'zeko-freelance' ),
					'menu-item-url'    => zeko_freelance_page_url( 'freelance' ),
					'menu-item-status' => 'publish',
					'menu-item-type'   => 'custom',
					'menu-item-order'  => $max_order + 1,
				)
			);

			foreach ( $children as $title => $slug ) {
				wp_update_nav_menu_item(
					$menu_id,
					0,
					array(
						'menu-item-title'     => $title,
						'menu-item-url'       => zeko_freelance_page_url( $slug ),
						'menu-item-status'    => 'publish',
						'menu-item-type'      => 'custom',
						'menu-item-parent-id' => $parent_id,
					)
				);
			}
		} else {
			$existing_by_id = array();
			foreach ( $items as $item ) {
				if ( (int) $item->menu_item_parent === $parent_id ) {
					$existing_by_id[ (int) $item->ID ] = $item;
				}
			}

			// Remove any child that duplicates the parent title (legacy.
			// installs may have a "Freelance" child under "Freelance").
			foreach ( $existing_by_id as $item_id => $item ) {
				if ( 'Freelance' === $item->title ) {
					wp_delete_post( $item_id, true );
					unset( $existing_by_id[ $item_id ] );
				}
			}

			$existing_titles = array();
			foreach ( $existing_by_id as $item ) {
				$existing_titles[ $item->title ] = true;
			}

			foreach ( $children as $title => $slug ) {
				$url = zeko_freelance_page_url( $slug );
				if ( false === strpos( (string) $url, '://' ) ) {
					// Scheme-less URLs (e.g. CLI without HTTP_HOST) would.
					// clobber stored links — leave them untouched.
					continue;
				}
				if ( isset( $existing_titles[ $title ] ) ) {
					// Refresh stored URL so scheme/host drift self-heals.
					foreach ( $existing_by_id as $item_id => $item ) {
						if ( $item->title === $title && $item->url !== $url ) {
							wp_update_nav_menu_item(
								$menu_id,
								$item_id,
								array(
									'menu-item-title'     => $title,
									'menu-item-url'       => $url,
									'menu-item-status'    => 'publish',
									'menu-item-type'      => 'custom',
									'menu-item-parent-id' => $parent_id,
								)
							);
						}
					}
					continue;
				}
				wp_update_nav_menu_item(
					$menu_id,
					0,
					array(
						'menu-item-title'     => $title,
						'menu-item-url'       => $url,
						'menu-item-status'    => 'publish',
						'menu-item-type'      => 'custom',
						'menu-item-parent-id' => $parent_id,
					)
				);
			}
		}

		update_option( 'zeko_freelance_menu_version', self::MENU_VERSION );
	}

	/**
	 * Add admin bar freelance node.
	 */
	public function add_admin_bar_freelance_node(): void {
		global $wp_admin_bar;

		if ( ! is_user_logged_in() ) {
			return;
		}

		$wp_admin_bar->add_node(
			array(
				'id'    => 'zeko-freelance',
				'title' => '<span class="ab-icon dashicons dashicons-briefcase"></span><span class="ab-label">' . esc_html__( 'Freelance', 'zeko-freelance' ) . '</span>',
				'href'  => zeko_freelance_page_url( 'freelance' ),
			)
		);

		$wp_admin_bar->add_node(
			array(
				'id'     => 'zeko-freelance-dashboard',
				'parent' => 'zeko-freelance',
				'title'  => __( 'Dashboard', 'zeko-freelance' ),
				'href'   => zeko_freelance_page_url( 'freelance' ),
			)
		);

		$wp_admin_bar->add_node(
			array(
				'id'     => 'zeko-freelance-marketplace',
				'parent' => 'zeko-freelance',
				'title'  => __( 'Marketplace', 'zeko-freelance' ),
				'href'   => zeko_freelance_page_url( 'freelance-projects' ),
			)
		);

		$wp_admin_bar->add_node(
			array(
				'id'     => 'zeko-freelance-portfolios',
				'parent' => 'zeko-freelance',
				'title'  => __( 'Portfolios', 'zeko-freelance' ),
				'href'   => zeko_freelance_page_url( 'freelance-portfolios' ),
			)
		);

		$wp_admin_bar->add_node(
			array(
				'id'     => 'zeko-freelance-post-project',
				'parent' => 'zeko-freelance',
				'title'  => __( 'Post a Project', 'zeko-freelance' ),
				'href'   => zeko_freelance_page_url( 'freelance-post-project' ),
			)
		);
	}

	/**
	 * Add a "Freelance" tab to the Zeko dashboard.
	 *
	 * @return array
	 * @param array $tabs Existing tabs.
	 */
	public function add_dashboard_tab( array $tabs ): array {
		$tabs['freelance'] = __( 'Freelance', 'zeko-freelance' );
		return $tabs;
	}

	/**
	 * Render the "Freelance" dashboard tab content.
	 */
	public function render_dashboard_tab(): void {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return;
		}

		$open_projects = $this->db->count_projects_by_status( 'open' );
		$my_projects   = count(
			$this->db->get_projects(
				array(
					'user_id'  => $user_id,
					'per_page' => 500,
				)
			)
		);
		$my_bids       = count( $this->db->get_user_bids( $user_id ) );
		$rating        = $this->db->average_rating( $user_id );

		echo '<div class="zeko-freelance-tab" style="display:flex;gap:16px;flex-wrap:wrap;margin-bottom:16px;">';
		foreach ( array(
			array( (string) number_format_i18n( $open_projects ), __( 'Open projects', 'zeko-freelance' ) ),
			array( (string) number_format_i18n( $my_projects ), __( 'My projects', 'zeko-freelance' ) ),
			array( (string) number_format_i18n( $my_bids ), __( 'My bids', 'zeko-freelance' ) ),
			array( $rating > 0 ? number_format_i18n( $rating, 1 ) . ' ★' : '-', __( 'Rating', 'zeko-freelance' ) ),
		) as $stat ) {
			echo '<div style="background:var(--color-surface,#fff);border:1px solid var(--color-border,#e2e8f0);border-radius:12px;padding:12px 18px;text-align:center;flex:1;min-width:120px;">';
			echo '<div style="font-size:22px;font-weight:700;color:#4f46e5;">' . esc_html( $stat[0] ) . '</div>';
			echo '<div style="font-size:12px;color:var(--color-text-secondary,#64748b);">' . esc_html( $stat[1] ) . '</div>';
			echo '</div>';
		}
		echo '</div>';

		echo '<p style="margin:0;"><a class="btn" href="' . esc_url( zeko_freelance_page_url( 'freelance' ) ) . '">' . esc_html__( 'Open Freelance dashboard', 'zeko-freelance' ) . '</a> '
			. '<a class="btn btn-secondary" href="' . esc_url( zeko_freelance_page_url( 'freelance-projects' ) ) . '">' . esc_html__( 'Marketplace', 'zeko-freelance' ) . '</a> '
			. '<a class="btn btn-secondary" href="' . esc_url( zeko_freelance_page_url( 'freelance-post-project' ) ) . '">' . esc_html__( 'Post a Project', 'zeko-freelance' ) . '</a> '
			. '<a class="btn btn-secondary" href="' . esc_url( zeko_freelance_page_url( 'freelance-portfolios' ) ) . '">' . esc_html__( 'Portfolios', 'zeko-freelance' ) . '</a></p>';
	}

	/**
	 * Freelance shortcode pages as menu-ready objects.
	 *
	 * @return object[]
	 */
	public function get_freelance_menu_items(): array {
		$items = array();

		foreach ( self::PAGE_SLUGS as $slug ) {
			$page = class_exists( 'Zeko_Core_Helpers' )
				? Zeko_Core_Helpers::get_instance()->get_page_by_slug( $slug )
				: get_page_by_path( $slug );
			if ( ! $page ) {
				continue;
			}

			$items[] = (object) array(
				'title' => get_the_title( $page ),
				'url'   => get_permalink( $page ),
				'slug'  => $slug,
			);
		}

		return $items;
	}
}
