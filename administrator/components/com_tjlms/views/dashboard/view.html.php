<?php
/**
 * @package     Shika
 * @subpackage  com_tjlms
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Component\ComponentHelper;

/**
 * View class for a list of Tjlms.
 *
 * @since  1.0.0
 */
class TjlmsViewDashboard extends HtmlView
{
	protected $items;

	protected $pagination;

	protected $state;

	public $isArabic = false;

	/**
	 * Display the view
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise a Error object.
	 *
	 * @since  1.0.0
	 */
	public function display($tpl = null)
	{
		TjlmsHelper::addSubmenu('dashboard');
		$this->comtjlmsHelper     = new comtjlmsHelper;
		$this->tjlmsCoursesHelper = new tjlmsCoursesHelper;

		// Get component params
		$this->tjlmsparams           = ComponentHelper::getParams('com_tjlms');
		$this->show_user_or_username = $this->tjlmsparams->get('show_user_or_username', 'name');

		$model                  = $this->getModel('dashboard');
		$this->DashboardDetails = $this->get('DashboardDetails');
		$this->popularCourses   = $this->get('popularCourses');
		$this->popularStudent   = $this->get('popularStudent');
		$this->mostLikedCourses = '';
		$this->state            = $this->get('State');
		$this->hasReminderTmpls = $model->hasReminderTemplates();

		// Check if jlike installed.
		if (File::exists(JPATH_ROOT . DS . 'components' . DS . 'com_jlike' . DS . 'jlike.php'))
		{
			if (ComponentHelper::isEnabled('com_jlike', true))
			{
				$this->mostLikedCourses = $this->get('mostLikedCourses');
			}
		}

		$this->yourActivities = $model->getactivity();
		$this->allow_paid_courses = $this->tjlmsparams->get('allow_paid_courses', '0', 'INT');
		$this->admin_approval = $this->tjlmsparams->get('admin_approval', '0', 'INT');
		$this->paid_course_admin_approval = $this->tjlmsparams->get('paid_course_admin_approval', '0', 'INT');

		if ($this->allow_paid_courses == 1)
		{
			$this->revenueData = $model->getrevenueData();
		}

		$tjlmsMigrationResult  = $model->checkMigrationStatus();

		if (!empty($tjlmsMigrationResult['templates']) && $tjlmsMigrationResult['houseKeepData'] < 3)
		{
			Factory::getApplication()->enqueueMessage(Text::_('COM_TJLMS_MIGRATE_CERTIFICATE_NOTICE'), 'error');
		}

		// Check if the Arabic language is install for display notice of download library.
		$languages         = LanguageHelper::getKnownLanguages();
		$arabicLibFilePath = JPATH_SITE . "/libraries/ar-php/I18N/Arabic/Glyphs.php";

		if (array_key_exists('ar-AA', $languages) && !file_exists($arabicLibFilePath))
		{
			$this->isArabic = true;
		}

		$this->userDefaultImg = Uri::root() . 'media/com_tjlms/images/default/user.png';

		$this->addToolbar();

		$this->sidebar = JHtmlSidebar::render();

		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @return  toolbar
	 *
	 * @since  1.6
	 */
	protected function addToolbar()
	{
		require_once JPATH_COMPONENT . '/helpers/tjlms.php';
		$canDo = TjlmsHelper::getActions();
		ToolBarHelper::title(Text::_('COM_TJLMS_TITLE_DASHBOARD'), 'home');

		if ($canDo->get('core.admin'))
		{
			ToolBarHelper::preferences('com_tjlms');
			ToolbarHelper::custom('fixdatabase', 'refresh', 'refresh', 'COM_TJLMS_TOOLBAR_DATABASE_MIGRATE', false);

			// JToolbarHelper::custom('fixColumnIndexes', 'refresh', 'refresh', 'COM_TJLMS_TOOLBAR_DATABASE_INDEX', false);

			if (!$this->hasReminderTmpls)
			{
				ToolbarHelper::custom('addReminderTmpls', 'refresh', 'reminder', 'COM_TJLMS_TOOLBAR_ADD_REMINDER_TEMPLATES', false);
			}

			$toolbar = Toolbar::getInstance('toolbar');
			$toolbar->appendButton(
			'Custom', '<a id="tjHouseKeepingFixDatabasebutton" class="btn btn-migrate btn-default hidden"><span class="icon-refresh"></span>'
			. Text::_('COM_TJLMS_MIGRATE_DATA') . '</a>');
		}
	}
}
