<?php
/**
 *
 * Knowledge base. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2017, Sheer
 * @license       GNU General Public License, version 2 (GPL-2.0)
 *
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
	'KNOWLEDGEBASE'   => 'Библиотека',
	'ACL_A_MANAGE_KB' => 'Может управлять библиотекой',
	'ACL_U_KB_VIEW'   => 'Может видеть Библиотеку',
]);
