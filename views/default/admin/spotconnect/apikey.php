<?php
/**
 * SpotConnect API Key Admin Page
 *
 * @package SpotConnect
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU Public License version 2
 * @author Jeff Tilson
 * @copyright THINK Global School 2010 - 2015
 * @link http://www.thinkglobalschool.org/
 * 
 */

// Get plugin entity
$plugin = elgg_get_plugin_from_id('spotconnect');

// Check for an api key
if (!$plugin->apikey) {
	$key = create_api_user(elgg_get_site_entity()->guid);
	$plugin->apikey = $key->api_key;
} 

echo $plugin->apikey;
