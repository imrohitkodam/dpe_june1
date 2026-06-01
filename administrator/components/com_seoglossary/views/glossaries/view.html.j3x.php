<?php
/**    
 * SEO Glossary Glossaries view
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
if(!defined('DS')){
define('DS',DIRECTORY_SEPARATOR);
}

jimport('joomla.application.component.view');
require_once(JPATH_SITE . '/components/com_seoglossary/helpers/compat.php');

/**
 * View class for a list of Seoglossary.
 */
class SeoglossaryViewGlossaries extends SeogView
{
	protected $items;
	protected $pagination;
	protected $state;
	protected $f_catid;

	/**
	 * Display the view
	 */
	public function display($tpl = null)
	{
		$this->state		= $this->get('State');
		$this->items		= $this->get('Items');
		$this->pagination	= $this->get('Pagination');

		// Check for errors.
		if (count($errors = $this->get('Errors'))) {
			JFactory::getApplication()->enqueueMessage(implode("\n", $errors),'error');
			return false;
		}

		// Preprocess the list of items to find ordering divisions.
		foreach ($this->items as &$item) {
			$this->ordering[@$item->parent_id][] = $item->id;
		}
		
		
		// Category filter.
		$options	= array();
		
		$db = JFactory::getDbo();
		$db->setQuery('SELECT id, name FROM #__seoglossaries ORDER BY ordering ASC;');
		$options[]	=JHtml::_('select.option', "",JTEXT::_('JSELECT'));
		foreach($db->loadObjectList() as $glossary)
		{
			$options[]	= JHtml::_('select.option', $glossary->id, $glossary->name);
		}

		$this->f_catid = $options;

		$this->addToolbar();
		if (!version_compare(JVERSION, '4.0', 'ge'))
		{
		$this->sidebar = JHtmlSidebar::render();
		}
		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @since	1.6
	 */
	protected function addToolbar()
	{
		require_once JPATH_COMPONENT.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'seoglossary.php';

		$state	= $this->get('State');
		$canDo	= SeoglossaryBackendHelper::getActions($state->get('filter.category_id'));

		JToolBarHelper::title(JText::_('COM_SEOGLOSSARY_TITLE_GLOSSARIES'), 'glossaries.png');

        //Check if the form exists before showing the add/edit buttons
        $formPath = JPATH_COMPONENT_ADMINISTRATOR.DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR.'glossary';
        if (file_exists($formPath)) {

            if ($canDo->get('core.create')) {
			    JToolBarHelper::addNew('glossary.add','JTOOLBAR_NEW');
		    }

		    if ($canDo->get('core.edit')) {
			    JToolBarHelper::editList('glossary.edit','JTOOLBAR_EDIT');
		    }

        }

		if ($canDo->get('core.edit.state')) {

            if (isset($this->items[0]->state)) {
			    JToolBarHelper::divider();
			    JToolBarHelper::custom('glossaries.publish', 'publish.png', 'publish_f2.png','JTOOLBAR_PUBLISH', true);
			    JToolBarHelper::custom('glossaries.unpublish', 'unpublish.png', 'unpublish_f2.png', 'JTOOLBAR_UNPUBLISH', true);
            } else {
                //If this component does not use state then show a direct delete button as we can not trash
                JToolBarHelper::deleteList('', 'glossaries.delete','JTOOLBAR_DELETE');
            }

            if (isset($this->items[0]->state)) {
			    JToolBarHelper::divider();
			    JToolBarHelper::archiveList('glossaries.archive','JTOOLBAR_ARCHIVE');
            }
            if (isset($this->items[0]->checked_out)) {
            	JToolBarHelper::custom('glossaries.checkin', 'checkin.png', 'checkin_f2.png', 'JTOOLBAR_CHECKIN', true);
            }
		}
        
        //Show trash and delete for components that uses the state field
        if (isset($this->items[0]->state)) {
		    if ($state->get('filter.state') == -2 && $canDo->get('core.delete')) {
			    JToolBarHelper::deleteList('', 'glossaries.delete','JTOOLBAR_EMPTY_TRASH');
			    JToolBarHelper::divider();
		    } else if ($canDo->get('core.edit.state')) {
			    JToolBarHelper::trash('glossaries.trash','JTOOLBAR_TRASH');
			    JToolBarHelper::divider();
		    }
        }

		if ($canDo->get('core.admin')) {
			JToolBarHelper::preferences('com_seoglossary');
		}
		
		JHtmlSidebar::setAction('index.php?option=com_seoglossary&view=glossaries');

		JHtmlSidebar::addFilter(
			JText::_('COM_SEOGLOSSARY_GLOSSARIES_CATID'),
			'filter_catid',
			JHtml::_('select.options', $this->f_catid, 'value', 'text', $this->state->get('filter.catid'))
		);
		JHtmlSidebar::addFilter(
			JText::_('JOPTION_SELECT_PUBLISHED'),
			'filter_published',
			JHtml::_('select.options', JHtml::_('jgrid.publishedOptions'), 'value', 'text', $this->state->get('filter.published'), true)
		);



	}
	
		/**
	 * Returns an array of fields the table can be sorted by
	 *
	 * @return  array  Array containing the field name to sort by as the key and display text as value
	 *
	 * @since   3.0
	 */
	protected function getSortFields()
	{
		return array(
			'a.ordering' => JText::_('JGRID_HEADING_ORDERING'),
			'a.state' => JText::_('JSTATUS'),
			'a.tterm' => JText::_('COM_SEOGLOSSARY_FORM_LBL_GLOSSARY_TTERM'),
			'catname' => JText::_('COM_SEOGLOSSARY_GLOSSARIES_CATID'),
			'a.id' => JText::_('JGRID_HEADING_ID'),
		);
	}
}
