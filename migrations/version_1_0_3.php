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

class version_1_0_3 extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return ['\sheer\knowledgebase\migrations\version_1_0_2'];
	}

	public function effectively_installed()
	{
		return
			(isset($this->config['knowledge_base_version']) &&
				version_compare($this->config['knowledge_base_version'], '1.0.3', '>=')) ||
			(isset($this->config['kb_version']) &&
				version_compare($this->config['kb_version'], '2.0.0', '>='));
	}

	public function update_schema()
	{
		return [
			'add_columns'  => [
				$this->table_prefix . 'kb_src_wrdmtch' => [
					'descr_match' => ['BOOL', 0],
				],
			],
			'drop_columns' => [
				$this->table_prefix . 'kb_src_wrdmtch' => [
					'reply_id',
				],
			],
			'drop_columns' => [
				$this->table_prefix . 'kb_articles' => [
					'bbcode_bitfield',
				],
			],
		];
	}

	public function revert_schema()
	{
		return [];
	}

	public function update_data()
	{
		return [
			// Update configs
			['config.update', ['knowledge_base_version', '1.0.3']],
			['custom', [[$this, 'add_key']]],
		];
	}

	public function add_key()
	{
		$sql = 'ALTER TABLE ' . $this->table_prefix . 'kb_src_wrdmtch DROP INDEX un_mtch, ADD UNIQUE un_mtch (article_id, word_id, title_match, descr_match) USING BTREE';
		$this->db->sql_query($sql);
	}
}
