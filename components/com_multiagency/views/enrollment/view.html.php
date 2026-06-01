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
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Language\Text;

jimport('joomla.application.component.view');

/**
 * View class for a list of Multiagency.
 *
 * @since  1.6
 */
class MultiagencyViewEnrollment extends HtmlView
{
	protected $items;

	protected $pagination;

	protected $state;

	protected $params;

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
		$this->license = $app->input->get('license', '0', 'INT');

		BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_multiagency/models/licenceform.php');
		$licenseModel = BaseDatabaseModel::getInstance('LicenceForm', 'MultiagencyModel');
		$licenseId = $app->input->get('license', '0', 'INT');
		$licenseData = $licenseModel->getData($licenseId);
		$this->licenseType = $licenseData->type;
		$this->courseId = $licenseData->course_id;
		$this->agenciesId = $app->getUserStateFromRequest('.agencies', 'agencies');

		FormHelper::addFieldPath(JPATH_ADMINISTRATOR . '/components/com_tjlms/models/fields/');
		$Courses = FormHelper::loadFieldType('courses', false);
		$this->courseoptions = $Courses->getOptionsExternally();
		$this->courseFilter = $app->getUserStateFromRequest('com_multiagency.enrollment.filter.selectedcourse', 'selectedcourse');

		$helperPath = JPATH_ADMINISTRATOR . '/components/com_tjlms/helpers/tjlms.php';
		JLoader::register('TjlmsHelper', $helperPath);
		JLoader::load('TjlmsHelper');
		$i = 0;

		foreach ($this->courseoptions as $course)
		{
			$canEnroll = TjlmsHelper::canManageCourseEnrollment($course->value);

			if (!$canEnroll)
			{
				unset($this->courseoptions[$i]);
			}

			$i++;
		}

		if (empty($this->courseFilter) && $this->licenseType === 'all')
		{
			$app->setUserState('com_multiagency.enrollment.filter.selectedcourse', $this->courseoptions[0]->value);
		}

		if ($this->licenseType === 'per')
		{
			$app->setUserState('com_multiagency.enrollment.filter.selectedcourse', $this->courseId);
		}

		$subuserAuth = RBACL::authorise(Factory::getUser()->id, 'com_multiagency', 'core.manageenrollment', 'com_multiagency');

		if (!$subuserAuth)
		{
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');

			return $app->setHeader('status', 403, true);
		}

		BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_multiagency/models');
		$licenseModel = BaseDatabaseModel::getInstance('Licences', 'MultiagencyModel', array('ignore_request' => true));

		if (!$licenseModel->isValidLicense($this->license))
		{
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');

			return $app->setHeader('status', 403, true);
		}

		$this->state      = $this->get('State');
		$this->items = $this->get('Items');

		// Get agencies
		BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_multiagency/models');
		$multiagencyModel = BaseDatabaseModel::getInstance('Multiagency', 'MultiagencyModel');
		$this->agencyData = $multiagencyModel->getData($licenseData->multiagency_id);

		$this->pagination = $this->get('Pagination');
		$this->params     = $app->getParams('com_multiagency');
		parent::display($tpl);
	}
}
