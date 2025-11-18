/**
 * GHSales Upsell JavaScript
 *
 * Handles AJAX add-to-cart for upsell products
 *
 * @package GHSales
 * @since 1.0.0
 */

(function($) {
	'use strict';

	/**
	 * GHSales Upsell Handler
	 */
	const GHSalesUpsell = {
		/**
		 * Initialize
		 */
		init: function() {
			this.bindEvents();
		},

		/**
		 * Bind event handlers
		 */
		bindEvents: function() {
			$(document).on('click', '.ghsales-upsell-add-to-cart', this.handleAddToCart.bind(this));
		},

		/**
		 * Handle add to cart button click
		 */
		handleAddToCart: function(e) {
			e.preventDefault();

			const $button = $(e.currentTarget);
			const productId = $button.data('product-id');

			if (!productId || $button.hasClass('loading')) {
				return;
			}

			// Set loading state
			$button.addClass('loading').prop('disabled', true);

			// Add to cart via AJAX
			$.ajax({
				url: wc_add_to_cart_params.ajax_url,
				type: 'POST',
				data: {
					action: 'woocommerce_add_to_cart',
					product_id: productId,
					quantity: 1
				},
				success: (response) => {
					if (response.error) {
						this.showError($button, response.error);
						return;
					}

					// Success - trigger cart update
					$(document.body).trigger('added_to_cart', [response.fragments, response.cart_hash, $button]);

					// Show success feedback
					this.showSuccess($button);

					// Reload fragments (mini cart content)
					$(document.body).trigger('wc_fragment_refresh');
				},
				error: () => {
					this.showError($button, 'Failed to add product to cart');
				},
				complete: () => {
					// Remove loading state after delay
					setTimeout(() => {
						$button.removeClass('loading').prop('disabled', false);
					}, 1000);
				}
			});
		},

		/**
		 * Show success feedback
		 */
		showSuccess: function($button) {
			const originalText = $button.text();
			$button.text('✓ Added!').addClass('success');

			setTimeout(() => {
				$button.text(originalText).removeClass('success');
			}, 2000);
		},

		/**
		 * Show error feedback
		 */
		showError: function($button, message) {
			const originalText = $button.text();
			$button.text('Error!').addClass('error');

			console.error('GHSales Upsell Error:', message);

			setTimeout(() => {
				$button.text(originalText).removeClass('error');
			}, 2000);
		}
	};

	// Initialize on document ready
	$(document).ready(function() {
		GHSalesUpsell.init();
	});

})(jQuery);
