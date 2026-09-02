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
use phpbb\db\driver\driver_interface;
use phpbb\extension\manager;
use phpbb\language\language;
use phpbb\request\request_interface;
use phpbb\user;
use sheer\knowledgebase\inc\functions_kb;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class kb_file
{
	/** @var \phpbb\db\driver\driver_interface */
	protected driver_interface $db;

	/** @var \phpbb\language\language */
	protected language $language;

	/** @var \phpbb\request\request_interface */
	protected request_interface $request;

	/** @var \phpbb\extension\manager */
	protected manager $ext_manager;

	/** @var \phpbb\auth\auth */
	protected auth $auth;

	/** @var \phpbb\user */
	protected user $user;

	/** @var \sheer\knowledgebase\inc\functions_kb */
	protected functions_kb $kb;

	/** @var string */
	protected string $attachments_table;

	/** @var string */
	protected string $articles_table;

	/**
	 * Constructor
	 *
	 * @param driver_interface  $db
	 * @param language          $language
	 * @param request_interface $request
	 * @param manager           $ext_manager
	 * @param auth              $auth
	 * @param user              $user
	 * @param functions_kb      $kb
	 * @param string            $attachments_table
	 * @param string            $articles_table
	 */
	public function __construct(
		driver_interface $db, language $language, request_interface $request, manager $ext_manager,
		auth $auth, user $user, functions_kb $kb, string $attachments_table, string $articles_table
	)
	{
		$this->db = $db;
		$this->language = $language;
		$this->request = $request;
		$this->ext_manager = $ext_manager;
		$this->auth = $auth;
		$this->user = $user;
		$this->kb = $kb;
		$this->attachments_table = $attachments_table;
		$this->articles_table = $articles_table;
	}

	/**
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function main(): Response
	{
		$attach_id = (int) $this->request->variable('id', 0);
		$thumbnail = $this->request->variable('t', false);

		$this->language->add_lang('viewtopic');

		if (!$attach_id)
		{
			send_status_line(404, 'Not Found');
			trigger_error('NO_ATTACHMENT_SELECTED');
		}

		if (!$this->auth->acl_get('u_kb_view') && !$this->auth->acl_get('a_manage_kb'))
		{
			send_status_line(403, 'Forbidden');
			trigger_error('NOT_AUTHORISED');
		}

		$sql = 'SELECT attach_id, article_id, poster_id, is_orphan, physical_filename, real_filename, extension, mimetype, filesize, filetime
			FROM ' . $this->attachments_table . '
			WHERE attach_id = ' . $attach_id;
		$result = $this->db->sql_query($sql);
		$attachment = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		if (!$attachment)
		{
			send_status_line(404, 'Not Found');
			trigger_error('ERROR_NO_ATTACHMENT');
		}

		if ($attachment['article_id'])
		{
			// Attached to a saved article: same view/approval gate the article itself is behind.
			$sql = 'SELECT article_category_id, approved
				FROM ' . $this->articles_table . '
				WHERE article_id = ' . (int) $attachment['article_id'];
			$result = $this->db->sql_query($sql);
			$article = $this->db->sql_fetchrow($result);
			$this->db->sql_freeresult($result);

			if (!$article || (!$article['approved'] && !($this->auth->acl_get('a_manage_kb') || $this->kb->acl_kb_get($article['article_category_id'], 'kb_m_approve'))))
			{
				send_status_line(403, 'Forbidden');
				trigger_error('NOT_AUTHORISED');
			}
		}
		else if ((int) $attachment['poster_id'] !== (int) $this->user->data['user_id'])
		{
			// Orphaned attachment (uploaded but not yet attached to a saved article): only its own uploader may preview it.
			send_status_line(403, 'Forbidden');
			trigger_error('NOT_AUTHORISED');
		}

		$attachment['physical_filename'] = utf8_basename($attachment['physical_filename']);

		if ($thumbnail)
		{
			$attachment['physical_filename'] = 'thumb_' . $attachment['physical_filename'];
		}

		$response = new Response();
		// Content-type header
		$response->headers->set('Content-Type', $attachment['mimetype']);
		// Display file types in browser and force download for others
		if (str_contains($attachment['mimetype'], 'image'))
		{
			$disposition = $response->headers->makeDisposition(
				ResponseHeaderBag::DISPOSITION_INLINE,
				$attachment['real_filename'],
				$this->filenameFallback($attachment['real_filename'])
			);
		}
		else
		{
			$disposition = $response->headers->makeDisposition(
				ResponseHeaderBag::DISPOSITION_ATTACHMENT,
				$attachment['real_filename'],
				$this->filenameFallback($attachment['real_filename'])
			);
		}
		$response->headers->set('Content-Disposition', $disposition);

		// Set expires header for browser cache
		$time = new \Datetime();
		$response->setExpires($time->modify('+1 month'));

		$upload_file = $this->ext_manager->get_extension_path('sheer/knowledgebase', true) . 'files/' . $attachment['physical_filename'];
		$response->setContent(file_get_contents($upload_file));

		return $response;
	}

	/**
	 * Remove non valid characters
	 * https://github.com/symfony/http-foundation/commit/c7df9082ee7205548a97031683bc6550b5dc9551
	 */
	/**
	 * @param $filename
	 * @return bool|string
	 */
	protected function filenameFallback($filename): bool|string
	{
		$filename = preg_replace(['/[^\x20-\x7e]/', '/%/', '/\//', '/\\\/'], '', $filename);
		return (!empty($filename)) ?: 'File';
	}
}
