<?php
/**
 *
 * Knowledge base. An extension for the phpBB Forum Software package.
 * French translation by Galixte (http://www.galixte.com)
 *
 * @copyright (c) 2017 Sheer <https://phpbbguru.net>
 * @license       GNU General Public License, version 2 (GPL-2.0)
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
//Configuration
'KB_NOT_WRITABLE'                            => 'The "files" or "plupload" folders cannot be automatically set to write permissions.<br>Please set the folders permissions to 0777 manually.',
'KB_FONT_ICON'                               => 'Knowledge Base link icon',
'KB_FONT_ICON_EXPLAIN'                       => 'Enter the name of a <a href="https://fontawesome.com/v4.7.0/icons/" target="_blank"><strong>Font Awesome</strong></a> icon to use for the Knowledge Base link in the header.<br> Leave this field blank for no Knowledge Base icon.',
'KB_FONT_ICON_INVALID'                       => 'The Knowledge Base link icon contained invalid characters.',
'KB_CONFIG_EXPLAIN'                          => 'Depuis cette page il est possible de modifier les options générales.',
'KB_CONFIG_UPDATED'                          => 'Paramètres sauvegardés avec succès !',
'ANOUNCE'                                    => 'Annoncer les nouveaux d’articles sur le forum',
'ANOUNCE_EXPLAIN'                            => 'Permet de créer un sujet pour commenter l’article, celui-ci contient une brève description ainsi qu’un lien vers l’article nouvellement créé.<br>Sélectionner un forum dans lequel seront publiés les sujets annonçant les nouveaux articles, depuis la liste de l’option suivante (le menu déroulant s’affiche une fois cette option activée).',
'KB_FORUM_EXPLAIN'                           => 'Permet de sélectionner a forum dans lequel seront publiés les annonces des nouveaux articles publiés dans la base de connaissances.',
'PER_PAGE'                                   => 'Nombre d’articles sur la page',
'PER_PAGE_EXPLAIN'                           => 'Permet de saisir le nombre d’articles à afficher sur la page de gestion des articles et sur la page des résulats de la recherche.',
'SORT_TYPE'                                  => 'Sorting order',
'SORT_TYPE_EXPLAIN'                          => 'Method of sorting articles',
'FORCIBLY'                                   => 'forcibly',
'SELECTABLE'                                 => 'selectable',
'ALPHABET'                                   => 'alphabetic navigation',
'EXTENSION_GROUP_EXPLAIN'                    => 'Depuis cette page il est possible de gérer les extensions des fichiers joints autorisées. À gauche les extensions actives et à droite les extensions disponibles (inactives). Pour désactiver une extension, utiliser le menu déroulant sur la gauche en veillant à ne garder que les extensions actives sélectionnées. Utiliser la combinaison de la touche CTRL et du clic gauche pour sélectionner/désélectionner plus d’une extension. Pour ajouter de nouvelles extensions, il est nécessaire de se rendre sur la page de gestion des extensions dans l’onglet « MESSAGES », page « Gérer les extensions des fichiers joints ».',
'FORUM_PREFIX'                               => 'Forum Prefix',
'FORUM_PREFIX_EXPLAIN'                       => 'The text displayed before the forum name.<br>You can use html entities e.g. &amp;bull; &bull;&nbsp;&nbsp;&nbsp;&amp;laquo; &laquo;&nbsp;&nbsp;&nbsp;&amp;raquo; &raquo;',
'TOPIC_PREFIX'                               => 'Topic Prefix',
'TOPIC_PREFIX_EXPLAIN'                       => 'The text displayed before the topic name.<br>You can use html entities e.g. &amp;bull; &bull;&nbsp;&nbsp;&nbsp;&amp;laquo; &laquo;&nbsp;&nbsp;&nbsp;&amp;raquo; &raquo;',

//Manage category
'ACP_LIBRARY_MANAGE_EXPLAIN'                 => 'Depuis cette page il est possible de configurer un nombre illimité de catégories et sous-catégories. Il est possible de créer, modifier des catégories, rechercher leurs emplacements et de déplacer une catégorie dans une autre ou à la racine. Si le nombre d’articles d’une catégories ne correspond pas à la réalisé il est possible de resynchroniser le compte des articles pour chaque catégorie.',
'CATEGORY_LIST'                              => 'Liste des catégories',
'SELECT_CATEGORY'                            => 'Sélectionner une catégorie',
'SELECTION_CATEGORY'                         => 'Sélectionner une catégorie',
'ADD_CATEGORY'                               => 'Créer une catégorie',
'ADD_CATEGORY_EXPLAIN'                       => 'Permet de créer une nouvelle catégorie.',
'CATEGORY_ADDED'                             => 'Catégorie créée avec succès ! À présent il est possible de définir %sses permissions%s.',
'CATEGORY_DELETED'                           => 'Catégorie supprimée avec succès !',
'CATEGORY_EDITED'                            => 'Catégorie modifiée avec succès !',
'CAT_PARENT'                                 => 'Catégorie parente',
'CAT_NAME'                                   => 'Nom de la catégorie',
'CAT_DESCR'                                  => 'Description de la catégorie',
'COPY_CAT_PERMISSIONS'                       => 'Copier les permissions de categorie',
'COPY_CAT_PERMISSIONS_EXPLAIN'               => 'Permet de copier les permissions de la catégorie sélectionnée pour la catégorie en cours de création/modification.',
'KB_ROOT'                                    => 'Racine de la base de connaissances',
'NO_CAT_NAME'                                => 'Aucun nom pour la catégorie n’a été saisi.',
'NO_CAT_DESCR'                               => 'Aucune description pour la catégorie n’a été saisi.',
'DELETE_SUBCATS'                             => 'Supprimer les sous-catégories et leur contenu',
'DEL_CATEGORY'                               => 'Suppression de la catégorie',
'DEL_CATEGORY_EXPLAIN'                       => 'Depuis cette page il est possible de supprimer la catégorie. Il est possible de sélectionner vers quelle catégorie le contenu (articles et sous-catégories) sera déplacé.',
'LIBRARY_EDIT_CAT'                           => 'Modification de la catégorie',
'LIBRARY_EDIT_CAT_EXPLAIN'                   => 'Depuis cette page il est possible de renommer la catégorie, saisir une brève description et de déplacer la catégorie (et son contenu) vers une autre catégorie ou vers la racine.',
'MOVE_SUBCATS_TO'                            => 'Déplacer les sous-catégories et leur contenu',
'NO_CATS_IN_KB'                              => 'La base de connaissances n’a pas de catégories.',
'NO_DESTINATION_CATEGORY'                    => 'La catégorie de destination n’a pu être trouvée.',
'NO_PARENT'                                  => 'Aucune / À la racine de la base de connaissances',

//Article
'ARTICLE_MANAGE_EXPLAIN'                     => 'Depuis cette page il est possible de supprimer ou déplacer les articles vers d’autres catégories, ainsi que de voir et modifier les articles (dans une fenêtre séparée).',
'ARTICLE_MOVE_EXPLAIN'                       => 'Permet de sélectionner la catégorie dans laquelle déplacer l’article.',
'EDIT_DATE'                                  => 'Modifié',
'MOVE_ARTICLES_TO'                           => 'Déplacer les articles',
'DELETE_ALL_ARTICLES'                        => 'Supprimer les articles',
'NO_ARTICLES_IN_KB'                          => 'La base de connaissances n’a aucun article.',

//Permission
'ACP_LIBRARY_PERMISSIONS'                    => 'Permissions',
'ACP_LIBRARY_PERMISSIONS_MASK'               => 'Permissions trace',
'ACP_LIBRARY_PERMISSIONS_NO_CATS'            => 'Pour définir des permissions au moins une catégorie doit être créée.',
'ACP_LIBRARY_PERMISSIONS_EXPLAIN'            => 'Depuis cette page il est possible de modifier pour chaque membre ou groupe les permissions d’accès de chaque catégorie de la base de connaissances, ainsi que d’assigner des modétateurs des catégories. Les permissions administrateur sont disponibles sur la page par défaut de phpBB dans les modèles de permissions, onglet « Base de connaissances ».',
'ALL_CATS'                                   => 'Toutes les catégories',
'NO_COPY_PERMISSIONS'                        => 'Ne pas copier les permissions',
// User Permissions
'kb_u_add'                                   => 'Peut créer de nouveaux articles.',
'kb_u_edit'                                  => 'Peut modifier ses articles.',
'kb_u_delete'                                => 'Peut supprimer ses articles.',
'kb_u_add_noapprove'                         => 'Peut créer de nouveaux articles sans approbation.',
// Moderator Permissions
'kb_m_edit'                                  => 'Peut modifier les articles.',
'kb_m_delete'                                => 'Peut supprimer les articles.',
'kb_m_approve'                               => 'Peut approuver les articles.',

//Search
'YES_SEARCH_EXPLAIN'                         => 'Active la fonctionnalité de recherche, ce qui inclut la recherche des membres.',
'PER_PAGE_SEARCH'                            => 'Résultats de la recherche',
'PER_PAGE_SEARCH_EXPLAIN'                    => 'Permet de saisir le nombre d’éléments à afficher sur la page des résultats de la recherche.',
'SEARCH_TYPE_EXPLAIN'                        => 'Extension Knowledge Base vous permet de choisir la méthode d’indexation utilisée pour la recherche de texte dans le contenu des messages. Par défaut, la recherche utilisera la recherche FULLTEXT de Knowledge Base.',

//Attachments
'ATTACHMENTS_EXPLAIN'                        => 'Depuis cette page il est possible de voir et supprimer les fichiers joints dans les articles.',
'ACP_LIBRARY_ATTACHMENTS_LOST_FILES_EXPLAIN' => 'Depuis cette page il est possible de trouver les fichiers perdus sur le serveur. Si des fichiers sont absents du serveur leurs entrées correspondantes dans les articles seront supprimées.',
'PRUNE_ATTACHMENTS_EXPLAIN'                  => 'Depuis cette page il est possible de vérifier l’existence des fichiers supplémentaires sur le serveur. Si des fichiers existent ils seront supprimés. Confirmer cette action.',
'ORPHAN_EXPLAIN'                             => 'Depuis cette page il est possible de consulter la liste des fichiers joints orphelins. En général ces fichiers apparaissent car les membres ont envoyés des fichiers joints sans publier d’article. Il est possible de les supprimer ou de les rattacher à des articles existants. Pour cela il suffit de saisir l’ID de l’article pour lequel on souhaite rattacher le fichier joint orphelin.',
'FILES_DELETED_SUCCESS'                      => 'Les fichiers joints ont été supprimés avec succès !',
'NO_FILES_SELECTED'                          => 'Aucun fichier sélectionné.',
'PRUNE_ATTACHMENTS_FINISHED'                 => 'Aucun fichier supplémentaire n’a été trouvé.',
'PRUNE_ATTACHMENTS_PROGRESS'                 => 'Les fichiers inutiles sont vérifiés. Merci de ne pas interrompre ce processus !<br>Les fichiers suivants ont été supprimés :',
'PRUNE_ATTACHMENTS_FAIL'                     => '<br>La suppression des fichiers suivants a rencontré un problème, il n’a pas été possible de les supprimer :',
'POST_ROW_ARTICLE_INFO'                      => ' ayant l’ID %1$d…',
'RESYNC_ATTACHMENTS_FINISHED'                => 'Les fichiers joints ont été synchronisés avec succès (les entrées correspondantes dans la base de données ont été vérifiées)',
'RESYNC_ATTACHMENTS_PROGRESS'                => 'La vérification des entrées dans la base de données est en cours ! Merci de ne pas interrompre le processus !',
'SYNC_OK'                                    => 'Catégorie synchronisée avec succès !',
'THUMBNAIL_EXPLAIN'                          => 'Les dimensions des miniatures sont définies dans la page par défaut de phpBB dans le PCA (&laquo; Paramètres des fichiers joints &raquo;).',
'UPLOAD_DENIED_ARTICLE'                      => 'L’article ayant l’ID n’existe pas.',
'UPLOADING_FILE_TO_ARTICLE'                  => 'Envoi du fichier « %1$s » pour l’article',

//Log
'ACP_LIBRARY_LOGS_EXPLAIN'                   => 'Depuis cette page il est possible de consulter l’ensemble des actions effectuées concernant la base de connaissances. Il est possible de trier selon le nom d’utilisateur, la date, l’adresse IP et l’action. Enfin, il est possible de supprimer des entrées du journal individuellement ou dans son ensemble.',
'LOG_CLEAR_KB'                               => '<strong>Journal des actions néttoyé</strong>',
'LOG_CATS_MOVE_DOWN'                         => '<strong>Catégorie déplacée</strong> %1$s <strong>en dessous de</strong> %2$s',
'LOG_CATS_MOVE_UP'                           => '<strong>Catégorie déplacée</strong> %1$s <strong>au-dessus de</strong> %2$s',
'LOG_CATS_ADD'                               => '<strong>Catégorie créée</strong><br> %s',
'LOG_CATS_DEL_ARTICLES'                      => '<strong>Articles de catégorie supprimés</strong><br> %s',
'LOG_CATS_DEL_MOVE_POSTS_MOVE_CATS'          => '<strong>Catégorie supprimée</strong> %3$s, <strong>Articles déplacés vers</strong> %1$s <strong>et sous-catégories vers</strong> % 2$s',
'LOG_CATS_DEL_MOVE_POSTS'                    => '<strong>Catégorie supprimée</strong> %2$s<br><strong>et articles déplacés dans</strong> % 1$s',
'LOG_CATS_DEL_CAT'                           => '<strong>Catégorie supprimée</strong><br> %s',
'LOG_CATS_DEL_MOVE_POSTS_CATS'               => '<strong>Catégorie supprimée</strong> %2$s<br><strong>et sous-catégorie et articlés déplacés vers</strong> %1$s',
'LOG_CATS_DEL_POSTS_MOVE_CATS'               => '<strong>Catégorie supprimée</strong> %2$s <strong>avec articles, sous-catégories déplacées vers</strong> %1$s',
'LOG_CATS_DEL_POSTS_CATS'                    => '<strong>Catégorie supprimée avec articles et sous-catégories</strong><br> %s',
'LOG_CATS_DEL_CATS'                          => '<strong>Catégorie supprimée</strong> %2$s <strong>et sous-catégories déplacés vers</strong> %1$s',
'LOG_CATS_EDIT'                              => '<strong>Catégorie modifiée</strong><br> %1$s',
'LOG_CATS_CAT_MOVED_TO'                      => '<strong>Catégorie</strong> %1$s <strong>déplacée vers</strong> %2$s',
'LOG_CATS_SYNC'                              => '<strong>Catégorie resynchronisée</strong><br> %1s',
'LOG_KB_CONFIG_SEARCH'                       => '<strong>Les paramètres de la recherche de la base de connaissances ont été sauvegardés avec succès !</strong>',
'LOG_LIBRARY_ADD_ARTICLE'                    => 'Article créé &laquo;<strong>%1s</strong>&raquo; dans la catégorie<br> <strong>%2s</strong>',
'LOG_LIBRARY_DEL_ARTICLE'                    => 'Article supprimé &laquo;<strong>%1s</strong>&raquo; de la catégorie<br> <strong>%2s</strong>',
'LOG_LIBRARY_EDIT_ARTICLE'                   => 'Article modifié &laquo;<strong>%1s</strong>&raquo; dans la catégorie<br> <strong>%2s</strong>',
'LOG_LIBRARY_MOVED_ARTICLE'                  => 'Article déplacé <strong>%1s</strong> de la catégorie <strong>%2s</strong><br>vers la catégorie <strong>%3s</strong>',
'LOG_LIBRARY_APPROVED_ARTICLE'               => 'Article approuvé <strong>%1s</strong> dans la catégorie <strong>%2s</strong><br>créé par le membre <strong>%3s</strong>',
'LOG_LIBRARY_REJECTED_ARTICLE'               => 'Article refusé <strong>%1s</strong> dans la catégorie <strong>%2s</strong><br>créé par le membre <strong>%3s</strong>',
'LOG_LIBRARY_PERMISSION_DELETED'             => 'Accès à la catégorie retiré pour le groupe/membre <strong>%1s</strong><br> %2s',
'LOG_LIBRARY_PERMISSION_ADD'                 => 'Accès à la catégorie ajouté/modifié pour le groupe/membre <strong>%1s</strong><br> %2s',
'LOG_LIBRARY_CONFIG'                         => '<strong>Base de connaissances reconfigurée</strong>',

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

'KNOWLEDGE_BASE' => 'Base de connaissances',
]);
