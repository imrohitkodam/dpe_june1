<?php
/**
 * @package     Tjlms.Plugin
 * @subpackage  Tjlms,SEB
 *
 * @copyright   Copyright (C) 2005 - 2020 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Registry\Registry;

// No direct access
defined('_JEXEC') or die;


/**
 * Tjlms plugins
 *
 * @package     PlgTjlmsSeb
 * @subpackage  SEB
 * @since       1.0
 */
class PlgTjlmsSeb extends CMSPlugin
{
	/**
	 * Load the language file on instantiation. Note this is only available in Joomla 3.1 and higher.
	 *
	 * @var    boolean
	 * @since  1.0
	 */
	protected $autoloadLanguage = true;

	/**
	 * To show integration fields on course page
	 *
	 * @return  Array  Plugin XML path and name
	 *
	 * @since   1.3.10
	 */
	public function onPrepareIntegrationField()
	{
		Form::addFieldPath(JPATH_PLUGINS . '/tjlms/seb/fields');
		$app    = Factory::getApplication();
		$option = $app->input->get("option");
		$view   = $app->input->get("view", '');

		if ($option == 'com_tjlms' && $view == 'course')
		{
			return array(
				'path' => JPATH_PLUGINS . '/' . $this->_type . '/' . $this->_name . '/seb.xml', 'name' => 'seb'
			);
		}
	}

	/**
	 * Validate: Is Safe Exam Browser used to launch lesson
	 *
	 * @param   Object  $lesson               Lesson Object
	 * @param   Array   $lessonMappedCourses  Array of course lesson belong too
	 * @param   Object  $lessontrack          Lesson track details
	 *
	 * @return  void
	 *
	 * @since   1.3.10
	 */
	public function onBeforeLessonLaunch($lesson, $lessonMappedCourses, $lessontrack)
	{
		$app          = Factory::getApplication();
		$sebKeysArray = array();
		$result       = array('access' => 0, 'msg' => Text::_('PLG_TJLMS_SEB_CORRECT_KEY_REQUEST'));

		JLoader::import('components.com_tjlms.includes.tjlms', JPATH_ADMINISTRATOR);
		$course   = TjLms::Course();
		$registry = new Registry;

		// Get SEB key's
		foreach ($lessonMappedCourses as $courseId)
		{
			$course->load($courseId);
			$registry = new Registry($course->params);
			$sebKeys  = $registry->get('seb.seb_keys') ? trim($registry->get('seb.seb_keys')) : "";

			if (!empty($sebKeys))
			{
				$sebKeys      = preg_split('/\r\n|[\r\n]/', $sebKeys);
				$sebKeysArray = array_merge($sebKeysArray, $sebKeys);
			}
			else
			{
				// Found the one course which has no SEB config then don't validate SEB
				$result['access'] = 1;
				$result['msg']    = Text::_('PLG_TJLMS_SEB_KEY_NOT_CONFIGURED');

				return $result;
			}
		}

		$uri            = Factory::getURI();
		$absoluteUrl    = $uri->toString();
		$SEBRequestHash = $app->input->server->getString('HTTP_X_SAFEEXAMBROWSER_REQUESTHASH');

		// No SEB hash so considered lesson not launched in Safe exam browser
		if (empty($SEBRequestHash))
		{
			$result['msg'] = Text::_('PLG_TJLMS_SEB_INVALID_REQUEST');

			return $result;
		}

		// Key's available then validate if user trying to launch lesson in SEB
		foreach ($sebKeysArray as $seb_key)
		{
			$generatedToken = hash('sha256', $absoluteUrl . $seb_key);

			if (strcmp($SEBRequestHash, $generatedToken) == 0)
			{
				$result['access'] = 1;
				$result['msg']    = '';
				break;
			}
		}

		return $result;
	}
}
