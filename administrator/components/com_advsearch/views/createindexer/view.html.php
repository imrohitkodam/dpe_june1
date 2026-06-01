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

/**
 * Class view for a list of Advsearch.
 *
 * @since  3.3
 */
class AdvsearchViewCreateindexer extends JViewLegacy
{
	protected $items;

	protected $pagination;

	protected $state;

	protected $Plugins;

	/**
	 * Method to display state item pagination
	 *
	 * @param   string  $tpl  tpl.
	 *
	 * @return    void
	 */
	public function display($tpl = null)
	{
		$this->state      = $this->get('State');
		$this->items      = $this->get('Items');
		$this->pagination = $this->get('Pagination');

		$this->Plugins = $this->get('Plugins');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors));
		}

		$this->addToolbar();

		$input = JFactory::getApplication()->input;
		$view  = $input->getCmd('view', '');
		AdvsearchHelper::addSubmenu($view);

		parent::display($tpl);
	}

	/**
	 * Method to add  page title and tool bar
	 *
	 * @return  void
	 *
	 * @since    1.6
	 */
	protected function addToolBar()
	{
		// JRequest::setVar('hidemainmenu', true);
		// $isNew = ($this->item->id == 0);
		$isNew = 0;
		JToolBarHelper::title($isNew ? JText::_('Add Indexer') : JText::_('Add Indexer'));
		JToolBarHelper::save('createindexer.saveIndexer');
		JToolBarHelper::cancel('createindexer.cancel', $isNew ? 'JTOOLBAR_CANCEL' : 'JTOOLBAR_CLOSE');
	}
}
