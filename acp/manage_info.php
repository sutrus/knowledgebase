<?php
/**
 *
 * Knowledge base. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2023, sutrus ( update to phpBB 3.3 )
 * @copyright (c) 2017, Sheer
 * @license       GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace sheer\knowledgebase\acp;

class manage_info
{
	public function module(): array
	{
		return [
			'filename' => '\sheer\knowledgebase\acp\manage_module',
			'version'  => '1.0.0',
			'title'    => 'ACP_LIBRARY_MANAGE',
			'modes'    => [
				'settings' => [
					'title' => 'ACP_LIBRARY_MANAGE',
					'auth'  => 'ext_sheer/knowledgebase && acl_a_board && acl_a_manage_kb',
					'cat'   => ['ACP_KNOWLEDGE_BASE'],
				],
			],
		];
	}
}
