<?php
/**
 * @package     TjGoPhish
 * @subpackage  com_tjgophish
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Language\Text;

use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Factory;

/**
 * TJGOPHISH - Group View
 *
 * @since  1.0.0
 */
class TjGoPhishViewGroup extends HtmlView
{
	protected $form;

	protected $item;

	/**
	 * Display the Group view
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  void
	 */
	public function display($tpl = null)
	{
		$user = Factory::getUser();
		$app  = Factory::getApplication();
		JLoader::import("/components/com_subusers/includes/rbacl", JPATH_ADMINISTRATOR);
		
		// DPE hack to check the RBACL permission
		$this->createGroup = RBACL::check($user->id, 'com_cluster', 'core.createGroup', 'com_tjgophish');

		if (!$user->authorise('core.manageall', 'com_cluster') && !$this->createGroup)
		{
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');

			return;
		}

		$jInput = $app->input;

		// Get the Data
		$this->form = $this->get('Form');
		$this->item = $this->get('Item');
		$this->state = $this->get('State');

		$this->user      = Factory::getUser();

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			//throw new Exception(implode("\n", $errors), 500);
		}

		// Set the document
		$this->setDocuments();

		// Display the template
		parent::display($tpl);
	}

	/**
	 * Method to set up the document properties
	 *
	 * @return void
	 */
	protected function setDocuments()
	{
		$isNew = (isset($this->item->id) && !empty($this->item->id)) ? 0 : 1;
		$document = Factory::getDocument();
		$document->setTitle($isNew ? Text::_('COM_TJGOPHISH_GROUP_CREATE') : Text::_('COM_TJGOPHISH_GROUP_EDIT'));
	}
}
