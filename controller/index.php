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

class index
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

	/** @var \phpbb\template\template */
	protected $template;

	/** @var \phpbb\user */
	protected $user;

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
	 * @param \phpbb\template\template              $template
	 * @param \phpbb\user                           $user
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
		\phpbb\template\template $template,
		\phpbb\user $user,
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
		$this->template = $template;
		$this->user = $user;
		$this->kb = $kb;
		$this->articles_table = $articles_table;
		$this->categories_table = $categories_table;
	}

	/**
	 * @return \Symfony\Component\HttpFoundation\Response
	 * @throws \Exception
	 */
	public function main()
	{
		if (!$this->auth->acl_get('u_kb_view') && !$this->auth->acl_get('a_manage_kb'))
		{
			trigger_error($this->language->lang('NOT_AUTHORISED'));
		}

		$sql = 'SELECT category_id, category_name, category_details, parent_id, number_articles
			FROM  ' . $this->categories_table . '
			WHERE parent_id = 0
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

			$sql_where = ($this->auth->acl_get('a_manage_kb') || $this->kb->acl_kb_get($cat_row['category_id'], 'kb_m_approve')) ? '' : 'AND approved = 1';
			$art_count = (int) $cat_row['number_articles'];

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
					'U_CAT' => $this->helper->route('sheer_knowledgebase_category', array('id' => $cat_row['category_id'])),
					'CAT_NAME' => $cat_row['category_name'],
					'ARTICLES' => $art_count,
					'CAT_DESCRIPTION' => $cat_row['category_details'],
					'SUBCATS' => $this->kb->get_cat_list($exclude_cats),
					'ARTICLE_TITLE' => $art_row['article_title'] ?? '',
					'U_ARTICLE' => (isset($art_row['article_id'])) ? $this->helper->route('sheer_knowledgebase_article', array('k' => $art_row['article_id'])) : '',
					'ARTICLE_TIME' => ($art_count) ? $this->user->format_date($art_row['article_date']) : '',
					'ARTICLE_AUTHOR' => (isset($art_row['author_id'])) ? get_username_string('full', $art_row['author_id'], $art_row['author'], $art_row['user_colour']) : '',
					'NEED_APPROVE' => !($art_row['approved'] ?? false),
				)
			);
		}
		$this->db->sql_freeresult($result);

		// Output the page
		$this->template->assign_vars(array(
			'LIBRARY_TITLE' => $this->language->lang('LIBRARY'),
		));

		$this->template->assign_vars(array(
				'U_KB_SEARCH'        => $this->helper->route('sheer_knowledgebase_search'),
				'S_IS_SEARCH'        => (bool) $this->config['kb_search'],
				'S_KB_SEARCH_ACTION' => $this->helper->route('sheer_knowledgebase_search'),
				'CATS_DROPBOX'       => $this->kb->make_category_dropbox(),
			)
		);

		$this->template->assign_block_vars('navlinks', array(
				'FORUM_NAME'   => $this->language->lang('LIBRARY'),
				'U_VIEW_FORUM' => $this->helper->route('sheer_knowledgebase_index'),
			)
		);

		return $this->helper->render('@sheer_knowledgebase/kb_index_body.html', $this->language->lang('LIBRARY'));
	}
}
