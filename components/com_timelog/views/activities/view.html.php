<?php
/**
 * @package    Com_Timelog
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2018 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\MVC\View\HtmlView;
JLoader::import("/components/com_subusers/includes/rbacl", JPATH_ADMINISTRATOR);
/**
 * View class for a list of Timelog.
 *
 * @since  1.0.0
 */
class TimelogViewActivities extends HtmlView
{
	/**
	 * An array of items
	 *
	 * @var  array
	 */
	protected $items;

	/**
	 * The pagination object
	 *
	 * @var  Pagination
	 */
	protected $pagination;

	/**
	 * The model state
	 *
	 * @var  object
	 */
	protected $state;

	/**
	 * Form object for search filters
	 *
	 * @var  Form
	 */
	public $filterForm;

	/**
	 * Logged in User
	 *
	 * @var  Object
	 */
	protected $user;

	/**
	 * The active search filters
	 *
	 * @var  array
	 */
	public $activeFilters;

	/**
	 * Params
	 *
	 * @var  object|array
	 */
	protected $params;

	/**
	 * @var  boolean
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected $canDelete;

	/**
	 * @var  boolean
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected $canView;

	/**
	 * @var  boolean
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected $canCreateTimelog;

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
		$app = Factory::getApplication();

		$this->user = Factory::getUser();

		// Validate user login.
		if (empty($this->user->id))
		{
			$return = base64_encode((string) Uri::getInstance());
			$login_url_with_return = Route::_('index.php?option=com_users&return=' . $return);
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'notice');
			$app->redirect($login_url_with_return, 403);
		}

		// DPE Hack - Check if you have access timelog for a activity
		JLoader::import('components.com_sla.includes.sla', JPATH_ADMINISTRATOR);
		$input          = $app->input;
		$activityId     = $input->getInt('sla_activity', 0);
		$licenceId      = $input->getInt('licence_id', 0);
		$slaSlaActivity = SlaSlaActivity::getInstance($activityId);

		// Get cluster details from license
		$slaClusterXrefs = SlaFactory::table("slaclusterxrefs");
		$slaClusterXrefs->load(array('license_id' => $licenceId));

		if (property_exists($slaClusterXrefs, 'cluster_id'))
		{
			$clusterId = $slaClusterXrefs->cluster_id;
		}

		$this->canView  = RBACL::check($this->user->id, 'com_cluster', 'core.view.logs', 'com_timelog', $clusterId);

		if ($this->user->authorise('core.manageall', 'com_cluster'))
		{
			$this->canCreateTimelog = true;
			$this->canView          = true;
			$this->canDelete        = true;
		}

		if (!$this->canView)
		{
			throw new Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		if (!$this->user->authorise('core.manageall', 'com_cluster'))
		{
			if (!$clusterId)
			{
				throw new Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403);
			}

			$this->canCreateTimelog = RBACL::check($this->user->id, 'com_cluster', 'core.create.logs', 'com_timelog', $clusterId);
			$this->canDelete        = RBACL::check($this->user->id, 'com_cluster', 'core.delete.logs', 'com_timelog', $clusterId);
		}

		$this->state         = $this->get('State');
		$this->items         = $this->get('Items');
		$this->pagination    = $this->get('Pagination');
		$this->filterForm    = $this->get('FilterForm');
		$this->activeFilters = $this->get('ActiveFilters');
		$this->params        = $app->getParams('com_timelog');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors));
		}

		parent::display($tpl);
	}
}
