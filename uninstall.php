<?php

if (defined('WP_UNINSTALL_PLUGIN')) {
	// Remove the general WP Avertere options
	delete_option ('wp_avertere_settings');
	global $wpdb;
	
	$sql = "
		SELECT $wpdb->posts.*
		FROM $wpdb->posts, $wpdb->postmeta
		WHERE $wpdb->posts.ID = $wpdb->postmeta.post_id
		AND $wpdb->postmeta.meta_key = 'wp-avertere-url'
		AND $wpdb->posts.post_date < NOW()
		ORDER BY $wpdb->posts.post_date DESC";

	$results = $wpdb->get_results ($sql, OBJECT);
	if ($results) {
		global $post;
		foreach ($results as $post) {
			setup_postdata ($post);
			delete_post_meta ($post->ID, 'wp-avertere-url');
			delete_post_meta ($post->ID, 'wp-avertere-type');
		}	// end-foreach
	}
}

else {
	exit ();
}

?>
