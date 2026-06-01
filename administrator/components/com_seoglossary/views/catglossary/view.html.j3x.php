<?php
/**    
 * SEO Glossary Catglossary view
 *
 * We developed this code with our hearts and passion.
 * We hope you found it useful, easy to understand and change.
 * Otherwise, please feel free to contact us at contact@joomunited.com
 *
 * @package 	SEO Glossary
 * @copyright 	Copyright (C) 2012 JoomUnited (http://www.joomunited.com). All rights reserved.
 * @license 	GNU General Public License version 2 or later; http://www.gnu.org/licenses/gpl-2.0.html
 */

// No direct access
defined('_JEXEC') or die;

jimport('joomla.application.component.view');
require_once(JPATH_SITE . '/components/com_seoglossary/helpers/compat.php');

/**
 * View to edit
 */
class SeoglossaryViewCatglossary extends SeogView
{
	protected $state;
	protected $item;
	protected $form;

	/**
	 * Display the view
	 */
	public function display($tpl = null)
	{
		$this->state	= $this->get('State');
		$this->item		= $this->get('Item');
		$this->form		= $this->get('Form');

		// Check for errors.
		if (count($errors = $this->get('Errors'))) {
			JFactory::getApplication()->enqueueMessage(implode("\n", $errors),'error');
			return false;
		}

		$this->addToolbar();
		if (!version_compare(JVERSION, '4.0', 'ge'))
		{
		$this->sidebar = JHtmlSidebar::render();
		}
		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 */
	protected function addToolbar()
	{
		 JFactory::getApplication()->input->set('hidemainmenu', false);

		$user		= JFactory::getUser();
		$isNew		= ($this->item->id == 0);
        if (isset($this->item->checked_out)) {
		    $checkedOut	= !($this->item->checked_out == 0 || $this->item->checked_out == $user->get('id'));
        } else {
            $checkedOut = false;
        }
		$canDo		= SeoglossaryBackendHelper::getActions();

		JToolBarHelper::title(JText::_('COM_SEOGLOSSARY_TITLE_CATGLOSSARY'), 'catglossary.png');

		// If not checked out, can save the item.
		if (!$checkedOut && ($canDo->get('core.edit')||($canDo->get('core.create'))))
		{

			JToolBarHelper::apply('catglossary.apply', 'JTOOLBAR_APPLY');
			JToolBarHelper::save('catglossary.save', 'JTOOLBAR_SAVE');
		}
		if (!$checkedOut && ($canDo->get('core.create'))){
			JToolBarHelper::custom('catglossary.save2new', 'save-new.png', 'save-new_f2.png', 'JTOOLBAR_SAVE_AND_NEW', false);
		}
		// If an existing item, can save to a copy.
		if (!$isNew && $canDo->get('core.create')) {
			JToolBarHelper::custom('catglossary.save2copy', 'save-copy.png', 'save-copy_f2.png', 'JTOOLBAR_SAVE_AS_COPY', false);
		}
		if (empty($this->item->id)) {
			JToolBarHelper::cancel('catglossary.cancel', 'JTOOLBAR_CANCEL');
		}
		else {
			JToolBarHelper::cancel('catglossary.cancel', 'JTOOLBAR_CLOSE');
		}

	}
}
