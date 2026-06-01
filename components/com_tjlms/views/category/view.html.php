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
use Joomla\Registry\Registry;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

jimport('joomla.application.component.view');

/**
 * view of courses
 *
 * @since  1.0
 */
class TjlmsViewCategory extends HtmlView
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
		global $mainframe;
		$mainframe = Factory::getApplication();
		$model = $this->getModel();
		$input = Factory::getApplication()->input;
		$this->ol_user = Factory::getUser();
		$this->tjlmsFrontendHelper = new comtjlmsHelper;
		$this->tjlmsCoursesHelper = new tjlmsCoursesHelper;
		$this->tjlmsparams = $this->tjlmsFrontendHelper->getcomponetsParams('com_tjlms');

		$this->course_image_upload_path = $this->tjlmsparams->get('course_image_upload_path', '', 'STRING');

		$params = $mainframe->getParams();
		$menuParams = new Registry;

		if ($menu = $mainframe->getMenu()->getActive())
		{
			$menuParams->loadString($menu->params);
		}

		$cat_id = $menuParams->get('defaultCatId');

		if (!$model->checkifCatPresent($cat_id))
		{
			$mainframe->enqueueMessage(Text::_('COM_TJLMS_INVLAID_CAT'), 'error');

			return;
		}

		$this->courses = $model->getcoursesByCats($cat_id);

		// Get itemidof all courses view
		$this->allcousresItemid = $this->tjlmsFrontendHelper->getItemId('index.php?option=com_tjlms&view=courses&layout=all');

		// Check if user is admin
		$this->ifuseradmin = $this->tjlmsFrontendHelper->checkAdmin($this->ol_user);

		// $this->assignRef('grades', $grades);
		$allcourses_url = 'index.php?option=com_tjlms&view=courses&Itemid=' . $this->allcousresItemid;
		$cat_url = 'index.php?option=com_tjlms&view=courses&Itemid=' . $this->allcousresItemid;

		$this->allcourses_url = Route::_($allcourses_url, false);
		$this->cat_url = $cat_url;
		$this->pagination = $this->get('Pagination');

		parent::display($tpl);
	}
}
