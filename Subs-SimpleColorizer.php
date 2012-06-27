<?php

if (!defined('SMF'))
	die('Hacking attempt...');

function ob_colorizer($buffer)
{
	global $context, $scripturl, $sourcedir, $modSettings;

	if (isset($_REQUEST['xml']))
		return $buffer;

	$regex = array(
		'~href="' . preg_quote($scripturl) . '\?action=profile;u=(\d+)"~',
		'~(href="' . preg_quote($scripturl) . '\?action=profile\;u={$user_id}"[^>]*)~'
	);

	$user_ids = preg_match_all($regex[0], $buffer, $matches) ? array_unique($matches[1]) : array();

	if (empty($user_ids))
		return $buffer;

	if (($user_colors = sc_loadColors($user_ids)) !== false)
		foreach ($user_colors as $user_id => $user_color)
			$buffer = preg_replace(str_replace('{$user_id}', $user_id, $regex[1]), '$1 style="color: ' . $user_color . ';"', $buffer);

	return $buffer;
}

function sc_loadColors($user_ids = array())
{
	global $smcFunc, $user_profile;

	if (empty($user_ids))
		return false;

	$user_ids = is_array($user_ids) ? $user_ids : array($user_ids);

	$request = $smcFunc['db_query']('','
		SELECT mem.id_member, mem.real_name, mg.online_color AS member_group_color, pg.online_color AS post_group_color
		FROM {db_prefix}members AS mem
			LEFT JOIN {db_prefix}membergroups AS pg ON (pg.id_group = mem.id_post_group)
			LEFT JOIN {db_prefix}membergroups AS mg ON (mg.id_group = mem.id_group)
		WHERE mem.id_member IN ({array_int:user_ids})',
		array(
			'user_ids'	=> $user_ids,
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

?>