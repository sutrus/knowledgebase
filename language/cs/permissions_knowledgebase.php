<?php

/**
 *
 * @package       phpBB Extension - Knowledge Base
 * @copyright (c) 2013 phpBB Limited <https://www.phpbb.com>
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
	'KNOWLEDGEBASE'   => 'Znalostní báze',
	'ACL_A_MANAGE_KB' => 'Může spravovat znalostní bázi',
	'ACL_U_KB_VIEW'   => 'Může zobrazit znalostní bázi',
]);
