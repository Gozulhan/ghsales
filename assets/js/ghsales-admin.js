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
                        $targetSelector.find('.ghsales-target-content').html('<p>Error loading options.</p>');
                    }
                },
                error: function() {
                    $targetSelector.find('.ghsales-target-content').html('<p>Error loading options.</p>');
                }
            });
        }
    });

    // Initialize Select2 dropdowns
    function initializeSelect2() {
        if ($.fn.select2) {
            $('.ghsales-select2').select2({
                width: '100%'
            });
        }
    }

});
