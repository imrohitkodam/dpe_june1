<?php
/**
 * @version    SVN: <svn_id>
 * @package    Mailcatcher
 * @author     Solidres Team <extensions@solidres.com>
 * @copyright  Copyright (C) 2016 - 2017 Solidres. All Rights Reserved.
 * @license    GNU General Public License version 3 or later.
 */

defined('_JEXEC') or die;
namespace MailCatcher;

/**
 * MailcatcherControllerDashboard
 *
 * @package     Mailcatcher
 * @subpackage  site
 * @since       2.2
 */
class Mailer extends \JMail
{
	protected static $instances;

	/**
	 * loadChartData
	 *
	 * @param   String  $id          category name
	 * @param   String  $exceptions  category name
	 *
	 * @return void
	 *
	 * @since    1.6
	 */
	public static function getInstance($id = 'Joomla', $exceptions = true)
	{
		if (empty(self::$instances[$id]))
		{
			self::$instances[$id] = new Mailer($exceptions);
		}

		return self::$instances[$id];
	}

	/**
	 * loadChartData
	 *
	 * @return void
	 *
	 * @since    1.6
	 */
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

				\JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_mailcatcher/tables');
				$table = \JTable::getInstance('Mail', 'MailcatcherTable');
				$data  = array(
					'mailer'         => $this->Mailer,
					'sent_from_mail' => $this->From,
					'sent_from_name' => $this->FromName,
					'receivers'      => '[' . join('][', $receivers) . ']',
					'subject'        => $this->Subject,
					'message'        => $this->Body,
					'ip'             => @$_SERVER['REMOTE_ADDR'],
					'referer'        => @$_SERVER['HTTP_REFERER'],
					'created_date'   => \JFactory::getDate()->toSql(),
					'is_html'        => $this->ContentType === 'text/html' ? 1 : 0,
				);

				if (false === $send || $send instanceof \Exception)
				{
					$data['success']       = 0;
					$data['error_message'] = \JText::_($this->ErrorInfo);
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

				if (!empty($attachments))
				{
					\JLoader::import('joomla.filesystem.folder');
					\JLoader::import('joomla.filesystem.file');
					$path  = JPATH_ROOT . '/media/com_mailcatcher/assets/attachments';
					$files = array();

					if (!\JFolder::exists($path))
					{
						\JFolder::create($path, 0755);
					}

					if (!is_file($path . '/.htaccess'))
					{
						\JFile::write($path . '/.htaccess', 'deny from all');
					}

					foreach ($attachments as $i => $attachment)
					{
						$file = $attachments[$i][0];

						if (is_file($file))
						{
							$files[]            = array($file, $attachments[$i][2]);
							$attachments[$i][0] = ltrim(str_replace(JPATH_ROOT, '', $file), '/');
						}
					}

					$data['attachments'] = json_encode($attachments);
				}

				$table->bind($data);

				if ($table->store() && !empty($files))
				{
					$filePath = JPATH_ROOT . '/media/com_mailcatcher/assets/attachments/' . $table->id;

					if (\JFolder::exists($filePath))
					{
						\JFolder::delete($filePath);
					}

					\JFolder::create($filePath, 0755);

					foreach ($files as $file)
					{
						\JFile::copy($file[0], $filePath . '/' . $file[1]);
					}
				}
			}
		}
		catch (\Exception $e)
		{
			\JFactory::getApplication()->enqueueMessage($e->getMessage(), 'warning');
		}

		return $send;
	}
}
