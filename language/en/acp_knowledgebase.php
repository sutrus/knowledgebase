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
'KB_NOT_WRITABLE'         => 'The "files" or "plupload" folders cannot be automatically set to write permissions.<br>Please set the folders permissions to 0777 manually.',
'KB_FONT_ICON'            => 'Knowledge Base link icon',
'KB_FONT_ICON_EXPLAIN'    => 'Enter the name of a <a href="https://fontawesome.com/v4.7.0/icons/" target="_blank"><strong>Font Awesome</strong></a> icon to use for the Knowledge Base link in the header.<br> Leave this field blank for no Knowledge Base icon.',
'KB_FONT_ICON_INVALID'    => 'The Knowledge Base link icon contained invalid characters.',
'KB_CONFIG_EXPLAIN'       => 'Here you can set the basic settings.',
'KB_CONFIG_UPDATED'       => 'Settings successfully updated.',
'ANOUNCE'                 => 'Announce article at the conference',
'ANOUNCE_EXPLAIN'         => 'If selected, after the addition of articles on the conference will be automatically created topic with a brief article description and link to this.<br>Choose forum, which will be created announcements, from the list below (will be available at activation options).',
'KB_FORUM_EXPLAIN'        => 'Select a forum in which to set up announcements of articles.',
'PER_PAGE'                => 'The number of articles on the page',
'PER_PAGE_EXPLAIN'        => 'The number of articles on the management page articles and view page search.',
'SORT_TYPE'               => 'Sorting order',
'SORT_TYPE_EXPLAIN'       => 'Method of sorting articles',
'FORCIBLY'                => 'forcibly',
'SELECTABLE'              => 'selectable',
'ALPHABET'                => 'alphabetic navigation',
'EXTENSION_GROUP_EXPLAIN' => 'Allows to manage permissions for extensions od attachment files. To delete extensions, select only these you want keep actves in le drop down menu on the left side. Use CTRL + click to select more than one extension. To add a new extension, use the default phpBB page in ACP.',
'FORUM_PREFIX'            => 'Forum Prefix',
'FORUM_PREFIX_EXPLAIN'    => 'The text displayed before the forum name.<br>You can use html entities e.g. &amp;bull; &bull;&nbsp;&nbsp;&nbsp;&amp;laquo; &laquo;&nbsp;&nbsp;&nbsp;&amp;raquo; &raquo;',
'TOPIC_PREFIX'            => 'Topic Prefix',
'TOPIC_PREFIX_EXPLAIN'    => 'The text displayed before the topic name.<br>You can use html entities e.g. &amp;bull; &bull;&nbsp;&nbsp;&nbsp;&amp;laquo; &laquo;&nbsp;&nbsp;&nbsp;&amp;raquo; &raquo;',

//Manage category
'ACP_LIBRARY_MANAGE_EXPLAIN'   => 'Each category can have an unlimited number of subcategories. Here you can add, edit, move, search places and move from one category to another. If the number of entries in the category does not coincide with the real, you can synchronize the category.',
'CATEGORY_LIST'                => 'Categories list',
'SELECT_CATEGORY'              => 'Select a category',
'SELECTION_CATEGORY'           => 'Select a category',
'ADD_CATEGORY'                 => 'Create a new category',
'ADD_CATEGORY_EXPLAIN'         => 'Create a new category',
'CATEGORY_ADDED'               => 'Category successfully added. Now you can %sset permission%s to this category.',
'CATEGORY_DELETED'             => 'Category deleted successfully.',
'CATEGORY_EDITED'              => 'Category successfully edited',
'CAT_PARENT'                   => 'Parent Category',
'CAT_NAME'                     => 'Category name',
'CAT_DESCR'                    => 'Category description',
'COPY_CAT_PERMISSIONS'         => 'Copy category permissions',
'COPY_CAT_PERMISSIONS_EXPLAIN' => 'Allows to copy category permissions to the current category.',
'KB_ROOT'                      => 'Root category',
'NO_CAT_NAME'                  => 'You have not specified category name.',
'NO_CAT_DESCR'                 => 'You have not created category description.',
'DELETE_SUBCATS'               => 'Delete categories and articles',
'DEL_CATEGORY'                 => 'Delete category',
'DEL_CATEGORY_EXPLAIN'         => 'This form below allows you delete a category. You can decide where to move all the articles in it or subcategory.',
'LIBRARY_EDIT_CAT'             => 'Edit the category',
'LIBRARY_EDIT_CAT_EXPLAIN'     => 'Here you can rename the category, give it a brief description and move to another category (with the content).',
'MOVE_SUBCATS_TO'              => 'Move subcategories',
'NO_CATS_IN_KB'                => 'Knowledge Base has no categories.',
'NO_DESTINATION_CATEGORY'      => 'Category recipient could not be found',
'NO_PARENT'                    => 'No parent',

//Article
'ARTICLE_MANAGE_EXPLAIN' => 'Here you can delete articles or move them to other categories, as well as view or edit them (in a separate window).',
'ARTICLE_MOVE_EXPLAIN'   => 'Select category to which you want move article.',
'EDIT_DATE'              => 'Edited',
'MOVE_ARTICLES_TO'       => 'Move article',
'DELETE_ALL_ARTICLES'    => 'Delete article',
'NO_ARTICLES_IN_KB'      => 'The Knowledge Base currently no articles.',

//Permission
'ACP_LIBRARY_PERMISSIONS'         => 'Permissions',
'ACP_LIBRARY_PERMISSIONS_MASK'    => 'Permissions trace',
'ACP_LIBRARY_PERMISSIONS_NO_CATS' => 'To set permissions, you must create at least one category.',
'ACP_LIBRARY_PERMISSIONS_EXPLAIN' => 'Here you can change for each user and group access to each category of the library. To assign moderators or administrator rights to use the definition of the relevant page.',
'ALL_CATS'                        => 'All Categories',
'NO_COPY_PERMISSIONS'             => 'Don’t copy permissions',
// User Permissions
'kb_u_add'                        => 'Can add articles',
'kb_u_edit'                       => 'Can edit own articles',
'kb_u_delete'                     => 'Can delete own articles',
'kb_u_add_noapprove'              => 'Can add / modify articles without prior approval',
// Moderator Permissions
'kb_m_edit'                       => 'Can edit articles',
'kb_m_delete'                     => 'Can delete articles',
'kb_m_approve'                    => 'Can approve articles',

//Search
'YES_SEARCH_EXPLAIN'      => 'Enables user facing search functionality including member search.',
'PER_PAGE_SEARCH'         => 'Search Results',
'PER_PAGE_SEARCH_EXPLAIN' => 'The number of items displayed on the search results page.',
'SEARCH_TYPE_EXPLAIN'     => 'Extension Knowledge Base allows you to choose the backend that is used for searching text in post contents. By default the search will use Knowledge Base own fulltext search.',

//Attachments
'ATTACHMENTS_EXPLAIN'                        => 'Allows to see and delete attachment files in articles.',
'ACP_LIBRARY_ATTACHMENTS_LOST_FILES_EXPLAIN' => 'Allows to find lost files on server. If files aren’t present on server entries will be deleted.',
'PRUNE_ATTACHMENTS_EXPLAIN'                  => 'It will be verify the existance of orphean files on server. If the file exists, it will be deleted. Would you confirm?',
'ORPHAN_EXPLAIN'                             => 'Allows to see lost files. In general these files are displayed here because users send files but don’t publish articles. It possible to delete these files or attach these to articles. You must enter the ID article to attach a lost file to an article.',
'FILES_DELETED_SUCCESS'                      => 'Attachment files deleted successfully!',
'NO_FILES_SELECTED'                          => 'No file selected.',
'PRUNE_ATTACHMENTS_FINISHED'                 => 'No supplementary files was found.',
'PRUNE_ATTACHMENTS_PROGRESS'                 => 'Unused files are checked. Don’t stop the process!<br>The next files were deleted:',
'PRUNE_ATTACHMENTS_FAIL'                     => '<br>It was impossible to delete the next files:',
'POST_ROW_ARTICLE_INFO'                      => ' with number %1$d…',
'RESYNC_ATTACHMENTS_FINISHED'                => 'The attachment files were successfully synchronized (verification of the accuracy of entries in the database)',
'RESYNC_ATTACHMENTS_PROGRESS'                => 'The verification of entries in the database is in progress! Don’t stop the process!',
'SYNC_OK'                                    => 'Category successfully synchronized.',
'THUMBNAIL_EXPLAIN'                          => 'The thumbnails sizes are defined in default phpBB page in ACP (&laquo;Attachment files settings&raquo;).',
'UPLOAD_DENIED_ARTICLE'                      => 'Article with this ID doesn’t exist.',
'UPLOADING_FILE_TO_ARTICLE'                  => 'Upload file “%1$s” to article',

//Log
'ACP_LIBRARY_LOGS_EXPLAIN'          => 'This is a list of actions performed with the library. You can sort the list by user name, date, IP-address or action. You can delete individual entries or clear the entire log as a whole.',
'LOG_CLEAR_KB'                      => '<strong>Cleaned logs library </strong>',
'LOG_CATS_MOVE_DOWN'                => '<strong>Moved category</strong> %1$s <strong>under</strong> %2$s',
'LOG_CATS_MOVE_UP'                  => '<strong>Moved category</strong> %1$s <strong>on</strong> %2$s',
'LOG_CATS_ADD'                      => '<strong>Add category</strong><br> %s',
'LOG_CATS_DEL_ARTICLES'             => '<strong>Remove Category articles</strong><br> %s',
'LOG_CATS_DEL_MOVE_POSTS_MOVE_CATS' => '<strong>Remove Category</strong> %3$s, <strong>Article moved to</strong> %1$s <strong>and subcategories</strong> % 2$s',
'LOG_CATS_DEL_MOVE_POSTS'           => '<strong>Remove Category</strong> %2$s<br><strong>and moved to an article in</strong> % 1$s',
'LOG_CATS_DEL_CAT'                  => '<strong>Remove Category</strong><br> %s',
'LOG_CATS_DEL_MOVE_POSTS_CATS'      => '<strong>Remove Category </strong> %2$s<br><strong>with subcategories, articles moved to</strong> %1$s',
'LOG_CATS_DEL_POSTS_MOVE_CATS'      => '<strong>Remove Category </strong> %2$s <strong>with articles, subcategory moved to</strong> %1$s',
'LOG_CATS_DEL_POSTS_CATS'           => '<strong>Remove Category with articles and subcategories</strong><br> %s',
'LOG_CATS_DEL_CATS'                 => '<strong>Remove Category</strong> %2$s <strong>and subcategories moved to</strong> %1$s',
'LOG_CATS_EDIT'                     => '<strong>Changed category information</strong><br> %1$s',
'LOG_CATS_CAT_MOVED_TO'             => '<strong>Category</strong> %1$s <strong>moved to</strong> %2$s',
'LOG_CATS_SYNC'                     => '<strong>Synchronized category</strong><br> %1s',
'LOG_KB_CONFIG_SEARCH'              => '<strong>Knowledge Base Search settings changed</strong>',
'LOG_LIBRARY_ADD_ARTICLE'           => 'Added article &laquo;<strong>%1s</strong>&raquo; in category<br> <strong>%2s</strong>',
'LOG_LIBRARY_DEL_ARTICLE'           => 'Removed article &laquo;<strong>%1s</strong>&raquo; from category<br> <strong>%2s</strong>',
'LOG_LIBRARY_EDIT_ARTICLE'          => 'Edited article &laquo;<strong>%1s</strong>&raquo; in category<br> <strong>%2s</strong>',
'LOG_LIBRARY_MOVED_ARTICLE'         => 'Moved article <strong>%1s</strong> from category <strong>%2s</strong><br>to category <strong>%3s</strong>',
'LOG_LIBRARY_APPROVED_ARTICLE'      => 'Approved article <strong>%1s</strong> in category <strong>%2s</strong><br>created by user <strong>%3s</strong>',
'LOG_LIBRARY_REJECTED_ARTICLE'      => 'Rejected article <strong>%1s</strong> in category <strong>%2s</strong><br>created by user <strong>%3s</strong>',
'LOG_LIBRARY_PERMISSION_DELETED'    => 'Remove user/group access to category <strong>%1s</strong><br> %2s',
'LOG_LIBRARY_PERMISSION_ADD'        => 'Adding or changing user/group access to category <strong>%1s</strong><br> %2s',
'LOG_LIBRARY_CONFIG'                => '<strong>Reconfigured library</strong>',

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

'KNOWLEDGE_BASE'                          => 'Knowledge Base',
));
