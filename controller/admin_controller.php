<?php

/**
 *
 * Knowledge base. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2017, Sheer
 * @license       GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace sheer\knowledgebase\controller;

use Exception;
use phpbb\auth\auth;
use phpbb\cache\driver\driver_interface as cache_interface;
use phpbb\config\config;
use phpbb\config\db_text;
use phpbb\controller\helper;
use phpbb\db\driver\driver_interface;
use phpbb\di\service_collection;
use phpbb\extension\manager;
use phpbb\group\helper as group_helper;
use phpbb\language\language;
use phpbb\log\log;
use phpbb\pagination;
use phpbb\request\request_interface;
use phpbb\template\template;
use phpbb\user;
use RuntimeException;
use sheer\knowledgebase\inc\functions_kb;
use sheer\knowledgebase\search\kb_search_backend_factory;

class admin_controller
{
	protected const STATE_SEARCH_TYPE = 0;
	protected const STATE_ACTION = 1;
	protected const STATE_POST_COUNTER = 2;

	/** @var driver_interface */
	protected driver_interface $db;

	/** @var config */
	protected config $config;

	/** @var db_text */
	protected db_text $config_text;

	/** @var helper */
	protected helper $helper;

	/** @var manager */
	protected manager $ext_manager;

	/** @var language */
	protected language $language;

	/** @var auth */
	protected auth $auth;

	/** @var request_interface */
	protected request_interface $request;

	/** @var template */
	protected template $template;

	/** @var user */
	protected user $user;

	/** @var cache_interface */
	protected cache_interface $cache;

	/** @var group_helper */
	protected group_helper $group_helper;

	/** @var pagination */
	protected pagination $pagination;

	/** @var log */
	protected log $log;

	/** @var functions_kb */
	protected functions_kb $kb;

	/** @var service_collection */
	protected service_collection $search_collection;

	/** @var kb_search_backend_factory */
	protected kb_search_backend_factory $search_factory;

	/** @var string */
	protected string $phpbb_root_path;

	/** @var string */
	protected string $php_ext;

	/** @var string */
	protected string $articles_table;

	/** @var string */
	protected string $categories_table;

	/** @var string */
	protected string $logs_table;

	/** @var string */
	protected string $attachments_table;

	/** @var string */
	protected string $options_table;

	/** @var string */
	protected string $kb_users_table;

	/** @var string */
	protected string $kb_groups_table;

	/** @var string */
	protected string $upload_dir;

	/** @var string */
	protected string $u_action;

	/**
	 * Constructor
	 *
	 * @param driver_interface          $db
	 * @param config                    $config
	 * @param db_text                   $config_text
	 * @param helper                    $helper
	 * @param manager                   $ext_manager
	 * @param language                  $language
	 * @param auth                      $auth
	 * @param request_interface         $request
	 * @param template                  $template
	 * @param user                      $user
	 * @param cache_interface           $cache
	 * @param group_helper              $group_helper
	 * @param pagination                $pagination
	 * @param log                       $log
	 * @param functions_kb              $kb
	 * @param service_collection        $search_collection
	 * @param kb_search_backend_factory $search_factory
	 * @param string                    $phpbb_root_path
	 * @param string                    $php_ext
	 * @param string                    $articles_table
	 * @param string                    $categories_table
	 * @param string                    $logs_table
	 * @param string                    $attachments_table
	 * @param string                    $options_table
	 * @param string                    $kb_users_table
	 * @param string                    $kb_groups_table
	 */
	public function __construct(
		driver_interface $db, config $config, db_text $config_text, helper $helper, manager $ext_manager,
		language $language, auth $auth, request_interface $request, template $template, user $user,
		cache_interface $cache, group_helper $group_helper, pagination $pagination, log $log, functions_kb $kb,
		service_collection $search_collection, kb_search_backend_factory $search_factory,
		string $phpbb_root_path, string $php_ext, string $articles_table, string $categories_table,
		string $logs_table, string $attachments_table, string $options_table, string $kb_users_table,
		string $kb_groups_table
	)
	{
		$this->db = $db;
		$this->config = $config;
		$this->config_text = $config_text;
		$this->helper = $helper;
		$this->ext_manager = $ext_manager;
		$this->language = $language;
		$this->auth = $auth;
		$this->request = $request;
		$this->template = $template;
		$this->user = $user;
		$this->cache = $cache;
		$this->group_helper = $group_helper;
		$this->pagination = $pagination;
		$this->log = $log;
		$this->kb = $kb;
		$this->search_collection = $search_collection;
		$this->search_factory = $search_factory;
		$this->phpbb_root_path = $phpbb_root_path;
		$this->php_ext = $php_ext;
		$this->articles_table = $articles_table;
		$this->categories_table = $categories_table;
		$this->logs_table = $logs_table;
		$this->attachments_table = $attachments_table;
		$this->options_table = $options_table;
		$this->kb_users_table = $kb_users_table;
		$this->kb_groups_table = $kb_groups_table;

		$this->upload_dir = $this->ext_manager->get_extension_path('sheer/knowledgebase', true) . 'files/';
	}


	/** Settings management functions
	 *
	 * @return void
	 * @access public
	 */
	public function settings(): void
	{
		$form_key = 'sheer/knowledgebase';
		add_form_key($form_key);
		if ($this->request->is_set_post('submit'))
		{
			if (!check_form_key($form_key))
			{
				trigger_error($this->language->lang('FORM_INVALID') . adm_back_link($this->u_action), E_USER_WARNING);
			}

			$this->set_settings();

			$this->log->add('admin', $this->user->data['user_id'], $this->user->data['user_ip'], 'LOG_LIBRARY_CONFIG', time());
			meta_refresh(2, $this->u_action);
			trigger_error($this->language->lang('CONFIG_UPDATED') . adm_back_link($this->u_action));
		}

		// Check upload directory
		$plupload_dir = $this->upload_dir . 'plupload/';
		$diru_exists = is_dir($this->upload_dir) || (mkdir($this->upload_dir, 0777, true) && is_dir($this->upload_dir));
		$dirp_exists = is_dir($plupload_dir) || (mkdir($plupload_dir, 0777, true) && is_dir($plupload_dir));
		$dirIsWritable = false;
		if ($diru_exists && $dirp_exists)
		{
			chmod($this->upload_dir, 0777);
			chmod($plupload_dir, 0777);
			if (is_writable($this->upload_dir))
			{
				$tempFile = tempnam($this->upload_dir, 'tmp');
				if ($tempFile !== false)
				{
					$res = file_put_contents($tempFile, 'test');
					$dirIsWritable = $res !== false;
					unlink($tempFile);
				}
			}
		}

		$max_filesize = get_formatted_filesize($this->config['kb_max_filesize'], false, ['mb', 'kb', 'b']);
		$identifier = $max_filesize['si_identifier'];
		$max_filesize = $max_filesize['value'];

		// Create extension list
		$all_ext = [];
		$sql = 'SELECT e.extension, g.group_name
			FROM ' . EXTENSIONS_TABLE . ' e, ' . EXTENSION_GROUPS_TABLE . ' g
			WHERE e.group_id = g.group_id';
		$this->db->sql_query($sql);
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$all_ext[$row['group_name']][] = $row['extension'];
		}
		$this->db->sql_freeresult($result);

		$extensions = json_decode($this->config_text->get('kb_extensions'), true);

		foreach ($all_ext as $group_name => $ext)
		{
			$extensions[$group_name] = empty($extensions[$group_name]) ? [] : $extensions[$group_name];
			$disabled_ext = array_diff($ext, $extensions[$group_name]);
			$assigned_ext = array_diff($ext, $disabled_ext);

			$s_options = $disabled_extensions = '';
			foreach ($disabled_ext as $disabled)
			{
				$disabled_extensions .= '<option value="' . $disabled . '">' . $disabled . '</option>';
			}
			foreach ($assigned_ext as $assigned)
			{
				$s_options .= '<option value="' . $assigned . '" selected="selected">' . $assigned . '</option>';
			}

			$this->template->assign_block_vars('row', [
				'GROUP'                => $group_name,
				'EXTENSIONS_GROUP'     => $this->language->is_set('EXT_GROUP_' . $group_name) ? $this->language->lang('EXT_GROUP_' . $group_name) : $group_name,
				'S_OPTIONS'            => $s_options,
				'DIASABLED_EXTENSIONS' => $disabled_extensions,
				'ASSIGNED_EXTENSIONS'  => implode(', ', $assigned_ext),
			]);
		}

		$this->template->assign_vars([
			'S_NOT_WRITABLE'           => !$dirIsWritable,
			'S_EXT_GROUP_SIZE_OPTIONS' => size_select_options($identifier),
			'MAX_ATTACHMENTS'          => $this->config['kb_max_attachments'],
			'EXTGROUP_FILESIZE'        => $max_filesize,
			'ADVANCED_FORM_ON'         => $this->config['kb_anounce'] ? 'checked="checked"' : '',
			'ADVANCED_FORM'            => $this->config['kb_anounce'] ? '' : 'none',
			'PER_PAGE'                 => $this->config['kb_articles_per_page'] ?? 10,
			'S_YES_ATTACH'             => (bool) $this->config['kb_allow_attachments'],
			'S_YES_THUMBNAIL'          => (bool) $this->config['kb_allow_thumbnail'],
			'S_FORUM_POST'             => make_forum_select($this->config['kb_forum_id'], 0, true, true, false),
			'S_FORUM_PREFIX'           => $this->config_text->get('kb_forum_prefix'),
			'S_TOPIC_PREFIX'           => $this->config_text->get('kb_topic_prefix'),
			'S_FORCIBLY'               => $this->config['kb_sort_type'] == 0,
			'S_ALPHABET'               => $this->config['kb_sort_type'] == -1,
			'S_SELECTABLE'             => $this->config['kb_sort_type'] == 1,
			'KB_FONT_ICON'             => $this->config['kb_font_icon'],
			'S_ACTION'                 => $this->u_action,
		]);
	}

	/** Set the options a user can configure
	 *
	 * @return void
	 * @access protected
	 */
	protected function set_settings(): void
	{
		// Create extension array
		$extension_list = $this->request->variable('extensions', ['' => ['']]);
		$disabled_list = $this->request->variable('diasabled_extensions', ['' => ['']]);
		$extension_list = array_merge_recursive($extension_list, $disabled_list);

		// Validate font icon field characters
		$kb_font_icon = $this->request->variable('kb_font_icon', '');
		if (!empty($kb_font_icon) && !preg_match('/^[a-z0-9-]+$/', $kb_font_icon))
		{
			trigger_error($this->language->lang('KB_FONT_ICON_INVALID') . adm_back_link($this->u_action), E_USER_WARNING);
		}

		// Max file size
		$max_filesize = (int) $this->request->variable('max_filesize', 0);
		$size_select = $this->request->variable('size_select', 'b');
		$max_filesize = ($size_select == 'kb') ? round($max_filesize * 1024) : (($size_select == 'mb') ? round($max_filesize * 1048576) : $max_filesize);

		// Save config value
		$this->config->set('kb_font_icon', $kb_font_icon);

		$this->config->set('kb_anounce', $this->request->variable('anounce', 0));
		$this->config->set('kb_forum_id', $this->request->variable('forum_id', 0));
		$this->config_text->set('kb_forum_prefix', utf8_normalize_nfc($this->request->variable('forum_prefix', '', true)));
		$this->config_text->set('kb_topic_prefix', utf8_normalize_nfc($this->request->variable('topic_prefix', '', true)));
		$this->config->set('kb_articles_per_page', $this->request->variable('articles_per_page', 15));
		$this->config->set('kb_sort_type', $this->request->variable('sort_type', 0));
		$this->config->set('kb_allow_attachments', $this->request->variable('allow_attachments', 0));
		$this->config->set('kb_allow_thumbnail', $this->request->variable('thumbnail', 0));
		$this->config->set('kb_max_attachments', $this->request->variable('max_attachments', 2));
		$this->config->set('kb_max_filesize', $max_filesize, 0);
		$this->config_text->set('kb_extensions', json_encode($extension_list));
	}
	/** END - Settings management functions */


	/** Attachments management functions
	 *
	 * @return void
	 * @access public
	 */
	public function main(): void
	{
		$submit = $this->request->is_set_post('submit');

		$form_key = 'acp_attach';
		add_form_key($form_key);

		if ($submit && !check_form_key($form_key))
		{
			trigger_error($this->language->lang('FORM_INVALID') . adm_back_link($this->u_action), E_USER_WARNING);
		}

		if ($submit)
		{
			$delete_files = ($this->request->is_set_post('delete')) ? array_keys($this->request->variable('delete', ['' => 0])) : [];
			if (count($delete_files))
			{
				$attachments_list = $attachments_ids = [];
				$sql = 'SELECT attach_id, physical_filename
					FROM ' . $this->attachments_table . '
					WHERE ' . $this->db->sql_in_set('attach_id', $delete_files);
				$result = $this->db->sql_query($sql);
				while ($attach_row = $this->db->sql_fetchrow($result))
				{
					$attachments_list[] = $attach_row['physical_filename'];
					$attachments_ids[] = $attach_row['attach_id'];
				}

				foreach ($attachments_list as $attachments)
				{
					unlink($this->upload_dir . $attachments);
					unlink($this->upload_dir . 'thumb_' . $attachments);
				}
				$sql = 'DELETE FROM  ' . $this->attachments_table . '
					WHERE ' . $this->db->sql_in_set('attach_id', $attachments_ids);
				$this->db->sql_query($sql);
				meta_refresh(2, $this->u_action);
				trigger_error($this->language->lang('FILES_DELETED_SUCCESS') . adm_back_link($this->u_action));
			}
			else
			{
				trigger_error($this->language->lang('NO_FILES_SELECTED') . adm_back_link($this->u_action), E_USER_WARNING);
			}
		}

		$start = $this->request->variable('start', 0);
		// Sort keys
		$sort_days = $this->request->variable('st', 0);
		$sort_key = $this->request->variable('sk', 't');
		$sort_dir = $this->request->variable('sd', 'd');

		// Sorting
		$limit_days = [
			0   => $this->language->lang('ALL_ENTRIES'),
			1   => $this->language->lang('1_DAY'),
			7   => $this->language->lang('7_DAYS'),
			14  => $this->language->lang('2_WEEKS'),
			30  => $this->language->lang('1_MONTH'),
			90  => $this->language->lang('3_MONTHS'),
			180 => $this->language->lang('6_MONTHS'),
			365 => $this->language->lang('1_YEAR'),
		];
		$sort_by_text = [
			'f' => $this->language->lang('FILENAME'),
			't' => $this->language->lang('FILEDATE'),
			's' => $this->language->lang('FILESIZE'),
			'x' => $this->language->lang('EXTENSION'),
			'u' => $this->language->lang('AUTHOR'),
		];
		$sort_by_sql = [
			'f' => 'a.real_filename',
			't' => 'a.filetime',
			's' => 'a.filesize',
			'x' => 'a.extension',
			'u' => 'u.username',
		];

		$s_limit_days = $s_sort_key = $s_sort_dir = $u_sort_param = '';
		gen_sort_selects($limit_days, $sort_by_text, $sort_days, $sort_key, $sort_dir, $s_limit_days, $s_sort_key, $s_sort_dir, $u_sort_param);

		$min_filetime = ($sort_days) ? (time() - ($sort_days * 86400)) : '';
		$limit_filetime = ($min_filetime) ? ' AND a.filetime >= ' . $min_filetime : '';
		$start = ($sort_days && $this->request->is_set_post('sort')) ? 0 : $start;

		$attachments_per_page = (int) $this->config['topics_per_page'];

		$stats = $this->get_kb_attachment_stats($limit_filetime);
		$num_files = $stats['num_files'];
		$total_size = $stats['upload_dir_size'];

		// If the user is trying to reach the second half of the attachments list, fetch it starting from the end
		$store_reverse = false;
		$sql_limit = $attachments_per_page;

		if ($start > $num_files / 2)
		{
			$store_reverse = true;

			// Select the sort order. Add time sort anchor for non-time sorting cases
			$sql_sort_anchor = ($sort_key != 't') ? ', a.filetime ' . (($sort_dir == 'd') ? 'ASC' : 'DESC') : '';
			$sql_sort_order = $sort_by_sql[$sort_key] . ' ' . (($sort_dir == 'd') ? 'ASC' : 'DESC') . $sql_sort_anchor;
			$sql_limit = $this->pagination->reverse_limit($start, $sql_limit, $num_files);
			$sql_start = $this->pagination->reverse_start($start, $sql_limit, $num_files);
		}
		else
		{
			// Select the sort order. Add time sort anchor for non-time sorting cases
			$sql_sort_anchor = ($sort_key != 't') ? ', a.filetime ' . (($sort_dir == 'd') ? 'DESC' : 'ASC') : '';
			$sql_sort_order = $sort_by_sql[$sort_key] . ' ' . (($sort_dir == 'd') ? 'DESC' : 'ASC') . $sql_sort_anchor;
			$sql_start = $start;
		}

		$attachments_list = [];

		$sql = 'SELECT a.*, u.username, u.user_colour, t.article_title
			FROM ' . $this->attachments_table . ' a
			LEFT JOIN ' . USERS_TABLE . ' u ON (u.user_id = a.poster_id)
			LEFT JOIN ' . $this->articles_table . ' t ON (a.article_id = t.article_id)
			WHERE a.is_orphan = 0 ' . $limit_filetime . '
			ORDER BY ' . $sql_sort_order;

		$result = $this->db->sql_query_limit($sql, $sql_limit, $sql_start);

		$i = ($store_reverse) ? $sql_limit - 1 : 0;

		// Store increment value in a variable to save some conditional calls
		$i_increment = ($store_reverse) ? -1 : 1;
		while ($attach_row = $this->db->sql_fetchrow($result))
		{
			$attachments_list[$i] = $attach_row;
			$i = $i + $i_increment;
		}
		$this->db->sql_freeresult($result);

		$base_url = $this->u_action . '&amp;' . $u_sort_param;

		for ($i = 0, $end = count($attachments_list); $i < $end; $i++)
		{
			$row = $attachments_list[$i];
			$img_src = ($this->kb->check_is_img($row['extension'])) ? '<span class="kb_preview"><img alt="" src="' . $this->helper->route('sheer_knowledgebase_kb_file', ['id' => $row['attach_id']]) . '"></span>' : '';
			$this->template->assign_block_vars('attachments', [
					'REAL_FILENAME'     => utf8_basename($row['real_filename']),
					'FILETIME'          => $this->user->format_date($row['filetime']),
					'ATTACHMENT_POSTER' => get_username_string('full', $row['poster_id'], $row['username'], $row['user_colour']),
					'U_FILE'            => $this->helper->route('sheer_knowledgebase_kb_file', ['id' => $row['attach_id']]),
					'ATTACH_ID'         => $row['attach_id'],
					'S_IS_ORPHAN'       => $row['is_orphan'],
					'IMG_SRC'           => $img_src,
					'U_ARTICLE'         => $this->helper->route('sheer_knowledgebase_article', ['k' => $row['article_id']]),
					'ARTICLE_TITLE'     => $row['article_title'],
					'FILESIZE'          => get_formatted_filesize($row['filesize']),
				]
			);
		}

		$this->pagination->generate_template_pagination($base_url, 'pagination', 'start', $num_files, $attachments_per_page, $start);

		$this->template->assign_vars([
				'L_TITLE'         => $this->language->lang('ATTACHMENTS'),
				'L_TITLE_EXPLAIN' => $this->language->lang('ATTACHMENTS_EXPLAIN'),
				'S_ATTACHMENTS'   => true,
				'TOTAL_FILES'     => $num_files,
				'TOTAL_SIZE'      => get_formatted_filesize($total_size),
				'S_LIMIT_DAYS'    => $s_limit_days,
				'S_SORT_KEY'      => $s_sort_key,
				'S_SORT_DIR'      => $s_sort_dir,
				'S_ACTION'        => $this->u_action,
			]
		);
	}

	/**
	 * Get attachment file count and size of upload directory
	 *
	 * @param string $limit Additional limit for WHERE clause to filter stats by.
	 * @return array Returns array with stats: num_files and upload_dir_size
	 */
	protected function get_kb_attachment_stats(string $limit = ''): array
	{
		$sql = 'SELECT COUNT(a.attach_id) AS num_files, SUM(a.filesize) AS upload_dir_size
			FROM ' . $this->attachments_table . ' a
			WHERE a.is_orphan = 0 ' . $limit;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		return [
			'num_files'       => (int) $row['num_files'],
			'upload_dir_size' => (float) $row['upload_dir_size'],
		];
	}

	/**
	 * @return void
	 * @access public
	 */
	public function orphan(): void
	{
		$submit = $this->request->is_set_post('submit');

		if ($submit)
		{
			$delete_files = ($this->request->is_set_post('delete')) ? array_keys($this->request->variable('delete', ['' => 0])) : [];
			$add_files = ($this->request->is_set_post('add')) ? array_keys($this->request->variable('add', ['' => 0])) : [];
			$post_ids = $this->request->variable('post_id', ['' => 0]);

			if (count($delete_files))
			{
				$attachments_list = $attachments_ids = [];

				$sql = 'SELECT attach_id, physical_filename, real_filename
					FROM ' . $this->attachments_table . '
					WHERE ' . $this->db->sql_in_set('attach_id', $delete_files);
				$result = $this->db->sql_query($sql);
				while ($attach_row = $this->db->sql_fetchrow($result))
				{
					$attachments_list[] = $attach_row['physical_filename'];
					$attachments_ids[] = $attach_row['attach_id'];
					$delete_files[$attach_row['attach_id']] = $attach_row['real_filename'];
				}

				foreach ($attachments_list as $attachments)
				{
					unlink($this->upload_dir . $attachments);
					unlink($this->upload_dir . 'thumb_' . $attachments);
				}
				$sql = 'DELETE FROM  ' . $this->attachments_table . '
					WHERE ' . $this->db->sql_in_set('attach_id', $attachments_ids);
				$this->db->sql_query($sql);
				meta_refresh(2, $this->u_action);
				trigger_error($this->language->lang('FILES_DELETED_SUCCESS') . adm_back_link($this->u_action));
			}

			$upload_list = [];
			foreach ($add_files as $attach_id)
			{
				if (!isset($delete_files[$attach_id]) && !empty($post_ids[$attach_id]))
				{
					$upload_list[$attach_id] = $post_ids[$attach_id];
				}
			}
			unset($add_files);

			if (count($upload_list))
			{
				$this->template->assign_var('S_UPLOADING_FILES', true);
				$sql = 'SELECT article_id
					FROM ' . $this->articles_table . '
					WHERE ' . $this->db->sql_in_set('article_id', $upload_list);
				$result = $this->db->sql_query($sql);

				$post_info = [];
				while ($row = $this->db->sql_fetchrow($result))
				{
					$post_info[$row['article_id']] = $row;
				}
				$this->db->sql_freeresult($result);

				// Select those attachments we want to change...
				$sql = 'SELECT *
					FROM ' . $this->attachments_table . '
					WHERE ' . $this->db->sql_in_set('attach_id', array_keys($upload_list)) . '
						AND is_orphan = 1';
				$result = $this->db->sql_query($sql);

				while ($row = $this->db->sql_fetchrow($result))
				{
					$post_row = $post_info[$upload_list[$row['attach_id']]];
					$mess = ($post_row['article_id']) ? sprintf($this->language->lang('POST_ROW_ARTICLE_INFO'), $post_row['article_id']) : '';
					$this->template->assign_block_vars('upload', [
						'FILE_INFO' => sprintf($this->language->lang('UPLOADING_FILE_TO_ARTICLE'), $row['real_filename']) . $mess,
						'S_DENIED'  => !$post_row['article_id'],
						'DENIED'    => $this->language->lang('UPLOAD_DENIED_ARTICLE'),
					]);

					if (!$post_row['article_id'])
					{
						continue;
					}

					// Adjust attachment entry
					$sql_ary = [
						'is_orphan'  => 0,
						'article_id' => $post_row['article_id'],
					];

					$sql = 'UPDATE ' . $this->attachments_table . '
						SET ' . $this->db->sql_build_array('UPDATE', $sql_ary) . '
						WHERE attach_id = ' . (int) $row['attach_id'];
					$this->db->sql_query($sql);
				}

				$this->db->sql_freeresult($result);
			}
		}
		// Just get the files with is_orphan set and older than 3 hours
		$sql = 'SELECT attach_id, real_filename, physical_filename, filesize, filetime, extension
			FROM ' . $this->attachments_table . '
			WHERE is_orphan = 1
				AND filetime < ' . (time() - 3 * 60 * 60) . '
			ORDER BY filetime DESC';
		$result = $this->db->sql_query($sql);

		while ($row = $this->db->sql_fetchrow($result))
		{
			$img_src = ($this->kb->check_is_img($row['extension'])) ? '<span class="kb_preview"><img alt="" src="' . $this->helper->route('sheer_knowledgebase_kb_file', ['id' => $row['attach_id']]) . '"></span>' : '';

			$this->template->assign_block_vars('orphan', [
					'FILESIZE'          => get_formatted_filesize($row['filesize']),
					'FILETIME'          => $this->user->format_date($row['filetime']),
					'REAL_FILENAME'     => utf8_basename($row['real_filename']),
					'PHYSICAL_FILENAME' => utf8_basename($row['physical_filename']),
					'ATTACH_ID'         => $row['attach_id'],
					'IMG_SRC'           => $img_src,
					'POST_IDS'          => (!empty($post_ids[$row['attach_id']])) ? $post_ids[$row['attach_id']] : '',
					'U_FILE'            => $this->helper->route('sheer_knowledgebase_kb_file', ['id' => $row['attach_id']]),
				]
			);
		}
		$this->db->sql_freeresult($result);

		$this->template->assign_vars([
				'S_ORPHAN'        => true,
				'L_TITLE'         => $this->language->lang('ACP_ORPHAN_ATTACHMENTS'),
				'L_TITLE_EXPLAIN' => $this->language->lang('ORPHAN_EXPLAIN'),
				'S_ACTION'        => $this->u_action,
			]
		);
	}

	/**
	 * @return void
	 * @access public
	 */
	public function extra_files(): void
	{
		$submit = $this->request->variable('submit', false);

		$batch_size = 500;
		$list = '';
		$files = $bd_files = $delete_list = $unsuccess = [];
		ignore_user_abort(true);
		set_time_limit(0);
		$files_list = $this->cache->get('_kb_prune_attachments'); // Try to get data from cache

		if ($submit)
		{
			if (!$files_list)
			{
				$this->scan($this->upload_dir, $files);

				$sql = 'SELECT attach_id, physical_filename
					FROM ' . $this->attachments_table;
				$result = $this->db->sql_query($sql);
				while ($data = $this->db->sql_fetchrow($result))
				{
					$bd_files[] = $this->upload_dir . $data['physical_filename'];
					$bd_files[] = $this->upload_dir . 'thumb_' . $data['physical_filename'];
				}
				$this->db->sql_freeresult($result);
				$files = array_diff($files, $bd_files);
				$files = array_unique($files);
				array_map('trim', $files);
				sort($files);
				$this->cache->put('_kb_prune_attachments', $files);
			}
			else
			{
				$files = $files_list;
			}

			$count = 0;
			foreach ($files as $del_file)
			{
				if (file_exists($del_file) && !is_dir($del_file))
				{
					if (unlink($del_file))
					{
						$delete_list[] = $del_file;
					}
					else
					{
						$unsuccess[] = $del_file;
					}

					$files = array_diff($files, [$del_file]);

					sort($files);
					$count++;
				}
				if ($count > ($batch_size - 1))
				{
					$this->cache->destroy('_kb_prune_attachments');
					$this->cache->put('_kb_prune_attachments', $files);
					break;
				}
			}

			if (count($delete_list))
			{
				$list .= implode('<br>', $delete_list);
				$exit = false;
			}
			else
			{
				$list = (count($unsuccess)) ? '' : $this->language->lang('PRUNE_ATTACHMENTS_FINISHED');
				$exit = true;
			}

			if (count($unsuccess))
			{
				$list .= $this->language->lang('PRUNE_ATTACHMENTS_FAIL') . '<br>' . implode('<br>', $unsuccess);
			}

			if ($exit)
			{
				$this->cache->destroy('_kb_prune_attachments');
				if ((count($unsuccess)))
				{
					trigger_error($list, E_USER_WARNING);
				}
				else
				{
					trigger_error($list);
				}
			}
			else
			{
				meta_refresh(10, $this->u_action . '&amp;submit=1');
				trigger_error($this->language->lang('PRUNE_ATTACHMENTS_PROGRESS') . '<br>' . $list);
			}
		}

		$this->template->assign_vars([
				'S_PRUNE_ATTACHMENTS' => true,
				'L_TITLE'             => $this->language->lang('ACP_LIBRARY_ATTACHMENTS_EXTRA_FILES'),
				'L_TITLE_EXPLAIN'     => $this->language->lang('PRUNE_ATTACHMENTS_EXPLAIN'),
				'S_ACTION'            => $this->u_action,
			]
		);
	}

	/**
	 * @param $path
	 * @param $res
	 * @return array
	 * @access public
	 */
	public function scan($path, &$res): array
	{
		$mass = scandir($path);
		for ($i = 0; $i <= count($mass) - 1; $i++)
		{
			if ($mass[$i] != '..' && $mass[$i] != '.' && $mass[$i] != 'index.htm' && $mass[$i] != '.htaccess' && $mass[$i] != 'plupload')
			{
				$res[] = $path . $mass[$i];
			}
			if (!str_contains($mass[$i], '.') && is_dir($path . $mass[$i]))
			{
				$this->scan($path . $mass[$i], $res);
			}
		}
		return $res;
	}

	/**
	 * @return void
	 * @access public
	 */
	public function lost_files(): void
	{
		$submit = $this->request->variable('submit', false);
		$step = $this->request->variable('step', 0);
		$batch_size = 500;
		$begin = $batch_size * $step;

		if ($submit)
		{
			// Get the batch
			$sql = 'SELECT attach_id, physical_filename
				FROM ' . $this->attachments_table;
			$result = $this->db->sql_query_limit($sql, $batch_size, $begin);
			$batch = $this->db->sql_fetchrowset($result);
			$this->db->sql_freeresult($result);

			if (empty($batch))
			{
				// Nothing to do
				trigger_error('RESYNC_ATTACHMENTS_FINISHED');
			}

			$delete_ids = [];

			foreach ($batch as $row)
			{
				// Does the file still exists?
				$path = $this->upload_dir . $row['physical_filename'];

				if (file_exists($path))
				{
					// Yes, next please!
					continue;
				}

				$delete_ids[] = $row['attach_id'];
			}

			// Run all the queries
			if (!empty($delete_ids))
			{
				$this->db->sql_query('DELETE FROM ' . $this->attachments_table . ' WHERE ' . $this->db->sql_in_set('attach_id', $delete_ids));
			}

			// Next step
			meta_refresh(2, $this->u_action . '&amp;step=' . ++$step . '&amp;submit=1');
			trigger_error($this->language->lang('RESYNC_ATTACHMENTS_PROGRESS'));
		}

		$this->template->assign_vars([
				'S_PRUNE_ATTACHMENTS' => true,
				'L_TITLE'             => $this->language->lang('ACP_LIBRARY_ATTACHMENTS_LOST_FILES'),
				'L_TITLE_EXPLAIN'     => $this->language->lang('ACP_LIBRARY_ATTACHMENTS_LOST_FILES_EXPLAIN'),
				'S_ACTION'            => $this->u_action,
			]
		);
	}
	/**
	 * END - Attachments management functions
	 */

	/**
	 * Article management functions
	 *
	 * @return void
	 */
	public function show_articles(): void
	{
		$per_page = $this->config['kb_articles_per_page'];

		// Sort keys
		$sort_days = $this->request->variable('st', 0);
		$sort_key = $this->request->variable('sk', 'd');
		$sort_dir = $this->request->variable('sd', 'd');
		$start = $this->request->variable('start', 0);

		// Sorting
		$limit_days = [];
		$sort_by_text = [
			'u' => $this->language->lang('ARTICLE_DATE'),
			'd' => $this->language->lang('SORT_DATE'),
			'c' => $this->language->lang('CATEGORY'),
			'e' => $this->language->lang('EDIT_DATE'),
		];
		$sort_by_sql = [
			'u' => 'article_title',
			'd' => 'article_date',
			'c' => 'article_category_id',
			'e' => 'edit_date',
		];

		$s_limit_days = $s_sort_key = $s_sort_dir = $u_sort_param = '';
		$keywords_param = '';
		gen_sort_selects($limit_days, $sort_by_text, $sort_days, $sort_key, $sort_dir, $s_limit_days, $s_sort_key, $s_sort_dir, $u_sort_param);
		// Sorting
		$sql_sort = (($sort_dir == 'd') ? 'DESC' : 'ASC');
		$order_by = $sort_by_sql[$sort_key];

		$sql = 'SELECT COUNT(article_id) as article_count
			FROM ' . $this->articles_table;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$article_count = $row['article_count'];
		$this->config->set('kb_num_articles', $article_count);
		$this->db->sql_freeresult($result);

		if (empty($per_page))
		{
			$per_page = 10;
		}
		$sql = 'SELECT *
			FROM ' . $this->articles_table . '
			ORDER BY ' . $order_by . ' ' . $sql_sort . '
			LIMIT ' . $start . ', ' . $per_page;
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$category_data = $this->kb->get_cat_info($row['article_category_id']);
			$this->template->assign_block_vars('articles', [
					'ID'               => $row['article_id'],
					'ARTICLE_TITLE'    => $row['article_title'],
					'ARTICLE_APPROVED' => ($row['approved']) ? '<i class="icon fa-thumbs-o-up fa-fw acp-icon-settings"></i>' : '<i class="icon fa fa-exclamation-triangle fa-fw acp-icon-resync" aria-hidden="true" title="' . $this->language->lang('NEED_APPROVE') . '"></i>',
					'CATEGORY_ID'      => $row['article_category_id'],
					'CATEGORY'         => ($category_data['category_name']) ?: $this->language->lang('CAT_NO_EXISTS'),
					'U_CATEGORY'       => $this->helper->route('sheer_knowledgebase_index', ['i' => '-sheer-knowledgebase-acp-manage_module', 'mode' => 'manage', 'parent_id' => $row['article_category_id']]),
					'U_ARTICLE'        => $this->helper->route('sheer_knowledgebase_article', ['k' => $row['article_id']]),
					'U_ARTICLE_EDIT'   => $this->helper->route('sheer_knowledgebase_posting', ['mode' => 'edit', 'id' => $row['article_category_id'], 'k' => $row['article_id']]),
					'AUTHOR'           => $row['author'],
					'TIME'             => $this->user->format_date($row['article_date']),
					'EDIT_TIME'        => ($row['edit_date']) ? $this->user->format_date($row['edit_date']) : 0,
					'U_MOVE'           => $this->u_action . '&amp;action=move&amp;aid=' . $row['article_id'],
					'U_DELETE'         => $this->u_action . '&amp;action=delete&amp;aid=' . $row['article_id'],
				]
			);
		}
		$this->db->sql_freeresult($result);

		$pagination_url = $this->u_action . '&amp;' . $u_sort_param . '&amp;' . $keywords_param;
		if ($article_count)
		{
			$this->pagination->generate_template_pagination($pagination_url, 'pagination', 'start', $article_count, $per_page, $start);
		}

		$this->template->assign_vars([
				'S_SORT_KEY'  => $s_sort_key,
				'S_SORT_DIR'  => $s_sort_dir,
				'S_ARTICLES'  => true,
				'S_ACTION'    => $this->u_action . '&amp;' . $u_sort_param . $keywords_param . '&amp;start=' . $start,
				'TOTAL_ITEMS' => $this->language->lang('TOTAL_ITEMS', (int) $article_count),
				'PAGE_NUMBER' => $this->pagination->on_page($article_count, $per_page, $start),
			]
		);
	}

	/**
	 * @return void
	 */
	public function move_article(): void
	{
		$article_id = $this->request->variable('aid', 0);
		$submit = $this->request->is_set_post('submit');

		// move to
		$to_id = $this->request->variable('to_id', 0);
		// move from
		$info = $this->kb->get_kb_article_info($article_id);

		if ($submit)
		{
			$this->kb->kb_move_article($article_id, $info['article_title'], $info['article_category_id'], $to_id, $info['display_order']);
			meta_refresh(2, $this->u_action);
			trigger_error($this->language->lang('ARTICLE_MOVED'));
		}

		$this->template->assign_vars([
				'S_MOVE_ART'              => true,
				'S_MOVE_CATEGORY_OPTIONS' => $this->kb->make_category_select(0, $info['article_category_id'], false),
				'S_ACTION'                => $this->u_action . '&amp;action=move&amp;aid=' . $article_id,
			]
		);
	}

	/**
	 * @return void
	 */
	public function delete_article(): void
	{
		$article_id = $this->request->variable('aid', 0);
		$article = $this->kb->get_kb_article_info($article_id);

		if (confirm_box(true))
		{
			$this->kb->kb_delete_article($article_id, $article['article_title']);

			// Select which method we'll use to obtain the post_id or topic_id information
			try
			{
				$kb_search = $this->search_factory->get_active();
				// remove index
				$author_ids[] = $article['author_id'];
				$article_ids[] = $article_id;
				$kb_search->index_remove($article_ids, $author_ids);
			}
			catch (RuntimeException $e)
			{
				if (str_starts_with($e->getMessage(), 'No service found'))
				{
					trigger_error('NO_SUCH_SEARCH_MODULE');
				}
				else
				{
					throw $e;
				}
			}

			meta_refresh(2, $this->u_action);
			trigger_error($this->language->lang('ARTICLE_DELETED'));
		}
		else
		{
			$s_hidden_fields = build_hidden_fields([
				'aid'    => $article_id,
				'action' => 'delete',
			]);

			confirm_box(false, $this->language->lang('CONFIRM_DELETE_ARTICLE'), $s_hidden_fields);
		}
	}
	/**
	 * END - Article management functions
	 */


	/**
	 * Log management functions
	 * /**
	 *
	 * @param $id
	 * @param $mode
	 * @return void
	 */
	public function log($id, string $mode): void
	{
		$start = $this->request->variable('start', 0);
		$deletemark = $this->request->variable('delmarked', false, false, request_interface::POST);
		$deleteall = $this->request->variable('delall', false, false, request_interface::POST);
		$marked = $this->request->variable('mark', [0]);

		// Sort keys
		$sort_days = $this->request->variable('st', 0);
		$sort_key = $this->request->variable('sk', 't');
		$sort_dir = $this->request->variable('sd', 'd');

		// Delete entries if requested and able
		if (($deletemark && count($marked) || $deleteall))
		{
			if (confirm_box(true))
			{
				if ($deleteall)
				{
					$sql = 'DELETE FROM ' . $this->logs_table;
				}
				else
				{
					$sql = 'DELETE FROM ' . $this->logs_table . ' WHERE ' . $this->db->sql_in_set('log_id', $marked);
				}

				$this->db->sql_query($sql);
				$this->log->add('admin', $this->user->data['user_id'], $this->user->data['user_ip'], 'LOG_CLEAR_KB', time());
			}
			else
			{
				confirm_box(false, $this->language->lang('CONFIRM_OPERATION'), build_hidden_fields([
						'start'     => $start,
						'delmarked' => $deletemark,
						'delall'    => $deleteall,
						'mark'      => $marked,
						'st'        => $sort_days,
						'sk'        => $sort_key,
						'sd'        => $sort_dir,
						'i'         => $id,
						'mode'      => $mode,
					])
				);
			}
		}

		// Sorting
		$limit_days = [
			0   => $this->language->lang('ALL_ENTRIES'), 1 => $this->language->lang('1_DAY'), 7 => $this->language->lang('7_DAYS'),
			14  => $this->language->lang('2_WEEKS'), 30 => $this->language->lang('1_MONTH'), 90 => $this->language->lang('3_MONTHS'),
			180 => $this->language->lang('6_MONTHS'), 365 => $this->language->lang('1_YEAR'),
		];
		$sort_by_text = [
			'u' => $this->language->lang('SORT_USERNAME'),
			't' => $this->language->lang('SORT_DATE'),
			'i' => $this->language->lang('SORT_IP'),
			'o' => $this->language->lang('SORT_ACTION'),
		];
		$sort_by_sql = [
			'u' => 'u.username_clean',
			't' => 'l.log_time',
			'i' => 'l.log_ip',
			'o' => 'l.log_operation',
		];

		$s_limit_days = $s_sort_key = $s_sort_dir = $u_sort_param = '';
		gen_sort_selects($limit_days, $sort_by_text, $sort_days, $sort_key, $sort_dir, $s_limit_days, $s_sort_key, $s_sort_dir, $u_sort_param);

		// Define where and sort sql for use in displaying logs
		$sql_where = ($sort_days) ? (time() - ($sort_days * 86400)) : 0;
		$sql_sort = $sort_by_sql[$sort_key] . ' ' . (($sort_dir == 'd') ? 'DESC' : 'ASC');

		$log_data = [];
		$log_count = 0;
		$start = view_log('admin', $log_data, $log_count, $this->config['topics_per_page'], $start, 0, 0, 0, $sql_where, $sql_sort);

		$base_url = $this->u_action . '&amp;' . $u_sort_param;
		$this->pagination->generate_template_pagination($base_url, 'pagination', 'start', $log_count, $this->config['topics_per_page'], $start);

		foreach ($log_data as $row)
		{
			$this->template->assign_block_vars('log', [
					'USERNAME' => $row['username_full'],
					'IP'       => $row['ip'],
					'DATE'     => $this->user->format_date($row['time']),
					'ACTION'   => $row['action'],
					'ID'       => $row['id'],
				]
			);
		}

		$this->template->assign_vars([
				'U_ACTION'     => $this->u_action . '&amp;start=' . $start,
				'S_LIMIT_DAYS' => $s_limit_days,
				'S_SORT_KEY'   => $s_sort_key,
				'S_SORT_DIR'   => $s_sort_dir,
			]
		);
	}
	/**
	 * END - Log management functions
	 */


	/**
	 * Category management functions
	 */
	/**
	 * Update category data
	 */
	public function update_category_data(&$category_data, $copy_perm_from_id): array
	{
		$errors = [];
		$category_id = '';

		if ($category_data['category_name'] == '')
		{
			$errors[] = $this->language->lang('NO_CAT_NAME');
		}

		$category_data_sql = $category_data;

		if (count($errors))
		{
			return $errors;
		}

		if (!isset($category_data_sql['category_id']))
		{
			// no category_id means we're creating a new category
			unset($category_data_sql['type_action']);

			if ($category_data_sql['parent_id'])
			{
				$sql = 'SELECT left_id, right_id
					FROM ' . $this->categories_table . '
					WHERE category_id = ' . (int) $category_data_sql['parent_id'];
				$result = $this->db->sql_query($sql);
				$row = $this->db->sql_fetchrow($result);
				$this->db->sql_freeresult($result);
				if (!$row)
				{
					trigger_error($this->language->lang('PARENT_NOT_EXIST') . adm_back_link($this->u_action), E_USER_WARNING);
				}

				$sql = 'UPDATE ' . $this->categories_table . '
					SET left_id = left_id + 2, right_id = right_id + 2
					WHERE left_id > ' . (int) $row['right_id'];
				$this->db->sql_query($sql);

				$sql = 'UPDATE ' . $this->categories_table . '
					SET right_id = right_id + 2
					WHERE ' . $row['left_id'] . ' BETWEEN left_id AND right_id';
				$this->db->sql_query($sql);

				$category_data_sql['left_id'] = $row['right_id'];
				$category_data_sql['right_id'] = $row['right_id'] + 1;
			}
			else
			{
				$sql = 'SELECT MAX(right_id) AS right_id
					FROM ' . $this->categories_table;
				$result = $this->db->sql_query($sql);
				$row = $this->db->sql_fetchrow($result);
				$this->db->sql_freeresult($result);

				$category_data_sql['left_id'] = $row['right_id'] + 1;
				$category_data_sql['right_id'] = $row['right_id'] + 2;
			}

			$sql = 'INSERT INTO ' . $this->categories_table . ' ' . $this->db->sql_build_array('INSERT', $category_data_sql);
			$this->db->sql_query($sql);
			$new_category_id = $category_data['category_id'] = $this->db->sql_nextid();
			$this->log->add('admin', $this->user->data['user_id'], $this->user->data['user_ip'], 'LOG_CATS_ADD', time(), [$category_data['category_name']]);
		}
		else
		{
			$row = $this->kb->get_cat_info($category_data_sql['category_id']);

			if ($row['parent_id'] != $category_data_sql['parent_id'])
			{
				if ($row['category_id'] != $category_data_sql['parent_id'])
				{
					$errors = $this->move_category($category_data_sql['category_id'], $category_data_sql['parent_id']);
				}
				else
				{
					$category_data_sql['parent_id'] = $row['parent_id'];
				}

				($category_data_sql['parent_id']) ? $dest = $this->kb->get_cat_info($category_data_sql['parent_id']) : $dest['category_name'] = $this->language->lang('KB_ROOT');
				$this->log->add('admin', $this->user->data['user_id'], $this->user->data['user_ip'], 'LOG_CATS_CAT_MOVED_TO', time(), [$category_data_sql['category_name'], $dest['category_name']]);
			}

			if (count($errors))
			{
				return $errors;
			}

			unset($category_data_sql['type_action']);

			if ($row['category_name'] != $category_data_sql['category_name'])
			{
				// the category name has changed, clear the parents list of all cats (for safety)
				$sql = 'UPDATE ' . $this->categories_table . "
					SET category_parents = ''";
				$this->db->sql_query($sql);
			}

			// Setting the category id to the category id is not really received well by some dbs. ;)
			$category_id = $category_data_sql['category_id'];
			unset($category_data_sql['category_id']);

			$sql = 'UPDATE ' . $this->categories_table . '
				SET ' . $this->db->sql_build_array('UPDATE', $category_data_sql) . '
				WHERE category_id = ' . (int) $category_id;
			$this->db->sql_query($sql);

			// Add it back
			$category_data['category_id'] = $category_id;
			$this->log->add('admin', $this->user->data['user_id'], $this->user->data['user_ip'], 'LOG_CATS_EDIT', time(), [$category_data['category_name']]);
		}

		if ($copy_perm_from_id)
		{
			$options = $sql_ary = [];

			if (isset($new_category_id))
			{
				$category_id = $new_category_id;
			}
			else
			{
				$sql = 'DELETE FROM ' . $this->kb_groups_table . '
					WHERE category_id = ' . (int) $category_id;
				$this->db->sql_query($sql);
			}

			$sql = 'SELECT group_id, auth_option_id, auth_setting
				FROM ' . $this->kb_groups_table . '
				WHERE category_id = ' . (int) $copy_perm_from_id;
			$result = $this->db->sql_query($sql);
			while ($row = $this->db->sql_fetchrow($result))
			{
				$options[] = $row;
			}
			$this->db->sql_freeresult($result);

			if (count($options))
			{
				foreach ($options as $permission)
				{
					$sql_ary[] = [
						'category_id'    => $category_id,
						'group_id'       => $permission['group_id'],
						'auth_option_id' => $permission['auth_option_id'],
						'auth_setting'   => $permission['auth_setting'],
					];
				}
				$this->db->sql_multi_insert($this->kb_groups_table, $sql_ary);
			}
		}

		return $errors;
	}

	protected function move_category($from_id, $to_id): array
	{
		$moved_ids = $errors = [];

		$moved_cats = $this->kb->get_category_branch($from_id, 'children', 'descending');
		$from_data = $moved_cats[0];
		$diff = count($moved_cats) * 2;

		for ($i = 0; $i < count($moved_cats); $i++)
		{
			$moved_ids[] = $moved_cats[$i]['category_id'];
		}

		// Resync parents
		$sql = 'UPDATE ' . $this->categories_table . '
			SET right_id = right_id - ' . $diff . ", category_parents = ''
			WHERE left_id < " . (int) $from_data['right_id'] . '
				AND right_id > ' . (int) $from_data['right_id'];
		$this->db->sql_query($sql);

		// Resync righthand side of tree
		$sql = 'UPDATE ' . $this->categories_table . '
			SET left_id = left_id - ' . $diff . ', right_id = right_id - ' . $diff . ", category_parents = ''
			WHERE left_id > " . (int) $from_data['right_id'];
		$this->db->sql_query($sql);

		if ($to_id > 0)
		{
			// Retrieve $to_data again, it may have been changed...
			$to_data = $this->kb->get_cat_info($to_id);

			// Resync new parents
			$sql = 'UPDATE ' . $this->categories_table . '
				SET right_id = right_id + ' . $diff . ", category_parents = ''
				WHERE " . $to_data['right_id'] . ' BETWEEN left_id AND right_id
					AND ' . $this->db->sql_in_set('category_id', $moved_ids, true);
			$this->db->sql_query($sql);

			// Resync the righthand side of the tree
			$sql = 'UPDATE ' . $this->categories_table . '
				SET left_id = left_id + ' . $diff . ', right_id = right_id + ' . $diff . ", category_parents = ''
				WHERE left_id > " . (int) $to_data['right_id'] . '
					AND ' . $this->db->sql_in_set('category_id', $moved_ids, true);
			$this->db->sql_query($sql);

			// Resync moved branch
			$to_data['right_id'] += $diff;

			if ($to_data['right_id'] > $from_data['right_id'])
			{
				$diff = '+ ' . ($to_data['right_id'] - $from_data['right_id'] - 1);
			}
			else
			{
				$diff = '- ' . abs($to_data['right_id'] - $from_data['right_id'] - 1);
			}
		}
		else
		{
			$sql = 'SELECT MAX(right_id) AS right_id
				FROM ' . $this->categories_table . '
				WHERE ' . $this->db->sql_in_set('category_id', $moved_ids, true);
			$result = $this->db->sql_query($sql);
			$row = $this->db->sql_fetchrow($result);
			$this->db->sql_freeresult($result);

			$diff = '+ ' . ($row['right_id'] - $from_data['left_id'] + 1);
		}

		$sql = 'UPDATE ' . $this->categories_table . '
			SET left_id = left_id ' . $diff . ', right_id = right_id ' . $diff . ", category_parents = ''
			WHERE " . $this->db->sql_in_set('category_id', $moved_ids);
		$this->db->sql_query($sql);

		return $errors;
	}

	public function move_category_by($category_row, $action = 'move_up', $steps = 1): string
	{
		$sql = 'SELECT category_id, category_name, left_id, right_id
			FROM ' . $this->categories_table . "
			WHERE parent_id = {$category_row['parent_id']}
				AND " . (($action == 'move_up') ? "right_id < {$category_row['right_id']} ORDER BY right_id DESC" : "left_id > {$category_row['left_id']} ORDER BY left_id ASC");
		$result = $this->db->sql_query_limit($sql, $steps);

		$target = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$target = $row;
		}
		$this->db->sql_freeresult($result);

		if (!count($target))
		{
			return false;
		}

		if ($action == 'move_up')
		{
			$left_id = $target['left_id'];
			$right_id = $category_row['right_id'];

			$diff_up = $category_row['left_id'] - $target['left_id'];
			$diff_down = $category_row['right_id'] + 1 - $category_row['left_id'];

			$move_up_left = $category_row['left_id'];
			$move_up_right = $category_row['right_id'];
		}
		else
		{
			$left_id = $category_row['left_id'];
			$right_id = $target['right_id'];

			$diff_up = $category_row['right_id'] + 1 - $category_row['left_id'];
			$diff_down = $target['right_id'] - $category_row['right_id'];

			$move_up_left = $category_row['right_id'] + 1;
			$move_up_right = $target['right_id'];
		}

		$sql = 'UPDATE ' . $this->categories_table . '
			SET left_id = left_id + CASE
				WHEN left_id BETWEEN ' . $move_up_left . ' AND ' . $move_up_right . ' THEN -' . $diff_up . '
				ELSE ' . $diff_down . '
			END,
			right_id = right_id + CASE
				WHEN right_id BETWEEN ' . $move_up_left . ' AND ' . $move_up_right . ' THEN -' . $diff_up . '
				ELSE ' . $diff_down . "
			END,
			category_parents = ''
			WHERE
				left_id BETWEEN " . $left_id . ' AND ' . $right_id . '
				AND right_id BETWEEN ' . $left_id . ' AND ' . $right_id;
		$this->db->sql_query($sql);
		return $target['category_name'];
	}

	public function sync($cat_id): array
	{
		$errors = [];

		$sql = 'SELECT COUNT(article_category_id) as articles
			FROM ' . $this->articles_table . '
			WHERE article_category_id = ' . (int) $cat_id;
		$result = $this->db->sql_query($sql);
		if ($result)
		{
			$articles = $this->db->sql_fetchfield('articles');
			$this->db->sql_freeresult($result);

			$sql = 'UPDATE ' . $this->categories_table . '
			SET number_articles = ' . $articles . '
			WHERE category_id = ' . (int) $cat_id;
			$this->db->sql_query($sql);
			return [];
		}
		$errors[] = $this->language->lang('CAT_NO_EXISTS');
		return $errors;
	}

	public function delete_category($category_id, $action_posts = 'delete', $action_sub_cats = 'delete', $posts_to_id = 0, $sub_cats_to_id = 0): array
	{
		$category_data = $this->kb->get_cat_info($category_id);
		$errors = [];
		$diff = 0;
		$log_action_posts = $log_action_cats = $posts_to_name = $sub_cats_to_name = '';
		$category_ids = [$category_id];

		if ($action_posts == 'delete')
		{
			$log_action_posts = 'POSTS';
			$errors = array_merge($errors, $this->delete_category_content($category_id));
		}
		else if ($action_posts == 'move')
		{
			if (!$posts_to_id)
			{
				$errors[] = $this->language->lang('NO_DESTINATION_CATEGORY');
			}
			else
			{
				$log_action_posts = 'MOVE_POSTS';

				$sql = 'SELECT category_name
					FROM ' . $this->categories_table . '
					WHERE category_id = ' . (int) $posts_to_id;
				$result = $this->db->sql_query($sql);
				$row = $this->db->sql_fetchrow($result);
				$this->db->sql_freeresult($result);

				if (!$row)
				{
					$errors[] = $this->language->lang('CAT_NO_EXISTS');
				}
				else
				{
					$posts_to_name = $row['category_name'];
					$errors = array_merge($errors, $this->move_category_content($category_id, $posts_to_id));
				}
			}
		}

		if (count($errors))
		{
			return $errors;
		}

		if ($action_sub_cats == 'delete')
		{
			$log_action_cats = 'CATS';
			$rows = $this->kb->get_category_branch($category_id, 'children', 'descending', false);

			foreach ($rows as $row)
			{
				$category_ids[] = $row['category_id'];
				$errors = array_merge($errors, $this->delete_category_content($row['category_id']));
			}

			if (count($errors))
			{
				return $errors;
			}

			$diff = count($category_ids) * 2;

			$sql = 'DELETE FROM ' . $this->categories_table . '
				WHERE ' . $this->db->sql_in_set('category_id', $category_ids);
			$this->db->sql_query($sql);
		}
		else if ($action_sub_cats == 'move')
		{
			if (!$sub_cats_to_id)
			{
				$errors[] = $this->language->lang('NO_DESTINATION_CATEGORY');
			}
			else
			{
				$log_action_cats = 'MOVE_CATS';

				$sql = 'SELECT category_name
					FROM ' . $this->categories_table . '
					WHERE category_id = ' . (int) $sub_cats_to_id;
				$result = $this->db->sql_query($sql);
				$row = $this->db->sql_fetchrow($result);
				$this->db->sql_freeresult($result);

				if (!$row)
				{
					$errors[] = $this->language->lang('CAT_NO_EXISTS');
				}
				else
				{
					$sub_cats_to_name = $row['category_name'];

					$sql = 'SELECT category_id FROM ' . $this->categories_table . '
						WHERE parent_id = ' . $category_id;
					$result = $this->db->sql_query($sql);

					while ($row = $this->db->sql_fetchrow($result))
					{
						$this->move_category($row['category_id'], $sub_cats_to_id);
					}
					$this->db->sql_freeresult($result);

					$category_data = $this->kb->get_cat_info($category_id);

					$sql = 'UPDATE ' . $this->categories_table . '
						SET parent_id = ' . $sub_cats_to_id . '
						WHERE parent_id = ' . $category_id;
					$this->db->sql_query($sql);

					$diff = 2;
					$sql = 'DELETE FROM ' . $this->categories_table . ' WHERE category_id = ' . $category_id;
					$this->db->sql_query($sql);
				}
			}

			if (count($errors))
			{
				return $errors;
			}
		}
		else
		{
			$diff = 2;
			$sql = 'DELETE FROM ' . $this->categories_table . ' WHERE category_id = ' . $category_id;
			$this->db->sql_query($sql);
		}

		// Resync tree
		$sql = 'UPDATE ' . $this->categories_table . '
			SET right_id = right_id - ' . $diff . '
			WHERE left_id < ' . $category_data['right_id'] . ' AND right_id > ' . $category_data['right_id'];
		$this->db->sql_query($sql);

		$sql = 'UPDATE ' . $this->categories_table . '
			SET left_id = left_id - ' . $diff . ', right_id = right_id - ' . $diff . '
			WHERE left_id > ' . $category_data['right_id'];
		$this->db->sql_query($sql);

		$log_action = implode('_', [$log_action_posts, $log_action_cats]);

		switch ($log_action)
		{
			case 'POSTS_MOVE_CATS':
				$this->log->add('admin', $this->user->data['user_id'], $this->user->data['user_ip'], 'LOG_CATS_DEL_POSTS_MOVE_CATS', time(), [$sub_cats_to_name, $category_data['category_name']]);
			break;

			case '_MOVE_CATS':
				$this->log->add('admin', $this->user->data['user_id'], $this->user->data['user_ip'], 'LOG_CATS_DEL_MOVE_CATS', time(), [$sub_cats_to_name, $category_data['category_name']]);
			break;

			case 'MOVE_POSTS_':
				$this->log->add('admin', $this->user->data['user_id'], $this->user->data['user_ip'], 'LOG_CATS_DEL_MOVE_POSTS', time(), [$posts_to_name, $category_data['category_name']]);
			break;

			case 'POSTS_CATS':
				$this->log->add('admin', $this->user->data['user_id'], $this->user->data['user_ip'], 'LOG_CATS_DEL_POSTS_CATS', time(), [$category_data['category_name']]);
			break;

			case '_CATS':
				$this->log->add('admin', $this->user->data['user_id'], $this->user->data['user_ip'], 'LOG_CATS_DEL_CAT', time(), [$category_data['category_name']]);
			break;

			case 'POSTS_':
				$this->log->add('admin', $this->user->data['user_id'], $this->user->data['user_ip'], 'LOG_CATS_DEL_ARTICLES', time(), [$category_data['category_name']]);
			break;

			case 'MOVE_POSTS_MOVE_CATS':
				$this->log->add('admin', $this->user->data['user_id'], $this->user->data['user_ip'], 'LOG_CATS_DEL_MOVE_POSTS_MOVE_CATS', time(), [$posts_to_name, $sub_cats_to_name, $category_data['category_name']]);
			break;

			case 'MOVE_POSTS_CATS':
				$this->log->add('admin', $this->user->data['user_id'], $this->user->data['user_ip'], 'LOG_CATS_DEL_MOVE_POSTS_CATS', time(), [$posts_to_name, $category_data['category_name']]);
			break;

			default:
				$this->log->add('admin', $this->user->data['user_id'], $this->user->data['user_ip'], 'LOG_CATS_DEL_CAT', time(), [$category_data['category_name']]);
			break;
		}

		// delete permissions
		$sql = 'DELETE
			FROM ' . $this->kb_users_table . '
			WHERE category_id = ' . (int) $category_id;
		$this->db->sql_query($sql);

		$sql = 'DELETE
			FROM ' . $this->kb_groups_table . '
			WHERE category_id = ' . (int) $category_id;
		$this->db->sql_query($sql);

		return $errors;
	}

	/**
	 * Delete category content
	 *
	 * @param $cat_id
	 * @return array
	 */
	function delete_category_content($cat_id): array
	{
		include_once($this->phpbb_root_path . 'includes/functions_posting.' . $this->php_ext);

		// remove topics
		$topics = $articles = $errors = [];
		$sql = 'SELECT topic_id, article_id
			FROM ' . $this->articles_table . '
			WHERE article_category_id = ' . (int) $cat_id;
		$result = $this->db->sql_query($sql);

		while ($row = $this->db->sql_fetchrow($result))
		{
			$topics[] = $row['topic_id'];
			$articles[] = $row['article_id'];
		}
		delete_topics('topic_id', $topics, true, true, true);

		// remove articles
		$sql = 'DELETE
			FROM ' . $this->articles_table . '
			WHERE article_category_id = ' . (int) $cat_id;
		$this->db->sql_query($sql);

		// Select which method we'll use to obtain the post_id or topic_id information
		if (!empty($articles))
		{
			try
			{
				$kb_search = $this->search_factory->get_active();
				// remove index
				$kb_search->index_remove($articles, []);
			}
			catch (RuntimeException $e)
			{
				if (str_starts_with($e->getMessage(), 'No service found'))
				{
					trigger_error('NO_SUCH_SEARCH_MODULE');
				}
				else
				{
					throw $e;
				}
			}
		}

		return $errors;
	}

	/**
	 * Move category content from one to another category
	 */
	protected function move_category_content($from_id, $to_id): array
	{
		$errors = [];
		// count the number of articles in the sender
		$sql = 'SELECT number_articles
			FROM ' . $this->categories_table . '
			WHERE category_id = ' . (int) $from_id;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		if (empty($row))
		{
			$errors[] = $this->language->lang('CAT_NO_EXISTS');
			return $errors;
		}
		$this->db->sql_freeresult($result);
		$from_id_articles = $row['number_articles'];
		// and recipient
		$sql = 'SELECT number_articles
			FROM ' . $this->categories_table . '
			WHERE category_id = ' . (int) $to_id;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		if (empty($row))
		{
			$errors[] = $this->language->lang('CAT_NO_EXISTS');
			return $errors;
		}
		$this->db->sql_freeresult($result);
		$to_id_articles = $row['number_articles'];

		$sql = 'SELECT MAX(display_order) AS ord
			FROM ' . $this->articles_table . '
			WHERE article_category_id = ' . (int) $to_id;
		$result = $this->db->sql_query($sql);
		$order = (int) $this->db->sql_fetchfield('ord');
		$this->db->sql_freeresult($result);

		$sql = 'UPDATE ' . $this->articles_table . ' SET display_order = display_order + ' . $order . '
			WHERE article_category_id = ' . (int) $from_id;
		$this->db->sql_query($sql);

		// change the id of articles
		$sql = 'UPDATE ' . $this->articles_table . '
			SET article_category_id = ' . (int) $to_id . '
			WHERE article_category_id = ' . (int) $from_id;
		$this->db->sql_query($sql);
		// change the number of articles in the receiver
		$to_id_articles = $to_id_articles + $from_id_articles;
		$sql = 'UPDATE ' . $this->categories_table . '
			SET number_articles = ' . $to_id_articles . '
			WHERE category_id = ' . (int) $to_id;
		$this->db->sql_query($sql);

		return [];
	}
	/**
	 * END - Category management functions
	 */

	/**
	 * Permission management functions
	 */
	public function make_array_categoryid(): array
	{
		$category_id = [];
		$sql = 'SELECT category_id
			FROM ' . $this->categories_table;
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$category_id[] = $row['category_id'];
		}
		$this->db->sql_freeresult($result);

		return $category_id;
	}

	/**
	 * @param $mode
	 * @param $user_mode
	 * @param $group_id
	 * @param $category_id
	 * @param $user_id
	 * @return void
	 */
	public function get_mask($mode, $user_mode, $group_id, $category_id, $user_id): void
	{
		$groups = [];

		$view_user_mask = ($mode == 'mask' && $user_mode == 'user');

		if (empty($group_id) && empty($user_id))
		{
			$this->permissions_v_mask($mode, $category_id, $user_id);
			return;
		}

		$types = [
			'u_' => $this->language->lang('ACL_TYPE_U_'),
			'm_' => $this->language->lang('ACL_TYPE_M_'),
		];

		$apply_all_permissions = $this->request->variable('apply_all_permissions', false);

		if (!empty($user_id) && $user_mode != 'group')
		{
			$where = $this->db->sql_in_set('user_id', $user_id, false);
			if ($where == 'user_id = 0')
			{
				$where = 'user_id = 1';
			}
			$sql = 'SELECT user_id, username
				FROM ' . USERS_TABLE . '
				WHERE ' . $where;
			$result = $this->db->sql_query($sql);
			while ($users = $this->db->sql_fetchrow($result))
			{
				$user_name = $users['username'];
				$group_ids[] = $groups[$user_name] = $users['user_id'];
			}
			if (!$user_mode)
			{
				$user_mode = 'user';
			}
		}
		else
		{
			$sql = 'SELECT group_id, group_name
				FROM ' . GROUPS_TABLE . '
				WHERE ' . $this->db->sql_in_set('group_id', $group_id, false);
			$result = $this->db->sql_query($sql);
			while ($group = $this->db->sql_fetchrow($result))
			{
				$group_name = $this->language->is_set('G_' . $group['group_name']) ? $this->language->lang('G_' . $group['group_name']) : $group['group_name'];
				$group_ids[] = $groups[$group_name] = $group['group_id'];
			}
			if (!$user_mode)
			{
				$user_mode = 'group';
			}
		}
		$this->db->sql_freeresult($result);

		$sql = 'SELECT * FROM ' . $this->categories_table . '
			WHERE ' . $this->db->sql_in_set('category_id', $category_id, false);
		$result = $this->db->sql_query($sql);

		$table = ($user_mode == 'user') ? $this->kb_users_table : $this->kb_groups_table;
		$id_field = $user_mode . '_id';

		while ($row = $this->db->sql_fetchrow($result)) // categories
		{
			$cat_id = $row['category_id'];
			$this->template->assign_block_vars('p_mask', [
					'CATEGORY_ID'   => $cat_id,
					'CATEGORY_NAME' => $row['category_name'],
					'S_VIEW'        => $mode == 'mask',
				]
			);

			foreach ($groups as $key => $group_id) // groups
			{
				$this->template->assign_block_vars('p_mask.g_mask', [
						'GROUP_ID'   => $group_id,
						'GROUP_NAME' => $key,
						'PADDING'    => '',
					]
				);

				$options = [];
				foreach ($types as $key => $value)
				{
					$submit = $this->request->variable('submit', [[0]]);
					$inherit = $this->request->variable('inherit', [[0]]);

					$sql = 'SELECT * FROM ' . $this->options_table . '
						WHERE auth_option_id <> 0
							AND auth_option LIKE \'%' . $key . '%\'';
					$res = $this->db->sql_query($sql);

					while ($row = $this->db->sql_fetchrow($res))
					{
						$auth_option = $row['auth_option'];
						$options[$auth_option] = $row['auth_option_id'];
					}
					$this->db->sql_freeresult($res);

					foreach ($options as $name => $option)
					{
						if ($view_user_mask)
						{
							$_options[$name] = $this->permission_trace($group_id, $cat_id, $name);
						}
						else
						{
							$sql1 = 'SELECT auth_setting
								FROM ' . $table . '
								WHERE ' . $id_field . ' = ' . $group_id . '
									AND auth_option_id = ' . $option . '
									AND category_id = ' . $cat_id;
							$result1 = $this->db->sql_query($sql1);
							$auth = $this->db->sql_fetchrow($result1);

							if (!isset($auth['auth_setting']))
							{
								$auth['auth_setting'] = '';
							}
							$_options[$name] = $auth['auth_setting'];
						}
					}

					$option_settings = $this->request->variable('setting', [[['' => 0]]]);

					$res = array_diff(array_count_values($_options), ['1']);
					$index = key($res);
					$v = $res[$index];

					$all_yes = $all_never = $all_no = false;
					if (count($_options) == $v)
					{
						if ($index === 1)
						{
							$all_yes = true;
						}
						else if ($index === 0)
						{
							$all_never = true;
						}
						else if ($index === '')
						{
							$all_never = true;
							$all_no = true;
						}
					}

					$this->template->assign_block_vars('p_mask.g_mask.category', [
							'PERMISSION_TYPE' => $value,
							'S_YES'           => $all_yes,
							'S_NEVER'         => $all_never,
							'S_NO'            => $all_no,
						]
					);

					foreach ($_options as $name => $option)
					{
						$_yes = $_no = $_never = false;

						if (!isset($option) || $option === '')
						{
							$_no = true;
							$_never = true;
						}
						else if ($option)
						{
							$_yes = true;
						}
						else
						{
							$_never = true;
						}

						$this->template->assign_block_vars('p_mask.g_mask.category.mask', [
								'S_FIELD_NAME' => $name,
								'L_FIELD_NAME' => $this->language->lang($name),
								'S_YES'        => $_yes,
								'S_NO'         => $_no,
								'S_NEVER'      => $_never,
								'U_TRACE'      => ($user_mode == 'user') ? $this->u_action . '&amp;action=trace&amp;user_id=' . $group_id . '&amp;auth=' . $name . '&amp;category_id=' . $cat_id : '',
							]
						);
					}
					unset($_options, $options);
				}
			}
		}
		$this->db->sql_freeresult($result);

		if ($submit)
		{
			foreach ($submit as $key => $value)
			{
				foreach ($value as $second => $val)
				{
					$select[$key][$second] = $option_settings[$key][$second];
				}
			}
			$this->apply_all_permissions($select, $user_mode);
		}

		if ($apply_all_permissions && !empty($inherit))
		{
			foreach ($inherit as $key => $value)
			{
				foreach ($value as $second => $val)
				{
					$select[$key][$second] = $option_settings[$key][$second];
				}
			}
			$this->apply_all_permissions($select, $user_mode);
		}

		$s_hidden_fields = [
			'category_id' => $category_id,
			'group_id'    => $group_ids,
			'user_id'     => $group_ids,
			'p_mode'      => $user_mode,
		];

		$this->template->assign_vars([
				'L_TITLE'               => ($mode == 'mask') ? $this->language->lang('ACP_LIBRARY_PERMISSIONS_MASK') : $this->language->lang('ACL_SET'),
				'L_EXPLAIN'             => '', //$this->title_explain,
				'S_VIEWING_PERMISSIONS' => true,
				'S_VIEWING_MASK'        => ($mode == 'mask'),
				'S_HIDDEN_FIELDS'       => build_hidden_fields($s_hidden_fields),
			]
		);
	}

	public function permissions_v_mask($mode, $category_id, $user_id): array
	{
		$items = $this->retrieve_defined_user_groups('local', $category_id, 'kb_');

		if (empty($category_id))
		{
			$this->template->assign_vars([
					'L_TITLE'           => $this->language->lang('ACL_VIEW'),
					'L_EXPLAIN'         => $this->language->lang('ACL_VIEW_EXPLAIN'),
					'S_SELECT_CATEGORY' => true]
			);
			return [];
		}

		$s_defined_group_options = $items['group_ids_options'];
		$s_defined_user_options = $items['user_ids_options'];
		$s_hidden_fields = compact('category_id', 'user_id');

		$this->template->assign_vars([
				'S_SELECT'                    => true,
				'S_CAN_SELECT_USER'           => true,
				'S_CAN_SELECT_GROUP'          => true,
				'S_ADD_GROUP_OPTIONS'         => group_select_options(false, $items['group_ids'], false),    // Show all groups
				'U_FIND_USERNAME'             => append_sid("{$this->phpbb_root_path}memberlist.$this->php_ext", 'mode=searchuser&amp;form=add_user&amp;field=username&amp;select_single=true'),
				'S_DEFINED_GROUP_OPTIONS'     => $s_defined_group_options,
				'S_DEFINED_USER_OPTIONS'      => $s_defined_user_options,
				'S_KB_PERMISSIONS_ACTION'     => $this->u_action . '&amp;action=settings',
				'S_KB_PERMISSIONS_ACTION_USR' => $this->u_action . '&amp;action=settings&amp;p_mode=user',
				'S_HIDDEN_FIELDS'             => build_hidden_fields($s_hidden_fields),
				'MASK_MODE'                   => $mode == 'mask',
			]
		);
		return $items;
	}

	/**
	 * Get already assigned users/groups
	 */
	protected function retrieve_defined_user_groups($permission_scope, $category_id, $permission_type): array
	{
		$sql_where = '';

		$sql_category_id = ($permission_scope == 'global') ? 'AND a.category_id = 0' : ((count($category_id)) ? 'AND ' . $this->db->sql_in_set('a.category_id', $category_id) : 'AND a.category_id <> 0');

		// Permission options are only able to be a permission set... therefore we will pre-fetch the possible options and also the possible roles
		$option_ids = [];

		$sql = 'SELECT auth_option_id
			FROM ' . $this->options_table . '
			WHERE auth_option ' . $this->db->sql_like_expression($permission_type . $this->db->get_any_char());
		$result = $this->db->sql_query($sql);

		while ($row = $this->db->sql_fetchrow($result))
		{
			$option_ids[] = (int) $row['auth_option_id'];
		}
		$this->db->sql_freeresult($result);

		if (count($option_ids))
		{
			$sql_where = 'AND ' . $this->db->sql_in_set('a.auth_option_id', $option_ids) . ' ';
		}

		// Not ideal, due to the filesort, non-use of indexes, etc.
		$sql = 'SELECT DISTINCT u.user_id, u.username, u.username_clean, u.user_regdate
			FROM ' . USERS_TABLE . ' u, ' . $this->kb_users_table . ' a
			WHERE u.user_id = a.user_id ' .
			$sql_where .
			$sql_category_id . '
			ORDER BY u.username_clean, u.user_regdate ASC';
		$result = $this->db->sql_query($sql);

		$s_defined_user_options = '';
		$defined_user_ids = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$s_defined_user_options .= '<option value="' . $row['user_id'] . '">' . $row['username'] . '</option>';
			$defined_user_ids[] = $row['user_id'];
		}
		$this->db->sql_freeresult($result);

		$sql = 'SELECT DISTINCT g.group_type, g.group_name, g.group_id
			FROM ' . GROUPS_TABLE . ' g, ' . $this->kb_groups_table . ' a
			WHERE g.group_id = a.group_id ' .
			$sql_where .
			$sql_category_id . '
			ORDER BY g.group_type DESC, g.group_name ASC';
		$result = $this->db->sql_query($sql);

		$s_defined_group_options = '';
		$defined_group_ids = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$s_defined_group_options .= '<option' . (($row['group_type'] == GROUP_SPECIAL) ? ' class="sep"' : '') . ' value="' . $row['group_id'] . '">' . (($row['group_type'] == GROUP_SPECIAL) ? $this->language->lang('G_' . $row['group_name']) : $row['group_name']) . '</option>';
			$defined_group_ids[] = $row['group_id'];
		}
		$this->db->sql_freeresult($result);

		return [
			'group_ids'         => $defined_group_ids,
			'group_ids_options' => $s_defined_group_options,
			'user_ids'          => $defined_user_ids,
			'user_ids_options'  => $s_defined_user_options,
		];
	}

	/**
	 * Display a complete trace tree for the selected permission to determine where settings are set/unset
	 */
	public function permission_trace($user_id, $category_id, $permission)
	{
		if ($user_id != $this->user->data['user_id'])
		{
			$userdata = $this->auth->obtain_user_data($user_id);
		}
		else
		{
			$userdata = $this->user->data;
		}

		if (!$userdata)
		{
			trigger_error('NO_USERS', E_USER_ERROR);
		}

		if ($category_id)
		{
			$sql = 'SELECT category_name FROM ' . $this->categories_table . ' WHERE category_id = ' . $category_id;
			$result = $this->db->sql_query($sql, 3600);
			$cat_name = $this->db->sql_fetchfield('category_name');
			$this->db->sql_freeresult($result);
		}

		$this->template->assign_vars([
				'PERMISSION'          => $this->language->lang($permission),
				'PERMISSION_USERNAME' => $userdata['username'],
				'FORUM_NAME'          => $cat_name ?? '',
			]
		);

		$this->template->assign_block_vars('trace', [
				'WHO'          => $this->language->lang('DEFAULT'),
				'INFORMATION'  => $this->language->lang('TRACE_DEFAULT'),
				'S_SETTING_NO' => true,
				'S_TOTAL_NO'   => true,
			]
		);

		$sql = 'SELECT DISTINCT g.group_name, g.group_id, g.group_type
			FROM ' . GROUPS_TABLE . ' g
				LEFT JOIN ' . USER_GROUP_TABLE . ' ug ON (ug.group_id = g.group_id)
			WHERE ug.user_id = ' . (int) $user_id . '
				AND ug.user_pending = 0
				AND NOT (ug.group_leader = 1 AND g.group_skip_auth = 1)
			ORDER BY g.group_type DESC, g.group_id DESC';
		$result = $this->db->sql_query($sql);

		$groups = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$groups[$row['group_id']] = [
				'auth_setting' => ACL_NO,
				'group_name'   => $this->group_helper->get_name($row['group_name']),
			];
		}
		$this->db->sql_freeresult($result);

		$total = ACL_NO;
		$add_key = (($category_id) ? '_LOCAL' : '');

		if (count($groups))
		{
			// Get group auth settings
			$hold_ary = $this->kb_acl_group_raw_data(array_keys($groups), $permission, $category_id);

			foreach ($hold_ary as $group_id => $category_ary)
			{
				$groups[$group_id]['auth_setting'] = $category_ary[$category_id][$permission];
			}
			unset($hold_ary);

			foreach ($groups as $row)
			{
				switch ($row['auth_setting'])
				{
					case ACL_NO:
						$information = $this->language->lang('KB_TRACE_GROUP_NO' . $add_key);
					break;

					case ACL_YES:
						$information = ($total == ACL_YES) ? $this->language->lang('KB_TRACE_GROUP_YES_TOTAL_YES' . $add_key) : (($total == ACL_NEVER) ? $this->language->lang('KB_TRACE_GROUP_YES_TOTAL_NEVER' . $add_key) : $this->language->lang('KB_TRACE_GROUP_YES_TOTAL_NO' . $add_key));
						$total = ($total == ACL_NO) ? ACL_YES : $total;
					break;

					case ACL_NEVER:
						$information = ($total == ACL_YES) ? $this->language->lang('KB_TRACE_GROUP_NEVER_TOTAL_YES' . $add_key) : (($total == ACL_NEVER) ? $this->language->lang('KB_TRACE_GROUP_NEVER_TOTAL_NEVER' . $add_key) : $this->language->lang('KB_TRACE_GROUP_NEVER_TOTAL_NO' . $add_key));
						$total = ACL_NEVER;
					break;
				}

				$this->template->assign_block_vars('trace', [
					'WHO'         => $row['group_name'],
					'INFORMATION' => $information,

					'S_SETTING_NO'    => $row['auth_setting'] == ACL_NO,
					'S_SETTING_YES'   => $row['auth_setting'] == ACL_YES,
					'S_SETTING_NEVER' => $row['auth_setting'] == ACL_NEVER,
					'S_TOTAL_NO'      => $total == ACL_NO,
					'S_TOTAL_YES'     => $total == ACL_YES,
					'S_TOTAL_NEVER'   => $total == ACL_NEVER,
				]);
			}
		}

		// Get user specific permission... globally or for this category
		$hold_ary = $this->kb_acl_user_raw_data($user_id, $permission, $category_id);
		$auth_setting = (!count($hold_ary)) ? ACL_NO : $hold_ary[$user_id][$category_id][$permission];

		switch ($auth_setting)
		{
			case ACL_NO:
				$information = ($total == ACL_NO) ? $this->language->lang('KB_TRACE_USER_NO_TOTAL_NO' . $add_key) : $this->language->lang('KB_TRACE_USER_KEPT' . $add_key);
				$total = ($total == ACL_NO) ? ACL_NEVER : $total;
			break;

			case ACL_YES:
				$information = ($total == ACL_YES) ? $this->language->lang('KB_TRACE_USER_YES_TOTAL_YES' . $add_key) : (($total == ACL_NEVER) ? $this->language->lang('KB_TRACE_USER_YES_TOTAL_NEVER' . $add_key) : $this->language->lang('KB_TRACE_USER_YES_TOTAL_NO' . $add_key));
				$total = ($total == ACL_NO) ? ACL_YES : $total;
			break;

			case ACL_NEVER:
				$information = ($total == ACL_YES) ? $this->language->lang('KB_TRACE_USER_NEVER_TOTAL_YES' . $add_key) : (($total == ACL_NEVER) ? $this->language->lang('KB_TRACE_USER_NEVER_TOTAL_NEVER' . $add_key) : $this->language->lang('KB_TRACE_USER_NEVER_TOTAL_NO' . $add_key));
				$total = ACL_NEVER;
			break;
		}

		$this->template->assign_block_vars('trace', [
			'WHO'         => $userdata['username'],
			'INFORMATION' => $information,

			'S_SETTING_NO'    => $auth_setting == ACL_NO,
			'S_SETTING_YES'   => $auth_setting == ACL_YES,
			'S_SETTING_NEVER' => $auth_setting == ACL_NEVER,
			'S_TOTAL_NO'      => false,
			'S_TOTAL_YES'     => $total == ACL_YES,
			'S_TOTAL_NEVER'   => $total == ACL_NEVER,
		]);

		// Take founder or admin status into account, overwriting the default values
		if ($userdata['user_type'] == USER_FOUNDER || (!empty($this->auth->acl_get_list($userdata['user_id'], 'a_manage_kb'))))
		{
			$information = ($userdata['user_type'] == USER_FOUNDER) ? $this->language->lang('KB_TRACE_USER_FOUNDER') : $this->language->lang('KB_TRACE_USER_ADMIN');
			$this->template->assign_block_vars('trace', [
				'WHO'         => $userdata['username'],
				'INFORMATION' => $information,

				'S_SETTING_NO'    => $auth_setting == ACL_NO,
				'S_SETTING_YES'   => $auth_setting == ACL_YES,
				'S_SETTING_NEVER' => $auth_setting == ACL_NEVER,
				'S_TOTAL_NO'      => false,
				'S_TOTAL_YES'     => true,
				'S_TOTAL_NEVER'   => false,
			]);

			$total = ACL_YES;
		}

		// Total value...
		$this->template->assign_vars([
			'S_RESULT_NO'    => $total == ACL_NO,
			'S_RESULT_YES'   => $total == ACL_YES,
			'S_RESULT_NEVER' => $total == ACL_NEVER,
		]);

		return $total;
	}

	/**
	 * Get raw group based permission settings
	 */
	protected function kb_acl_group_raw_data($group_id = false, $opts = false, $category_id = false): array
	{
		$sql_group = ($group_id !== false) ? ((!is_array($group_id)) ? 'group_id = ' . (int) $group_id : $this->db->sql_in_set('group_id', array_map('intval', $group_id))) : '';
		$sql_category = ($category_id !== false) ? ((!is_array($category_id)) ? 'AND a.category_id = ' . (int) $category_id : 'AND ' . $this->db->sql_in_set('a.category_id', array_map('intval', $category_id))) : '';

		$sql_opts = '';
		$hold_ary = $sql_ary = [];

		if ($opts !== false)
		{
			$this->build_auth_option_statement('ao.auth_option', $opts, $sql_opts);
		}

		// Grab group settings - non-role specific...
		$sql_ary[] = 'SELECT a.group_id, a.category_id, a.auth_setting, a.auth_option_id, ao.auth_option
			FROM ' . $this->kb_groups_table . ' a, ' . $this->options_table . ' ao
			WHERE a.auth_option_id = ao.auth_option_id ' .
			(($sql_group) ? 'AND a.' . $sql_group : '') .
			$sql_category . ' ' .
			$sql_opts . '
			ORDER BY a.category_id, ao.auth_option';

		foreach ($sql_ary as $sql)
		{
			$result = $this->db->sql_query($sql);

			while ($row = $this->db->sql_fetchrow($result))
			{
				$hold_ary[$row['group_id']][$row['category_id']][$row['auth_option']] = $row['auth_setting'];
			}
			$this->db->sql_freeresult($result);
		}

		return $hold_ary;
	}

	/** Fill auth_option statement for later querying based on the supplied options
	 *
	 * @param $key
	 * @param $auth_options
	 * @param $sql_opts
	 * @return void
	 */
	protected function build_auth_option_statement($key, $auth_options, &$sql_opts): void
	{
		if (!is_array($auth_options))
		{
			if (str_contains($auth_options, '%'))
			{
				$sql_opts = 'AND ' . $key . $this->db->sql_like_expression(str_replace('%', $this->db->get_any_char(), $auth_options));
			}
			else
			{
				$sql_opts = 'AND ' . $key . ' = ' . $this->db->sql_escape($auth_options);
			}
		}
		else
		{
			$is_like_expression = false;

			foreach ($auth_options as $option)
			{
				if (str_contains($option, '%'))
				{
					$is_like_expression = true;
				}
			}

			if (!$is_like_expression)
			{
				$sql_opts = 'AND ' . $this->db->sql_in_set($key, $auth_options);
			}
			else
			{
				$sql = [];

				foreach ($auth_options as $option)
				{
					if (str_contains($option, '%'))
					{
						$sql[] = $key . ' ' . $this->db->sql_like_expression(str_replace('%', $this->db->get_any_char(), $option));
					}
					else
					{
						$sql[] = $key . " = '" . $this->db->sql_escape($option) . "'";
					}
				}

				$sql_opts = 'AND (' . implode(' OR ', $sql) . ')';
			}
		}
	}

	/**
	 * Get raw user based permission settings
	 */
	protected function kb_acl_user_raw_data($user_id = false, $opts = false, $category_id = false): array
	{
		$sql_user = ($user_id !== false) ? ((!is_array($user_id)) ? 'user_id = ' . (int) $user_id : $this->db->sql_in_set('user_id', array_map('intval', $user_id))) : '';
		$sql_category = ($category_id !== false) ? ((!is_array($category_id)) ? 'AND a.category_id = ' . (int) $category_id : 'AND ' . $this->db->sql_in_set('a.category_id', array_map('intval', $category_id))) : '';

		$sql_opts = '';
		$hold_ary = $sql_ary = [];

		if ($opts !== false)
		{
			$this->build_auth_option_statement('ao.auth_option', $opts, $sql_opts);
		}

		// Grab user settings - non-role specific...
		$sql_ary[] = 'SELECT a.user_id, a.category_id, a.auth_setting, a.auth_option_id, ao.auth_option
			FROM ' . $this->kb_users_table . ' a, ' . $this->options_table . ' ao
			WHERE a.auth_option_id = ao.auth_option_id ' . (($sql_user) ? 'AND a.' . $sql_user : '') .
			$sql_category . ' ' . $sql_opts . '
			ORDER BY a.category_id, ao.auth_option';

		foreach ($sql_ary as $sql)
		{
			$result = $this->db->sql_query($sql);

			while ($row = $this->db->sql_fetchrow($result))
			{
				$hold_ary[$row['user_id']][$row['category_id']][$row['auth_option']] = $row['auth_setting'];
			}
			$this->db->sql_freeresult($result);
		}

		return $hold_ary;
	}

	/**
	 * @param $hold_ary
	 * @param $user_mode
	 * @return void
	 */
	protected function apply_all_permissions($hold_ary, $user_mode): void
	{
		$sql = 'SELECT auth_option, auth_option_id
			FROM ' . $this->options_table;
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$auth_option = $row['auth_option'];
			$auth_option_ids[$auth_option] = $row['auth_option_id'];
		}

		$table = ($user_mode == 'user') ? $this->kb_users_table : $this->kb_groups_table;
		$id_field = $user_mode . '_id';
		$group_id = $user_id = $category_id = [];

		foreach ($hold_ary as $cat => $value)
		{
			foreach ($value as $group => $settings)
			{
				$category_id[] = $cat;
				foreach ($settings as $opt_name => $option)
				{
					if ($option == -1)
					{
						$sql = 'DELETE FROM ' . $table . '
							WHERE ' . $id_field . ' = ' . $group . '
								AND category_id = ' . $cat . '
								AND auth_option_id = ' . $auth_option_ids[$opt_name];
						$this->db->sql_query($sql);
					}
					else
					{
						$sql = 'SELECT * FROM ' . $table . '
							WHERE ' . $id_field . ' = ' . $group . '
								AND category_id = ' . $cat . '
								AND auth_option_id = ' . $auth_option_ids[$opt_name];

						$result = $this->db->sql_query($sql);
						$row = $this->db->sql_fetchrow($result);
						if ($row)
						{
							$sql = 'UPDATE ' . $table . ' SET auth_setting = ' . $option . '
								WHERE ' . $id_field . ' = ' . $group . '
									AND category_id = ' . $cat . '
									AND auth_option_id = ' . $auth_option_ids[$opt_name];
						}
						else
						{
							$sql = 'INSERT INTO ' . $table . '(' . $id_field . ', category_id, auth_option_id, auth_setting)
								VALUES (' . $group . ', ' . $cat . ', ' . $auth_option_ids[$opt_name] . ', ' . $option . ')';
						}
						$this->db->sql_query($sql);
					}

					if ($user_mode == 'user')
					{
						$user_id[] = $group;
					}
					else
					{
						$group_id[] = $group;
					}
				}
			}
		}

		$this->add_kb_log($group_id, $user_id, $category_id, 'LOG_LIBRARY_PERMISSION_ADD');
		$url = $this->u_action . '&amp;action=setting_group_local&amp;category_id[]=' . implode('&amp;category_id[]=', $category_id);
		trigger_error($this->language->lang('AUTH_UPDATED') . adm_back_link($url));
	}

	/**
	 * @param $group_id
	 * @param $user_id
	 * @param $category_id
	 * @param $log_type
	 * @return void
	 */
	protected function add_kb_log($group_id, $user_id, $category_id, $log_type): void
	{
		$this->log->set_log_table($this->logs_table);

		$user_mode = (empty($group_id)) ? 'user' : 'group';

		$sql = 'SELECT category_name
			FROM ' . $this->categories_table . '
			WHERE ' . $this->db->sql_in_set('category_id', $category_id) . '
			ORDER BY left_id ASC';
		$result = $this->db->sql_query($sql);

		$category_names = $names = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$category_names[] = $row['category_name'];
		}
		$this->db->sql_freeresult($result);

		if ($user_mode === 'user')
		{
			$gr_name = 'username';
			$tbl = USERS_TABLE;
			$where = $this->db->sql_in_set('user_id', $user_id);
		}
		else
		{
			$gr_name = 'group_name';
			$tbl = GROUPS_TABLE;
			$where = $this->db->sql_in_set('group_id', $group_id);
		}

		$sql = 'SELECT ' . $gr_name . ' FROM ' . $tbl . '
				WHERE ' . $where;
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$names[] = ($user_mode === 'user') ? $row['username'] : $this->group_helper->get_name($row['group_name']);
		}

		$this->db->sql_freeresult($result);

		foreach ($names as $name)
		{
			foreach ($category_names as $category_name)
			{
				$this->log->add('admin', $this->user->data['user_id'], $this->user->data['user_ip'], $log_type, time(), [$category_name, $name]);
			}
		}
	}

	/**
	 * @param $group_id
	 * @param $user_id
	 * @param $category_id
	 * @return void
	 */
	public function delete_permissions($group_id, $user_id, $category_id): void
	{
		if (empty($group_id) && empty($user_id))
		{
			return;
		}
//		$phpbb_log->set_log_table($this->logs_table);
		(empty($group_id)) ? $user_mode = 'user' : $user_mode = 'group';
		$table = ($user_mode === 'user') ? $this->kb_users_table : $this->kb_groups_table;
		$id_field = $user_mode . '_id';
		$where = ($user_mode === 'user') ? $user_id : $group_id;

		$sql = 'DELETE FROM ' . $table . '
			WHERE ' . $id_field . ' IN (' . implode(',', $where) . ')
				AND category_id IN (' . implode(',', $category_id) . ')';
		$this->db->sql_query($sql);

		$this->add_kb_log($group_id, $user_id, $category_id, 'LOG_LIBRARY_PERMISSION_DELETED');

		$url = $this->u_action . '&amp;action=setting_group_local&amp;category_id[]=' . implode('&amp;category_id[]=', $category_id);
		trigger_error($this->language->lang('AUTH_UPDATED') . adm_back_link($url));
	}
	/** END - Permission management functions */


	/** Search management functions
	 * Settings page
	 *
	 * @param int    $id
	 * @param string $mode
	 */
	public function search_settings($id, string $mode): void
	{
		$submit = $this->request->is_set_post('submit');
		if ($submit && !check_form_key('kb_acp_search'))
		{
			trigger_error($this->language->lang('FORM_INVALID') . adm_back_link($this->u_action), E_USER_WARNING);
		}

		// Create num_articles
		if (empty($this->config['kb_num_articles']))
		{
			$sql = 'SELECT COUNT(article_id) as article_count
			FROM ' . $this->articles_table;
			$result = $this->db->sql_query($sql);
			$row = $this->db->sql_fetchrow($result);
			$this->config->set('kb_num_articles', $row['article_count']);
			$this->db->sql_freeresult($result);
		}

		$settings = [
			'kb_search'          => 'bool',
			'kb_per_page_search' => 'int',
		];

		$search_options = '';

		foreach ($this->search_collection as $search)
		{
			// Only show available search backends
			if ($search->is_available())
			{
				$name = $search->get_name();
				$type = $search->get_type();

				$selected = ($this->config['kb_search_type'] === $type) ? ' selected="selected"' : '';
				$identifier = substr($type, strrpos($type, '\\') + 1);
				$search_options .= "<option value=\"{$type}\"{$selected} data-toggle-setting=\"#search_{$identifier}_settings\">{$name}</option>";
			}
		}

		$cfg_array = (isset($_REQUEST['config'])) ? $this->request->variable('config', ['' => ''], true) : [];
		$updated = $this->request->variable('updated', false);

		foreach ($settings as $config_name => $var_type)
		{
			if (!isset($cfg_array[$config_name]))
			{
				continue;
			}

			// e.g. integer:4:12 (min 4, max 12)
			$var_type = explode(':', $var_type);

			$config_value = $cfg_array[$config_name];
			settype($config_value, $var_type[0]);

			if (isset($var_type[1]))
			{
				$config_value = max($var_type[1], $config_value);
			}

			if (isset($var_type[2]))
			{
				$config_value = min($var_type[2], $config_value);
			}

			// only change config if anything was actually changed
			if ($submit && ($this->config[$config_name] != $config_value))
			{
				$this->config->set($config_name, $config_value);
				$updated = true;
			}
		}

		if ($submit)
		{
			$extra_message = '';
			if ($updated)
			{
				$this->log->add('admin', $this->user->data['user_id'], $this->user->data['user_ip'], 'LOG_KB_CONFIG_SEARCH', time());
			}

			if (isset($cfg_array['kb_search_type']) && ($cfg_array['kb_search_type'] != $this->config['kb_search_type']))
			{
				$search = $this->search_factory->get($cfg_array['kb_search_type']);
				if (confirm_box(true))
				{
					// Initialize search backend, if $error is false means that everything is ok
					if (!($error = $search->init()))
					{
						$this->config->set('kb_search_type', $cfg_array['kb_search_type']);

						if (!$updated)
						{
							$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_CONFIG_SEARCH');
						}
						$extra_message = '<br>' . $this->language->lang('SWITCHED_SEARCH_BACKEND');
					}
					else
					{
						trigger_error($error . adm_back_link($this->u_action), E_USER_WARNING);
					}
				}
				else
				{
					confirm_box(false, $this->language->lang('CONFIRM_SEARCH_BACKEND'), build_hidden_fields([
						'i'       => $id,
						'mode'    => $mode,
						'submit'  => true,
						'updated' => $updated,
						'config'  => ['kb_search_type' => $cfg_array['kb_search_type']],
					]));
				}
			}

			trigger_error($this->language->lang('CONFIG_UPDATED') . $extra_message . adm_back_link($this->u_action));
		}
		unset($cfg_array);

		$this->template->assign_vars([
			'S_SEARCH_TYPES'     => $search_options,
			'S_YES_SEARCH'       => (bool) $this->config['kb_search'],
			'S_SETTINGS'         => true,
			'PER_PAGE_KB_SEARCH' => ($this->config['kb_per_page_search']) ? $this->config['kb_per_page_search'] : 10,

			'U_ACTION' => $this->u_action . '&amp;hash=' . generate_link_hash('kb_acp_search'),
		]);
	}

	/**
	 * Execute action depending on the action and state
	 *
	 * @param int    $id
	 * @param string $mode
	 * @throws Exception
	 */
	public function search_index($id, string $mode): void
	{
		$action = $this->request->variable('action', '');
		$state = !empty($this->config['search_indexing_state']) ? explode(',', $this->config['search_indexing_state']) : [];

		if ($action && !$this->request->is_set_post('cancel'))
		{
			switch ($action)
			{
				case 'progress_bar':
					$this->display_progress_bar();
				break;

				case 'create':
				case 'delete':
					$this->index_action($id, $mode, $action, $state);
				break;

				default:
					trigger_error('NO_ACTION', E_USER_ERROR);
			}
		}
		else
		{
			// If clicked to cancel the indexing progress (acp_search_index_inprogress form)
			if ($this->request->is_set_post('cancel'))
			{
				$state = [];
				$this->save_state($state);
			}

			if (!empty($state))
			{
				$this->index_inprogress($id, $mode, $state[self::STATE_ACTION]);
			}
			else
			{
				$this->index_overview($id, $mode);
			}
		}
	}

	/**
	 * @param int    $id
	 * @param string $mode
	 *
	 * @throws Exception
	 */
	private function index_overview($id, string $mode): void
	{
		foreach ($this->search_collection as $search)
		{
			$this->template->assign_block_vars('backend', [
				'NAME' => $search->get_name(),
				'TYPE' => $search->get_type(),

				'S_ACTIVE'        => $search->get_type() === $this->config['kb_search_type'],
				'S_HIDDEN_FIELDS' => build_hidden_fields(['kb_search_type' => $search->get_type()]),
				'S_INDEXED'       => $search->index_created(),
				'S_STATS'         => $search->index_stats(),
			]);
		}

		$this->template->assign_vars([
			'U_ACTION'        => $this->u_action,
			'UA_PROGRESS_BAR' => addslashes($this->u_action . '&amp;action=progress_bar'),
		]);
	}

	/**
	 * Form to continue or cancel indexing process
	 *
	 * @param int    $id
	 * @param string $mode
	 * @param string $action Action in progress: 'create' or 'delete'
	 */
	private function index_inprogress($id, string $mode, string $action): void
	{
		$this->template->assign_vars([
			'U_ACTION'           => $this->u_action . '&amp;action=' . $action . '&amp;hash=' . generate_link_hash('kb_acp_search'),
			'UA_PROGRESS_BAR'    => addslashes($this->u_action . '&amp;action=progress_bar'),
			'L_CONTINUE'         => ($action === 'create') ? $this->language->lang('CONTINUE_INDEXING') : $this->language->lang('CONTINUE_DELETING_INDEX'),
			'L_CONTINUE_EXPLAIN' => ($action === 'create') ? $this->language->lang('CONTINUE_INDEXING_EXPLAIN') : $this->language->lang('CONTINUE_DELETING_INDEX_EXPLAIN'),
			'S_ACTION'           => $action,
		]);
	}

	/**
	 * Progress that do the indexing/index removal, updating the page continuously until is finished
	 *
	 * @param int    $id
	 * @param string $mode
	 * @param string $action
	 * @param array  $state
	 */
	private function index_action($id, string $mode, string $action, array $state): void
	{
		// For some this may be of help...
		ini_set('memory_limit', '128M');

//		if (!check_link_hash($this->request->variable('hash', ''), 'kb_acp_search'))
//		{
//			trigger_error($this->language->lang('FORM_INVALID') . adm_back_link($this->u_action), E_USER_WARNING);
//		}

		// Entering here for the first time
		if (empty($state))
		{
			if ($this->request->is_set_post('kb_search_type'))
			{
				$state = [
					self::STATE_SEARCH_TYPE  => $this->request->variable('kb_search_type', ''),
					self::STATE_ACTION       => $action,
					self::STATE_POST_COUNTER => 0,
				];
			}
			else
			{
				trigger_error($this->language->lang('FORM_INVALID') . adm_back_link($this->u_action), E_USER_WARNING);
			}

			$this->save_state($state); // Create new state in the database
		}

		$type = $state[self::STATE_SEARCH_TYPE];
		$action = $state[self::STATE_ACTION];
		$post_counter = &$state[self::STATE_POST_COUNTER];

		// Execute create/delete
		$search = $this->search_factory->get($type);

		try
		{
			$status = ($action == 'create') ? $search->create_index($post_counter) : $search->delete_index($post_counter);
			if ($status) // Status is not null, so action is in progress....
			{
				$this->save_state($state); // update $post_counter in $state in the database

				$u_action = append_sid($this->u_action, 'i=' . $id . '&mode=' . $mode . '&action=' . $action);
				meta_refresh(1, $u_action);

				$message_redirect = $this->language->lang(($action == 'create') ? 'SEARCH_INDEX_CREATE_REDIRECT' : 'SEARCH_INDEX_DELETE_REDIRECT', (int) $status['row_count'], $status['post_counter']);
				$message_rate = $this->language->lang(($action == 'create') ? 'SEARCH_INDEX_CREATE_REDIRECT_RATE' : 'SEARCH_INDEX_DELETE_REDIRECT_RATE', $status['rows_per_second']);
				trigger_error($message_redirect . $message_rate);
			}
		}
		catch (Exception $e)
		{
			$this->save_state([]); // Unexpected error, cancel action
			trigger_error($e->getMessage() . adm_back_link($this->u_action) . $this->close_popup_js(), E_USER_WARNING);
		}

		$search->tidy();

		$this->save_state([]); // finished operation, cancel action

		$log_operation = ($action == 'create') ? 'LOG_SEARCH_INDEX_CREATED' : 'LOG_SEARCH_INDEX_REMOVED';
		$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, $log_operation, false, [$search->get_name()]);

		$message = $this->language->lang(($action == 'create') ? 'SEARCH_INDEX_CREATED' : 'SEARCH_INDEX_REMOVED');
		trigger_error($message . adm_back_link($this->u_action) . $this->close_popup_js());
	}

	/**
	 * Popup window
	 */
	private function display_progress_bar(): void
	{
		$type = $this->request->variable('type', '');
		$l_type = ($type === 'create') ? 'INDEXING_IN_PROGRESS' : 'DELETING_INDEX_IN_PROGRESS';

		adm_page_header($this->language->lang($l_type));

		$this->template->set_filenames([
			'body' => 'progress_bar.html',
		]);

		$this->template->assign_vars([
			'L_PROGRESS'         => $this->language->lang($l_type),
			'L_PROGRESS_EXPLAIN' => $this->language->lang($l_type . '_EXPLAIN'),
		]);

		adm_page_footer();
	}

	/**
	 * Javascript code for closing the waiting screen (is attached to the trigger_errors)
	 *
	 * @return string
	 */
	private function close_popup_js(): string
	{
		return "<script type=\"text/javascript\">\n" .
			"// <![CDATA[\n" .
			"	close_waitscreen = 1;\n" .
			"// ]]>\n" .
			"</script>\n";
	}

	/**
	 * @param array $state
	 */
	private function save_state(array $state = []): void
	{
		ksort($state);

		$this->config->set('search_indexing_state', implode(',', $state), true);
	}
	/**
	 * END - Search management functions
	 */


	/**
	 * Set page url
	 *
	 * @param string $u_action Custom form action
	 * @return void
	 * @access public
	 */
	public function set_page_url(string $u_action): void
	{
		$this->u_action = $u_action;
	}
}
