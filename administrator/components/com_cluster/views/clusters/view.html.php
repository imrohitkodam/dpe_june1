<?php
/**
 * @package    Cluster
 *
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2018 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Pagination\Pagination;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Object\CMSObject;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Toolbar\ToolbarHelper;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView;

/**
 * Clusters view
 *
 * @since  1.0.0
 */
class ClusterViewClusters extends HtmlView
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
	 * @var  CMSObject
	 */
	public $user;

	/**
	 * The active search filters
	 *
	 * @var  array
	 */
	public $activeFilters;

	/**
	 * The sidebar markup
	 *
	 * @var  string
	 */
	protected $sidebar;

	/**
	 * An ACL object to verify user rights.
	 *
	 * @var    CMSObject
	 * @since  1.0.0
	 */
	protected $canDo;

	/**
	 * Display the view
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise an Error object.
	 */
	public function display($tpl = null)
	{
		$this->state = $this->get('State');
		$this->items = $this->get('Items');
		$this->pagination = $this->get('Pagination');
		$this->filterForm    = $this->get('FilterForm');
		$this->activeFilters = $this->get('ActiveFilters');
		$this->user            = Factory::getUser();
		$this->canDo         = ContentHelper::getActions('com_cluster');

		ClusterHelper::addSubmenu('clusters');

		// DPE - Hack -- Start to
		// $this->addToolbar();

		$this->sidebar = JHtmlSidebar::render();

		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @return void
	 *
	 * @since    1.0.0
	 */
	protected function addToolbar()
	{
		JToolBarHelper::title(Text::_('COM_CLUSTERS_VIEW_CLUSTERS'), '');
		$canDo = $this->canDo;

		if ($canDo->get('core.create'))
		{
			ToolbarHelper::addNew('cluster.add');
		}

		if ($canDo->get('core.edit'))
		{
			ToolbarHelper::editList('cluster.edit');
		}

		if ($canDo->get('core.edit.state'))
		{
			ToolbarHelper::divider();
			ToolbarHelper::publish('clusters.publish', 'JTOOLBAR_PUBLISH', true);
			ToolbarHelper::unpublish('clusters.unpublish', 'JTOOLBAR_UNPUBLISH', true);
			JToolBarHelper::archiveList('clusters.archive', 'JTOOLBAR_ARCHIVE');
			ToolbarHelper::divider();
		}

		if ($canDo->get('core.delete'))
		{
			ToolbarHelper::deleteList('JGLOBAL_CONFIRM_DELETE', 'clusters.delete', 'JTOOLBAR_DELETE');
			ToolbarHelper::divider();
		}

		if ($canDo->get('core.admin') || $canDo->get('core.options'))
		{
			ToolbarHelper::preferences('com_cluster');
			ToolbarHelper::divider();
		}
	}

	/**
	 * Method to order fields
	 *
	 * @return array
	 */
	protected function getSortFields()
	{
		return array(
			'cl.id' => Text::_('JGRID_HEADING_ID'),
			'cl.title' => Text::_('COM_CLUSTER_LIST_CLUSTERS_NAME'),
			'cl.client' => Text::_('COM_CLUSTER_LIST_CLUSTERS_CLIENT'),
			'cl.ordering' => Text::_('JGRID_HEADING_ORDERING'),
			'cl.state' => Text::_('JSTATUS'),
		);
	}
}
