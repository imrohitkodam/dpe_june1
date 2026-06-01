<?php
/**
 * @package    Sla
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Pagination\Pagination;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Object\CMSObject;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

/**
 * Slas view
 *
 * @since  1.0.0
 */
class SlaViewSlas extends HtmlView
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
	 * The access varible
	 *
	 * @var  int
	 */
	protected $canCreate;

	/**
	 * The access varible
	 *
	 * @var  int
	 */
	protected $canEdit;

	/**
	 * The access varible
	 *
	 * @var  int
	 */
	protected $canCheckin;

	/**
	 * The access varible
	 *
	 * @var  int
	 */
	protected $canChangeStatus;

	/**
	 * The access varible
	 *
	 * @var  int
	 */
	protected $canDelete;

	/**
	 * Display the view
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise an Error object.
	 */
	public function display($tpl = null)
	{
		// Get state
		$this->state = $this->get('State');

		// This calls model function getItems()
		$this->items = $this->get('Items');

		// Get pagination
		$this->pagination = $this->get('Pagination');

		$this->filterForm    = $this->get('FilterForm');
		$this->activeFilters = $this->get('ActiveFilters');

		// Get ACL actions
		$this->user            = Factory::getUser();

		$this->canCreate       = $this->user->authorise('core.content.create', 'com_sla');
		$this->canEdit         = $this->user->authorise('core.content.edit', 'com_sla');
		$this->canCheckin      = $this->user->authorise('core.content.manage', 'com_sla');
		$this->canChangeStatus = $this->user->authorise('core.content.edit.state', 'com_sla');
		$this->canDelete       = $this->user->authorise('core.content.delete', 'com_sla');

		// Display the view
		parent::display($tpl);
	}

	/**
	 * Method to order fields
	 *
	 * @return ARRAY
	 */
	protected function getSortFields()
	{
		return array(
			's.id' => Text::_('JGRID_HEADING_ID'),
			's.title' => Text::_('COM_SLA_LIST_SLAS_TITLE'),
			's.type' => Text::_('COM_SLA_LIST_SLAS_TYPE'),
			's.ordering' => Text::_('JGRID_HEADING_ORDERING'),
			's.state' => Text::_('JSTATUS'),
		);
	}
}
