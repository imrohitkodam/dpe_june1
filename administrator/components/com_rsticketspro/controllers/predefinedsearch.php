<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproControllerPredefinedsearch extends JControllerForm
{
	protected function allowAdd($data = array())
	{
		// only staff members can add predefined searches
		return RSTicketsProHelper::isStaff();
	}
	
	protected function allowEdit($data = array(), $key = 'id')
	{
		// only staff members can edit predefined searches
		if (!RSTicketsProHelper::isStaff())
		{
			return false;
		}
		
		// check if we're editing our own search
		if (!empty($data[$key]))
		{
			return $this->isSameUser($data[$key]);
		}
		
		return true;
	}
	
	protected function allowSave($data, $key = 'id')
	{
		// only staff members can save
		if (!RSTicketsProHelper::isStaff())
		{
			return false;
		}
		
		// check if we're saving our own search
		if (!empty($data[$key]))
		{
			return $this->isSameUser($data[$key]);
		}
		
		return true;
	}
	
	protected function isSameUser($id)
	{
		$model = $this->getModel();
		$table = $model->getTable();
		
		// not found
		if (!$table->load($id))
		{
			return false;
		}
		
		return $table->user_id == JFactory::getUser()->id;
	}
	
	public function perform()
	{
		$app 	= JFactory::getApplication();
		$id	 	= $app->input->getInt('id');
		
		if (!RSTicketsProHelper::isStaff() || !$this->isSameUser($id))
		{
			return $this->setRedirect(JRoute::_('index.php?option=com_rsticketspro&view=tickets', false));
		}
		
		// load the predefined search
		$model = $this->getModel();
		$table = $model->getTable();
		$table->load($id);
		
		// perform it
		$tickets = $this->getModel('Tickets');
		$tickets->performSearch($table);
		
		return $this->setRedirect(JRoute::_('index.php?option=com_rsticketspro&view=tickets', false));
	}
}