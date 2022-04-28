<?php

/**
 *
 * Knowledge base. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2017, Sheer
 * @license       GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace sheer\knowledgebase\inc;

use phpbb\auth\auth;
use phpbb\cache\service;
use phpbb\config\config;
use phpbb\config\db_text;
use phpbb\controller\helper;
use phpbb\db\driver\driver_interface;
use phpbb\extension\manager;
use phpbb\language\language;
use phpbb\log\log_interface;
use phpbb\template\template;
use phpbb\user;

class functions_kb
{
	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \phpbb\config\config $config */
	protected $config;

	/** @var \phpbb\config\db_text */
	protected $config_text;

	/** @var \phpbb\controller\helper */
	protected $helper;

	/** @var \phpbb\extension\manager */
	protected $ext_manager;

	/** @var \phpbb\language\language */
	protected $language;

	/** @var \phpbb\auth\auth */
	protected $auth;

	/** @var \phpbb\template\template */
	protected $template;

	/** @var \phpbb\user */
	protected $user;

	/** @var \phpbb\cache\service */
	protected $cache;

	/** @var \phpbb\log\log_interface */
	protected $log;

	/** @var string phpbb_root_path */
	protected $phpbb_root_path;

	/** @var string php_ext */
	protected $php_ext;

	/** @var string */
	protected $articles_table;

	/** @var string */
	protected $categories_table;

	/** @var string */
	protected $kb_logs_table;

	/** @var string */
	protected $attachments_table;

	/** @var string */
	protected $options_table;

	/** @var string */
	protected $kb_users_table;

	/** @var string */
	protected $kb_groups_table;

	/** @var string */
	protected $upload_dir;

	/**
	 * Constructor
	 *
	 * @param \phpbb\db\driver\driver_interface $db
	 * @param \phpbb\config\config     $config
	 * @param \phpbb\config\db_text    $config_text
	 * @param \phpbb\controller\helper $helper
	 * @param \phpbb\extension\manager $ext_manager
	 * @param \phpbb\language\language $language
	 * @param \phpbb\auth\auth         $auth
	 * @param \phpbb\template\template $template
	 * @param \phpbb\user              $user
	 * @param \phpbb\cache\service     $cache
	 * @param \phpbb\log\log_interface $log
	 * @param string                   $phpbb_root_path
	 * @param string                   $php_ext
	 * @param string                   $articles_table
	 * @param string                   $categories_table
	 * @param string                   $kb_logs_table
	 * @param string                   $attachments_table
	 * @param string                   $options_table
	 * @param string                   $kb_users_table
	 * @param string                   $kb_groups_table
	 */
	public function __construct(
		driver_interface $db,
		config $config,
		db_text $config_text,
		helper $helper,
		manager $ext_manager,
		language $language,
		auth $auth,
		template $template,
		user $user,
		service $cache,
		log_interface $log,
		string $phpbb_root_path,
		string $php_ext,
		string $articles_table,
		string $categories_table,
		string $kb_logs_table,
		string $attachments_table,
		string $options_table,
		string $kb_users_table,
		string $kb_groups_table
	)
	{
		$this->db = $db;
		$this->config = $config;
		$this->config_text = $config_text;
		$this->helper = $helper;
		$this->ext_manager	= $ext_manager;
		$this->language = $language;
		$this->auth = $auth;
		$this->template = $template;
		$this->user = $user;
		$this->cache = $cache;
		$this->log = $log;
		$this->phpbb_root_path = $phpbb_root_path;
		$this->php_ext = $php_ext;
		$this->articles_table = $articles_table;
		$this->categories_table = $categories_table;
		$this->kb_logs_table = $kb_logs_table;
		$this->attachments_table = $attachments_table;
		$this->options_table = $options_table;
		$this->kb_users_table = $kb_users_table;
		$this->kb_groups_table = $kb_groups_table;

		$this->upload_dir = $this->ext_manager->get_extension_path('sheer/knowledgebase', true) . 'files/';
		$this->log->set_log_table($this->kb_logs_table);
	}

	/**
	 * get_category_branch
	 *
	 * @param int    $category_id
	 * @param string $type
	 * @param string $order
	 * @param bool   $include_category
	 *
	 * @return array
	 */
	public function get_category_branch(int $category_id, string $type = 'all', string $order = 'descending', bool $include_category = true): array
	{
		switch ($type)
		{
			case 'parents':
				$condition = 'f1.left_id BETWEEN f2.left_id AND f2.right_id';
			break;

			case 'children':
				$condition = 'f2.left_id BETWEEN f1.left_id AND f1.right_id';
			break;

			default:
				$condition = 'f2.left_id BETWEEN f1.left_id AND f1.right_id OR f1.left_id BETWEEN f2.left_id AND f2.right_id';
			break;
		}

		$rows = array();

		$sql = 'SELECT f2.*
			FROM ' . $this->categories_table . ' f1
			LEFT JOIN ' . $this->categories_table . " f2 ON ($condition)
			WHERE f1.category_id = $category_id
			ORDER BY f2.left_id " . (($order == 'descending') ? 'ASC' : 'DESC');
		$result = $this->db->sql_query($sql);

		while ($row = $this->db->sql_fetchrow($result))
		{
			if (!$include_category && $row['category_id'] == $category_id)
			{
				continue;
			}
			$rows[] = $row;
		}
		$this->db->sql_freeresult($result);
		return $rows;
	}

	public function get_cat_list($ignore_id = false): string
	{
		$right = 0;
		$padding_store = array('0' => '');
		$padding = $cat_list = '';

		$sql = 'SELECT category_id, category_name, parent_id, left_id, right_id, number_articles
			FROM ' . $this->categories_table . '
			ORDER BY left_id ASC';
		$result = $this->db->sql_query($sql, 600);

		while ($row = $this->db->sql_fetchrow($result))
		{
			if ($row['left_id'] < $right)
			{
				$padding .= '&nbsp; &nbsp;';
				$padding_store[$row['parent_id']] = $padding;
			}
			else if ($row['left_id'] > $right + 1)
			{
				$padding = (isset($padding_store[$row['parent_id']])) ? $padding_store[$row['parent_id']] : '';
			}

			$right = $row['right_id'];

			if ((is_array($ignore_id) && in_array($row['category_id'], $ignore_id)) || $row['category_id'] == $ignore_id)
			{
				$sql_where = ($this->auth->acl_get('a_manage_kb') || $this->acl_kb_get($row['category_id'], 'kb_m_approve')) ? '' : 'AND approved = 1';
				$sql = 'SELECT COUNT(article_id) AS articles
					FROM ' . $this->articles_table . '
					WHERE article_category_id = ' . (int) $row['category_id'] . '
						' . $sql_where;
				$res = $this->db->sql_query($sql);
				$art_row = $this->db->sql_fetchrow($res);
				$this->db->sql_freeresult($res);
				$cat_list .= $padding . '<a href="' . $this->helper->route('sheer_knowledgebase_category', array('id' => $row['category_id'])) . '">' . $row['category_name'] . '</a> (' . $this->language->lang('ARTICLES') . ': ' . $art_row['articles'] . ')<br>';
			}
		}
		$this->db->sql_freeresult($result);
		unset($padding_store);
		return $cat_list;
	}

	public function gen_kb_auth_level($category_id)
	{
		$rules = array(
			($this->acl_kb_get($category_id, 'kb_u_add')) ? $this->language->lang('RULES_KB_ADD_CAN') : $this->language->lang('RULES_KB_ADD_CANNOT'),
		);

		if ($this->acl_kb_get($category_id, 'kb_m_delete'))
		{
			$rules = array_merge($rules, array(
				$this->language->lang('RULES_KB_DELETE_MOD_CAN'),
			));
		}
		else
		{
			$rules = array_merge($rules, array(
				($this->acl_kb_get($category_id, 'kb_u_delete')) ? $this->language->lang('RULES_KB_DELETE_CAN') : $this->language->lang('RULES_KB_DELETE_CANNOT'),
			));
		}

		if ($this->acl_kb_get($category_id, 'kb_m_edit'))
		{
			$rules = array_merge($rules, array(
				$this->language->lang('RULES_KB_EDIT_MOD_CAN'),
			));
		}
		else
		{
			$rules = array_merge($rules, array(
				($this->acl_kb_get($category_id, 'kb_u_edit')) ? $this->language->lang('RULES_KB_EDIT_CAN') : $this->language->lang('RULES_KB_EDIT_CANNOT'),
			));
		}

		if ($this->acl_kb_get($category_id, 'kb_m_approve'))
		{
			$rules = array_merge($rules, array(
				$this->language->lang('RULES_KB_APPROVE_MOD_CAN'),
			));
		}

		foreach ($rules as $rule)
		{
			$this->template->assign_block_vars('rules', array('RULE' => $rule));
		}
	}

	public function acl_kb_get($category_id, $permission): bool
	{
		$sql = 'SELECT DISTINCT g.group_name, g.group_id, g.group_type
			FROM ' . GROUPS_TABLE . ' g
				LEFT JOIN ' . USER_GROUP_TABLE . ' ug ON (ug.group_id = g.group_id)
			WHERE ug.user_id = ' . (int) $this->user->data['user_id'] . '
				AND ug.user_pending = 0
				AND NOT (ug.group_leader = 1 AND g.group_skip_auth = 1)
			ORDER BY g.group_type DESC, g.group_id DESC';
		$result = $this->db->sql_query($sql);

		$groups = $user_groups = array();
		while ($row = $this->db->sql_fetchrow($result))
		{
			$groups[$row['group_id']] = array(
				'auth_setting' => ACL_NO,
			);
			$user_groups[] = $row['group_id'];
		}
		$this->db->sql_freeresult($result);

		$total = ACL_NO;

		if (count($groups))
		{
			// Get group auth settings
			$sql_ary[] = 'SELECT a.group_id, a.category_id, a.auth_setting, a.auth_option_id, ao.auth_option
					FROM ' . $this->kb_groups_table . ' a, ' . $this->options_table . ' ao
					WHERE a.auth_option_id = ao.auth_option_id AND ' . $this->db->sql_in_set('a.group_id', array_map('intval', $user_groups)) . '
						AND a.category_id = ' . $category_id . '
						AND ao.auth_option = \'' . $permission . '\'
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

			if (!empty($hold_ary))
			{
				foreach ($hold_ary as $group_id => $category_ary)
				{
					$groups[$group_id]['auth_setting'] = $category_ary[$category_id][$permission];
				}
				unset($hold_ary);
			}

			foreach ($groups as $row)
			{
				switch ($row['auth_setting'])
				{
					case ACL_NO:
					break;

					case ACL_YES:
						$total = ($total == ACL_NO) ? ACL_YES : $total;
					break;

					case ACL_NEVER:
						$total = ACL_NEVER;
					break;
				}
			}
		}

		// Grab user settings - non-role specific...
		$sql = 'SELECT a.user_id, a.category_id, a.auth_setting, a.auth_option_id, ao.auth_option
			FROM ' . $this->kb_users_table . ' a, ' . $this->options_table . ' ao
			WHERE a.auth_option_id = ao.auth_option_id
				AND a.user_id = ' . (int) $this->user->data['user_id'] . ' AND a.category_id = 1 AND ao.auth_option = \'' . $permission . '\'';

		$result = $this->db->sql_query($sql);
		$auth_option = $this->db->sql_fetchfield('auth_option_id');

		if ($auth_option == 1)
		{
			$total = ACL_YES;
		}
		$this->db->sql_freeresult($result);

		// Take founder or admin status into account, overwriting the default values
		if ($this->user->data['user_type'] == USER_FOUNDER || $this->auth->acl_get('a_manage_kb'))
		{
			$total = ACL_YES;
		}

		return $total && $total > 0;
	}

	public function make_category_dropbox(): string
	{
		$sql = 'SELECT *
			FROM ' . $this->categories_table . '
			ORDER BY left_id ASC';
		$result = $this->db->sql_query($sql, 600);

		$cats_list = '';
		$padding = '';
		$padding_store = [];
		$right = 0;

		while ($row = $this->db->sql_fetchrow($result))
		{
			if ($row['left_id'] < $right) {
				$padding .= '&nbsp;&nbsp;';
				$padding_store[$row['parent_id']] = $padding;
			} else if ($row['left_id'] > $right + 1) {
				$padding = isset($padding_store[$row['parent_id']]) ? $padding_store[$row['parent_id']] : '';
			}
			$right = $row['right_id'];
			$category_parent = $row['parent_id'] == 0;

			$class = ($category_parent) ? ' class="jumpbox-cat-link"' : ' class="jumpbox-forum-link"';
			$category_name = ($category_parent) ? $padding . $row['category_name'] : $padding . '&raquo;&nbsp;&nbsp;' . $row['category_name'];
			$cats_list .= '<li><a href="' . $this->helper->route('sheer_knowledgebase_category', array('id' => $row['category_id'])) . '" ' . $class . '>' . $category_name . '</a></li>';
		}
		$this->db->sql_freeresult($result);
		unset($padding_store);

		return $cats_list;
	}

	public function make_category_select($select_id = false, $ignore_id = false, $ignore_acl = false): string
	{
		$sql = 'SELECT *
			FROM ' . $this->categories_table . '
			ORDER BY left_id ASC';
		$result = $this->db->sql_query($sql, 600);

		$cats_list = '';
		$padding = '';
		$padding_store = [];
		$right = 0;

		while ($row = $this->db->sql_fetchrow($result))
		{
			if ($row['left_id'] < $right) {
				$padding .= '&nbsp;&nbsp;';
				$padding_store[$row['parent_id']] = $padding;
			} else if ($row['left_id'] > $right + 1) {
				$padding = isset($padding_store[$row['parent_id']]) ? $padding_store[$row['parent_id']] : '';
			}
			$right = $row['right_id'];
			$disabled = false;

			if (!$ignore_acl && ((is_array($ignore_id) && in_array($row['category_id'], $ignore_id)) || !$this->acl_kb_get($row['category_id'], 'kb_u_add')))
			{
				$disabled = true;
			}

			$category_name = $padding . '&nbsp;&nbsp;' . $row['category_name'];
			$selected = (is_array($select_id)) ? ((in_array($row['category_id'], $select_id)) ? ' selected="selected"' : '') : (($row['category_id'] == $select_id) ? ' selected="selected"' : '');
			$cats_list .= '<option value="' . $row['category_id'] . '"' . (($disabled) ? ' disabled="disabled" class="disabled-option"' : $selected) . '>' . $category_name . '</option>';
		}
		$this->db->sql_freeresult($result);
		unset($padding_store);
		return $cats_list;
	}

	public function allowed_extension(): array
	{
		$enabled_extensions = [];
		$extensions = json_decode($this->config_text->get('kb_extensions'), true);

		foreach ($extensions as $extension)
		{
			$enabled_extensions = array_merge($enabled_extensions, array_values($extension));
		}
		return $enabled_extensions;
	}

	public function generate_filter_string(): string
	{
		$filters = [];

		$attach_extensions = json_decode($this->config_text->get('kb_extensions'), true);

		// Re-arrange the extension array
		foreach ($attach_extensions as $group => $group_info)
		{
			$filters[] = sprintf(
				"{title: '%s', extensions: '%s', max_file_size: %s}",
				addslashes(ucfirst(strtolower($group))),
				addslashes(implode(',', $group_info)),
				(int) $this->config['kb_max_filesize']
			);
		}
		return implode(',', $filters);
	}

	public function kb_delete_article($id, $article_title)
	{
		$attachment_data = [];
		include_once($this->phpbb_root_path . 'includes/functions_admin.' . $this->php_ext);

		$info = $this->get_kb_article_info($id);
		$category_id = $info['article_category_id'];

		$cat_info = $this->get_cat_info($category_id);
		$articles_count = $cat_info['number_articles'];

		$sql = 'UPDATE ' . $this->articles_table . ' SET display_order = display_order - 1
			WHERE article_category_id = ' . (int) $category_id . '
			AND display_order > ' . (int) $info['display_order'];
		$this->db->sql_query($sql);

		$sql = 'DELETE FROM ' . $this->articles_table . '
			WHERE article_id = ' . (int) $id;
		$this->db->sql_query($sql);

		$ids = array();
		$sql = 'SELECT attach_id, physical_filename, thumbnail
			FROM ' . $this->attachments_table . '
			WHERE article_id = ' . (int) $id;
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$attachment_data[] = $row;
			$ids[] = $row['attach_id'];
		}
		$this->db->sql_freeresult($result);

		if (!empty($ids))
		{
			foreach ($attachment_data as $attachment)
			{
				@unlink($this->upload_dir . $attachment['physical_filename']);
				if ($attachment['thumbnail'])
				{
					@unlink($this->upload_dir . 'thumb_' . $attachment['physical_filename']);
				}
			}
			$sql = 'DELETE FROM ' . $this->attachments_table . '
				WHERE ' . $this->db->sql_in_set('attach_id', $ids);
			$this->db->sql_query($sql);
		}
		$articles_count--;
		if ($articles_count >= 0)
		{
			$sql = 'UPDATE ' . $this->categories_table . '
				SET number_articles = ' . $articles_count . '
				WHERE category_id = ' . (int) $category_id;
			$this->db->sql_query($sql);
		}
		$this->cache->destroy('sql', $this->categories_table);
		$this->config->increment('kb_num_articles', -1);

		delete_topics('topic_id', array($info['topic_id']), true, true, true);
		$this->log->add('admin', $this->user->data['user_id'], $this->user->data['user_ip'], 'LOG_LIBRARY_DEL_ARTICLE', time(), array($article_title, $cat_info['category_name']));
	}

	public function get_kb_article_info($art_id)
	{
		$sql = 'SELECT *
			FROM ' . $this->articles_table . '
			WHERE article_id = ' . (int) $art_id;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);
		if (!$row)
		{
			trigger_error($this->language->lang('ARTICLE_NO_EXISTS'));
		}
		return $row;
	}

	public function get_cat_info($category_id)
	{
		$sql = 'SELECT *
			FROM ' . $this->categories_table . "
			WHERE category_id = $category_id";
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		if (!$row)
		{
			return false;
		}
		return $row;
	}

	public function kb_move_article($k, $article_title, $cat_id, $id, $order)
	{
		// Change category id in the table of articles
		$sql = 'UPDATE ' . $this->articles_table . '
			SET article_category_id = ' . (int) $id . ', display_order = 0
			WHERE article_id = ' . (int) $k;
		$this->db->sql_query($sql);

		// Change display order in categories
		$sql = 'UPDATE ' . $this->articles_table . ' SET display_order = display_order + 1
			WHERE article_category_id = ' . (int) $id;
		$this->db->sql_query($sql);

		$sql = 'UPDATE ' . $this->articles_table . ' SET display_order = display_order - 1
			WHERE article_category_id = ' . (int) $cat_id . '
			AND display_order > ' . (int) $order;
		$this->db->sql_query($sql);

		// recalculate the number of articles in source category
		$cat_info = $this->get_cat_info($cat_id);
		$articles_count = $cat_info['number_articles'];
		$articles_count--;
		// ... and a new category
		$to_cat_info = $this->get_cat_info($id);
		$to_articles_count = $to_cat_info['number_articles'];
		$to_articles_count++;
		// change in DB
		$sql = 'UPDATE ' . $this->categories_table . '
			SET number_articles = ' . $articles_count . '
			WHERE category_id = ' . (int) $cat_id;
		$this->db->sql_query($sql);

		$sql = 'UPDATE ' . $this->categories_table . '
			SET number_articles = ' . $to_articles_count . '
			WHERE category_id = ' . (int) $id;
		$this->db->sql_query($sql);
		$this->cache->destroy('sql', $this->categories_table);
		$this->log->add('admin', $this->user->data['user_id'], $this->user->data['user_ip'], 'LOG_LIBRARY_MOVED_ARTICLE', time(), array($article_title, $cat_info['category_name'], $to_cat_info['category_name']));
	}

	public function submit_article($cat_id, $fid, $article_title, $article_description, $article_author, $category_name, $new)
	{
		$options = '';
		$topic_title = '';

		$sql = 'SELECT forum_id
			FROM ' . FORUMS_TABLE . '
			WHERE forum_id = ' . (int) $fid;
		$result = $this->db->sql_query($sql);
		if ($this->db->sql_fetchrow($result))
		{
			$topic_title = $article_title;
		}
		else
		{
			trigger_error($this->language->lang('NO_FORUM'));
		}
		$this->db->sql_freeresult($result);

		$topic_text = '[b]' . $this->language->lang('ARTICLE_TITLE') . $this->language->lang('COLON') . '[/b] ' . $article_title;
		$topic_text .= "\n";
		$topic_text .= '[b]' . $this->language->lang('ARTICLE_AUTHOR') . $this->language->lang('COLON') . '[/b] ' . $article_author;
		$topic_text .= "\n";
		$topic_text .= '[b]' . $this->language->lang('ARTICLE_DESCRIPTION') . $this->language->lang('COLON') . '[/b] ' . $article_description;
		$topic_text .= "\n";
		$topic_text .= '[b]' . $this->language->lang('CATEGORY') . $this->language->lang('COLON') . '[/b] ' . $category_name;
		$topic_text .= "\n\n";
		$topic_text .= '[b][url=' . generate_board_url() . '/knowledgebase/article?k=' . $new . ']' . $this->language->lang('READ_FULL') . '[/url][/b]';

		generate_text_for_storage($topic_text, $bbcode_uid, $bitfield, $options, true, true, true);

		$data = array(
			'topic_title'      => $topic_title,
			'forum_id'         => $fid,
			'forum_name'       => '',
			'icon_id'          => 0,
			'poster_id'        => (int) $this->user->data['user_id'],
			'enable_bbcode'    => true,
			'enable_smilies'   => true,
			'enable_urls'      => true,
			'enable_sig'       => true,
			'notify'           => 0,
			'notify_set'       => '',
			'enable_indexing'  => true,
			'message'          => htmlspecialchars_decode($topic_text),
			'message_md5'      => md5(time()),
			'bbcode_bitfield'  => $bitfield,
			'bbcode_uid'       => substr(md5(rand()), 0, 8),
			'post_edit_locked' => 0,
		);

		submit_post('post', $topic_title, $this->user->data['username'], 0, $poll, $data, false);

		$sql = 'SELECT MAX(topic_id) AS last_topic
			FROM ' . TOPICS_TABLE . '
			WHERE forum_id = ' . (int) $fid;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		$last_topic = $row['last_topic'];
		$sql = 'UPDATE ' . $this->articles_table . '
			SET topic_id = ' . (int) $last_topic . '
			WHERE article_id = ' . (int) $new;
		$this->db->sql_query($sql);
	}

	public function parse_att(&$text, $attachments)
	{
		krsort($attachments);
		preg_match_all('#<!\-\- ia([0-9]+) \-\->(.*?)<!\-\- ia\1 \-\->#', $text, $matches, PREG_PATTERN_ORDER);
		$replace = array();
		foreach ($matches[0] as $num => $capture)
		{
			$index = $matches[1][$num];
			$comment = ($attachments[$index]['attach_comment']) ? '<dd>' . $attachments[$index]['attach_comment'] . '</dd>' : '';
			if ($attachments[$index]['thumbnail'] == 1)
			{
				$replacement = '<dl class="thumbnail"><dt><a href="' . $this->helper->route('sheer_knowledgebase_kb_file', array('id' => $attachments[$index]['attach_id'])) . '"><img src="' . $this->helper->route('sheer_knowledgebase_kb_file', array('id' => $attachments[$index]['attach_id'])) . '&amp;t=1" class="postimage" alt="' . $attachments[$index]['real_filename'] . '" title="' . $attachments[$index]['real_filename'] . '"></a></dt>' . $comment . '</dl>';
			}
			else
			{
				if ($this->check_is_img($attachments[$index]['extension'], $extensions))
				{
					$replacement = '<dl class="file"><dt class="attach-image"><img src="' . $this->helper->route('sheer_knowledgebase_kb_file', array('id' => $attachments[$index]['attach_id'])) . '" class="postimage" alt="' . $attachments[$index]['real_filename'] . '" onclick="viewableArea(this);"></dt><dd>' . $comment . '</dd></dl>';
				}
				else if ($attachments[$index]['extension'] == 'mp3' || $attachments[$index]['extension'] == 'mp4')
				{
					$replacement = '<p><audio src="' . $this->helper->route('sheer_knowledgebase_kb_file', array('id' => $attachments[$index]['attach_id'])) . '" controls preload="none"></audio>';
					if ($attachments[$index]['attach_comment'])
					{
						$replacement .= '<br>' . $attachments[$index]['attach_comment'];
					}
					$replacement .= '</p>';
				}
				else
				{
					$icon = ($extensions[$attachments[$index]['extension']]['upload_icon']) ? '<img src="' . generate_board_url() . '/images/upload_icons/' . $extensions[$attachments[$index]['extension']]['upload_icon'] . '" alt="">' : '';
					$replacement = '<dl class="file"><dt>' . $icon . ' <a class="postlink" href="' . $this->helper->route('sheer_knowledgebase_kb_file', array('id' => $attachments[$index]['attach_id'])) . '">' . $attachments[$index]['real_filename'] . '</a></dt></dl>';
				}
			}
			$replace['from'][] = $matches[0][$num];
			$replace['to'][] = (isset($attachments[$index])) ? $replacement : sprintf($this->language->lang('MISSING_INLINE_ATTACHMENT'), $matches[2][array_search($index, $matches[1])]);
			$unset_tpl[] = $index;
		}

		if (isset($replace['from']))
		{
			$text = str_replace($replace['from'], $replace['to'], $text);
		}

		if (isset($unset_tpl))
		{
			foreach ($attachments as $num => $attach)
			{
				if (array_key_exists($num, $unset_tpl))
				{
					unset($attachments[$unset_tpl[$num]]);
				}
			}
		}

		if (count($attachments))
		{
			$text .= '</div><dl class="attachbox"><dt>' . $this->language->lang('ATTACHMENTS') . '</dt>';
			foreach ($attachments as $value)
			{
				$comment = ($value['attach_comment']) ? '<dd>' . $value['attach_comment'] . '</dd>' : '';
				if ($value['thumbnail'])
				{
					$text .= '<dd><dl class="thumbnail"><dt><a href="' . $this->helper->route('sheer_knowledgebase_kb_file', array('id' => $value['attach_id'])) . '"><img alt= "" src="' . $this->helper->route('sheer_knowledgebase_kb_file', array('id' => $value['attach_id'])) . '&amp;t=1"></a></dt>' . $comment . '</dl></dd>';
				}
				else
				{
					if ($this->check_is_img($value['extension'], $extensions))
					{
						$text .= '<dd><dl class="file">';
						$text .= '<dt class="attach-image"><img src="' . $this->helper->route('sheer_knowledgebase_kb_file', array('id' => $value['attach_id'])) . '" class="postimage" alt="' . $value['real_filename'] . '" onclick="viewableArea(this);"></dt>' . $comment;
					}
					else if ($value['extension'] == 'mp3')
					{
						$text .= '<p><audio src="' . $this->helper->route('sheer_knowledgebase_kb_file', array('id' => $value['attach_id'])) . '" controls preload="none"></audio>';
						if ($value['attach_comment'])
						{
							$text .= '<br>' . $value['attach_comment'];
						}
						$text .= '</p>';
					}
					else
					{
						$icon = ($extensions[$value['extension']]['upload_icon']) ? '<img src="' . generate_board_url() . '/images/upload_icons/' . $extensions[$value['extension']]['upload_icon'] . '" alt="">' : '';
						$text .= '<dd><dl class="file"><dt>' . $icon . ' <a class="postlink" href="' . $this->helper->route('sheer_knowledgebase_kb_file', array('id' => $value['attach_id'])) . '">' . $value['real_filename'] . '</a></dt>' . $comment;
					}
					$text .= '</dl></dd>';
				}
			}
			$text .= '</dl><div>';
		}
	}

	public function check_is_img($ext, &$extensions = array()): bool
	{
		if (($extensions = $this->cache->get('_kb_extension')) === false)
		{
			$sql = 'SELECT e.extension, g.*
				FROM ' . EXTENSIONS_TABLE . ' e, ' . EXTENSION_GROUPS_TABLE . ' g
				WHERE e.group_id = g.group_id';
			$result = $this->db->sql_query($sql);

			while ($row = $this->db->sql_fetchrow($result))
			{
				$extension = strtolower(trim($row['extension']));

				$extensions[$extension] = array(
					'display_cat'   => (int) $row['cat_id'],
					'download_mode' => $row['download_mode'] ?? 0,// phpbb 4.x
					'upload_icon'   => trim($row['upload_icon']),
					'max_filesize'  => (int) $row['max_filesize'],
					'allow_group'   => $row['allow_group'],
					'allow_in_pm'   => $row['allow_in_pm'],
					'group_name'    => $row['group_name'],
				);
			}
			$this->db->sql_freeresult($result);
			$this->cache->put('_kb_extension', $extensions);
		}

		if ( phpbb_version_compare($this->config['version'], '4.0.0', '<'))
		{
			return isset($extensions[$ext]['display_cat']) && $extensions[$ext]['display_cat'] == ATTACHMENT_CATEGORY_IMAGE;
		} else {
			return isset($extensions[$ext]['display_cat']) && $extensions[$ext]['display_cat'] == \phpbb\attachment\attachment_category::IMAGE;
		}
	}
}
