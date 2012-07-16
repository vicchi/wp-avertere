<?php
/*
Plugin Name: WP Redirect
Plugin URI: http://www.vicchi.org/codeage/wp-redirect/
Description: Set up an HTTP 301 Redirect from the URL of any post type to another URL, either on your site or external.
Version: 1.0
Author: Gary Gale
Author URI: http://www.garygale.com/
License: GPL2
Text Domain: wp-redirect
*/

define ('WPREDIRECT_PATH', plugin_dir_path (__FILE__));
define ('WPREDIRECT_URL', plugin_dir_url (__FILE__));

require_once (WPREDIRECT_PATH . '/wp-plugin-base/wp-plugin-base.php');

class WP_Redirect extends WP_PluginBase {
	const OPTIONS = 'wp_redirect_settings';
	const VERSION = '100';
	const DISPLAY_VERSION = 'v1.0.0';
	const NONCE_NAME = 'wp-redirect-nonce';
	const URL_KEY = 'wp-redirect-url';
	const STATUS_KEY = 'wp-redirect-status';
	const AJAX_ACTION = 'wp_redirect_check_url';
	const REDIRECT_PERMANENT = 301;
	const REDIRECT_TEMPORARY = 302;
	
	/**
	 * Class constructor
	 */
	
	function __construct () {
		$this->hook ('plugins_loaded');
	}

	/**
	 * Queries the back-end database for WP Redirect settings and options.
	 *
	 * @param string $key Optional settings/options key name; if specified only the value
	 * for the key will be returned, if the key exists, if omitted all settings/options
	 * will be returned.
	 * @return mixed If $key is specified, a string containing the key's settings/option 
	 * value is returned, if the key exists, else an empty string is returned. If $key is
	 * omitted, an array containing all settings/options will be returned.
	 */
	
	function get_option () {
		$num_args = func_num_args ();
		$options = get_option (self::OPTIONS);

		if ($num_args > 0) {
			$args = func_get_args ();
			$key = $args[0];
			$value = "";
			if (isset ($options[$key])) {
				$value = $options[$key];
			}
			return $value;
		}
		
		else {
			return $options;
		}
	}

	/**
	 * Adds/updates a settings/option key and value in the back-end database.
	 *
	 * @param string key Settings/option key to be created/updated.
	 * @param string value Value to be associated with the specified settings/option key
	 */
	
	function set_option ($key , $value) {
		$options = get_option (self::OPTIONS);
		$options[$key] = $value;
		update_option (self::OPTIONS , $options);
	}
	
	/**
	 * "plugins_loaded" action hook; called after all active plugins and pluggable functions
	 * are loaded.
	 *
	 * Adds front-end display actions and admin actions.
	 */

	function plugins_loaded () {
		register_activation_hook (__FILE__, array ($this, 'add_settings'));
		
		$this->hook ('template_redirect');
		$this->hook ('page_link');
		$this->hook ('post_link');
		if (is_admin ()) {
			$this->hook ('admin_print_scripts');
			$this->hook ('admin_print_styles');
			$this->hook ('add_meta_boxes', 'admin_add_meta_boxes');
			$this->hook ('save_post', 'admin_save_meta_boxes');
			$this->hook ('wp_ajax_' . self::AJAX_ACTION, 'ajax_check_url');
		}
	}
	
	/**
	 * plugin activation / "activate_pluginname" action hook; called when the plugin is
	 * first activated.
	 *
	 * Defines and sets up the default settings and options for the plugin.
	 */
	
	function add_settings () {
		$settings = $this->get_option ();
		
		if (!is_array ($settings)) {
			$settings = array (
					'wp_redirect_installed' => 'on',
					'wp_redirect_version' => self::VERSION
				); 
			update_option (self::OPTIONS, $settings);
		}
	}

	/**
	 * "page_link" action hook; filters the calculated page URL by the get_page_link
	 * API call.
	 *
	 * @param string link Current page URL
	 * @param integer id Current page ID
	 * @return string Filtered URL
	 */

	function page_link  ($link, $id) {
		$key = 'wp_redirect';
		$url = get_post_meta ($id, $key, true);
		if (isset ($url) && !empty ($url)) {
			$link = $url;
		}
		return $link;
	}
	
	/**
	 * "post_link" action hook; filters the calculated post permalink by the get_permalink,
	 * the_permalink, post_permalink, previous_post_link and next_post_link API calls.
	 *
	 * @param string link Current post URL
	 * @param object post Current post object
	 * @return string Filtered URL
	 */

	function post_link ($link, $post) {
		$key = 'wp_redirect';
		$url = get_post_meta ($post->ID, $key, true);
		if (isset ($url) && !empty ($url)) {
			$link = $url;
		}
		return $link;
	}
	
	/**
	 * "template_redirect" action hook; runs before the determination of the template file
	 * to be used to display the requested page.
	 */
	
	function template_redirect () {
		global $post;
		$key = 'wp_redirect_url';
		$url = get_post_meta ($post->ID, $key, true);
		$key = 'wp_redirect_type';
		$status = get_post_meta ($post->ID, $key, true);
		if (isset ($url) && !empty ($url) && is_singular ()) {
			wp_redirect ($url, $status);
			exit;
		}
	}

	function ajax_check_url () {
		if ((!isset ($_POST['action']) || empty ($_POST['action'])) ||
					(!isset ($_POST['url']) || empty ($_POST['url']))) {
			exit;
		}

		$raw_url = $_POST['url'];
		$protocols = apply_filters ('wp_redirect_protocols', wp_allowed_protocols ());
		$escaped_url = esc_url ($raw_url, $protocols);
		$success = (isset ($escaped_url) && !empty ($escaped_url));
		$response = json_encode (array ('success' => $success));
		header ("Content-Type: application/json");
		echo $response;
		exit;
	}
	
	/**
	 * "admin_print_scripts" action hook; called to enqueue admin specific scripts.
	 */

	function admin_print_scripts () {
		global $pagenow;

		if ($pagenow == 'post.php' || $pagenow == 'post-new.php') {
			$deps = array ('jquery');
			//wp_enqueue_script ('wp-redirect-admin-script', WPREDIRECT_URL . 'js/wp-redirect-admin.min.js');
			wp_enqueue_script ('wp-redirect-admin-script', WPREDIRECT_URL . 'js/wp-redirect-admin.js', $deps);
			wp_localize_script ('wp-redirect-admin-script',
				'WPRedirectAJAX',
				array (
					'ajaxurl' => admin_url ('admin-ajax.php'),
					'action' => self::AJAX_ACTION
					));
		}
	}
	
	/**
	 * "admin_print_styles" action hook; called to enqueue admin specific CSS.
	 */

	function admin_print_styles () {
		global $pagenow;

		if ($pagenow == 'post.php' || $pagenow == 'post-new.php') {
			//wp_enqueue_style ('wp-redirect-admin-style', WPREDIRECT_URL . 'css/wp-redirect-admin.min.css');	
			wp_enqueue_style ('wp-redirect-admin-style', WPREDIRECT_URL . 'css/wp-redirect-admin.css');	
		}
	}

	/**
	 * "add_meta_boxes" action hook; adds a meta box to define a redirect URL for each post
	 * type to the admin edit screens.
	 */

	function admin_add_meta_boxes () {
		$pts = get_post_types (array (), 'objects');
		foreach ($pts as $pt) {
			$id = sprintf ('wp-redirect-%s-meta', $pt->name);
			$title = sprintf (__('Redirect This %s', 'wp-redirect'), $pt->labels->singular_name);
			
			add_meta_box ($id, $title, array ($this, 'admin_display_meta_box'), $pt->name);
		}	// end-foreach
	}
	
	/**
	 * "add_meta_box" callback; adds a meta box to define a redirect URL for a specific post
	 * to the admin edit screens.
	 *
	 * @param object post WordPress post object
	 */
	
	function admin_display_meta_box ($post) {
		$content = array ();
		$pt = get_post_type ();
		$pto = get_post_type_object ($pt);
		$title = sprintf (__('Redirect This %s To Another URL', 'wp-redirect'), $pto->labels->singular_name);
		$name = 'wp_redirect_url';
		$id = 'wp-redirect-url';
		$text = sprintf (__('Enter the URL which this %s should be redirected to each time it\'s accessed using the Single %s Template', 'wp-redirect'), $pto->labels->singular_name, $pto->labels->singular_name);
		$url = esc_attr (get_post_meta ($post->ID, $name, true));
		
		$content[] = wp_nonce_field (basename (__FILE__), self::NONCE_NAME);
		$content[] = '<p><strong>' . $title . '</strong><br />';
		$content[] = '<input class="widefat" type="text" name="' . $name . '" id="' . $id . '" value="' . $url . '" />';
		$content[] = '<small>' . $text . '</small></p>';
		
		$title = __('Check URL');
		$id = 'wp-redirect-check';
		
		$content[] = '<p>';
		$content[] = __('A redirection URL which is not well formed will stop the redirection from working; it\'s a good idea to check this before saving or updating this post', 'wp-redirect');
		$content[] = '<br />';
		$content[] = '<button id="' . $id . '" class="button-secondary">' . $title . '</button>';
		$content[] = '</p>';

		$style = 'style="display: none;"';
		if (isset ($url) && !empty ($url)) {
			$protocols = apply_filters ('wp_redirect_protocols', wp_allowed_protocols ());
			$escaped_url = esc_url ($url, $protocols);
			if (!isset ($escaped_url) || empty ($escaped_url)) {
				$style = '';
			}
		}

		$title = __('Clear Redirection URL', 'wp-redirect');
		$id = 'wp-redirect-clear';

		$content[] = '<p>';
		$content[] = sprintf (__('You can cancel an existing redirection by clearing the URL or click the %s button below', 'wp-redirect'), $title);
		$content[] = '<br />';
		$content[] = '<button id="' . $id . '" class="button-secondary">' . $title . '</button>';
		$content[] = '</p>';

		$content[] = '<div id="wp-redirect-url-warning" class="wp-redirect-warning" ' . $style . '>';
		$content[] = __('Oh no! Your redirect URL doesn\'t validate as well formed; your redirect probably won\'t work.');
		$content[] = '</div>';
		$content[] = '<div id="wp-redirect-url-success" class="wp-redirect-success">';
		$content[] = __('Everything looks good. Your redirect URL is well formed; you should still check this URL actually exists though.');
		$content[] = '</div>';
		
		$title = __('Redirection Type', 'wp-redirect');
		$name = 'wp_redirect_type';
		$id = 'wp-redirect-type';
		$meta = esc_attr (get_post_meta ($post->ID, $name, true));
		if (!isset ($meta) || empty ($meta)) {
			$meta = self::REDIRECT_PERMANENT;
		}

		$content[] = '<p><strong>' . $title . '</strong><br />';
		$content[] = '<input type="radio" name="' . $name . '" id="' . $id . '" value="' . self::REDIRECT_PERMANENT .'" ' . checked ($meta, self::REDIRECT_PERMANENT, false) . '/>&nbsp' . __('Permanent (HTTP 301)', 'wp_redirect') . '<br />';
		$content[] = '<input type="radio" name="' . $name . '" id="' . $id . '" value="' . self::REDIRECT_TEMPORARY .'" ' . checked ($meta, self::REDIRECT_TEMPORARY, false) . '/>&nbsp' . __('Temporary (HTTP 302)', 'wp_redirect') . '<br />';
		$content[] = '<small>' . __('Specify the type of redirection; permanent or temporary', 'wp_redirect') . '</small></p>';
		echo implode (PHP_EOL, $content);
	}

	/**
	 * "save_post" action hook; save the post/page/custom post redirect URL (if present)
	 *
	 * @param integer post_id Post ID for the current post
	 * @param object post WordPress post object
	 */

	function admin_save_meta_boxes ($post_id, $post) {
		// CODE HEALTH WARNING
		// the "save_post" hook is a misnomer; it's not called just on the saving of a
		// post, but during initial post creation, during autosave, during the creation of
		// a revision, in fact during anything that changes the disposition of the post.
		// Which is why there's a whole lot of checking and validation going on here before
		// we even look at the custom meta box options.
		
		if (defined ('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
			return $post_id; 
		}

		if ($parent_id = wp_is_post_revision ($post_id)) {
			return $post_id;
		}
		
		
		$post_type = get_post_type_object ($post->post_type);
		if (!current_user_can ($post_type->cap->edit_post, $post_id)) {
			return $post_id;
		}
		
		switch ($post->post_status) {
			case 'draft':
			case 'pending':
			case 'publish':
				break;
				
			default:
				return $post_id;
		}

		if (!isset ($_POST[self::NONCE_NAME]) || !check_admin_referer (basename (__FILE__), self::NONCE_NAME)) {
			return $post_id;
		}

		$url_key = 'wp_redirect_url';
		$type_key = 'wp_redirect_type';

		$new_meta_url = (isset ($_POST[$url_key]) ? $_POST[$url_key] : '');
		$new_meta_type = (isset ($_POST[$type_key]) ? $_POST[$type_key] : '');
		$new_meta_type = ($new_meta_type == self::REDIRECT_PERMANENT || $new_meta_type == self::REDIRECT_TEMPORARY ? $new_meta_type : '');

		$meta_url = get_post_meta ($post_id, $url_key, true);
		$meta_type = get_post_meta ($post_id, $type_key, true);

		if ($new_meta_type && '' == $meta_type) {
			add_post_meta ($post_id, $type_key, $new_meta_type, true);
		}
		
		elseif ($new_meta_type && $new_meta_type != $meta_type) {
			update_post_meta ($post_id, $type_key, $new_meta_type);
		}
		
		if ($new_meta_url && '' == $meta_url) {
			add_post_meta ($post_id, $url_key, $new_meta_url, true);
		}
		
		elseif ($new_meta_url && $new_meta_url != $meta_url) {
			update_post_meta ($post_id, $url_key, $new_meta_url);
		}
		
		elseif ('' == $new_meta_url && $meta_url) {
			delete_post_meta ($post_id, $url_key, $meta_url);
			delete_post_meta ($post_id, $type_key);
		}
	}

}	// end-class WP_Redirect

$__wp_redirect_instance = new WP_Redirect;

?>