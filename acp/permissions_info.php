<?php
/**
 *
 * Knowledge base. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2017, Sheer
 * @license       GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace sheer\knowledgebase\acp;

class permissions_info
{
	public function module()
	{
		return [
			'filename' => '\sheer\knowledgebase\acp\permissions_module',
			'version'  => '1.0.0',
			'title'    => 'ACP_LIBRARY_PERMISSIONS',
			'modes'    => [
				'settings' => [
					'title' => 'ACP_LIBRARY_PERMISSIONS',
					'auth'  => 'ext_sheer/knowledgebase && acl_a_board && acl_a_manage_kb',
					'cat'   => ['ACP_KNOWLEDGE_BASE'],
				],
				'mask'     => [
					'title' => 'ACP_LIBRARY_PERMISSIONS_MASK',
					'auth'  => 'ext_sheer/knowledgebase && acl_a_board && acl_a_manage_kb',
					'cat'   => ['ACP_KNOWLEDGE_BASE'],
				],
			],
		];
	}
}
