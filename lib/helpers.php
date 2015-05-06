<?php
/**
 * SpotConnect Helper Library
 *
 * @package SpotConnect
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU Public License version 2
 * @author Jeff Tilson
 * @copyright THINK Global School 2010 - 2015
 * @link http://www.thinkglobalschool.org/
 * 
 */

function spotconnect_get_mobile_album($user_guid = NULL) {
	if (!$user_guid) {
		$user_guid = elgg_get_logged_in_user_guid();
	}

	$dbprefix = elgg_get_config('dbprefix');

	$options = array(
		'limit' => 1,
		'type' => 'object',
		'subtype' => 'album',
		'owner_guid' => $user_guid
	);

	$title = "Mobile Uploads";

	$options['joins'][] = "JOIN {$dbprefix}objects_entity as oe";
	$options['wheres'][] = "oe.guid = e.guid";
	$options['wheres'][] = "oe.title = '{$title}'";

	$albums = elgg_get_entities($options);

	// If we've got the uploads album already, return the guid
	if (count($albums)) {
		return $albums[0]->guid;
	} else {
		// Nope, create it
		$album = new TidypicsAlbum();
		$album->container_guid = $user_guid;
		$album->owner_guid = $user_guid;
		$album->access_id = ACCESS_LOGGED_IN;
		$album->title = $title;
		$album->new_album = FALSE;
		$album->save();

		// Return new guid
		return $album->guid;
	}
}