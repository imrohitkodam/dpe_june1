<?php
/**
 * @package    Com_Cluster
 *
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2018 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Object\CMSObject;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Language\Text;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView;

/**
 * View to edit
 *
 * @since  1.0.0
 */
class ClusterViewClusterUser extends HtmlView
{
	/**
	 * The Form object
	 *
	 * @var  Form
	 */
	protected $form;

	/**
	 * The dashboard helper
	 *
	 * @var  object
	 */
	protected $clusterHelper;

	/**
	 * The active item
	 *
	 * @var  object
	 */
	protected $item;

	/**
	 * The model state
	 *
	 * @var  object
	 */
	protected $state;

	/**
	 * The actions the user is authorised to perform
	 *
	 * @var  CMSObject
	 */
	protected $canDo;

	/**
	 * Display the view
	 *
	 * @param   string  $tpl  Template name
	 *
	 * @return void
	 *
	 * @throws Exception
	 */
	public function display($tpl = null)
	{
		$this->state = $this->get('State');
		$this->item  = $this->get('Item');
		$this->form  = $this->get('Form');
		$this->input = Factory::getApplication()->input;

		$this->canDo = ContentHelper::getActions('com_cluster', 'clusteruser', $this->item->id);

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors), 500);
		}

		$this->addToolbar();

		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	protected function addToolbar()
	{
		$user       = Factory::getUser();
		$isNew      = ($this->item->id == 0);

		$this->clusterHelper = new ClusterHelper;
		$checkedOut = $this->isCheckedOut($user->id);

		// Built the actions for new and existing records.
		$layout = Factory::getApplication()->input->get("layout");

		$this->sidebar = JHtmlSidebar::render();

		// For new records, check the create permission.
		if ($layout != "default")
		{
			Factory::getApplication()->input->set('hidemainmenu', true);

			ToolbarHelper::title(
				Text::_('COM_CLUSTER_PAGE_' . ($checkedOut ? 'VIEW_USER' : ($isNew ? 'ADD_USER' : 'EDIT_USER'))),
				'pencil-2 cluster-add'
			);

			if ($isNew)
			{
				ToolbarHelper::save('clusteruser.save');
				ToolbarHelper::cancel('clusteruser.cancel');
			}
			else
			{
				$itemEditable = $this->isEditable($this->canDo, $user->id);
				$this->canSave($checkedOut, $itemEditable);
				ToolbarHelper::cancel('clusteruser.cancel', 'JTOOLBAR_CLOSE');
			}
		}
		else
		{
			ToolbarHelper::title(
				Text::_('COM_CLUSTER_PAGE_VIEW_CLUSTER_USER')
			);

			ClusterHelper::addSubmenu('clusteruser');

			$this->sidebar = JHtmlSidebar::render();
		}

		ToolbarHelper::divider();
	}

	/**
	 * Can't save the record if it's checked out and editable
	 *
	 * @param   boolean  $checkedOut    Checked Out
	 *
	 * @param   boolean  $itemEditable  Item editable
	 *
	 * @return void
	 */
	protected function canSave($checkedOut, $itemEditable)
	{
		if (!$checkedOut && $itemEditable)
		{
			ToolbarHelper::save('clusteruser.save');
		}
	}

	/**
	 * Is editable
	 *
	 * @param   Object   $canDo   Checked Out
	 *
	 * @param   integer  $userId  User ID
	 *
	 * @return boolean
	 */
	protected function isEditable($canDo, $userId)
	{
		// Since it's an existing record, check the edit permission, or fall back to edit own if the owner.
		return $canDo->get('core.edit') || ($canDo->get('core.edit.own') && $this->item->created_by == $userId);
	}

	/**
	 * Is Checked Out
	 *
	 * @param   integer  $userId  User ID
	 *
	 * @return boolean
	 */
	protected function isCheckedOut($userId)
	{
		return !($this->item->checked_out == 0 || $this->item->checked_out == $userId);
	}
}
