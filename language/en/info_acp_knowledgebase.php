<?php
/**
 *
 * knowledgebase [English]
 *
 * @copyright (c) 2013 phpBB Group
 * @license       http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
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
	'KNOWLEDGE_BASE'                      => 'Knowledge Base',
	'ACP_KNOWLEDGE_BASE_CONFIGURE'        => 'Configuration',
	'ACP_LIBRARY_MANAGE'                  => 'Knowledge Base Management',
	'ACP_LIBRARY_ARTICLES'                => 'Articles',
	'ACP_LIBRARY_PERMISSIONS'             => 'Permissions',
	'ACP_LIBRARY_SEARCH'                  => 'Search',
	'ACP_LIBRARY_ATTACHMENTS'             => 'Attachment files',
	'ACP_LIBRARY_ATTACHMENTS_ORPHAN'      => 'Attachment orphean files',
	'ACP_LIBRARY_LOGS'                    => 'Log action',
	'ACP_LIBRARY_ATTACHMENTS_EXTRA_FILES' => 'Extra files',
	'ACP_LIBRARY_ATTACHMENTS_LOST_FILES'  => 'Lost files',
	'ACP_LIBRARY_PERMISSIONS_MASK'        => 'Permissions trace',
]);
