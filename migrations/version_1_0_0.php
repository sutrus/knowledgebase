<?php

/**
 *
 * Knowledge base. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2017, Sheer
 * @license       GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace sheer\knowledgebase\migrations;

class version_1_0_0 extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return ['\phpbb\db\migration\data\v310\dev'];
	}

	public function effectively_installed()
	{
		return;
	}

	public function update_schema()
	{
		return [
			'add_tables' => [
				$this->table_prefix . 'kb_articles' => [
					'COLUMNS'     => [
						'article_id'          => ['UINT', null, 'auto_increment'],
						'article_category_id' => ['UINT', 0],
						'approved'            => ['BOOL', 0],
						'article_title'       => ['VCHAR:255', ''],
						'article_description' => ['VCHAR:255', ''],
						'article_date'        => ['UINT:11', 0],
						'edit_date'           => ['UINT:11', 0],
						'author_id'           => ['UINT', 0],
						'author'              => ['VCHAR:255', ''],
						'bbcode_uid'          => ['VCHAR:10', ''],
						'bbcode_bitfield'     => ['VCHAR:32', ''],
						'article_body'        => ['MTEXT_UNI', ''],
						'topic_id'            => ['UINT', 0],
						'views'               => ['BINT', 0],
					],
					'PRIMARY_KEY' => 'article_id',
					'KEYS'        => [
						'topic_id'  => ['INDEX', 'topic_id'],
						'author_id' => ['INDEX', 'author_id'],
						'author'    => ['INDEX', 'author'],
					],
				],

				$this->table_prefix . 'kb_config' => [
					'COLUMNS'     => [
						'config_name'  => ['VCHAR:255', ''],
						'config_value' => ['MTEXT_UNI', ''],
					],
					'PRIMARY_KEY' => 'config_name',
				],

				$this->table_prefix . 'kb_categories' => [
					'COLUMNS'     => [
						'category_id'      => ['UINT', null, 'auto_increment'],
						'parent_id'        => ['UINT', 0],
						'left_id'          => ['UINT', 0],
						'right_id'         => ['UINT', 0],
						'category_parents' => ['MTEXT_UNI', ''],
						'category_name'    => ['VCHAR:255', ''],
						'category_details' => ['VCHAR:255', ''],
						'number_articles'  => ['USINT', 0],
					],
					'PRIMARY_KEY' => 'category_id',
					'KEYS'        => [
						'left_id'  => ['INDEX', 'left_id'],
						'right_id' => ['INDEX', 'right_id'],
					],
				],

				$this->table_prefix . 'kb_options' => [
					'COLUMNS'     => [
						'auth_option_id' => ['UINT', null, 'auto_increment'],
						'auth_option'    => ['VCHAR:50', ''],
						'is_global'      => ['BOOL', 0],
						'is_local'       => ['BOOL', 1],
					],
					'PRIMARY_KEY' => 'auth_option_id',
					'KEYS'        => [
						'auth_option' => ['UNIQUE', 'auth_option'],
					],
				],

				$this->table_prefix . 'kb_src_wrdlist' => [
					'COLUMNS'     => [
						'word_id'     => ['UINT', null, 'auto_increment'],
						'word_text'   => ['VCHAR_UNI', ''],
						'word_common' => ['BOOL', 0],
						'word_count'  => ['UINT', 0],
					],
					'PRIMARY_KEY' => 'word_id',
					'KEYS'        => [
						'word_text'  => ['UNIQUE', 'word_text'],
						'word_count' => ['INDEX', 'word_count'],
					],
				],

				$this->table_prefix . 'kb_src_wrdmtch' => [
					'COLUMNS' => [
						'article_id'  => ['UINT', 0],
						'reply_id'    => ['UINT', 0],
						'word_id'     => ['UINT', 0],
						'title_match' => ['BOOL', 0],
					],
					'KEYS'    => [
						'un_mtch'    => ['UNIQUE', ['article_id', 'word_id', 'title_match']],
						'word_id'    => ['INDEX', 'word_id'],
						'article_id' => ['INDEX', 'article_id'],
					],
				],

				$this->table_prefix . 'kb_groups' => [
					'COLUMNS' => [
						'group_id'       => ['UINT', 0],
						'category_id'    => ['UINT', 0],
						'auth_option_id' => ['UINT', 0],
						'auth_setting'   => ['TINT:2', 0],
					],
					'KEYS'    => [
						'group_id'       => ['INDEX', 'group_id'],
						'auth_option_id' => ['INDEX', 'auth_option_id'],
					],
				],

				$this->table_prefix . 'kb_users' => [
					'COLUMNS' => [
						'user_id'        => ['UINT', 0],
						'category_id'    => ['UINT', 0],
						'auth_option_id' => ['UINT', 0],
						'auth_setting'   => ['TINT:2', 0],
					],
					'KEYS'    => [
						'user_id'        => ['INDEX', 'user_id'],
						'auth_option_id' => ['INDEX', 'auth_option_id'],
					],
				],

				$this->table_prefix . 'kb_search_results' => [
					'COLUMNS'     => [
						'search_key'      => ['VCHAR:32', 0],
						'search_time'     => ['UINT:11', 0],
						'search_keywords' => ['MTEXT_UNI', ''],
						'search_authors'  => ['MTEXT_UNI', ''],
					],
					'PRIMARY_KEY' => 'search_key',
				],

				$this->table_prefix . 'kb_log' => [
					'COLUMNS'     => [
						'log_id'        => ['UINT', null, 'auto_increment'],
						'log_type'      => ['TINT:4', 0],
						'user_id'       => ['UINT', 0],
						'forum_id'      => ['UINT', 0],
						'reportee_id'   => ['UINT', 0],
						'topic_id'      => ['UINT', 0],
						'log_ip'        => ['VCHAR:40', ''],
						'log_time'      => ['UINT:11', 0],
						'log_operation' => ['TEXT', ''],
						'log_data'      => ['MTEXT_UNI', ''],
					],
					'PRIMARY_KEY' => 'log_id',
					'KEYS'        => [
						'log_type'    => ['INDEX', 'log_type'],
						'forum_id'    => ['INDEX', 'forum_id'],
						'topic_id'    => ['INDEX', 'topic_id'],
						'reportee_id' => ['INDEX', 'reportee_id'],
						'user_id'     => ['INDEX', 'user_id'],
					],
				],

				$this->table_prefix . 'kb_attachments' => [
					'COLUMNS'     => [
						'attach_id'         => ['UINT', null, 'auto_increment'],
						'article_id'        => ['UINT', 0],
						'poster_id'         => ['UINT', 0],
						'is_orphan'         => ['BOOL', 1],
						'physical_filename' => ['VCHAR:255', ''],
						'real_filename'     => ['VCHAR:255', ''],
						'download_count'    => ['UINT', 0],
						'attach_comment'    => ['MTEXT_UNI', ''],
						'extension'         => ['VCHAR:100', ''],
						'mimetype'          => ['VCHAR:100', ''],
						'filesize'          => ['UINT:20', 0],
						'filetime'          => ['UINT:11', 0],
						'thumbnail'         => ['BOOL', 0],
					],
					'PRIMARY_KEY' => 'attach_id',
					'KEYS'        => [
						'filetime'  => ['INDEX', 'filetime'],
						'poster_id' => ['INDEX', 'poster_id'],
						'is_orphan' => ['INDEX', 'is_orphan'],
					],
				],
			],
		];
	}

	public function revert_schema()
	{
		return [
			'drop_tables' => [
				$this->table_prefix . 'kb_articles',
				$this->table_prefix . 'kb_config',
				$this->table_prefix . 'kb_categories',
				$this->table_prefix . 'kb_options',
				$this->table_prefix . 'kb_src_wrdlist',
				$this->table_prefix . 'kb_src_wrdmtch',
				$this->table_prefix . 'kb_groups',
				$this->table_prefix . 'kb_users',
				$this->table_prefix . 'kb_search_results',
				$this->table_prefix . 'kb_log',
				$this->table_prefix . 'kb_attachments',
			],
		];
	}

	public function update_data()
	{
		return [
			// Current version
			['config.add', ['knowledge_base_version', '1.0.0']],

			// Search in Knowledge Base
			['config.add', ['kb_search', '1']],
			['config.add', ['kb_search_type', 'kb_fulltext_native']],
			['config.add', ['kb_per_page_search', '10']],

			// Add permissions
			['permission.add', ['a_manage_kb', true]],

			// Add permissions sets
			['permission.permission_set', ['ROLE_ADMIN_FULL', 'a_manage_kb', 'role', true]],

			// Update kb_options table
			['custom', [[$this, 'update_kb_options_table']]],
			// Set default config
			['custom', [[$this, 'set_default_config']]],
			// Remove old modules, etc...
			['custom', [[$this, 'remove_knowlege_base_ext']]],

			// ACP
			['module.add', ['acp', 'ACP_CAT_DOT_MODS', 'KNOWLEDGE_BASE']],
			['module.add', ['acp', 'KNOWLEDGE_BASE', [
				'module_basename' => '\sheer\knowledgebase\acp\config_module',
				'module_langname' => 'ACP_KNOWLEDGE_BASE_CONFIGURE',
				'module_mode'     => 'settings',
				'module_auth'     => 'ext_sheer/knowledgebase && acl_a_board && acl_a_manage_kb',
			]]],
			['module.add', ['acp', 'KNOWLEDGE_BASE', [
				'module_basename' => '\sheer\knowledgebase\acp\manage_module',
				'module_langname' => 'ACP_LIBRARY_MANAGE',
				'module_mode'     => 'manage',
				'module_auth'     => 'ext_sheer/knowledgebase && acl_a_board && acl_a_manage_kb',
			]]],

			['module.add', ['acp', 'KNOWLEDGE_BASE', [
				'module_basename' => '\sheer\knowledgebase\acp\articles_module',
				'module_langname' => 'ACP_LIBRARY_ARTICLES',
				'module_mode'     => 'articles',
				'module_auth'     => 'ext_sheer/knowledgebase && acl_a_board && acl_a_manage_kb',
			]]],

			['module.add', ['acp', 'KNOWLEDGE_BASE', [
				'module_basename' => '\sheer\knowledgebase\acp\permissions_module',
				'module_langname' => 'ACP_LIBRARY_PERMISSIONS',
				'module_mode'     => 'permissions',
				'module_auth'     => 'ext_sheer/knowledgebase && acl_a_board && acl_a_manage_kb',
			]]],

			['module.add', ['acp', 'KNOWLEDGE_BASE', [
				'module_basename' => '\sheer\knowledgebase\acp\search_module',
				'module_langname' => 'ACP_LIBRARY_SEARCH',
				'module_mode'     => 'index',
				'module_auth'     => 'ext_sheer/knowledgebase && acl_a_board && acl_a_manage_kb',
			]]],

			['module.add', ['acp', 'KNOWLEDGE_BASE', [
				'module_basename' => '\sheer\knowledgebase\acp\attachments_module',
				'module_langname' => 'ACP_LIBRARY_ATTACHMENTS',
				'module_mode'     => 'attachments',
				'module_auth'     => 'ext_sheer/knowledgebase && acl_a_board && acl_a_manage_kb',
			]]],

			['module.add', ['acp', 'KNOWLEDGE_BASE', [
				'module_basename' => '\sheer\knowledgebase\acp\attachments_module',
				'module_langname' => 'ACP_LIBRARY_ATTACHMENTS_ORPHAN',
				'module_mode'     => 'orphan',
				'module_auth'     => 'ext_sheer/knowledgebase && acl_a_board && acl_a_manage_kb',
			]]],

			['module.add', ['acp', 'KNOWLEDGE_BASE', [
				'module_basename' => '\sheer\knowledgebase\acp\logs_module',
				'module_langname' => 'ACP_LIBRARY_LOGS',
				'module_mode'     => 'logs',
				'module_auth'     => 'ext_sheer/knowledgebase && acl_a_board && acl_a_manage_kb',
			]]],
		];
	}

	public function remove_knowlege_base_ext()
	{
		$sql = 'DELETE FROM ' . $this->table_prefix . 'ext WHERE ext_name LIKE \'Sheer/knowlegebase\'';
		$this->db->sql_query($sql);

		$sql = 'DELETE FROM ' . $this->table_prefix . 'config WHERE config_name LIKE \'knowlege_base_version\'';
		$this->db->sql_query($sql);

		$sql = 'DELETE FROM ' . $this->table_prefix . 'modules
			WHERE module_langname IN (
				"KNOWLEGE_BASE", "ACP_KNOWLEGE_BASE_CONFIGURE", "ACP_LIBRARY_MANAGE", "ACP_LIBRARY_ARTICLES",
				"ACP_LIBRARY_PERMISSIONS", "ACP_LIBRARY_SEARCH", "ACP_LIBRARY_LOGS"
			)';
		$this->db->sql_query($sql);
	}

	public function set_default_config()
	{
		$sql = 'SELECT e.extension, g.group_name
			FROM ' . $this->table_prefix . 'extensions e, ' . $this->table_prefix . 'extension_groups g
			WHERE e.group_id = g.group_id
			AND g.allow_group = 1';
		$this->db->sql_query($sql);
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$extensions[] = $row;
		}
		$this->db->sql_freeresult($result);

		foreach ($extensions as $extension)
		{
			$names[] = $extension['group_name'];
		}
		$names = array_values(array_unique($names));

		foreach ($extensions as $extension)
		{
			foreach ($names as $name)
			{
				foreach ($extension as $ext)
				{
					if ($extension['group_name'] == $name)
					{
						$xt[$name][] = $extension['extension'];
					}
				}
			}
		}

		$extensions = serialize($xt);

		$sql = 'SELECT config_value
			FROM ' . $this->table_prefix . 'config' . '
			WHERE config_name LIKE \'max_filesize\'';
		$result = $this->db->sql_query($sql);
		$max_filesize = $this->db->sql_fetchfield('config_value');
		$this->db->sql_freeresult($result);

		$sql = 'SELECT * FROM ' . $this->table_prefix . 'kb_config
			WHERE config_name IN(\'anounce\', \'articles_per_page\', \'forum_id\')';
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);
		if (empty($row))
		{
			$sql = 'INSERT INTO ' . $this->table_prefix . 'kb_config' . ' (config_name, config_value) VALUES
				(\'allow_attachments\', 0),
				(\'anounce\', 0),
				(\'articles_per_page\', 10),
				(\'forum_id\', 0),
				(\'max_filesize\', ' . $max_filesize . '),
				(\'extensions\', \'' . $extensions . '\'),
				(\'thumbnail\', 0),
				(\'max_attachments\', 1)
				';
		}
		else
		{
			$sql = 'ALTER TABLE ' . $this->table_prefix . 'kb_config DROP is_dynamic';
			$this->db->sql_query($sql);
			$sql = 'ALTER TABLE ' . $this->table_prefix . 'kb_config CHANGE config_value config_value TEXT CHARACTER SET utf8 COLLATE utf8_bin';
			$this->db->sql_query($sql);
			$sql = 'INSERT INTO ' . $this->table_prefix . 'kb_config' . ' (config_name, config_value) VALUES
				(\'allow_attachments\', 0),
				(\'max_filesize\', ' . $max_filesize . '),
				(\'extensions\', \'' . $extensions . '\'),
				(\'thumbnail\', 0),
				(\'max_attachments\', 1)
				';
		}
		$this->db->sql_query($sql);
	}

	public function update_kb_options_table()
	{
		$sql = 'SELECT * FROM ' . $this->table_prefix . 'kb_options';
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);
		if (empty($row))
		{
			$options = [
				1 => 'kb_u_add',
				2 => 'kb_u_edit',
				3 => 'kb_u_delete',
				4 => 'kb_u_add_noapprove',
				5 => 'kb_m_edit',
				6 => 'kb_m_delete',
				7 => 'kb_m_approve',
			];

			foreach ($options as $key => $value)
			{
				$sql_ary[] = [
					'auth_option_id' => $key,
					'auth_option'    => $value,
					'is_global'      => 0,
					'is_local'       => 1,
				];
			}
			$this->db->sql_multi_insert($this->table_prefix . 'kb_options', $sql_ary);
		}
	}

}
