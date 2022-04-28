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

class category
{
	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\controller\helper */
	protected $helper;

	/** @var \phpbb\language\language */
	protected $language;

	/** @var \phpbb\auth\auth */
	protected $auth;

	/** @var \phpbb\request\request_interface */
	protected $request;

	/** @var \phpbb\template\template */
	protected $template;

	/** @var \phpbb\user */
	protected $user;

	/** @var \phpbb\pagination */
	protected $pagination;

	/** @var \sheer\knowledgebase\inc\functions_kb */
	protected $kb;

	/** @var string */
	protected $articles_table;

	/** @var string */
	protected $categories_table;

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
	 * @param \phpbb\pagination                     $pagination
	 * @param \sheer\knowledgebase\inc\functions_kb $kb
	 * @param string                                $articles_table
	 * @param string                                $categories_table
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
		\phpbb\pagination $pagination,
		\sheer\knowledgebase\inc\functions_kb $kb,
		$articles_table,
		$categories_table
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
		$this->pagination = $pagination;
		$this->kb = $kb;
		$this->articles_table = $articles_table;
		$this->categories_table = $categories_table;
	}


	/**
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function cat()
	{
		if (!$this->auth->acl_get('u_kb_view') && !$this->auth->acl_get('a_manage_kb'))
		{
			trigger_error($this->language->lang('NOT_AUTHORISED'));
		}

		$cat_id = $this->request->variable('id', 0);
		$start = $this->request->variable('start', 0);

		if (!$cat_id)
		{
			redirect($this->helper->route('sheer_knowledgebase_index'));
		}

		$sql = 'SELECT category_id, category_name
			FROM ' . $this->categories_table . '
			WHERE category_id = ' . (int) $cat_id;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		if (empty($row))
		{
			trigger_error('CAT_NO_EXISTS');
		}

		$per_page = $this->config['kb_articles_per_page'];
		$sort_type = $this->config['kb_sort_type'];

		$order_by = ' ORDER BY a.display_order ASC';
		$s_sort_key = $s_sort_dir = '';
		$alphabet = [];
		$s_can_move = !$sort_type && $this->kb->acl_kb_get($cat_id, 'kb_m_edit');

		$sql_where = ($this->kb->acl_kb_get($cat_id, 'kb_m_approve')) ? '' : 'AND a.approved = 1';
		$pagination_ary = array('id' => $cat_id);

		if ($sort_type == 1)
		{
			$sort_dir = $this->request->variable('sd', 'a');
			$sort_key = $this->request->variable('sk', 't');

			$sort_key_text = array('a' => $this->language->lang('AUTHOR'),
								   't' => $this->language->lang('POST_TIME'),
								   's' => $this->language->lang('SUBJECT'),
								   'v' => $this->language->lang('VIEWS'));
			$sort_by_sql = array('a' => 'a.author', 't' => 'a.article_date', 's' => 'LOWER(a.article_title)', 'v' => 'a.views');
			$sort_dir_text = array('a' => $this->language->lang('ASCENDING'), 'd' => $this->language->lang('DESCENDING'));

			foreach ($sort_key_text as $key => $value)
			{
				$selected = ($sort_key == $key) ? ' selected="selected"' : '';
				$s_sort_key .= '<option value="' . $key . '"' . $selected . '>' . $value . '</option>';
			}

			foreach ($sort_dir_text as $key => $value)
			{
				$selected = ($sort_dir == $key) ? ' selected="selected"' : '';
				$s_sort_dir .= '<option value="' . $key . '"' . $selected . '>' . $value . '</option>';
			}

			$direction = (($sort_dir == 'd') ? 'ASC' : 'DESC');
			$order_by = ' ORDER BY ' . $sort_by_sql[$sort_key] . ' ';
			$order_by .= $direction;
			$pagination_ary = array('id' => $cat_id, 'sd' => $sort_dir, 'sk' => $sort_key);
		}
		else if ($sort_type == -1)
		{
			$alphabet = explode('-', $this->language->lang('ALPHABET'));
			$order_by = ' ORDER BY LOWER(a.article_title) ASC';
			$first_letter = $this->request->variable('l', '', true);
			$url = [];
			foreach ($alphabet as $letter)
			{
				$b_letter =  ($first_letter === $letter) ? '<span class="alphabet">' . $letter . '</span>' : $letter;
				$url[] = '<a href="' . $this->helper->route('sheer_knowledgebase_category', ['id' => $cat_id, 'l' => $letter]) . '">' . $b_letter . '</a>';
			}
			$sql_where .= ' AND a.article_title LIKE "' . $this->db->sql_escape($first_letter) . '%"';

			$this->template->assign_vars([
					'ALPHA_URLS'      => $this->language->lang('ALPHABET_NAV') . '&nbsp;&nbsp;' . implode(' - ', $url) . '&nbsp;&nbsp;&nbsp;&nbsp;',
					'U_RESET_FILTER' => $this->helper->route('sheer_knowledgebase_category', ['id' => $cat_id]),
				]
			);
		}

		$category_name = $row['category_name'];

		$sql = 'SELECT COUNT(a.article_id) as article_count
			FROM ' . $this->articles_table . ' a
			WHERE a.article_category_id = ' . (int) $cat_id . '
			' . $sql_where;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$article_count = $row['article_count'];
		$this->db->sql_freeresult($result);

		$pagination_url = $this->helper->route('sheer_knowledgebase_category', $pagination_ary);
		if ($article_count)
		{
			$this->pagination->generate_template_pagination($pagination_url, 'pagination', 'start', $article_count, $per_page, $start);
		}
		$current_page_number = $this->pagination->get_on_page($per_page, $start);

		$this->template->assign_block_vars('navlinks', array(
				'FORUM_NAME'   => $this->language->lang('LIBRARY'),
				'U_VIEW_FORUM' => $this->helper->route('sheer_knowledgebase_index'),
			)
		);

		foreach ($this->kb->get_category_branch($cat_id, 'parents') as $row)
		{
			$this->template->assign_block_vars('navlinks', array(
					'FORUM_NAME'   => $row['category_name'],
					'U_VIEW_FORUM' => $this->helper->route('sheer_knowledgebase_category', ['id' => $row['category_id']]),
				)
			);
		}

		$sql = 'SELECT *
			FROM ' . $this->categories_table . '
			WHERE parent_id = ' . (int) $cat_id . '
			ORDER BY left_id ASC';
		$result = $this->db->sql_query($sql);

		while ($cat_row = $this->db->sql_fetchrow($result))
		{
			$exclude_cats = array();
			foreach ($this->kb->get_category_branch($cat_row['category_id'], 'children') as $row)
			{
				$exclude_cats[] = $row['category_id'];
			}
			array_shift($exclude_cats);

			$where = ($this->kb->acl_kb_get($cat_row['category_id'], 'kb_m_approve')) ? '' : 'AND approved = 1';
			$sql = 'SELECT COUNT(article_id) AS articles
				FROM ' . $this->articles_table . '
				WHERE article_category_id = ' . (int) $cat_row['category_id'] . '
					' . $where;
			$res = $this->db->sql_query($sql);
			$art_count = (int) $this->db->sql_fetchfield('articles');
			$this->db->sql_freeresult($res);

			$sql = 'SELECT a.article_id, a.article_title, a.article_date, a.author_id, a.author, a.approved, u.user_id, u.user_colour
					FROM ' . $this->articles_table . ' a, ' . USERS_TABLE . ' u
					WHERE a.article_category_id = ' . (int) $cat_row['category_id'] . '
					AND a.article_date =
						(SELECT MAX(article_date) AS max FROM ' . $this->articles_table . '
							WHERE article_category_id = ' . (int) $cat_row['category_id'] . ' ' . $sql_where . ')
								AND a.author_id = u.user_id';
			$res = $this->db->sql_query($sql);
			$art_row = $this->db->sql_fetchrow($res);
			$this->db->sql_freeresult($res);
			$this->template->assign_block_vars('cat_row', array(
					'CAT_ID'          => $cat_row['category_id'],
					'CAT_NAME'        => $cat_row['category_name'],
					'CAT_DESCRIPTION' => $cat_row['category_details'],
					'U_CAT'           => $this->helper->route('sheer_knowledgebase_category', ['id' => $cat_row['category_id']]),
					'ARTICLES'        => $art_count,
					'SUBCATS'         => $this->kb->get_cat_list($exclude_cats),
					'ARTICLE_TITLE'   => $art_row['article_title'] ?? '',
					'U_ARTICLE'       => (isset($art_row['article_id'])) ? $this->helper->route('sheer_knowledgebase_article', ['k' => $art_row['article_id']]) : '',
					'ARTICLE_TIME'    => (isset($art_row['article_date'])) ? $this->user->format_date($art_row['article_date']) : '',
					'ARTICLE_AUTHOR'  => (isset($art_row['author_id'])) ? get_username_string('full', $art_row['author_id'], $art_row['author'], $art_row['user_colour']) : '',
					'NEED_APPROVE'    => !($art_row['approved'] ?? false),
				)
			);
		}

		if (!isset($per_page))
		{
			$per_page = 10;
		}

		$sql = 'SELECT a.*, u.user_colour, u.username
			FROM ' . $this->articles_table . ' a, ' . USERS_TABLE . ' u
			WHERE a.article_category_id = ' . (int) $cat_id . '
			' . $sql_where . '
			AND u.user_id = a.author_id'
			. $order_by;

		$result = $this->db->sql_query_limit($sql, $per_page, $start);
		while ($art_row = $this->db->sql_fetchrow($result))
		{
			$art_id = $art_row['article_id'];
			$author_id = $art_row['author_id'] ?? ANONYMOUS;
			$this->template->assign_block_vars('art_row', array(
					'ID'                  => $art_id,
					'ORDER_ID'            => $art_row['display_order'],
					'U_ARTICLE'           => $this->helper->route('sheer_knowledgebase_article', ['k' => $art_row['article_id']]),
					'ARTICLE_TITLE'       => $art_row['article_title'],
					'ARTICLE_AUTHOR'      => get_username_string('full', $art_row['author_id'], $art_row['username'], $art_row['user_colour']),
					'ARTICLE_DESCRIPTION' => $art_row['article_description'],
					'ARTICLE_DATE'        => $this->user->format_date($art_row['article_date']),
					'ART_VIEWS'           => $art_row['views'],
					'U_DELETE'            => $this->helper->route('sheer_knowledgebase_posting', ['id' => $cat_id, 'mode' => 'delete', 'k' => $art_id]),
					'U_EDIT_ART'          => $this->helper->route('sheer_knowledgebase_posting', ['id' => $cat_id, 'mode' => 'edit', 'k' => $art_id]),
					'S_CAN_DELETE'        => $this->kb->acl_kb_get($cat_id, 'kb_m_delete') || ($this->kb->acl_kb_get($cat_id, 'kb_u_delete') && $this->user->data['user_id'] == $author_id),
					'S_CAN_EDIT'          => $this->kb->acl_kb_get($cat_id, 'kb_m_edit') || ($this->kb->acl_kb_get($cat_id, 'kb_u_edit') && $this->user->data['user_id'] == $author_id),
					'S_APPROVED'          => (bool) $art_row['approved'],
				)
			);
		}
		$this->db->sql_freeresult($result);

		$this->template->assign_vars([
				'CATS_DROPBOX'        => $this->kb->make_category_dropbox(),
				'CATEGORY'            => $category_name,
				'CATEGORY_ID'         => $cat_id,
				'TOTAL_ITEMS'         => $this->language->lang('TOTAL_ITEMS', (int) $article_count),
				'PAGE_NUMBER'         => $this->pagination->on_page($article_count, $per_page, $start),
				'U_ADD_ARTICLE'       => $this->helper->route('sheer_knowledgebase_posting', ['id' => $cat_id]),
				'U_KB'                => $this->helper->route('sheer_knowledgebase_index'),
				'U_KB_SEARCH'         => $this->helper->route('sheer_knowledgebase_search'),
				'S_CAN_ADD'           => $this->kb->acl_kb_get($cat_id, 'kb_u_add'),
				'S_ACTION'            => $this->helper->route('sheer_knowledgebase_category', ['id' => $cat_id]),
				'S_IS_SEARCH'         => (bool) $this->config['kb_search'],
				'S_KB_SEARCH_ACTION'  => $this->helper->route('sheer_knowledgebase_search'),
				'S_KNOWLEDGEBASE'     => true,
				'S_CAN_MOVE'          => $s_can_move,
				'CURRENT_PAGE_NUMBER' => $current_page_number,
				'S_SORT_OPTIONS'      => ($s_sort_key) ?: '',
				'S_ORDER_SELECT'      => ($s_sort_dir) ?: '',
				'S_ALPHABET'          => !empty($alphabet),
				'L_SORT'              => $this->language->lang('SUBMIT'),
			]
		);

		$this->kb->gen_kb_auth_level($cat_id);

		return $this->helper->render('@sheer_knowledgebase/kb_cat_body.html', ($this->language->lang('LIBRARY') . ' &raquo; ' . $this->language->lang('CATEGORY')));
	}
}
