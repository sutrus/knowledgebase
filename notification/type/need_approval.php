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

class need_approval extends \phpbb\notification\type\base
{
	/**
	 * Notification option data (for outputting to the user)
	 *
	 * @var bool|array False if the service should use its default data
	 *                 Array of data (including keys 'id', 'lang', and 'group')
	 */
	public static $notification_option = array(
		'lang'  => 'NOTIFICATION_TYPE_NEED_APPROVAL',
		'group' => 'NOTIFICATION_GROUP_MODERATION',
	);
	/** @var \phpbb\controller\helper */
	protected \phpbb\controller\helper $helper;
	/** @var \phpbb\user_loader */
	protected \phpbb\user_loader $user_loader;

	/**
	 * Get the id of the item
	 *
	 * @param array $type_data
	 * @return int
	 */
	public static function get_item_id($type_data): int
	{
		return (int) $type_data['item_id'];
	}

	/**
	 * Get the id of the parent
	 *
	 * @param array $type_data
	 * @return int
	 */
	public static function get_item_parent_id($type_data): int
	{
		return 0;//(int) $data['author_id'];
	}

	/**
	 * Set the controller helper
	 *
	 * @param \phpbb\controller\helper $helper
	 * @return void
	 */
	public function set_controller_helper(\phpbb\controller\helper $helper): void
	{
		$this->helper = $helper;
	}

	/**
	 * @param \phpbb\user_loader $user_loader
	 * @return void
	 */
	public function set_user_loader(\phpbb\user_loader $user_loader): void
	{
		$this->user_loader = $user_loader;
	}

	/**
	 * Get notification type name
	 *
	 * @return string
	 */
	public function get_type(): string
	{
		return 'sheer.knowledgebase.notification.type.need_approval';
	}

	/**
	 * Is available
	 *
	 * @return bool
	 */
	public function is_available(): bool
	{
		$available = ($this->config['allow_privmsg'] ?? false) && ($this->auth->acl_get('u_readpm') ?? false);

		if ($available)
		{
			$auth_approve = $this->auth->acl_get_list(false, 'a_manage_kb');
			if (empty($auth_approve))
			{
				$auth_approve[0]['a_manage_kb'] = array();
			}

			$has_permission = $this->check_permission('kb_m_approve', 0);
			if (!empty($auth_approve) || !empty($has_permission))
			{
				$users = array_merge($has_permission, $auth_approve[0]['a_manage_kb']);
				$users = array_unique($users);
				$available = ((in_array($this->user->data['author_id'], $users)));
			}
			else
			{
				return false;
			}
		}
		return $available;
	}

	/**
	 * @param string $auth
	 * @param int    $category_id
	 * @return array
	 */
	public function check_permission(string $auth, int $category_id = 0): array
	{
		global $table_prefix;

		$sql_where = ($category_id) ? ' AND category_id = ' . $category_id : '';

		$moderators = $groups = $exclude = array();

		$sql = 'SELECT auth_option_id
			FROM ' . $table_prefix . 'kb_options' . '
			WHERE auth_option LIKE \'' . $auth . '\'
			AND is_local = 1';
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$auth_option_id = $row['auth_option_id'];
		$this->db->sql_freeresult($result);

		$sql = 'SELECT user_id FROM ' . $table_prefix . 'kb_users' . '
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
			FROM ' . $table_prefix . 'kb_groups' . '
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
			FROM ' . $table_prefix . 'kb_users' . '
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
		return array_unique($moderators);
	}

	/**
	 * Find the users who want to receive notifications
	 *
	 * @param array $type_data
	 * @param array $options Options for finding users for notification
	 *
	 * @return array
	 */
	public function find_users_for_notification($type_data, $options = array()): array
	{
		$options = array_merge([
			'ignore_users' => [],
		], $options);

		$auth_approve = $this->auth->acl_get_list(false, 'a_manage_kb');
		if (empty($auth_approve))
		{
			$auth_approve[0]['a_manage_kb'] = array();
		}
		$auth = 'kb_m_approve';

		$has_permission = $this->check_permission($auth, $type_data['article_category_id']);
		$users = array_merge($auth_approve[0]['a_manage_kb'], $has_permission);
		$users = array_unique($users);

		return $this->check_user_notification_options($users, $options);
	}

	/**
	 * Users needed to query before this notification can be displayed
	 *
	 * @return array Array of user_ids
	 */
	public function users_to_query(): array
	{
		return array($this->get_data('author_id'));
	}

	/**
	 * Get the user's avatar
	 *
	 * @return string
	 */
	public function get_avatar(): string
	{
		return $this->user_loader->get_avatar($this->get_data('author_id'), false, true);
	}

	/**
	 * Get the reason for this notification.
	 *
	 * @return string  The notification's reason
	 */
	//public function get_reason()
	//{
	//		return $this->language->lang('NOTIFICATION_NEED_APPROVAL');
	//}

	/**
	 * Get the HTML formatted title of this notification
	 *
	 * @return array|string
	 */
	public function get_title()
	{
		$username = $this->user_loader->get_username($this->get_data('author_id'), 'no_profile');
		return $this->language->lang('NOTIFICATION_NEED_APPROVAL', $username);
	}

	/**
	 * Get the url to this item
	 *
	 * @return string URL
	 */
	public function get_url(): string
	{
		return $this->helper->route('sheer_knowledgebase_article', array(
			'k' => $this->get_data('item_id'),
		));
	}

	/**
	 * Get email template
	 *
	 * @return string
	 */
	public function get_email_template(): string
	{
		return '@sheer_knowledgebase/need_approve';
	}

	/**
	 * Get email template variables
	 *
	 * @return array
	 */
	public function get_email_template_variables(): array
	{
		$username = $this->user_loader->get_username($this->get_data('author_id'), 'username');
		return array(
			//'USERNAME'			=> htmlspecialchars_decode($this->user->data['username']),
			'ARTICLE_TITLE'  => htmlspecialchars_decode(censor_text($this->get_data('title'))),
			'POSTER_NAME'    => htmlspecialchars_decode($username),
			'U_VIEW_ARTICLE' => generate_board_url() . '/knowledgebase/article?k=' . $this->get_data('item_id'),
		);
	}

	/**
	 * Get the HTML formatted reference of the notification
	 *
	 * @return string
	 */
	public function get_reference(): string
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
	public function trim_user_ary(array $users): array
	{
		if (count($users) > 4)
		{
			array_splice($users, 3);
		}
		return $users;
	}

	/**
	 * Function for preparing the data for insertion in an SQL query
	 * (The service handles' insertion)
	 *
	 * @param array $type_data       Data from insert_need_approval
	 * @param array $pre_create_data Data from pre_create_insert_array()
	 *
	 * @return void Array of data ready to be inserted into the database
	 */
	public function create_insert_array($type_data, $pre_create_data = array())
	{
		$this->set_data('author_id', $type_data['author_id']);
		$this->set_data('title', $type_data['title']);
		$this->set_data('item_id', $type_data['item_id']);
		$this->set_data('article_category_id', $type_data['article_category_id']);

		parent::create_insert_array($type_data, $pre_create_data);
	}
}
