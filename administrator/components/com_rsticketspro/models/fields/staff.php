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

class JFormFieldStaff extends JFormFieldList
{
	protected $type = 'Staff';

	protected $userField;
	
	protected function getDepartments() {
		$db		= JFactory::getDbo();
		$query 	= $db->getQuery(true);
		
		$query->select($db->qn('id'))
			  ->select($db->qn('name'))
			  ->from('#__rsticketspro_departments')
			  ->where($db->qn('published').'='.$db->q(1))
			  ->order($db->qn('ordering').' '.$db->escape('asc'));
		$db->setQuery($query);
		return $db->loadObjectList();
	}
	
	protected function getUsers() {
		$db		= JFactory::getDbo();
		$query 	= $db->getQuery(true);
		$users	= array();
		
		$query->select($db->qn('user_id'))
			  ->select($db->qn('department_id'))
			  ->from('#__rsticketspro_staff_to_department');
		$db->setQuery($query);
		if ($results = $db->loadObjectList()) {
			foreach ($results as $result) {
				if (!isset($users[$result->department_id])) {
					$users[$result->department_id] = array();
				}
				
				$users[$result->department_id][] = JFactory::getUser($result->user_id);
			}
		}
		
		return $users;
	}
	
	protected function getOptions() {
		// Initialize variables.
		$options 	 = parent::getOptions();
		$departments = $this->getDepartments();
		$users		 = $this->getUsers();

        $this->userField = RSTicketsProHelper::getConfig('show_user_info');
		
		if (isset($this->element['unassigned']) && $this->element['unassigned'] == 'true') {
			$options[] = JHtml::_('select.option', 0, JText::_('RST_UNASSIGNED'));
		}
		
		foreach ($departments as $department) {
			// opening <OPTGROUP> tag
			$options[] = (object) array(
				'value' => '<OPTGROUP>',
				'text'  => JText::_($department->name)
			);
			
			if (isset($users[$department->id])) {
			    usort($users[$department->id], array($this, 'sort'));
				foreach ($users[$department->id] as $user) {
					$options[] = JHtml::_('select.option', $user->id, $user->get($this->userField));
				}
			}
			
			// closing </OPTGROUP> tag
			$options[] = (object) array(
				'value' => '</OPTGROUP>',
				'text'  => ''
			);
		}

		reset($options);
		
		return $options;
	}

	protected function sort($a, $b)
    {
        return strcasecmp($a->get($this->userField), $b->get($this->userField));
    }
}