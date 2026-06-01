<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproControllerStaff extends JControllerForm
{
	protected $view_list = 'staffs';

	public function allowAdd($data = array())
	{
		return JFactory::getUser()->authorise('staff.create', 'com_rsticketspro');
	}

	public function allowEdit($data = array(), $key = 'id')
	{
		return JFactory::getUser()->authorise('staff.edit', 'com_rsticketspro');
	}
}