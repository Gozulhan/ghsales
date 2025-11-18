/**
 * GHSales Frontend JavaScript
 *
 * Handles BOGO quantity display in custom mini cart
 */

jQuery(document).ready(function($) {

    // Get translation helper
    function t(key, defaultValue) {
        return (typeof ghsalesTranslations !== 'undefined' && ghsalesTranslations[key])
            ? ghsalesTranslations[key]
            : defaultValue;
    }

    // Add BOGO quantity info to mini cart items
    function addBogoQuantityInfo() {
        // Find all mini cart items
        $('.ghminicart-item-details').each(function() {
            var $itemDetails = $(this);
            var $quantityDiv = $itemDetails.find('.ghminicart-item-quantity');

            // Skip if already processed
            if ($itemDetails.find('.ghsales-bogo-quantity').length > 0) {
                return;
            }

            // Look for BOGO badge with data attributes
            var $bogoBadge = $itemDetails.find('.ghsales-bogo-badge');

            if ($bogoBadge.length > 0) {
                // Get data from badge attributes
                var freePerPaid = parseInt($bogoBadge.data('free-per-paid')) || 1;
                var paidQty = parseInt($bogoBadge.data('paid-qty')) || 1;
                var totalReceived = parseInt($bogoBadge.data('total-qty')) || (paidQty * (1 + freePerPaid));

                // Create quantity info div with minimal styling - matches .text-cp-gray-200 .text-[12px]
                var $bogoQtyDiv = $('<div>', {
                    'class': 'ghsales-bogo-quantity text-cp-gray-200 text-[12px]',
                    'style': 'font-size: 12px; color: var(--color-cp-gray-200, #666); margin-bottom: 4px;'
                });

                // Use translations for "betaald" and "gratis"
                var paidText = t('paid', 'betaald');
                var freeText = t('free', 'gratis').toLowerCase();
                var andText = t('and', '+');

                $bogoQtyDiv.html(
                    paidQty + ' ' + paidText + ' ' + andText + ' ' + (paidQty * freePerPaid) + ' ' + freeText
                );

                // Insert BEFORE the quantity controls (directly above)
                $quantityDiv.before($bogoQtyDiv);
            }
        });
    }

    // Run on page load
    addBogoQuantityInfo();

    // Re-run when mini cart updates (listen for WooCommerce events)
    $(document.body).on('updated_cart_totals updated_checkout wc_fragments_refreshed', function() {
        setTimeout(addBogoQuantityInfo, 100);
    });

    // Also watch for quantity changes in mini cart
    $(document).on('click', '.qty-btn', function() {
        setTimeout(function() {
            // Remove old BOGO quantity divs
            $('.ghsales-bogo-quantity').remove();
            // Re-add with updated quantities
            addBogoQuantityInfo();
        }, 500);
    });

    // Listen for ghminicart drawer updates (AJAX refresh)
    $(document).on('ghminicart_drawer_updated', function() {
        console.log('GHSales: Drawer updated, re-adding BOGO info');
        setTimeout(function() {
            // Remove old BOGO quantity divs
            $('.ghsales-bogo-quantity').remove();
            // Re-add with updated quantities
            addBogoQuantityInfo();
        }, 100);
    });

    // Debug: Log when badges are found
    if ($('.ghsales-bogo-badge').length > 0) {
        console.log('GHSales: Found ' + $('.ghsales-bogo-badge').length + ' BOGO badges');
    } else {
        console.log('GHSales: No BOGO badges found on page load');
    }
});
