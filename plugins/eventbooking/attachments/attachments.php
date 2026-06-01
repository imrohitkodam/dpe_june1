<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;

class plgEventbookingAttachments extends CMSPlugin implements SubscriberInterface
{
	use RADEventResult;

	/**
	 * Application object.
	 *
	 * @var    \Joomla\CMS\Application\CMSApplication
	 */
	protected $app;

	/**
	 * Database object.
	 *
	 * @var    \Joomla\Database\DatabaseDriver
	 */
	protected $db;

	/**
	 * @return array
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			'onAfterSaveEvent' => 'onAfterSaveEvent',
			'onEditEvent'      => 'onEditEvent',
		];
	}

	/**
	 * Render setting form
	 *
	 * @param   Event  $eventObj
	 *
	 * @return void
	 */
	public function onEditEvent(Event $eventObj): void
	{
		/* @var EventbookingTableEvent $row */
		[$row] = array_values($eventObj->getArguments());

		if (!$this->canRun($row))
		{
			return;
		}

		$result = [
			'title' => Text::_('EB_ATTACHMENTS'),
			'form'  => $this->drawSettingForm($row),
		];

		$this->addResult($eventObj, $result);
	}

	/**
	 * Store setting into database, in this case, use params field of plans table
	 *
	 * @param   Event  $eventObj
	 */
	public function onAfterSaveEvent(Event $eventObj): void
	{
		/**
		 * @var EventbookingTableEvent $row
		 * @var array                  $data
		 * @var bool                   $isNew
		 */
		[$row, $data, $isNew] = array_values($eventObj->getArguments());

		if (empty($data['attachments_plugin_rendered']))
		{
			return;
		}

		$app         = $this->app;
		$config      = EventbookingHelper::getConfig();
		$attachments = $app->getInput()->files->get('attachments', [], 'raw');

		$pathUpload = JPATH_ROOT . '/' . ($config->attachments_path ?: 'media/com_eventbooking');

		$allowedExtensions = $config->attachment_file_types;

		if (!$allowedExtensions)
		{
			$allowedExtensions = 'doc|docx|ppt|pptx|pdf|zip|rar|bmp|gif|jpg|jepg|png|swf|zipx';
		}

		$allowedExtensions = explode('|', $allowedExtensions);
		$allowedExtensions = array_map('trim', $allowedExtensions);
		$allowedExtensions = array_map('strtolower', $allowedExtensions);

		$attachmentFiles = [];

		foreach ($attachments as $file)
		{
			$attachment = $file['attachment_file'];

			if ($attachment['name'])
			{
				$fileName = $attachment['name'];
				$fileExt  = File::getExt($fileName);

				if (in_array(strtolower($fileExt), $allowedExtensions))
				{
					$fileName = File::makeSafe($fileName);

					if ($app->isClient('administrator'))
					{
						File::upload($attachment['tmp_name'], $pathUpload . '/' . $fileName, false, true);
					}
					else
					{
						File::upload($attachment['tmp_name'], $pathUpload . '/' . $fileName);
					}

					$attachmentFiles[] = $fileName;
				}
			}
		}

		if (isset($data['existing_attachments']))
		{
			$attachmentFiles = array_merge($attachmentFiles, array_filter($data['existing_attachments']));
		}

		$row->attachment = implode('|', $attachmentFiles);
		$row->store();
	}

	/**
	 * Display form allows users to change settings on subscription plan add/edit screen
	 *
	 * @param   EventbookingTableEvent  $row
	 *
	 * @return string
	 */
	private function drawSettingForm($row)
	{
		$config = EventbookingHelper::getConfig();
		$form   = Form::getInstance('attachments', JPATH_ROOT . '/plugins/eventbooking/attachments/form/attachments.xml');

		// List existing attachments here
		$layoutData = [
			'existingAttachmentsList' => EventbookingHelper::callOverridableHelperMethod(
				'Helper',
				'attachmentList',
				[explode('|', $row->attachment ?? ''), $config, 'existing_attachments']
			),
			'form'                    => $form,
		];

		return EventbookingHelperHtml::loadCommonLayout('plugins/attachments.php', $layoutData);
	}

	/**
	 * Method to check to see whether the plugin should run
	 *
	 * @param   EventbookingTableEvent  $row
	 *
	 * @return bool
	 */
	private function canRun($row): bool
	{
		if ($this->app->isClient('site') && !$this->params->get('show_on_frontend'))
		{
			return false;
		}

		return true;
	}
}
