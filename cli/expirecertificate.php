<?php
/**
 * @package     TJLms
 * @subpackage  com_shika
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2021 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// Make sure this is being called from the command line

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (PHP_SAPI !== 'cli')
{
	die('This is a command line only application.');
}

const _JEXEC = 1;

if (file_exists(dirname(__DIR__) . '/defines.php'))
{
	require_once dirname(__DIR__) . '/defines.php';
}

if (!defined('_JDEFINES'))
{
	define('JPATH_BASE', dirname(__DIR__));
	require_once JPATH_BASE . '/includes/defines.php';
	require_once JPATH_BASE . '/includes/framework.php';
}

require_once JPATH_LIBRARIES . '/import.legacy.php';
require_once JPATH_LIBRARIES . '/cms.php';
require_once JPATH_CONFIGURATION . '/configuration.php';

jimport('joomla.application.cli');
ini_set('display_errors', 'On');

jimport('joomla.application.component.model');
jimport('joomla.application.component.view');
jimport('joomla.application.component.modellist');

use Joomla\CMS\Log\Log;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Table\Table;
use Joomla\Registry\Registry;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Filesystem\File;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Filter\OutputFilter;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

$app  = Factory::getApplication('site');
$app->initialise();
$lang = Factory::getLanguage();
$lang->load('com_tjlms', JPATH_SITE, 'en-GB', true);

/**
 * A command-line cron job to expire the certificates & archive the corresponding lesson attempts.
 *
 * @since  1.4.0
 */
class ExpireCertificate extends CliApplication
{
	/**
	 * Entry point for CLI script
	 *
	 * @return  void
	 *
	 * @since   1.4.0
	 */
	public function execute()
	{
		$category = "expire_certificate";

		Log::addLogger(array('text_file' => "expire_certificate.php"), Log::ALL, array($category));

		$this->out(Text::_("COM_TJLMS_EXPIRE_CERTIFICATE_ARCHIVE_LESSONS_START"));
		Log::add(Text::_("COM_TJLMS_EXPIRE_CERTIFICATE_ARCHIVE_LESSONS_START"), Log::INFO, $category);

		$db    = Factory::getDBO();
		$query = $db->getQuery(true);
		$query->select('c.id');
		$query->from($db->quoteName('#__tjlms_courses', 'c'));
		$query->where($db->quoteName('certificate_id') . "<> 0");
		$query->where($db->quoteName('expiry') . "<> 0");
		$db->setQuery($query);
		$courses = $db->loadObjectList();

		require_once JPATH_ADMINISTRATOR . "/components/com_tjlms/includes/course.php";
		require_once JPATH_ADMINISTRATOR . "/components/com_tjlms/includes/tjlms.php";
		require_once JPATH_SITE . "/components/com_tjlms/helpers/tjdbhelper.php";
		require_once JPATH_SITE . "/components/com_tjlms/helpers/lesson.php";
		Tjlms::init();

		foreach ($courses as $course)
		{
			$courseData = Tjlms::course($course->id);

			if (!$courseData->expireCertificate())
			{
				$error = $courseData->getError();
				$this->out(Text::_("COM_TJLMS_EXPIRE_CERTIFICATE_ARCHIVE_LESSONS_ERROR"));
				Log::add(Text::_("COM_TJLMS_EXPIRE_CERTIFICATE_ARCHIVE_LESSONS_ERROR"), Log::INFO, $error);
			}
			else
			{
				$this->out(Text::_("COM_TJLMS_EXPIRE_CERTIFICATE_ARCHIVE_LESSONS_END"));
				Log::add(Text::_("COM_TJLMS_EXPIRE_CERTIFICATE_ARCHIVE_LESSONS_END"), Log::INFO, 'SUCCESS');
			}
		}
	}
}

JApplicationCli::getInstance('ExpireCertificate')->execute();
