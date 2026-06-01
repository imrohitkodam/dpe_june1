<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproModelStatus extends JModelAdmin
{
	public function getTable($type = 'Statuses', $prefix = 'RsticketsproTable', $config = array())
	{
		return parent::getTable($type, $prefix, $config);
	}
	
	public function getForm($data = array(), $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm('com_rsticketspro.status', 'status', array('control' => 'jform', 'load_data' => $loadData));
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
		$data = JFactory::getApplication()->getUserState('com_rsticketspro.edit.status.data', array());

		if (empty($data))
		{
			$data = $this->getItem();
		}

		return $data;
	}
	
	protected function canDelete($record)
	{
		if ($this->isCore($record->id))
		{
			return false;
		}

		return JFactory::getUser()->authorise('status.delete', 'com_rsticketspro');
	}
	
	protected function canEditState($record)
	{
		$task = JFactory::getApplication()->input->get('task');
		if ($task !== 'saveOrderAjax')
		{
			$id = !empty($record->id) ? $record->id : JFactory::getApplication()->input->getInt('id');

			if ($this->isCore($id))
			{
				return false;
			}
		}

		return JFactory::getUser()->authorise('status.edit.state', 'com_rsticketspro');
	}
	
	protected function isCore($id)
	{
		return $id > 0 && $id <= 3;
	}
}