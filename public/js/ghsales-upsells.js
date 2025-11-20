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

	console.log('GHSales Upsell: Script loaded');

	/**
	 * GHSales Upsell Handler
	 */
	const GHSalesUpsell = {
		/**
		 * Initialize
		 */
		init: function() {
			console.log('GHSales Upsell: Initializing...');
			this.bindEvents();
			console.log('GHSales Upsell: Initialized successfully');
		},

		/**
		 * Bind event handlers
		 */
		bindEvents: function() {
			console.log('GHSales Upsell: Binding event handlers to .ghsales-upsell-add-to-cart');

			// Bind in CAPTURING phase to prevent minicart from intercepting
			// Use native addEventListener instead of jQuery to access capturing phase
			document.addEventListener('click', (e) => {
				const button = e.target.closest('.ghsales-upsell-add-to-cart');
				if (button) {
					console.log('GHSales Upsell: Click captured in CAPTURING phase');
					e.preventDefault();
					e.stopPropagation();
					this.handleAddToCart({
						preventDefault: () => {},
						currentTarget: button
					});
				}
			}, true); // true = use capturing phase

			console.log('GHSales Upsell: Event handlers bound successfully (CAPTURING PHASE)');

			// Check if buttons exist on page
			const buttonCount = $('.ghsales-upsell-add-to-cart').length;
			console.log('GHSales Upsell: Found ' + buttonCount + ' add to cart buttons on page');
		},

		/**
		 * Handle add to cart button click
		 */
		handleAddToCart: function(e) {
			e.preventDefault();
			console.log('GHSales Upsell: Add to cart button clicked');
			console.log('GHSales Upsell: Event object:', e);

			const $button = $(e.currentTarget);
			console.log('GHSales Upsell: Button element:', $button[0]);

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
		 * Show success feedback - swap cart icon for checkmark
		 */
		showSuccess: function($button) {
			// Save original SVG HTML
			const originalSVG = $button.html();

			// Checkmark SVG (Material Design check icon)
			const checkmarkSVG = '<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#ffffff"><path d="M382-240 154-468l57-57 171 171 367-367 57 57-424 424Z"/></svg>';

			// Replace cart icon with checkmark and add success class
			$button.html(checkmarkSVG).addClass('success');

			// Restore original cart icon after 2 seconds
			setTimeout(() => {
				$button.html(originalSVG).removeClass('success');
			}, 2000);
		},

		/**
		 * Show error feedback
		 */
		showError: function($button, message) {
			// Save original SVG HTML
			const originalSVG = $button.html();

			// Error X SVG (Material Design close icon)
			const errorSVG = '<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#ffffff"><path d="m256-200-56-56 224-224-224-224 56-56 224 224 224-224 56 56-224 224 224 224-56 56-224-224-224 224Z"/></svg>';

			// Replace cart icon with X and add error class
			$button.html(errorSVG).addClass('error');

			console.error('GHSales Upsell Error:', message);

			// Restore original cart icon after 2 seconds
			setTimeout(() => {
				$button.html(originalSVG).removeClass('error');
			}, 2000);
		}
	};

	// Initialize on document ready
	$(document).ready(function() {
		console.log('GHSales Upsell: Document ready, calling init()');
		GHSalesUpsell.init();
	});

	/**
	 * Initialize Swiper carousels in minicart
	 * Called after minicart AJAX updates
	 */
	GHSalesUpsell.initSwipers = function() {
		console.log('🎠 GHSales: initSwipers() called');

		// Check if Swiper library is loaded
		if (typeof Swiper === 'undefined') {
			console.error('🎠 GHSales: Swiper library not loaded yet, retrying in 200ms...');
			setTimeout(function() {
				GHSalesUpsell.initSwipers();
			}, 200);
			return;
		}

		console.log('🎠 GHSales: Swiper library loaded, initializing carousels');

		// Find all Swiper containers in minicart
		const swiperContainers = document.querySelectorAll('.ghsales-cart-upsells .swiper, .ghsales-special-sales-section .swiper');
		console.log('🎠 GHSales: Found ' + swiperContainers.length + ' Swiper containers');

		swiperContainers.forEach(function(swiperEl) {
			// Get the parent container to find unique ID
			const parentSection = swiperEl.closest('[id^="ghsales-"]');
			if (!parentSection) {
				console.warn('🎠 GHSales: Swiper container has no parent section, skipping');
				return;
			}

			const containerId = parentSection.id;
			const selector = '#' + containerId + ' .swiper';
			console.log('🎠 GHSales: Initializing Swiper for:', selector);

			// Destroy existing instance if it exists
			if (swiperEl.swiper && typeof swiperEl.swiper.destroy === 'function') {
				console.log('🎠 GHSales: Destroying existing Swiper instance');
				swiperEl.swiper.destroy(true, true);
			}

			// Create new Swiper instance
			try {
				new Swiper(selector, {
					slidesPerView: 2,
					spaceBetween: 16,
					centeredSlides: false,
					slidesPerGroup: 1,
					loop: false,
					pagination: {
						el: '#' + containerId + ' .swiper-pagination',
						type: 'progressbar',
						clickable: false
					},
					breakpoints: {
						768: {
							slidesPerView: 3,
							spaceBetween: 16
						},
						1024: {
							slidesPerView: 2,
							spaceBetween: 16
						}
					}
				});
				console.log('🎠 GHSales: Swiper created successfully for:', containerId);
			} catch (error) {
				console.error('🎠 GHSales: Error creating Swiper:', error);
			}
		});
	};

	// Listen for minicart updates to reinitialize Swipers
	$(document.body).on('wc_fragments_refreshed wc_fragments_loaded', function() {
		console.log('🎠 GHSales: Minicart fragments updated, reinitializing Swipers');
		// Small delay to ensure DOM is updated
		setTimeout(function() {
			GHSalesUpsell.initSwipers();
		}, 100);
	});

	// Also initialize on page load if minicart is already present
	$(document).ready(function() {
		console.log('🎠 GHSales: Document ready, checking for existing Swipers');
		setTimeout(function() {
			GHSalesUpsell.initSwipers();
		}, 500);
	});

})(jQuery);
