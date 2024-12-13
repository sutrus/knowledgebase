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

class manage_module
{
	public string $page_title;
	public string $tpl_name;
	public string $u_action;

	/**
	 * Manage ACP module
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

		/** @var \phpbb\db\driver\driver_interface $db */
		$db = $phpbb_container->get('dbal.conn');

		/** @var \phpbb\cache\driver\driver_interface $cache */
		$cache = $phpbb_container->get('cache');

		/** @var \phpbb\user $user */
		$user = $phpbb_container->get('user');

		/** @var \phpbb\auth\auth $auth */
		$auth = $phpbb_container->get('auth');

		/** @var \phpbb\template\template $template */
		$template = $phpbb_container->get('template');

		/** @var \phpbb\log\log $phpbb_log */
		$phpbb_log = $phpbb_container->get('log');

		/** @var \sheer\knowledgebase\inc\functions_kb $kb */
		$kb = $phpbb_container->get('sheer.knowledgebase.inc');

		// Get an instance of the admin controller
		$admin_controller = $phpbb_container->get('sheer.knowledgebase.controller.acp');

		// Make the $u_action url available in the admin controller
		$admin_controller->set_page_url($this->u_action);

		// Load a template from adm/style for our ACP page
		$this->tpl_name = 'acp_knowledgebase_category';
		$this->page_title = $language->lang('ACP_LIBRARY_MANAGE');

		$categories_table = $phpbb_container->getParameter('tables.categories_table');
		$phpbb_admin_path = $phpbb_container->getParameter('core.root_path') . 'adm/';
		$phpEx = $phpbb_container->getParameter('core.php_ext');

		$category_data = $errors = [];

		$action = $request->variable('action', '');
		$update = ($request->is_set_post('update'));
		$category_id = $request->variable('f', '');
		$parent_id = $request->variable('parent_id', 0);
		$copy_perm_from_id = $request->variable('cat_perm_from', 0);

		if ($update)
		{
			switch ($action)
			{
				case 'delete':
					$action_sub_cats = $request->variable('action_sub_cats', '');
					$sub_cats_to_id = $request->variable('sub_cats_to_id', 0);
					$action_posts = $request->variable('action_posts', '');
					$posts_to_id = $request->variable('posts_to_id', 0);
					$errors = $admin_controller->delete_category($category_id, $action_posts, $action_sub_cats, $posts_to_id, $sub_cats_to_id);
					if (count($errors))
					{
						break;
					}
					$auth->acl_clear_prefetch();
					$cache->destroy('sql', $categories_table);
					meta_refresh(2, $this->u_action . '&amp;parent_id=' . $parent_id);
					trigger_error($language->lang('CATEGORY_DELETED') . adm_back_link($this->u_action . '&amp;parent_id=' . $parent_id));
				break;
				case 'edit':
					$category_data = compact('category_id');
//				break;
				case 'add':
					$category_data += [
						'parent_id'        => $request->variable('parent_id', $parent_id),
						'type_action'      => $request->variable('type_action', ''),
						'category_parents' => '',
						'category_name'    => utf8_normalize_nfc($request->variable('category_name', '', true)),
						'category_details' => utf8_normalize_nfc($request->variable('category_details', '', true)),
					];
					$errors = $admin_controller->update_category_data($category_data, $copy_perm_from_id);
					if (!count($errors))
					{
						$cache->destroy('sql', $categories_table);
						$message = ($action == 'add') ? sprintf($language->lang('CATEGORY_ADDED'), '<a href="' . append_sid("{$phpbb_admin_path}index.{$phpEx}", 'i=-sheer-knowledgebase-acp-permissions_module&mode=permissions&action=setting_group_local&category_id[]=' . $category_data['category_id']) . '">', '</a>') : $language->lang('CATEGORY_EDITED');
						meta_refresh(2, $this->u_action . '&amp;parent_id=' . $parent_id);
						trigger_error($message . adm_back_link($this->u_action . '&amp;parent_id=' . $parent_id));
					}
				break;
			}
		}

		switch ($action)
		{
			case 'move_up':
			case 'move_down':
				if (!$category_id)
				{
					trigger_error($language->lang('CAT_NO_EXISTS') . adm_back_link($this->u_action . '&amp;parent_id=' . $parent_id), E_USER_WARNING);
				}
				$sql = 'SELECT * FROM ' . $categories_table . ' WHERE category_id = ' . $category_id;
				$result = $db->sql_query($sql);
				$row = $db->sql_fetchrow($result);
				$db->sql_freeresult($result);
				if (!$row)
				{
					trigger_error($language->lang('CAT_NO_EXISTS') . adm_back_link($this->u_action . '&amp;parent_id=' . $parent_id), E_USER_WARNING);
				}
				$move_category_name = $admin_controller->move_category_by($row, $action, 1);
				if ($move_category_name !== false)
				{
					$phpbb_log->add('admin', $user->data['user_id'], $user->data['user_ip'], 'LOG_CATS_' . strtoupper($action), time(), [$row['category_name'], $move_category_name]);
					$cache->destroy('sql', $categories_table);
				}
			break;
			case 'add':
			case 'edit':
				// Show form to create/modify a category
				if ($action == 'edit')
				{
					$this->page_title = 'LIBRARY_EDIT_CAT';
					$row = $kb->get_cat_info($category_id);

					if (!$update)
					{
						$category_data = $row;
					}
					else
					{
						$category_data['left_id'] = $row['left_id'];
						$category_data['right_id'] = $row['right_id'];
					}

					// Make sure no direct child cats are able to be selected as parents.
					$exclude_cats = [];
					foreach ($kb->get_category_branch($category_id, 'children') as $row)
					{
						$exclude_cats[] = $row['category_id'];
					}

					$parents_list = $kb->make_category_select($category_data['parent_id'], $exclude_cats, false);
				}
				else
				{
					$this->page_title = 'ADD_CATEGORY';
					$category_id = $parent_id;
					$parents_list = $kb->make_category_select($parent_id, [], false);

					// Fill category data with default values
					if (!$update)
					{
						$category_data = [
							'parent_id'        => $parent_id,
							'category_name'    => utf8_normalize_nfc($request->variable('category_name', '', true)),
							'category_details' => '',
						];
					}
				}

				$category_desc_data = [
					'text' => $category_data['category_details'],
				];

				$sql = 'SELECT category_id FROM ' . $categories_table . ' WHERE  category_id <> ' . (int) $category_id;
				$result = $db->sql_query_limit($sql, 1);
				$postable_category_exists = false;
				if ($db->sql_fetchrow($result))
				{
					$postable_category_exists = true;
				}
				$db->sql_freeresult($result);

				// Subcat move options
				if ($postable_category_exists)
				{
					$template->assign_vars([
							'S_MOVE_CATEGORY_OPTIONS' => $kb->make_category_select($category_data['parent_id'], [$category_id], false),
						]
					);
				}

				$copy_category_id = ($action == 'add') ? 0 : $category_id;
				$template->assign_vars([
						'S_EDIT'               => true,
						'S_ERROR'              => (bool) count($errors),
						'S_PARENT_ID'          => $parent_id,
						'S_CATEGORY_PARENT_ID' => $category_data['parent_id'],
						'S_ADD_ACTION'         => ($action == 'add'),
						'U_BACK'               => $this->u_action . '&amp;parent_id=' . $parent_id,
						'U_EDIT_ACTION'        => $this->u_action . '&amp;parent_id=' . $parent_id . '&amp;action=' . $action . '&amp;f=' . $category_id,
						'L_TITLE'              => $language->lang($this->page_title),
						'ERROR_MSG'            => (count($errors)) ? implode('br/>', $errors) : '',
						'CATEGORY_NAME'        => $category_data['category_name'],
						'CATEGORY_DESCR'       => $category_desc_data['text'],
						'S_PARENT_OPTIONS'     => $parents_list,
						'S_COPY_OPTIONS'       => $kb->make_category_select(false, [$copy_category_id], false),
					]
				);

			break;
			case 'delete':
				if (!$category_id)
				{
					trigger_error($language->lang('CAT_NO_EXISTS') . adm_back_link($this->u_action . '&amp;parent_id=' . $parent_id), E_USER_WARNING);
				}
				$category_data = $kb->get_cat_info($category_id);
				$sub_cats_id = [];
				$sub_cats = $kb->get_category_branch($category_id, 'children');
				foreach ($sub_cats as $row)
				{
					$sub_cats_id[] = $row['category_id'];
				}
				$cats_list = $kb->make_category_select($category_data['parent_id'], $sub_cats_id, false);
				$sql = 'SELECT category_id
					FROM ' . $categories_table . '
					WHERE  category_id <> ' . (int) $category_id;
				$result = $db->sql_query_limit($sql, 1);
				if ($db->sql_fetchrow($result))
				{
					$template->assign_vars([
							'S_MOVE_CATEGORY_OPTIONS' => $kb->make_category_select($category_data['parent_id'], $sub_cats_id, false),
						]
					);
				}
				$db->sql_freeresult($result);
				$parent_id = ($parent_id == $category_id) ? 0 : $parent_id;
				$template->assign_vars([
					'S_DELETE_CATEGORY' => true,
					'U_ACTION'          => $this->u_action . '&amp;parent_id=' . $parent_id . '&amp;action=delete&amp;f=' . $category_id,
					'U_BACK'            => $this->u_action . '&amp;parent_id=' . $parent_id,
					'CATEGORY_NAME'     => $category_data['category_name'],
					'S_HAS_SUBCATS'     => ($category_data['right_id'] - $category_data['left_id'] > 1),
					'S_CATS_LIST'       => $cats_list,
					'S_ERROR'           => (bool) count($errors),
					'ERROR_MSG'         => (count($errors)) ? implode('<br>', $errors) : '',
				]);
			break;
			case 'sync':
				$errors = $admin_controller->sync($category_id);
				$category_data = $kb->get_cat_info($category_id);
				if (!count($errors))
				{
					$phpbb_log->add('admin', $user->data['user_id'], $user->data['user_ip'], 'LOG_CATS_' . strtoupper($action), time(), [$category_data['category_name']]);
					$cache->destroy('sql', $categories_table);
					meta_refresh(2, $this->u_action . '&amp;parent_id=' . $parent_id);
					trigger_error('SYNC_OK');
				}
			break;
		}

		// Default management page
		if (!$parent_id)
		{
			$navigation = $language->lang('CATEGORY_LIST');
			if (!empty($this->config['kb_forum_id']) && !empty($this->config['kb_anounce']))
			{
				$errors[] = $language->lang('WARNING_DEFAULT_CONFIG');
			}
		}
		else
		{
			$navigation = '<a href="' . $this->u_action . '">' . $language->lang('CATEGORY_LIST') . '</a>';
			$cats_nav = $kb->get_category_branch($parent_id, 'parents', 'descending');
			foreach ($cats_nav as $row)
			{
				if ($row['category_id'] == $parent_id)
				{
					$navigation .= ' -&gt; ' . $row['category_name'];
				}
				else
				{
					$navigation .= ' -&gt; <a href="' . $this->u_action . '&amp;parent_id=' . $row['category_id'] . '">' . $row['category_name'] . '</a>';
				}
			}
		}

		// Jumpbox
		$cats_box = $kb->make_category_select($parent_id, [], false);
		$sql = 'SELECT * FROM ' . $categories_table . '
			WHERE parent_id = ' . (int) $parent_id . '
			ORDER BY left_id';
		$result = $db->sql_query($sql);
		if ($row = $db->sql_fetchrow($result))
		{
			do
			{
				$url = $this->u_action . '&amp;parent_id=' . $parent_id . '&amp;f=' . $row['category_id'];

				$template->assign_block_vars('categories', [
						'ID'             => $row['category_id'],
						'CATEGORY_NAME'  => $row['category_name'],
						'CATEGORY_DESCR' => $row['category_details'],
						'ARTICLES'       => $row['number_articles'],
						'U_CATEGORY'     => $this->u_action . '&amp;parent_id=' . $row['category_id'],
						'U_MOVE_UP'      => $url . '&amp;action=move_up',
						'U_MOVE_DOWN'    => $url . '&amp;action=move_down',
						'U_EDIT'         => $url . '&amp;action=edit',
						'U_DELETE'       => $url . '&amp;action=delete',
						'U_SYNC'         => $url . '&amp;action=sync',
					]
				);
			} while ($row = $db->sql_fetchrow($result));
		}
		else if ($parent_id)
		{
			$row = $kb->get_cat_info($parent_id);
			if (empty($row))
			{
				$errors[] = $language->lang('CAT_NO_EXISTS');
			}

			$url = $this->u_action . '&amp;parent_id=' . $parent_id . '&amp;f=' . $row['category_id'];

			$template->assign_vars([
					'S_NO_CATS' => true,
					'U_EDIT'    => $url . '&amp;action=edit',
					'U_DELETE'  => $url . '&amp;action=delete',
				]
			);
		}

		$db->sql_freeresult($result);

		$template->assign_vars([
			'ERROR_MSG'  => (count($errors)) ? implode('<br>', $errors) : '',
			'NAVIGATION' => $navigation,
			'CATS_BOX'   => $cats_box,
			'S_MANAGE'   => true,
			'S_ACTION'   => $this->u_action . '&amp;parent_id=' . $parent_id . '&amp;action=' . $action . '&amp;f=' . $category_id,
			'U_ACTION'   => $this->u_action . '&amp;parent_id=' . $parent_id,
		]);
	}
}
