<?php
/**
* @version 2.0.0
* @package RSTickets! Pro 2.0.0
* @copyright (C) 2010 www.rsjoomla.com
* @license GPL, http://www.gnu.org/licenses/gpl-2.0.html
*/

defined('_JEXEC') or die('Restricted access');

class RsticketsproModelCron extends JModelAdmin
{
	public function getTable($type = 'Crons', $prefix = 'RsticketsproTable', $config = array())
	{
		return parent::getTable($type, $prefix, $config);
	}
	
	public function getForm($data = array(), $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm('com_rsticketspro.cron', 'cron', array('control' => 'jform', 'load_data' => $loadData));
		if (empty($form))
		{
			return false;
		}

		return $form;
	}
	
	protected function loadFormData()
	{
		// Check the session for previously entered form data.
		$app  = JFactory::getApplication();
		$data = $app->getUserState('com_rsticketspro.edit.cron.data', array());

		if (empty($data))
		{
			$data = $this->getItem();
		}

		return $data;
	}
	
	public function getRSTabs()
	{
		return new RsticketsproAdapterTabs('com-rsticketspro-cron');
	}
}