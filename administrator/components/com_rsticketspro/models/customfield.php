<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproModelCustomfield extends JModelAdmin
{
	public function getTable($type = 'Customfields', $prefix = 'RsticketsproTable', $config = array())
	{
		return parent::getTable($type, $prefix, $config);
	}
	
	public function getForm($data = array(), $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm('com_rsticketspro.customfield', 'customfield', array('control' => 'jform', 'load_data' => $loadData));
		if (empty($form))
		{
			return false;
		}

		// Modify the form based on access controls.
		if (!$this->canEditState((object) $data))
		{
			// Disable fields for display.
			$form->setFieldAttribute('ordering', 'disabled', 'true');
			$form->setFieldAttribute('published', 'disabled', 'true');

			// Disable fields while saving.
			// The controller has already verified this is a record you can edit.
			$form->setFieldAttribute('ordering', 'filter', 'unset');
			$form->setFieldAttribute('published', 'filter', 'unset');
		}

		return $form;
	}
	
	protected function loadFormData()
	{
		// Check the session for previously entered form data.
		$app  = JFactory::getApplication();
		$data = $app->getUserState('com_rsticketspro.edit.customfield.data', array());
		
		if (empty($data))
		{
			$data = $this->getItem();
		}
		
		if (!empty($data) && is_object($data) && !$data->id && !$data->department_id)
		{
			$model = $this->getInstance('Customfields', 'RsticketsproModel');
			$data->department_id = $model->getState('filter.department_id');
		}

		return $data;
	}
	
	protected function getReorderConditions($table)
	{
		return array(
			'department_id = '.(int) $table->department_id
		);
	}

	protected function canDelete($record)
	{
		return JFactory::getUser()->authorise('customfield.delete', 'com_rsticketspro');
	}

	protected function canEditState($record)
	{
		return JFactory::getUser()->authorise('customfield.edit.state', 'com_rsticketspro');
	}
}