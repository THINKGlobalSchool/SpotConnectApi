<?php
/**
 * SpotConnect User Related API
 *
 * @package SpotConnect
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU Public License version 2
 * @author Jeff Tilson
 * @copyright THINK Global School 2010 - 2015
 * @link http://www.thinkglobalschool.org/
 * 
 */

/**
 * user.get_profile API call, returns basic information about the 
 * authenticated user
 */
function user_get_profile() {
	$user = elgg_get_logged_in_user_entity();

	$user_profile_array = array(
		'username' => $user->username,
		'name' => $user->name,
		'email' => $user->email,
		'picture' => $user->getIconURL()
	);

	return $user_profile_array;
}