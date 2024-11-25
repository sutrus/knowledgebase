<?php
/**
 *
 * This file is part of the phpBB Forum Software package.
 *
 * @copyright (c) phpBB Limited <https://www.phpbb.com>
 * @license       GNU General Public License, version 2 (GPL-2.0)
 *
 * For full copyright and license information, please see
 * the docs/CREDITS.txt file.
 *
 */

namespace sheer\knowledgebase\search\backend;

use phpbb\config\config;
use phpbb\db\driver\driver_interface;
use phpbb\language\language;
use phpbb\user;

/**
 * phpBB's own db driven fulltext search, version 2
 */
class kb_fulltext_native extends kb_base implements kb_search_backend_interface
{
	protected const UTF8_HANGUL_FIRST = "\xEA\xB0\x80";
	protected const UTF8_HANGUL_LAST = "\xED\x9E\xA3";
	protected const UTF8_CJK_FIRST = "\xE4\xB8\x80";
	protected const UTF8_CJK_LAST = "\xE9\xBE\xBB";
	protected const UTF8_CJK_B_FIRST = "\xF0\xA0\x80\x80";
	protected const UTF8_CJK_B_LAST = "\xF0\xAA\x9B\x96";

	/**
	 * Associative array holding index stats
	 *
	 * @var array
	 */
	protected $stats = [];

	/**
	 * Associative array stores the min and max word length to be searched
	 *
	 * @var array
	 */
	protected $word_length = [];

	/**
	 * Contains tidied search query.
	 * Operators are prefixed in search query and common words excluded
	 *
	 * @var string
	 */
	protected $search_query = '';

	/**
	 * Contains common words.
	 * Common words are words with length less/more than min/max length
	 *
	 * @var array
	 */
	protected $common_words = [];

	/**
	 * Post ids of posts containing words that are to be included
	 *
	 * @var array
	 */
	protected $must_contain_ids = [];

	/**
	 * Post ids of posts containing words that should not be included
	 *
	 * @var array
	 */
	protected $must_not_contain_ids = [];

	/**
	 * Post ids of posts containing at least one word that needs to be excluded
	 *
	 * @var array
	 */
	protected $must_exclude_one_ids = [];

	/**
	 * Relative path to board root
	 *
	 * @var string
	 */
	protected $phpbb_root_path;

	/**
	 * PHP Extension
	 *
	 * @var string
	 */
	protected $php_ext;

	/** @var language */
	protected $language;

	/** @var string */
	protected $articles_table;

	/** @var string */
	protected string $search_results_table;

	/** @var string */
	protected $wordmatch_table;

	/** @var string */
	protected $wordlist_table;

	/**
	 * Initialises the fulltext_native search backend with min/max word length
	 *
	 * @param config           $config               Config object
	 * @param driver_interface $db                   Database object
	 * @param language         $language
	 * @param user             $user                 User object
	 * @param string           $articles_table       Articles_table
	 * @param string           $search_results_table Search_results_table
	 * @param string           $wordmatch_table      Wordmatch_table
	 * @param string           $wordlist_table       Wordlist_table
	 * @param string           $phpbb_root_path      phpBB root path
	 * @param string           $phpEx                PHP file extension
	 */
	public function __construct(
		config $config,
		driver_interface $db,
		language $language,
		user $user,
		string $articles_table,
		string $search_results_table,
		string $wordmatch_table,
		string $wordlist_table,
		string $phpbb_root_path,
		string $phpEx
	)
	{
		global $cache;

		parent::__construct($cache, $config, $db, $user, $search_results_table);
		$this->language = $language;

		$this->articles_table = $articles_table;
		$this->wordmatch_table = $wordmatch_table;
		$this->wordlist_table = $wordlist_table;

		$this->phpbb_root_path = $phpbb_root_path;
		$this->php_ext = $phpEx;

		$this->word_length = ['min' => (int) $this->config['fulltext_native_min_chars'], 'max' => (int) $this->config['fulltext_native_max_chars']];

		/**
		 * Load the UTF tools
		 */
		if (!function_exists('utf8_decode_ncr'))
		{
			include($this->phpbb_root_path . 'includes/utf/utf_tools.' . $this->php_ext);
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_name(): string
	{
		return 'Knowledge Base Native Fulltext';
	}

	/**
	 * {@inheritdoc}
	 */
	public function is_available(): bool
	{
		return true;
	}

	/**
	 * {@inheritdoc}
	 */
	public function init()
	{
		return false;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_search_query(): string
	{
		return $this->search_query;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_common_words(): array
	{
		return $this->common_words;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_word_length()
	{
		return $this->word_length;
	}

	/**
	 * {@inheritdoc}
	 */
	public function split_keywords(string &$keywords, string $terms): bool
	{
		$tokens = '+-|()* ';

		$keywords = trim($this->cleanup($keywords, $tokens));

		// allow word|word|word without brackets
		if ((strpos($keywords, ' ') === false) && (strpos($keywords, '|') !== false) && (strpos($keywords, '(') === false))
		{
			$keywords = '(' . $keywords . ')';
		}

		$open_bracket = $space = false;
		for ($i = 0, $n = strlen($keywords); $i < $n; $i++)
		{
			if ($open_bracket !== false)
			{
				switch ($keywords[$i])
				{
					case ')':
						if ($open_bracket + 1 == $i)
						{
							$keywords[$i - 1] = '|';
							$keywords[$i] = '|';
						}
						$open_bracket = false;
					break;
					case '(':
						$keywords[$i] = '|';
					break;
					case '+':
					case '-':
					case ' ':
						$keywords[$i] = '|';
					break;
					case '*':
						// $i can never be 0 here since $open_bracket is initialised to false
						if (strpos($tokens, $keywords[$i - 1]) !== false && ($i + 1 === $n || strpos($tokens, $keywords[$i + 1]) !== false))
						{
							$keywords[$i] = '|';
						}
					break;
				}
			}
			else
			{
				switch ($keywords[$i])
				{
					case ')':
						$keywords[$i] = ' ';
					break;
					case '(':
						$open_bracket = $i;
						$space = false;
					break;
					case '|':
						$keywords[$i] = ' ';
					break;
					case '-':
						// Ignore hyphen if followed by a space
						if (isset($keywords[$i + 1]) && $keywords[$i + 1] == ' ')
						{
							$keywords[$i] = ' ';
						}
						else
						{
							$space = $keywords[$i];
						}
					break;
					case '+':
						$space = $keywords[$i];
					break;
					case ' ':
						if ($space !== false)
						{
							$keywords[$i] = $space;
						}
					break;
					default:
						$space = false;
				}
			}
		}

		if ($open_bracket !== false)
		{
			$keywords .= ')';
		}

		$match = [
			'#  +#',
			'#\|\|+#',
			'#(\+|\-)(?:\+|\-)+#',
			'#\(\|#',
			'#\|\)#',
		];
		$replace = [
			' ',
			'|',
			'$1',
			'(',
			')',
		];

		$keywords = preg_replace($match, $replace, $keywords);
		$num_keywords = count(explode(' ', $keywords));

		// We limit the number of allowed keywords to minimize load on the database
		if ($this->config['max_num_search_keywords'] && $num_keywords > $this->config['max_num_search_keywords'])
		{
			trigger_error($this->language->lang('MAX_NUM_SEARCH_KEYWORDS_REFINE', (int) $this->config['max_num_search_keywords'], $num_keywords));
		}

		// $keywords input format: each word separated by a space, words in a bracket are not separated

		// the user wants to search for any word, convert the search query
		if ($terms == 'any')
		{
			$words = [];

			preg_match_all('#([^\\s+\\-|()]+)(?:$|[\\s+\\-|()])#u', $keywords, $words);
			if (count($words[1]))
			{
				$keywords = '(' . implode('|', $words[1]) . ')';
			}
		}

		// Remove non trailing wildcards from each word to prevent a full table scan (it's now using the database index)
		$match = '#\*(?!$|\s)#';
		$replace = '$1';
		$keywords = preg_replace($match, $replace, $keywords);

		// Only allow one wildcard in the search query to limit the database load
		$match = '#\*#';
		$count_wildcards = substr_count($keywords, '*');

		// Reverse the string to remove all wildcards except the first one
		$keywords = strrev(preg_replace($match, $replace, strrev($keywords), $count_wildcards - 1));
		unset($count_wildcards);

		// set the search_query which is shown to the user
		$this->search_query = $keywords;

		$exact_words = [];
		preg_match_all('#([^\\s+\\-|()]+)(?:$|[\\s+\\-|()])#u', $keywords, $exact_words);
		$exact_words = $exact_words[1];

		$common_ids = $words = [];

		if (count($exact_words))
		{
			$sql = 'SELECT word_id, word_text, word_common
				FROM ' . $this->wordlist_table . '
				WHERE ' . $this->db->sql_in_set('word_text', $exact_words) . '
				ORDER BY word_count ASC';
			$result = $this->db->sql_query($sql);

			// store an array of words and ids, remove common words
			while ($row = $this->db->sql_fetchrow($result))
			{
				if ($row['word_common'])
				{
					$this->common_words[] = $row['word_text'];
					$common_ids[$row['word_text']] = (int) $row['word_id'];
					continue;
				}

				$words[$row['word_text']] = (int) $row['word_id'];
			}
			$this->db->sql_freeresult($result);
		}

		// Handle +, - without preceding whitespace character
		$match = ['#(\S)\+#', '#(\S)-#'];
		$replace = ['$1 +', '$1 +'];

		$keywords = preg_replace($match, $replace, $keywords);

		// now analyse the search query, first split it using the spaces
		$query = explode(' ', $keywords);

		$this->must_contain_ids = [];
		$this->must_not_contain_ids = [];
		$this->must_exclude_one_ids = [];

		foreach ($query as $word)
		{
			if (empty($word))
			{
				continue;
			}

			// words which should not be included
			if ($word[0] == '-')
			{
				$word = substr($word, 1);

				// a group of which at least one may not be in the resulting posts
				if ($word[0] == '(')
				{
					$word = array_unique(explode('|', substr($word, 1, -1)));
					$mode = 'must_exclude_one';
				}
				// one word which should not be in the resulting posts
				else
				{
					$mode = 'must_not_contain';
				}
				$ignore_no_id = true;
			}
			// words which have to be included
			else
			{
				// no prefix is the same as a +prefix
				if ($word[0] == '+')
				{
					$word = substr($word, 1);
				}

				// a group of words of which at least one word should be in every resulting post
				if (isset($word[0]) && $word[0] == '(')
				{
					$word = array_unique(explode('|', substr($word, 1, -1)));
				}
				$ignore_no_id = false;
				$mode = 'must_contain';
			}

			if (empty($word))
			{
				continue;
			}

			// if this is an array of words then retrieve an id for each
			if (is_array($word))
			{
				$non_common_words = [];
				$id_words = [];
				foreach ($word as $i => $word_part)
				{
					if (strpos($word_part, '*') !== false)
					{
						$len = utf8_strlen(str_replace('*', '', $word_part));
						if ($len >= $this->word_length['min'] && $len <= $this->word_length['max'])
						{
							$id_words[] = '\'' . $this->db->sql_escape(str_replace('*', '%', $word_part)) . '\'';
							$non_common_words[] = $word_part;
						}
						else
						{
							$this->common_words[] = $word_part;
						}
					}
					else if (isset($words[$word_part]))
					{
						$id_words[] = $words[$word_part];
						$non_common_words[] = $word_part;
					}
					else
					{
						$len = utf8_strlen($word_part);
						if ($len < $this->word_length['min'] || $len > $this->word_length['max'])
						{
							$this->common_words[] = $word_part;
						}
					}
				}
				if (count($id_words))
				{
					sort($id_words);
					if (count($id_words) > 1)
					{
						$this->{$mode . '_ids'}[] = $id_words;
					}
					else
					{
						$mode = ($mode === 'must_exclude_one') ? 'must_not_contain' : $mode;
						$this->{$mode . '_ids'}[] = $id_words[0];
					}
				}
				// throw an error if we shall not ignore unexistant words
				else if (!$ignore_no_id && count($non_common_words))
				{
					trigger_error(sprintf($this->language->lang('WORDS_IN_NO_POST'), implode($this->language->lang('COMMA_SEPARATOR'), $non_common_words)));
				}
				unset($non_common_words);
			}
			// else we only need one id
			else if (($wildcard = strpos($word, '*') !== false) || isset($words[$word]))
			{
				if ($wildcard)
				{
					$len = utf8_strlen(str_replace('*', '', $word));
					if ($len >= $this->word_length['min'] && $len <= $this->word_length['max'])
					{
						$this->{$mode . '_ids'}[] = '\'' . $this->db->sql_escape(str_replace('*', '%', $word)) . '\'';
					}
					else
					{
						$this->common_words[] = $word;
					}
				}
				else
				{
					$this->{$mode . '_ids'}[] = $words[$word];
				}
			}
			else
			{
				if (!isset($common_ids[$word]))
				{
					$len = utf8_strlen($word);
					if ($len < $this->word_length['min'] || $len > $this->word_length['max'])
					{
						$this->common_words[] = $word;
					}
				}
			}
		}

		// Return true if all words are not common words
		if (count($exact_words) - count($this->common_words) > 0)
		{
			return true;
		}
		return false;
	}


	/**
	 * {@inheritdoc}
	 */
	public function keyword_search(string $type, string $fields, string $terms, array $sort_by_sql, string $sort_key, string $sort_dir, string $sort_days, array $ex_fid_ary, int $category_id, array $author_ary, string $author_name, array &$id_ary, int &$start, int $per_page)
	{
		// No keywords? No posts.
		// we can't search for negatives only
		if (empty($this->search_query) || empty($this->must_contain_ids))
		{
			return false;
		}

		$must_contain_ids = $this->must_contain_ids;
		$must_not_contain_ids = $this->must_not_contain_ids;
		$must_exclude_one_ids = $this->must_exclude_one_ids;

		sort($must_contain_ids);
		sort($must_not_contain_ids);
		sort($must_exclude_one_ids);

		// generate a search_key from all the options to identify the results
		$search_key_array = [
			serialize($must_contain_ids),
			serialize($must_not_contain_ids),
			serialize($must_exclude_one_ids),
			$type,
			$fields,
			$terms,
			$sort_days,
			$sort_key,
			$category_id,
			implode(',', $ex_fid_ary),
			'',
			implode(',', $author_ary),
			$author_name,
		];
		$search_key = md5(implode('#', $search_key_array));

		// try reading the results from cache
		$total_results = 0;
		if ($this->obtain_ids($search_key, $total_results, $id_ary, $start, $per_page, $sort_dir) == self::KB_SEARCH_RESULT_IN_CACHE)
		{
			return $total_results;
		}

		$id_ary = [];

		$sql_where = [];
		$m_num = 0;
		$w_num = 0;

		$sql_array = [
			'SELECT'    => 'p.article_id',
			'FROM'      => [
				$this->wordmatch_table => [],
				$this->wordlist_table  => [],
			],
			'LEFT_JOIN' => [[
								'FROM' => [$this->articles_table => 'p'],
								'ON'   => 'm0.article_id = p.article_id',
							]],
		];

		$title_match = '';
		$group_by = true;
		// Build some display specific sql strings
		switch ($fields)
		{
			case 'titleonly':
				$title_match = 'title_match = 1 AND descr_match = 0';
				$group_by = false;
			break;

			case 'msgonly':
				$title_match = 'title_match = 0 AND descr_match = 0';
				$group_by = false;
			break;

			case 'descronly':
				$title_match = 'title_match = 0 AND descr_match = 1';
				$group_by = false;
			break;
		}

		foreach ($this->must_contain_ids as $subquery)
		{
			if (is_array($subquery))
			{
				$group_by = true;

				$word_id_sql = [];
				$word_ids = [];
				foreach ($subquery as $id)
				{
					if (is_string($id))
					{
						$sql_array['LEFT_JOIN'][] = [
							'FROM' => [$this->wordlist_table => 'w' . $w_num],
							'ON'   => "w$w_num.word_text LIKE $id",
						];
						$word_ids[] = "w$w_num.word_id";

						$w_num++;
					}
					else
					{
						$word_ids[] = $id;
					}
				}

				$sql_where[] = $this->db->sql_in_set("m$m_num.word_id", $word_ids);

				unset($word_id_sql);
				unset($word_ids);
			}
			else if (is_string($subquery))
			{
				$sql_array['FROM'][$this->wordlist_table][] = 'w' . $w_num;

				$sql_where[] = "w$w_num.word_text LIKE $subquery";
				$sql_where[] = "m$m_num.word_id = w$w_num.word_id";

				$group_by = true;
				$w_num++;
			}
			else
			{
				$sql_where[] = "m$m_num.word_id = $subquery";
			}

			$sql_array['FROM'][$this->wordmatch_table][] = 'm' . $m_num;

			if ($title_match)
			{
				$sql_where[] = "m$m_num.$title_match";
			}

			if ($m_num !== 0)
			{
				$sql_where[] = "m$m_num.article_id = m0.article_id";
			}
			$m_num++;
		}

		foreach ($this->must_not_contain_ids as $key => $subquery)
		{
			if (is_string($subquery))
			{
				$sql_array['LEFT_JOIN'][] = [
					'FROM' => [$this->wordlist_table => 'w' . $w_num],
					'ON'   => "w$w_num.word_text LIKE $subquery",
				];

				$this->must_not_contain_ids[$key] = "w$w_num.word_id";

				$group_by = true;
				$w_num++;
			}
		}

		if (count($this->must_not_contain_ids))
		{
			$sql_array['LEFT_JOIN'][] = [
				'FROM' => [$this->wordmatch_table => 'm' . $m_num],
				'ON'   => $this->db->sql_in_set("m$m_num.word_id", $this->must_not_contain_ids) . (($title_match) ? " AND m$m_num.$title_match" : '') . " AND m$m_num.article_id = m0.article_id",
			];

			$sql_where[] = "m$m_num.word_id IS NULL";
			$m_num++;
		}

		foreach ($this->must_exclude_one_ids as $ids)
		{
			$is_null_joins = [];
			foreach ($ids as $id)
			{
				if (is_string($id))
				{
					$sql_array['LEFT_JOIN'][] = [
						'FROM' => [$this->wordlist_table => 'w' . $w_num],
						'ON'   => "w$w_num.word_text LIKE $id",
					];
					$id = "w$w_num.word_id";

					$group_by = true;
					$w_num++;
				}

				$sql_array['LEFT_JOIN'][] = [
					'FROM' => [$this->wordmatch_table => 'm' . $m_num],
					'ON'   => "m$m_num.word_id = $id AND m$m_num.article_id = m0.article_id" . (($title_match) ? " AND m$m_num.$title_match" : ''),
				];
				$is_null_joins[] = "m$m_num.word_id IS NULL";

				$m_num++;
			}
			$sql_where[] = '(' . implode(' OR ', $is_null_joins) . ')';
		}

		$sql_where[] = 'p.approved = 1';

		$search_query = $this->search_query;
		$must_exclude_one_ids = $this->must_exclude_one_ids;
		$must_not_contain_ids = $this->must_not_contain_ids;
		$must_contain_ids = $this->must_contain_ids;

		$sql_sort_table = $sql_sort_join = $sql_match = $sql_match_where = $sql_sort = '';

		if ($category_id)
		{
			$sql_where[] = 'p.article_category_id = ' . $category_id;
		}

		if (count($author_ary))
		{
			$sql_author = $this->db->sql_in_set('p.author_id', $author_ary);
			$sql_where[] = $sql_author;
		}

		if (count($ex_fid_ary))
		{
			$sql_where[] = $this->db->sql_in_set('p.article_category_id', $ex_fid_ary, true);
		}

		if ($sort_days)
		{
			$sql_where[] = 'p.article_date >= ' . (time() - ($sort_days * 86400));
		}

		$sql_array['WHERE'] = implode(' AND ', $sql_where);

		$is_mysql = false;
		// if the total result count is not cached yet, retrieve it from the db
		if (!$total_results)
		{
			$sql = '';
			$sql_array_count = $sql_array;

			switch ($this->db->get_sql_layer())
			{
				case 'mysqli':
					$is_mysql = true;

				break;

				case 'sqlite3':
					$sql_array_count['SELECT'] = 'DISTINCT p.article_id';
					$sql = 'SELECT COUNT(article_id) as total_results
							FROM (' . $this->db->sql_build_query('SELECT', $sql_array_count) . ')';

				// no break

				default:
					$sql_array_count['SELECT'] = 'COUNT(DISTINCT p.article_id) AS total_results';
					$sql = (!$sql) ? $this->db->sql_build_query('SELECT', $sql_array_count) : $sql;

					$result = $this->db->sql_query($sql);
					$total_results = (int) $this->db->sql_fetchfield('total_results');
					$this->db->sql_freeresult($result);

					if (!$total_results)
					{
						return false;
					}
				break;
			}

			unset($sql_array_count, $sql);
		}

		// Build sql strings for sorting
		$sql_sort = $sort_by_sql[$sort_key] . (($sort_dir == 'a') ? ' ASC' : ' DESC');

		$sql_array['WHERE'] = implode(' AND ', $sql_where);
		$sql_array['GROUP_BY'] = ($group_by) ? 'p.article_id, ' . $sort_by_sql[$sort_key] : '';
		$sql_array['ORDER_BY'] = $sql_sort;
		$sql_array['SELECT'] .= $sort_by_sql[$sort_key] ? ", $sort_by_sql[$sort_key]" : '';

		unset($sql_where, $sql_sort, $group_by);

		$sql = $this->db->sql_build_query('SELECT', $sql_array);
		$result = $this->db->sql_query_limit($sql, $this->config['search_block_size'], $start);

		while ($row = $this->db->sql_fetchrow($result))
		{
			$id_ary[] = (int) $row['article_id'];
		}
		$this->db->sql_freeresult($result);

		// If using mysql and the total result count is not calculated yet, get it from the db
		if (!$total_results && $is_mysql)
		{
			$sql_count = str_replace("SELECT {$sql_array['SELECT']}", "SELECT COUNT(DISTINCT {$sql_array['SELECT']}) as total_results", $sql);
			$result = $this->db->sql_query($sql_count);
//			$total_results = (int) $this->db->sql_fetchfield('total_results');
			$total_results = count($this->db->sql_fetchrowset($result));
			$this->db->sql_freeresult($result);

			if (!$total_results)
			{
				return false;
			}
		}

		if ($start >= $total_results)
		{
			$start = floor(($total_results - 1) / $per_page) * $per_page;

			$result = $this->db->sql_query_limit($sql, $this->config['search_block_size'], $start);

			while ($row = $this->db->sql_fetchrow($result))
			{
				$id_ary[] = (int) $row['article_id'];
			}
			$this->db->sql_freeresult($result);
		}

		// store the ids, from start on then delete anything that isn't on the current page because we only need ids for one page
		$this->save_ids($search_key, $this->search_query, $author_ary, $total_results, $id_ary, $start, $sort_dir);
		$id_ary = array_slice($id_ary, 0, $per_page);

		return $total_results;
	}

	/**
	 * {@inheritdoc}
	 */
	public function author_search(string $type, array $sort_by_sql, string $sort_key, string $sort_dir, string $sort_days, array $ex_fid_ary, int $category_id, array $author_ary, string $author_name, array &$id_ary, int &$start, int $per_page)
	{
		// No author? No posts
		if (!count($author_ary))
		{
			return 0;
		}

		// generate a search_key from all the options to identify the results
		$search_key_array = [
			'',
			$type,
			'',
			'',
			'',
			$sort_days,
			$sort_key,
			$category_id,
			implode(',', $ex_fid_ary),
			'',
			implode(',', $author_ary),
			$author_name,
		];
		$search_key = md5(implode('#', $search_key_array));

		// try reading the results from cache
		$total_results = 0;
		if ($this->obtain_ids($search_key, $total_results, $id_ary, $start, $per_page, $sort_dir) == self::KB_SEARCH_RESULT_IN_CACHE)
		{
			return $total_results;
		}

		$id_ary = [];

		// Create some display specific sql strings
		$sql_author = $this->db->sql_in_set('p.author_id', $author_ary);
		$sql_fora = (count($ex_fid_ary)) ? ' AND ' . $this->db->sql_in_set('p.article_category_id', $ex_fid_ary, true) : '';
		$sql_time = ($sort_days) ? ' AND p.article_date >= ' . (time() - ($sort_days * 86400)) : '';
		$sql_category_id = ($category_id) ? ' AND p.article_category_id = ' . $category_id : '';
		$post_visibility = ' AND p.approved = 1 ';

		// Build sql strings for sorting
		$sql_sort = $sort_by_sql[$sort_key] . (($sort_dir == 'a') ? ' ASC' : ' DESC');

		$select = 'p.article_id';
		$select .= $sort_by_sql[$sort_key] ? ", $sort_by_sql[$sort_key]" : '';
		$is_mysql = false;

		// If the cache was completely empty count the results
		if (!$total_results)
		{
			switch ($this->db->get_sql_layer())
			{
				case 'mysqli':
					$is_mysql = true;
				break;

				default:
					$sql = 'SELECT COUNT(p.article_id) as total_results
						FROM ' . $this->articles_table . ' p
						WHERE ' . $sql_author .
						$sql_category_id .
						$sql_fora .
						$sql_time .
						$post_visibility;
					$result = $this->db->sql_query($sql);

					$total_results = (int) $this->db->sql_fetchfield('total_results');
					$this->db->sql_freeresult($result);

					if (!$total_results)
					{
						return false;
					}
				break;
			}
		}

		// Build the query for really selecting the article_ids
		$sql = "SELECT $select
			FROM " . $this->articles_table . " p
			WHERE $sql_author
				$sql_category_id
				$sql_fora
				$sql_time
			ORDER BY $sql_sort";
		$field = 'article_id';

		// Only read one block of posts from the db and then cache it
		$result = $this->db->sql_query_limit($sql, $this->config['search_block_size'], $start);

		while ($row = $this->db->sql_fetchrow($result))
		{
			$id_ary[] = (int) $row[$field];
		}
		$this->db->sql_freeresult($result);

		if (!$total_results && $is_mysql)
		{
			$sql_count = str_replace("SELECT $select", 'SELECT COUNT(*) as total_results', $sql);
			$result = $this->db->sql_query($sql_count);
			$total_results = (int) $this->db->sql_fetchfield('total_results');
			$this->db->sql_freeresult($result);

			if (!$total_results)
			{
				return false;
			}
		}

		if ($start >= $total_results)
		{
			$start = floor(($total_results - 1) / $per_page) * $per_page;

			$result = $this->db->sql_query_limit($sql, $this->config['search_block_size'], $start);

			while ($row = $this->db->sql_fetchrow($result))
			{
				$id_ary[] = (int) $row[$field];
			}
			$this->db->sql_freeresult($result);
		}

		if (count($id_ary))
		{
			$this->save_ids($search_key, '', $author_ary, $total_results, $id_ary, $start, $sort_dir);
			$id_ary = array_slice($id_ary, 0, $per_page);

			return $total_results;
		}
		return false;
	}

	/**
	 * {@inheritdoc}
	 */
	public function supports_phrase_search(): bool
	{
		return false;
	}

	/**
	 * {@inheritdoc}
	 */
	public function index(string $mode, int $article_id, string &$message, string &$subject, string &$description, int $poster_id)
	{
		if (!$this->config['kb_search'])
		{
			/**
			 * The search indexer is disabled, return
			 */
			return;
		}

		// Split old and new article/title/description to obtain array of 'words'
		$split_text = $this->split_message($message);
		$split_title = $this->split_message($subject);
		$split_descr = $this->split_message($description);

		$cur_words = ['post' => [], 'title' => [], 'descr' => []];

		$words = [];
		if ($mode == 'edit')
		{
			$sql = 'SELECT w.word_id, w.word_text, m.title_match, m.descr_match
				FROM ' . $this->wordlist_table . ' w, ' . $this->wordmatch_table . ' m
				WHERE m.article_id = ' . $article_id . '
					AND w.word_id = m.word_id';
			$result = $this->db->sql_query($sql);

			while ($row = $this->db->sql_fetchrow($result))
			{
				$which = ($row['title_match']) ? 'title' : 'post';
				$which = ($row['descr_match']) ? 'descr' : $which;
				$cur_words[$which][$row['word_text']] = $row['word_id'];
			}
			$this->db->sql_freeresult($result);

			$words['add']['post'] = array_diff($split_text, array_keys($cur_words['post']));
			$words['add']['title'] = array_diff($split_title, array_keys($cur_words['title']));
			$words['add']['descr'] = array_diff($split_descr, array_keys($cur_words['descr']));
			$words['del']['post'] = array_diff(array_keys($cur_words['post']), $split_text);
			$words['del']['title'] = array_diff(array_keys($cur_words['title']), $split_title);
			$words['del']['descr'] = array_diff(array_keys($cur_words['descr']), $split_descr);
		}
		else
		{
			$words['add']['post'] = $split_text;
			$words['add']['title'] = $split_title;
			$words['add']['descr'] = $split_descr;
			$words['del']['post'] = [];
			$words['del']['title'] = [];
			$words['del']['descr'] = [];
		}
		unset($split_text);
		unset($split_title);
		unset($split_descr);

		// Get unique words from the above arrays
		$unique_add_words = array_unique(array_merge($words['add']['post'], $words['add']['title'], $words['add']['descr']));

		// We now have unique arrays of all words to be added and removed and
		// individual arrays of added and removed words for text and title. What
		// we need to do now is add the new words (if they don't already exist)
		// and then add (or remove) matches between the words and this post
		if (count($unique_add_words))
		{
			$sql = 'SELECT word_id, word_text
				FROM ' . $this->wordlist_table . '
				WHERE ' . $this->db->sql_in_set('word_text', $unique_add_words);
			$result = $this->db->sql_query($sql);

			$word_ids = [];
			while ($row = $this->db->sql_fetchrow($result))
			{
				$word_ids[$row['word_text']] = $row['word_id'];
			}
			$this->db->sql_freeresult($result);
			$new_words = array_diff($unique_add_words, array_keys($word_ids));

			$this->db->sql_transaction('begin');
			if (count($new_words))
			{
				$sql_ary = [];

				foreach ($new_words as $word)
				{
					$sql_ary[] = ['word_text' => (string) $word, 'word_count' => 0];
				}
				$this->db->sql_return_on_error(true);
				$this->db->sql_multi_insert($this->wordlist_table, $sql_ary);
				$this->db->sql_return_on_error(false);
			}
			unset($new_words, $sql_ary);
		}
		else
		{
			$this->db->sql_transaction('begin');
		}

		// now update the search match table, remove links to removed words and add links to new words
		foreach ($words['del'] as $word_in => $word_ary)
		{
			$title_match = ($word_in == 'title') ? 1 : 0;
			$descr_match = ($word_in == 'descr') ? 1 : 0;

			if (count($word_ary))
			{
				$sql_in = [];
				foreach ($word_ary as $word)
				{
					$sql_in[] = $cur_words[$word_in][$word];
				}

				$sql = 'DELETE FROM ' . $this->wordmatch_table . '
					WHERE ' . $this->db->sql_in_set('word_id', $sql_in) . '
						AND article_id = ' . $article_id . '
						AND (descr_match = ' . $descr_match . '
						OR title_match = ' . $title_match . ')';
				$this->db->sql_query($sql);

				$sql = 'UPDATE ' . $this->wordlist_table . '
					SET word_count = word_count - 1
					WHERE ' . $this->db->sql_in_set('word_id', $sql_in) . '
						AND word_count > 0';
				$this->db->sql_query($sql);

				unset($sql_in);
			}
		}

		$this->db->sql_return_on_error(true);
		foreach ($words['add'] as $word_in => $word_ary)
		{
			$title_match = ($word_in == 'title') ? 1 : 0;
			$descr_match = ($word_in == 'descr') ? 1 : 0;

			if (count($word_ary))
			{
				$sql = 'INSERT INTO ' . $this->wordmatch_table . ' (article_id, word_id, title_match, descr_match)
					SELECT ' . $article_id . ', word_id, ' . $title_match . ', ' . $descr_match . '
					FROM ' . $this->wordlist_table . '
					WHERE ' . $this->db->sql_in_set('word_text', $word_ary);
				$this->db->sql_query($sql);

				$sql = 'UPDATE ' . $this->wordlist_table . '
					SET word_count = word_count + 1
					WHERE ' . $this->db->sql_in_set('word_text', $word_ary);
				$this->db->sql_query($sql);
			}
		}
		$this->db->sql_return_on_error(false);

		$this->db->sql_transaction('commit');

		// destroy cached search results containing any of the words removed or added
		$this->destroy_cache(array_unique(array_merge($words['add']['post'], $words['add']['title'], $words['add']['descr'], $words['del']['post'], $words['del']['title'], $words['del']['descr'])), [$poster_id]);

		unset($unique_add_words);
		unset($words);
		unset($cur_words);
	}

	/**
	 * {@inheritdoc}
	 */
	public function index_remove(array $article_ids, array $author_ids): void
	{
		if (count([$article_ids]))
		{
			$sql = 'SELECT w.word_id, w.word_text, m.title_match, m.descr_match
				FROM ' . $this->wordmatch_table . ' m, ' . $this->wordlist_table . ' w
				WHERE ' . $this->db->sql_in_set('m.article_id', $article_ids) . '
					AND w.word_id = m.word_id';
			$result = $this->db->sql_query($sql);

			$message_word_ids = $title_word_ids = $descr_word_ids = $word_texts = [];
			while ($row = $this->db->sql_fetchrow($result))
			{
				if ($row['title_match'])
				{
					$title_word_ids[] = $row['word_id'];
				}
				if ($row['descr_match'])
				{
					$descr_word_ids[] = $row['word_id'];
				}
				else
				{
					$message_word_ids[] = $row['word_id'];
				}
				$word_texts[] = $row['word_text'];
			}
			$this->db->sql_freeresult($result);

			if (count($title_word_ids))
			{
				$sql = 'UPDATE ' . $this->wordlist_table . '
					SET word_count = word_count - 1
					WHERE ' . $this->db->sql_in_set('word_id', $title_word_ids) . '
						AND word_count > 0';
				$this->db->sql_query($sql);
			}

			if (count($descr_word_ids))
			{
				$sql = 'UPDATE ' . $this->wordlist_table . '
					SET word_count = word_count - 1
					WHERE ' . $this->db->sql_in_set('word_id', $descr_word_ids) . '
						AND word_count > 0';
				$this->db->sql_query($sql);
			}

			if (count($message_word_ids))
			{
				$sql = 'UPDATE ' . $this->wordlist_table . '
					SET word_count = word_count - 1
					WHERE ' . $this->db->sql_in_set('word_id', $message_word_ids) . '
						AND word_count > 0';
				$this->db->sql_query($sql);
			}

			unset($title_word_ids);
			unset($descr_word_ids);
			unset($message_word_ids);

			$sql = 'DELETE FROM ' . $this->wordmatch_table . '
				WHERE ' . $this->db->sql_in_set('article_id', $article_ids);
			$this->db->sql_query($sql);
		}

		$this->destroy_cache(array_unique($word_texts), array_unique($author_ids));
	}

	/**
	 * {@inheritdoc}
	 */
	public function tidy(): void
	{
		// Is the fulltext indexer disabled? If yes then we need not
		// carry on ... it's okay ... I know when I'm not wanted boo hoo
		if (!$this->config['kb_search'])
		{
			$this->config->set('search_last_gc', time(), false);
			return;
		}

		$destroy_cache_words = [];

		// Remove common words
		if ($this->config['kb_num_articles'] >= 100 && $this->config['fulltext_native_common_thres'])
		{
			$common_threshold = ((float) $this->config['fulltext_native_common_thres']) / 100.0;
			// First, get the IDs of common words
			$sql = 'SELECT word_id, word_text
				FROM ' . $this->wordlist_table . '
				WHERE word_count > ' . floor($this->config['kb_num_articles'] * $common_threshold) . '
					OR word_common = 1';
			$result = $this->db->sql_query($sql);

			$sql_in = [];
			while ($row = $this->db->sql_fetchrow($result))
			{
				$sql_in[] = $row['word_id'];
				$destroy_cache_words[] = $row['word_text'];
			}
			$this->db->sql_freeresult($result);

			if (count($sql_in))
			{
				// Flag the words
				$sql = 'UPDATE ' . $this->wordlist_table . '
					SET word_common = 1
					WHERE ' . $this->db->sql_in_set('word_id', $sql_in);
				$this->db->sql_query($sql);

				// by setting search_last_gc to the new time here we make sure that if a user reloads because the
				// following query takes too long, he won't run into it again
				$this->config->set('search_last_gc', time(), false);

				// Delete the matches
				$sql = 'DELETE FROM ' . $this->wordmatch_table . '
					WHERE ' . $this->db->sql_in_set('word_id', $sql_in);
				$this->db->sql_query($sql);
			}
			unset($sql_in);
		}

		if (count($destroy_cache_words))
		{
			// destroy cached search results containing any of the words that are now common or were removed
			$this->destroy_cache(array_unique($destroy_cache_words));
		}

		$this->config->set('search_last_gc', time(), false);
	}

	// create_index is inherited from base.php

	/**
	 * {@inheritdoc}
	 */
	public function delete_index(int|null &$post_counter = null): array|null
	{
		$sql_queries = [];

		switch ($this->db->get_sql_layer())
		{
			case 'sqlite3':
				$this->db->sql_query('DELETE FROM ' . $this->wordlist_table);
				$this->db->sql_query('DELETE FROM ' . $this->wordmatch_table);
				$this->db->sql_query('DELETE FROM ' . $this->search_results_table);
			break;

			default:
				$this->db->sql_query('TRUNCATE TABLE ' . $this->wordlist_table);
				$this->db->sql_query('TRUNCATE TABLE ' . $this->wordmatch_table);
				$this->db->sql_query('TRUNCATE TABLE ' . $this->search_results_table);
			break;
		}

		foreach ($sql_queries as $sql_query)
		{
			$this->db->sql_query($sql_query);
		}

		return null;
	}

	/**
	 * {@inheritdoc}
	 */
	public function index_created(): bool
	{
		if (!count($this->stats))
		{
			$this->get_stats();
		}

		return $this->stats['total_words'] && $this->stats['total_matches'];
	}

	/**
	 * {@inheritdoc}
	 */
	public function index_stats()
	{
		if (!count($this->stats))
		{
			$this->get_stats();
		}

		return [
			$this->language->lang('TOTAL_WORDS')   => $this->stats['total_words'],
			$this->language->lang('TOTAL_MATCHES') => $this->stats['total_matches']];
	}

	/**
	 * Computes the stats and store them in the $this->stats associative array
	 */
	protected function get_stats()
	{
		$this->stats['total_words'] = $this->db->get_estimated_row_count($this->wordlist_table);
		$this->stats['total_matches'] = $this->db->get_estimated_row_count($this->wordmatch_table);
	}

	/**
	 * Split a text into words of a given length
	 *
	 * The text is converted to UTF-8, cleaned up, and split. Then, words that
	 * conform to the defined length range are returned in an array.
	 *
	 * NOTE: duplicates are NOT removed from the return array
	 *
	 * @param string $text Text to split, encoded in UTF-8
	 * @return    array            Array of UTF-8 words
	 */
	public function split_message($text)
	{
		$match = $words = [];

		/**
		 * Taken from the original code
		 */
		// Do not index code
		$match[] = '#\[code(?:=.*?)?(\:?[0-9a-z]{5,})\].*?\[\/code(\:?[0-9a-z]{5,})\]#is';
		// BBcode
		$match[] = '#\[\/?[a-z0-9\*\+\-]+(?:=.*?)?(?::[a-z])?(\:?[0-9a-z]{5,})\]#';

		$min = $this->word_length['min'];

		$isset_min = $min - 1;

		/**
		 * Clean up the string, remove HTML tags, remove BBCodes
		 */
		$word = strtok($this->cleanup(preg_replace($match, ' ', strip_tags($text)), -1), ' ');

		while (strlen($word))
		{
			if (strlen($word) > 255 || strlen($word) <= $isset_min)
			{
				/**
				 * Words longer than 255 bytes are ignored. This will have to be
				 * changed whenever we change the length of search_wordlist.word_text
				 *
				 * Words shorter than $isset_min bytes are ignored, too
				 */
				$word = strtok(' ');
				continue;
			}

			$len = utf8_strlen($word);

			/**
			 * Test whether the word is too short to be indexed.
			 *
			 * Note that this limit does NOT apply to CJK and Hangul
			 */
			if ($len < $min)
			{
				/**
				 * Note: this could be optimized. If the codepoint is lower than Hangul's range
				 * we know that it will also be lower than CJK ranges
				 */
				if ((strncmp($word, self::UTF8_HANGUL_FIRST, 3) < 0 || strncmp($word, self::UTF8_HANGUL_LAST, 3) > 0)
					&& (strncmp($word, self::UTF8_CJK_FIRST, 3) < 0 || strncmp($word, self::UTF8_CJK_LAST, 3) > 0)
					&& (strncmp($word, self::UTF8_CJK_B_FIRST, 4) < 0 || strncmp($word, self::UTF8_CJK_B_LAST, 4) > 0))
				{
					$word = strtok(' ');
					continue;
				}
			}

			$words[] = $word;
			$word = strtok(' ');
		}

		return $words;
	}

	/**
	 * Clean up a text to remove non-alphanumeric characters
	 *
	 * This method receives a UTF-8 string, normalizes and validates it, replaces all
	 * non-alphanumeric characters with strings then returns the result.
	 *
	 * Any number of "allowed chars" can be passed as a UTF-8 string in NFC.
	 *
	 * @param string $text          Text to split, in UTF-8 (not normalized or sanitized)
	 * @param string $allowed_chars String of special chars to allow
	 * @param string $encoding      Text encoding
	 * @return    string                    Cleaned up text, only alphanumeric chars are left
	 */
	protected function cleanup($text, $allowed_chars = null, $encoding = 'utf-8')
	{
		static $conv = [], $conv_loaded = [];
		$allow = [];

		// Convert the text to UTF-8
		$encoding = strtolower($encoding);
		if ($encoding != 'utf-8')
		{
			$text = utf8_recode($text, $encoding);
		}

		$utf_len_mask = [
			"\xC0" => 2,
			"\xD0" => 2,
			"\xE0" => 3,
			"\xF0" => 4,
		];

		/**
		 * Replace HTML entities and NCRs
		 */
		$text = htmlspecialchars_decode(utf8_decode_ncr($text), ENT_QUOTES);

		/**
		 * Normalize to NFC
		 */
		$text = \Normalizer::normalize($text);

		/**
		 * The first thing we do is:
		 *
		 * - convert ASCII-7 letters to lowercase
		 * - remove the ASCII-7 non-alpha characters
		 * - remove the bytes that should not appear in a valid UTF-8 string: 0xC0,
		 *   0xC1 and 0xF5-0xFF
		 *
		 * @todo in theory, the third one is already taken care of during normalization and those chars should have been replaced by Unicode replacement chars
		 */
		$sb_match = "ISTCPAMELRDOJBNHFGVWUQKYXZ\r\n\t!\"#$%&'()*+,-./:;<=>?@[\\]^_`{|}~\x00\x01\x02\x03\x04\x05\x06\x07\x08\x0B\x0C\x0E\x0F\x10\x11\x12\x13\x14\x15\x16\x17\x18\x19\x1A\x1B\x1C\x1D\x1E\x1F\xC0\xC1\xF5\xF6\xF7\xF8\xF9\xFA\xFB\xFC\xFD\xFE\xFF";
		$sb_replace = 'istcpamelrdojbnhfgvwuqkyxz                                                                              ';

		/**
		 * This is the list of legal ASCII chars, it is automatically extended
		 * with ASCII chars from $allowed_chars
		 */
		$legal_ascii = ' eaisntroludcpmghbfvq10xy2j9kw354867z';

		/**
		 * Prepare an array containing the extra chars to allow
		 */
		if (isset($allowed_chars[0]))
		{
			$pos = 0;
			$len = strlen($allowed_chars);
			do
			{
				$c = $allowed_chars[$pos];

				if ($c < "\x80")
				{
					/**
					 * ASCII char
					 */
					$sb_pos = strpos($sb_match, $c);
					if (is_int($sb_pos))
					{
						/**
						 * Remove the char from $sb_match and its corresponding
						 * replacement in $sb_replace
						 */
						$sb_match = substr($sb_match, 0, $sb_pos) . substr($sb_match, $sb_pos + 1);
						$sb_replace = substr($sb_replace, 0, $sb_pos) . substr($sb_replace, $sb_pos + 1);
						$legal_ascii .= $c;
					}

					$pos++;
				}
				else
				{
					/**
					 * UTF-8 char
					 */
					$utf_len = $utf_len_mask[$c & "\xF0"];
					$allow[substr($allowed_chars, $pos, $utf_len)] = 1;
					$pos += $utf_len;
				}
			} while ($pos < $len);
		}

		$text = strtr($text, $sb_match, $sb_replace);
		$ret = '';

		$pos = 0;
		$len = strlen($text);

		do
		{
			/**
			 * Do all consecutive ASCII chars at once
			 */
			if ($spn = strspn($text, $legal_ascii, $pos))
			{
				$ret .= substr($text, $pos, $spn);
				$pos += $spn;
			}

			if ($pos >= $len)
			{
				return $ret;
			}

			/**
			 * Capture the UTF char
			 */
			$utf_len = $utf_len_mask[$text[$pos] & "\xF0"];
			$utf_char = substr($text, $pos, $utf_len);
			$pos += $utf_len;

			if (($utf_char >= self::UTF8_HANGUL_FIRST && $utf_char <= self::UTF8_HANGUL_LAST)
				|| ($utf_char >= self::UTF8_CJK_FIRST && $utf_char <= self::UTF8_CJK_LAST)
				|| ($utf_char >= self::UTF8_CJK_B_FIRST && $utf_char <= self::UTF8_CJK_B_LAST))
			{
				/**
				 * All characters within these ranges are valid
				 *
				 * We separate them with a space in order to index each character
				 * individually
				 */
				$ret .= ' ' . $utf_char . ' ';
				continue;
			}

			if (isset($allow[$utf_char]))
			{
				/**
				 * The char is explicitly allowed
				 */
				$ret .= $utf_char;
				continue;
			}

			if (isset($conv[$utf_char]))
			{
				/**
				 * The char is mapped to something, maybe to itself actually
				 */
				$ret .= $conv[$utf_char];
				continue;
			}

			/**
			 * The char isn't mapped, but did we load its conversion table?
			 *
			 * The search indexer table is split into blocks. The block number of
			 * each char is equal to its codepoint right-shifted for 11 bits. It
			 * means that out of the 11, 16 or 21 meaningful bits of a 2-, 3- or
			 * 4- byte sequence we only keep the leftmost 0, 5 or 10 bits. Thus,
			 * all UTF chars encoded in 2 bytes are in the same first block.
			 */
			if (isset($utf_char[2]))
			{
				if (isset($utf_char[3]))
				{
					/**
					 * 1111 0nnn 10nn nnnn 10nx xxxx 10xx xxxx
					 * 0000 0111 0011 1111 0010 0000
					 */
					$idx = ((ord($utf_char[0]) & 0x07) << 7) | ((ord($utf_char[1]) & 0x3F) << 1) | ((ord($utf_char[2]) & 0x20) >> 5);
				}
				else
				{
					/**
					 * 1110 nnnn 10nx xxxx 10xx xxxx
					 * 0000 0111 0010 0000
					 */
					$idx = ((ord($utf_char[0]) & 0x07) << 1) | ((ord($utf_char[1]) & 0x20) >> 5);
				}
			}
			else
			{
				/**
				 * 110x xxxx 10xx xxxx
				 * 0000 0000 0000 0000
				 */
				$idx = 0;
			}

			/**
			 * Check if the required conv table has been loaded already
			 */
			if (!isset($conv_loaded[$idx]))
			{
				$conv_loaded[$idx] = 1;
				$file = $this->phpbb_root_path . 'includes/utf/data/search_indexer_' . $idx . '.' . $this->php_ext;

				if (file_exists($file))
				{
					$conv += include($file);
				}
			}

			if (isset($conv[$utf_char]))
			{
				$ret .= $conv[$utf_char];
			}
			else
			{
				/**
				 * We add an entry to the conversion table so that we
				 * don't have to convert to codepoint and perform the checks
				 * that are above this block
				 */
				$conv[$utf_char] = ' ';
				$ret .= ' ';
			}
		} while (1);

		return $ret;
	}
}
