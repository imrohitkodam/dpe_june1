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
 * Class for a list of Advsearch.
 *
 * @since  3.3
 */
class AdvsearchViewPluginfo extends JViewLegacy
{
	protected $items;

	protected $pagination;

	protected $state;

	/**
	 * Method to display state item pagination
	 *
	 * @param   string  $tpl  tpl.
	 *
	 * @return    void.
	 */
	public function display($tpl = null)
	{
		$this->state      = $this->get('State');
		$this->items      = $this->get('Items');
		$this->pagination = $this->get('Pagination');

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
	 * @return  void.
	 *
	 * @since    1.6
	 */
	protected function addToolbar()
	{
		require_once JPATH_COMPONENT . '/helpers/advsearch.php';

		$state = $this->get('State');
		$canDo = AdvsearchHelper::getActions($state->get('filter.category_id'));

		JToolBarHelper::title(JText::_('COM_ADVSEARCH_TITLE_SEARCHINDEXER'), 'searchindexer.png');

		// Check if the form exists before showing the add/edit buttons
		$formPath = JPATH_COMPONENT_ADMINISTRATOR . '/views/createsearchindexer';

		if ($canDo->get('core.admin'))
		{
			JToolBarHelper::preferences('com_advsearch');
		}
	}
}
