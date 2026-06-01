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
 * Class View for a list of Advsearch.
 *
 * @since  3.3
 */
class AdvsearchViewCreatemapping extends JViewLegacy
{
	protected $item;

	protected $pagination;

	protected $state;

	protected  $Indexer;

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
		$this->item       = $this->get('DataMapp');
		$this->pagination = $this->get('Pagination');

		$this->Indexer = $this->get('Indexer');

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
		$isNew = 0;
		JToolBarHelper::title($isNew ? JText::_('Add Mapping') : JText::_('Add Mapping'));
		JToolBarHelper::save('createmapping.saveMapping');
		JToolBarHelper::cancel('createmapping.cancel', $isNew ? 'JTOOLBAR_CANCEL' : 'JTOOLBAR_CLOSE');
	}
}
