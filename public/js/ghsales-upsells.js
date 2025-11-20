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

		// CRITICAL: Destroy ALL stored instances first (they might be stale after DOM updates)
		console.log('🎠 GHSales: Destroying all stored Swiper instances (' + Object.keys(GHSalesUpsell.swiperInstances).length + ' total)');
		for (const instanceId in GHSalesUpsell.swiperInstances) {
			try {
				GHSalesUpsell.swiperInstances[instanceId].destroy(true, true);
				console.log('🎠 GHSales: Destroyed stored instance:', instanceId);
			} catch (e) {
				console.error('🎠 GHSales: Error destroying instance ' + instanceId + ':', e);
			}
		}
		GHSalesUpsell.swiperInstances = {}; // Clear all stored instances

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

		// CRITICAL FIX: Only find containers INSIDE the currently visible minicart drawer
		// This prevents initializing on cached/hidden fragments
		const minicartDrawer = document.querySelector('.ghminicart-drawer');
		if (!minicartDrawer) {
			console.warn('🎠 GHSales: No minicart drawer found, aborting');
			GHSalesUpsell.swiperInitializing = false;
			return;
		}

		// Find all unique parent containers ONLY within the active minicart drawer
		const containers = minicartDrawer.querySelectorAll('[id^="ghsales-special-sales-"], [id^="ghsales-cart-upsells-"], [id^="ghsales-minicart-upsells-"], [id^="ghsales-sale-products-"]');
		console.log('🎠 GHSales: Found ' + containers.length + ' container sections in minicart drawer');

		containers.forEach(function(container) {
			const containerId = container.id;
			const swiperEl = container.querySelector('.swiper');

			if (!swiperEl) {
				console.warn('🎠 GHSales: No .swiper found in container:', containerId);
				return;
			}

			// CRITICAL FIX: Skip hidden containers (desktop/mobile duplicates)
			// The cart drawer includes sale-section.php TWICE (desktop + mobile)
			// Check actual visibility: display !== 'none' AND has dimensions (not during CSS transition)
			const isVisible = window.getComputedStyle(container).display !== 'none' &&
			                  container.offsetWidth > 0 &&
			                  container.offsetHeight > 0;

			if (!isVisible) {
				console.log('🎠 GHSales: Skipping hidden container:', containerId);
				return;
			}

			console.log('🎠 GHSales: Processing visible container:', containerId);

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

			// CRITICAL FIX: Wait for drawer animation to complete (300ms) before initializing
			// This prevents width calculations during CSS transitions
			setTimeout(function() {
				try {
					// CRITICAL FIX: Use 'auto' to let CSS control widths (Swiper won't calculate)
					const swiperInstance = new Swiper('#' + containerId + ' .swiper', {
						// Let CSS control slide widths
						slidesPerView: 'auto',
						spaceBetween: 16,
						centeredSlides: false,
						slidesPerGroup: 1,
						loop: false,

						// DISABLE ALL AUTO-SCROLLING
						autoplay: false,
						freeMode: false,
						freeModeMomentum: false,
						freeModeSticky: false,
						autoHeight: false,

						// DISABLE ALL AUTO-UPDATE FEATURES
						observer: false,
						observeParents: false,
						observeSlideChildren: false,
						resizeObserver: false,
						updateOnWindowResize: false,
						watchOverflow: false,

						// DISABLED pagination to test if it's causing width bug
						// pagination: {
						// 	el: '#' + containerId + ' .swiper-pagination',
						// 	type: 'progressbar',
						// 	clickable: false
						// },

						// Breakpoints for responsive behavior
						breakpoints: {
							768: {
								slidesPerView: 3,
								spaceBetween: 16
							},
							1024: {
								slidesPerView: 2,
								spaceBetween: 16
							}
						},

						// DISABLED to test if causing width bug
						// watchSlidesProgress: true,
						// watchSlidesVisibility: true,

						// Callbacks
						on: {
							init: function() {
								console.log('🎠 Swiper initialized (ALL auto-updates DISABLED):', containerId);
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
			}, 350); // Wait 350ms for drawer animation (300ms) to complete
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

	// Listen for ALL cart update events to reinitialize Swipers
	$(document.body).on('wc_fragments_refreshed wc_fragments_loaded added_to_cart removed_from_cart updated_cart_totals', function(event) {
		console.log('🎠 GHSales: Cart event fired:', event.type);

		// Clear existing timer to debounce rapid events
		if (GHSalesUpsell.fragmentRefreshTimer) {
			console.log('🎠 GHSales: Clearing previous timer (debouncing)');
			clearTimeout(GHSalesUpsell.fragmentRefreshTimer);
		}

		// Wait 500ms after last event to ensure DOM is fully updated
		GHSalesUpsell.fragmentRefreshTimer = setTimeout(function() {
			console.log('🎠 GHSales: Debounced cart update complete, reinitializing Swipers');
			GHSalesUpsell.initSwipers();
		}, 500);
	});

	// CRITICAL FIX: Listen specifically for minicart drawer updates (fires AFTER HTML replacement)
	// This event is triggered by ghminicart plugin after drawer content is replaced via AJAX
	$(document).on('ghminicart_drawer_updated', function(event) {
		console.log('🎠 GHSales: Drawer HTML updated via AJAX, reinitializing Swipers');

		// Clear any pending timers from WooCommerce events to prevent conflicts
		if (GHSalesUpsell.fragmentRefreshTimer) {
			console.log('🎠 GHSales: Clearing WooCommerce event timer (drawer update takes priority)');
			clearTimeout(GHSalesUpsell.fragmentRefreshTimer);
		}

		// Wait only for drawer animation (400ms = 300ms animation + 100ms buffer)
		// No debounce needed since drawer HTML is already stable
		GHSalesUpsell.fragmentRefreshTimer = setTimeout(function() {
			console.log('🎠 GHSales: Drawer animation complete, initializing Swipers on fresh DOM');
			GHSalesUpsell.initSwipers();
		}, 400);
	});

	// Also initialize on page load if minicart is already present
	$(document).ready(function() {
		console.log('🎠 GHSales: Document ready, checking for existing Swipers');
		setTimeout(function() {
			GHSalesUpsell.initSwipers();
		}, 500);
	});

})(jQuery);
