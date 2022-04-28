<?php

/**
 *
 * Knowledge base. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2017, Sheer
 * @license       GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace sheer\knowledgebase\event;

/**
 * @ignore
 */
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class listener implements EventSubscriberInterface
{
	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\config\db_text */
	protected $config_text;

	/** @var \phpbb\controller\helper */
	protected $helper;

	/* @var \phpbb\language\language */
	protected $language;

	/** @var \phpbb\auth\auth */
	protected $auth;

	/** @var \phpbb\template\template */
	protected $template;

	/* @var string */
	protected $php_ext;

	/**
	 * Constructor
	 *
	 * @param \phpbb\config\config     $config
	 * @param \phpbb\config\db_text    $config_text
	 * @param \phpbb\controller\helper $helper
	 * @param \phpbb\language\language $language
	 * @param \phpbb\auth\auth         $auth
	 * @param \phpbb\template\template $template
	 * @param string                   $php_ext
	 */
	public function __construct(
		\phpbb\config\config $config,
		\phpbb\config\db_text $config_text,
		\phpbb\controller\helper $helper,
		\phpbb\language\language $language,
		\phpbb\auth\auth $auth,
		\phpbb\template\template $template,
		$php_ext
	)
	{
		$this->config = $config;
		$this->config_text = $config_text;
		$this->helper = $helper;
		$this->language = $language;
		$this->auth = $auth;
		$this->template = $template;
		$this->php_ext = $php_ext;
	}

	public static function getSubscribedEvents()
	{
		return [
			'core.user_setup'                    => 'load_language_on_setup',
			'core.page_header'                   => 'add_page_header_link',
			'core.viewonline_overwrite_location' => 'viewonline_page',
			'core.permissions'                   => 'add_permission',
			'core.display_forums_modify_template_vars' => 'display_forums_modify_template_vars',
			'core.viewforum_modify_topicrow' => 'viewforum_modify_topicrow',
		];
	}

	/**
	 * Load common language files during user setup
	 *
	 * @param \phpbb\event\data	$event	Event object
	 */
	public function load_language_on_setup($event)
	{
		$lang_set_ext = $event['lang_set_ext'];
		$lang_set_ext[] = array(
			'ext_name' => 'sheer/knowledgebase',
			'lang_set' => 'knowledgebase_lng',
		);
		$event['lang_set_ext'] = $lang_set_ext;
	}

	/**
	 * Add a link to the controller in the forum navbar
	 */
	public function add_page_header_link($event)
	{
		$this->template->assign_vars([
			'KB_FONT_ICON' => $this->config['kb_font_icon'],
			//      'S_KNOWLEDGEBASE_LINK_ENABLED' => !empty($this->config['kbase_enable']) && !empty($this->config['kbase_header_link']) && $this->auth->acl_get('u_manage_kb'),
			//      'U_LIBRARY' => $this->helper->route('sheer_knowledgebase_index')
			'U_LIBRARY'    => ($this->auth->acl_get('u_kb_view') || $this->auth->acl_get('a_manage_kb')) ? $this->helper->route('sheer_knowledgebase_index') : '',
		]);
	}

	/**
	 * Show users viewing Knowledge Base page on the Who Is Online page
	 *
	 * @param \phpbb\event\data	$event	Event object
	 */
	public function viewonline_page($event)
	{
		if ($event['on_page'][1] === 'app' && strrpos($event['row']['session_page'], 'app.' . $this->php_ext . '/knowledgebase') === 0)
		{
			$event['location'] = $this->language->lang('LIBRARY');
			$event['location_url'] = $this->helper->route('sheer_knowledgebase_index', ['name' => 'index']);
		}
	}

	/**
	 * Modifies the names of the forums on index
	 *
	 * @param \phpbb\event\data	$event	Event object
	 */
	public function display_forums_modify_template_vars($event)
	{
		$forum_row = $event['forum_row'];
		if (($this->config['kb_anounce']) &&
			($forum_row['FORUM_ID'] == $this->config['kb_forum_id']) &&
			(!empty($this->config_text->get('kb_forum_prefix'))))
		{
			$forum_row['FORUM_NAME'] = htmlspecialchars_decode($this->config_text->get('kb_forum_prefix'), ENT_HTML5) . ' ' . $forum_row['FORUM_NAME'];
		}
		$event['forum_row'] = $forum_row;
	}

	/**
	 * Modifies the names of the topics
	 *
	 * @param \phpbb\event\data	$event	Event object
	 */
	public function viewforum_modify_topicrow($event)
	{
		$topic_row = $event['topic_row'];
		if (($this->config['kb_anounce']) &&
			($topic_row['FORUM_ID'] == $this->config['kb_forum_id']) &&
			(!empty($this->config_text->get('kb_topic_prefix'))))
		{
			$topic_row['TOPIC_TITLE'] = htmlspecialchars_decode($this->config_text->get('kb_topic_prefix'), ENT_HTML5) . ' ' . $topic_row['TOPIC_TITLE'];
		}
		$event['topic_row'] = $topic_row;
	}

	/**
	 * Add permissions to the ACP -> Permissions settings page
	 * This is where permissions are assigned language keys and
	 * categories (where they will appear in the Permissions table):
	 * actions|content|forums|misc|permissions|pm|polls|post
	 * post_actions|posting|profile|settings|topic_actions|user_group
	 *
	 * Developers note: To control access to ACP, MCP and UCP modules, you
	 * must assign your permissions in your module_info.php file. For example,
	 * to allow only users with the a_new_sutrus_clubmanagement permission
	 * access to your ACP module, you would set this in your acp/main_info.php:
	 *    'auth' => 'ext_sheer/knowledgebase && acl_a_board && acl_a_manage_kb'
	 *
	 * @param \phpbb\event\data	$event	Event object
	 */
	public function add_permission($event)
	{
		$permissions = $event['permissions'];
		$categories = $event['categories'];
		$permissions['a_manage_kb'] = array('lang' => 'ACL_A_MANAGE_KB', 'cat' => 'knowledgebase');
		$permissions['u_kb_view'] = array('lang' => 'ACL_U_KB_VIEW', 'cat' => 'knowledgebase');
		$event['permissions'] = $permissions;
		$event['categories'] = array_merge($categories, array('knowledgebase' => 'KNOWLEDGEBASE'));
	}
}
