<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproViewNotes extends JViewLegacy
{
	protected $id;
	protected $items;
	protected $pagination;
	protected $state;
	protected $permissions;
	protected $dateFormat;
	protected $userField;
	
	public function display($tpl = null)
	{
		if (!$this->hasPermission())
		{
			throw new Exception(JText::_('RST_STAFF_CANNOT_VIEW_NOTES'), 403);
		}
		
		$this->id 			= $this->get('Id');
		$this->items 		= $this->get('Items');
		$this->pagination 	= $this->get('Pagination');
		$this->state 		= $this->get('State');
		$this->userId		= JFactory::getUser()->id;
		
		$this->dateFormat 	= RSTicketsProHelper::getConfig('date_format');
		$this->userField	= RSTicketsProHelper::getConfig('show_user_info');
		
		parent::display($tpl);
	}
	
	protected function showDate($date)
	{
		return JHtml::_('date', $date, $this->dateFormat);
	}
	
	protected function showUser($user_id) {
		if ($user_id) {
			return JFactory::getUser($user_id)->{$this->userField};
		} else {
			return '-';
		}
	}
	
	protected function hasPermission() {
		// get id
		$id = $this->get('Id');
		// get model
		$model = JModelLegacy::getInstance('Ticket', 'RsticketsproModel', array(
			'option' => 'com_rsticketspro',
			'table_path' => JPATH_ADMINISTRATOR.'/components/com_rsticketspro/tables'
		));
		// get permissions
		$this->permissions = $model->getStaffPermissions();
		return $model->isStaff() && $this->permissions->view_notes && $model->hasPermission($id);
	}
}