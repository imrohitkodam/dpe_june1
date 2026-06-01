<?php
/**
 * @version    SVN: <svn_id>
 * @package    Plg_System_Tjlms
 * @copyright  Copyright (C) 2005 - 2014. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * Shika is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

// No direct access.
defined('_JEXEC') or die;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

jimport('joomla.application.component.view');

/**
 * Certificate view
 *
 * @since       1.0.0
 * @deprecated  1.3.32 Use TJCertificate certificate view instead
 */
class TjlmsViewCertificate extends HtmlView
{
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
		$app  = Factory::getApplication();
		$this->userid = Factory::getUser()->id;

		if ($this->userid)
		{
			require_once JPATH_SITE . '/components/com_tjlms/models/course.php';
			$this->tjlmsModelcourse = new TjlmsModelcourse;
			$this->comtjlmsHelper = new comtjlmsHelper;
			$model = $this->getModel();

			$input = Factory::getApplication()->input;
			$courseId = $input->get('course_id', '0', 'INT');
			$this->course_id = $courseId;

			// Check if your has completed the course
			$this->isCompleted = $model->checkIfCourseCompleted($this->userid, $courseId);

			if (!$this->isCompleted)
			{
				$app->enqueueMessage(Text::_('COM_TJLMS_CERTIFICATE_CONDITION_FAILED'), 'warning');
				$app->setHeader('status', 500, true);

				return false;
			}

			// Check if course certificate has expired
			$this->isExpired = $model->checkIfCourseCertExpired($this->userid, $courseId);

			if ($this->isExpired === true)
			{
				$app->enqueueMessage(Text::_('COM_TJLMS_CERTIFICATE_EXPIRED'), 'warning');
				$app->setHeader('status', 500, true);

				return false;
			}

			$this->html = $this->tjlmsModelcourse->getcertificateHTML($courseId, $this->userid);
		}

		parent::display($tpl);
	}
}
