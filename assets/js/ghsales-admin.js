/**
 * GHSales Admin JavaScript
 *
 * Handles admin interface interactions
 */

jQuery(document).ready(function($) {

    // Sale Event Rule Management
    let ruleIndex = $('.ghsales-rule-row').length;

    // Initialize Select2 for existing selectors
    initializeSelect2();

    // Update discount value labels on page load
    updateDiscountValueLabels();

    // Add new rule
    $('#ghsales-add-rule').on('click', function(e) {
        e.preventDefault();

        // Get template
        let template = $('#ghsales-rule-template').html();

        // Replace placeholders with actual values
        template = template.replace(/{{INDEX}}/g, ruleIndex);
        template = template.replace(/{{DISPLAY_NUMBER}}/g, ruleIndex + 1);

        // Append to container
        $('#ghsales-rules-container').append(template);

        // Initialize Select2 for new selectors
        initializeSelect2();

        // Update labels for the new rule
        updateDiscountValueLabels();

        ruleIndex++;
    });

    // Remove rule
    $(document).on('click', '.ghsales-remove-rule', function(e) {
        e.preventDefault();

        if (confirm('Are you sure you want to remove this rule?')) {
            $(this).closest('.ghsales-rule-row').remove();

            // Re-number remaining rules
            $('.ghsales-rule-row').each(function(index) {
                $(this).find('h4 span').text('Rule #' + (index + 1));
            });
        }
    });

    // Handle "Applies To" dropdown change
    $(document).on('change', '.ghsales-applies-to', function() {
        var $this = $(this);
        var index = $this.data('index');
        var value = $this.val();
        var $targetSelector = $('.ghsales-target-selector[data-index="' + index + '"]');

        if (value === 'all') {
            // Hide target selector for "All Products"
            $targetSelector.hide();
        } else {
            // Show target selector and load correct options via AJAX
            $targetSelector.show();

            // Check if ghsalesAdmin is defined
            if (typeof ghsalesAdmin === 'undefined') {
                console.error('ghsalesAdmin object not found');
                $targetSelector.find('.ghsales-target-content').html('<p>Configuration error. Please refresh the page.</p>');
                return;
            }

            // Validate index before sending
            if (!index && index !== 0) {
                console.error('Index is undefined or empty:', index);
                $targetSelector.find('.ghsales-target-content').html('<p>Error: Invalid rule index.</p>');
                return;
            }

            // Show loading message
            $targetSelector.find('.ghsales-target-content').html('<p>Loading...</p>');

            // Load the correct selector via AJAX
            $.ajax({
                url: ghsalesAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'ghsales_load_target_selector',
                    applies_to: value,
                    index: index,
                    nonce: ghsalesAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $targetSelector.find('.ghsales-target-content').html(response.data.html);
                        // Re-initialize Select2 for the new dropdown
                        initializeSelect2();
                    } else {
                        var errorMsg = response.data && response.data.message ? response.data.message : 'Error loading options.';
                        $targetSelector.find('.ghsales-target-content').html('<p>' + errorMsg + '</p>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX selector load failed:', status, error);
                    $targetSelector.find('.ghsales-target-content').html('<p>Error loading options.</p>');
                }
            });
        }
    });

    // Handle "Discount Type" dropdown change
    $(document).on('change', 'select[name*="[rule_type]"]', function() {
        updateDiscountValueLabels();
    });

    // Initialize Select2 dropdowns
    function initializeSelect2() {
        if ($.fn.select2) {
            $('.ghsales-select2').select2({
                width: '100%'
            });
        }
    }

    // Update discount value labels based on rule type
    function updateDiscountValueLabels() {
        $('select[name*="[rule_type]"]').each(function() {
            var $select = $(this);
            var ruleType = $select.val();
            var index = $select.attr('name').match(/\[(\d+)\]/)[1];

            var $label = $('.ghsales-discount-value-label[data-index="' + index + '"]');
            var $input = $('.ghsales-discount-value-input[data-index="' + index + '"]');
            var $help = $('.ghsales-discount-value-help[data-index="' + index + '"]');

            // Update label, placeholder, help text, and input settings based on rule type
            switch(ruleType) {
                case 'percentage':
                    $label.text('Discount Percentage');
                    $input.attr('placeholder', 'e.g., 20 for 20% off');
                    $input.attr('step', '0.01'); // Allow decimals like 12.5%
                    $input.attr('max', '100');
                    $help.text('Enter the percentage discount (e.g., 20 for 20% off)');
                    break;
                case 'fixed':
                    $label.text('Discount Amount');
                    $input.attr('placeholder', 'e.g., 10.00 for €10 off');
                    $input.attr('step', '0.01'); // Allow decimals for currency
                    $input.removeAttr('max');
                    $help.text('Enter the fixed amount discount (e.g., 10.00 for €10 off)');
                    break;
                case 'bogo':
                    $label.text('Free Items Quantity');
                    $input.attr('placeholder', 'e.g., 1 for Buy 1 Get 1 Free');
                    $input.attr('step', '1'); // Whole numbers only
                    $input.removeAttr('max');
                    $help.text('Enter number of free items (e.g., 1 = Buy 1 Get 1 Free, 2 = Buy 1 Get 2 Free)');
                    break;
                case 'buy_x_get_y':
                    $label.text('Free Items (Y)');
                    $input.attr('placeholder', 'e.g., 1 for Buy 2 Get 1 Free');
                    $input.attr('step', '1'); // Whole numbers only
                    $input.removeAttr('max');
                    $help.text('Enter number of free items to give (e.g., 1 = Buy 2 Get 1 Free)');
                    break;
                case 'spend_threshold':
                    $label.text('Discount Value');
                    $input.attr('placeholder', 'e.g., 10 for 10% or €10 off');
                    $input.attr('step', '0.01'); // Allow decimals
                    $input.removeAttr('max');
                    $help.text('Enter discount percentage or fixed amount when spending above threshold');
                    break;
                default:
                    $label.text('Discount Value');
                    $input.attr('placeholder', 'e.g., 10');
                    $input.attr('step', '0.01');
                    $input.removeAttr('max');
                    $help.text('');
            }
        });
    }

});
