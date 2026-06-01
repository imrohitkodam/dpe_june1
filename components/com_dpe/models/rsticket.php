<?php
/**
 * @package     DPE
 * @subpackage  com_dpe
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;
use Joomla\CMS\Form\Form;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;

/**
 * Rsticket Model to perform dpe specific operations.
 *
 * @since  1.0.0
 */
class DpeModelRsticket extends ListModel
{
	/**
	 * Method to get the record form.
	 *
	 * @param   array   $actions   Subusers actions.
	 * @param   string  $client    subuser's action's client.
	 * @param   int     $clientId  multiagency id.
	 *
	 * @return  Form|boolean  A Form object on success, false on failure
	 *
	 * @since   1.0.0
	 */
	public function getUsersByActions($actions = array(), $client = null, $clientId = null)
	{
		// Get roles
		$subusersModelRole = RBACL::model('role');

		$roleIds = $subusersModelRole->getAuthorizeRoles($actions, $client);

		// Get users by roles
		$subusersModelUsers = RBACL::model('users', array('ignore_request' => true));

		if ($clientId)
		{
			$subusersModelUsers->setState('filter.client_id', $clientId);
		}

		$subusersModelUsers->setState('filter.role_id', $roleIds);
		$subusersModelUsers->setState('group_by', 'user_id');
		$subusersModelUsers->setState('filter.client', 'com_multiagency');
		$subusersModelUsers->setState('filter.state', 0);
		$subusersModelUsers->setState('list.ordering', 'name');
		$subusersModelUsers->setState('list.direction', 'asc');

		return $subusersModelUsers->getItems();
	}


	/**
	 * Method to save the log data in xref table
	 *
	 * @param $logId  integer Log Id
	 * 
	 * @param $ticketId  integer ticket Id
	 * 
	 * @since   __DEPLOY__VERSION__
	 */
	public function saveLogFromTicket($logId, $ticketId)
	{
		if (!$logId || !$ticketId)
		{
			return false;
		}
		
		Table::addIncludePath(JPATH_SITE . '/components/com_dpe/tables');
		$ticketLogExtendTable = Table::getInstance('LogticketExtend', 'DpeTable');
		$ticketLogSaveData    = array('ticketId' => $ticketId);
		$isPresentLogId   = $ticketLogExtendTable->load($ticketLogSaveData);

		if (!$isPresentLogId)
		{	
			// insert the Log and ticekt
			if($ticketLogExtendTable->save(array('ticketId' => $ticketId, 'logId' => $logId)))
			{
				return true;
			}
			else
			{
				return false;
			}
		}
		else
		{
			$ticketLogSaveData = array('id'=>$ticketLogExtendTable->id, 'ticketId' => $ticketId, 'logId' => $logId);

			if($ticketLogExtendTable->save($ticketLogSaveData))
			{
				return true;
			}
			else
			{
				return false;
			}
		}
	}
}
