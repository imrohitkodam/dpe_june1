<?php
/**    
 * SEO Glossary mail file
 *
 * We developed this code with our hearts and passion.
 * We hope you found it useful, easy to understand and change.
 * Otherwise, please feel free to contact us at contact@joomunited.com
 *
 * @package 	SEO Glossary
 * @copyright 	Copyright (C) 2012 JoomUnited (http://www.joomunited.com). All rights reserved.
 * @license 	GNU General Public License version 2 or later; http://www.gnu.org/licenses/gpl-2.0.html
 */

defined('_JEXEC') or die;

// Include dependancies
jimport('joomla.application.component.controller');

// Execute the task.
$doc = JFactory::getDocument();
require_once JPATH_COMPONENT . '/helpers/seoglossary.php';
SeoglossaryHelper::loadLanguage();

if (!defined('SEOG_CSS')) {
	$doc->addStyleSheet( JURI::base( true ) . "/components/com_seoglossary/assets/css/style.css" );
	define('SEOG_CSS',1);
}

JFactory::getConfig()->set('gzip', 0);
require_once(JPATH_SITE . '/components/com_seoglossary/helpers/compat.php');
$controller	= SeogController::getInstance('Seoglossary');
$task = JFactory::getApplication()->input->getString('task','');
$controller->execute($task);

if (!empty($task)) {
	$catid=JFactory::getApplication()->input->getInt('catid',0);
	if($catid!=0)
	{
		 $Itemid = SeoglossaryHelper::getGlossaryMenu($catid);
				$item_id="";
				if($Itemid)
				{
					$item_id='&Itemid='.$Itemid;
					
				}
	$controller->setRedirect(JRoute::_('index.php?option=com_seoglossary&view=glossaries&catid=' . $catid.$item_id));
	}
}
$controller->redirect();

