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

class version_1_0_2 extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return ['\sheer\knowledgebase\migrations\version_1_0_1'];
	}

	public function effectively_installed()
	{
		return
			(isset($this->config['knowledge_base_version']) &&
				version_compare($this->config['knowledge_base_version'], '1.0.2', '>=')) ||
			(isset($this->config['kb_version']) &&
				version_compare($this->config['kb_version'], '2.0.0', '>='));
	}

	public function update_schema()
	{
		return [
			'add_columns' => [
				$this->table_prefix . 'kb_articles' => [
					'display_order' => ['UINT', 0],
				],
			],
		];
	}

	public function revert_schema()
	{
		return [
			'drop_columns' => [
				$this->table_prefix . 'kb_articles' => [
					'display_order',
				],
			],
		];
	}

	public function update_data()
	{
		return [
			// Update configs
			['config.update', ['knowledge_base_version', '1.0.2']],
			['custom', [[$this, 'set_display_order']]],
		];
	}

	public function set_display_order()
	{
		$sql = 'SELECT category_id
			FROM ' . $this->table_prefix . 'kb_categories';
		$this->db->sql_query($sql);
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$sql = 'SELECT article_id
				FROM ' . $this->table_prefix . 'kb_articles
				WHERE article_category_id = ' . (int) $row['category_id'];
			$res = $this->db->sql_query($sql);
			$i = 1;
			while ($art_row = $this->db->sql_fetchrow($res))
			{
				$sql = 'UPDATE ' . $this->table_prefix . 'kb_articles
						SET display_order = ' . $i . '
						WHERE article_id = ' . (int) $art_row['article_id'];
				$this->db->sql_query($sql);
				$i++;
			}
			$this->db->sql_freeresult($res);
		}
		$this->db->sql_freeresult($result);
	}
}
