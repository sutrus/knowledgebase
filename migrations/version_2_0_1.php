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

class version_2_0_1 extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return [
			'\sheer\knowledgebase\migrations\version_2_0_0',
		];
	}

	public function effectively_installed()
	{
		return
			(isset($this->config['kb_version']) &&
				version_compare($this->config['kb_version'], '2.0.1', '>='));
	}

	public function update_data()
	{
		//Get config data and check first character(detect serialized data and convert to json)
//		$extensions = $this->config_text->get('kb_extensions');
		$sql = 'SELECT config_value FROM ' . CONFIG_TEXT_TABLE . "
			WHERE config_name ='kb_extensions'";
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$extensions = $row['config_value'];
		$this->db->sql_freeresult($result);

		if (substr($extensions, 0, 1) != '{')
		{
			$extensions = json_encode(unserialize($extensions));
		}

		return [
			['config_text.update', ['kb_extensions', $extensions]],

			// Update configs
			['config.update', ['kb_version', '2.0.1']],
		];
	}
}
