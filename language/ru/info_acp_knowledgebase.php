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

// DEVELOPERS PLEASE NOTE
//
// All language files should use UTF-8 as their encoding and the files must not contain a BOM.
//
// Placeholders can now contain order information, e.g. instead of
// 'Page %s of %s' you can (and should) write 'Page %1$s of %2$s', this allows
// translators to re-order the output of data while ensuring it remains correct
//
// You do not need this where single placeholders are used, e.g. 'Message %d' is fine
// equally where a string contains only two placeholders which are used to wrap text
// in a url you again do not need to specify an order e.g., 'Click %sHERE%s' is fine

$lang = array_merge($lang, [
	'KNOWLEDGE_BASE'                      => 'Библиотека',
	'ACP_KNOWLEDGE_BASE_CONFIGURE'        => 'Конфигурация',
	'ACP_LIBRARY_MANAGE'                  => 'Управление библиотекой',
	'ACP_LIBRARY_ARTICLES'                => 'Статьи',
	'ACP_LIBRARY_PERMISSIONS'             => 'Права доступа',
	'ACP_LIBRARY_SEARCH'                  => 'Поиск',
	'ACP_LIBRARY_ATTACHMENTS'             => 'Вложения',
	'ACP_LIBRARY_ATTACHMENTS_ORPHAN'      => 'Потерянные вложения',
	'ACP_LIBRARY_LOGS'                    => 'Лог действий',
	'ACP_LIBRARY_ATTACHMENTS_EXTRA_FILES' => 'Лишние файлы',
	'ACP_LIBRARY_ATTACHMENTS_LOST_FILES'  => 'Потерянные файлы',
	'ACP_LIBRARY_PERMISSIONS_MASK'        => 'Маски прав доступа',
]);
