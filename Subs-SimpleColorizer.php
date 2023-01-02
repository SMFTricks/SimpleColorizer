<?php

/**
 * @package SimpleColorizer
 * @version 1.4
 * @author Diego Andrés <diegoandres_cortes@outlook.com>
 * @copyright Copyright (c) 2022, SMF Tricks
 * @license MIT
 */

if (!defined('SMF'))
	die('Hacking attempt...');

/**
 * Using integrate_buffer it will insert the color of the group for each user if a profile link is found.
 * 
 * @param string $buffer The buffer content
 * @return $buffer The modified buffer content
 */
function ob_colorizer($buffer)
{
	global $scripturl;

	// on xml we don't need to do anything
	if (isset($_REQUEST['xml']))
		return $buffer;

	// Get all of the profile links in the content and obtain their ID's.
	$user_ids = preg_match_all('~<a.+?href="' . preg_quote($scripturl) . '\?action=profile;u=(\d+)"~', $buffer, $matches) ? array_unique($matches[1]) : array();

	// Do nothing if there are no ID's
	if (empty($user_ids))
		return $buffer;

	// Get the users and their colors if there are any.
	if (($user_colors = sc_loadColors($user_ids)) !== false)
	{
		// Loop through the users and insert their colors.
		foreach ($user_colors as $user_id => $user_color)
		{
			// No color, no fun
			if (empty($user_color))
				continue;

			// Replace the links that match the profile URL pattern
			$buffer = preg_replace_callback(str_replace('{$user_id}', $user_id, '~<a[^>]*href="' . preg_quote($scripturl) . '\?action=profile\;u={$user_id}"[^>]*>~'),
				function ($matches) use ($user_color)
				{
					$result = $matches[0];

					// No styles
					if (strpos($result, 'style="') === false)
						$result = str_replace('>', ' style="color: ' . $user_color . ';">', $result);
					// Add the color
					else
						$result = preg_replace('/style=(["\'])([^"\']*)\1/','style=$1$2;color:'. $user_color . ';$1', $result);

					return $result;
				},
			$buffer);
		}
	}

	// Return the colorized forum buffer.
	return $buffer;
}

/**
 * Loads the users with the found ID's and returns an array with the user ID's and the color.
 * 
 * @param array $user_ids The user ID's
 * @return array|bool The user ID's and the color or false if there are no users
 */
function sc_loadColors($user_ids = array())
{
	global $smcFunc;

	// No users? Sad.
	if (empty($user_ids))
		return false;

	// Make sure it's an array or make it an array.
	$user_ids = is_array($user_ids) ? $user_ids : array($user_ids);

	$request = $smcFunc['db_query']('','
		SELECT mem.id_member, mem.real_name, mg.online_color AS member_group_color, pg.online_color AS post_group_color
		FROM {db_prefix}members AS mem
			LEFT JOIN {db_prefix}membergroups AS pg ON (pg.id_group = mem.id_post_group)
			LEFT JOIN {db_prefix}membergroups AS mg ON (mg.id_group = mem.id_group)
		WHERE mem.id_member IN ({array_int:user_ids})',
		array(
			'user_ids' => $user_ids,
		)
	);
	$user_colors = array();
	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		$user_colors[$row['id_member']] = !empty($row['member_group_color']) ? $row['member_group_color'] : $row['post_group_color'];

		unset($row['member_group_color'], $row['post_group_color']);
	}
	$smcFunc['db_free_result']($request);

	return $user_colors;
}