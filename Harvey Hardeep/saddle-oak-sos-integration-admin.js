(function( $ ) {
	'use strict';

	/**
	 * All of the code for your admin-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */


	/**
	 * Reveal the license.
	 */
	function connectSOSInventory() {
		$(document).on("click", "#edd-sl-connect-sos-inventory", function (e) {
			e.stopPropagation();
			e.preventDefault();
			let data = $('#frm_sos_inventory_sync').serialize();
			$.ajax({
				url: so_sos_ajax_object.ajaxurl,
				dataType: 'json',
				type: 'POST',
				data: data,
				success: function(response){
					if (response.success) {
						let sos_url = 'https://api.sosinventory.com/oauth2/authorize?response_type=code&client_id=' + response.data + '&redirect_uri=' + response.referer;
						window.location = sos_url;
					} else {
						$(".sos-inventory-sync-status")
							.removeClass("edd-sl-success edd-sl-error")
							.addClass("edd-sl-error")
							.html(response.data[0].message)
							.css("display", "block");
					}
					setTimeout(function () {
						$(".sos-inventory-sync-status").fadeOut();
					}, 15000);
				}
			});

		});
	}
	connectSOSInventory();


	$(document).on('click', '#edd-sl-sync-sos-inventory-items', function(e){
		e.stopPropagation();
		e.preventDefault();
		$.ajax({
			url: so_sos_ajax_object.ajaxurl,
			dataType: 'json',
			type: 'POST',
			data: {'action': 'sos_pull_sos_inventory_item'},
			success: function(response){
				if (response.success) {
					$(".sos-inventory-sync-status")
						.removeClass("edd-sl-success edd-sl-error")
						.addClass("edd-sl-success")
						.html(response.message)
						.css("display", "block");
				} else {
					$(".sos-inventory-sync-status")
						.removeClass("edd-sl-success edd-sl-error")
						.addClass("edd-sl-error")
						.html(response.message)
						.css("display", "block");
				}
				setTimeout(function () {
					$(".sos-inventory-sync-status").fadeOut();
				}, 15000);
			}
		});
	})

})( jQuery );
