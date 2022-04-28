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

class permissions_module
{
	public $page_title;
	public $tpl_name;
	public $u_action;

	/**
	 * Permissions ACP module
	 *
	 * @param int    $id   The module ID
	 * @param string $mode The module mode (for example: manage or settings)
	 * @throws \Exception
	 */
	function main($id, $mode)
	{
		global $phpbb_container;

		/** @var \phpbb\language\language $language */
		$language = $phpbb_container->get('language');
		$language->add_lang('acp_knowledgebase', 'sheer/knowledgebase');
		$language->add_lang('acp/permissions');

		/** @var \phpbb\request\request $request */
		$request = $phpbb_container->get('request');

		/** @var \phpbb\db\driver\driver_interface */
		$db = $phpbb_container->get('dbal.conn');

		/** @var \phpbb\user */
		$user = $phpbb_container->get('user');

		/** @var \phpbb\auth\auth */
		$auth = $phpbb_container->get('auth');

		/** @var \phpbb\template\template */
		$template = $phpbb_container->get('template');

		/** @var \phpbb\log\log */
		$phpbb_log = $phpbb_container->get('log');

		/** @var @sheer\knowledgebase\inc\functions_kb */
		$phpbb_ext_kb = $phpbb_container->get('sheer.knowledgebase.inc');

		// Get an instance of the admin controller
		$admin_controller = $phpbb_container->get('sheer.knowledgebase.admin.controller');

		// Make the $u_action url available in the admin controller
		$admin_controller->set_page_url($this->u_action);

		$phpbb_root_path = $phpbb_container->getParameter('core.root_path');
		$phpEx = $phpbb_container->getParameter('core.php_ext');

		$categories_table = $phpbb_container->getParameter('tables.categories_table');

		include_once($phpbb_root_path . 'includes/functions_user.' . $phpEx);

		$this->tpl_name = 'acp_permissions_body';

//mode: permissions
//mode: mask
		if ($mode == 'mask')
		{
			$this->page_title = $language->lang('KNOWLEDGE_BASE') . ' &bull; ' . $language->lang('ACP_LIBRARY_PERMISSIONS_MASK');
			$title_edit_permissions = $title_add_permissions = $language->lang('VIEW_PERMISSIONS');
		}
		else
		{
			$this->page_title = $language->lang('KNOWLEDGE_BASE') . ' &bull; ' . $language->lang('ACP_LIBRARY_PERMISSIONS');
			$title_edit_permissions = $language->lang('EDIT_PERMISSIONS');
			$title_add_permissions = $language->lang('ADD_PERMISSIONS');
		}

		$user_id = $request->variable('user_id', array(0));
		$group_id = $request->variable('group_id', array(0));
		$username = $request->variable('username', array(''), true);
		$usernames = $request->variable('usernames', '', true);
		$all_cats = $request->variable('all_cats', 0);
		$p_mode = $request->variable('p_mode', '');
		$action = $request->variable('action', '');
		$delete = $request->variable('delete', false);
		$category_id = $all_cats ? $admin_controller->make_array_categoryid() : $request->variable('category_id', array(0));

		// Map usernames to ids and vice versa
		if ($usernames)
		{
			$username = explode("\n", $usernames);
		}
		unset($usernames);

		if (count($username) && !count($user_id))
		{
			user_get_id_name($user_id, $username);

			if (!count($user_id))
			{
				trigger_error($language->lang('SELECTED_USER_NOT_EXIST') . adm_back_link($this->u_action), E_USER_WARNING);
			}
		}
		unset($username);

		if ($delete)
		{
			$action = 'delete';
		}

		// Handle actions
		switch ($action)
		{
			case 'trace':
				$permission = $request->variable('auth', '');
				$user_id = $request->variable('user_id', 0);
				$category_id = $request->variable('category_id', 0);

				$this->tpl_name = 'permission_trace';

				if ($user_id && $auth->acl_get('a_viewauth'))
				{
					$this->page_title = $language->lang('KNOWLEDGE_BASE') . ' &bull; ' . sprintf($language->lang('TRACE_PERMISSION'), $language->lang($permission));
					$admin_controller->permission_trace($user_id, $category_id, $permission);
					return;
				}
				trigger_error('NO_MODE', E_USER_ERROR);
			break;

			case 'settings':
				$admin_controller->get_mask($mode, $p_mode, (count($group_id)) ? $group_id : false, $category_id, (count($user_id)) ? $user_id : false);
			break;

			case 'delete':
				if (confirm_box(true))
				{
					$admin_controller->delete_permissions($group_id, $user_id, $category_id);
				}
				else
				{
					$s_hidden_fields = array(
						'i'           => $id,
						'mode'        => $mode,
						'action'      => array($action => 1),
						'user_id'     => $user_id,
						'group_id'    => $group_id,
						'category_id' => $category_id,
						'delete'      => true,
					);
					confirm_box(false, $language->lang('CONFIRM_OPERATION'), build_hidden_fields($s_hidden_fields));
				}
			break;

			case 'setting_group_local':
				$admin_controller->permissions_v_mask($mode, $category_id, $user_id);
//				$submit_edit_options = $request->variable('submit_edit_options', false);
//				if ($submit_edit_options)
//				{
//					$action = 'settings';
//				}
			break;

			default:
				$cats_box = $phpbb_ext_kb->make_category_select(0, false, true);
				$template->assign_vars(array(
						'S_SELECT_CATEGORY'       => true, //($cats_box) ? true : false,
						'CATS_BOX'                => $cats_box,
						'S_KB_PERMISSIONS_ACTION' => $this->u_action . '&amp;action=setting_group_local',
					)
				);
			break;
		}

		if (count($category_id))
		{
			$sql = 'SELECT category_name
				FROM ' . $categories_table . '
				WHERE ' . $db->sql_in_set('category_id', $category_id) . '
				ORDER BY left_id ASC';
			$result = $db->sql_query($sql);

			$category_names = array();
			while ($row = $db->sql_fetchrow($result))
			{
				$category_names[] = $row['category_name'];
			}
			$db->sql_freeresult($result);

			$template->assign_vars(array(
					'S_CATEGORY_NAMES' => (bool) count($category_names),
					'CATEGORY_NAMES'   => implode($language->lang('COMMA_SEPARATOR'), $category_names))
			);
		}

		$template->assign_vars(array(
				'L_EDIT_PERMISSIONS' => $title_edit_permissions,
				'L_ADD_PERMISSIONS'  => $title_add_permissions,
			)
		);
	}
}
