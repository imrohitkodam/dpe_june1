<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_search
 *
 * @copyright   (C) 2005 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Helper\ModuleHelper;

// Include the search functions only once
JLoader::register('ModSearchHelper', __DIR__ . '/helper.php');

$lang       = Factory::getLanguage();
$app        = Factory::getApplication();
$set_Itemid = (int) $params->get('set_itemid', 0);
$mitemid    = $set_Itemid > 0 ? $set_Itemid : $app->input->getInt('Itemid');

if ($params->get('opensearch', 1))
{
	$doc = Factory::getDocument();

	$ostitle = $params->get('opensearch_title', Text::_('MOD_SEARCH_SEARCHBUTTON_TEXT') . ' ' . $app->get('sitename'));
	$doc->addHeadLink(
			Uri::getInstance()->toString(array('scheme', 'host', 'port'))
			. Route::_('&option=com_search&format=opensearch&Itemid=' . $mitemid), 'search', 'rel',
			array(
				'title' => htmlspecialchars($ostitle, ENT_COMPAT, 'UTF-8'),
				'type' => 'application/opensearchdescription+xml'
			)
		);
}

$upper_limit     = $lang->getUpperLimitSearchWord();
$button          = $params->get('button', 0);
$imagebutton     = $params->get('imagebutton', 0);
$button_pos      = $params->get('button_pos', 'left');
$button_text     = htmlspecialchars($params->get('button_text', Text::_('MOD_SEARCH_SEARCHBUTTON_TEXT')), ENT_COMPAT, 'UTF-8');
$width           = (int) $params->get('width');
$maxlength       = $upper_limit;
$text            = htmlspecialchars($params->get('text', Text::_('MOD_SEARCH_SEARCHBOX_TEXT')), ENT_COMPAT, 'UTF-8');
$label           = htmlspecialchars($params->get('label', Text::_('MOD_SEARCH_LABEL_TEXT')), ENT_COMPAT, 'UTF-8');
$moduleclass_sfx = htmlspecialchars($params->get('moduleclass_sfx', ''), ENT_COMPAT, 'UTF-8');

if ($imagebutton)
{
	$img = ModSearchHelper::getSearchImage();
}

require ModuleHelper::getLayoutPath('mod_search', $params->get('layout', 'default'));
