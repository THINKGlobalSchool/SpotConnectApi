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
	$pattern = "/(?:^|\s)(\#\w+)/";
	preg_match_all($pattern, $title, $matches);
	
	if (!empty($matches[1])) {
		$tags = array();
		// Got hashtags
		foreach ($matches[1] as $idx => $tag) {
			$tag = strtolower(str_replace("#", '', $tag));
			$tags[] = $tag;
		}
	}

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
