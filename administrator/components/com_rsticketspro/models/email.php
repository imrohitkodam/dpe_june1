<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproModelEmail extends JModelAdmin
{
	public function getTable($type = 'Emails', $prefix = 'RsticketsproTable', $config = array())
	{
		return parent::getTable($type, $prefix, $config);
	}
	
	public function getForm($data = array(), $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm('com_rsticketspro.email', 'email', array('control' => 'jform', 'load_data' => $loadData));
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

		if (in_array($form->getValue('type'), array('add_ticket_reply_customer', 'add_ticket_reply_staff', 'add_ticket_customer', 'add_ticket_staff', 'add_ticket_notify')))
		{
			$form->setFieldAttribute('subject', 'disabled', 'true');
			$form->setFieldAttribute('subject', 'filter', 'unset');
		}

		return $form;
	}
	
	protected function loadFormData()
	{
		// Check the session for previously entered form data.
		$data = JFactory::getApplication()->getUserState('com_rsticketspro.edit.email.data', array());

		if (empty($data))
		{
			$data = $this->getItem();
		}

		return $data;
	}

	protected function canEditState($record)
	{
		return JFactory::getUser()->authorise('email.edit.state', 'com_rsticketspro');
	}
}