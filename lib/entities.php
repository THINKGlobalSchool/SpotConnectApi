<?php
/**
 * SpotConnect Entity Related API
 *
 * @package SpotConnect
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU Public License version 2
 * @author Jeff Tilson
 * @copyright THINK Global School 2010 - 2015
 * @link http://www.thinkglobalschool.org/
 * 
 */

/**
 * Get a list of albums owned by the given user
 */
function albums_list($user_guid = NULL) {
	// by default use logged in user
	if(empty($user_guid)) {
		$user = elgg_get_logged_in_user_entity();
		$user_guid = $user->guid;
	}

	// fetch albums
	$owner_albums = elgg_get_entities(array(
		'types' => 'object', 
		'subtypes' => 'album', 
		'container_guids' => $user_guid,
		'limit' => 0)
	);

	// push data to returned array
	$data = array();
	foreach ($owner_albums as $album) {
		$tmp_data['title'] = $album['title'];
		$tmp_data['guid'] = $album['guid'];
		$data[] = $tmp_data;
	}
	return $data;
}