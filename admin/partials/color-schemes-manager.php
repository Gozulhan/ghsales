<?php
/**
 * Color Schemes Manager Template
 *
 * Admin interface for creating and managing color schemes with color pickers.
 *
 * @package GHSales
 * @since 1.2.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wrap ghsales-color-schemes-wrap">
	<h1 class="wp-heading-inline">
		<?php esc_html_e( 'Color Schemes Manager', 'ghsales' ); ?>
	</h1>

	<button type="button" class="page-title-action" id="ghsales-create-scheme-btn">
		<?php esc_html_e( 'Create New Color Scheme', 'ghsales' ); ?>
	</button>

	<hr class="wp-header-end">

	<div class="ghsales-schemes-grid">

		<!-- Left Column: Schemes List -->
		<div class="ghsales-schemes-list-section">
			<h2><?php esc_html_e( 'Existing Color Schemes', 'ghsales' ); ?></h2>

			<?php if ( ! empty( $color_schemes ) ) : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Scheme Name', 'ghsales' ); ?></th>
							<th><?php esc_html_e( 'Colors Preview', 'ghsales' ); ?></th>
							<th><?php esc_html_e( 'Actions', 'ghsales' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $color_schemes as $scheme ) : ?>
							<tr>
								<td>
									<strong><?php echo esc_html( $scheme->scheme_name ); ?></strong>
									<?php if ( 1 === (int) $scheme->id ) : ?>
										<span class="ghsales-default-badge"><?php esc_html_e( 'Default', 'ghsales' ); ?></span>
									<?php endif; ?>
								</td>
								<td>
									<div class="ghsales-color-preview">
										<span class="ghsales-color-swatch" style="background-color: <?php echo esc_attr( $scheme->primary_color ); ?>;" title="<?php esc_attr_e( 'Primary', 'ghsales' ); ?>"></span>
										<span class="ghsales-color-swatch" style="background-color: <?php echo esc_attr( $scheme->secondary_color ); ?>;" title="<?php esc_attr_e( 'Secondary', 'ghsales' ); ?>"></span>
										<span class="ghsales-color-swatch" style="background-color: <?php echo esc_attr( $scheme->accent_color ); ?>;" title="<?php esc_attr_e( 'Accent', 'ghsales' ); ?>"></span>
										<span class="ghsales-color-swatch" style="background-color: <?php echo esc_attr( $scheme->text_color ); ?>;" title="<?php esc_attr_e( 'Text', 'ghsales' ); ?>"></span>
										<span class="ghsales-color-swatch" style="background-color: <?php echo esc_attr( $scheme->background_color ); ?>;" title="<?php esc_attr_e( 'Background', 'ghsales' ); ?>"></span>
									</div>
								</td>
								<td>
									<button type="button" class="button ghsales-edit-scheme" data-scheme-id="<?php echo esc_attr( $scheme->id ); ?>">
										<?php esc_html_e( 'Edit', 'ghsales' ); ?>
									</button>
									<?php if ( 1 !== (int) $scheme->id ) : ?>
										<button type="button" class="button ghsales-delete-scheme" data-scheme-id="<?php echo esc_attr( $scheme->id ); ?>">
											<?php esc_html_e( 'Delete', 'ghsales' ); ?>
										</button>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php else : ?>
				<p><?php esc_html_e( 'No color schemes found. Create your first scheme!', 'ghsales' ); ?></p>
			<?php endif; ?>
		</div>

		<!-- Right Column: Create/Edit Form -->
		<div class="ghsales-scheme-editor-section">
			<div class="ghsales-scheme-editor">
				<h2 id="ghsales-editor-title"><?php esc_html_e( 'Create New Color Scheme', 'ghsales' ); ?></h2>

				<form id="ghsales-color-scheme-form">
					<input type="hidden" id="scheme-id" name="scheme_id" value="0">

					<!-- Scheme Name -->
					<div class="ghsales-form-field">
						<label for="scheme-name">
							<?php esc_html_e( 'Scheme Name', 'ghsales' ); ?>
							<span class="required">*</span>
						</label>
						<input type="text" id="scheme-name" name="scheme_name" class="regular-text" placeholder="<?php esc_attr_e( 'e.g., Black Friday Red', 'ghsales' ); ?>" required>
						<p class="description"><?php esc_html_e( 'Give your color scheme a descriptive name', 'ghsales' ); ?></p>
					</div>

					<!-- Color Pickers -->
					<div class="ghsales-color-fields">

						<!-- Primary Color -->
						<div class="ghsales-form-field ghsales-color-picker-field">
							<label for="primary-color">
								<?php esc_html_e( 'Primary Color', 'ghsales' ); ?>
								<span class="required">*</span>
							</label>
							<input type="text" id="primary-color" name="primary_color" class="ghsales-color-picker" value="<?php echo esc_attr( $elementor_colors['primary'] ); ?>" data-default-color="<?php echo esc_attr( $elementor_colors['primary'] ); ?>">
							<p class="description"><?php esc_html_e( 'Main brand color (buttons, links)', 'ghsales' ); ?></p>
						</div>

						<!-- Secondary Color -->
						<div class="ghsales-form-field ghsales-color-picker-field">
							<label for="secondary-color">
								<?php esc_html_e( 'Secondary Color', 'ghsales' ); ?>
								<span class="required">*</span>
							</label>
							<input type="text" id="secondary-color" name="secondary_color" class="ghsales-color-picker" value="<?php echo esc_attr( $elementor_colors['secondary'] ); ?>" data-default-color="<?php echo esc_attr( $elementor_colors['secondary'] ); ?>">
							<p class="description"><?php esc_html_e( 'Secondary brand color', 'ghsales' ); ?></p>
						</div>

						<!-- Accent Color -->
						<div class="ghsales-form-field ghsales-color-picker-field">
							<label for="accent-color">
								<?php esc_html_e( 'Accent Color', 'ghsales' ); ?>
								<span class="required">*</span>
							</label>
							<input type="text" id="accent-color" name="accent_color" class="ghsales-color-picker" value="<?php echo esc_attr( $elementor_colors['accent'] ); ?>" data-default-color="<?php echo esc_attr( $elementor_colors['accent'] ); ?>">
							<p class="description"><?php esc_html_e( 'Accent color for highlights', 'ghsales' ); ?></p>
						</div>

						<!-- Text Color -->
						<div class="ghsales-form-field ghsales-color-picker-field">
							<label for="text-color">
								<?php esc_html_e( 'Text Color', 'ghsales' ); ?>
								<span class="required">*</span>
							</label>
							<input type="text" id="text-color" name="text_color" class="ghsales-color-picker" value="<?php echo esc_attr( $elementor_colors['text'] ); ?>" data-default-color="<?php echo esc_attr( $elementor_colors['text'] ); ?>">
							<p class="description"><?php esc_html_e( 'Main text color', 'ghsales' ); ?></p>
						</div>

						<!-- Background Color -->
						<div class="ghsales-form-field ghsales-color-picker-field">
							<label for="background-color">
								<?php esc_html_e( 'Background Color', 'ghsales' ); ?>
							</label>
							<input type="text" id="background-color" name="background_color" class="ghsales-color-picker" value="#ffffff" data-default-color="#ffffff">
							<p class="description"><?php esc_html_e( 'Background color (optional)', 'ghsales' ); ?></p>
						</div>

					</div>

					<!-- Color Preview -->
					<div class="ghsales-color-preview-box">
						<h3><?php esc_html_e( 'Live Preview', 'ghsales' ); ?></h3>
						<div class="ghsales-preview-swatches">
							<div class="ghsales-preview-swatch">
								<div class="swatch-box" id="preview-primary" style="background-color: <?php echo esc_attr( $elementor_colors['primary'] ); ?>;"></div>
								<span><?php esc_html_e( 'Primary', 'ghsales' ); ?></span>
							</div>
							<div class="ghsales-preview-swatch">
								<div class="swatch-box" id="preview-secondary" style="background-color: <?php echo esc_attr( $elementor_colors['secondary'] ); ?>;"></div>
								<span><?php esc_html_e( 'Secondary', 'ghsales' ); ?></span>
							</div>
							<div class="ghsales-preview-swatch">
								<div class="swatch-box" id="preview-accent" style="background-color: <?php echo esc_attr( $elementor_colors['accent'] ); ?>;"></div>
								<span><?php esc_html_e( 'Accent', 'ghsales' ); ?></span>
							</div>
							<div class="ghsales-preview-swatch">
								<div class="swatch-box" id="preview-text" style="background-color: <?php echo esc_attr( $elementor_colors['text'] ); ?>;"></div>
								<span><?php esc_html_e( 'Text', 'ghsales' ); ?></span>
							</div>
							<div class="ghsales-preview-swatch">
								<div class="swatch-box" id="preview-background" style="background-color: #ffffff;"></div>
								<span><?php esc_html_e( 'Background', 'ghsales' ); ?></span>
							</div>
						</div>
					</div>

					<!-- Form Actions -->
					<div class="ghsales-form-actions">
						<button type="button" id="ghsales-prefill-elementor" class="button">
							<span class="dashicons dashicons-admin-appearance"></span>
							<?php esc_html_e( 'Pre-fill from Elementor', 'ghsales' ); ?>
						</button>

						<div class="ghsales-form-buttons">
							<button type="button" id="ghsales-cancel-edit" class="button" style="display: none;">
								<?php esc_html_e( 'Cancel', 'ghsales' ); ?>
							</button>
							<button type="submit" id="ghsales-save-scheme" class="button button-primary">
								<?php esc_html_e( 'Save Color Scheme', 'ghsales' ); ?>
							</button>
						</div>
					</div>

					<!-- Status Messages -->
					<div id="ghsales-form-message" class="ghsales-message" style="display: none;"></div>

				</form>
			</div>
		</div>

	</div>
</div>
