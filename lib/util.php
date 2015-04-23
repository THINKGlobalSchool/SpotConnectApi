<?php
/**
 * SpotConnect Utilities Library
 *
 * @package SpotConnect
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU Public License version 2
 * @author Jeff Tilson
 * @copyright THINK Global School 2010 - 2015
 * @link http://www.thinkglobalschool.org/
 * 
 */

/**
 * PING api call, simply responds if the server is alive
 */
function util_ping() {
	$user = elgg_get_logged_in_user_entity();

	return array(
		'alive' => time()
	);
}