<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproModelDepartment extends JModelAdmin
{
	public function getTable($type = 'Departments', $prefix = 'RsticketsproTable', $config = array())
	{
		return parent::getTable($type, $prefix, $config);
	}

	public function getForm($data = array(), $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm('com_rsticketspro.department', 'department', array('control' => 'jform', 'load_data' => $loadData));

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
		$data = JFactory::getApplication()->getUserState('com_rsticketspro.edit.department.data', array());

		if (empty($data))
		{
			$data = $this->getItem();
		}

		if (!empty($data->jgroups))
		{
			$data->jgroups = json_decode($data->jgroups, true);
		}

		return $data;
	}

	public function getPHPValues()
	{
		return array(
			'upload_max_filesize' => ini_get('upload_max_filesize'),
			'max_file_uploads' => ini_get('max_file_uploads'),
			'post_max_size' => ini_get('post_max_size')
		);
	}

	public function getRSTabs()
	{
		return new RsticketsproAdapterTabs('com-rsticketspro-department');
	}

	protected function canDelete($record)
	{
		return JFactory::getUser()->authorise('department.delete', 'com_rsticketspro');
	}

	protected function canEditState($record)
	{
		return JFactory::getUser()->authorise('department.edit.state', 'com_rsticketspro');
	}
}