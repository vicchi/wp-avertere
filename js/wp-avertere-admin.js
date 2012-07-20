(function($) {
	$().ready(function() {
		$('#wp-avertere-check').click(function() {
			var redirect_url = $('#wp-avertere-url').val ();
			if (redirect_url) {
				$.post (
					WPRedirectAJAX.ajaxurl,
					{
						action: WPRedirectAJAX.action,
						url: redirect_url
					},
					function (response) {
						if (response.success) {
							$('#wp-avertere-url-warning').hide ();
							$('#wp-avertere-url-success').show ();
						}
						
						else {
							$('#wp-avertere-url-warning').show ();
							$('#wp-avertere-url-success').hide ();
							
						}
					}
				);
			}
			return false;
		});
		
		$('#wp-avertere-clear').click(function() {
			$('#wp-avertere-url').val ('');
			$('#wp-avertere-url-warning').hide ();
			$('#wp-avertere-url-success').hide ();
			return false;
		});
	});
})(jQuery);