<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */
defined('JPATH_PLATFORM') or die;

JFormHelper::loadFieldClass('list');

class JFormFieldKBCategories extends JFormFieldList
{
	protected $type = 'KBCategories';
	
	protected function getOptions()
	{
		// Initialize variables.
		$options = array();
		
		if (isset($this->element['please']) && $this->element['please'] == 'true')
		{
			$options[] = JHtml::_('select.option', '', JText::_('RST_KB_SELECT_CATEGORY'));
		}
		
		if (isset($this->element['show_noparent']) && $this->element['show_noparent'] == 'true')
		{
			$options[] = JHtml::_('select.option', 0, JText::_('RST_KB_NO_PARENT'));
		}
		
		$db 	= JFactory::getDbo();
		$query 	= $db->getQuery(true);
		
		// Load the list items.
		$query->select('*')
			  ->from($db->qn('#__rsticketspro_kb_categories'))
			  ->order($db->qn('ordering').' '.$db->escape('asc'));
		$items = $db->setQuery($query)->loadObjectList();
		$children = array();
		
		// first pass - collect children
		if ($items)
		{
			foreach ($items as $item)
			{
				$parent	= $item->parent_id;
				$item->parent = $parent;
				$item->title = '';
				$list = @$children[$parent] ? $children[$parent] : array();
				array_push($list, $item);
				$children[$parent] = $list;
			}
		}
		unset($items);
		
		// second pass - get an indent list of the items
		$list = JHtml::_('menu.treerecurse', 0, '', array(), $children, 9999, 0, 0);
		foreach ($list as $item)
		{
			// Add the option object to the result set.
			$options[] = JHtml::_('select.option', $item->id, $item->treename.$item->name);
		}
		unset($list);

		reset($options);
		
		return $options;
	}
}