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
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;

/**
 * Multiagency controller class.
 *
 * @since  1.6
 */
class MultiagencyControllerMultiagency extends FormController
{
	/**
	 * Constructor
	 *
	 * @throws Exception
	 */
	public function __construct()
	{
		$this->view_list = 'multiagences';
		parent::__construct();
	}

	/**
	 * Method to Get region.
	 *
	 * @return bool
	 *
	 * @since 1.6
	 */
	public function getRegions()
	{
		$path = JPATH_SITE . '/components/com_tjfields/helpers/geo.php';

		if (!class_exists('tmtTestsHelper'))
		{
			// Require_once $path
			JLoader::register('TjGeoHelper', $path);
			JLoader::load('TjGeoHelper');
		}

		$tjGeoHelper = TjGeoHelper::getInstance('TjGeoHelper');
		$input = Factory::getApplication()->input;
		$country_id = $input->get('country_id', '0', 'INT');

		$defaultState = array();
		$defaultState['id'] = '';
		$defaultState['region'] = Text::_('COM_MULTIAGENCY_SELECT_REGION');
		$stateList = (array) $tjGeoHelper->getRegionList($country_id);
		$stateList = array_merge(array($defaultState), $stateList);

		echo HTMLHelper::_('select.genericlist', $stateList, 'jform[state_id]', 'class="chzn-done" size="1"', 'id', 'region', '', 'jform_state_id');

		jexit();
	}
}
