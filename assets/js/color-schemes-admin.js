/**
 * Color Schemes Manager Admin JavaScript
 *
 * Handles color picker initialization, live preview updates,
 * and AJAX operations for color scheme CRUD.
 *
 * @package GHSales
 * @since 1.2.0
 */

(function($) {
	'use strict';

	/**
	 * Initialize color pickers and event handlers
	 */
	function init() {
		initColorPickers();
		bindEvents();
	}

	/**
	 * Initialize WordPress color pickers
	 */
	function initColorPickers() {
		$('.ghsales-color-picker').wpColorPicker({
			change: function(event, ui) {
				// Update live preview when color changes
				updateLivePreview($(this), ui.color.toString());
			},
			clear: function() {
				// Reset to default color when cleared
				var $input = $(this);
				var defaultColor = $input.data('default-color');
				updateLivePreview($input, defaultColor);
			}
		});
	}

	/**
	 * Update live preview swatch when color changes
	 *
	 * @param {jQuery} $input Color picker input
	 * @param {string} color Hex color value
	 */
	function updateLivePreview($input, color) {
		var fieldId = $input.attr('id');
		var previewId = '';

		// Map input ID to preview swatch ID
		switch(fieldId) {
			case 'primary-color':
				previewId = '#preview-primary';
				break;
			case 'secondary-color':
				previewId = '#preview-secondary';
				break;
			case 'accent-color':
				previewId = '#preview-accent';
				break;
			case 'text-color':
				previewId = '#preview-text';
				break;
			case 'background-color':
				previewId = '#preview-background';
				break;
		}

		// Update preview swatch background color
		if (previewId) {
			$(previewId).css('background-color', color);
		}
	}

	/**
	 * Bind event handlers
	 */
	function bindEvents() {
		// Create new scheme button
		$('#ghsales-create-scheme-btn').on('click', resetForm);

		// Save scheme form submission
		$('#ghsales-color-scheme-form').on('submit', handleFormSubmit);

		// Cancel edit button
		$('#ghsales-cancel-edit').on('click', resetForm);

		// Edit scheme buttons
		$(document).on('click', '.ghsales-edit-scheme', handleEditScheme);

		// Delete scheme buttons
		$(document).on('click', '.ghsales-delete-scheme', handleDeleteScheme);

		// Pre-fill from Elementor button
		$('#ghsales-prefill-elementor').on('click', handlePrefillElementor);
	}

	/**
	 * Handle form submission (create or update)
	 *
	 * @param {Event} e Form submit event
	 */
	function handleFormSubmit(e) {
		e.preventDefault();

		// Validate form
		var schemeName = $('#scheme-name').val().trim();
		if (!schemeName) {
			showMessage(ghsalesColorSchemes.strings.nameRequired, 'error');
			$('#scheme-name').focus();
			return;
		}

		// Collect form data
		var formData = {
			action: 'ghsales_save_color_scheme',
			nonce: ghsalesColorSchemes.nonce,
			scheme_id: $('#scheme-id').val(),
			scheme_name: schemeName,
			primary_color: $('#primary-color').val(),
			secondary_color: $('#secondary-color').val(),
			accent_color: $('#accent-color').val(),
			text_color: $('#text-color').val(),
			background_color: $('#background-color').val()
		};

		// Show loading state
		var $form = $(this);
		$form.addClass('ghsales-loading');
		$('#ghsales-save-scheme').prop('disabled', true).text(ghsalesColorSchemes.strings.saving);

		// Send AJAX request
		$.ajax({
			url: ghsalesColorSchemes.ajaxUrl,
			type: 'POST',
			data: formData,
			success: function(response) {
				if (response.success) {
					showMessage(ghsalesColorSchemes.strings.saved, 'success');

					// Reload page after short delay to show updated list
					setTimeout(function() {
						window.location.reload();
					}, 1000);
				} else {
					showMessage(response.data || ghsalesColorSchemes.strings.error, 'error');
					$form.removeClass('ghsales-loading');
					$('#ghsales-save-scheme').prop('disabled', false).text(ghsalesColorSchemes.strings.saveButton || 'Save Color Scheme');
				}
			},
			error: function() {
				showMessage(ghsalesColorSchemes.strings.error, 'error');
				$form.removeClass('ghsales-loading');
				$('#ghsales-save-scheme').prop('disabled', false).text(ghsalesColorSchemes.strings.saveButton || 'Save Color Scheme');
			}
		});
	}

	/**
	 * Handle edit scheme button click
	 */
	function handleEditScheme() {
		var schemeId = $(this).data('scheme-id');

		// Get scheme data from table row
		var $row = $(this).closest('tr');
		var schemeName = $row.find('strong').text().trim();
		var $swatches = $row.find('.ghsales-color-swatch');

		// Populate form
		$('#scheme-id').val(schemeId);
		$('#scheme-name').val(schemeName);

		// Extract colors from swatches and update form
		$swatches.each(function(index) {
			var color = $(this).css('background-color');
			var hexColor = rgbToHex(color);

			switch(index) {
				case 0: // Primary
					$('#primary-color').wpColorPicker('color', hexColor);
					break;
				case 1: // Secondary
					$('#secondary-color').wpColorPicker('color', hexColor);
					break;
				case 2: // Accent
					$('#accent-color').wpColorPicker('color', hexColor);
					break;
				case 3: // Text
					$('#text-color').wpColorPicker('color', hexColor);
					break;
				case 4: // Background
					$('#background-color').wpColorPicker('color', hexColor);
					break;
			}
		});

		// Update UI for editing mode
		$('#ghsales-editor-title').text('Edit Color Scheme');
		$('#ghsales-cancel-edit').show();
		$('#ghsales-save-scheme').text('Update Color Scheme');

		// Scroll to editor
		$('html, body').animate({
			scrollTop: $('.ghsales-scheme-editor').offset().top - 50
		}, 500);
	}

	/**
	 * Handle delete scheme button click
	 */
	function handleDeleteScheme() {
		var schemeId = $(this).data('scheme-id');
		var schemeName = $(this).closest('tr').find('strong').text().trim();

		// Confirm deletion
		if (!confirm(ghsalesColorSchemes.strings.confirmDelete)) {
			return;
		}

		var $button = $(this);
		$button.prop('disabled', true).text('Deleting...');

		// Send AJAX delete request
		$.ajax({
			url: ghsalesColorSchemes.ajaxUrl,
			type: 'POST',
			data: {
				action: 'ghsales_delete_color_scheme',
				nonce: ghsalesColorSchemes.nonce,
				scheme_id: schemeId
			},
			success: function(response) {
				if (response.success) {
					// Remove row from table
					$button.closest('tr').fadeOut(300, function() {
						$(this).remove();
					});
					showMessage('Color scheme deleted successfully', 'success');
				} else {
					showMessage(response.data || 'Failed to delete color scheme', 'error');
					$button.prop('disabled', false).text('Delete');
				}
			},
			error: function() {
				showMessage('Error deleting color scheme', 'error');
				$button.prop('disabled', false).text('Delete');
			}
		});
	}

	/**
	 * Handle pre-fill from Elementor button click
	 */
	function handlePrefillElementor() {
		var $button = $(this);
		$button.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Loading...');

		// Get Elementor colors via AJAX
		$.ajax({
			url: ghsalesColorSchemes.ajaxUrl,
			type: 'POST',
			data: {
				action: 'ghsales_get_elementor_colors',
				nonce: ghsalesColorSchemes.nonce
			},
			success: function(response) {
				if (response.success && response.data) {
					var colors = response.data;

					// Update color pickers with Elementor colors
					if (colors.primary) {
						$('#primary-color').wpColorPicker('color', colors.primary);
					}
					if (colors.secondary) {
						$('#secondary-color').wpColorPicker('color', colors.secondary);
					}
					if (colors.accent) {
						$('#accent-color').wpColorPicker('color', colors.accent);
					}
					if (colors.text) {
						$('#text-color').wpColorPicker('color', colors.text);
					}

					showMessage('Colors pre-filled from Elementor', 'success');
				} else {
					showMessage('Could not load Elementor colors', 'error');
				}

				$button.prop('disabled', false).html('<span class="dashicons dashicons-admin-appearance"></span> Pre-fill from Elementor');
			},
			error: function() {
				showMessage('Error loading Elementor colors', 'error');
				$button.prop('disabled', false).html('<span class="dashicons dashicons-admin-appearance"></span> Pre-fill from Elementor');
			}
		});
	}

	/**
	 * Reset form to create new scheme mode
	 */
	function resetForm() {
		// Clear form
		$('#ghsales-color-scheme-form')[0].reset();
		$('#scheme-id').val('0');

		// Reset color pickers to defaults
		$('.ghsales-color-picker').each(function() {
			var defaultColor = $(this).data('default-color');
			$(this).wpColorPicker('color', defaultColor);
		});

		// Update UI for create mode
		$('#ghsales-editor-title').text('Create New Color Scheme');
		$('#ghsales-cancel-edit').hide();
		$('#ghsales-save-scheme').text('Save Color Scheme');

		// Hide any messages
		$('#ghsales-form-message').slideUp();
	}

	/**
	 * Show status message
	 *
	 * @param {string} message Message text
	 * @param {string} type Message type: 'success', 'error', 'info'
	 */
	function showMessage(message, type) {
		var $messageBox = $('#ghsales-form-message');

		$messageBox
			.removeClass('success error info')
			.addClass(type)
			.text(message)
			.slideDown();

		// Auto-hide success messages after 5 seconds
		if (type === 'success') {
			setTimeout(function() {
				$messageBox.slideUp();
			}, 5000);
		}
	}

	/**
	 * Convert RGB color to Hex
	 *
	 * @param {string} rgb RGB color string (e.g., "rgb(255, 0, 0)")
	 * @return {string} Hex color string (e.g., "#ff0000")
	 */
	function rgbToHex(rgb) {
		// Handle already hex colors
		if (rgb.indexOf('#') === 0) {
			return rgb;
		}

		// Extract RGB values
		var matches = rgb.match(/^rgb\((\d+),\s*(\d+),\s*(\d+)\)$/);
		if (!matches) {
			return '#000000';
		}

		// Convert to hex
		function toHex(num) {
			var hex = parseInt(num).toString(16);
			return hex.length === 1 ? '0' + hex : hex;
		}

		return '#' + toHex(matches[1]) + toHex(matches[2]) + toHex(matches[3]);
	}

	/**
	 * Add CSS for spinning animation
	 */
	var spinCSS = '<style>.dashicons.spin { animation: ghsales-dashicons-spin 1s linear infinite; } @keyframes ghsales-dashicons-spin { to { transform: rotate(360deg); } }</style>';
	$('head').append(spinCSS);

	// Initialize on document ready
	$(document).ready(init);

})(jQuery);
