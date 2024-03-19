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
	'INSTALL_FAILED_VERSION' => '<b>Rozšíření Knowledge Base nesplňuje některé požadavky na spuštění</b><br><br>phpBB min. verze %1s - používáte %2s <br>php min. verze %3s - používáte %4s',
]);
