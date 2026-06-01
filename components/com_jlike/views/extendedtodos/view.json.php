<?php
/**
 * @package     JLike
 * @subpackage  com_jlike
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Factory;

/**
 *
 *
 * @since  __DEPLOY_VERSION__
 */
class JLikeViewExtendedTodos extends BaseHtmlView
{
	/**
	 * The pagination object
	 *
	 * @var  JPagination
	 */
	public $pagination;

	/**
	 * The model state
	 *
	 * @var  object
	 */
	public $state;

	/**
	 * Form object for search filters
	 *
	 * @var  Joomla\CMS\Form\Form
	 */
	public $filterForm;

	/**
	 * The active search filters
	 *
	 * @var  array
	 */
	public $activeFilters;

	/**
	 * Display the view
	 *
	 * @param   string  $tpl  Template name
	 *
	 * @return string
	 *
	 * @throws Exception
	 */
	public function display($tpl = null)
	{
		$app =Factory::getApplication();
		$this->state = $this->get('State');
		$this->items = $this->get('Items');
		$this->pagination = $this->get('Pagination');
		$this->filterForm    = $this->get('FilterForm');
		$this->activeFilters = $this->get('ActiveFilters');

		echo new JsonResponse($this->loadTemplate($tpl));
		$app->close();
	}

	/**
	 * Display the assignment list besed on the filters
	 *
	 * @return string
	 *
	 * @throws Exception
	 */
	public function loadMore()
	{
		$app =Factory::getApplication();
		$this->state = $this->get('State');
		$this->items = $this->get('Items');
		$this->pagination = $this->get('Pagination');
		$this->filterForm    = $this->get('FilterForm');
		$this->activeFilters = $this->get('ActiveFilters');

		$response = array("html" => $this->loadTemplate('todos'), "total" => $this->get('Total'));
		echo new JsonResponse($response);
		$app->close();
	}
}
