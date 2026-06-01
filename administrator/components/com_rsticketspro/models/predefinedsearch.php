<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproModelPredefinedsearch extends JModelAdmin
{
	public function getTable($type = 'Searches', $prefix = 'RsticketsproTable', $config = array())
	{
		return parent::getTable($type, $prefix, $config);
	}
	
	public function getForm($data = array(), $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm('com_rsticketspro.predefinedsearches', 'predefinedsearches', array('control' => 'jform', 'load_data' => $loadData));
		if (empty($form))
		{
			return false;
		}

		if (!$form->getValue('id'))
		{
			$form->setFieldAttribute('update', 'disabled', 'true');
			$form->setFieldAttribute('update', 'filter', 'unset');
		}

		if (JFactory::getApplication()->isClient('site') && !RSTicketsProHelper::getConfig('use_btn_group_radio'))
		{
			$form->setFieldAttribute('default', 'class', '');
			$form->setFieldAttribute('published', 'class', '');
		}

		return $form;
	}
	
	public function save($data)
	{
		$model = $this->getInstance('Tickets', 'RsticketsproModel');

		if (empty($data['id']) || !empty($data['id']) && !empty($data['update']))
		{
			$data['params'] = array(
				'search' => $model->getState('filter.search', ''),
				'flagged' => $model->getState('filter.flagged', 0),
				'priority_id' => $model->getState('filter.priority_id', array()),
				'status_id' => $model->getState('filter.status_id', array()),
				'department_id' => $model->getState('filter.department_id', array()),
				'customer' => $model->getState('filter.customer', ''),
				'staff' => $model->getState('filter.staff', ''),
				'ordering' => $model->getState('list.ordering'),
				'direction' => $model->getState('list.direction'),
				'from' => $model->getState('filter.from', ''),
				'to' => $model->getState('filter.to', '')
			);
		}
		else
		{
			$data['params'] = null;
		}

		$data['user_id'] = JFactory::getUser()->id;
		
		return parent::save($data);
	}
	
	protected function loadFormData()
	{
		// Check the session for previously entered form data.
		$data = JFactory::getApplication()->getUserState('com_rsticketspro.edit.predefinedsearches.data', array());

		if (empty($data))
		{
			$data = $this->getItem();
		}

		return $data;
	}
	
	protected function getReorderConditions($table)
	{
		return array(
			'user_id = '.(int) $table->user_id
		);
	}
	
	protected function canEditState($record)
	{
		return $record->user_id == JFactory::getUser()->id;
	}
	
	protected function canDelete($record)
	{
		return $record->user_id == JFactory::getUser()->id;
	}
}