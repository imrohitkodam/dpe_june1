<?php
/*------------------------------------------------------------------------
  Mail Catcher - Email logging extension for Joomla
  ------------------------------------------------------------------------
  @Author    Solidres Team
  @Website   https://www.solidres.com
  @Copyright Copyright (C) 2016 - 2019 Solidres. All Rights Reserved.
  @License   GNU General Public License version 3, or later
------------------------------------------------------------------------*/

namespace MailCatcher;

defined('_JEXEC') or die;

use Joomla\CMS\Mail\Mail;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Factory as CMSFactory;
use Exception;
use RuntimeException;

class Mailer extends Mail
{
	public static $instances;

	public function __construct($exceptions = true)
	{
		parent::__construct($exceptions);
	}

	public static function getInstance($id = 'Joomla', $exceptions = true)
	{
		if (empty(self::$instances[$id]))
		{
			self::$instances[$id] = new Mailer($exceptions);
		}

		return self::$instances[$id];
	}

	public function Send()
	{
		$send      = parent::Send();
		$receivers = $this->getToAddresses();

		try
		{
			if (!empty($receivers))
			{
				$cc          = $this->getBccAddresses();
				$bcc         = $this->getBccAddresses();
				$attachments = $this->getAttachments();

				foreach ($receivers as &$receiver)
				{
					$receiver = $receiver[0];
				}

				Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_mailcatcher/tables');

				$table = Table::getInstance('Mail', 'MailcatcherTable');

				// Hack for DPE
				$ipAdresss = '127.0.0.1';

				if (@$_SERVER['REMOTE_ADDR'])
				{
					$ipAdresss = @$_SERVER['REMOTE_ADDR'];
				}

				$data = [
					'mailer'         => $this->Mailer,
					'sent_from_mail' => $this->From,
					'sent_from_name' => $this->FromName,
					'receivers'      => '[' . join('][', $receivers) . ']',
					'subject'        => $this->Subject,
					'message'        => $this->Body,
					'ip'             => $ipAdresss,
					'referer'        => @$_SERVER['HTTP_REFERER'],
					'created_date'   => CMSFactory::getDate()->toSql(),
					'is_html'        => $this->ContentType === 'text/html' ? 1 : 0,
					'unread'         => 1,
					'mime_header'    => $this->MIMEHeader,
					'mime_body'      => $this->MIMEBody,
				];

				if (false === $send || $send instanceof Exception)
				{
					$data['success']       = 0;
					$data['error_message'] = Text::_($this->ErrorInfo);

				}
				else
				{
					$data['success'] = 1;
				}

				if (!empty($cc))
				{
					foreach ($cc as &$c)
					{
						$c = $c[0];
					}

					$data['cc'] = '[' . join('][', $cc) . ']';
				}

				if (!empty($bcc))
				{
					foreach ($bcc as &$bc)
					{
						$bc = $bc[0];
					}

					$data['bcc'] = '[' . join('][', $bcc) . ']';
				}

				$stringFiles = array();

				if (!empty($attachments))
				{
					\JLoader::import('joomla.filesystem.folder');
					\JLoader::import('joomla.filesystem.file');
					$path  = JPATH_ROOT . '/media/com_mailcatcher/assets/attachments';
					$files = array();

					if (!Folder::exists($path))
					{
						Folder::create($path, 0755);
					}

					if (!is_file($path . '/.htaccess'))
					{
						File::write($path . '/.htaccess', 'deny from all');
					}

					foreach ($attachments as $i => $attachment)
					{
						$isFilePath   = $attachments[$i][0];
						$fileBaseName = $attachments[$i][1];

						if (is_file($isFilePath))
						{
							$files[]            = array($isFilePath, $fileBaseName, false);
							$attachments[$i][0] = ltrim(str_replace(JPATH_ROOT, '', $isFilePath), '/');
						}
						elseif ($attachments[$i][5] === true)
						{
							$stringFiles[$fileBaseName] = $isFilePath;
							$files[]                    = array($fileBaseName, $fileBaseName, true);
							$attachments[$i][0]         = $fileBaseName;
						}
					}

					$data['attachments'] = json_encode($attachments);
				}

				if ($table->bind($data) && $table->store() && !empty($files))
				{					
					$filePath = JPATH_ROOT . '/media/com_mailcatcher/assets/attachments/' . $table->id;

					if (Folder::exists($filePath))
					{
						Folder::delete($filePath);
					}

					Folder::create($filePath, 0755);

					foreach ($files as $file)
					{
						if ($file[2])
						{
							if (isset($stringFiles[$file[1]]))
							{
								$buffer = $stringFiles[$file[1]];
								File::write($filePath . '/' . $file[1], $buffer);
							}
						}
						else
						{
							File::copy($file[0], $filePath . '/' . $file[1]);
						}
					}
				}


			}
		}
		catch (RuntimeException $e)
		{
			CMSFactory::getApplication()->enqueueMessage($e->getMessage(), 'warning');
		}

		return $send;
	}
}
