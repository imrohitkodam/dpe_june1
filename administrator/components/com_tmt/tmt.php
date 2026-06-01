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
use Joomla\CMS\Access\Exception\NotAllowed;
use Joomla\CMS\MVC\Controller\BaseController;

// Access check.
if (!Factory::getUser()->authorise('core.manage', 'com_tmt'))
{
	throw new NotAllowed(Text::_('JERROR_ALERTNOAUTHOR'), 403);
}

JLoader::import('components.com_tmt.includes.tmt', JPATH_ADMINISTRATOR);
TMT::init('admin');

// Deprecated - Start
// Load defines file.
require_once JPATH_SITE . '/components/com_tmt/defines.php';

// Load frontend helper.
if (!class_exists('tmtFrontendHelper'))
{
	$helperPath = JPATH_SITE . '/components/com_tmt/helper.php';
	JLoader::register('tmtFrontendHelper', $helperPath);
	JLoader::load('tmtFrontendHelper');
}

// Load category helper.
if (!class_exists('tmtCategoryHelper'))
{
	$categoryHelperPath = JPATH_SITE . '/components/com_tmt/helpers/category.php';
	JLoader::register('tmtCategoryHelper', $categoryHelperPath);
	JLoader::load('tmtCategoryHelper');
}

// Load questions helper.
if (!class_exists('tmtQuestionsHelper'))
{
	$questionsHelperPath = JPATH_SITE . '/components/com_tmt/helpers/questions.php';
	JLoader::register('tmtQuestionsHelper', $questionsHelperPath);
	JLoader::load('tmtQuestionsHelper');
}

// Load Candidate helper.
if (!class_exists('tmtCandidateHelper'))
{
	$tmtCandidateHelperPath = JPATH_SITE . '/components/com_tmt/helpers/candidate.php';
	JLoader::register('tmtCandidateHelper', $tmtCandidateHelperPath);
	JLoader::load('tmtCandidateHelper');
}

// Load tests helper.
if (!class_exists('tmtTestsHelper'))
{
	$testsHelperPath = JPATH_SITE . '/components/com_tmt/helpers/tests.php';
	JLoader::register('tmtTestsHelper', $testsHelperPath);
	JLoader::load('tmtTestsHelper');
}

// Load Emails helper.
if (!class_exists('tmtEmailsHelper'))
{
	$tmtEmailsHelperPath = JPATH_SITE . '/components/com_tmt/helpers/emails.php';
	JLoader::register('tmtEmailsHelper', $tmtEmailsHelperPath);
	JLoader::load('tmtEmailsHelper');
}

$helperPath = dirname(__FILE__) . '/helpers/tmt.php';

if (!class_exists('TmtHelper'))
{
	JLoader::register('TmtHelper', $helperPath);
	JLoader::load('TmtHelper');
}
// Deprecated - End

// Load Tjlms main helper
if (!class_exists('comtjlmsHelper'))
{
	$comtjlmsHelperPath = JPATH_SITE . '/components/com_tjlms/helpers/main.php';

	JLoader::register('comtjlmsHelper', $comtjlmsHelperPath);
	JLoader::load('comtjlmsHelper');
}

$controller	= BaseController::getInstance('Tmt');
$controller->execute(Factory::getApplication()->input->get('task'));
$controller->redirect();
