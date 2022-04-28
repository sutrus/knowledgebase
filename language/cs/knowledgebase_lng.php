<?php

/**
 *
 * @package       phpBB Extension - Knowledge base
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

$lang = array_merge($lang, array(
	'ALPHABET'                             => 'A-B-C-D-E-F-G-H-I-J-K-L-M-N-O-P-Q-R-S-T-U-V-W-X-Y-Z',
	'ALPHABET_NAV'                         => 'Filtrovat dle abecedy:',
	'ADD_ARTICLE'                          => 'Přidat článek',
	'APPROVE'                              => 'Schválit',
	'ARTICLE'                              => 'Článek',
	'ARTICLES'                             => 'Články',
	'ARTICLE_APPROVED_SUCESS'              => 'Článek byl schválen.',
	'ARTICLE_AUTHOR'                       => 'Autor',
	'ARTICLE_BODY'                         => 'Text článku',
	'ARTICLE_BODY_EXPLAIN'                 => 'Vložte text článku',
	'ARTICLE_DATE'                         => 'Datum',
	'ARTICLE_DELETED'                      => 'Článek byl úspěšně smazán.',
	'ARTICLE_DESCRIPTION'                  => 'Popis',
	'ARTICLE_DISAPPROVED_SUCESS'           => 'Článek byl zamítnut.',
	'ARTICLE_EDITED'                       => 'Článek byl úspěšně upraven.',
	'ARTICLE_MANAGE'                       => 'Spravovat články',
	'ARTICLE_MOVED'                        => 'Článek úspěšně přesunut.',
	'ARTICLE_NEED_APPROVE'                 => 'Článek byl úspěšně přidán, ale potřebuje schválení.',
	'ARTICLE_NO_EXISTS'                    => 'Tento článek neexistuje',
	'ARTICLE_SUBMITTED'                    => 'Článek byl úspěšně přidán.',
	'ARTICLE_TITLE'                        => 'Pojmenování článku',
	'CATEGORIES'                           => 'Kategorie',
	'CATEGORIES_LIST'                      => 'Seznam kategorií',
	'CATEGORY'                             => 'Kategorie',
	'CAT_NO_EXISTS'                        => 'Tato kategorie neexistuje',
	'COMMENTS'                             => 'Komentáře',
	'COPYED'                               => 'Zkopírováno',
	'COPY_TO_BUFFER'                       => 'Kopírovat',
	'CONFIRM_DELETE_ARTICLE'               => 'Opravdu chcete smazat tento článek?',
	'COULDNT_GET_CAT_DATA'                 => 'Data nelze přijmout',
	'COULDNT_UPDATE_ORDER'                 => 'Pořadí kategorií nelze změnit',
	'DELETE_ARTICLE'                       => 'Smazat článek',
	'DELETE_ARTICLE_WARN'                  => 'Smazaný článek nelze obnovit',
	'DESCR'                                => 'Popis článku',
	'DIRECT_LINK'                          => 'Přímý odkaz',
	'DISAPPROVE'                           => 'Zamítnout',
	'EDIT'                                 => 'Upravit',
	'EDIT_ARTICLE'                         => 'Upravit článek',
	'EMPTY_QUERY'                          => 'Nezadali jste žádný vyhledávací dotaz',
	'FOUND_KB_SEARCH_MATCH'                => '%d výsledek nalezen',
	'FOUND_KB_SEARCH_MATCHES'              => 'Nalezeno %d výsledků',
	'KB_PERMISSIONS'                       => 'Oprávnění',
	'LAST_ARTICLE'                         => 'Poslední článek',
	'LEAVE_COMMENTS'                       => 'Napsat komentář',
	'LIBRARY'                              => 'Znalostní báze',
	'LINK_TO_ARTICLE'                      => 'Odkaz na článek (bb-code [URL])',
	'LOGIN_EXPLAIN_APPROVE'                => 'K provedení této akce musíte být zaregistrován a přihlášen.',
	'MAX_NUM_ATTACHMENTS'                  => 'Bylo dosaženo max. počtu povolených souborů příloh: %d ',
	'MISSING_INLINE_ATTACHMENT'            => 'Soubor přílohy <strong>%s</strong> není k dispozici',
	'MOVE_DRAGNDROP'                       => 'Podržením levého tlačítka myši přesuňte článek na požadované místo',
	'NEED_APPROVE'                         => 'Článek vyžaduje schválení',
	'NOTIFICATION_ARTICLE_APPROVE'         => '<strong>Moderátor</strong> %1$s schválil váš článek:',
	'NOTIFICATION_ARTICLE_DISAPPROVE'      => '<strong>Moderátor</strong> %1$s odmítl váš článek:',
	'NOTIFICATION_NEED_APPROVAL'           => '<strong>Článek čeká na schválení</strong> od %1$s:',
	'NOTIFICATION_TYPE_ARTICLE_APPROVE'    => 'Článek byl schválen',
	'NOTIFICATION_TYPE_ARTICLE_DISAPPROVE' => 'Článek byl zamítnut',
	'NOTIFICATION_TYPE_NEED_APPROVAL'      => 'Článek čeká na schválení',
	'NO_ARTICLES'                          => 'V této kategorii nejsou žádné články',
	'NO_CAT_YET'                           => 'Znalostní báze zatím nemá žádnou kategorii.',
	'NO_DESCR'                             => 'Nezadali jste popis článku',
	'NO_ID_SPECIFIED'                      => 'Není nastaveno číslo článku',
	'NO_KB_SEARCH_RESULTS'                 => 'Nebyly nalezeny žádné odpovídající články.',
	'NO_NEED_APPROVE'                      => 'Tento článek nevyžaduje schválení.',
	'NO_TEXT'                              => 'Nezadali jste text článku',
	'NO_TITLE'                             => 'Nezadali jste název článku',
	'PRINT'                                => 'Tisk',
	'READ_FULL'                            => 'Přečíst celý článek',
	'RESET_FILTER'                         => 'Zrušit filter',
	'RETURN_ARTICLE'                       => '%sZpět na článek%s',
	'RETURN_CAT'                           => '%sZpět na kategorie%s',
	'RETURN_LIBRARY'                       => '%sZpět na znalostní bázi%s',
	'RETURN_NEW_CAT'                       => '%sZpět na novou kategorii%s',
	'RETURN_TO_KB_SEARCH_ADV'              => 'Zpět na pokročilé hledání',
	'RULES_KB_ADD_CAN'                     => '<strong>Můžete</strong> přidávat články',
	'RULES_KB_ADD_CANNOT'                  => '<strong>Nemůžete</strong> přidávat články',
	'RULES_KB_ADD_NOAPPROVE'               => '<strong>Můžete</strong> přidávat články bez schválení',
	'RULES_KB_ADD_NOAPPROVE_CANNOT'        => '<strong>Nemůžete</strong> přidávat články bez schválení',
	'RULES_KB_APPROVE_MOD_CAN'             => '<strong>Můžete</strong> schvalovat články',
	'RULES_KB_APPROVE_MOD_CANNOT'          => '<strong>Nemůžete</strong> schvalovat články',
	'RULES_KB_DELETE_CAN'                  => '<strong>Můžete</strong> mazat vlastní články',
	'RULES_KB_DELETE_CANNOT'               => '<strong>Nemůžete</strong> mazat vlastní články',
	'RULES_KB_DELETE_MOD_CAN'              => '<strong>Můžete</strong> mazat články',
	'RULES_KB_EDIT_CAN'                    => '<strong>Můžete</strong> upravovat vlastní články',
	'RULES_KB_EDIT_CANNOT'                 => '<strong>Nemůžete</strong> upravovat vlastní články',
	'RULES_KB_EDIT_MOD_CAN'                => '<strong>Můžete</strong> upravovat články',
	'RULES_KB_MOD_DELETE_CANNOT'           => '<strong>Nemůžete</strong> mazat články',
	'RULES_KB_MOD_EDIT_CANNOT'             => '<strong>Nemůžete</strong> upravovat články',
	'SEARCH_ALL'                           => 'V názvu, popisu a textu článků',
	'SEARCH_ARTICLES_ONLY'                 => 'Pouze v textu článků',
	'SEARCH_ARTICLES_TITLE_ONLY'           => 'Pouze v názvu článků',
	'SEARCH_DESCRIPTIONS_ONLY'             => 'Pouze v popisu článků',
	'SEARCH_CAT'                           => 'Hledat v kategotiích',
	'SEARCH_CAT_EXPLAIN'                   => 'Vyberte kategorii nebo kategorie, ve kterých se bude vyhledávat. Pokud není vybráno nic, bude vyhledávání provedeno ve všech kategoriích.',
	'SEARCH_DISABLED'                      => 'Hledání ve znalostní bázi bylo zakázáno administrátorem',
	'SEARCH_IN_CAT'                        => 'Hledám v kategorii…',
	'SORT_ARTICLE_TITLE'                   => 'Nadpis článku',
	'TOTAL_ITEMS'                          => 'Článků: <strong>%d</strong>',
	'WARNING_DEFAULT_CONFIG'               => 'Znalostní báze je nainstalována s výchozím nastavením nastavením konfigurace, což může vést k nesprávnému fungování modulu. <br> Přejděte do části <strong>Konfigurace</strong> a nastavte požadované hodnoty.',
));