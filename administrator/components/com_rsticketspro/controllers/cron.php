<?php
/**
* @version 2.0.0
* @package RSTickets! Pro 2.0.0
* @copyright (C) 2010 www.rsjoomla.com
* @license GPL, http://www.gnu.org/licenses/gpl-2.0.html
*/

defined('_JEXEC') or die('Restricted access');

class RsticketsproControllerCron extends JControllerForm
{
	public function preview()
	{
		JFactory::getApplication()->triggerEvent('onCronTestConnection');
	}
}