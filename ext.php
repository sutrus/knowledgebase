<?php

/**
 *
 * Knowledge base. An extension for the phpBB Forum Software package.
 *
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
		return $enableable;
	}

	/**
	 * Overwrite enable_step to enable notifications
	 * before any included migrations are installed.
	 *
	 * @param mixed $old_state State returned by previous call of this method
	 * @return bool|string Returns false after last step, otherwise temporary state
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
	 * Overwrite disable_step to disable notifications
	 * before the extension is disabled.
	 *
	 * @param mixed $old_state State returned by previous call of this method
	 * @return false|string Returns false after last step, otherwise temporary state
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
	 * Overwrite purge_step to purge notifications before
	 * any included and installed migrations are reverted.
	 *
	 * @param mixed $old_state State returned by previous call of this method
	 * @return bool|string Returns false after last step, otherwise temporary state
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
