<?php
/**
 *  @package     XT Transitional Package from FrameworkOnFramework
 *  @subpackage  include
 *  @copyright   Copyright (C) 2010-2015 Nicholas K. Dionysopoulos
 *  @license     GNU General Public License version 2, or later
 *
 *  Initializes XTF0F
 */

defined('_JEXEC') or die();

if (!defined('XTF0F_INCLUDED'))
{
    define('XTF0F_INCLUDED', '2.5.4');

	// Register a debug log
	if (defined('JDEBUG') && JDEBUG)
	{
		XTF0FPlatform::getInstance()->logAddLogger('xtfof.log.php');
	}
}