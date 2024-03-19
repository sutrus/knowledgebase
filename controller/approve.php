<?php

/**
 *
 * Knowledge base. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2017, Sheer
 * @license       GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace sheer\knowledgebase\controller;

use phpbb\auth\auth;
use phpbb\cache\driver\driver_interface as cache;
use phpbb\config\config;
use phpbb\controller\helper;
use phpbb\db\driver\driver_interface;
use phpbb\language\language;
use phpbb\log\log;
use phpbb\notification\manager;
use phpbb\request\request_interface;
use phpbb\template\template;
use phpbb\user;
use RuntimeException;
use sheer\knowledgebase\inc\functions_kb;
use sheer\knowledgebase\search\kb_search_backend_factory;

class approve
{
	/** @var \phpbb\db\driver\driver_interface */
	protected driver_interface $db;

	/** @var \phpbb\config\config */
	protected config $config;

	/** @var \phpbb\controller\helper */
	protected helper $helper;

	/** @var \phpbb\language\language */
	protected language $language;

	/** @var \phpbb\auth\auth */
	protected auth $auth;

	/** @var \phpbb\request\request_interface */
	protected request_interface $request;

	/** @var \phpbb\template\template */
	protected template $template;

	/** @var \phpbb\user */
	protected user $user;

	/** @var \phpbb\cache\driver\driver_interface */
	protected cache $cache;

	/** @var \phpbb\log\log */
	protected log $log;

	/** @var \phpbb\notification\manager */
	protected manager $notification_manager;

	/** @var \sheer\knowledgebase\inc\functions_kb */
	protected functions_kb $kb;

	/** @var \sheer\knowledgebase\search\kb_search_backend_factory */
	protected kb_search_backend_factory $search_factory;

	/** @var string */
	protected string $phpbb_root_path;

	/** @var string */
	protected string $php_ext;

	/** @var string */
	protected string $articles_table;

	/**
	 * Constructor
	 *
	 * @param driver_interface          $db
	 * @param config                    $config
	 * @param helper                    $helper
	 * @param language                  $language
	 * @param auth                      $auth
	 * @param request_interface         $request
	 * @param template                  $template
	 * @param user                      $user
	 * @param cache                     $cache
	 * @param log                       $log
	 * @param manager                   $notification_manager
	 * @param functions_kb              $kb
	 * @param kb_search_backend_factory $search_factory
	 * @param string                    $phpbb_root_path
	 * @param string                    $php_ext
	 * @param string                    $articles_table
	 */
	public function __construct(
		driver_interface $db, config $config, helper $helper, language $language, auth $auth,
		request_interface $request, template $template, user $user, cache $cache, log $log,
		manager $notification_manager, functions_kb $kb, kb_search_backend_factory $search_factory,
		string $phpbb_root_path, string $php_ext, string $articles_table
	)
	{
		$this->db = $db;
		$this->config = $config;
		$this->helper = $helper;
		$this->language = $language;
		$this->auth = $auth;
		$this->request = $request;
		$this->template = $template;
		$this->user = $user;
		$this->cache = $cache;
		$this->log = $log;
		$this->notification_manager = $notification_manager;
		$this->kb = $kb;
		$this->search_factory = $search_factory;
		$this->phpbb_root_path = $phpbb_root_path;
		$this->php_ext = $php_ext;
		$this->articles_table = $articles_table;
	}

	/**
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function approve_article(): \Symfony\Component\HttpFoundation\Response
	{
		// If not logged in
		$dd = $this->user->data;
		if ($this->user->data['user_id'] == ANONYMOUS)
		{
			$mode = '';
			login_box('', (($this->language->is_set('LOGIN_EXPLAIN_' . strtoupper($mode))) ? $this->language->lang('LOGIN_EXPLAIN_' . strtoupper($mode)) : $this->language->lang('LOGIN_EXPLAIN_APPROVE')));
		}

		$art_id = $this->request->variable('id', 0);
		$approve = $this->request->variable('approve', false);
		$disapprove = $this->request->variable('disapprove', false);

		$kb_article_info = $this->kb->get_kb_article_info($art_id);
		$kb_category_info = $this->kb->get_cat_info($kb_article_info['article_category_id']);
		$category_name = $kb_category_info['category_name'];

		$redirect = $this->helper->route('sheer_knowledgebase_category', ['id' => $kb_article_info['article_category_id']]);

		if ($this->user->data['user_type'] != USER_FOUNDER &&
			!$this->kb->acl_kb_get($kb_article_info['article_category_id'], 'kb_m_approve') &&
			!$this->auth->acl_get('a_manage_kb')
		)
		{
			trigger_error('RULES_KB_APPROVE_MOD_CANNOT');
		}

		if ($kb_article_info['approved'])
		{
			trigger_error('NO_NEED_APPROVE');
		}

		if ($approve)
		{
			include_once($this->phpbb_root_path . 'includes/functions_posting.' . $this->php_ext);

			// Select which method we'll use to obtain the post_id or topic_id information
			try
			{
				$kb_search = $this->search_factory->get_active();
			}
			catch (RuntimeException $e)
			{
				if (str_starts_with($e->getMessage(), 'No service found'))
				{
					trigger_error('NO_SUCH_SEARCH_MODULE');
				}
				else
				{
					throw $e;
				}
			}

			$sql = 'UPDATE ' . $this->articles_table . ' SET approved = 1 WHERE article_id = ' . (int) $art_id;
			$this->db->sql_query($sql);

			if (isset($kb_search))
			{
				// Add search index
				$this->cache->purge();
				$kb_search->index('add', (int) $art_id, $kb_article_info['article_body'], $kb_article_info['article_title'], $kb_article_info['article_description'], (int) $kb_article_info['author']);
			}

			if (!empty($this->config['kb_forum_id']) && $this->config['kb_anounce'])
			{
				$this->kb->submit_article($kb_article_info['article_category_id'], $this->config['kb_forum_id'], $kb_article_info['article_title'], $kb_article_info['article_description'], $kb_article_info['author'], $category_name, $art_id);
			}
		}
		else if ($disapprove)
		{
			$sql = 'DELETE FROM ' . $this->articles_table . ' WHERE article_id = ' . (int) $art_id;
			$this->db->sql_query($sql);
		}

		if ($approve || $disapprove)
		{
			// add log
			$log_type = ($approve) ? 'LOG_LIBRARY_APPROVED_ARTICLE' : 'LOG_LIBRARY_REJECTED_ARTICLE';
			$this->log->add('admin', $this->user->data['user_id'], $this->user->data['user_ip'], $log_type, time(), [$kb_article_info['article_title'], $kb_category_info['category_name'], $kb_article_info['author']]);

			// Delete notification - moderator
			$notification_data = [
				'author_id'           => $this->user->data['user_id'],
				'title'               => $kb_article_info['article_title'],
				'article_category_id' => $kb_article_info['article_category_id'],
				'item_id'             => $art_id,
			];
			$this->notification_manager->delete_notifications('sheer.knowledgebase.notification.type.need_approval', $notification_data);

			// Send notification - author
			$message = ($approve) ? 'ARTICLE_APPROVED_SUCESS' : 'ARTICLE_DISAPPROVED_SUCESS';
			$notification_type = ($approve) ? 'sheer.knowledgebase.notification.type.approve' : 'sheer.knowledgebase.notification.type.disapprove';
			$notification_data = [
				'author_id' => $kb_article_info['author_id'],
				'title'     => $kb_article_info['article_title'],
				'moderator' => $this->user->data['user_id'],
				'item_id'   => $art_id,
			];
			$this->notification_manager->add_notifications($notification_type, $notification_data);

			meta_refresh(2, $redirect);
			trigger_error($message);
		}

		$this->template->assign_vars([
			'ARTICLE_AUTHOR'      => $kb_article_info['author'],
			'ARTICLE_TITLE'       => $kb_article_info['article_title'],
			'ARTICLE_DESCRIPTION' => $kb_article_info['article_description'],
			'ARTICLE_CATEGORY'    => $kb_category_info['category_name'],
			'S_ACTION'            => $this->helper->route('sheer_knowledgebase_approve', ['id' => $art_id]),
		]);

		return $this->helper->render('@sheer_knowledgebase/kb_approve_body.html', ($this->language->lang('LIBRARY') . ' &raquo; ' . $this->language->lang('APPROVE')));
	}
}
