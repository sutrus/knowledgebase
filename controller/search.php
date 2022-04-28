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

class search
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

	/** @var \phpbb\request\request */
	protected $request;

	/** @var \phpbb\template\template */
	protected $template;

	/** @var \phpbb\user */
	protected $user;

	/** @var \phpbb\pagination */
	protected $pagination;

	/** @var \sheer\knowledgebase\inc\functions_kb */
	protected $kb;

	/** @var \sheer\knowledgebase\search\kb_search_backend_factory */
	protected $search_factory;

	/** @var string */
	protected $phpbb_root_path;

	/** @var string */
	protected $php_ext;

	/** @var string */
	protected $articles_table;

	/** @var string */
	protected $categories_table;

	/**
	 * Constructor
	 *
	 * @param \phpbb\db\driver\driver_interface                     $db
	 * @param \phpbb\config\config                                  $config
	 * @param \phpbb\controller\helper                              $helper
	 * @param \phpbb\language\language                              $language
	 * @param \phpbb\auth\auth                                      $auth
	 * @param \phpbb\request\request_interface                      $request
	 * @param \phpbb\template\template                              $template
	 * @param \phpbb\user                                           $user
	 * @param \phpbb\pagination                                     $pagination
	 * @param \sheer\knowledgebase\inc\functions_kb                 $kb
	 * @param \sheer\knowledgebase\search\kb_search_backend_factory $search_factory
	 * @param string                                                $phpbb_root_path
	 * @param string                                                $php_ext
	 * @param string                                                $articles_table
	 * @param string                                                $categories_table
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
		\sheer\knowledgebase\search\kb_search_backend_factory $search_factory,
		$phpbb_root_path,
		$php_ext,
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
		$this->search_factory = $search_factory;
		$this->phpbb_root_path = $phpbb_root_path;
		$this->php_ext = $php_ext;
		$this->articles_table = $articles_table;
		$this->categories_table = $categories_table;
	}

	/**
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function main()
	{
		$this->language->add_lang(array('search'));

		// Define initial vars
		$start = $this->request->variable('start', 0);
		$submit = $this->request->variable('submit', false);
		$keywords = $this->request->variable('keywords', '', true);
		$author = $this->request->variable('author', '', true);
		$category_id = $this->request->variable('cid', 0);
		$show_results = ($category_id) ? 'posts' : $this->request->variable('sr', 'posts');
		$show_results = ($show_results == 'posts') ? 'posts' : 'topics';
		$search_terms = $this->request->variable('terms', 'all');
		$search_fields = $this->request->variable('sf', 'all');

		$sort_days = $this->request->variable('st', 0);
		$sort_key = $this->request->variable('sk', 't');
		$sort_dir = $this->request->variable('sd', 'd');

		$return_chars = $this->request->variable('ch', 300);
		$categories = $this->request->variable('cat_ids', array(0));

		// Is search enabled?
		if (!$this->config['kb_search'])
		{
			trigger_error('SEARCH_DISABLED');
		}

		// Is user able to search? Has search been disabled?
		if (!$this->auth->acl_get('u_search') || !$this->config['load_search'])
		{
			$this->template->assign_var('S_NO_SEARCH', true);
			trigger_error('NO_SEARCH');
		}

		// Check search load limit
		if ($this->user->load && $this->config['limit_search_load'] && ($this->user->load > doubleval($this->config['limit_search_load'])))
		{
			$this->template->assign_var('S_NO_SEARCH', true);
			trigger_error('NO_SEARCH_TIME');
		}

		// Define some vars
		$limit_days = [
			0   => $this->language->lang('ALL_RESULTS'), 1 => $this->language->lang('1_DAY'),
			7   => $this->language->lang('7_DAYS'), 14 => $this->language->lang('2_WEEKS'),
			30  => $this->language->lang('1_MONTH'), 90 => $this->language->lang('3_MONTHS'),
			180 => $this->language->lang('6_MONTHS'), 365 => $this->language->lang('1_YEAR')
		];
		$sort_by_text = [
			't' => $this->language->lang('SORT_TIME'), 'a' => $this->language->lang('SORT_AUTHOR'),
			'c' => $this->language->lang('CATEGORY'), 's' => $this->language->lang('SORT_ARTICLE_TITLE')
		];

		$s_limit_days = $s_sort_key = $s_sort_dir = $u_sort_param = '';
		gen_sort_selects($limit_days, $sort_by_text, $sort_days, $sort_key, $sort_dir, $s_limit_days, $s_sort_key, $s_sort_dir, $u_sort_param);

		$cat_ary = $ex_fid_ary = array();
		if (!empty($categories))
		{
			$sql = 'SELECT category_id
				FROM ' . $this->categories_table;
			$result = $this->db->sql_query($sql);
			while ($row = $this->db->sql_fetchrow($result))
			{
				$cat_ary[] = $row['category_id'];
			}
			$this->db->sql_freeresult($result);

			foreach ($cat_ary as $value)
			{
				if (!in_array($value, $categories))
				{
					$ex_fid_ary[] = $value;
				}
			}
		}

		// clear arrays
		$id_ary = array();

		// If we are looking for authors get their ids
		$author_id_ary = [];
		if ($author)
		{
			$author_ary[] = $author;
			include_once($this->phpbb_root_path . 'includes/functions_user.' . $this->php_ext);
			user_get_id_name( $author_id_ary, $author_ary, false, true);
		}

		// Select which method we'll use to obtain the post_id or topic_id information
		try
		{
			$kb_search = $this->search_factory->get_active();
		}
		catch (RuntimeException $e)
		{
			if (strpos($e->getMessage(), 'No service found') === 0)
			{
				trigger_error('NO_SUCH_SEARCH_MODULE');
			}
			else
			{
				throw $e;
			}
		}

		if ($keywords)
		{
			$correct_query = $kb_search->split_keywords($keywords, $search_terms);
			$common_words = $kb_search->get_common_words();

			if (!$correct_query || (!$kb_search->get_search_query() && !count($author_id_ary)))
			{
				$ignored = (count($common_words)) ? sprintf($this->language->lang('IGNORED_TERMS_EXPLAIN'), implode(' ', $common_words)) . '<br />' : '';
				$word_length = $kb_search->get_word_length();
				if ($word_length)
				{
					trigger_error($ignored . $this->language->lang('NO_KEYWORDS', $this->language->lang('CHARACTERS', (int) $word_length['min']), $this->language->lang('CHARACTERS', (int) $word_length['max'])));
				}
				else
				{
					trigger_error($ignored);
				}
			}
		}

		// define some variables needed for retrieving post_id/topic_id information
		$sort_by_sql = [
			'a' => 'author',
			't' => 'article_date',
			'c' => 'article_category_id',
			's' => (($show_results == 'posts') ? 'article_title' : 'category_name'),
		];

		// show_results should not change after this
		$per_page = $this->config['kb_per_page_search'] ?? 10;
		$total_match_count = 0;

		// Set limit for the $total_match_count to reduce server load
		$total_matches_limit = 1000;
		$found_more_search_matches = false;

		// make sure that some arrays are always in the same order
		sort($ex_fid_ary);
		sort($author_id_ary);

		// define some vars for urls
		// A single wildcard will make the search results look ugly
		$hilit = phpbb_clean_search_string(str_replace(array('+', '-', '|', '(', ')', '&quot;'), ' ', $keywords));
		$hilit = str_replace(' ', '|', $hilit);

		$u_hilit = urlencode(htmlspecialchars_decode(str_replace('|', ' ', $hilit), ENT_COMPAT));
		$u_show_results = '&amp;sr=' . $show_results;
		$u_search_cat = implode('&amp;cat_ids%5B%5D=', $categories);

		$search_url = append_sid("{$this->phpbb_root_path}knowledgebase/search", $u_sort_param . $u_show_results);
		$search_url .= ($u_hilit) ? '&amp;keywords=' . urlencode(htmlspecialchars_decode($keywords, ENT_COMPAT)) : '';
		$search_url .= ($search_terms != 'all') ? '&amp;terms=' . $search_terms : '';
		$search_url .= ($category_id) ? '&amp;cid=' . $category_id : '';
		$search_url .= ($author) ? '&amp;author=' . urlencode(htmlspecialchars_decode($author, ENT_COMPAT)) : '';
		$search_url .= ($search_fields != 'all') ? '&amp;sf=' . $search_fields : '';
		$search_url .= ($return_chars !== (int) $this->config['default_search_return_chars']) ? '&amp;ch=' . $return_chars : '';
		$search_url .= ($u_search_cat) ? '&amp;cat_ids%5B%5D=' . $u_search_cat : '';

		if ($hilit)
		{
			// Remove bad highlights
			$hilit_array = array_filter(explode('|', $hilit), 'strlen');
			foreach ($hilit_array as $key => $value)
			{
				$hilit_array[$key] = phpbb_clean_search_string($value);
				$hilit_array[$key] = str_replace('\*', '\w*?', preg_quote($hilit_array[$key], '#'));
				$hilit_array[$key] = preg_replace('#(^|\s)\\\\w\*\?(\s|$)#', '$1\w+?$2', $hilit_array[$key]);
			}
			$hilit = implode('|', $hilit_array);
		}

		if ($kb_search->get_search_query())
		{
			$total_match_count = $kb_search->keyword_search($show_results, $search_fields, $search_terms, $sort_by_sql, $sort_key, $sort_dir, $sort_days, $ex_fid_ary, $category_id, $author_id_ary, $author, $id_ary, $start, $per_page);
		}
		else if (count($author_id_ary))
		{
			$total_match_count = $kb_search->author_search($show_results, $sort_by_sql, $sort_key, $sort_dir, $sort_days, $ex_fid_ary, $category_id, $author_id_ary, $author, $id_ary, $start, $per_page);
		}

		if (count($id_ary))
		{
			$sql_sort = $sort_by_sql[$sort_key] . (($sort_dir == 'a') ? ' ASC' : ' DESC');

			$sql = 'SELECT DISTINCT a.*, u.user_id, u.username, u.user_colour
					FROM ' . $this->articles_table . ' a, ' . USERS_TABLE . ' u
					WHERE ' . $this->db->sql_in_set('article_id', $id_ary) . '
						AND (a.author_id = u.user_id)
						AND a.approved = 1';
			if ($author && $keywords)
			{
				$sql .= ' AND author_id = ' . $author_id;
			}
			$sql .= ' ORDER BY ' . $sql_sort;
			$result = $this->db->sql_query($sql);
			while ($row = $this->db->sql_fetchrow($result))
			{
				$article_id = $row['article_id'];
				$article_info = $this->kb->get_kb_article_info($article_id);
				$category_id = $article_info['article_category_id'];

				if ($show_results == 'posts')
				{
					$text_only_message = $message = $row['article_body'];
					if ($row['bbcode_uid'])
					{
						$text_only_message = str_replace('[*:' . $row['bbcode_uid'] . ']', '&sdot;&nbsp;', $text_only_message);

						// no BBCode in text only message
						strip_bbcode($text_only_message, $row['bbcode_uid']);
						$row['article_body'] = get_context($text_only_message, array_filter(explode('|', $hilit), 'strlen'), $return_chars);
						$row['article_body'] = bbcode_nl2br($row['article_body']);

					}
					if ($return_chars == -1 || utf8_strlen($text_only_message) < ($return_chars + 3))
					{
						$row['bbcode_bitfield'] = false;
						$parse_flags = ($row['bbcode_bitfield'] ? OPTION_FLAG_BBCODE : 0) | OPTION_FLAG_SMILIES;
						$row['article_body'] = generate_text_for_display($message, $row['bbcode_uid'], $row['bbcode_bitfield'], $parse_flags, false);
						$row['article_body'] = strtr($row['article_body'], array('&lt;' => '<', '&gt;' => '>'));
					}

					if ($hilit)
					{
						$row['article_body'] = preg_replace('#(?!<.*)(?<!\w)(' . $hilit . ')(?!\w|[^<>]*(?:</s(?:cript|tyle))?>)#is', '<span class="posthilit">\1</span>', $row['article_body']);
						$row['article_title'] = preg_replace('#(?!<.*)(?<!\w)(' . $hilit . ')(?!\w|[^<>]*(?:</s(?:cript|tyle))?>)#isu', '<span class="posthilit">$1</span>', $row['article_title']);
					}
				}

				$category = $this->kb->get_cat_info($row['article_category_id']);

				$this->template->assign_block_vars('searchrow', array(
						'MESSAGE'   => $row['article_body'],
						'DATE'      => $this->user->format_date($row['article_date']),
						'TITLE'     => $row['article_title'],
						'U_VIEW'    => $this->helper->route('sheer_knowledgebase_article', array('k' => $article_id)),
						'CATEGORY'  => $category['category_name'],
						'U_CAT'     => $this->helper->route('sheer_knowledgebase_category', array('id' => $category_id)),
						'USER_FULL' => get_username_string('full', $row['user_id'], $row['username'], $row['user_colour']),
						'ID'        => $article_id,
					)
				);
			}
			$this->db->sql_freeresult($result);
		}

		$this->pagination->generate_template_pagination($search_url, 'pagination', 'start', $total_match_count, $per_page, $start);

		$this->template->assign_vars(array(
			'TOTAL_ITEMS'             => $this->language->lang('TOTAL_ITEMS', (int) $total_match_count),
			'PAGE_NUMBER'             => $this->pagination->on_page($total_match_count, $per_page, $start),
			'TOTAL_MATCHES'           => $total_match_count,
			'SEARCH_MATCHES'          => ($total_match_count == 1) ? $this->language->lang('FOUND_KB_SEARCH_MATCH', (int) $total_match_count) : $this->language->lang('FOUND_KB_SEARCH_MATCHES', (int) $total_match_count),
			'U_SEARCH_WORDS'          => $search_url,
			'SEARCH_WORDS_AND_AUTHOR' => $author . ' &bull; ' . $keywords,
			'SEARCH_WORDS'            => $keywords,
		));

		$this->template->assign_vars(array(
				'S_SHOW_TITLES'      => !(($show_results == 'posts')),
				'S_SELECT_SORT_DAYS' => $s_limit_days,
				'S_SELECT_SORT_KEY'  => $s_sort_key,
				'S_SELECT_SORT_DIR'  => $s_sort_dir,
				'S_SEARCH_ACTION'    => $search_url,
				'U_KB_SEARCH'        => $this->helper->route('sheer_knowledgebase_search'),
				'CATS_BOX'           => $this->kb->make_category_select(0, false, false),
			)
		);

		$this->template->assign_block_vars('navlinks', array(
				'FORUM_NAME'   => $this->language->lang('LIBRARY'),
				'U_VIEW_FORUM' => $this->helper->route('sheer_knowledgebase_index'),
			)
		);

		$this->template->assign_block_vars('navlinks', array(
				'FORUM_NAME'   => $this->language->lang('SEARCH'),
				'U_VIEW_FORUM' => $this->helper->route('sheer_knowledgebase_search'),
			)
		);

		if (($keywords && $keywords != $this->language->lang('SEARCH_MINI')) || $author)
		{
			$html_template = 'kb_search_results.html';
		}
		else
		{
			$html_template = 'kb_search_body.html';
		}

		return $this->helper->render('@sheer_knowledgebase/' . $html_template, $this->language->lang('LIBRARY'));
	}
}
