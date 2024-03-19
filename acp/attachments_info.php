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

class attachments_info
{
	public function module(): array
	{
		return [
			'filename' => '\sheer\knowledgebase\acp\attachments_module',
			'version'  => '1.0.0',
			'title'    => 'ACP_LIBRARY_ATTACHMENTS',
			'modes'    => [
				'attachments' => [
					'title' => 'ACP_LIBRARY_ATTACHMENTS',
					'auth'  => 'ext_sheer/knowledgebase && acl_a_board && acl_a_manage_kb',
					'cat'   => ['ACP_KNOWLEDGE_BASE'],
				],
				'orphan'      => [
					'title' => 'ACP_LIBRARY_ATTACHMENTS_ORPHAN',
					'auth'  => 'ext_sheer/garage && acl_a_board',
					'cat'   => ['ACP_KNOWLEDGE_BASE'],
				],
				'extra_files' => [
					'title' => 'ACP_LIBRARY_ATTACHMENTS_EXTRA_FILES',
					'auth'  => 'ext_sheer/garage && acl_a_board',
					'cat'   => ['ACP_KNOWLEDGE_BASE'],
				],
				'lost_files'  => [
					'title' => 'ACP_LIBRARY_ATTACHMENTS_LOST_FILES',
					'auth'  => 'ext_sheer/garage && acl_a_board',
					'cat'   => ['ACP_KNOWLEDGE_BASE'],
				],
			],
		];
	}
}
