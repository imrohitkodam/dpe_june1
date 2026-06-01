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
use Joomla\CMS\Router\Route;
use Joomla\CMS\Plugin\PluginHelper;

$tjlmsLessonHelperPath = JPATH_ROOT . '/components/com_tjlms/helpers/lesson.php';

if (!class_exists('TjlmsLessonHelper'))
{
	JLoader::register('TjlmsLessonHelper', $tjlmsLessonHelperPath);
	JLoader::load('TjlmsLessonHelper');
}

/**
 * View class for a list of users.
 *
 * @since  1.6
 */
class DpeViewMyAssignments extends HtmlView
{
	protected $items;

	protected $pagination;

	protected $state;

	protected $params;

	// Plugin Object
	public  $jlikeTjlmslessonPlugin;

	public  $user;

	public  $db;

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
		$this->db = Factory::getDbo();

		if (!$this->user->id)
		{
			$msg = Text::_('COM_DPE_LOGIN_MSG');
			$uri = $app->input->server->get('REQUEST_URI', '', 'STRING');
			$url = base64_encode($uri);
			$app->redirect(Route::_('index.php?option=com_users&view=login&return=' . $url, false), $msg);
		}

		// Get Plugins: jLike Shika lesson plugin params
		$this->jlikeTjlmslessonPlugin = PluginHelper::getPlugin('content', 'jlike_tjlmslesson');
		$this->state = $this->get('State');
		$this->items = $this->get('Items');
		$this->pagination = $this->get('Pagination');
		$this->filterForm = $this->get('FilterForm');
		$this->activeFilters = $this->get('ActiveFilters');

		// Check whether lesson is valid or not
		$this->tjlmsLessonHelper = new tjlmsLessonHelper;

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
