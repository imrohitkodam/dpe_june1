<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproModelKbresults extends JModelLegacy
{
	public function getItems()
	{
		// Load the list items.
		$query = $this->getListQuery();

		try
		{
			$items = $this->_getList($query, 0, 10);
		}
		catch (RuntimeException $e)
		{
			$this->setError($e->getMessage());
			return false;
		}
		
		return $items;
	}
	
	protected function getListQuery()
	{
		$db 	= JFactory::getDbo();
		$query 	= $db->getQuery(true);
		
		// get the search string
		$value = JFactory::getApplication()->input->get('filter_search', '', 'raw');
		// escape it with extra characters
		$value = $db->escape($value, true);
		// just quote it
		$value = $db->q('%'.$value.'%', false);
		
		$query->select('*')
			  ->from($db->qn('#__rsticketspro_kb_content'))
			  ->where('('.$db->qn('name').' LIKE '.$value.' OR '.$db->qn('text').' LIKE '.$value.')')
			  ->where($db->qn('published').'='.$db->q(1))
			  ->order($db->qn('name'));
		return $query;
	}
}