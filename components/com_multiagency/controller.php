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
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\Factory;

jimport('joomla.application.component.controller');
JPATH_SITE . 'components/com_multiagency/helpers/subusers.php';
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

/**
 * Class MultiagencyController
 *
 * @since  1.6
 */
class MultiagencyController extends BaseController
{
	/**
	 * Method to display a view.
	 *
	 * @param   boolean  $cachable   If true, the view output will be cached
	 * @param   mixed    $urlparams  An array of safe url parameters and their variable types, for valid values see {@link InputFilter::clean()}.
	 *
	 * @return  JController   This object to support chaining.
	 *
	 * @since    1.5
	 */
	public function display($cachable = false, $urlparams = false)
	{
		$app  = Factory::getApplication();
		$view = $app->input->getCmd('view', 'multiagences');
		$app->input->set('view', $view);

		parent::display($cachable, $urlparams);

		return $this;
	}

	/**
	 * Method to display a view.
	 *
	 * @return  Null
	 *
	 * @since    1.5
	 */
	public function migrateHierarchy()
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true);

		$query = 'UPDATE z467w_hierarchy_users SET reports_to = user_id, user_id = subuser_id, created_by = subuser_id,
		modified_by =subuser_id where reports_to = 0';
		$db->setQuery($query);

		$result = $db->execute();
	}
}
