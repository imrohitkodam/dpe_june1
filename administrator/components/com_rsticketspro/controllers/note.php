<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproControllerNote extends JControllerForm
{
	protected function getRedirectToListAppend()
	{
		$append  = parent::getRedirectToListAppend();
		$append	.= '&ticket_id=' . JFactory::getApplication()->input->getInt('ticket_id');

		return $append;
	}
	
	protected function getRedirectToItemAppend($recordId = null, $urlVar = 'id')
	{
		$append = parent::getRedirectToItemAppend($recordId, $urlVar);
		$append	.= '&ticket_id=' . JFactory::getApplication()->input->getInt('ticket_id');
		
		return $append;
	}

	protected function allowAdd($data = array())
	{
		$permissions = RSTicketsProHelper::getCurrentPermissions();

		return $permissions->add_note;
	}

	protected function allowEdit($data = array(), $key = 'id')
	{
		$permissions = RSTicketsProHelper::getCurrentPermissions();
		
		$model  = $this->getModel();
		$table  = $model->getTable();
		$userId = JFactory::getUser()->id;
		
		// load data
		$table->load($data[$key]);
		
		return ($permissions->update_note && $table->user_id == $userId) || ($permissions->update_note_staff && $table->user_id != $userId);
	}
}