<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproModelSearch extends JModelAdmin
{
	public function getForm($data = array(), $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm('com_rsticketspro.search', 'search', array('control' => false, 'load_data' => $loadData));
		if (empty($form))
		{
			return false;
		}

		$permissions = RSTicketsProHelper::getCurrentPermissions();
		$isStaff = RSTicketsProHelper::isStaff();

		if (!$isStaff || !$permissions || !$permissions->see_other_tickets)
		{
			$form->removeField('staff');
		}

		if (!$isStaff)
		{
			$form->removeField('customer');
		}

		if (JFactory::getApplication()->isClient('site') && !$this->getAdvanced())
		{
			$form->removeField('staff');
			$form->removeField('customer');
			$form->removeField('department_id');
			$form->removeField('priority_id');
			$form->removeField('status_id');
			$form->removeField('filter_order');
			$form->removeField('filter_order_Dir');
		}

		if (JFactory::getApplication()->isClient('site') && !RSTicketsProHelper::getConfig('use_btn_group_radio'))
		{
			$form->setFieldAttribute('flagged', 'class', '');
		}

		return $form;
	}

	protected function loadFormData()
	{
		$model = $this->getInstance('Tickets', 'RsticketsproModel');

		return array(
			'filter_search' => $model->getState('filter.search', ''),
			'flagged' => $model->getState('filter.flagged', 0),
			'priority_id' => $model->getState('filter.priority_id', array()),
			'status_id' => $model->getState('filter.status_id', array()),
			'department_id' => $model->getState('filter.department_id', array()),
			'customer' => $model->getState('filter.customer', ''),
			'staff' => $model->getState('filter.staff', ''),
			'filter_order' => $model->getState('list.ordering'),
			'filter_order_Dir' => $model->getState('list.direction'),
			'from' => $model->getState('filter.from', ''),
			'to' => $model->getState('filter.to', ''),
		);
	}

	public function getAdvanced()
	{
		return JFactory::getApplication()->input->get('advanced', false, 'bool');
	}

	public function getItemId()
	{
		$params = JFactory::getApplication()->getParams('com_rsticketspro');

		if (RSTicketsProHelper::isStaff() && $params->get('staff_itemid'))
		{
			return '&Itemid='.(int) $params->get('staff_itemid');
		}

		if (!RSTicketsProHelper::isStaff() && $params->get('customer_itemid'))
		{
			return '&Itemid='.(int) $params->get('customer_itemid');
		}

		return '';
	}
}