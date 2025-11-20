/**
 * GHSales Analytics Dashboard JavaScript
 *
 * Handles tab switching and live activity feed auto-refresh.
 *
 * @package GHSales
 * @since 1.1.0
 */

(function($) {
	'use strict';

	/**
	 * Initialize analytics dashboard functionality
	 */
	function initAnalyticsDashboard() {
		console.log('GH Sales Analytics Dashboard initialized');

		// Initialize tabs
		initTabs();

		// Initialize live feed auto-refresh
		initLiveFeedRefresh();
	}

	/**
	 * Initialize tab switching functionality
	 */
	function initTabs() {
		$('.ghsales-tab').on('click', function(e) {
			e.preventDefault();

			const tabId = $(this).data('tab');

			// Remove active class from all tabs and tab contents
			$('.ghsales-tab').removeClass('active');
			$('.ghsales-tab-content').removeClass('active');

			// Add active class to clicked tab and corresponding content
			$(this).addClass('active');
			$('#tab-' + tabId).addClass('active');

			console.log('Switched to tab:', tabId);
		});
	}

	/**
	 * Initialize live activity feed auto-refresh
	 * Refreshes every 30 seconds
	 */
	function initLiveFeedRefresh() {
		// Don't auto-refresh if activity feed doesn't exist
		if ($('#ghsales-activity-feed').length === 0) {
			return;
		}

		// Refresh every 30 seconds
		setInterval(refreshActivityFeed, 30000);

		console.log('Live activity feed auto-refresh enabled (30s interval)');
	}

	/**
	 * Refresh activity feed via AJAX
	 */
	function refreshActivityFeed() {
		const $feed = $('#ghsales-activity-feed');

		// Add loading indicator
		$feed.addClass('ghsales-loading');

		$.ajax({
			url: ghsalesAnalytics.ajaxUrl,
			type: 'POST',
			data: {
				action: 'ghsales_get_live_feed',
				nonce: ghsalesAnalytics.nonce
			},
			success: function(response) {
				if (response.success && response.data) {
					updateActivityFeed(response.data);
					console.log('Activity feed refreshed:', response.data.length, 'activities');
				} else {
					console.error('Failed to refresh activity feed:', response);
				}
			},
			error: function(xhr, status, error) {
				console.error('AJAX error refreshing activity feed:', error);
			},
			complete: function() {
				$feed.removeClass('ghsales-loading');
			}
		});
	}

	/**
	 * Update activity feed HTML
	 *
	 * @param {Array} activities Array of activity objects
	 */
	function updateActivityFeed(activities) {
		const $feed = $('#ghsales-activity-feed');

		if (!activities || activities.length === 0) {
			$feed.html('<p class="ghsales-no-activity">No recent activity.</p>');
			return;
		}

		let html = '';

		activities.forEach(function(activity) {
			const icon = getActivityIcon(activity.type);

			html += `
				<div class="ghsales-activity-entry">
					<span class="ghsales-activity-icon dashicons dashicons-${icon}"></span>
					<div class="ghsales-activity-content">
						<p>${escapeHtml(activity.message)}</p>
						<span class="ghsales-activity-time">${escapeHtml(activity.time_ago)} ago</span>
					</div>
				</div>
			`;
		});

		$feed.html(html);
	}

	/**
	 * Get dashicon name for activity type
	 *
	 * @param {string} type Activity type
	 * @return {string} Dashicon name
	 */
	function getActivityIcon(type) {
		const icons = {
			'view': 'visibility',
			'add_to_cart': 'cart',
			'purchase': 'yes-alt',
			'search': 'search',
			'category_view': 'category'
		};

		return icons[type] || 'admin-generic';
	}

	/**
	 * Escape HTML to prevent XSS
	 *
	 * @param {string} text Text to escape
	 * @return {string} Escaped text
	 */
	function escapeHtml(text) {
		const map = {
			'&': '&amp;',
			'<': '&lt;',
			'>': '&gt;',
			'"': '&quot;',
			"'": '&#039;'
		};

		return text.replace(/[&<>"']/g, function(m) { return map[m]; });
	}

	// Initialize when DOM is ready
	$(document).ready(function() {
		initAnalyticsDashboard();
	});

})(jQuery);
