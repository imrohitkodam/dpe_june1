<?php
/**
* @version 2.0.0
* @package RSTickets! Pro 2.0.0
* @copyright (C) 2010 www.rsjoomla.com
* @license GPL, http://www.gnu.org/licenses/gpl-2.0.html
*/

defined('_JEXEC') or die('Restricted access');

class RsticketsproControllerCronlog extends JControllerAdmin
{
	public function getModel($name = 'Cronlog', $prefix = 'RsticketsproModel', $config = array('ignore_request' => true))
	{
		return parent::getModel($name, $prefix, $config);
	}

	/**
	 * Delete all Cron Logs
	 */
	public function deleteAll()
	{
		$this->checkToken();

		$db		= JFactory::getDbo();
		$query 	= $db->getQuery(true);

		$query->delete()
			->from($db->qn('#__rsticketspro_accounts_log'));

		$db->setQuery($query);
		$db->execute();

		$this->setRedirect('index.php?option=com_rsticketspro&view=cronlog', JText::_('RST_CRON_LOG_DELETED_ALL'));
	}
}