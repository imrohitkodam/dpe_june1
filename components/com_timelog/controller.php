<?php
/**
 * @package    Com_Timelog
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Filter\InputFilter;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;

\JLoader::load(JPATH_COMPONENT_ADMINISTRATOR . '/includes/timelog');

/**
 * Class TimelogController
 *
 * @since  1.0.0
 */
class TimelogController extends BaseController
{
	/**
	 * Method to display a view.
	 *
	 * @param   boolean  $cachable   If true, the view output will be cached
	 * @param   mixed    $urlparams  An array of safe url parameters and their variable types, for valid values see {@link InputFilter::clean()}.
	 *
	 * @return  JController   This object to support chaining.
	 *
	 * @since    1.0.0
	 */
	public function display($cachable = false, $urlparams = array())
	{
		$app  = Factory::getApplication();
		$view = $app->input->getCmd('view', 'activities');
		$app->input->set('view', $view);

		return parent::display($cachable, $urlparams);
	}
}
