<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

// no direct access
defined('_JEXEC') or die( 'Restricted access' );

class plgSearchRsticketsprocontent extends JPlugin
{
	/**
	 * Load the language file on instantiation.
	 *
	 * @var    boolean
	 * @since  3.1
	 */
	protected $autoloadLanguage = true;
	
	/**
	 * @return array An array of search areas
	 */
	public function onContentSearchAreas() {
		static $areas = array(
			'rsticketsprocontent' => 'Knowledgebase'
		);
		return $areas;
	}
	
	/**
	 * Contacts Search method
	 *
	 * The sql must return the following fields that are used in a common display
	 * routine: href, title, section, created, text, browsernav
	 *
	 * @param string Target search string
	 * @param string matching option, exact|any|all
	 * @param string ordering option, newest|oldest|popular|alpha|category
	 */
	public function onContentSearch($text, $phrase = '', $ordering = '', $areas = null) {
		if (!file_exists(JPATH_ADMINISTRATOR.'/components/com_rsticketspro/helpers/rsticketspro.php'))
			return false;
			
		require_once JPATH_ADMINISTRATOR.'/components/com_rsticketspro/helpers/rsticketspro.php';
		
		if (is_array($areas)) {
			if (!array_intersect($areas, array_keys($this->onContentSearchAreas()))) {
				return array();
			}
		}
		
		$db			= JFactory::getDbo();
		$query		= $db->getQuery(true);
		$searchText = $text;
		$text		= trim($text);
		$results	= array();

		if ($text == '') {
			return array();
		}
		
		$limit			= $this->params->def('search_limit', 50);
		$uncategorised	= $this->params->def('search_uncategorised', 1);
		$itemid			= $this->params->def('Itemid',0);
		$itemid			= $itemid ? '&Itemid='.$itemid : '';
		
		$query->select($db->qn('a.id'))
			->select($db->qn('a.category_id'))
			->select($db->qn('a.name','title'))
			->select($db->qn('a.created'))->select($db->qn('a.text'))
			->select($db->qn('c.name','section'))
			->from($db->qn('#__rsticketspro_kb_content','a'))
			->join('LEFT',$db->qn('#__rsticketspro_kb_categories','c').' ON '.$db->qn('a.category_id').' = '.$db->qn('c.id'))
			->where($db->qn('a.published').' = '.$db->q(1));
		
		if (!RSTicketsProHelper::isStaff()) {
			$query->where($db->qn('a.private').' = '.$db->q(0));
		}
		
		if (!$uncategorised) {
			$query->where($db->qn('a.category_id').' > '.$db->q(0));
		}
		
		switch ($phrase) {
			case 'exact':
				$text = $db->q('%' . $db->escape($text, true) . '%', false);
				$query->where('('.$db->qn('a.name').' LIKE '.$text.' OR '.$db->qn('a.text').' LIKE '.$text.')');
				break;

			case 'all':
			case 'any':
			default:
				$words	= explode(' ', $text);
				$wheres	= array();
				
				foreach ($words as $word) {
					$word = $db->q('%' . $db->escape($word, true) . '%', false);
					$wheres2   = array();
					$wheres2[] = $db->qn('a.name').' LIKE ' . $word;
					$wheres2[] = $db->qn('a.text').' LIKE ' . $word;
					$wheres[]  = implode(' OR ', $wheres2);
				}
				
				$where = '(' . implode(($phrase == 'all' ? ') AND (' : ') OR ('), $wheres) . ')';
				$query->where($where);
				break;
		}

		switch ($ordering) {
			case 'oldest':
				$query->order($db->qn('a.created').'  ASC');
			break;

			case 'alpha':
				$query->order($db->qn('a.name').'  ASC');
			break;

			case 'category':
				$query->order($db->qn('section').'  ASC');
			break;

			case 'newest':
			default:
				$query->order($db->qn('a.created').'  DESC');
			break;
		}

		$db->setQuery($query, 0, $limit);
		if ($rows = $db->loadObjectList()) {
			foreach ($rows as $i => $row) {
				$row->href			= JRoute::_('index.php?option=com_rsticketspro&view=article&cid='.$row->id.':'.JFilterOutput::stringURLSafe($row->title).$itemid);
				$row->browsernav	= 2;
				$row->created		= $row->created;
				
				if (!$row->category_id && $uncategorised)
					$row->section = JText::_('Uncategorised Content');
				
				$results[] = $row;
			}
		}
		
		return $results;
	}
}