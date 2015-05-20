<?php
/**
 * SpotConnect Post Library
 *
 * @package SpotConnect
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU Public License version 2
 * @author Jeff Tilson
 * @copyright THINK Global School 2010 - 2015
 * @link http://www.thinkglobalschool.org/
 * 
 */

/**
 * Posts given message to the wire.
 *
 * @param string $text text to be posted
 * @return bool operation success
 */
function wire_post($text) {
	// access level
	$access = ACCESS_PUBLIC;

	// Elgg 1.8 requires the user_id now aswell
	$user_guid = elgg_get_logged_in_user_guid();

	$text = strip_tags($text);

    $guid = tgswire_save_post($text, $user_guid, $access, 0, "spotconnect");
   
    $entity = get_entity($guid);

    return $guid;
}

/**
 * Post a bookmark
 */
function bookmark_post($title, $address) {
	$access = ACCESS_LOGGED_IN;

	if (empty($title)) {
		$title = $address;
	}

	$user_guid = elgg_get_logged_in_user_guid();

	$title = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');

	// don't use elgg_normalize_url() because we don't want
	// relative links resolved to this site.
	if ($address && !preg_match("#^((ht|f)tps?:)?//#i", $address)) {
		$address = "http://$address";
	}

	// see https://bugs.php.net/bug.php?id=51192
	$php_5_2_13_and_below = version_compare(PHP_VERSION, '5.2.14', '<');
	$php_5_3_0_to_5_3_2 = version_compare(PHP_VERSION, '5.3.0', '>=') &&
			version_compare(PHP_VERSION, '5.3.3', '<');

	$validated = false;
	if ($php_5_2_13_and_below || $php_5_3_0_to_5_3_2) {
		$tmp_address = str_replace("-", "", $address);
		$validated = filter_var($tmp_address, FILTER_VALIDATE_URL);
	} else {
		$validated = filter_var($address, FILTER_VALIDATE_URL);
	}

	if (!$validated) {
		register_error(elgg_echo('bookmarks:save:failed'));
		return FALSE;
	}

	// Check for hash tags in title
	$tags = spotconnect_parse_tags($title);

	$bookmark = new ElggObject;
	$bookmark->subtype = "bookmarks";
	$bookmark->owner_guid = $user_guid;
	$bookmark->container_guid = $user_guid;
	$bookmark->title = $title;
	$bookmark->address = $address;
	$bookmark->access_id = $access;
	$bookmark->tags = $tags;

	if ($bookmark->save()) {
		add_to_river('river/object/bookmarks/create','create', elgg_get_logged_in_user_guid(), $bookmark->getGUID());
	} else {
		register_error(elgg_echo('bookmarks:save:failed'));
		return FALSE;
	}

	return $bookmark->guid;
}

/**
 * Post Photos
 */
function photos_post($batch, $album_guid = FALSE, $description = NULL) {
	if (!$album_guid) {
		$album_guid = spotconnect_get_mobile_album();
	}

	elgg_load_library('tidypics:upload');

	$album = get_entity($album_guid);

	//Make sure we can write to the container (for groups)
	if (!$album->getContainerEntity()->canWriteToContainer(elgg_get_logged_in_user_guid())) {
		throw new APIException(elgg_echo('tidypics:nopermission'));
	}

	$errors = array();
	$messages = array();

	// probably POST limit exceeded
	if (empty($_FILES)) {
		throw new APIException(elgg_echo('tidypics:exceedpostlimit'));
	}
	
	$file = $_FILES['file'];

	// If theres no error, remove the error key (Tidypics isn't handling this well)
	if ($file['error'] === 0) {
		unset($file['error']);
	}

	// Fix odd casing coming into the API
	$file['type'] = strtolower($file['type']);

	$mime = tp_upload_get_mimetype($file['name']);

	// Fix mimetype mismatch (going to let tidypics determine it)
	if ($mime !== $file['type']) {
		$file['type'] = $mime;
	}

	if ($mime == 'unknown') {
		throw new APIException(elgg_echo('tidypics:not_image', array($file['name'])));
	}

	$image = new TidypicsImage();
	$image->container_guid = $album->guid;
	$image->access_id = $album->access_id;
	$image->setMimeType($mime);
	$image->batch = $batch;
	$image->spot_connect_upload = 1; // Set a flag to identify these images later on if we have too

	if ($description) {
		$image->description = $description;
		$image->tags = spotconnect_parse_tags($description);
	}

	try {
		$image->save($file);

		if (elgg_get_plugin_setting('img_river_view', 'tidypics') === "all") {
			add_to_river('river/object/image/create', 'create', $image->getOwnerGUID(), $image->getGUID());
		}

		system_message(elgg_echo('success'));
	} catch (Exception $e) {
		throw new APIException($e->getMessage());
	}

	return $image->guid;
}

/**
 * Finish Posting Photos (handles batch logic)
 */
function photos_finalize_post($batch, $album_guid = FALSE) {
	if (!$album_guid) {
		$album_guid = spotconnect_get_mobile_album();
	}

	$img_river_view = elgg_get_plugin_setting('img_river_view', 'tidypics');

	$album = get_entity($album_guid);

	// Check permissions on album container (for groups)
	if (!$album->getContainerEntity()->canWriteToContainer(elgg_get_logged_in_user_guid())) {
	 	throw new APIException(elgg_echo('tidypics:nopermission'));
	}

	$params = array(
		'type'            => 'object',
		'subtype'         => 'image',
		'metadata_names'  => 'batch',
		'metadata_values' => $batch,
		'limit'           => 0
	);

	$images = elgg_get_entities_from_metadata($params);

	if ($images) {	
		// Create a new batch object to contain these photos
		$batch = new ElggObject();
		$batch->subtype = "tidypics_batch";
		$batch->access_id = $album->access_id;
		$batch->container_guid = $album->guid;

		if ($batch->save()) {
			$image_list = array();

			foreach ($images as $image) {
				// Add batch relationship
				add_entity_relationship($image->guid, "belongs_to_batch", $batch->getGUID());

				// Add image to image list
				$image_list[] = $image->guid;
			}

			// Update the album's image list
			$album->prependImageList($image_list);
		}

	} else {
		throw new APIException(elgg_echo('tidypics:noimagesuploaded'));
	}

	// "added images to album" river
	if ($img_river_view == "batch" && $album->new_album == false) {
		add_to_river('river/object/tidypics_batch/create', 'create', $batch->getOwnerGUID(), $batch->getGUID());
	}

	// "created album" river
	if ($album->new_album) {
		$album->new_album = false;
		$album->first_upload = true;

		add_to_river('river/object/album/create', 'create', $album->getOwnerGUID(), $album->getGUID());

		// "created album" notifications
		// we throw the notification manually here so users are not told about the new album until
		// there are at least a few photos in it
		if ($album->shouldNotify()) {
			object_notifications('create', 'object', $album);
			$album->last_notified = time();
		}
	} else {
		// "added image to album" notifications
		if ($album->first_upload) {
			$album->first_upload = false;
		}

		if ($album->shouldNotify()) {
			// This is a bit of a hack, but there's no other way to control the subject for image notifications
			global $CONFIG;
			$CONFIG->register_objects['object']['album'] = elgg_echo('tidypics:newphotos', array($album->title));
			object_notifications('create', 'object', $album);
			$album->last_notified = time();
		}
	}
	return $batch->guid;
}