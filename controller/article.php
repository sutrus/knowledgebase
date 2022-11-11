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

class article
{
	/** @var \phpbb\db\driver\driver_interface */
	protected \phpbb\db\driver\driver_interface $db;

	/** @var \phpbb\config\config */
	protected \phpbb\config\config $config;

	/** @var \phpbb\controller\helper */
	protected \phpbb\controller\helper $helper;

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

	/** @var \sheer\knowledgebase\inc\functions_kb */
	protected \sheer\knowledgebase\inc\functions_kb $kb;

	/** @var string */
	protected string $phpbb_root_path;

	/** @var string */
	protected string $php_ext;

	/** @var string */
	protected string $articles_table;

	/** @var string */
	protected string $attachments_table;

	/**
	 * Constructor
	 *
	 * @param \phpbb\db\driver\driver_interface     $db
	 * @param \phpbb\config\config                  $config
	 * @param \phpbb\controller\helper              $helper
	 * @param \phpbb\language\language              $language
	 * @param \phpbb\auth\auth                      $auth
	 * @param \phpbb\request\request_interface      $request
	 * @param \phpbb\template\template              $template
	 * @param \phpbb\user                           $user
	 * @param \sheer\knowledgebase\inc\functions_kb $kb
	 * @param string                                $phpbb_root_path
	 * @param string                                $php_ext
	 * @param string                                $articles_table
	 * @param string                                $attachments_table
	 */
	public function __construct(
		\phpbb\db\driver\driver_interface $db,
		\phpbb\config\config $config,
		\phpbb\controller\helper $helper,
		\phpbb\language\language $language,
		\phpbb\auth\auth $auth,
		\phpbb\request\request_interface $request,
		\phpbb\template\template $template,
		\phpbb\user $user,
		\sheer\knowledgebase\inc\functions_kb $kb,
		string $phpbb_root_path,
		string $php_ext,
		string $articles_table,
		string $attachments_table
	)
	{
		$this->db = $db;
		$this->config = $config;
		$this->helper = $helper;
		$this->language = $language;
		$this->auth = $auth;
		$this->request = $request;
		$this->template = $template;
		$this->user = $user;
		$this->kb = $kb;
		$this->phpbb_root_path = $phpbb_root_path;
		$this->php_ext = $php_ext;
		$this->articles_table = $articles_table;
		$this->attachments_table = $attachments_table;
	}

	/**
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function show(): \Symfony\Component\HttpFoundation\Response
	{
		if (!$this->auth->acl_get('u_kb_view') && !$this->auth->acl_get('a_manage_kb'))
		{
			trigger_error($this->language->lang('NOT_AUTHORISED'));
		}

		$art_id = $this->request->variable('k', 0);
		$mode = $this->request->variable('mode', '');

		if (empty($art_id))
		{
			trigger_error($this->language->lang('NO_ID_SPECIFIED'));
		}

		$sql = 'SELECT a.*, u.user_colour, u.username
			FROM ' . $this->articles_table . ' a, ' . USERS_TABLE . ' u
			WHERE a.article_id = ' . (int) $art_id . '
			AND u.user_id = a.author_id ';
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		if (empty($row))
		{
			trigger_error('ARTICLE_NO_EXISTS');
		}

		$fid = $this->config['kb_forum_id'];
		$cat_id = $row['article_category_id'];

		if (!$row['approved'] && !($this->auth->acl_get('a_manage_kb') || $this->kb->acl_kb_get($cat_id, 'kb_m_approve')))
		{
			redirect($this->helper->route('sheer_knowledgebase_category', array('id' => $cat_id)));
		}

		$catrow = $this->kb->get_cat_info($row['article_category_id']);
		if (empty($catrow))
		{
			trigger_error($this->language->lang('CAT_NO_EXISTS'));
		}

		$this->template->assign_vars(array(
				'ARTICLE_CATEGORY' => '<a href="' . $this->helper->route('sheer_knowledgebase_category', array('id' => $catrow['category_id'])) . '">' . $catrow['category_name'] . '</a>',
				'CATS_DROPBOX'     => $this->kb->make_category_dropbox(),
				'S_ACTION'         => $this->helper->route('sheer_knowledgebase_category', array('id' => $cat_id)),
			)
		);

		$comment_topic_id = $row['topic_id'];

		// Get comments
		if ($comment_topic_id)
		{
			$count = -1;
			$sql = 'SELECT DISTINCT p.poster_id, p.post_time, p.post_subject, p.post_text, p.bbcode_uid, p.bbcode_bitfield, u.user_id, u.username
				FROM ' . POSTS_TABLE . ' p, ' . USERS_TABLE . ' u
				WHERE p.topic_id = ' . (int) $comment_topic_id . '
				AND (p.poster_id = u.user_id)
				ORDER BY p.post_time ASC';
			$res = $this->db->sql_query($sql);
			while ($postrow = $this->db->sql_fetchrow($res))
			{
				$count++;
				if ($count > 0)
				{
					$this->template->assign_block_vars('postrow', array(
							'POSTER_NAME'  => $postrow['username'],
							'POST_DATE'    => $this->user->format_date($postrow['post_time']),
							'POST_SUBJECT' => $postrow['post_subject'],
							'MESSAGE'      => generate_text_for_display($postrow['post_text'], $postrow['bbcode_uid'], $postrow['bbcode_bitfield'], 3, true),
						)
					);
				}
			}
			$this->db->sql_freeresult($res);

			$temp_url = append_sid("{$this->phpbb_root_path}viewtopic." . $this->php_ext, 'f=' . $fid . '&amp;t=' . $row['topic_id']);
		}
		$views = $row['views'];
		$article = $row['article_id'];
		$text = generate_text_for_display($row['article_body'], $row['bbcode_uid'], false, 3, true);

		$sql = 'SELECT *
			FROM ' . $this->attachments_table . '
			WHERE article_id = ' . (int) $art_id . '
			ORDER BY attach_id DESC';
		$result = $this->db->sql_query($sql);

		while ($attach_row = $this->db->sql_fetchrow($result))
		{
			$attachments[] = $attach_row;
		}
		$this->db->sql_freeresult($result);

		// Parse attachments
		if (isset($attachments) && count($attachments))
		{
			$this->kb->parse_att($text, $attachments);
		}

		include_once($this->phpbb_root_path . 'includes/functions_display.' . $this->php_ext);
		$rank = phpbb_get_user_rank($this->user->data, false);

		$this->template->assign_vars(array(
				'ARTICLE_AUTHOR'      => get_username_string('full', $row['author_id'], $row['username'], $row['user_colour']),
				'ARTICLE_DESCRIPTION' => $row['article_description'],
				'ARTICLE_DATE'        => $this->user->format_date($row['article_date']),
				'ART_VIEWS'           => $row['views'],
				'ARTICLE_TITLE'       => $row['article_title'],
				'ARTICLE_TEXT'        => $text,
				'VIEWS'               => $views,
				'RANK'                => $rank['title'],
				'U_EDIT_ART'          => $this->helper->route('sheer_knowledgebase_posting', array('mode' => 'edit', 'id' => $cat_id, 'k' => $art_id)),
				'U_DELETE_ART'        => $this->helper->route('sheer_knowledgebase_posting', array('mode' => 'delete', 'id' => $cat_id, 'k' => $art_id)),
				'U_APPROVE_ART'       => $this->helper->route('sheer_knowledgebase_approve', array('id' => $art_id)),
				'U_PRINT'             => $this->helper->route('sheer_knowledgebase_article', array('mode' => 'print', 'k' => $art_id)),
				'U_ARTICLE'           => '[url=' . generate_board_url() . '/knowledgebase/article?k=' . $art_id . ']' . $row['article_title'] . '[/url]',
				'U_DIRECT_LINK'       => generate_board_url() . '/knowledgebase/article?k=' . $art_id,
				'COMMENTS'            => ($comment_topic_id) ? $this->language->lang('COMMENTS') . $this->language->lang('COLON') . ' ' . $count : '',
				'U_COMMENTS'          => ($comment_topic_id) ? $temp_url : '',
				'S_CAN_EDIT'          => $this->kb->acl_kb_get($cat_id, 'kb_m_edit') || ($this->user->data['user_id'] == $row['author_id'] && $this->kb->acl_kb_get($cat_id, 'kb_u_edit') || $this->auth->acl_get('a_manage_kb')),
				'S_CAN_DELETE'        => $this->kb->acl_kb_get($cat_id, 'kb_m_delete') || ($this->user->data['user_id'] == $row['author_id'] && $this->kb->acl_kb_get($cat_id, 'kb_u_delete') || $this->auth->acl_get('a_manage_kb')),
				'S_CAN_APPROVE'       => $this->auth->acl_get('a_manage_kb') || $this->kb->acl_kb_get($cat_id, 'kb_m_approve'),
				'COUNT_COMMENTS'      => ($comment_topic_id) ? '[' . $this->language->lang('LEAVE_COMMENTS') . ']' : '',
				'U_FORUM'             => generate_board_url() . '/',
				'S_APPROVED'          => $row['approved'],
				'S_KNOWLEDGEBASE'     => true,
				'LIBRARY'             => $this->language->lang('LIBRARY'),
			)
		);

		if ($mode != 'print' && $row['approved'])
		{
			// Increase the number of views
			++$views;
			$sql = 'UPDATE ' . $this->articles_table . '
				SET views = ' . $views . '
				WHERE article_id = ' . (int) $article;
			$this->db->sql_query($sql);
		}

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

		$html_template = (($mode != 'print')) ? 'kb_article_body.html' : 'kb_article_body_print.html';

		return $this->helper->render('@sheer_knowledgebase/' . $html_template, $this->language->lang('LIBRARY'));
	}
}
