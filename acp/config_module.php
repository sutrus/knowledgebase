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

class config_module
{
	public string $page_title;
	public string $tpl_name;
	public string $u_action;

	/**
	 * Config ACP module
	 *
	 * @param int    $id   The module ID
	 * @param string $mode The module mode (for example: manage or settings)
	 * @throws \Exception
	 */
	public function main(int $id, string $mode): void
	{
		global $phpbb_container;

		/** @var \phpbb\language\language $language */
		$language = $phpbb_container->get('language');
		$language->add_lang('acp_knowledgebase', 'sheer/knowledgebase');
		$language->add_lang(['acp/attachments']);

		// Get an instance of the admin controller
		$admin_controller = $phpbb_container->get('sheer.knowledgebase.admin.controller');

		// Make the $u_action url available in the admin controller
		$admin_controller->set_page_url($this->u_action);

		$this->tpl_name = 'acp_knowledgebase_config';
		$this->page_title = $language->lang('KNOWLEDGE_BASE') . ' &bull; ' . $language->lang('ACP_KNOWLEDGE_BASE_CONFIGURE');

		switch ($mode)
		{
			case 'settings':
				// Load the display options handle in the admin controller
				$admin_controller->settings();
			break;

			default:
			break;
		}
	}
}
