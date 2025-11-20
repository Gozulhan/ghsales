<?php
/**
 * Color Schemes Manager Template
 *
 * Admin interface for creating and managing color schemes with dynamic color pickers.
 * Detects ALL Elementor colors (system + custom) and allows customization.
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
							<?php
							// Decode JSON colors or use legacy columns
							$scheme_colors = array();
							if ( ! empty( $scheme->colors_json ) ) {
								$scheme_colors = json_decode( $scheme->colors_json, true );
							}
							// Fallback to legacy columns if JSON not available
							if ( empty( $scheme_colors ) ) {
								$scheme_colors = array(
									'primary'    => $scheme->primary_color,
									'secondary'  => $scheme->secondary_color,
									'accent'     => $scheme->accent_color,
									'text'       => $scheme->text_color,
									'background' => $scheme->background_color,
								);
							}
							?>
							<tr data-scheme-id="<?php echo esc_attr( $scheme->id ); ?>" data-colors="<?php echo esc_attr( wp_json_encode( $scheme_colors ) ); ?>">
								<td>
									<strong><?php echo esc_html( $scheme->scheme_name ); ?></strong>
									<?php if ( 1 === (int) $scheme->id ) : ?>
										<span class="ghsales-default-badge"><?php esc_html_e( 'Default', 'ghsales' ); ?></span>
									<?php endif; ?>
								</td>
								<td>
									<div class="ghsales-color-preview">
										<?php
										$preview_count = 0;
										foreach ( $scheme_colors as $color_id => $color_hex ) :
											if ( $preview_count >= 8 ) {
												break; // Show max 8 swatches in preview
											}
											?>
											<span class="ghsales-color-swatch"
												  style="background-color: <?php echo esc_attr( $color_hex ); ?>;"
												  title="<?php echo esc_attr( ucfirst( $color_id ) ); ?>"></span>
											<?php
											$preview_count++;
										endforeach;
										?>
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

					<!-- Detected Elementor Colors Info -->
					<div class="ghsales-detected-colors-info">
						<?php
						$system_colors = array_filter( $elementor_colors, function( $item ) {
							return $item['type'] === 'system';
						});
						$custom_colors = array_filter( $elementor_colors, function( $item ) {
							return $item['type'] === 'custom';
						});
						?>
						<p class="description">
							<?php
							printf(
								/* translators: %1$d: number of system colors, %2$d: number of custom colors */
								esc_html__( 'Detected %1$d system colors and %2$d custom colors from Elementor.', 'ghsales' ),
								count( $system_colors ),
								count( $custom_colors )
							);
							?>
						</p>
					</div>

					<!-- Dynamic Color Pickers -->
					<div id="ghsales-color-fields-container">

						<?php if ( ! empty( $system_colors ) ) : ?>
							<h3 class="ghsales-color-section-title"><?php esc_html_e( 'System Colors', 'ghsales' ); ?></h3>
							<div class="ghsales-color-fields ghsales-system-colors">
								<?php foreach ( $system_colors as $color_id => $color_data ) : ?>
									<div class="ghsales-form-field ghsales-color-picker-field" data-color-id="<?php echo esc_attr( $color_id ); ?>">
										<label for="color-<?php echo esc_attr( $color_id ); ?>">
											<?php echo esc_html( $color_data['title'] ); ?>
											<code class="ghsales-color-id"><?php echo esc_html( $color_id ); ?></code>
										</label>
										<input type="text"
											   id="color-<?php echo esc_attr( $color_id ); ?>"
											   name="colors[<?php echo esc_attr( $color_id ); ?>]"
											   class="ghsales-color-picker"
											   value="<?php echo esc_attr( $color_data['color'] ); ?>"
											   data-default-color="<?php echo esc_attr( $color_data['color'] ); ?>"
											   data-color-id="<?php echo esc_attr( $color_id ); ?>">
									</div>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>

						<?php if ( ! empty( $custom_colors ) ) : ?>
							<h3 class="ghsales-color-section-title"><?php esc_html_e( 'Custom Colors', 'ghsales' ); ?></h3>
							<div class="ghsales-color-fields ghsales-custom-colors">
								<?php foreach ( $custom_colors as $color_id => $color_data ) : ?>
									<div class="ghsales-form-field ghsales-color-picker-field" data-color-id="<?php echo esc_attr( $color_id ); ?>">
										<label for="color-<?php echo esc_attr( $color_id ); ?>">
											<?php echo esc_html( $color_data['title'] ); ?>
											<code class="ghsales-color-id"><?php echo esc_html( $color_id ); ?></code>
										</label>
										<input type="text"
											   id="color-<?php echo esc_attr( $color_id ); ?>"
											   name="colors[<?php echo esc_attr( $color_id ); ?>]"
											   class="ghsales-color-picker"
											   value="<?php echo esc_attr( $color_data['color'] ); ?>"
											   data-default-color="<?php echo esc_attr( $color_data['color'] ); ?>"
											   data-color-id="<?php echo esc_attr( $color_id ); ?>">
									</div>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>

						<?php if ( empty( $elementor_colors ) ) : ?>
							<div class="notice notice-warning inline">
								<p><?php esc_html_e( 'No Elementor colors detected. Make sure Elementor is installed and has global colors configured.', 'ghsales' ); ?></p>
							</div>
						<?php endif; ?>

					</div>

					<!-- Live Preview -->
					<div class="ghsales-color-preview-box">
						<h3><?php esc_html_e( 'Live Preview', 'ghsales' ); ?></h3>
						<div class="ghsales-preview-swatches" id="ghsales-preview-container">
							<?php foreach ( $elementor_colors as $color_id => $color_data ) : ?>
								<div class="ghsales-preview-swatch" data-color-id="<?php echo esc_attr( $color_id ); ?>">
									<div class="swatch-box"
										 id="preview-<?php echo esc_attr( $color_id ); ?>"
										 style="background-color: <?php echo esc_attr( $color_data['color'] ); ?>;"></div>
									<span class="swatch-title"><?php echo esc_html( $color_data['title'] ); ?></span>
									<code class="swatch-id"><?php echo esc_html( $color_id ); ?></code>
								</div>
							<?php endforeach; ?>
						</div>
					</div>

					<!-- Form Actions -->
					<div class="ghsales-form-actions">
						<button type="button" id="ghsales-prefill-elementor" class="button">
							<span class="dashicons dashicons-admin-appearance"></span>
							<?php esc_html_e( 'Reset to Elementor Colors', 'ghsales' ); ?>
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
