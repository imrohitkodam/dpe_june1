<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Multiagency
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2017 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\Toolbar\ToolbarHelper;

jimport('joomla.application.component.view');

/**
 * View to edit
 *
 * @since  1.6
 */
class MultiagencyViewMultiagency extends HtmlView
{
	protected $state;

	protected $item;

	protected $form;

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
		$this->model   = $this->getModel('multiagency');

		$path = JPATH_SITE . '/components/com_tjfields/helpers/geo.php';

		if (!class_exists('tmtTestsHelper'))
		{
			// Require_once $path
			JLoader::register('TjGeoHelper', $path);
			JLoader::load('TjGeoHelper');
		}

		$tjGeoHelper = TjGeoHelper::getInstance('TjGeoHelper');

		// Get country list
		$defaultCountry = array();
		$defaultCountry['id'] = '';
		$defaultCountry['country'] = Text::_('COM_MULTIAGENCY_SELECT_COUNTRY');
		$this->countryList = (array) $tjGeoHelper->getCountryList();
		$this->countryList = array_merge(array($defaultCountry), $this->countryList);

		// Get state list
		$defaultState = array();
		$defaultState['id'] = '';
		$defaultState['region'] = Text::_('COM_MULTIAGENCY_SELECT_REGION');
		$this->stateList = (array) $tjGeoHelper->getRegionList($this->item->country_id);
		$this->stateList = array_merge(array($defaultState), $this->stateList);

		// Get manager list
		$defaultManager = array();
		$defaultManager['id'] = '';
		$defaultManager['username'] = Text::_('COM_MULTIAGENCY_SELECT_MULTIAGENCY_MANAGER');
		$this->managerList = (array) $this->model->getMultiagencyManagers();
		$this->managerList = array_merge(array($defaultManager), $this->managerList);

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors));
		}

		$this->addToolbar();
		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @return void
	 *
	 * @throws Exception
	 */
	protected function addToolbar()
	{
		Factory::getApplication()->input->set('hidemainmenu', true);

		$user  = Factory::getUser();
		$isNew = ($this->item->id == 0);

		if (isset($this->item->checked_out))
		{
			$checkedOut = !($this->item->checked_out == 0 || $this->item->checked_out == $user->get('id'));
		}
		else
		{
			$checkedOut = false;
		}

		$canDo = MultiagencyHelpersMultiagency::getActions();

		ToolBarHelper::title(Text::_('COM_MULTIAGENCY_TITLE_MULTIAGENCY'), 'enterprise.png');

		// If not checked out, can save the item.
		if (!$checkedOut && ($canDo->get('core.edit') || ($canDo->get('core.create'))))
		{
			ToolBarHelper::apply('multiagency.apply', 'JTOOLBAR_APPLY');
			ToolBarHelper::save('multiagency.save', 'JTOOLBAR_SAVE');
		}

		if (!$checkedOut && ($canDo->get('core.create')))
		{
			ToolBarHelper::custom('multiagency.save2new', 'save-new.png', 'save-new_f2.png', 'JTOOLBAR_SAVE_AND_NEW', false);
		}

		if (empty($this->item->id))
		{
			ToolBarHelper::cancel('multiagency.cancel', 'JTOOLBAR_CANCEL');
		}
		else
		{
			ToolBarHelper::cancel('multiagency.cancel', 'JTOOLBAR_CLOSE');
		}
	}
}
