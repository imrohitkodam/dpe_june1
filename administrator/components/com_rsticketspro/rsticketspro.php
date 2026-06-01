<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

// Access check.
$user = JFactory::getUser();
if (!$user->authorise('core.manage', 'com_rsticketspro'))
{
    throw new Exception(JText::_('JERROR_ALERTNOAUTHOR'), 403);
}

$lang = JFactory::getLanguage();

// load frontend
$lang->load('com_rsticketspro', JPATH_SITE, 'en-GB', true);
$lang->load('com_rsticketspro', JPATH_SITE, $lang->getDefault(), true);
$lang->load('com_rsticketspro', JPATH_SITE, null, true);

// load backend
$lang->load('com_rsticketspro', JPATH_ADMINISTRATOR, 'en-GB', true);
$lang->load('com_rsticketspro', JPATH_ADMINISTRATOR, $lang->getDefault(), true);
$lang->load('com_rsticketspro', JPATH_ADMINISTRATOR, null, true);

// Require helper files
require_once __DIR__ . '/helpers/adapter.php';
require_once __DIR__ . '/helpers/rsticketspro.php';
require_once __DIR__ . '/helpers/toolbar.php';

JHtml::_('jquery.framework', true);
JHtml::_('stylesheet', 'com_rsticketspro/rsticketspro.css', array('relative' => true, 'version' => 'auto'));
JHtml::_('stylesheet', 'com_rsticketspro/icons.css', array('relative' => true, 'version' => 'auto'));
JHtml::_('script', 'com_rsticketspro/rsticketspro.js', array('relative' => true, 'version' => 'auto'));

if (version_compare(JVERSION, '4.0', '>='))
{
	JHtml::_('stylesheet', 'com_rsticketspro/style40.css', array('relative' => true, 'version' => 'auto'));
}
else
{
	JHtml::_('stylesheet', 'com_rsticketspro/style30.css', array('relative' => true, 'version' => 'auto'));
}

// Require the base controller
require_once __DIR__ . '/controller.php';

$controller	= JControllerLegacy::getInstance('Rsticketspro');
$task = JFactory::getApplication()->input->get('task');
$controller->execute($task);
$controller->redirect();