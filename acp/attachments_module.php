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

class attachments_module
{
	public $page_title;
	public $tpl_name;
	public $u_action;

	/**
	 * Attachments ACP module
	 *
	 * @param int    $id   The module ID
	 * @param string $mode The module mode (for example: manage or settings)
	 * @throws \Exception
	 */
	public function main($id, $mode)
	{
		global $phpbb_container;

		/** @var \phpbb\language\language $language */
		$language = $phpbb_container->get('language');
		$language->add_lang('acp_knowledgebase', 'sheer/knowledgebase');
		$language->add_lang('acp/attachments');

		// Get an instance of the admin controller
		$admin_controller = $phpbb_container->get('sheer.knowledgebase.admin.controller');

		// Make the $u_action url available in the admin controller
		$admin_controller->set_page_url($this->u_action);

		switch ($mode)
		{
			case 'attachments':
				// Load a template from adm/style for our ACP page
				$this->tpl_name = 'acp_attachments_body';
				// Set the page title for our ACP page
				$this->page_title = $language->lang('KNOWLEDGE_BASE') . ' &bull; ' . $language->lang('ACP_ATTACHMENTS');
				// Load the display options handle in the admin controller
				$admin_controller->main();
			break;
			case 'orphan':
				$this->tpl_name = 'acp_attachments_body';
				$this->page_title = $language->lang('KNOWLEDGE_BASE') . ' &bull; ' . $language->lang('ACP_ORPHAN_ATTACHMENTS');
				$admin_controller->orphan();
			break;
			case 'extra_files':
				$this->tpl_name = 'acp_attachments_body';
				$this->page_title = $language->lang('KNOWLEDGE_BASE') . ' &bull; ' . $language->lang('ACP_LIBRARY_ATTACHMENTS_EXTRA_FILES');
				$admin_controller->extra_files();
			break;
			case 'lost_files':
				$this->tpl_name = 'acp_attachments_body';
				$this->page_title = $language->lang('KNOWLEDGE_BASE') . ' &bull; ' . $language->lang('ACP_LIBRARY_ATTACHMENTS_LOST_FILES');
				$admin_controller->lost_files();
			break;
		}
	}
}
