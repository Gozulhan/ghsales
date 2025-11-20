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
			console.log('GHSales Upsell: Add to cart button clicked');

			const $button = $(e.currentTarget);
			const productId = $button.data('product-id');
			console.log('GHSales Upsell: Product ID:', productId);

			if (!productId || $button.hasClass('loading')) {
				console.warn('GHSales Upsell: No product ID or button is loading, aborting');
				return;
			}

			// Set loading state
			$button.addClass('loading').prop('disabled', true);
			console.log('GHSales Upsell: Button set to loading state');

			// Add to cart via AJAX
			console.log('GHSales Upsell: Sending AJAX request to:', wc_add_to_cart_params.ajax_url);
			$.ajax({
				url: wc_add_to_cart_params.ajax_url,
				type: 'POST',
				data: {
					action: 'woocommerce_add_to_cart',
					product_id: productId,
					quantity: 1
				},
				success: (response) => {
					console.log('GHSales Upsell: AJAX success response:', response);
					if (response.error) {
						console.error('GHSales Upsell: Error in response:', response.error);
						this.showError($button, response.error);
						return;
					}

					// Success - trigger cart update
					console.log('GHSales Upsell: Triggering added_to_cart event');
					$(document.body).trigger('added_to_cart', [response.fragments, response.cart_hash, $button]);

					// Show success feedback
					console.log('GHSales Upsell: Showing success feedback');
					this.showSuccess($button);

					// Reload fragments (mini cart content)
					console.log('GHSales Upsell: Triggering wc_fragment_refresh');
					$(document.body).trigger('wc_fragment_refresh');
				},
				error: (xhr, status, error) => {
					console.error('GHSales Upsell: AJAX error:', status, error);
					console.error('GHSales Upsell: XHR object:', xhr);
					this.showError($button, 'Failed to add product to cart');
				},
				complete: () => {
					console.log('GHSales Upsell: AJAX request complete');
					// Remove loading state after delay
					setTimeout(() => {
						$button.removeClass('loading').prop('disabled', false);
						console.log('GHSales Upsell: Loading state removed from button');
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
