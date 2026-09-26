<?php
/**
 * Template: post a project (create + edit).
 *
 * Available: $freelance (Zeko_Freelance), $db (Zeko_Freelance_DB),
 * $user_id (int), $atts (array) shortcode attributes.
 *
 * @package Zeko_Freelance
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings   = zeko_freelance_get_settings();
$categories = Zeko_Freelance_Ajax::allowed_categories();
$durations  = Zeko_Freelance_Ajax::durations();
$skills     = $db->get_skills();

$edit_id = isset( $_GET['zf_edit'] ) ? absint( $_GET['zf_edit'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
$editing = null;
if ( $edit_id ) {
	$candidate = $db->get_project( $edit_id );
	if ( $candidate && (int) $candidate->user_id === $user_id ) {
		$editing = $candidate;
	}
}

$form_title  = $editing ? $editing->title : '';
$form_desc   = $editing ? $editing->description : '';
$form_min    = $editing ? (float) $editing->budget_min : '';
$form_max    = $editing ? (float) $editing->budget_max : '';
$form_cat    = $editing ? $editing->category : '';
$form_loc    = $editing ? $editing->location : '';
$form_dur    = $editing ? $editing->duration : '';
$form_skills = array_filter( array_map( 'trim', explode( ',', $editing ? (string) $editing->skills : '' ) ) );
?>
<div class="zf-post-project">
	<nav class="zf-tabs" aria-label="<?php esc_attr_e( 'Freelance pages', 'zeko-freelance' ); ?>">
		<a class="zf-tab" href="<?php echo esc_url( zeko_freelance_page_url( 'freelance' ) ); ?>"><?php esc_html_e( 'Dashboard', 'zeko-freelance' ); ?></a>
		<a class="zf-tab" href="<?php echo esc_url( zeko_freelance_page_url( 'freelance-projects' ) ); ?>"><?php esc_html_e( 'Marketplace', 'zeko-freelance' ); ?></a>
		<a class="zf-tab zf-tab-active" href="<?php echo esc_url( zeko_freelance_page_url( 'freelance-post-project' ) ); ?>"><?php esc_html_e( 'Post a Project', 'zeko-freelance' ); ?></a>
		<a class="zf-tab" href="<?php echo esc_url( zeko_freelance_page_url( 'freelance-portfolios' ) ); ?>"><?php esc_html_e( 'Portfolios', 'zeko-freelance' ); ?></a>
	</nav>

	<h2 class="zf-section-title"><?php echo $editing ? esc_html__( 'Edit project', 'zeko-freelance' ) : esc_html__( 'Post a project', 'zeko-freelance' ); ?></h2>

	<?php if ( $editing && 'open' !== $editing->status ) : ?>
		<p class="zf-notice zf-notice-warning">
			<?php
			printf(
				/* translators: %s: current project status */
				esc_html__( 'This project is currently "%s" and cannot be edited until it is open again.', 'zeko-freelance' ),
				esc_html( $editing->status )
			);
			?>
		</p>
	<?php endif; ?>

	<?php if ( $edit_id && ! $editing ) : ?>
		<p class="zf-notice zf-notice-error"><?php esc_html_e( 'Project not found or you do not own it.', 'zeko-freelance' ); ?></p>
	<?php endif; ?>

	<form id="zf-project-form" class="zf-form" method="post">
		<input type="hidden" name="action" value="zeko_freelance_save_project" />
		<?php if ( $editing ) : ?>
			<input type="hidden" name="zf_project_id" value="<?php echo esc_attr( (string) $editing->id ); ?>" />
		<?php endif; ?>
		<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( Zeko_Freelance_Ajax::nonce_action() ) ); ?>" />

		<p class="zf-field">
			<label for="zf-title"><?php esc_html_e( 'Project title', 'zeko-freelance' ); ?> <span class="zf-required">*</span></label>
			<input type="text" id="zf-title" name="zf_title" value="<?php echo esc_attr( $form_title ); ?>" required minlength="4" maxlength="200" />
		</p>

		<p class="zf-field">
			<label for="zf-description"><?php esc_html_e( 'Description', 'zeko-freelance' ); ?> <span class="zf-required">*</span></label>
			<textarea id="zf-description" name="zf_description" rows="7" required minlength="20"><?php echo esc_textarea( $form_desc ); ?></textarea>
			<?php if ( class_exists( 'Zeko_AI_Writer_UI' ) ) : ?>
				<?php
				echo wp_kses_post(
					(string) Zeko_AI_Writer_UI::button(
						array(
							'preset' => 'project_brief',
							'target' => '#zf-description',
						)
					)
				);
				?>
			<?php endif; ?>
			<span class="zf-hint"><?php esc_html_e( 'Describe the work, deliverables and any deadlines. Minimum 20 characters.', 'zeko-freelance' ); ?></span>
		</p>

		<div class="zf-row">
			<p class="zf-field zf-col">
				<label for="zf-budget-min"><?php esc_html_e( 'Budget minimum', 'zeko-freelance' ); ?> (<?php echo esc_html( $settings['currency'] ); ?>) <span class="zf-required">*</span></label>
				<input type="number" id="zf-budget-min" name="zf_budget_min" value="<?php echo esc_attr( $form_min ); ?>" min="0" step="0.01" required />
			</p>
			<p class="zf-field zf-col">
				<label for="zf-budget-max"><?php esc_html_e( 'Budget maximum', 'zeko-freelance' ); ?> (<?php echo esc_html( $settings['currency'] ); ?>) <span class="zf-required">*</span></label>
				<input type="number" id="zf-budget-max" name="zf_budget_max" value="<?php echo esc_attr( $form_max ); ?>" min="0" step="0.01" required />
			</p>
		</div>

		<div class="zf-row">
			<p class="zf-field zf-col">
				<label for="zf-category"><?php esc_html_e( 'Category', 'zeko-freelance' ); ?> <span class="zf-required">*</span></label>
				<select id="zf-category" name="zf_category" required>
					<option value=""><?php esc_html_e( 'Select a category…', 'zeko-freelance' ); ?></option>
					<?php foreach ( $categories as $category_id ) : ?>
						<option value="<?php echo esc_attr( $category_id ); ?>" <?php selected( $form_cat, $category_id ); ?>><?php echo esc_html( ucfirst( $category_id ) ); ?></option>
					<?php endforeach; ?>
				</select>
			</p>
			<p class="zf-field zf-col">
				<label for="zf-duration"><?php esc_html_e( 'Estimated duration', 'zeko-freelance' ); ?> <span class="zf-required">*</span></label>
				<select id="zf-duration" name="zf_duration" required>
					<option value=""><?php esc_html_e( 'Select a duration…', 'zeko-freelance' ); ?></option>
					<?php foreach ( $durations as $key => $duration ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $form_dur, $key ); ?>><?php echo esc_html( $duration['label'] ); ?></option>
					<?php endforeach; ?>
				</select>
			</p>
		</div>

		<p class="zf-field">
			<label for="zf-location"><?php esc_html_e( 'Location', 'zeko-freelance' ); ?></label>
			<input type="text" id="zf-location" name="zf_location" value="<?php echo esc_attr( $form_loc ); ?>" maxlength="150" placeholder="<?php esc_attr_e( 'e.g. Remote, Lagos, New York', 'zeko-freelance' ); ?>" />
		</p>

		<fieldset class="zf-field">
			<legend><?php esc_html_e( 'Skills', 'zeko-freelance' ); ?></legend>
			<div class="zf-skill-grid">
				<?php foreach ( $skills as $skill ) : ?>
					<label class="zf-skill-check">
						<input type="checkbox" name="zf_skills[]" value="<?php echo esc_attr( $skill->name ); ?>" <?php checked( in_array( $skill->name, $form_skills, true ) ); ?> />
						<?php echo esc_html( $skill->name ); ?>
					</label>
				<?php endforeach; ?>
			</div>
			<p class="zf-field" style="margin-top:8px;">
				<input type="text" id="zf-skills-extra" name="zf_skills_extra" value="<?php echo esc_attr( implode( ', ', array_diff( $form_skills, wp_list_pluck( $skills, 'name' ) ) ) ); ?>" maxlength="400" list="zf-skill-options" data-zf-skill-suggest="1" placeholder="<?php esc_attr_e( 'Add your own skills…', 'zeko-freelance' ); ?>" />
				<datalist id="zf-skill-options"></datalist>
			</p>
		</fieldset>

		<p class="zf-form-actions">
			<button type="submit" class="zf-btn" data-submit-text="<?php echo $editing ? esc_attr__( 'Update project', 'zeko-freelance' ) : esc_attr__( 'Post project', 'zeko-freelance' ); ?>">
				<?php echo $editing ? esc_html__( 'Update project', 'zeko-freelance' ) : esc_html__( 'Post project', 'zeko-freelance' ); ?>
			</button>
			<?php if ( $editing ) : ?>
				<a class="zf-btn zf-btn-secondary" href="<?php echo esc_url( zeko_freelance_page_url( 'freelance-post-project' ) ); ?>"><?php esc_html_e( 'Cancel', 'zeko-freelance' ); ?></a>
			<?php endif; ?>
		</p>

		<p id="zf-form-notice" class="zf-notice" role="status" aria-live="polite"></p>
	</form>
</div>
