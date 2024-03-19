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

namespace sheer\knowledgebase;

class ext extends \phpbb\extension\base
{
	/**
	 * Check if extension can be enabled
	 *
	 * @return bool|array True if enableable, false (or an array of error messages) if not.
	 */
	public function is_enableable()
	{
		$phpbb_ver = '3.3.0';
		$php_ver = '7.4';
		$config = $this->container->get('config');
		$enableable = phpbb_version_compare($config['version'], $phpbb_ver, '>=') && version_compare(PHP_VERSION, $php_ver, '>=');

		if (!$enableable)
		{
			// Import my extension's language file
			$language = $this->container->get('language');
			$language->add_lang('install_failed', 'sheer/knowledgebase');

			// Return message
			return [$language->lang('INSTALL_FAILED_VERSION', $phpbb_ver, $config['version'], $php_ver, PHP_VERSION)];
		}

		// Return the boolean result of the test, either true (or false for phpBB 3.2 and 3.1)
		return true;
	}

	/**
	 * Enable notifications for the extension
	 *
	 * @param mixed $old_state        The return value of the previous call
	 *                                of this method, or false on the first call
	 * @return    bool|string         Returns false after last step, otherwise
	 *                                temporary state which is passed as an
	 *                                argument to the next step
	 */
	function enable_step($old_state)
	{
		if ($old_state === false)
		{
			// Enable notifications
			$phpbb_notifications = $this->container->get('notification_manager');
			$phpbb_notifications->enable_notifications('sheer.knowledgebase.notification.type.need_approval');
			$phpbb_notifications->enable_notifications('sheer.knowledgebase.notification.type.approve');
			$phpbb_notifications->enable_notifications('sheer.knowledgebase.notification.type.disapprove');

			return 'notifications';
		}

		return parent::enable_step($old_state);
	}

	/**
	 * Disable notifications for the extension
	 *
	 * @param mixed $old_state        The return value of the previous call
	 *                                of this method, or false on the first call
	 * @return    false|string        Returns false after last step, otherwise
	 *                                temporary state which is passed as an
	 *                                argument to the next step
	 */
	function disable_step($old_state)
	{
		if ($old_state === false)
		{
			// Disable notifications
			$phpbb_notifications = $this->container->get('notification_manager');
			$phpbb_notifications->disable_notifications('sheer.knowledgebase.notification.type.need_approval');
			$phpbb_notifications->disable_notifications('sheer.knowledgebase.notification.type.approve');
			$phpbb_notifications->disable_notifications('sheer.knowledgebase.notification.type.disapprove');

			return 'notifications';
		}

		return parent::disable_step($old_state);
	}

	/**
	 * Purge notifications for the extension
	 *
	 * @param mixed $old_state        The return value of the previous call
	 *                                of this method, or false on the first call
	 * @return    bool|string         Returns false after last step, otherwise
	 *                                temporary state which is passed as an
	 *                                argument to the next step
	 */
	function purge_step($old_state)
	{
		if ($old_state === false)
		{
			// Purge notifications
			$phpbb_notifications = $this->container->get('notification_manager');
			$phpbb_notifications->purge_notifications('sheer.knowledgebase.notification.type.need_approval');
			$phpbb_notifications->purge_notifications('sheer.knowledgebase.notification.type.approve');
			$phpbb_notifications->purge_notifications('sheer.knowledgebase.notification.type.disapprove');

			return 'notifications';
		}

		return parent::purge_step($old_state);
	}
}
