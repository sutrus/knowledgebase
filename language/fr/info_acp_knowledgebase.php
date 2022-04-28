<?php
/**
 *
 * Knowledge base. An extension for the phpBB Forum Software package.
 * French translation by Galixte (http://www.galixte.com)
 *
 * @copyright (c) 2017 Sheer <https://phpbbguru.net>
 * @license       GNU General Public License, version 2 (GPL-2.0)
 *
 */
if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = array();
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
$lang = array_merge($lang, array(
	'KNOWLEDGE_BASE'                      => 'Base de connaissances',
	'ACP_KNOWLEDGE_BASE_CONFIGURE'        => 'Paramètres',
	'ACP_LIBRARY_MANAGE'                  => 'Gestion des catégories',
	'ACP_LIBRARY_ARTICLES'                => 'Gestion des articles',
	'ACP_LIBRARY_PERMISSIONS'             => 'Permissions',
	'ACP_LIBRARY_SEARCH'                  => 'Recherche',
	'ACP_LIBRARY_ATTACHMENTS'             => 'Fichiers joints',
	'ACP_LIBRARY_ATTACHMENTS_ORPHAN'      => 'Fichiers joints orphelins',
	'ACP_LIBRARY_LOGS'                    => 'Journal des actions',
	'ACP_LIBRARY_ATTACHMENTS_EXTRA_FILES' => 'Fichiers supplémentaires',
	'ACP_LIBRARY_ATTACHMENTS_LOST_FILES'  => 'Fichiers perdus',
	'ACP_LIBRARY_PERMISSIONS_MASK'        => 'Permissions trace',
));
