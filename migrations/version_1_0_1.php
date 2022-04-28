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

class version_1_0_1 extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return array('\sheer\knowledgebase\migrations\version_1_0_0');
	}

	public function effectively_installed()
	{
		return
			(isset($this->config['knowledge_base_version']) &&
				version_compare($this->config['knowledge_base_version'], '1.0.1', '>=')) ||
			(isset($this->config['kb_version']) &&
				version_compare($this->config['kb_version'], '2.0.0', '>='));
	}

	public function update_schema()
	{
		return array();
	}

	public function revert_schema()
	{
		return array();
	}

	public function update_data()
	{
		return array(
			// Update configs
			array('config.update', array('knowledge_base_version', '1.0.1')),

			array('module.add', array('acp', 'KNOWLEDGE_BASE', array(
				'module_basename' => '\sheer\knowledgebase\acp\attachments_module',
				'module_langname' => 'ACP_LIBRARY_ATTACHMENTS_EXTRA_FILES',
				'module_mode'     => 'extra_files',
				'module_auth'     => 'ext_sheer/knowledgebase && acl_a_board && acl_a_manage_kb',
			))),

			array('module.add', array('acp', 'KNOWLEDGE_BASE', array(
				'module_basename' => '\sheer\knowledgebase\acp\attachments_module',
				'module_langname' => 'ACP_LIBRARY_ATTACHMENTS_LOST_FILES',
				'module_mode'     => 'lost_files',
				'module_auth'     => 'ext_sheer/knowledgebase && acl_a_board && acl_a_manage_kb',
			))),
		);
	}
}
