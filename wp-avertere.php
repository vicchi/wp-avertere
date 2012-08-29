<?php
/*
Plugin Name: WP Avertere
Plugin URI: http://www.vicchi.org/codeage/wp-avertere/
Description: Set up and manage an HTTP 301/302 Redirect from the URL of any post type to another URL, either on your site or externally.
Version: 1.0.1
Author: Gary Gale
Author URI: http://www.garygale.com/
License: GPL2
Text Domain: wp-avertere
*/

define ('WPAVERTERE_PATH', plugin_dir_path (__FILE__));
define ('WPAVERTERE_URL', plugin_dir_url (__FILE__));

require_once (WPAVERTERE_PATH . '/wp-plugin-base/wp-plugin-base.php');

class WP_Avertere extends WP_PluginBase {
	const OPTIONS = 'wp_avertere_settings';
	const VERSION = '101';
	const DISPLAY_VERSION = 'v1.0.1';
	const NONCE_NAME = 'wp-avertere-nonce';
	const URL_KEY = 'wp-avertere-url';
	const TYPE_KEY = 'wp-avertere-type';
	const STATUS_KEY = 'wp-avertere-status';
	const AJAX_ACTION = 'wp_avertere_check_url';
	const REDIRECT_PERMANENT = 301;
	const REDIRECT_TEMPORARY = 302;
	
	/**
	 * Class constructor
	 */
	
	function __construct () {
		$this->hook ('plugins_loaded');
	}

	/**
	 * Queries the back-end database for WP Avertere settings and options.
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
		
		$this->hook ('init');
		$this->hook ('template_redirect');
		$this->hook ('page_link');
		$this->hook ('post_link');

		if (is_admin ()) {
			$this->hook ('admin_init');
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
					'installed' => 'on',
					'version' => self::VERSION
				); 
			update_option (self::OPTIONS, $settings);
		}
	}

	/**
	 * "init" action hook; called to initialise the plugin
	 */
	
	function init () {
		$lang_dir = basename (dirname (__FILE__)) . DIRECTORY_SEPARATOR . 'lang';
		load_plugin_textdomain ('wp-avertere', false, $lang_dir);
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
		$url = get_post_meta ($id, self::URL_KEY, true);
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
		$url = get_post_meta ($post->ID, self::URL_KEY, true);
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
		$url = get_post_meta ($post->ID, self::URL_KEY, true);
		$status = get_post_meta ($post->ID, self::TYPE_KEY, true);
		if (isset ($url) && !empty ($url) && is_singular ()) {
			wp_redirect ($url, $status);
			exit;
		}
	}

	/**
	 * WPRedirectAJAX action hook; called via AJAX to validate an entered redirect URL
	 */
	
	function ajax_check_url () {
		if ((!isset ($_POST['action']) || empty ($_POST['action'])) ||
					(!isset ($_POST['url']) || empty ($_POST['url']))) {
			exit;
		}

		$raw_url = $_POST['url'];
		$protocols = apply_filters ('wp_avertere_protocols', wp_allowed_protocols ());
		$escaped_url = esc_url ($raw_url, $protocols);
		$success = (isset ($escaped_url) && !empty ($escaped_url));
		$response = json_encode (array ('success' => $success));
		header ("Content-Type: application/json");
		echo $response;
		exit;
	}
	
	/**
	 * "admin_init" action hook; called after the admin panel is initialised.
	 */

	function admin_init () {
		$this->admin_upgrade ();
	}

	/**
	 * Called in response to the "admin_init" action hook; checks the current set of
	 * settings/options and upgrades them according to the new version of the plugin.
	 */
	
	function admin_upgrade () {
		$settings = null;
		$upgrade_settings = false;
		$current_plugin_version = null;
		
		/*
		 * Even if the plugin has only just been installed, the activation hook should have
		 * fired *before* the admin_init action so therefore we /should/ already have the
		 * plugin's configuration options defined in the database, but there's no harm in checking
		 * just to make sure ...
		 */

		$settings = $this->get_option ();

		/*
		 * Bale out early if there's no need to check for the need to upgrade the configuration
		 * settings ...
		 */

		if (is_array ($settings) &&
				isset ($settings['version']) &&
				$settings['version'] == self::VERSION) {
			return;
		}

		if (!is_array ($settings)) {
			/*
			 * Something odd is going on, so define the default set of config settings ...
			 */
			$this->add_settings ();
		}
		
		else {
			if (isset ($settings['version'])) {
				$current_plugin_version = $settings['version'];
			}
			else {
				$current_plugin_version = '000';
			}
			
			switch ($current_plugin_version) {
				case '000':
				case '100':
				case '101':
					$settings['version'] = self::VERSION;
					$upgrade_settings = true;

				default:
					break;
			}	// end-switch

			if ($upgrade_settings) {
				update_option (self::OPTIONS, $settings);
			}
		}
	}

	/**
	 * "admin_print_scripts" action hook; called to enqueue admin specific scripts.
	 */

	function admin_print_scripts () {
		global $pagenow;

		if ($pagenow == 'post.php' || $pagenow == 'post-new.php') {
			$deps = array ('jquery');
			wp_enqueue_script ('wp-avertere-admin-script', WPAVERTERE_URL . 'js/wp-avertere-admin.min.js', $deps);
			//wp_enqueue_script ('wp-avertere-admin-script', WPAVERTERE_URL . 'js/wp-avertere-admin.js', $deps);
			wp_localize_script ('wp-avertere-admin-script',
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
			wp_enqueue_style ('wp-avertere-admin-style', WPAVERTERE_URL . 'css/wp-avertere-admin.min.css');	
			//wp_enqueue_style ('wp-avertere-admin-style', WPAVERTERE_URL . 'css/wp-avertere-admin.css');	
		}
	}

	/**
	 * "add_meta_boxes" action hook; adds a meta box to define a redirect URL for each post
	 * type to the admin edit screens.
	 */

	function admin_add_meta_boxes () {
		$pts = get_post_types (array (), 'objects');
		foreach ($pts as $pt) {
			$id = sprintf ('wp-avertere-%s-meta', $pt->name);
			$title = sprintf (__('Redirect This %s', 'wp-avertere'), $pt->labels->singular_name);
			
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
		$title = sprintf (__('Redirect This %s To Another URL', 'wp-avertere'), $pto->labels->singular_name);
		$name = 'wp_avertere_url';
		$id = 'wp-avertere-url';
		$text = sprintf (__('Enter the URL which this %s should be redirected to each time it\'s accessed using the Single %s Template', 'wp-avertere'), $pto->labels->singular_name, $pto->labels->singular_name);
		$url = esc_attr (get_post_meta ($post->ID, self::URL_KEY, true));
		
		$content[] = wp_nonce_field (basename (__FILE__), self::NONCE_NAME);
		$content[] = '<p><strong>' . $title . '</strong><br />';
		$content[] = '<input class="widefat" type="text" name="' . $name . '" id="' . $id . '" value="' . $url . '" />';
		$content[] = '<small>' . $text . '</small></p>';
		
		$title = __('Check URL', 'wp-avertere');
		$id = 'wp-avertere-check';
		
		$content[] = '<p>';
		$content[] = __('A redirection URL which is not well formed will stop the redirection from working; it\'s a good idea to check this before saving or updating this post', 'wp-avertere');
		$content[] = '<br />';
		$content[] = '<button id="' . $id . '" class="button-secondary">' . $title . '</button>';
		$content[] = '</p>';

		$style = 'style="display: none;"';
		if (isset ($url) && !empty ($url)) {
			$protocols = apply_filters ('wp_avertere_protocols', wp_allowed_protocols ());
			$escaped_url = esc_url ($url, $protocols);
			if (!isset ($escaped_url) || empty ($escaped_url)) {
				$style = '';
			}
		}

		$content[] = '<div id="wp-avertere-url-warning" class="wp-avertere-warning" ' . $style . '>';
		$content[] = __('Oh no! Your redirect URL doesn\'t validate as well formed; your redirect probably won\'t work.', 'wp-avertere');
		$content[] = '</div>';
		$content[] = '<div id="wp-avertere-url-success" class="wp-avertere-success">';
		$content[] = __('Everything looks good. Your redirect URL is well formed; you should still check this URL actually exists though.', 'wp-avertere');
		$content[] = '</div>';
		
		$title = __('Clear Redirection URL', 'wp-avertere');
		$id = 'wp-avertere-clear';

		$content[] = '<p>';
		$content[] = sprintf (__('You can cancel an existing redirection by clearing the URL or click the %s button below', 'wp-avertere'), $title);
		$content[] = '<br />';
		$content[] = '<button id="' . $id . '" class="button-secondary">' . $title . '</button>';
		$content[] = '</p>';

		$title = __('Redirection Type', 'wp-avertere');
		$name = 'wp_avertere_type';
		$id = 'wp-avertere-type';
		$meta = esc_attr (get_post_meta ($post->ID, self::TYPE_KEY, true));
		if (!isset ($meta) || empty ($meta)) {
			$meta = self::REDIRECT_PERMANENT;
		}

		$content[] = '<p><strong>' . $title . '</strong><br />';
		$content[] = '<input type="radio" name="' . $name . '" id="' . $id . '" value="' . self::REDIRECT_PERMANENT .'" ' . checked ($meta, self::REDIRECT_PERMANENT, false) . '/>&nbsp' . __('Permanent (HTTP 301)', 'wp-avertere') . '<br />';
		$content[] = '<input type="radio" name="' . $name . '" id="' . $id . '" value="' . self::REDIRECT_TEMPORARY .'" ' . checked ($meta, self::REDIRECT_TEMPORARY, false) . '/>&nbsp' . __('Temporary (HTTP 302)', 'wp-avertere') . '<br />';
		$content[] = '<small>' . __('Specify the type of redirection; permanent or temporary', 'wp-redirect') . '</small></p>';
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

		$url_field = 'wp_avertere_url';
		$type_field = 'wp_avertere_type';

		$new_meta_url = (isset ($_POST[$url_field]) ? $_POST[$url_field] : '');
		$new_meta_type = (isset ($_POST[$type_field]) ? $_POST[$type_field] : '');
		$new_meta_type = ($new_meta_type == self::REDIRECT_PERMANENT || $new_meta_type == self::REDIRECT_TEMPORARY ? $new_meta_type : '');

		$meta_url = get_post_meta ($post_id, self::URL_KEY, true);
		$meta_type = get_post_meta ($post_id, self::TYPE_KEY, true);

		if ($new_meta_type && '' == $meta_type) {
			add_post_meta ($post_id, self::TYPE_KEY, $new_meta_type, true);
		}
		
		elseif ($new_meta_type && $new_meta_type != $meta_type) {
			update_post_meta ($post_id, self::TYPE_KEY, $new_meta_type);
		}
		
		if ($new_meta_url && '' == $meta_url) {
			add_post_meta ($post_id, self::URL_KEY, $new_meta_url, true);
		}
		
		elseif ($new_meta_url && $new_meta_url != $meta_url) {
			update_post_meta ($post_id, self::URL_KEY, $new_meta_url);
		}
		
		elseif ('' == $new_meta_url && $meta_url) {
			delete_post_meta ($post_id, self::URL_KEY, $meta_url);
			delete_post_meta ($post_id, self::TYPE_KEY);
		}
	}

}	// end-class WP_Avertere

$__wp_avertere_instance = new WP_Avertere;

?>