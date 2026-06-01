<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

// Load language
$lang = JFactory::getLanguage();
$lang->load('com_rsticketspro', JPATH_SITE, 'en-GB', true);
$lang->load('com_rsticketspro', JPATH_SITE, $lang->getDefault(), true);
$lang->load('com_rsticketspro', JPATH_SITE, null, true);

// Require helper files
require_once JPATH_ADMINISTRATOR . '/components/com_rsticketspro/helpers/adapter.php';
require_once JPATH_ADMINISTRATOR . '/components/com_rsticketspro/helpers/rsticketspro.php';

$lang = JFactory::getLanguage();

// load frontend
$lang->load('com_rsticketspro', JPATH_SITE, 'en-GB', true);
$lang->load('com_rsticketspro', JPATH_SITE, $lang->getDefault(), true);
$lang->load('com_rsticketspro', JPATH_SITE, null, true);

// DPE hack to show the language constant

 JText::script('RST_MAX_UPLOAD_FILES_REACHED');
 JText::script('RST_TICKET_ATTACHMENTS');
 JText::script('RST_TICKET_ATTACHMENTS_REQUIRED');
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

if (RSTicketsProHelper::getConfig('jquery'))
{
	JHtml::_('jquery.framework');
}

if (RSTicketsProHelper::getConfig('bootstrap'))
{
	JHtml::_('bootstrap.framework');

	// Load optional rtl Bootstrap css and Bootstrap bugfixes
	JHtml::_('bootstrap.loadCss', $includeMaincss = true, JFactory::getDocument()->direction);
}

// Require the base controller
require_once __DIR__ . '/controller.php';

$controller	= JControllerLegacy::getInstance('Rsticketspro');
$task = JFactory::getApplication()->input->get('task');
$controller->execute($task);
$controller->redirect();