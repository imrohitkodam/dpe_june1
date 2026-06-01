<?php
/**
 * @package     TMT
 * @subpackage  com_shika
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Controller\BaseController;

JLoader::import('components.com_tmt.includes.tmt', JPATH_ADMINISTRATOR);
TMT::init();

require_once JPATH_SITE . '/components/com_tjlms/defines.php';

$input = Factory::getApplication()->input;
$view  = $input->get('view', '', 'string');

// Get TjLms params
$tjlmsparams = ComponentHelper::getParams('com_tjlms');

if ($tjlmsparams->get('load_bootstrap') == '1' || $view == 'answersheet' || $view == 'test' ||  $view == 'testpremise')
{
	// Load bootstrap CSS and JS.
	HTMLHelper::stylesheet('media/techjoomla_strapper/bs3/css/bootstrap.css');
	HTMLHelper::_('bootstrap.framework');
}

HTMLHelper::stylesheet('media/com_tjlms/vendors/artificiers/artficier.css');

// Load Tjlms helper
$path = JPATH_SITE . '/components/com_tjlms/helpers/main.php';

if (!class_exists('comtjlmsHelper'))
{
	// Require_once $path;
	JLoader::register('comtjlmsHelper', $path);
	JLoader::load('comtjlmsHelper');
}

// Load Tjlms lesson helper
$path = JPATH_SITE . '/components/com_tjlms/helpers/lesson.php';

if (!class_exists('TjlmsLessonHelper'))
{
	// Require_once $path;
	JLoader::register('TjlmsLessonHelper', $path);
	JLoader::load('TjlmsLessonHelper');
}

// Deprecated - Start
// Define DIRECTORY_SEPARATOR.
if (!defined('DS'))
{
	define('DS', '/');
}

// Load defines file.
require_once JPATH_COMPONENT . '/defines.php';

// Load frontend helper.
if (!class_exists('tmtFrontendHelper'))
{
	$helperPath = dirname(__FILE__) . DS . 'helper.php';
	JLoader::register('tmtFrontendHelper', $helperPath);
	JLoader::load('tmtFrontendHelper');
}

$TmtHelper = JPATH_SITE . '/components/com_tmt/helpers/tmt.php';

// Load frontend helper.
if (!class_exists('TmtHelper'))
{
	JLoader::register('TmtHelper', $TmtHelper);
	JLoader::load('TmtHelper');
}

// Load category helper.
if (!class_exists('tmtCategoryHelper'))
{
	$categoryHelperPath = dirname(__FILE__) . DS . 'helpers' . DS . 'category.php';
	JLoader::register('tmtCategoryHelper', $categoryHelperPath);
	JLoader::load('tmtCategoryHelper');
}

// Load questions helper.
if (!class_exists('tmtQuestionsHelper'))
{
	$questionsHelperPath = dirname(__FILE__) . DS . 'helpers' . DS . 'questions.php';
	JLoader::register('tmtQuestionsHelper', $questionsHelperPath);
	JLoader::load('tmtQuestionsHelper');
}

// Load Candidate helper.
if (!class_exists('tmtCandidateHelper'))
{
	$tmtCandidateHelperPath = dirname(__FILE__) . DS . 'helpers' . DS . 'candidate.php';
	JLoader::register('tmtCandidateHelper', $tmtCandidateHelperPath);
	JLoader::load('tmtCandidateHelper');
}

// Load tests helper.
if (!class_exists('tmtTestsHelper'))
{
	$testsHelperPath = dirname(__FILE__) . DS . 'helpers' . DS . 'tests.php';
	JLoader::register('tmtTestsHelper', $testsHelperPath);
	JLoader::load('tmtTestsHelper');
}

if (!class_exists('subUsersHelper'))
{
	$subUsersHelperPath = JPATH_ROOT . '/components/com_subusers/helpers/subusers.php';
	JLoader::register('subUsersHelper', $subUsersHelperPath);
	JLoader::load('subUsersHelper');
}

// Load Emails helper.
if (!class_exists('tmtEmailsHelper'))
{
	$tmtEmailsHelperPath = dirname(__FILE__) . DS . 'helpers' . DS . 'emails.php';
	JLoader::register('tmtEmailsHelper', $tmtEmailsHelperPath);
	JLoader::load('tmtEmailsHelper');
}

// Load frontend helper.
if (!class_exists('viFrontendHelper'))
{
	$viFrontendHelper = JPATH_ROOT . '/components/com_vi/helper.php';
	JLoader::register('viFrontendHelper', $viFrontendHelper);
	JLoader::load('viFrontendHelper');
}
// Deprecated - End

// Execute the task.
$controller = BaseController::getInstance('Tmt');
$controller->execute(Factory::getApplication()->input->get('task'));
$controller->redirect();
