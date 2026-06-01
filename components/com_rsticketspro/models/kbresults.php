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
		$this->is_staff = RSTicketsProHelper::isStaff();

		if (!$value = JFactory::getApplication()->input->getString('filter_search',''))
		{
			return array();
		}
		
		$query		= $this->_db->getQuery(true);
		$results	= array();
		$escvalue	= $this->_db->escape($value);
		$escvalue	= str_replace('%','\%', $escvalue);
		$escvalue	= str_replace(' ','%', $escvalue);
		
		$query->select($this->_db->qn('id'))
			->from($this->_db->qn('#__rsticketspro_kb_categories'))
			->where($this->_db->qn('published').' = '.$this->_db->q(1));
		
		if (!$this->is_staff)
		{
			$query->where($this->_db->qn('private').' = '.$this->_db->q(0));
		}
		
		$this->_db->setQuery($query);
		$cat_ids = $this->_db->loadColumn();
		$cat_ids[] = 0;

		$cat_ids = array_map('intval', $cat_ids);
		
		$query->clear()
			->select($this->_db->qn('c').'.*')
			->from($this->_db->qn('#__rsticketspro_kb_content','c'))
			->join('LEFT',$this->_db->qn('#__rsticketspro_kb_categories','cat').' ON '.$this->_db->qn('c.category_id').' = '.$this->_db->qn('cat.id'))
			->where('('.$this->_db->qn('c.name').' LIKE '.$this->_db->q('%'.$escvalue.'%', false).' OR '.$this->_db->qn('c.text').' LIKE '.$this->_db->q('%'.$escvalue.'%', false).')')
			->where($this->_db->qn('c.published').' = '.$this->_db->q(1))
			->order($this->_db->qn('cat.ordering'))
			->order($this->_db->qn('c.ordering'));
			
		if (!$this->is_staff)
		{
			$query->where($this->_db->qn('c.private').' = '.$this->_db->q(0));
		}
		
		if ($cat_ids)
		{
			$query->where($this->_db->qn('c.category_id').' IN ('.implode(',',$cat_ids).')');
		}
		
		$this->_db->setQuery($query);
		if ($results = $this->_db->loadObjectList())
		{
			$category = JTable::getInstance('Kbcategories', 'RsticketsproTable');
			
			foreach ($results as $i => $result)
			{
				$parent_id = $result->category_id;
				$category->load($parent_id);
				
				while ($parent_id > 0)
				{
					$parent_id = $category->parent_id;
					$category->load($parent_id);
					
					if ($category->private)
					{
						$result->private = 1;
					}

					if (!$category->published)
					{
						$result->published = 0;
					}
				}
				
				if ((!$this->is_staff && $result->private) || !$result->published)
				{
					unset($results[$i]);
				}
			}
		}
		
		return $results;
	}
}