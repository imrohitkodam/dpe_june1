<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproModelKbrule extends JModelAdmin
{
	public function getTable($type = 'Kbrules', $prefix = 'RsticketsproTable', $config = array())
	{
		return parent::getTable($type, $prefix, $config);
	}
	
	public function getForm($data = array(), $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm('com_rsticketspro.kbrule', 'kbrule', array('control' => 'jform', 'load_data' => $loadData));
		if (empty($form))
		{
			return false;
		}

		// Modify the form based on access controls.
		if (!$this->canEditState((object) $data))
		{
			// Disable fields for display.
			$form->setFieldAttribute('published', 'disabled', 'true');

			// Disable fields while saving.
			// The controller has already verified this is a record you can edit.
			$form->setFieldAttribute('published', 'filter', 'unset');
		}

		return $form;
	}
	
	protected function loadFormData()
	{
		// Check the session for previously entered form data.
		$data = JFactory::getApplication()->getUserState('com_rsticketspro.edit.kbrule.data', array());

		if (empty($data))
		{
			$data = $this->getItem();
		}
		else
		{
			// Emulate conditions so we can keep editing if we run into an error
			$table = $this->getTable();
			if ($table->bind($data))
			{
				$data['conditions'] = $table->conditions;
			}
		}

		return $data;
	}

	protected function canDelete($record)
	{
		return JFactory::getUser()->authorise('kbrule.delete', 'com_rsticketspro');
	}

	protected function canEditState($record)
	{
		return JFactory::getUser()->authorise('kbrule.edit.state', 'com_rsticketspro');
	}
}