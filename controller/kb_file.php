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

use phpbb\db\driver\driver_interface;
use phpbb\extension\manager;
use phpbb\language\language;
use phpbb\request\request_interface;
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

	/** @var string */
	protected string $attachments_table;

	/**
	 * Constructor
	 *
	 * @param driver_interface  $db
	 * @param language          $language
	 * @param request_interface $request
	 * @param manager           $ext_manager
	 * @param string            $attachments_table
	 */
	public function __construct(
		driver_interface $db, language $language, request_interface $request, manager $ext_manager, string $attachments_table
	)
	{
		$this->db = $db;
		$this->language = $language;
		$this->request = $request;
		$this->ext_manager = $ext_manager;
		$this->attachments_table = $attachments_table;
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

		$sql = 'SELECT attach_id, is_orphan, physical_filename, real_filename, extension, mimetype, filesize, filetime
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
