<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('JPATH_PLATFORM') or die;

require_once JPATH_ADMINISTRATOR.'/components/com_rsticketspro/helpers/rsticketspro.php';

JFormHelper::loadFieldClass('list');

class JFormFieldDepartments extends JFormFieldList
{
	protected $type = 'Departments';
	
	protected function getOptions()
	{
		// Initialize variables.
		$options 			= parent::getOptions();
		$is_staff			= RSTicketsProHelper::isStaff();
		$force_departments	= RSTicketsProHelper::getConfig('staff_force_departments');
		$departments		= RSTicketsProHelper::getCurrentDepartments();
		$view				= JFactory::getApplication()->input->get('view');
		$db 				= JFactory::getDbo();
		$user_groups		= !$is_staff ? JAccess::getGroupsByUser(JFactory::getUser()->id, false) : array();

		if (isset($this->element['please']) && $this->element['please'] == 'true')
		{
			$options[] = JHtml::_('select.option', '', JText::_('RST_PLEASE_SELECT_DEPARTMENT'));
		}

		$query 	= $db->getQuery(true);
		$query->select($db->qn('id'))
			  ->select($db->qn('name'))
			  ->from('#__rsticketspro_departments');
		if (!$is_staff)
		{
			$query->select($db->qn('jgroups'));
		}
		
		if (isset($this->element['published']) && $this->element['published'] == 'true')
		{
			$query->where($db->qn('published').'='.$db->q(1));
		}
		
		$query->order($db->qn('ordering').' '.$db->escape('asc'));
		$db->setQuery($query);

		if ($results = $db->loadObjectList())
		{
			foreach ($results as $result)
			{
				if ($is_staff && $force_departments && !in_array($view, array('staff', 'customfield', 'customfields', 'item')) && !in_array($result->id, $departments))
				{
					continue;
				}

				// Search in the departments relations table to see if this department is excluded from the current user group (only for non staff)
				if (!$is_staff)
				{
					if (!empty($result->jgroups))
					{
						$json_groups = json_decode($result->jgroups, true);

						if (is_array($json_groups) && array_intersect($json_groups, $user_groups))
						{
							continue;
						}
					}
				}

				$tmp = JHtml::_('select.option', $result->id, JText::_($result->name));

				// Add the option object to the result set.
				$options[] = $tmp;
			}
		}

		reset($options);
		
		return $options;
	}
}