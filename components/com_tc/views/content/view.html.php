<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Tc
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2016-2017 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

jimport('joomla.application.component.view');
use \Joomla\CMS\Component\ComponentHelper;

/**
 * View to edit
 *
 * @since  1.6
 */
class TcViewContent extends JViewLegacy
{
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
		$app        = JFactory::getApplication();
		$input = JFactory::getApplication()->input;
		$this->tc_id = $input->get('tc_id', '', 'INT');
		$this->layout     = $input->get('layout', 'default', 'STRING');
		$this->user_id    = JFactory::getUser()->id;

		JLoader::import('components.com_tc.models.content', JPATH_ADMINISTRATOR);

		// To get the foematted date
		$comParams = ComponentHelper::getParams('com_tc');
		$this->dateFormat = $comParams->get('date_format_show');

		if ($this->dateFormat == "custom")
		{
			$this->dateFormat = $comParams->get('custom_format');
		}

		// Call api to get user accepted version
		$model  = JModelLegacy::getInstance('Content', 'TcModel');

		if ($this->user_id && $this->tc_id)
		{
			$userAcceptedTC = $model->isUserAcceptedTC($this->user_id, $this->tc_id);
		}

		if ($userAcceptedTC)
		{
			// Redirect to Home page
			$app->redirect(JRoute::_(JUri::base()));
		}
		else
		{
			// Redirect to T&C page
			$this->layout     = $input->get('layout', 'default', 'STRING');

			if (!$this->user_id && $this->layout != 'terms')
			{
				$app->redirect(JRoute::_(JUri::base()));

				return false;
			}

			$this->termsandconditions            = $model->getItem($this->tc_id);
			parent::display($tpl);
		}
	}
}
