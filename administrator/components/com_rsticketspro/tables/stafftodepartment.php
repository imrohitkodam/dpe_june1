<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproTableStafftodepartment extends JTable
{
	public $id = null;
	
	public $user_id = null;
	public $department_id = null;
	
	public function __construct(&$db)
	{
		parent::__construct('#__rsticketspro_staff_to_department', 'id', $db);
	}
}