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

class articles_module
{
	public string $page_title;
	public string $tpl_name;
	public string $u_action;

	/**
	 * Articles ACP module
	 *
	 * @param string $id   The module ID
	 * @param string $mode The module mode (for example: manage or settings)
	 * @throws \Exception
	 */
	public function main(string $id, string $mode): void
	{
		global $phpbb_container;

		/** @var \phpbb\language\language $language */
		$language = $phpbb_container->get('language');
		$language->add_lang('acp_knowledgebase', 'sheer/knowledgebase');

		/** @var \phpbb\request\request $request */
		$request = $phpbb_container->get('request');

		// Get an instance of the admin controller
		$admin_controller = $phpbb_container->get('sheer.knowledgebase.controller.acp');

		// Make the $u_action url available in the admin controller
		$admin_controller->set_page_url($this->u_action);

		// Load a template from adm/style for our ACP page
		$this->tpl_name = 'acp_knowledgebase_articles';
		// Set the page title for our ACP page
		$this->page_title = $language->lang('KNOWLEDGE_BASE') . ' &bull; ' . $language->lang('ACP_LIBRARY_ARTICLES');

		$action = $request->variable('action', '');
		switch ($action)
		{
			case 'move':
				// Load the display options handle in the admin controller
				$admin_controller->move_article();
			break;

			case 'delete':
				// Load the display options handle in the admin controller
				$admin_controller->delete_article();
//			break;

			default:
				// Load the display options handle in the admin controller
				$admin_controller->show_articles();
			break;
		}
	}
}
