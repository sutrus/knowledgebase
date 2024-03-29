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

use phpbb\config\config;
use phpbb\db\driver\driver_interface;
use phpbb\request\request_interface;

class set_order
{
	/** @var \phpbb\db\driver\driver_interface */
	protected driver_interface $db;

	/** @var \phpbb\config\config */
	protected config $config;

	/** @var \phpbb\request\request_interface */
	protected request_interface $request;

	/** @var string */
	protected string $articles_table;

	/**
	 * Constructor
	 *
	 * @param driver_interface  $db
	 * @param config            $config
	 * @param request_interface $request
	 * @param string            $articles_table
	 */
	public function __construct(driver_interface $db, config $config, request_interface $request, string $articles_table)
	{
		$this->db = $db;
		$this->config = $config;
		$this->request = $request;
		$this->articles_table = $articles_table;
	}

	/**
	 * @return void
	 */
	public function main(): void
	{
		$list_order = $this->request->variable('list_order', '');
		$page = $this->request->variable('page', 0);
		$per_page = $this->config['kb_articles_per_page'];
		$list = explode(',', $list_order);
		$i = 1 + (($page - 1) * $per_page);
		foreach ($list as $id)
		{
			$sql = 'UPDATE ' . $this->articles_table . ' SET display_order = ' . (int) $i . ' WHERE article_id = ' . (int) $id;
			$i++;
			$this->db->sql_query($sql);
		}
	}
}
