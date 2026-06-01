<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproModelHistory extends JModelList
{
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'date', 'ip', 'type'
			);
		}

		parent::__construct($config);
	}
	
	public function getId()
	{
		return JFactory::getApplication()->input->getInt('id');
	}
	
	protected function getListQuery()
	{
		$db 	= JFactory::getDbo();
		$query 	= $db->getQuery(true);
		
		$query->select('*')
			  ->from('#__rsticketspro_ticket_history')
			  ->where($db->qn('ticket_id').'='.$db->q($this->getId()))
			  ->order($db->qn($this->getState('list.ordering', 'date')).' '.$db->escape($this->getState('list.direction', 'desc')));
		
		return $query;
	}
	
	protected function populateState($ordering = 'date', $direction = 'desc')
	{
		// List state information.
		parent::populateState($ordering, $direction);
	}
}