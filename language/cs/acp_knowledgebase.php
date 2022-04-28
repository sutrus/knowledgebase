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
//Configuration
'KB_NOT_WRITABLE'         => 'Složkám "files" nebo "plupload" nelze automaticky nastavit opránění pro zápis.<br>Nastavte prosím složkám oprávnění na 0777 ručně.',
'KB_FONT_ICON'            => 'Ikona Znalostní Báze',
'KB_FONT_ICON_EXPLAIN'    => 'Zadejte jméno <a href="https://fontawesome.com/v4.7.0/icons/" target="_blank"><strong>Font Awesome</strong></a> ikony pro odkaz v záhlaví.<br>Ponechte toto pole prázdné, pro zobrazení výchozí ikony znalostní báze.',
'KB_FONT_ICON_INVALID'    => 'Ikona znalostní databáze obsahuje neplatné znaky.',
'KB_CONFIG_EXPLAIN'       => 'Zde můžete nastavit základní nastavení.',
'KB_CONFIG_UPDATED'       => 'Nastavení bylo úspěšně aktualizováno.',
'ANOUNCE'                 => 'Oznámení článků na fóru',
'ANOUNCE_EXPLAIN'         => 'Pokud toto povolíte, bude na fóru automaticky vytvořeno téma se stručným popisem článku a odkazem na něj. <br> Ze seznamu níže (bude k dispozici po aktivaci) vyberte fórum, do kterého se budou vytvářet oznámení.',
'KB_FORUM_EXPLAIN'        => 'Vyberte fórum, ve kterém budou články oznamovány.',
'PER_PAGE'                => 'Počet zobrazených článků na stránce',
'PER_PAGE_EXPLAIN'        => 'Počet článků ve správě článků a vyhledávání.',
'SORT_TYPE'               => 'Řazení',
'SORT_TYPE_EXPLAIN'       => 'Typ řazení článků',
'FORCIBLY'                => 'vlastní pořadí',
'SELECTABLE'              => 'dle data přidání',
'ALPHABET'                => 'abecední navigace',
'EXTENSION_GROUP_EXPLAIN' => 'Umožňuje spravovat oprávnění pro přípony a soubory příloh. Chcete-li odstranit přípony, vyberte v rozbalovací nabídce na levé straně pouze ty, které chcete zachovat. Chcete-li vybrat více než jedno rozšíření, použijte klávesy CTRL + kliknutí. Chcete-li přidat nové rozšíření, použijte výchozí stránku phpBB v ACP.',
'FORUM_PREFIX'            => 'Prefix fóra',
'FORUM_PREFIX_EXPLAIN'    => 'Zobrazený text před jménem fóra.<br>Lze použít html entity např. &amp;bull; &bull;&nbsp;&nbsp;&nbsp;&amp;laquo; &laquo;&nbsp;&nbsp;&nbsp;&amp;raquo; &raquo;',
'TOPIC_PREFIX'            => 'Prefix tématu',
'TOPIC_PREFIX_EXPLAIN'    => 'Zobrazený text před jménem tématu.<br>Lze použít html entity např. &amp;bull; &bull;&nbsp;&nbsp;&nbsp;&amp;laquo; &laquo;&nbsp;&nbsp;&nbsp;&amp;raquo; &raquo;',

//Manage category
'ACP_LIBRARY_MANAGE_EXPLAIN'   => 'Každá kategorie může mít neomezený počet podkategorií. Zde můžete přidávat, upravovat, přesouvat, vyhledávat místa a přecházet z jedné kategorie do druhé. Pokud se počet záznamů v kategorii neshoduje se skutečným počtem, můžete kategorii synchronizovat.',
'CATEGOTY_LIST'                => 'Seznam kategorií',
'SELECT_CATEGORY'              => 'Vyberte kategorii',
'SELECTION_CATEGORY'           => 'Vyběr kategorie',
'ADD_CATEGORY'                 => 'Vytvořit novou kategorii',
'ADD_CATEGORY_EXPLAIN'         => 'Vytvořit novou kategorii',
'CATEGORY_ADDED'               => 'Kategorie úspěšně přidána. Nyní můžete %snastavit oprávnění%s této kategorie.',
'CATEGORY_DELETED'             => 'Kategorie úspěšně smazána.',
'CATEGORY_EDITED'              => 'Kategorie úspěšně upravena',
'CAT_PARENT'                   => 'Nadřazená kategorie',
'CAT_NAME'                     => 'Jméno kategorie',
'CAT_DESCR'                    => 'Popis kategorie',
'COPY_CAT_PERMISSIONS'         => 'Kopírování oprávnění z kategorie',
'COPY_CAT_PERMISSIONS_EXPLAIN' => 'Umožňuje zkopírovat oprávnění vybrané kategorie do aktuální kategorie.',
'KB_ROOT'                      => 'Hlavní kategorie',
'NO_CAT_NAME'                  => 'Nezadali jste název kategorie.',
'NO_CAT_DESCR'                 => 'Nemáte vytvořen popis kategorie.',
'DELETE_SUBCATS'               => 'Odstranění kategorií a článků',
'DEL_CATEGORY'                 => 'Odstranit kategorii',
'DEL_CATEGORY_EXPLAIN'         => 'Tento formulář umožňuje odstranit kategorii. Můžete se rozhodnout, kam přesunete všechny články v ní nebo v podkategorii.',
'LIBRARY_EDIT_CAT'             => 'Edit the category',
'LIBRARY_EDIT_CAT_EXPLAIN'     => 'Zde můžete kategorii přejmenovat, stručně ji popsat a přesunout do jiné kategorie (s obsahem).',
'MOVE_SUBCATS_TO'              => 'Přesunout subkategorii',
'NO_CATS_IN_KB'                => 'Databáze znalostí neobsahuje žádné kategorie.',
'NO_DESTINATION_CATEGORY'      => 'Příjemce kategorie nebyl nalezen',
'NO_PARENT'                    => 'Nemá nadřazené',

//Article
'ARTICLE_MANAGE_EXPLAIN' => 'Zde můžete články mazat nebo přesouvat do jiných kategorií a také je prohlížet nebo upravovat (v samostatném okně).',
'ARTICLE_MOVE_EXPLAIN'   => 'Vyberte kategorii, do které chcete článek přesunout.',
'EDIT_DATE'              => 'Upraveno',
'MOVE_ARTICLES_TO'       => 'Přesunout článek',
'DELETE_ALL_ARTICLES'    => 'Delete article',
'NO_ARTICLES_IN_KB'      => 'Znalostní báze ještě neobsahuje žádné články.',

//Permission
'ACP_LIBRARY_PERMISSIONS'         => 'Oprávnění',
'ACP_LIBRARY_PERMISSIONS_MASK'    => 'Zobrazení oprávnění',
'ACP_LIBRARY_PERMISSIONS_NO_CATS' => 'Chcete-li nastavit oprávnění, musíte vytvořit alespoň jednu kategorii.',
'ACP_LIBRARY_PERMISSIONS_EXPLAIN' => 'Zde můžete pro každého uživatele a skupinu změnit přístup k jednotlivým kategoriím knihovny. Pro přidělení moderátorských nebo administrátorských práv použijte definici příslušné stránky.',
'ALL_CATS'                        => 'Všechny kategorie',
'NO_COPY_PERMISSIONS'             => 'Nekopírovat oprávnění',
// User Permissions
'kb_u_add'                        => 'Může přidávat články',
'kb_u_edit'                       => 'Může upravit vlastní články',
'kb_u_delete'                     => 'Může smazat vlastní články',
'kb_u_add_noapprove'              => 'Může přidat / upravit články bez schválení',
// Moderator Permissions
'kb_m_edit'                       => 'Může upravovat články',
'kb_m_delete'                     => 'Může mazat články',
'kb_m_approve'                    => 'Může schvalovat články',

//Search
'YES_SEARCH_EXPLAIN'              => 'Umožní uživatelům využívat funkci vyhledávání ve Znalostní bázi.',
'PER_PAGE_SEARCH'                 => 'Výsledky hledání',
'PER_PAGE_SEARCH_EXPLAIN'         => 'Počet zobrazených položek ve výsledcích hledání.',
'SEARCH_TYPE_EXPLAIN'             => 'Rozšíření Znalostní báze umožňuje zvolit backend, který se používá pro vyhledávání textu v obsahu příspěvku. Ve výchozím nastavení se používá vlastní fulltextové vyhledávání.',

//Attachments
'ATTACHMENTS_EXPLAIN'                        => 'Umožňuje zobrazit a odstranit soubory příloh v článcích.',
'ACP_LIBRARY_ATTACHMENTS_LOST_FILES_EXPLAIN' => 'Umožňuje najít ztracené soubory na serveru. Pokud se soubory na serveru nenacházejí, budou záznamy odstraněny.',
'PRUNE_ATTACHMENTS_EXPLAIN'                  => 'Ověří existenci osiřelých souborů na serveru. Pokud soubor existuje, bude odstraněn. Mohli byste to potvrdit?',
'ORPHAN_EXPLAIN'                             => 'Umožňuje zobrazit ztracené soubory. Obecně se zde tyto soubory zobrazují, protože uživatelé soubory odesílají, ale nepublikují články. Tyto soubory je možné odstranit nebo je připojit k článkům. Chcete-li ztracený soubor připojit k článku, musíte zadat ID článku.',
'FILES_DELETED_SUCCESS'                      => 'Soubory příloh byly úspěšně odstraněny!',
'NO_FILES_SELECTED'                          => 'Není vybrán žádný soubor.',
'PRUNE_ATTACHMENTS_FINISHED'                 => 'Nebyly nalezeny žádné doplňující soubory.',
'PRUNE_ATTACHMENTS_PROGRESS'                 => 'Nepoužívané soubory jsou kontrolovány. Nezastavujte proces!<br>Následující soubory byly odstraněny:',
'PRUNE_ATTACHMENTS_FAIL'                     => '<br>Následující soubory nebylo možné smazat:',
'POST_ROW_ARTICLE_INFO'                      => ' s číslem %1$d…',
'RESYNC_ATTACHMENTS_FINISHED'                => 'Soubory příloh byly úspěšně synchronizovány (ověření korektnosti záznamů v databázi).',
'RESYNC_ATTACHMENTS_PROGRESS'                => 'Probíhá ověřování záznamů v databázi! Nezastavujte proces!',
'SYNC_OK'                                    => 'Kategorie byla úspěšně synchronizována.',
'THUMBNAIL_EXPLAIN'                          => 'Velikost miniatur je definována na výchozí stránce phpBB v ACP (&laquo;Přílohy&raquo;).',
'UPLOAD_DENIED_ARTICLE'                      => 'Článek s tímto ID neexistuje.',
'UPLOADING_FILE_TO_ARTICLE'                  => 'Nahrát soubor “%1$s” do článku',

//Log
'ACP_LIBRARY_LOGS_EXPLAIN'          => 'Toto je seznam provedených akcí rozšíření. Seznam můžete seřadit podle jména uživatele, data, IP adresy nebo akce.<br>Jednotlivé záznamy můžete odstranit nebo celý protokol jako celek vymazat.',
'LOG_CLEAR_KB'                      => '<strong>Knihovna záznamů vyčištěna</strong>',
'LOG_CATS_MOVE_DOWN'                => '<strong>Přesun kategorie</strong> %1$s <strong>dolů</strong> %2$s',
'LOG_CATS_MOVE_UP'                  => '<strong>Přesun kategorie</strong> %1$s <strong>nahoru</strong> %2$s',
'LOG_CATS_ADD'                      => '<strong>Přidána kategorie</strong><br> %s',
'LOG_CATS_DEL_ARTICLES'             => '<strong>Odebrány články z kategorie</strong><br> %s',
'LOG_CATS_DEL_MOVE_POSTS_MOVE_CATS' => '<strong>Odebrána kategorie</strong> %3$s, <strong>přesunuty příspěvky do</strong> %1$s <strong>a subkategorie do</strong> % 2$s',
'LOG_CATS_DEL_MOVE_POSTS'           => '<strong>Odebrána kategorie</strong> %2$s<br><strong> příspěvky přesunuty</strong> % 1$s',
'LOG_CATS_DEL_CAT'                  => '<strong>Odebrána kategorie</strong><br> %s',
'LOG_CATS_DEL_MOVE_POSTS_CATS'      => '<strong>Odebrána kategorie</strong> %2$s<br><strong>se subkategoriemi, články přesunuté do</strong> %1$s',
'LOG_CATS_DEL_POSTS_MOVE_CATS'      => '<strong>Odebrána kategorie</strong> %2$s <strong>s články, subkategorie přesunuta do</strong> %1$s',
'LOG_CATS_DEL_POSTS_CATS'           => '<strong>Odebrána kategorie s články a subkategoriemi</strong><br> %s',
'LOG_CATS_DEL_CATS'                 => '<strong>Odebrána kategorie</strong> %2$s <strong>a subkategorie přesunuty do</strong> %1$s',
'LOG_CATS_EDIT'                     => '<strong>Upraveny informace kategorie</strong><br> %1$s',
'LOG_CATS_CAT_MOVED_TO'             => '<strong>Kategorie</strong> %1$s <strong>přesunuta do</strong> %2$s',
'LOG_CATS_SYNC'                     => '<strong>Kategorie sychronyzována</strong><br> %1s',
'LOG_KB_CONFIG_SEARCH'              => '<strong>Změna nastavení vyhledávání ve znalostní databázi</strong>',
'LOG_LIBRARY_ADD_ARTICLE'           => 'Přidán článek &laquo;<strong>%1s</strong>&raquo; do kategorie<br><strong>%2s</strong>',
'LOG_LIBRARY_DEL_ARTICLE'           => 'Odebrán článek &laquo;<strong>%1s</strong>&raquo; z kategorie<br><strong>%2s</strong>',
'LOG_LIBRARY_EDIT_ARTICLE'          => 'Upraven článek &laquo;<strong>%1s</strong>&raquo; v kategorii<br><strong>%2s</strong>',
'LOG_LIBRARY_MOVED_ARTICLE'         => 'Přesunut článek <strong>%1s</strong> z kategorie <strong>%2s</strong><br>do kategorie <strong>%3s</strong>',
'LOG_LIBRARY_APPROVED_ARTICLE'      => 'Schválen článek <strong>%1s</strong> v kategorii <strong>%2s</strong><br>od uživatele <strong>%3s</strong>',
'LOG_LIBRARY_REJECTED_ARTICLE'      => 'Zamítnut článek <strong>%1s</strong> v kategorii <strong>%2s</strong><br>od uživatele <strong>%3s</strong>',
'LOG_LIBRARY_PERMISSION_DELETED'    => 'Odebrán přístup uživatele/skupiny do kategorie <strong>%1s</strong><br> %2s',
'LOG_LIBRARY_PERMISSION_ADD'        => 'Přidán nebo upraven přístup uživatele/skupiny do kategorie <strong>%1s</strong><br> %2s',
'LOG_LIBRARY_CONFIG'                => '<strong>Změněno nastavení rozšíření</strong>',

'KB_TRACE_GROUP_NEVER_TOTAL_NO_LOCAL'  => 'This group’s permission for this category is set to <strong>NEVER</strong> which becomes the new total value because it wasn’t set yet (set to <strong>NO</strong>).',
'KB_TRACE_GROUP_NEVER_TOTAL_YES_LOCAL' => 'This group’s permission for this category is set to <strong>NEVER</strong> which overwrites the total <strong>YES</strong> to a <strong>NEVER</strong> for this user.',
'KB_TRACE_GROUP_NO_LOCAL'              => 'The permission is <strong>NO</strong> for this group within this category so the old total value is kept.',
'KB_TRACE_GROUP_YES_TOTAL_NEVER_LOCAL' => 'This group’s permission for this category is set to <strong>YES</strong> but the total <strong>NEVER</strong> cannot be overwritten.',
'KB_TRACE_GROUP_YES_TOTAL_NO_LOCAL'    => 'This group’s permission for this category is set to <strong>YES</strong> which becomes the new total value because it wasn’t set yet (set to <strong>NO</strong>).',
'KB_TRACE_GROUP_YES_TOTAL_YES_LOCAL'   => 'This group’s permission for this category is set to <strong>YES</strong> and the total permission is already set to <strong>YES</strong>, so the total result is kept.',

'KB_TRACE_USER_FOUNDER' => 'The user is a founder, therefore admin permissions are always set to <strong>YES</strong>.',
'KB_TRACE_USER_ADMIN'   => 'The user is a board administrator with permission <strong>Can manage Knowledge Base</strong>, therefore admin permissions are always set to <strong>YES</strong>.',

'KB_TRACE_USER_KEPT_LOCAL'              => 'The user’s permission for this category is <strong>NO</strong> so the old total value is kept.',
'KB_TRACE_USER_NEVER_TOTAL_NEVER_LOCAL' => 'The user’s permission for this category is set to <strong>NEVER</strong> and the total value is set to <strong>NEVER</strong>, so nothing is changed.',
'KB_TRACE_USER_NEVER_TOTAL_NO_LOCAL'    => 'The user’s permission for this category is set to <strong>NEVER</strong> which becomes the total value because it was set to NO.',
'KB_TRACE_USER_NEVER_TOTAL_YES_LOCAL'   => 'The user’s permission for this category is set to <strong>NEVER</strong> and overwrites the previous <strong>YES</strong>.',
'KB_TRACE_USER_NO_TOTAL_NO_LOCAL'       => 'The user’s permission for this category is <strong>NO</strong> and the total value was set to NO so it defaults to <strong>NEVER</strong>.',
'KB_TRACE_USER_YES_TOTAL_NEVER_LOCAL'   => 'The user’s permission for this category is set to <strong>YES</strong> but the total <strong>NEVER</strong> cannot be overwritten.',
'KB_TRACE_USER_YES_TOTAL_NO_LOCAL'      => 'The user’s permission for this category is set to <strong>YES</strong> which becomes the total value because it was set to <strong>NO</strong>.',
'KB_TRACE_USER_YES_TOTAL_YES_LOCAL'     => 'The user’s permission for this category is set to <strong>YES</strong> and the total value is set to <strong>YES</strong>, so nothing is changed.',

'KNOWLEDGE_BASE'                          => 'Znalostní báze',
));
