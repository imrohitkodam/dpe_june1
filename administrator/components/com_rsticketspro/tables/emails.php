<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproTableEmails extends JTable
{
	public $id = null;
	public $lang = null;
	public $type = '';
	public $subject = '';
	public $message = '';
	
	public function __construct(&$db)
	{
		parent::__construct('#__rsticketspro_emails', 'id', $db);
	}
}