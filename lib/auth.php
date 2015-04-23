<?php
/**
 * SpotConnect Auth Related API
 *
 * @package SpotConnect
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU Public License version 2
 * @author Jeff Tilson
 * @copyright THINK Global School 2010 - 2015
 * @link http://www.thinkglobalschool.org/
 * 
 */

/**
 * The auth.get_user_pass_auth_token API.
 * This API call lets a user log in, returning an authentication token which can be used
 * to authenticate a user for a period of time. It is passed in future calls as the parameter
 * auth_token.
 *
 * @param string $username Username
 * @param string $password Clear text password
 *
 * @return string Token string or exception
 * @throws SecurityException
 * @access private
 */
function auth_get_infinity_token($username, $password) {
	// check if username is an email address
	if (is_email_address($username)) {
		$users = get_user_by_email($username);
			
		// check if we have a unique user
		if (is_array($users) && (count($users) == 1)) {
			$username = $users[0]->username;
		} else {
			throw new SecurityException(elgg_echo('SecurityException:authenticationfailed'));
		}
	}
	
	// validate username and password
	if (true === elgg_authenticate($username, $password)) {
		$token = create_user_token($username, 60 * 24 * 365 * 100);
		if ($token) {
			return $token;
		}
	}

	throw new SecurityException(elgg_echo('SecurityException:authenticationfailed'));
}

/**
 * The auth.get_google_auth_token API
 * This API call lets a user log in with their google account, returning an authentication token which can be used
 * to authenticate a user for a period of time. It is passed in future calls as the parameter
 * auth_token.
 */
function auth_google_get_infinity_token ($email) {
	if (is_email_address($email)) {
		$users = get_user_by_email($email);

		// check if we have a unique user
		if (is_array($users) && (count($users) == 1)) {
			$username = $users[0]->username;
			$user = get_user_by_username($username);
			if ($user->google_connected) {
				$token = create_user_token($username, 60 * 24 * 365 * 100);
				if ($token) {
					return $token;
				}
			}
		}
	}
	throw new SecurityException(elgg_echo('SecurityException:authenticationfailed'));
}

