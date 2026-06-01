<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproTablePriorities extends JTable
{
	public $id = null;
	
	public $name = '';
	public $bg_color = '';
	public $fg_color = '';
	
	public $published = 1;
	public $ordering = null;
	
	public function __construct(&$db)
	{
		parent::__construct('#__rsticketspro_priorities', 'id', $db);
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
			
			$query->select($db->qn('id'))
				  ->from('#__rsticketspro_priorities')
				  ->where($db->qn('published') . '=' . $db->q(1));
			if ($priority_id = $db->setQuery($query)->loadResult())
			{
				$query->clear();
				// update all tickets with the next available priority
				$query->update('#__rsticketspro_tickets')
					  ->set($db->qn('priority_id') . '=' . $db->q($priority_id))
					  ->where($db->qn('priority_id') . '=' . $db->q($pk));
				$db->setQuery($query)->execute();
			}
		}
		
		return $deleted;
	}
}