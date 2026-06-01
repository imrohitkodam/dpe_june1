<?php
/**    
 * SEO Glossary
 *
 * We developed this code with our hearts and passion.
 * We hope you found it useful, easy to understand and change.
 * Otherwise, please feel free to contact us at contact@joomunited.com
 *
 * @package 	SEO Glossary
 * @copyright 	Copyright (C) 2012 JoomUnited (http://www.joomunited.com). All rights reserved.
 * @license 	GNU General Public License version 2 or later; http://www.gnu.org/licenses/gpl-2.0.html
 */

// no direct access
defined('_JEXEC') or die;

// Access check.
if ( !JFactory::getUser()->authorise( 'core.manage', 'com_seoglossary' ) ) 
{
	return JFactory::getApplication()->enqueueMessage(JText::_( 'JERROR_ALERTNOAUTHOR' ),'error' );
}

// Include dependancies
jimport( 'joomla.application.component.controller' );
$doc = JFactory::getDocument();
$doc->addStyleSheet( JURI::base( true ) . "/components/com_seoglossary/assets/css/style.css" );
require_once(JPATH_SITE . '/components/com_seoglossary/helpers/compat.php');
require_once(JPATH_ADMINISTRATOR . '/components/com_seoglossary/helpers/seoglossary.php');
SeoglossaryBackendHelper::loadLanguage();
$controller	= SeogController::getInstance('Seoglossary');
$task = JFactory::getApplication()->input->getString( 'task' );
if ( !empty($task) && FALSE !== strpos($task, '.') )
    list( $view, $task ) = explode('.', $task, 2);

$controller->execute( $task );

$controller->redirect();
