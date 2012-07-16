(function($) {
	$().ready(function() {
		$('#wp-redirect-check').click(function() {
			var redirect_url = $('#wp-redirect-url').val ();
			if (redirect_url) {
				$.post (
					WPRedirectAJAX.ajaxurl,
					{
						action: WPRedirectAJAX.action,
						url: redirect_url
					},
					function (response) {
						if (response.success) {
							$('#wp-redirect-url-warning').hide ();
							$('#wp-redirect-url-success').show ();
						}
						
						else {
							$('#wp-redirect-url-warning').show ();
							$('#wp-redirect-url-success').hide ();
							
						}
					}
				);
			}
			return false;
		});
		
		$('#wp-redirect-clear').click(function() {
			$('#wp-redirect-url').val ('');
			$('#wp-redirect-url-warning').hide ();
			$('#wp-redirect-url-success').hide ();
			return false;
		});
	});
})(jQuery);