<?php
/**
 * @package     Joomla.Site
 * @subpackage  Com_Advsearch
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (c) 2009-2017 TechJoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license     GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>.
 * @link        http://techjoomla.com.
 */

// No direct access
defined('_JEXEC') or die;

jimport('joomla.application.component.view');
JLoader::import('AdvsearchHelper', JPATH_ROOT . '/components/com_advsearch/helpers/advsearch.php');

/**
 * Class View to edit
 *
 * @since  3.3
 */
class AdvsearchViewCreatesearchindexer extends JViewLegacy
{
	protected $state;

	protected $item;

	protected $form;

	/**
	 * Method to display state item pagination
	 *
	 * @param   string  $tpl  tpl.
	 *
	 * @return    void
	 */
	public function display($tpl = null)
	{
		$this->state = $this->get('State');
		$this->item  = $this->get('Item');
		$this->form  = $this->get('Form');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors));
		}

		$this->addToolbar();
		parent::display($tpl);
	}

	/**
	 * Method to add  page title and tool bar
	 *
	 * @return  void
	 *
	 * @since    1.6
	 */
	public function addToolbar()
	{
		JFactory::getApplication()->input->set('hidemainmenu', true);

		$user  = JFactory::getUser();
		$isNew = ($this->item->id == 0);

		if (isset($this->item->checked_out))
		{
			$checkedOut = !($this->item->checked_out == 0 || $this->item->checked_out == $user->get('id'));
		}
		else
		{
			$checkedOut = false;
		}

		$canDo = AdvsearchHelper::getActions();

		JToolBarHelper::title(JText::_('COM_ADVSEARCH_TITLE_CREATESEARCHINDEXER'), 'createsearchindexer.png');

		// If not checked out, can save the item.
		if (!$checkedOut && ($canDo->get('core.edit') || ($canDo->get('core.create'))))
		{
			JToolBarHelper::apply('createsearchindexer.apply', 'JTOOLBAR_APPLY');
			JToolBarHelper::save('createsearchindexer.save', 'JTOOLBAR_SAVE');
		}

		if (!$checkedOut && ($canDo->get('core.create')))
		{
			JToolBarHelper::custom('createsearchindexer.save2new', 'save-new.png', 'save-new_f2.png', 'JTOOLBAR_SAVE_AND_NEW', false);
		}
		// If an existing item, can save to a copy.
		if (!$isNew && $canDo->get('core.create'))
		{
			JToolBarHelper::custom('createsearchindexer.save2copy', 'save-copy.png', 'save-copy_f2.png', 'JTOOLBAR_SAVE_AS_COPY', false);
		}

		if (empty($this->item->id))
		{
			JToolBarHelper::cancel('createsearchindexer.cancel', 'JTOOLBAR_CANCEL');
		}
		else
		{
			JToolBarHelper::cancel('createsearchindexer.cancel', 'JTOOLBAR_CLOSE');
		}
	}
}
