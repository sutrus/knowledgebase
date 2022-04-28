<?php

/**
 *
 * Knowledge base. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2022, sutrus
 * @license       GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace sheer\knowledgebase\migrations;

class version_2_0_0 extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return [
			'\sheer\knowledgebase\migrations\version_1_0_7',
		];
	}

	public function effectively_installed()
	{
		return
			(isset($this->config['kb_version']) &&
				version_compare($this->config['kb_version'], '2.0.0', '>='));
	}

	public function update_schema()
	{
		// Read config data from db
		$sql = 'SELECT config_name, config_value FROM ' . $this->table_prefix . 'kb_config';
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$this->config[$row['config_name']] = $row['config_value'];
		}

		// Drop table
		return [
			'drop_tables' => [
				$this->table_prefix . 'kb_config',
			],
		];
	}

	public function update_data()
	{
		return [
			// Add config data to config table
			['config.add', ['kb_font_icon', 'book']],

			// Create config from kb_config table
			['config.add', ['kb_allow_attachments', $this->config['allow_attachments']]],
			['config.add', ['kb_allow_thumbnail', $this->config['thumbnail']]],
			['config.add', ['kb_anounce', $this->config['anounce']]],
			['config.add', ['kb_articles_per_page', $this->config['articles_per_page']]],
			['config_text.add', ['kb_extensions', $this->config['extensions']]],
			['config.add', ['kb_forum_id', $this->config['forum_id']]],
			['config_text.add', ['kb_forum_prefix', '']],
			['config_text.add', ['kb_topic_prefix', '']],
			['config.add', ['kb_max_attachments', $this->config['max_attachments']]],
			['config.add', ['kb_max_filesize', $this->config['max_filesize']]],
			['config.add', ['kb_sort_type', $this->config['sort_type']]],

			// Update search method
			['config.add', ['kb_num_articles', '0']],
			['config.update', ['kb_search_type', 'sheer\\knowledgebase\\search\\backend\\' . $this->config['kb_search_type']]],

			// Update configs
			['config.remove', ['knowledge_base_version']],
			['config.add', ['kb_version', '2.0.0']],
		];
	}
}
