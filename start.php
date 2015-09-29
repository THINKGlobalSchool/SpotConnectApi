<?php
/**
 * SpotConnect API
 *
 * @package SpotConnect
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU Public License version 2
 * @author Jeff Tilson
 * @copyright THINK Global School 2010 - 2015
 * @link http://www.thinkglobalschool.org/
 * 
 */

// Register hooks in REST context
if (elgg_get_context() == 'services') {
	// Register init events
	elgg_register_event_handler('init', 'system', 'spotconnect_init');
	elgg_register_event_handler('init', 'system', 'spotconnect_expose_functions', 501);
} else {
	elgg_register_event_handler('init', 'system', 'spotconnect_global_init');
}

/**
 *  Init Handler
 */
function spotconnect_init() {
	// Register libs
	$lib_path = elgg_get_plugins_path() . 'spotconnect/lib/';
	elgg_register_library('spotconnect:auth', $lib_path . 'auth.php');
	elgg_register_library('spotconnect:entities', $lib_path . 'entities.php');
	elgg_register_library('spotconnect:post', $lib_path . 'post.php');
	elgg_register_library('spotconnect:user', $lib_path . 'user.php');
	elgg_register_library('spotconnect:util', $lib_path . 'util.php');
	elgg_register_library('spotconnect:helpers', $lib_path . 'helpers.php');

	// Load libs
	elgg_load_library('spotconnect:auth');
	elgg_load_library('spotconnect:entities');
	elgg_load_library('spotconnect:post');
	elgg_load_library('spotconnect:user');
	elgg_load_library('spotconnect:util');
	elgg_load_library('spotconnect:helpers');

	// Override REST API init
	elgg_register_plugin_hook_handler('rest', 'init', 'spotconnect_rest_init_handler');	
}	

/**
 * Global init handler
 */
function spotconnect_global_init() {
	// Admin menu
	elgg_register_event_handler('pagesetup','system','spotconnect_adminmenu');
}

// Use custom authentication handlers for the api
function spotconnect_rest_init_handler() {
	// Admins can debug
	if (elgg_is_admin_logged_in()) {
		//register_pam_handler('pam_auth_session');
	}

	// user token can also be used for user authentication
	register_pam_handler('pam_auth_usertoken');
	
	// enable api key check
	register_pam_handler('api_auth_key', "sufficient", "api");

	// Returning true here cancels out all other pam handlers in lib/web_services
	return TRUE;
}

/**
 * Expose API functions
 */
function spotconnect_expose_functions() {
	// Get infinity token
	elgg_ws_expose_function("auth.get_user_pass_auth_token", "auth_get_infinity_token", array(
		'username' => array('type' => 'string'),
		'password' => array('type' => 'string'),
	), elgg_echo('auth.gettoken'),	'POST', TRUE, FALSE);

	// Get infinity token via google sign in
	elgg_ws_expose_function("auth.get_google_auth_token", "auth_google_get_infinity_token", array(
		'email' => array('type' => 'string')
	), elgg_echo('auth.gettoken'),	'POST', TRUE, FALSE);

	// Get user information
	elgg_ws_expose_function("user.get_profile", "user_get_profile", array(), "Get user profile", 'GET', TRUE, TRUE);

	// Ping
	elgg_ws_expose_function("util.ping", "util_ping", array(), "Ping the server", 'GET', TRUE, FALSE);

	// Allow wire posts
	elgg_ws_expose_function('thewire.post', 'wire_post', array(
		'text' => array(
			'type' => 'string'
		)
	), 'Post to the wire', 'POST', TRUE, TRUE);	

	// Allow bookmarks posts
	elgg_ws_expose_function('bookmark.post', 'bookmark_post', array(
		'title' => array(
			'type' => 'string',
			'required' => FALSE
		), 
		'url' => array(
			'type' => 'string'
		)
	), 'Post a bookmark', 'POST', TRUE, TRUE);	

	// Allow image posts
	elgg_ws_expose_function('photos.post', 'photos_post', array(
		'batch' => array(
			'type' => 'string',
			'required' => TRUE
		),
		'album' => array(
			'type' => 'string',
			'required' => FALSE
		),
		'description' => array(
			'type' => 'string',
			'required' => FALSE
		)
	), 'Post photos', 'POST', TRUE, TRUE);	

	// Finalize photo posting
	elgg_ws_expose_function('photos.finalize.post', 'photos_finalize_post', array(
		'batch' => array(
			'type' => 'string',
			'required' => TRUE
		),
		'album' => array(
			'type' => 'string',
			'required' => FALSE
		)
	), 'Finalize photo posts', 'POST', TRUE, TRUE);	

	elgg_ws_expose_function('albums.list', 'albums_list', array(
		'user_guid' => array(
			'type' => 'int',
			'required' => FALSE
		)
	), 'List user albums', 'GET', TRUE, FALSE);
}

/**
 * Sets up API admin menu. Triggered on pagesetup.
 */
function spotconnect_adminmenu() {
	if (elgg_in_context('admin')) {
		elgg_register_admin_menu_item('administer', 'apikey', 'spotconnect');
	}
}