/**
 * Color Schemes Manager Admin JavaScript
 *
 * Handles DYNAMIC color picker initialization, live preview updates,
 * and AJAX operations for ALL Elementor colors (system + custom).
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
	 * Initialize WordPress color pickers for ALL dynamic color inputs
	 */
	function initColorPickers() {
		$('.ghsales-color-picker').wpColorPicker({
			change: function(event, ui) {
				// Get the color ID from data attribute
				var colorId = $(this).data('color-id');
				var colorHex = ui.color.toString();

				// Update live preview
				updateLivePreview(colorId, colorHex);
			},
			clear: function() {
				// Reset to default color when cleared
				var $input = $(this);
				var defaultColor = $input.data('default-color');
				var colorId = $input.data('color-id');

				updateLivePreview(colorId, defaultColor);
			}
		});
	}

	/**
	 * Update live preview swatch when color changes
	 *
	 * @param {string} colorId Color ID (e.g., 'primary', 'custom-1')
	 * @param {string} color Hex color value
	 */
	function updateLivePreview(colorId, color) {
		var previewId = '#preview-' + colorId;

		if ($(previewId).length) {
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

		// Pre-fill/Reset from Elementor button
		$('#ghsales-prefill-elementor').on('click', handleResetToElementor);
	}

	/**
	 * Handle form submission (create or update)
	 * Collects ALL dynamic colors and sends as array
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

		// Collect ALL colors from dynamic color pickers
		var colors = {};
		$('.ghsales-color-picker').each(function() {
			var colorId = $(this).data('color-id');
			var colorValue = $(this).val();

			if (colorId && colorValue) {
				colors[colorId] = colorValue;
			}
		});

		// Check if we have any colors
		if (Object.keys(colors).length === 0) {
			showMessage('Please set at least one color', 'error');
			return;
		}

		// Prepare form data
		var formData = {
			action: 'ghsales_save_color_scheme',
			nonce: ghsalesColorSchemes.nonce,
			scheme_id: $('#scheme-id').val(),
			scheme_name: schemeName,
			colors: colors
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
					$('#ghsales-save-scheme').prop('disabled', false).text('Save Color Scheme');
				}
			},
			error: function() {
				showMessage(ghsalesColorSchemes.strings.error, 'error');
				$form.removeClass('ghsales-loading');
				$('#ghsales-save-scheme').prop('disabled', false).text('Save Color Scheme');
			}
		});
	}

	/**
	 * Handle edit scheme button click
	 * Loads saved colors from data attribute and populates dynamic form
	 */
	function handleEditScheme() {
		var schemeId = $(this).data('scheme-id');
		var $row = $(this).closest('tr');
		var schemeName = $row.find('strong').text().trim();

		// Get saved colors from data attribute (JSON)
		var savedColors = $row.data('colors');

		// Populate form fields
		$('#scheme-id').val(schemeId);
		$('#scheme-name').val(schemeName);

		// Update ALL dynamic color pickers with saved values
		if (savedColors && typeof savedColors === 'object') {
			$.each(savedColors, function(colorId, colorHex) {
				var $colorInput = $('#color-' + colorId);

				if ($colorInput.length) {
					// Update color picker value
					$colorInput.wpColorPicker('color', colorHex);
				}
			});
		}

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
	 * Handle reset to Elementor colors button click
	 * Resets ALL color pickers to their default Elementor values
	 */
	function handleResetToElementor() {
		// Reset all color pickers to their default values
		$('.ghsales-color-picker').each(function() {
			var defaultColor = $(this).data('default-color');

			if (defaultColor) {
				$(this).wpColorPicker('color', defaultColor);
			}
		});

		showMessage('Colors reset to Elementor defaults', 'success');
	}

	/**
	 * Reset form to create new scheme mode
	 */
	function resetForm(e) {
		if (e) {
			e.preventDefault();
		}

		// Clear form
		$('#ghsales-color-scheme-form')[0].reset();
		$('#scheme-id').val('0');

		// Reset ALL color pickers to defaults
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

		// Scroll to editor
		$('html, body').animate({
			scrollTop: $('.ghsales-scheme-editor').offset().top - 50
		}, 500);

		// Focus on name input
		setTimeout(function() {
			$('#scheme-name').focus();
		}, 600);
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
	 * Add CSS for spinning animation
	 */
	var spinCSS = '<style>.dashicons.spin { animation: ghsales-dashicons-spin 1s linear infinite; } @keyframes ghsales-dashicons-spin { to { transform: rotate(360deg); } }</style>';
	$('head').append(spinCSS);

	// Initialize on document ready
	$(document).ready(init);

})(jQuery);
