<?php
/**
 * Latest SEO Glossary module main file
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
defined( '_JEXEC' ) or die ;
if(!defined('DS')){
define('DS',DIRECTORY_SEPARATOR);
}

// Include the syndicate functions only once
require_once dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'helper.php';

if ( !is_dir( JPATH_SITE . '/components/com_seoglossary/models' ) )
{
	return;
}

require_once(JPATH_SITE . '/components/com_seoglossary/helpers/seoglossary.php');
SeoglossaryHelper::loadLanguage();

$moduleclass_sfx = htmlspecialchars( $params->get( 'moduleclass_sfx' ) );

require JModuleHelper::getLayoutPath( 'mod_searchseoglossary', $params->get( 'layout', 'default' ) );
