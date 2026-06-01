<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproTableStatuses extends JTable
{
	public $id = null;
	public $name = '';
	public $published = 1;
	public $ordering = null;
	
	public function __construct(&$db)
	{
		parent::__construct('#__rsticketspro_statuses', 'id', $db);
	}

	public function check()
	{
		if (!$this->id && !$this->ordering)
		{
			$this->ordering = $this->getNextOrder();
		}

		return true;
	}
	
	public function delete($pk = null)
	{
		$deleted = parent::delete($pk);
		if ($deleted)
		{
			$db = $this->getDbo();
			$query = $db->getQuery(true);
			
			// update all tickets with this status to "closed"
			$query->update('#__rsticketspro_tickets')
				  ->set($db->qn('status_id') . '=' . $db->q(2))
				  ->where($db->qn('status_id') . '=' . $db->q($pk));
			$db->setQuery($query)->execute();
		}
		
		return $deleted;
	}
}