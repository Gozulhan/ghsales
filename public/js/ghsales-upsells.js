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
	GHSalesUpsell.swiperInitializing = false; // Flag to prevent concurrent initializations
	GHSalesUpsell.swiperInstances = {}; // Store Swiper instances by container ID

	GHSalesUpsell.initSwipers = function() {
		GHSalesUpsell.initSwipersCallCount++;
		console.log('🎠 GHSales: initSwipers() called (call #' + GHSalesUpsell.initSwipersCallCount + ')');
		console.trace('🎠 GHSales: Call stack:');

		// Prevent concurrent initialization
		if (GHSalesUpsell.swiperInitializing) {
			console.warn('🎠 GHSales: Swiper initialization already in progress, skipping (call #' + GHSalesUpsell.initSwipersCallCount + ')');
			return;
		}

		GHSalesUpsell.swiperInitializing = true;

		// Check if Swiper library is loaded
		if (typeof Swiper === 'undefined') {
			console.error('🎠 GHSales: Swiper library not loaded yet, retrying in 200ms...');
			GHSalesUpsell.swiperInitializing = false;
			setTimeout(function() {
				GHSalesUpsell.initSwipers();
			}, 200);
			return;
		}

		console.log('🎠 GHSales: Swiper library loaded, initializing carousels');

		// Find all unique parent containers (not individual .swiper elements)
		const containers = document.querySelectorAll('[id^="ghsales-special-sales-"], [id^="ghsales-cart-upsells-"]');
		console.log('🎠 GHSales: Found ' + containers.length + ' container sections');

		containers.forEach(function(container) {
			const containerId = container.id;
			const swiperEl = container.querySelector('.swiper');

			if (!swiperEl) {
				console.warn('🎠 GHSales: No .swiper found in container:', containerId);
				return;
			}

			console.log('🎠 GHSales: Processing container:', containerId);

			// Destroy existing instance if stored
			if (GHSalesUpsell.swiperInstances[containerId]) {
				console.log('🎠 GHSales: Destroying stored Swiper instance for:', containerId);
				try {
					GHSalesUpsell.swiperInstances[containerId].destroy(true, true);
					delete GHSalesUpsell.swiperInstances[containerId];
				} catch (e) {
					console.error('🎠 GHSales: Error destroying stored instance:', e);
				}
			}

			// Also check element's swiper property
			if (swiperEl.swiper) {
				console.log('🎠 GHSales: Destroying element Swiper instance for:', containerId);
				try {
					swiperEl.swiper.destroy(true, true);
				} catch (e) {
					console.error('🎠 GHSales: Error destroying element instance:', e);
				}
			}

			// Small delay before creating new instance
			setTimeout(function() {
				try {
					const swiperInstance = new Swiper('#' + containerId + ' .swiper', {
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

					// Store instance reference
					GHSalesUpsell.swiperInstances[containerId] = swiperInstance;
					console.log('🎠 GHSales: Swiper created successfully for:', containerId);

					// Monitor slide width for 5 seconds to detect runaway growth
					const slideEl = swiperEl.querySelector('.swiper-slide');
					if (slideEl) {
						let widthCheckCount = 0;
						let lastWidth = slideEl.offsetWidth;
						console.log('🎠 GHSales: Initial slide width for ' + containerId + ':', lastWidth + 'px');

						const widthMonitor = setInterval(function() {
							widthCheckCount++;
							const currentWidth = slideEl.offsetWidth;

							if (currentWidth !== lastWidth) {
								console.warn('🎠 GHSales: WIDTH CHANGED for ' + containerId + '! Old: ' + lastWidth + 'px, New: ' + currentWidth + 'px (check #' + widthCheckCount + ')');
								lastWidth = currentWidth;
							}

							// Stop monitoring after 10 checks (5 seconds)
							if (widthCheckCount >= 10) {
								clearInterval(widthMonitor);
								console.log('🎠 GHSales: Width monitoring stopped for ' + containerId + '. Final width: ' + currentWidth + 'px');
							}
						}, 500);
					}
				} catch (error) {
					console.error('🎠 GHSales: Error creating Swiper for ' + containerId + ':', error);
				}
			}, 50);
		});

		// Reset flag after all processing
		setTimeout(function() {
			GHSalesUpsell.swiperInitializing = false;
			console.log('🎠 GHSales: Swiper initialization complete');
		}, 200);
	};

	// Debounce timer for fragment refresh events
	GHSalesUpsell.fragmentRefreshTimer = null;
	GHSalesUpsell.initSwipersCallCount = 0; // Track how many times initSwipers is called

	// Listen for minicart updates to reinitialize Swipers (DEBOUNCED)
	$(document.body).on('wc_fragments_refreshed wc_fragments_loaded', function(event) {
		console.log('🎠 GHSales: Fragment event fired:', event.type);

		// Clear existing timer to debounce rapid events
		if (GHSalesUpsell.fragmentRefreshTimer) {
			console.log('🎠 GHSales: Clearing previous timer (debouncing)');
			clearTimeout(GHSalesUpsell.fragmentRefreshTimer);
		}

		// Wait 300ms after last event before initializing
		GHSalesUpsell.fragmentRefreshTimer = setTimeout(function() {
			console.log('🎠 GHSales: Debounced fragment refresh complete, initializing Swipers');
			GHSalesUpsell.initSwipers();
		}, 300);
	});

	// Also initialize on page load if minicart is already present
	$(document).ready(function() {
		console.log('🎠 GHSales: Document ready, checking for existing Swipers');
		setTimeout(function() {
			GHSalesUpsell.initSwipers();
		}, 500);
	});

})(jQuery);
