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

use RuntimeException;

class posting
{
	/** @var \phpbb\db\driver\driver_interface */
	protected \phpbb\db\driver\driver_interface $db;

	/** @var \phpbb\config\config */
	protected \phpbb\config\config $config;

	/** @var \phpbb\controller\helper */
	protected \phpbb\controller\helper $helper;

	/** @var \phpbb\extension\manager */
	protected \phpbb\extension\manager $ext_manager;

	/** @var \phpbb\language\language */
	protected \phpbb\language\language $language;

	/** @var \phpbb\auth\auth */
	protected \phpbb\auth\auth $auth;

	/** @var \phpbb\request\request_interface */
	protected \phpbb\request\request_interface $request;

	/** @var \phpbb\template\template */
	protected \phpbb\template\template $template;

	/** @var \phpbb\user */
	protected \phpbb\user $user;

	/** @var \phpbb\cache\driver\driver_interface */
	protected \phpbb\cache\driver\driver_interface $cache;

	/** @var \phpbb\log\log */
	protected $log;

	/** @var \phpbb\files\factory */
	protected \phpbb\files\factory $files_factory;

	/* @var \phpbb\plupload\plupload */
	protected \phpbb\plupload\plupload $plupload;

	/** @var \phpbb\notification\manager */
	protected \phpbb\notification\manager $notification_manager;

	/** @var \sheer\knowledgebase\inc\functions_kb */
	protected \sheer\knowledgebase\inc\functions_kb $kb;

	/** @var \sheer\knowledgebase\search\kb_search_backend_factory */
	protected \sheer\knowledgebase\search\kb_search_backend_factory $search_factory;

	/** @var string */
	protected string $phpbb_root_path;

	/** @var string */
	protected string $php_ext;

	/** @var string */
	protected string $logs_table;

	/** @var string */
	protected string $articles_table;

	/** @var string */
	protected string $categories_table;

	/** @var string */
	protected string $attachments_table;

	/** @var string */
	protected string $upload_dir;

	/**
	 * Constructor
	 *
	 * @param \phpbb\db\driver\driver_interface                     $db
	 * @param \phpbb\config\config                                  $config
	 * @param \phpbb\controller\helper                              $helper
	 * @param \phpbb\extension\manager                              $ext_manager
	 * @param \phpbb\language\language                              $language
	 * @param \phpbb\auth\auth                                      $auth
	 * @param \phpbb\request\request_interface                      $request
	 * @param \phpbb\template\template                              $template
	 * @param \phpbb\user                                           $user
	 * @param \phpbb\cache\driver\driver_interface                  $cache
	 * @param \phpbb\log\log_interface                              $log
	 * @param \phpbb\files\factory                                  $files_factory
	 * @param \phpbb\plupload\plupload                              $plupload
	 * @param \phpbb\notification\manager                           $notification_manager
	 * @param \sheer\knowledgebase\inc\functions_kb                 $kb
	 * @param \sheer\knowledgebase\search\kb_search_backend_factory $search_factory
	 * @param string                                                $phpbb_root_path
	 * @param string                                                $php_ext
	 * @param string                                                $logs_table
	 * @param string                                                $articles_table
	 * @param string                                                $categories_table
	 * @param string                                                $attachments_table
	 */
	public function __construct(
		\phpbb\db\driver\driver_interface $db,
		\phpbb\config\config $config,
		\phpbb\controller\helper $helper,
		\phpbb\extension\manager $ext_manager,
		\phpbb\language\language $language,
		\phpbb\auth\auth $auth,
		\phpbb\request\request_interface $request,
		\phpbb\template\template $template,
		\phpbb\user $user,
		\phpbb\cache\driver\driver_interface $cache,
		\phpbb\log\log_interface $log,
		\phpbb\files\factory $files_factory,
		\phpbb\plupload\plupload $plupload,
		\phpbb\notification\manager $notification_manager,
		\sheer\knowledgebase\inc\functions_kb $kb,
		\sheer\knowledgebase\search\kb_search_backend_factory $search_factory,
		string $phpbb_root_path,
		string $php_ext,
		string $logs_table,
		string $articles_table,
		string $categories_table,
		string $attachments_table
	)
	{
		$this->db = $db;
		$this->config = $config;
		$this->helper = $helper;
		$this->ext_manager = $ext_manager;
		$this->language = $language;
		$this->auth = $auth;
		$this->request = $request;
		$this->template = $template;
		$this->user = $user;
		$this->cache = $cache;
		$this->log = $log;
		$this->files_factory = $files_factory;
		$this->plupload = $plupload;
		$this->notification_manager = $notification_manager;
		$this->kb = $kb;
		$this->search_factory = $search_factory;
		$this->phpbb_root_path = $phpbb_root_path;
		$this->php_ext = $php_ext;
		$this->logs_table = $logs_table;
		$this->articles_table = $articles_table;
		$this->categories_table = $categories_table;
		$this->attachments_table = $attachments_table;

		$this->upload_dir = $this->ext_manager->get_extension_path('sheer/knowledgebase', true) . 'files/';
	}

	public function post_article(): \Symfony\Component\HttpFoundation\Response
	{
		if (!$this->auth->acl_get('u_kb_view') && !$this->auth->acl_get('a_manage_kb'))
		{
			trigger_error($this->language->lang('NOT_AUTHORISED'));
		}

		$this->language->add_lang(array('plupload', 'posting'));
		$this->log->set_log_table($this->logs_table);

		$fid = $this->config['kb_forum_id'];

		if (empty($this->config['kb_forum_id']) && $this->config['kb_anounce'])
		{
			trigger_error('WARNING_DEFAULT_CONFIG');
		}

		$cat_id = $this->request->variable('id', 0);
		$art_id = $this->request->variable('k', 0);
		$mode = $this->request->variable('mode', '');

		if (!$this->kb->acl_kb_get($cat_id, 'kb_u_add'))
		{
			trigger_error('RULES_KB_ADD_CANNOT');
		}

		$row = '';
		$delete_allowed = $edit_allowed = $article_author_id = false;
		if ($mode)
		{
			$sql = 'SELECT DISTINCT a.*, c.category_name, c.category_id
				FROM ' . $this->articles_table . ' a, ' . $this->categories_table . ' c
				WHERE article_id = ' . (int) $art_id . '
					AND (c.category_id = a.article_category_id)';
			$result = $this->db->sql_query($sql);
			$row = $this->db->sql_fetchrow($result);
			$this->db->sql_freeresult($result);

			if (empty($row))
			{
				trigger_error('ARTICLE_NO_EXISTS');
			}

			$article_author_id = $row['author_id'];

			$edit_allowed = ($this->kb->acl_kb_get($cat_id, 'kb_m_edit') || (
					$this->user->data['user_id'] == $article_author_id &&
					$this->kb->acl_kb_get($cat_id, 'kb_u_edit'))
			);

			$delete_allowed = ($this->kb->acl_kb_get($cat_id, 'kb_m_delete') || (
					$this->user->data['user_id'] == $article_author_id &&
					$this->kb->acl_kb_get($cat_id, 'kb_u_delete'))
			);
		}

		// Select which method we'll use to obtain the post_id or topic_id information
		try
		{
			$kb_search = $this->search_factory->get_active();
		}
		catch (RuntimeException $e)
		{
			$kb_search = false;
			if (strpos($e->getMessage(), 'No service found') === 0)
			{
				trigger_error('NO_SUCH_SEARCH_MODULE');
			}
			else
			{
				throw $e;
			}
		}

		$error = array();
		$submit = $this->request->is_set_post('submit');
		$preview = $this->request->is_set_post('preview');
		$cancel = $this->request->is_set_post('cancel');
		$delete = $this->request->is_set_post('delete');
		$edit = $mode == 'edit';

		$action = $this->helper->route('sheer_knowledgebase_posting', array('id' => $cat_id));

		if ($mode == 'delete' || $delete)
		{
			if (!$delete_allowed)
			{
				trigger_error('RULES_KB_MOD_DELETE_CANNOT');
			}

			$s_hidden_fields = build_hidden_fields(array(
					'mode' => 'delete',
					'k'    => $art_id)
			);

			if (confirm_box(true))
			{
				$art_info = $this->kb->get_kb_article_info($art_id);
				$this->kb->kb_delete_article($art_id, $art_info['article_title']);
				if ($kb_search)
				{
					$author_ids[] = $art_info['author_id'];
					$art_ids[] = $art_id;
					$kb_search->index_remove($art_ids, $author_ids);
				}
				$msg = $this->language->lang('ARTICLE_DELETED');
				$root = $this->helper->route('sheer_knowledgebase_category', array('id' => $cat_id));
				$msg .= '<br><br>' . sprintf($this->language->lang('RETURN_CAT'), '<a href="' . $root . '">', '</a>');
				$this->cache->destroy('sql', $this->categories_table);
				$this->cache->destroy('sql', $this->articles_table);
				meta_refresh(2, $root);
				trigger_error($msg);
			}
			else
			{
				confirm_box(false, $this->language->lang('CONFIRM_DELETE_ARTICLE'), $s_hidden_fields);
			}
		}

		if ($mode == 'edit')
		{
			$to_id = $this->request->variable('to_id', 0);
			if (empty($art_id))
			{
				trigger_error($this->language->lang('NO_ID_SPECIFIED'));
			}

			$action = $this->helper->route('sheer_knowledgebase_posting', array('mode' => 'edit', 'k' => $art_id, 'id' => $cat_id));

			$uid = $bitfield = $options = '';

			$article_title = $row['article_title'];
			$article_text = $row['article_body'];
			$article_description = $row['article_description'];
			$article_author = $row['author'];
			$views = $row['views'];
			$article_date = $row['article_date'];
			$order = $row['display_order'];

			$article_text = $this->decode_message($article_text, $row['bbcode_uid']);

			if (!$edit_allowed)
			{
				trigger_error('RULES_KB_MOD_EDIT_CANNOT');
			}
		}

		$this->kb_parse_attachments($art_id, $attachment_data, $preview, $edit, $submit);

		$bbcode_status = true;
		$smilies_status = true;
		$img_status = true;
		$url_status = true;

		$allowed_bbcode = $allowed_smilies = $allowed_urls = true;

		$article_title = (isset($article_title)) ? $this->request->variable('subject', $article_title, true) : $this->request->variable('subject', '', true);
		$article_text = (isset($article_text)) ? $this->request->variable('message', $article_text, true) : $this->request->variable('message', '', true);
		$article_description = (isset($article_description)) ? $this->request->variable('descr', $article_description, true) : $this->request->variable('descr', '', true);

		$articles_count = 0;
		$category_name = '';
		if ($row = $this->kb->get_cat_info($cat_id))
		{
			$articles_count = $row['number_articles'];
			$category_name = $row['category_name'];
		}
		else
		{
			trigger_error($this->language->lang('CAT_NO_EXISTS'));
		}

		include($this->phpbb_root_path . 'includes/functions_posting.' . $this->php_ext);
		generate_smilies('inline', 0);
		include($this->phpbb_root_path . 'includes/functions_display.' . $this->php_ext);
		display_custom_bbcodes();

		if ($submit)
		{
			if ($article_title && $article_text)
			{
				// to enable bbcode, urls and smilies parsing, be enabled it when using
				// generate_text_for_storage function
				generate_text_for_storage($article_text, $bbcode_uid, $bbcode_bitfield, $options, true, true, true);

				$sql_data = array(
					'article_category_id' => $cat_id,
					'article_title'       => $article_title,
					'article_description' => $article_description,
					'article_date'        => time(),
					'author_id'           => $this->user->data['user_id'],
					'bbcode_uid'          => substr(md5(rand()), 0, 8),
					'article_body'        => $article_text,
					'views'               => 0,
					'author'              => $this->user->data['username'],
					'approved'            => ($this->kb->acl_kb_get($cat_id, 'kb_u_add_noapprove')) ? 1 : 0,
				);

				$root = $this->helper->route('sheer_knowledgebase_category', array('id' => $cat_id));

				if ($mode == 'edit')
				{
					$sql_data['author_id'] = $article_author_id;
					$sql_data['author'] = $article_author;
					$sql_data['views'] = $views;
					$sql_data['article_date'] = $article_date;
					$sql_data['edit_date'] = time();
					$redirect = $this->helper->route('sheer_knowledgebase_article', array('k' => $art_id));

					if ($cat_id != $to_id) // Move article to another category
					{
						$sql_data['article_category_id'] = $to_id;
						$sql_data['display_order'] = 0;
					}

					$sql = 'UPDATE ' . $this->articles_table . '
						SET ' . $this->db->sql_build_array('UPDATE', $sql_data) . "
						WHERE article_id = $art_id";
					$this->db->sql_query($sql);

					if ($cat_id != $to_id) // Move article to another category
					{
						$sql = 'UPDATE ' . $this->categories_table . '
							SET number_articles = number_articles - 1
							WHERE category_id = ' . (int) $cat_id;
						$this->db->sql_query($sql);

						$sql = 'UPDATE ' . $this->categories_table . '
							SET number_articles = number_articles + 1
							WHERE category_id = ' . (int) $to_id;
						$this->db->sql_query($sql);

						$sql = 'UPDATE ' . $this->articles_table . ' SET display_order = display_order + 1
							WHERE article_category_id = ' . (int) $to_id;
						$this->db->sql_query($sql);

						$sql = 'UPDATE ' . $this->articles_table . ' SET display_order = display_order - 1
							WHERE article_category_id = ' . (int) $cat_id . '
							AND display_order > ' . (int) $order;
						$this->db->sql_query($sql);
					}

					// Upd search index
					if ($kb_search)
					{
						$kb_search->index('edit', (int) $art_id, $article_text, $article_title, $article_description, (int) $article_author_id);
					}

					$this->insert_attachments($attachment_data, $art_id);

					$msg = $this->language->lang('ARTICLE_EDITED');
					$msg .= '<br><br>' . sprintf($this->language->lang('RETURN_ARTICLE'), '<a href="' . $redirect . '">', '</a>');
					$msg .= '<br><br>' . sprintf($this->language->lang('RETURN_CAT'), '<a href="' . $root . '">', '</a>');
					$this->log->add('admin', $this->user->data['user_id'], $this->user->data['user_ip'], 'LOG_LIBRARY_EDIT_ARTICLE', time(), array($article_title, $category_name));
				}
				else
				{
					$sql = 'UPDATE ' . $this->articles_table . ' SET display_order = display_order + 1
						WHERE article_category_id = ' . (int) $cat_id;
					$this->db->sql_query($sql);

					$sql_data = array_merge($sql_data, array('display_order' => 1));
					$sql = 'INSERT INTO ' . $this->articles_table . '
						' . $this->db->sql_build_array('INSERT', $sql_data);
					$this->db->sql_query($sql);
					$new = $this->db->sql_nextid();
					$this->insert_attachments($attachment_data, $new);

					$articles_count++;
					$this->config->increment('kb_num_articles', 1);
					$sql = 'UPDATE ' . $this->categories_table . '
						SET number_articles = ' . $articles_count . '
						WHERE category_id = ' . (int) $cat_id;
					$this->db->sql_query($sql);

					if ($this->kb->acl_kb_get($cat_id, 'kb_u_add_noapprove'))
					{
						$redirect = $this->helper->route('sheer_knowledgebase_article', array('k' => $new));

						if (isset($kb_search))
						{
							// Add search index
							$kb_search->index('add', (int) $new, $article_text, $article_title, $article_description, (int) $this->user->data['user_id']);
						}

						if (!empty($this->config['kb_forum_id']) && $this->config['kb_anounce'])
						{
							$this->kb->submit_article($cat_id, $fid, $article_title, $article_description, $this->user->data['username'], $category_name, $new);
						}

						$msg = $this->language->lang('ARTICLE_SUBMITTED');
						$msg .= '<br><br>' . sprintf($this->language->lang('RETURN_ARTICLE'), '<a href="' . $redirect . '">', '</a>');
					}
					else
					{
						$msg = $this->language->lang('ARTICLE_NEED_APPROVE');
						$redirect = $this->helper->route('sheer_knowledgebase_category', array('id' => $cat_id));

						// Add notification
						$notification_data = array(
							'author_id'           => $this->user->data['user_id'],
							'title'               => $article_title,
							'article_category_id' => $cat_id,
							'item_id'             => $new,
						);
						$this->notification_manager->add_notifications('sheer.knowledgebase.notification.type.need_approval', $notification_data);
					}

					$this->cache->destroy('sql', $this->categories_table);
					$this->cache->destroy('sql', $this->articles_table);

					$msg .= '<br><br>' . sprintf($this->language->lang('RETURN_CAT'), '<a href="' . $root . '">', '</a>');
					$this->log->add('admin', $this->user->data['user_id'], $this->user->data['user_ip'], 'LOG_LIBRARY_ADD_ARTICLE', time(), array($article_title, $category_name));
				}
				meta_refresh(2, $redirect);
				trigger_error($msg);
			}
			else
			{
				if (!$article_title)
				{
					$error[] = $this->language->lang('NO_TITLE');
				}

				if (!$article_text)
				{
					$error[] = $this->language->lang('NO_TEXT');
				}
			}
		}
		if ($cancel)
		{
			redirect($this->helper->route('sheer_knowledgebase_category', array('id' => $cat_id)));
		}

		if ($preview)
		{
			if (!$article_title)
			{
				$error[] = $this->language->lang('NO_TITLE');
			}

			if (!$article_text)
			{
				$error[] = $this->language->lang('NO_TEXT');
			}

			$uid = $bitfield = $options = '';
			$preview_text = $article_text;

			generate_text_for_storage($preview_text, $uid, $bitfield, $options, true, true, true);

			$preview_text = generate_text_for_display($preview_text, $uid, $bitfield, $options);

			// Parse attachments
			if (count($attachment_data))
			{
				$this->kb->parse_att($preview_text, $attachment_data);
			}

			$this->template->assign_vars(array(
					'PREVIEW_MESSAGE' => $preview_text,
					'PREVIEW_SUBJECT' => $article_title,
				)
			);
		}

		$this->template->assign_vars(array(
				'L_POST_A'          => ($mode == 'edit') ? $this->language->lang('EDIT_ARTICLE') : $this->language->lang('ADD_ARTICLE'),
				'CATEGORY_NAME'     => $category_name,
				'DESCR'             => $article_description,
				'TOPIC_TITLE'       => $article_title,
				'SUBJECT'           => $article_title,
				'MESSAGE'           => $article_text,
				'ERROR'             => (count($error)) ? implode('<br>', $error) : '',
				'S_DISPLAY_PREVIEW' => !count($error) && $preview,
				'POST_DATE'         => $this->user->format_date(time()),
				'PREVIEW_SUBJECT'   => (isset($article_title)) ? $article_title : '',
				'PREVIEW_MESSAGE'   => (isset($preview_text)) ? $preview_text : '',
				'S_BBCODE_ALLOWED'  => ($bbcode_status) ? 1 : 0,
				'BBCODE_STATUS'     => ($bbcode_status) ? sprintf($this->language->lang('BBCODE_IS_ON'), '<a href="' . $this->helper->route('phpbb_help_bbcode_controller') . '">', '</a>') : sprintf($this->language->lang('BBCODE_IS_OFF'), '<a href="' . $this->helper->route('phpbb_help_bbcode_controller') . '">', '</a>'),
				'IMG_STATUS'        => ($img_status) ? $this->language->lang('IMAGES_ARE_ON') : $this->language->lang('IMAGES_ARE_OFF'),
				'SMILIES_STATUS'    => ($smilies_status) ? $this->language->lang('SMILIES_ARE_ON') : $this->language->lang('SMILIES_ARE_OFF'),
				'URL_STATUS'        => ($bbcode_status && $url_status) ? $this->language->lang('URL_IS_ON') : $this->language->lang('URL_IS_OFF'),
				'S_LINKS_ALLOWED'   => $url_status,
				'S_BBCODE_IMG'      => $img_status,
				'S_BBCODE_URL'      => $url_status,
				'S_BBCODE_QUOTE'    => true,
				'S_EDIT_POST'       => $mode == 'edit',
				'S_CAN_DELETE'      => isset($delete_allowed) && $delete_allowed,

				'S_FORM_ENCTYPE' => ($this->config['kb_allow_attachments']) ? ' enctype="multipart/form-data"' : '',

				'S_PLUPLOAD'              => (bool) $this->config['kb_allow_attachments'],
				'S_RESIZE'                => $this->plupload->generate_resize_string(),
				'FILESIZE'                => $this->config['kb_max_filesize'],
				'FILTERS'                 => $this->kb->generate_filter_string(),
				'CHUNK_SIZE'              => $this->plupload->get_chunk_size(),
				'S_PLUPLOAD_URL'          => generate_board_url() . '/knowledgebase/posting?id=' . $cat_id,
				'MAX_ATTACHMENTS'         => (!$this->auth->acl_get('a_manage_kb')) ? $this->config['kb_max_attachments'] : 0,
				'ATTACH_ORDER'            => 'desc',
				'L_TOO_MANY_ATTACHMENTS'  => $this->language->lang('TOO_MANY_ATTACHMENTS', $this->config['kb_max_attachments']),
				'S_ATTACH_DATA'           => (count($attachment_data)) ? json_encode($attachment_data) : '[]',
				'MAX_ATTACHMENT_FILESIZE' => $this->config['kb_max_filesize'] > 0 ? $this->language->lang('MAX_ATTACHMENT_FILESIZE', get_formatted_filesize($this->config['kb_max_filesize'])) : '',

				'CATS_BOX' => '<option value="0" disabled="disabled">' . $this->language->lang('CATEGORIES_LIST') . '</option>' . $this->kb->make_category_select($cat_id, false, false),

				'U_KB'           => $this->helper->route('sheer_knowledgebase_index'),
				'S_POST_ACTION'  => $action,
				'S_POST_ARTICLE' => true,
			)
		);

		$this->template->assign_block_vars('navlinks', array(
				'FORUM_NAME'   => $this->language->lang('LIBRARY'),
				'U_VIEW_FORUM' => $this->helper->route('sheer_knowledgebase_index'),
			)
		);

		foreach ($this->kb->get_category_branch($cat_id, 'parents') as $row)
		{
			$this->template->assign_block_vars('navlinks', array(
					'FORUM_NAME'   => $row['category_name'],
					'U_VIEW_FORUM' => $this->helper->route('sheer_knowledgebase_category', array('id' => $row['category_id'])),
				)
			);
		}

		$title = ($mode == 'edit') ? $this->language->lang('EDIT_ARTICLE') : $this->language->lang('ADD_ARTICLE');

		return $this->helper->render('@sheer_knowledgebase/kb_post_body.html', ($this->language->lang('LIBRARY') . ' &raquo; ' . $title));
	}

	public function decode_message($message, $bbcode_uid = '')
	{
		if ($bbcode_uid)
		{
			$match = array('<br>', "[/*:m:$bbcode_uid]", ":u:$bbcode_uid", ":o:$bbcode_uid", ":$bbcode_uid");
			$replace = array("\n", '', '', '', '');
		}
		else
		{
			$match = array('<br>');
			$replace = array("\n");
		}

		$message = str_replace($match, $replace, $message);
		$match = get_preg_expression('bbcode_htm');
		$replace = array('\1', '\1', '\2', '\1', '', '');
		return preg_replace($match, $replace, $message);
	}

	public function kb_parse_attachments($art_id, &$attachment_data, $preview, $edit, $submit)
	{
		$delete_file = $this->request->is_set_post('delete_file');
		$add_file = $this->request->is_set_post('add_file');
		$filename = $this->request->file('fileupload');
		$json_response = new \phpbb\json_response();
		$thumbnail = false;

		$this->plupload->set_upload_directories($this->upload_dir, $this->upload_dir . '/plupload');

		$attachment_data = $this->request->variable('attachment_data', array(0 => array('' => '')), true, \phpbb\request\request_interface::POST);

		// First of all adjust comments if changed
		$actual_comment_list = $this->request->variable('comment_list', array(''), true);

		foreach ($actual_comment_list as $comment_key => $comment)
		{
			if (!isset($attachment_data[$comment_key]))
			{
				continue;
			}

			if ($attachment_data[$comment_key]['attach_comment'] != $comment)
			{
				$attachment_data[$comment_key]['attach_comment'] = $comment;
			}
		}

		if ($delete_file)
		{
			$index = array_keys($this->request->variable('delete_file', array(0 => 0)));
			$index = (!empty($index)) ? $index[0] : false;

			if ($index !== false && !empty($attachment_data[$index]))
			{
				$sql = 'SELECT physical_filename, thumbnail
					FROM ' . $this->attachments_table . ' WHERE attach_id = ' . (int) $attachment_data[$index]['attach_id'];
				$result = $this->db->sql_query($sql);

				$filename = $this->db->sql_fetchfield('physical_filename');
				$this->db->sql_freeresult($result);

				@unlink($this->upload_dir . $filename);
				@unlink($this->upload_dir . 'thumb_' . $filename);

				$sql = 'DELETE FROM ' . $this->attachments_table . ' WHERE attach_id = ' . (int) $attachment_data[$index]['attach_id'];
				$this->db->sql_query($sql);
				unset($attachment_data[$index]);

				$attachment_data = array_values($attachment_data);

				$json_response->send($attachment_data);
			}
		}
		else if ($add_file)
		{
			$error = array();

			if ((empty($filename) || $filename['name'] === 'none'))
			{
				$error[] = $this->language->lang('NO_UPLOAD_FORM_FOUND');
			}

			$num_attachments = count($attachment_data);
			if ($num_attachments >= $this->config['kb_max_attachments'] && !$this->auth->acl_get('a_manage_kb'))
			{
				$error[] = sprintf($this->language->lang('MAX_NUM_ATTACHMENTS'), $num_attachments);
			}

			if (!count($error))
			{
				$allowed_extensions = $this->kb->allowed_extension();
				$fileupload = $this->files_factory->get('files.upload')
					->set_disallowed_content([])
					->set_allowed_extensions($allowed_extensions)
					->set_max_filesize($this->config['kb_max_filesize'])
				;

				$upload_file = (isset($this->files_factory)) ? $fileupload->handle_upload('files.types.form', 'fileupload') : $fileupload->form_upload('fileupload');

				$ext = $upload_file->get('extension');
				if (!in_array($upload_file->get('extension'), $allowed_extensions))
				{
					$error[] = sprintf($this->language->lang('DISALLOWED_EXTENSION'), $ext);
				}
				else
				{
					$is_image = $this->kb->check_is_img($upload_file->get('extension'));
					$upload_file->clean_filename('unique', $this->user->data['user_id'] . '_');
					$result = $upload_file->move_file($this->upload_dir, false, !$is_image);

					if (count($upload_file->error))
					{
						$upload_file->remove();
						$error = array_merge($error, $upload_file->error);
						$result = false;
					}

					if ($result)
					{
						if ($this->config['kb_allow_thumbnail'] && $is_image)
						{
							include($this->phpbb_root_path . 'includes/functions_posting.' . $this->php_ext);
							$thumbnail = create_thumbnail($this->upload_dir . $upload_file->get('realname'), $this->upload_dir . 'thumb_' . $upload_file->get('realname'), $upload_file->get('mimetype'));
						}

						$sql_ary = array(
							'poster_id'         => $this->user->data['user_id'],
							'physical_filename' => $upload_file->get('realname'),
							'real_filename'     => $upload_file->get('uploadname'),
							'filesize'          => $filename['size'],
							'filetime'          => time(),
							'extension'         => $upload_file->get('extension'),
							'mimetype'          => $upload_file->get('mimetype'),
							'attach_comment'    => '',
							'thumbnail'         => ($thumbnail) ? 1 : 0,
						);

						$this->db->sql_query('INSERT INTO ' . $this->attachments_table . ' ' . $this->db->sql_build_array('INSERT', $sql_ary));
						$new = $this->db->sql_nextid();
						$new_entry = array(
							'attach_id'         => $new,
							'is_orphan'         => 1,
							'real_filename'     => $upload_file->get('uploadname'),
							'physical_filename' => $upload_file->get('realname'),
							'filesize'          => $filename['size'],
							'filetime'          => time(),
							'extension'         => $upload_file->get('extension'),
							'mimetype'          => $upload_file->get('mimetype'),
							'attach_comment'    => '',
							'thumbnail'         => ($thumbnail) ? 1 : 0,
						);
						$attachment_data = array_merge(array(0 => $new_entry), $attachment_data);
						$download_url = 'kb_file?id=' . $new;
						$json_response->send(array('data' => $attachment_data, 'download_url' => $download_url));
					}
				}
			}

			if (count($error))
			{
				$json_response->send(array(
					'jsonrpc' => '2.0',
					'id'      => 'id',
					'error'   => array(
						'code'    => 105,
						'message' => current($error),
					),
				));
			}
		}
		$this->get_kb_submitted_attachments($art_id, $attachment_data, $preview, $edit, $submit);
		$this->plupload->set_upload_directories($this->config['upload_path'], $this->config['upload_path'] . '/plupload');
	}

	public function get_kb_submitted_attachments($art_id, &$attachment_data, $preview, $edit, $submit)
	{
		if ($art_id && !$preview && !($edit && $submit))
		{
			$sql = 'SELECT *
				FROM ' . $this->attachments_table . '
				WHERE article_id = ' . (int) $art_id . '
				AND is_orphan = 0
				ORDER BY attach_id DESC';
			$result = $this->db->sql_query($sql);
			while ($row = $this->db->sql_fetchrow($result))
			{
				$attachment_data[] = $row;
			}
			$this->db->sql_freeresult($result);
		}

		if (count($attachment_data))
		{
			($this->config['display_order']) ? krsort($attachment_data) : ksort($attachment_data);
		}

		if (count($attachment_data))
		{
			$s_inline_attachment_options = '';
			$i = 0;
			foreach ($attachment_data as $count => $attach_row)
			{
				$hidden = '';
				$attach_row['real_filename'] = utf8_basename($attach_row['real_filename']);

				foreach ($attach_row as $key => $value)
				{
					$hidden .= '<input type="hidden" name="attachment_data[' . $count . '][' . $key . ']" value="' . $value . '" />';
				}

				$this->template->assign_block_vars('attach_row', array(
						'FILENAME'     => utf8_basename($attach_row['real_filename']),
						'A_FILENAME'   => addslashes(utf8_basename($attach_row['real_filename'])),
						'FILE_COMMENT' => $attach_row['attach_comment'],
						'ATTACH_ID'    => $attach_row['attach_id'],
						'S_IS_ORPHAN'  => $attach_row['is_orphan'],
						'ASSOC_INDEX'  => $count,
						'FILESIZE'     => get_formatted_filesize($attach_row['filesize']),

						'U_VIEW_ATTACHMENT' => 'kb_file?id=' . $attach_row['attach_id'],
						'S_HIDDEN'          => $hidden,
					)
				);

				$s_inline_attachment_options .= '<option value="' . $i . '">' . utf8_basename($attach_row['real_filename']) . '</option>';
				$i++;
			}
			$this->template->assign_var('S_INLINE_ATTACHMENT_OPTIONS', $s_inline_attachment_options);
		}
	}

	public function insert_attachments($attachment_data, $id)
	{
		if (count($attachment_data))
		{
			foreach ($attachment_data as $attach_row)
			{
				$attach_sql = array(
					'is_orphan'      => 0,
					'attach_comment' => $attach_row['attach_comment'],
					'article_id'     => $id,
				);
				$sql = 'UPDATE ' . $this->attachments_table . ' SET ' . $this->db->sql_build_array('UPDATE', $attach_sql) . '
					WHERE attach_id = ' . (int) $attach_row['attach_id'] . '
						AND poster_id = ' . (int) $this->user->data['user_id'];
				$this->db->sql_query($sql);
			}
		}
	}
}
