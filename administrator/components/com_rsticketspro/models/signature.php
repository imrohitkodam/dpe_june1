<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproModelSignature extends JModelAdmin
{
	public function getForm($data = array(), $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm('com_rsticketspro.signature', 'signature', array('control' => 'jform', 'load_data' => $loadData));
		if (empty($form))
		{
			return false;
		}

		return $form;
	}
	
	protected function loadFormData()
	{
		$data = array(
			'signature' => RSTicketsProHelper::getSignature(null, true)
		);
		
		return $data;
	}
	
	public function save($data)
	{
		$db 	= JFactory::getDbo();
		$query 	= $db->getQuery(true);
		$userId	= JFactory::getUser()->id;
		
		$query->update($db->qn('#__rsticketspro_staff'))
			  ->set($db->qn('signature') . '=' . $db->q($data['signature']))
			  ->where($db->qn('user_id') . '=' . $db->q($userId));
		return $db->setQuery($query)->execute();
	}
	
	public function getIsAssigned()
	{
		$db 	= JFactory::getDbo();
		$query 	= $db->getQuery(true);
		$userId	= JFactory::getUser()->id;
		
		$query->select($db->qn('id'))
			  ->from($db->qn('#__rsticketspro_staff'))
			  ->where($db->qn('user_id') . '=' . $db->q($userId));
		$db->setQuery($query);
		return $db->loadResult();
	}
}