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
use RuntimeException;

/**
 * Fulltext search for PostgreSQL
 */
class kb_fulltext_postgres extends kb_base implements kb_search_backend_interface
{
	/**
	 * Associative array holding index stats
	 *
	 * @var array
	 */
	protected $stats = [];

	/**
	 * Holds the words entered by user, obtained by splitting the entered query on whitespace
	 *
	 * @var array
	 */
	protected $split_words = [];

	/**
	 * Stores the tsearch query
	 *
	 * @var string
	 */
	protected $tsearch_query = '';

	/**
	 * True if phrase search is supported.
	 * PostgreSQL fulltext currently doesn't support it
	 *
	 * @var boolean
	 */
	protected $phrase_search = false;

	/**
	 * @var language
	 */
	protected $language;
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
	 * Associative array stores the min and max word length to be searched
	 *
	 * @var array
	 */
	protected $word_length = [];

	/** @var string */
	protected $articles_table;

	/** @var string */
	protected string $search_results_table;

	/**
	 * Constructor
	 * Creates a new \phpbb\search\backend\fulltext_postgres, which is used as a search backend
	 *
	 * @param config           $config               Config object
	 * @param driver_interface $db                   Database object
	 * @param language         $language
	 * @param user             $user                 User object
	 * @param string           $articles_table       Articles_table
	 * @param string           $search_results_table Search_results_table
	 * @param string           $phpbb_root_path      Relative path to phpBB root
	 * @param string           $phpEx                PHP file extension
	 */
	public function __construct(
		config $config,
		driver_interface $db,
		language $language,
		user $user,
		string $articles_table,
		string $search_results_table,
		string $phpbb_root_path,
		string $phpEx
	)
	{
		global $cache;

		parent::__construct($cache, $config, $db, $user, $search_results_table);
		$this->language = $language;

		$this->word_length = ['min' => $this->config['fulltext_postgres_min_word_len'], 'max' => $this->config['fulltext_postgres_max_word_len']];

		$this->articles_table = $articles_table;
		$this->search_results_table = $search_results_table;

		/**
		 * Load the UTF tools
		 */
		if (!function_exists('utf8_strlen'))
		{
			include($phpbb_root_path . 'includes/utf/utf_tools.' . $phpEx);
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_name(): string
	{
		return 'Knowledge Base PostgreSQL Fulltext (test version)';
	}

	/**
	 * {@inheritdoc}
	 */
	public function is_available(): bool
	{
		return $this->db->get_sql_layer() == 'postgres';
	}

	/**
	 * {@inheritdoc}
	 */
	public function init()
	{
		if (!$this->is_available())
		{
			return $this->language->lang('FULLTEXT_POSTGRES_INCOMPATIBLE_DATABASE');
		}

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
		if ($terms == 'all')
		{
			$match = ['#\sand\s#iu', '#\sor\s#iu', '#\snot\s#iu', '#(^|\s)\+#', '#(^|\s)-#', '#(^|\s)\|#'];
			$replace = [' +', ' |', ' -', ' +', ' -', ' |'];

			$keywords = preg_replace($match, $replace, $keywords);
		}

		// Filter out as above
		$split_keywords = preg_replace("#[\"\n\r\t]+#", ' ', trim(htmlspecialchars_decode($keywords, ENT_COMPAT)));

		// Split words
		$split_keywords = preg_replace('#([^\p{L}\p{N}\'*"()])#u', '$1$1', str_replace('\'\'', '\' \'', trim($split_keywords)));
		$matches = [];
		preg_match_all('#(?:[^\p{L}\p{N}*"()]|^)([+\-|]?(?:[\p{L}\p{N}*"()]+\'?)*[\p{L}\p{N}*"()])(?:[^\p{L}\p{N}*"()]|$)#u', $split_keywords, $matches);
		$this->split_words = $matches[1];

		foreach ($this->split_words as $i => $word)
		{
			$clean_word = preg_replace('#^[+\-|"]#', '', $word);

			// check word length
			$clean_len = utf8_strlen(str_replace('*', '', $clean_word));
			if (($clean_len < $this->config['fulltext_postgres_min_word_len']) || ($clean_len > $this->config['fulltext_postgres_max_word_len']))
			{
				$this->common_words[] = $word;
				unset($this->split_words[$i]);
			}
		}

		$this->search_query = '';
		$this->tsearch_query = '';
		if ($terms == 'any')
		{
			foreach ($this->split_words as $word)
			{
				if ((strpos($word, '+') === 0) || (strpos($word, '-') === 0) || (strpos($word, '|') === 0))
				{
					$word = substr($word, 1);
				}
				$this->search_query .= $word . ' ';
				$this->tsearch_query .= '|' . $word . ' ';
			}
		}
		else
		{
			foreach ($this->split_words as $word)
			{
				if (strpos($word, '+') === 0)
				{
					$this->search_query .= $word . ' ';
					$this->tsearch_query .= '&' . substr($word, 1) . ' ';
				}
				else if (strpos($word, '-') === 0)
				{
					$this->search_query .= $word . ' ';
					$this->tsearch_query .= '&!' . substr($word, 1) . ' ';
				}
				else if (strpos($word, '|') === 0)
				{
					$this->search_query .= $word . ' ';
					$this->tsearch_query .= '|' . substr($word, 1) . ' ';
				}
				else
				{
					$this->search_query .= '+' . $word . ' ';
					$this->tsearch_query .= '&' . $word . ' ';
				}
			}
		}

		$this->tsearch_query = substr($this->tsearch_query, 1);
		$this->search_query = utf8_htmlspecialchars($this->search_query);

		if ($this->search_query)
		{
			$this->split_words = array_values($this->split_words);
			sort($this->split_words);
			return true;
		}
		return false;
	}


	/**
	 * {@inheritdoc}
	 */
	public function keyword_search(string $type, string $fields, string $terms, array $sort_by_sql, string $sort_key, string $sort_dir, string $sort_days, array $ex_fid_ary, int $category_id, array $author_ary, string $author_name, array &$id_ary, int &$start, int $per_page)
	{
		// No keywords? No posts
		// When search query contains queries like -foo
		if (!$this->search_query || strpos($this->search_query, '+') === false)
		{
			return false;
		}

		// generate a search_key from all the options to identify the results
		$search_key_array = [
			implode(', ', $this->split_words),
			$type,
			$fields,
			$terms,
			$sort_days,
			$sort_key,
			$category_id,
			implode(',', $ex_fid_ary),
			true,
			implode(',', $author_ary),
		];
		$search_key = md5(implode('#', $search_key_array));

		if ($start < 0)
		{
			$start = 0;
		}

		// try reading the results from cache
		$result_count = 0;
		if ($this->obtain_ids($search_key, $result_count, $id_ary, $start, $per_page, $sort_dir) == self::KB_SEARCH_RESULT_IN_CACHE)
		{
			return $result_count;
		}

		$id_ary = [];

		// Build sql strings for sorting
		$sql_sort = $sort_by_sql[$sort_key] . (($sort_dir == 'a') ? ' ASC' : ' DESC');

		// Build some display specific sql strings
		switch ($fields)
		{
			case 'titleonly':
				$sql_match = 'p.article_title';
			break;

			case 'descronly':
				$sql_match = 'p.article_description';
			break;

			case 'msgonly':
				$sql_match = 'p.article_body';
			break;

			default:
				$sql_match = 'p.article_title, p.article_body, p.article_description';
			break;
		}

		$tsearch_query = $this->tsearch_query;

		$sql_select = 'p.article_id';
		$field = 'article_id';

		if (count($author_ary) && $author_name)
		{
			// first one matches post of registered users, second one guests and deleted users
			$sql_author = ' AND (' . $this->db->sql_in_set('p.author_id', array_diff($author_ary, [ANONYMOUS]), false, true) . ' OR p.author = ' . $author_name . ')';
		}
		else if (count($author_ary))
		{
			$sql_author = ' AND ' . $this->db->sql_in_set('p.author_id', $author_ary);
		}
		else
		{
			$sql_author = '';
		}

		$sql_where_options = ($category_id) ? ' AND p.article_category_id = ' . $category_id : '';
		$sql_where_options .= (count($ex_fid_ary)) ? ' AND ' . $this->db->sql_in_set('p.article_category_id', $ex_fid_ary, true) : '';
		$sql_where_options .= $sql_author;
		$sql_where_options .= ($sort_days) ? ' AND p.article_date >= ' . (time() - ($sort_days * 86400)) : '';
		$sql_where_options .= ' AND p.approved = 1 ';

		$sql_match = str_replace(',', " || ' ' ||", $sql_match);
		$tmp_sql_match = "to_tsvector ('" . $this->db->sql_escape($this->config['fulltext_postgres_ts_name']) . "', " . $sql_match . ") @@ to_tsquery ('" . $this->db->sql_escape($this->config['fulltext_postgres_ts_name']) . "', '" . $this->db->sql_escape($this->tsearch_query) . "')";

		$this->db->sql_transaction('begin');

		$sql_from = 'FROM ' . $this->articles_table . ' p';
		$sql_where = 'WHERE (' . $tmp_sql_match . ")
			$sql_where_options";
		$sql = "SELECT $sql_select
			$sql_from
			$sql_where
			ORDER BY $sql_sort";
		$result = $this->db->sql_query_limit($sql, $this->config['search_block_size'], $start);

		while ($row = $this->db->sql_fetchrow($result))
		{
			$id_ary[] = $row[$field];
		}
		$this->db->sql_freeresult($result);

		$id_ary = array_unique($id_ary);

		// if the total result count is not cached yet, retrieve it from the db
		if (!$result_count)
		{
			$sql_count = "SELECT COUNT(*) as result_count
				$sql_from
				$sql_where";
			$result = $this->db->sql_query($sql_count);
			$result_count = (int) $this->db->sql_fetchfield('result_count');
			$this->db->sql_freeresult($result);

			if (!$result_count)
			{
				return false;
			}
		}

		$this->db->sql_transaction('commit');

		if ($start >= $result_count)
		{
			$start = floor(($result_count - 1) / $per_page) * $per_page;

			$result = $this->db->sql_query_limit($sql, $this->config['search_block_size'], $start);

			while ($row = $this->db->sql_fetchrow($result))
			{
				$id_ary[] = $row[$field];
			}
			$this->db->sql_freeresult($result);

			$id_ary = array_unique($id_ary);
		}

		// store the ids, from start on then delete anything that isn't on the current page because we only need ids for one page
		$this->save_ids($search_key, implode(' ', $this->split_words), $author_ary, $result_count, $id_ary, $start, $sort_dir);
		$id_ary = array_slice($id_ary, 0, $per_page);

		return $result_count;
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
			true,
			implode(',', $author_ary),
			$author_name,
		];
		$search_key = md5(implode('#', $search_key_array));

		if ($start < 0)
		{
			$start = 0;
		}

		// try reading the results from cache
		$result_count = 0;
		if ($this->obtain_ids($search_key, $result_count, $id_ary, $start, $per_page, $sort_dir) == self::KB_SEARCH_RESULT_IN_CACHE)
		{
			return $result_count;
		}

		$id_ary = [];

		// Create some display specific sql strings
		$sql_author = $this->db->sql_in_set('p.author_id', $author_ary) . ' AND p.approved=1 ';

		$sql_fora = (count($ex_fid_ary)) ? ' AND ' . $this->db->sql_in_set('p.article_category_id', $ex_fid_ary, true) : '';
		$sql_category_id = ($category_id) ? ' AND p.article_category_id = ' . $category_id : '';
		$sql_time = ($sort_days) ? ' AND p.article_date >= ' . (time() - ($sort_days * 86400)) : '';

		// Build sql strings for sorting
		$sql_sort = $sort_by_sql[$sort_key] . (($sort_dir == 'a') ? ' ASC' : ' DESC');

		// Build the query for really selecting the post_ids
		$sql = 'SELECT p.article_id
			FROM ' . $this->articles_table . ' p' . "
			WHERE $sql_author
				$sql_category_id
				$sql_fora
				$sql_time
			ORDER BY $sql_sort";
		$field = 'article_id';

		$this->db->sql_transaction('begin');

		// Only read one block of posts from the db and then cache it
		$result = $this->db->sql_query_limit($sql, $this->config['search_block_size'], $start);

		while ($row = $this->db->sql_fetchrow($result))
		{
			$id_ary[] = $row[$field];
		}
		$this->db->sql_freeresult($result);

		// retrieve the total result count if needed
		if (!$result_count)
		{
			$sql_count = 'SELECT COUNT(*) as result_count
				FROM ' . $this->articles_table . " p
				WHERE $sql_author
					$sql_category_id
					$sql_fora
					$sql_time";

			$result = $this->db->sql_query($sql_count);
			$result_count = (int) $this->db->sql_fetchfield('result_count');
			$this->db->sql_freeresult($result);

			if (!$result_count)
			{
				return false;
			}
		}

		$this->db->sql_transaction('commit');

		if ($start >= $result_count)
		{
			$start = floor(($result_count - 1) / $per_page) * $per_page;

			$result = $this->db->sql_query_limit($sql, $this->config['search_block_size'], $start);
			while ($row = $this->db->sql_fetchrow($result))
			{
				$id_ary[] = (int) $row[$field];
			}
			$this->db->sql_freeresult($result);

			$id_ary = array_unique($id_ary);
		}

		if (count($id_ary))
		{
			$this->save_ids($search_key, '', $author_ary, $result_count, $id_ary, $start, $sort_dir);
			$id_ary = array_slice($id_ary, 0, $per_page);

			return $result_count;
		}
		return false;
	}

	/**
	 * {@inheritdoc}
	 */
	public function supports_phrase_search(): bool
	{
		return $this->phrase_search;
	}

	/**
	 * {@inheritdoc}
	 */
	public function index(string $mode, int $article_id, string &$message, string &$subject, string &$description, int $poster_id)
	{
		// Split old and new post/subject to obtain array of words
		$split_text = $this->split_message($message);
		$split_title = ($subject) ? $this->split_message($subject) : [];
		$split_descr = ($description) ? $this->split_message($description) : [];

		$words = array_unique(array_merge($split_text, $split_title, $split_descr));

		unset($split_text, $split_title, $split_descr);

		// destroy cached search results containing any of the words removed or added
		$this->destroy_cache($words, [$poster_id]);

		unset($words);
	}

	/**
	 * {@inheritdoc}
	 */
	public function index_remove(array $article_ids, array $author_ids): void
	{
		$this->destroy_cache([], $author_ids);
	}

	/**
	 * {@inheritdoc}
	 */
	public function tidy(): void
	{
		// destroy too old cached search results
		$this->destroy_cache([]);

		$this->config->set('search_last_gc', time(), false);
	}

	/**
	 * {@inheritdoc}
	 */
	public function create_index(int &$post_counter = 0): ?array
	{
		// Make sure we can actually use PostgreSQL with fulltext indexes
		if ($error = $this->init())
		{
			throw new RuntimeException($error);
		}

		if (empty($this->stats))
		{
			$this->get_stats();
		}

		$sql_queries = [];

		if (!isset($this->stats['article_title']))
		{
			$sql_queries[] = 'CREATE INDEX ' . $this->articles_table . '_' . $this->config['fulltext_postgres_ts_name'] . '_post_subject ON ' . POSTS_TABLE . " USING gin (to_tsvector ('" . $this->db->sql_escape($this->config['fulltext_postgres_ts_name']) . "', article_title))";
		}

		if (!isset($this->stats['article_description']))
		{
			$sql_queries[] = 'CREATE INDEX ' . $this->articles_table . '_' . $this->config['fulltext_postgres_ts_name'] . '_post_content ON ' . POSTS_TABLE . " USING gin (to_tsvector ('" . $this->db->sql_escape($this->config['fulltext_postgres_ts_name']) . "', article_description))";
		}

		if (!isset($this->stats['article_body']))
		{
			$sql_queries[] = 'CREATE INDEX ' . $this->articles_table . '_' . $this->config['fulltext_postgres_ts_name'] . '_post_content ON ' . POSTS_TABLE . " USING gin (to_tsvector ('" . $this->db->sql_escape($this->config['fulltext_postgres_ts_name']) . "', article_body))";
		}

		if (!isset($this->stats['article_content']))
		{
			$sql_queries[] = 'CREATE INDEX ' . $this->articles_table . '_' . $this->config['fulltext_postgres_ts_name'] . '_post_subject_content ON ' . POSTS_TABLE . " USING gin (to_tsvector ('" . $this->db->sql_escape($this->config['fulltext_postgres_ts_name']) . "', article_content ))";
		}

		$stats = $this->stats;

		foreach ($sql_queries as $sql_query)
		{
			$this->db->sql_query($sql_query);
		}

		$this->db->sql_query('TRUNCATE TABLE ' . $this->search_results_table);

		return null;
	}

	/**
	 * {@inheritdoc}
	 */
	public function delete_index(?int &$post_counter = null): ?array
	{
		// Make sure we can actually use PostgreSQL with fulltext indexes
		if ($error = $this->init())
		{
			throw new RuntimeException($error);
		}

		if (empty($this->stats))
		{
			$this->get_stats();
		}

		$sql_queries = [];

		if (isset($this->stats['article_title']))
		{
			$sql_queries[] = 'DROP INDEX ' . $this->stats['article_title']['relname'];
		}

		if (isset($this->stats['article_description']))
		{
			$sql_queries[] = 'DROP INDEX ' . $this->stats['article_description']['relname'];
		}

		if (isset($this->stats['article_body']))
		{
			$sql_queries[] = 'DROP INDEX ' . $this->stats['article_body']['relname'];
		}

		if (isset($this->stats['article_content']))
		{
			$sql_queries[] = 'DROP INDEX ' . $this->stats['article_content']['relname'];
		}

		$stats = $this->stats;

		foreach ($sql_queries as $sql_query)
		{
			$this->db->sql_query($sql_query);
		}

		$this->db->sql_query('TRUNCATE TABLE ' . SEARCH_RESULTS_TABLE);

		return null;
	}

	/**
	 * {@inheritdoc}
	 */
	public function index_created(): bool
	{
		if (empty($this->stats))
		{
			$this->get_stats();
		}

		return isset($this->stats['article_title']) && isset($this->stats['article_content']) && isset($this->stats['article_description']) && isset($this->stats['article_body']);
	}

	/**
	 * {@inheritdoc}
	 */
	public function index_stats()
	{
		if (empty($this->stats))
		{
			$this->get_stats();
		}

		return [
			$this->language->lang('FULLTEXT_POSTGRES_TOTAL_POSTS') => ($this->index_created()) ? $this->stats['total_posts'] : 0,
		];
	}

	protected function get_stats()
	{
		if ($this->db->get_sql_layer() != 'postgres')
		{
			$this->stats = [];
			return;
		}

		$sql = "SELECT c2.relname, pg_catalog.pg_get_indexdef(i.indexrelid, 0, true) AS indexdef
			  FROM pg_catalog.pg_class c1, pg_catalog.pg_index i, pg_catalog.pg_class c2
			 WHERE c1.relname = '" . $this->articles_table . "'
			   AND pg_catalog.pg_table_is_visible(c1.oid)
			   AND c1.oid = i.indrelid
			   AND i.indexrelid = c2.oid";
		$result = $this->db->sql_query($sql);

		while ($row = $this->db->sql_fetchrow($result))
		{
			// deal with older PostgreSQL versions which didn't use Index_type
			if (strpos($row['indexdef'], 'to_tsvector') !== false)
			{
				if ($row['relname'] == $this->articles_table . '_' . $this->config['fulltext_postgres_ts_name'] . '_article_title' || $row['relname'] == $this->articles_table . '_article_title')
				{
					$this->stats['article_title'] = $row;
				}
				else if ($row['relname'] == $this->articles_table . '_' . $this->config['fulltext_postgres_ts_name'] . '_article_description' || $row['relname'] == $this->articles_table . '_article_description')
				{
					$this->stats['article_description'] = $row;
				}
				else if ($row['relname'] == $this->articles_table . '_' . $this->config['fulltext_postgres_ts_name'] . '_article_body' || $row['relname'] == $this->articles_table . '_article_body')
				{
					$this->stats['article_body'] = $row;
				}
				else if ($row['relname'] == $this->articles_table . '_' . $this->config['fulltext_postgres_ts_name'] . '_article_content' || $row['relname'] == $this->articles_table . '_article_content')
				{
					$this->stats['article_content'] = $row;
				}

			}
		}
		$this->db->sql_freeresult($result);

		$this->stats['total_posts'] = $this->config['num_posts'];
	}

	/**
	 * Turns text into an array of words
	 *
	 * @param string $text contains post text/subject
	 * @return array
	 */
	protected function split_message($text)
	{
		// Split words
		$text = preg_replace('#([^\p{L}\p{N}\'*])#u', '$1$1', str_replace('\'\'', '\' \'', trim($text)));
		$matches = [];
		preg_match_all('#(?:[^\p{L}\p{N}*]|^)([+\-|]?(?:[\p{L}\p{N}*]+\'?)*[\p{L}\p{N}*])(?:[^\p{L}\p{N}*]|$)#u', $text, $matches);
		$text = $matches[1];

		// remove too short or too long words
		$text = array_values($text);
		for ($i = 0, $n = count($text); $i < $n; $i++)
		{
			$text[$i] = trim($text[$i]);
			if (utf8_strlen($text[$i]) < $this->config['fulltext_postgres_min_word_len'] || utf8_strlen($text[$i]) > $this->config['fulltext_postgres_max_word_len'])
			{
				unset($text[$i]);
			}
		}

		return array_values($text);
	}
}
