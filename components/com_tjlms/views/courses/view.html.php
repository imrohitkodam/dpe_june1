<?php
/**
 * @package    LMS_Shika
 * @copyright  Copyright (C) 2009-2014 Techjoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license    GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link       http://www.techjoomla.com
 */
// No direct access.
defined('_JEXEC') or die;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;

jimport('joomla.application.component.view');

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

/**
 * view of courses
 *
 * @since  1.0
 */
class TjlmsViewcourses extends HtmlView
{
	protected $items;

	protected $pagination;

	protected $state;

	/**
	 * Display the view
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  void
	 */
	public function display($tpl = null)
	{
		$app = Factory::getApplication();
		$this->state = $this->get('State');
		$this->items = $this->get('Items');
		$this->itemsCount = $this->get('Total');
		$this->pagination = $this->get('Pagination');
		$this->ol_user = Factory::getUser();
		$input = $app->input;

		// Get Menu params : Which layout to be shown
		$this->menuparams = $this->state->params;
		$this->course_images_size = $this->menuparams->get('course_images_size', 'S_');
		$this->courses_to_show = $this->menuparams->get('courses_to_show', 'all');

		if (!$this->courses_to_show || $this->courses_to_show == 'all')
		{
			$this->courses_to_show = $input->get('courses_to_show', 'all', 'STRING');
		}

		// Validate user login.
		if (($this->courses_to_show == 'enrolled' || $this->courses_to_show == 'liked') && !$this->ol_user->id)
		{
			$msg = Text::_('COM_TJLMS_MESSAGE_LOGIN_FIRST');

			// Get current url.
			$current = Uri::getInstance()->toString();
			$url = base64_encode($current);
			$app->redirect(Route::_('index.php?option=com_users&view=login&return=' . $url, false), $msg);
		}

		$model = $this->getModel();
		$input = Factory::getApplication()->input;
		$this->tjlmsFrontendHelper = new comtjlmsHelper;
		$this->tjlmsCoursesHelper = new tjlmsCoursesHelper;
		$this->tjlmsparams = $this->tjlmsFrontendHelper->getcomponetsParams('com_tjlms');

		$this->currency = $this->tjlmsparams->get('currency');
		$this->currency_symbol = $this->tjlmsparams->get('currency_symbol');
		$this->allow_paid_courses = $this->tjlmsparams->get('allow_paid_courses', '0', 'INT');
		$this->course_image_upload_path = $this->tjlmsparams->get('course_image_upload_path', '', 'STRING');

		$cat_id = $input->get('catid', '', 'STRING');
		$layouttobeshown = $input->get('show', 'pin', 'STRING');

		$course_cat = $input->get('course_cat', '', 'INT');
		$state = $model->getStatusCategory($course_cat);

		$params = $app->getParams();
		$this->course_images_size = $params->get('course_images_size', 'S_');

		if ($course_cat && $course_cat != -1 && $state == 0)
		{
			return false;
		}

		if (!$input->get('course_cat_filter', '', 'STRING'))
		{
			$input->set('course_cat_filter', $cat_id);
		}

		// Course categories
		$this->course_cats = $this->get('TjlmsCats');

		// Check if user is admin
		$this->ifuseradmin = $this->tjlmsFrontendHelper->checkAdmin($this->ol_user);

		$allcourses_url = $this->tjlmsFrontendHelper->tjlmsRoute('index.php?option=com_tjlms&view=courses');
		$cat_url = $this->tjlmsFrontendHelper->tjlmsRoute('index.php?option=com_tjlms&view=courses');

		$this->allcourses_url = Route::_($allcourses_url, false);
		$this->cat_url = $cat_url;
		$this->pagination = $this->get('Pagination');

		// Courses layout Start
		if ($app->getMenu()->getActive())
		{
			// If the current view is the active item and an courses view for this courses, then the menu item params take priority

			if (!empty($this->menuparams->get('courses_layout')))
			{
				$this->setLayout($this->menuparams->get('courses_layout'));
			}
		}

		$this->filterstates = new stdClass;
		$this->filterstates->category_filter = $app->getUserStateFromRequest('com_tjlms.filter.category_filter', 'category_filter', 0, 'INTEGER');

		$this->filterstates->course_type = $app->getUserStateFromRequest('com_tjlms.filter.course_type', 'course_type', -1, 'INTEGER');

		$this->filterstates->search = $app->getUserStateFromRequest('com_tjlms.filter.filter_search', 'filter_search', '', 'STRING');

		$this->courseCreators = $this->get('CourseCreators');
		$this->filterstates->creator_filter = $app->getUserStateFromRequest('com_tjlms.filter.creator_filter', 'creator_filter', 0, 'INTEGER');

		$this->filterstates->course_status = $app->getUserStateFromRequest('com_tjlms.filter.course_status', 'course_status', '', 'STRING');

		$this->tags = $this->get('Tags');
		$this->filterstates->course_tag_filter = $app->getUserStateFromRequest('com_tjlms.filter.filter_tag', 'filter_tag', '', 'STRING');

		JLoader::import('components.com_fields.models.fields', JPATH_ADMINISTRATOR);
		$fieldsModel = $app->bootComponent('com_fields')->getMVCFactory()->createModel('Fields', 'Administrator', ['ignore_request' => true]);
		$fieldsModel->setState('filter.context', 'com_tjlms.course');
		$fieldsModel->setState('filter.state', 1);
		$fields = $fieldsModel->getItems();

		$this->courseFields = array();

		if (!empty($fields))
		{
			foreach ($fields as $key => $value)
			{
				if ($value->type == 'list')
				{
					$name = $value->name;

					if (!empty($value->name))
					{
						$this->courseFields[$key]['name'] = $name;
					}

					$options = array();

					foreach ($value->fieldparams->get('options') as $option)
					{
						$option->value = empty($option->value) ? '' : $option->value;
						$options[] = HTMLHelper::_('select.option', $option->value, $option->name);
					}

					if (!empty($options) && !empty($name))
					{
						$this->courseFields[$key]['options'] = $options;
					}
				}
			}
		}

		$this->filterstates->course_fields = $app->getUserStateFromRequest('com_tjlms.filter.course_fields', 'course_fields', '', 'STRING'
				);

		parent::display($tpl);
	}
}
