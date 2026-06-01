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

class JFormFieldStatuses extends JFormFieldList
{
	protected $type = 'Statuses';
	
	protected function getOptions() {		
		// Initialize variables.
		$options = parent::getOptions();
		
		$db 	= JFactory::getDbo();
		$query 	= $db->getQuery(true);
		$query->select($db->qn('id'))
			  ->select($db->qn('name'))
			  ->from('#__rsticketspro_statuses');
			  
		if (isset($this->element['published']) && $this->element['published'] == 'true') {
			$query->where($db->qn('published').'='.$db->q(1));
		}
		
		$query->order($db->qn('ordering').' '.$db->escape('asc'));
		
		$db->setQuery($query);
		
		$statuses = $db->loadObjectList();
		foreach ($statuses as $status) {
			$tmp = JHtml::_('select.option', $status->id, JText::_($status->name));

			// Add the option object to the result set.
			$options[] = $tmp;
		}

		reset($options);
		
		return $options;
	}
}