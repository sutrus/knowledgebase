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

class version_1_0_5 extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return ['\sheer\knowledgebase\migrations\version_1_0_4'];
	}

	public function effectively_installed()
	{
		return
			(isset($this->config['knowledge_base_version']) &&
				version_compare($this->config['knowledge_base_version'], '1.0.5', '>=')) ||
			(isset($this->config['kb_version']) &&
				version_compare($this->config['kb_version'], '2.0.0', '>='));
	}

	public function update_schema()
	{
		return [];
	}


	public function revert_schema()
	{
		return [];
	}

	public function update_data()
	{
		return [
			// Update configs
			['config.update', ['knowledge_base_version', '1.0.5']],
			['custom', [[$this, 'add_config']]],
		];
	}

	public function add_config()
	{
		$sql = 'INSERT INTO ' . $this->table_prefix . 'kb_config' . ' (config_name, config_value) VALUES
			(\'sort_type\', 0)';
		$this->db->sql_query($sql);
	}
}
