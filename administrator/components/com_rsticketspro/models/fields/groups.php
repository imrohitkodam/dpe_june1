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

class JFormFieldGroups extends JFormFieldList
{
	protected $type = 'Groups';
	
	protected function getOptions()
	{
		// Initialize variables.
		$options = array();
		
		if (isset($this->element['all']) && $this->element['all'] == 'true')
		{
			$options[] = JHtml::_('select.option', 0, JText::_('RST_ALL_PRIORITIES'));
		}
		
		$db 	= JFactory::getDbo();
		$query 	= $db->getQuery(true);
		$query->select($db->qn('id'))
			  ->select($db->qn('name'))
			  ->from('#__rsticketspro_groups');
		$db->setQuery($query);
		
		$groups = $db->loadObjectList();
		foreach ($groups as $group)
		{
			$options[] = JHtml::_('select.option', $group->id, JText::_($group->name));
		}

		reset($options);
		
		return $options;
	}
}