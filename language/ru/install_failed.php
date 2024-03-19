<?php

/**
 *
 * @package       phpBB Extension - Knowledge Base
 * @copyright (c) 2022 phpBB Limited <https://www.phpbb.com>
 * @license       GNU General Public License, version 2 (GPL-2.0)
 *
 */
/**
 * DO NOT CHANGE
 */
if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = [];
}

$lang = array_merge($lang, [
	'INSTALL_FAILED_VERSION' => '<b>The Knowledge Base extension does not match some of the startup requirements</b><br><br>phpBB min. version %1s - you are using %2s <br>php min. version %3s - you are using %4s',
]);
