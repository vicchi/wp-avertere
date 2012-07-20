=== WP Avertere ===
Contributors: vicchi
Donate link: http://www.vicchi.org/codeage/donate
Tags: wp-avertere, redirect, redirection, http, 301, 302, temporary, permanent, post, page
Requires at least: 3.4
Tested up to: 3.4.1
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Set up and manage an HTTP 301/302 Redirect from the URL of any post type to another URL, either on your site or externally.

== Description ==

This plugin allows you to easily set up redirections from the URL of any post, page or other post type on your WordPress site to another URL, either on your site or external to your site. Redirections can either be permanent (HTTP 301) or temporary (HTTP 302) and can easily be changed or deleted entirely.

Settings and options include:

1. The URL you want to redirect to.
1. The type of redirection, permanent or temporary
1. Validation of the redirect URL to ensure it is well formed.

In addition to setting up a redirect, the plugin replaces the original post's or page's permalink with the redirected permalink or external URL; when you hover your mouse pointer over a redirected permalink you will see the new permalink or external URL not the original.

Once installed and activated, the plugin adds a *Redirect This Post/Page/etc* meta box to the admin *Edit Post/Page*. Simply create a new post, or edit an existing one, add the URL you want to redirect to (copying and pasting is a good idea here to ensure there's no typing errors), choose whether the redirection is permanent or temporary, click on the *Check URL* button to ensure your URL is well formed and save the post. You're done.

While the main use of the plugin is to redirect posts and pages, you can also use it to:

1. Convert a post to a page; useful for when the post needs to be kept updated regularly and is more suited to be a page on your site.
1. Add a menu bar link to an external site; you can create a new blank page as a menu bar link and then redirect that page to the external URL with no need to edit any code in your theme's `functions.php`.
1. Create a shortcut category or tag archive link; you can create a new blank page, such as `/plugins` and then redirect that page to `/tags/plugins`.

== Installation ==

1. You can install WP Avertere automatically from the WordPress admin panel. From the Dashboard, navigate to the *Plugins / Add New* page and search for *"WP Avertere"* and click on the *"Install Now"* link.
1. Or you can install WP Avertere manually. Download the plugin Zip archive and uncompress it. Copy or upload the `wp-avertere` folder to the `wp-content/plugins` folder on your web server.
1. Activate the plugin. From the Dashboard, navigate to Plugins and click on the *"Activate"* link under the entry for WP Avertere.

== Frequently Asked Questions ==

= How do I get help or support for this plugin? =

In short, very easily. But before you read any further, take a look at [Asking For WordPress Plugin Help And Support Without Tears](http://www.vicchi.org/2012/03/31/asking-for-wordpress-plugin-help-and-support-without-tears/) before firing off a question. In order of preference, you can ask a question on the [WordPress support forum](http://wordpress.org/tags/wp-avertere?forum_id=10); this is by far the best way so that other users can follow the conversation. You can ask me a question on Twitter; I'm [@vicchi](http://twitter.com/vicchi). Or you can drop me an email instead. I can't promise to answer your question but I do promise to answer and do my best to help.

= Is there a web site for this plugin? =

Absolutely. Go to the [WP Avertere home page](http://www.vicchi.org/codeage/wp-avertere/) for the latest information. There's also the official [WordPress plugin repository page](http://wordpress.org/extend/plugins/wp-avertere/) and the [source for the plugin is on GitHub](http://vicchi.github.com/wp-avertere/) as well.

= I've just installed this plugin; where's the admin Settings & Options page for the plugin? =

There isn't one! All the settings and options for the plugin are in the *Redirect This Post* meta box that you'll find on the *Edit Post* page.

= My redirect URL validates as well formed but the URL now shows a 404/Page Not Found when I click on it. What's going on? =

It's probably just what your browser is telling you. WP Avertere can set up the redirection for you and can check that your redirection URL is well formed. But what it can't do is actually check that the redirection URL points to a web page that actually exists. Now might be a good time to copy and paste your redirection URL into your browser and see if that shows a 404/Page Not Found error. If it does, then the page wasn't found. But if it was found, then you might have found a bug, so get in touch and let me know about it.

= My redirect URL looks well formed to me, but the plugin tells me it's not. What's going on here? =

WordPress defines a set of acceptable URL protocols which are returned by the `wp_allowed_protocols` API call. WP Avertere uses the `esc_url` API call, which acts on this set of allowed protocols to determine which URLs are allowed and which are not. At the time of writing, the set is defined as `http(s)`, `ftp(s)`, `mailto`, `news`, `irc`, `gopher`, `nntp`, `feed` and `telnet`. If your redirect URL is not for one of these allowed protocols the redirection will not be set up correctly. You can add to, or even limit, the list of allowed protocols via the `wp_avertere_protocols` filter that the plugin provides. See the *Filter Support And Usage* section for more information on this.

= My redirect isn't happening. Why not? =

Check that the redirection URL is well formed by clicking on the *Check URL* button on the *Redirect This Post* meta box. If the URL isn't well formed and you save the post anyway then the redirection will be ignored. Check that the URL actually exists in another browser window and behaves as you'd expect. If the redirect still doesn't work, now would be a good time to get in touch.

= What's the difference between a permanent and a temporary redirection? =

A permanent redirection means that the current and all future requests for the original URL should be directed to the new, redirected, URL. A temporary redirection means that the current request for the original URL should be directed to the new, redirected URL but subsequent requests can continue to use the original URL.

It's important to note that both permanent and temporary redirects can, and do, cease and the act of cancelling (or in other words, removing) a redirection, be it permanent or temporary, means that the behaviour for a URL reverts to how it was before any redirection was put in place. See the next FAQ for how to cancel a redirection.

= I don't want a redirection any more; how do I cancel it? =

In short, very easily. Edit the post that the redirection is set up on and then either delete the redirection URL or, even easier, click on the *Clear Redirection URL* button. Then just save the post and your redirection is gone.

= Why don't I just use the REFRESH HTML meta tag in my post instead? =

There's nothing wrong with using the `REFRESH` HTML meta tag to redirect to another URL but it's not as easy or efficient as using the plugin. Here's why. The `REFRESH` meta tag lives in a page's header section. You not only need to inject this into the page (you could use the `wp_head` action hook) but you need to wait for the entire page to load before your browser will take note of and act on the `REFRESH` meta tag. WP Avertere hooks into the WordPress `template_redirect` hook and issues an HTTP `Location` header on your behalf; this means that the decision to redirect and the act of actually redirecting takes place before the page even loads, which is faster and more efficient.

= Wait a moment. HTTP 302 is Found not Temporary Redirect. Why aren't you using HTTP 307 Temporary Redirect instead? =

This is a classic case of *industry practice contradicting the standard* (according to [Wikipedia](http://en.wikipedia.org/wiki/HTTP_302)). The HTTP/1.0 standard defined HTTP 301 as *Moved Permanently* and HTTP 302 as *Temporary Redirect*. With the introduction of HTTP/1.1, HTTP 302 changed to *Found* and added HTTP 307 *Temporary Redirect*. But the majority of web services still use HTTP/1.1 302 as the original intent of the HTTP/1.0 meaning.

= My original post had comments; I can't see them now that I've set up a redirect. Where are they? =

The current version of the plugin doesn't touch comments but after a redirect is set up they won't be visible due to the inherent nature of a redirect. The next version of the plugin will support the ability to copy comments from the source URL to the redirected target URL as long as that URL is on the same WordPress powered site.

= WP Avertere isn't available in my language; can I submit a translation? =

WordPress and this plugin use the gettext tools to support internationalisation. The source file containing each string that needs to be translated ships with the plugin in `wp-avertere/lang/src/wp-biographia.po`. See the [I18n for WordPress Developers](http://codex.wordpress.org/I18n_for_WordPress_Developers) page for more information or get in touch for help and hand-holding.

= I want to amend/hack/augment this plugin; can I do this? =

Totally; this plugin is licensed under the GNU General Public License v2 (GPLV2). See http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt for the full license terms.

= Where does the name WP Avertere come from? =

WP Avertere is named after the latin for "divert", meaning to turn aside from a path or course.

== Screenshots ==

1. Edit Post *Redirect This Post* meta box; well formed and valid URL.
1. Edit Post *Redirect This Post* meta box; unsupported format URL.

== Changelog ==

The current version is 1.0.0 (2012.07.17)

= 1.0.0 =
* Released 2012.07.17
* First version of WP Avertere released.

== Upgrade Notice ==

= 1.0.0 =
* This is the first version of WP Avertere.

== Filter Support And Usage ==

WP Avertere supports a single filter, `wp_avertere_protocols` that allows you to change the set of acceptable URL protocols that WordPress and the plugin permits.

*Example:* Add support for GitHub repositories to the plugin.

`add_filter ('wp_avertere_protocols', 'add_github_protocol');

function add_github_protocol ($protocols) {
	// protocols = array ('name', 'name', ...)
	$protocols[] = 'git';
	
	return $protocols;
}`
