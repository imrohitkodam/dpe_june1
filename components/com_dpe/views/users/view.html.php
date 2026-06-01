<?php
/**
 * @version    CVS: 1.0.0
 * @package    Com_Dpe
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2017 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;

use Joomla\CMS\Component\ComponentHelper;


/**
 * View class for a list of users.
 *
 * @since  1.6
 */
class DpeViewUsers extends HtmlView
{
	protected $items;

	protected $pagination;

	protected $state;

	protected $params;

	protected $agencyListArray;

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
		$this->user	= Factory::getUser();

		$this->canAssign = $this->user->authorise('core.assign', 'com_dpe');

		if (!$this->canAssign)
		{
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'warning');

			return;
		}

		$layout    = $app->input->get('layout');
		$clusterId = $app->input->get('cluster_id', 0, "INT");

		if (ComponentHelper::getComponent('com_subusers', true)->enabled)
		{
			JLoader::import("/components/com_subusers/includes/rbacl", JPATH_ADMINISTRATOR);

			if ($layout == 'users')
			{
				if (!$this->user->authorise('core.manageall', 'com_cluster')
					&& !RBACL::check($this->user->id, 'com_cluster', 'core.deassign.lesson', 'com_tjlms', $clusterId))
				{
					$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'warning');

					return;
				}
			}
			else
			{
				if (!$this->user->authorise('core.manageall', 'com_cluster')
					&& !RBACL::check($this->user->id, 'com_cluster', 'core.assign.lesson', 'com_tjlms', $clusterId))
				{
					$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'warning');

					return;
				}
			}
		}

		$this->state = $this->get('State');
		$this->items = $this->get('Items');
		$this->lessonId = $app->input->get('element_id', '0', 'INT');

		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpe/tables');
		$table = Table::getInstance('TjlmsClusterXref', 'DpeTable');
		$table->load(array('lesson_id' => $this->lessonId));

		// Check logged-in user associated with passed cluster_id
		JLoader::import("/components/com_cluster/libraries/cluster", JPATH_ADMINISTRATOR);

		// Check logged-in user is a member of cluster
		$cluster = ClusterCluster::getInstance($table->cluster_id);
		$this->clusterId = $cluster->client_id;

		$isValid = $cluster->isMember($this->user->id);

		// Check user not manageall cluster permission & not a members of cluster
		if (!$this->user->authorise('core.manageall', 'com_cluster') && !$isValid)
		{
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'warning');

			return;
		}

		$this->title = $app->input->get('title', '', 'STR');
		$this->pagination = $this->get('Pagination');
		$this->params = $app->getParams('com_multiagency');
		$this->agenciesId = $this->getState('filter.agencies');
		$this->filterForm = $this->get('FilterForm');
		$this->activeFilters = $this->get('ActiveFilters');

		JLoader::import('main', JPATH_SITE . '/components/com_dpe/helpers');

		DpeMainHelper::getLanguageConstant();

		parent::display($tpl);
	}

	/**
	 * Check if state is set
	 *
	 * @param   mixed  $state  State
	 *
	 * @return bool
	 */
	public function getState($state)
	{
		return isset($this->state->{$state}) ? $this->state->{$state} : false;
	}
}
