<?php

if (defined('WP_UNINSTALL_PLUGIN')) {
	// Remove the general WP Redirect options
	delete_option ('wp_biographia_settings');
}

else {
	exit ();
}

?>
