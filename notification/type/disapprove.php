<?php

/**
 *
 * Knowledge base. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2017, Sheer
 * @license       GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace sheer\knowledgebase\notification\type;

class disapprove extends \phpbb\notification\type\base
{
	/**
	 * Notification option data (for outputting to the user)
	 *
	 * @var bool|array False if the service should use it's default data
	 *                    Array of data (including keys 'id', 'lang', and 'group')
	 */
	public static $notification_option = array(
		'lang'  => 'NOTIFICATION_TYPE_ARTICLE_DISAPPROVE',
		'group' => 'NOTIFICATION_GROUP_MISCELLANEOUS',
	);
	/** @var \phpbb\controller\helper */
	protected $helper;
	/** @var \phpbb\user_loader */
	protected $user_loader;

	/**
	 * Get the id of the item
	 *
	 * @param array $data
	 */
	public static function get_item_id($data)
	{
		return (int) $data['item_id'];
	}

	/**
	 * Get the id of the parent
	 *
	 * @param array $data
	 */
	public static function get_item_parent_id($data)
	{
		return 0;
	}

	/**
	 * Set the controller helper
	 *
	 * @param \phpbb\controller\helper $helper
	 * @return void
	 */
	public function set_controller_helper(\phpbb\controller\helper $helper)
	{
		$this->helper = $helper;
	}

	/* Is available
	*/

	public function set_user_loader(\phpbb\user_loader $user_loader)
	{
		$this->user_loader = $user_loader;
	}

	/**
	 * Get notification type name
	 *
	 * @return string
	 */
	public function get_type()
	{
		return 'sheer.knowledgebase.notification.type.disapprove';
	}

	public function is_available()
	{
		return false;
		// $available = $this->config['allow_privmsg'] && $this->auth->acl_get('u_readpm');

		// if ($available)
		// {
		//   $auth_approve = $this->auth->acl_get_list(false, 'a_manage_kb');
		//   if (empty($auth_approve))
		//   {
		//   	$auth_approve[0]['a_manage_kb'] = array();
		//   }

		//   $has_permission = $this->check_permisson('kb_m_approve', 0);
		//   if (!empty($auth_approve) || !empty($has_permission))
		//   {
		//   	$users = array_merge($has_permission, $auth_approve[0]['a_manage_kb']);
		//   	$users = array_unique($users);
		// 		$available = ((in_array($this->user->data['author_id'], $users)));
		//   }
		//   else
		//   {
		//   	return false;
		// 	}
		// }
		// return $available;
	}

	/**
	 * Find the users who want to receive notifications
	 *
	 * @param array $data
	 * @param array $options Options for finding users for notification
	 *
	 * @return array
	 */
	public function find_users_for_notification($data, $options = array())
	{
		$options = array_merge(array(
			'ignore_users' => array(),
		), $options);

		$users = array((int) $data['author_id']);
		return $this->check_user_notification_options($users, $options);
	}

	/**
	 * Users needed to query before this notification can be displayed
	 *
	 * @return array Array of user_ids
	 */
	public function users_to_query()
	{
		return array($this->get_data('moderator'));
	}

	/**
	 * Get the user's avatar
	 */
	public function get_avatar()
	{
		return $this->user_loader->get_avatar($this->get_data('moderator'), false, true);
	}

	/**
	 * Get the HTML formatted title of this notification
	 *
	 * @return string
	 */
	public function get_title()
	{
		$username = $this->user_loader->get_username($this->get_data('moderator'), 'no_profile');
		return $this->language->lang('NOTIFICATION_ARTICLE_DISAPPROVE', $username);
	}

	/**
	 * Get the url to this item
	 *
	 * @return string URL
	 */
	public function get_url()
	{
		return $this->helper->route('sheer_knowledgebase_index', array());
	}

	/**
	 * Get email template
	 *
	 * @return string
	 */
	public function get_email_template()
	{
		return '@sheer_knowledgebase/article_disapprove';
	}

	/**
	 * Get email template variables
	 *
	 * @return array
	 */
	public function get_email_template_variables()
	{
		$username = $this->user_loader->get_username($this->get_data('user'), 'username');
		return array(
			'USERNAME'      => htmlspecialchars_decode($username),
			'MODERATOR'     => htmlspecialchars_decode($this->user_loader->get_username($this->get_data('author_id'), 'username')),
			'ARTICLE_TITLE' => htmlspecialchars_decode(censor_text($this->get_data('title'))),
		);
	}

	/**
	 * Get the HTML formatted reference of the notification
	 *
	 * @return string
	 */
	public function get_reference()
	{
		return $this->language->lang('NOTIFICATION_REFERENCE', censor_text($this->get_data('title')));
	}

	/**
	 * Trim the user array passed down to 3 users if the array contains
	 * more than 4 users.
	 *
	 * @param array $users Array of users
	 * @return array Trimmed array of user_ids
	 */
	public function trim_user_ary($users)
	{
		if (count($users) > 4)
		{
			array_splice($users, 3);
		}
		return $users;
	}

	/**
	 * Function for preparing the data for insertion in an SQL query
	 * (The service handles insertion)
	 *
	 * @param array $data            Data from insert_need_approval
	 * @param array $pre_create_data Data from pre_create_insert_array()
	 *
	 * @return void Array of data ready to be inserted into the database
	 */
	public function create_insert_array($data, $pre_create_data = array())
	{
		$this->set_data('author_id', $data['author_id']);
		$this->set_data('title', $data['title']);
		$this->set_data('moderator', $data['moderator']);
		$this->set_data('item_id', $data['item_id']);

		parent::create_insert_array($data, $pre_create_data);
	}

	public function check_permisson($auth, $category_id = 0)
	{
		global $table_prefix;

		$kb_options_table = $table_prefix . 'kb_options';
		$kb_users_table = $table_prefix . 'kb_users';
		$kb_groups_table = $table_prefix . 'kb_groups';

		$sql_where = ($category_id) ? ' AND category_id = ' . $category_id : '';

		$moderators = $groups = $exclude = array();

		$sql = 'SELECT auth_option_id
			FROM ' . $kb_options_table . '
			WHERE auth_option LIKE \'' . $auth . '\'
			AND is_local = 1';
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$auth_option_id = $row['auth_option_id'];
		$this->db->sql_freeresult($result);

		$sql = 'SELECT user_id FROM ' . $kb_users_table . '
			WHERE auth_option_id = ' . (int) $auth_option_id . '
				AND auth_setting = 1
				' . $sql_where;
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$moderators[] = $row['user_id'];
		}
		$this->db->sql_freeresult($result);

		$sql = 'SELECT group_id
			FROM ' . $kb_groups_table . '
			WHERE auth_option_id = ' . (int) $auth_option_id . '
				AND auth_setting = 1
				' . $sql_where;
		$result = $this->db->sql_query($sql);

		while ($group_row = $this->db->sql_fetchrow($result))
		{
			$groups[] = $group_row['group_id'];
		}
		$this->db->sql_freeresult($result);

		$sql = 'SELECT user_id
			FROM ' . $kb_users_table . '
			WHERE auth_setting = 0
				' . $sql_where;
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$exclude[] = $row['user_id'];
		}
		$this->db->sql_freeresult($result);

		if (count($groups))
		{
			$sql = 'SELECT  user_id
				FROM ' . USERS_TABLE . '
				WHERE group_id IN(' . implode(',', $groups) . ')';
			$result = $this->db->sql_query($sql);

			while ($row = $this->db->sql_fetchrow($result))
			{
				if (!in_array($row['user_id'], $exclude))
				{
					$moderators[] = $row['user_id'];
				}
			}
			$this->db->sql_freeresult($result);
		}

		if ($this->user->data['user_type'] == USER_FOUNDER)
		{
			$moderators[] = $this->user->data['user_id'];
		}

		return array_unique($moderators);
	}
}
