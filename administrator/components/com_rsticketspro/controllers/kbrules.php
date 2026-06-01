<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproControllerKbrules extends JControllerAdmin
{
	public function getModel($name = 'Kbrule', $prefix = 'RsticketsproModel', $config = array('ignore_request' => true))
	{
		return parent::getModel($name, $prefix, $config);
	}
	
	public function showDepartments()
	{
		header('Content-Type: text/javascript; charset=utf-8');
		
		$model = $this->getModel('kbrules');
		$departments = $model->getDepartments();
		
		echo json_encode($departments);
		die();
	}
	
	public function showPriorities()
	{
		header('Content-Type: text/javascript; charset=utf-8');
		
		$model = $this->getModel('kbrules');
		$priorities = $model->getPriorities();
		
		echo json_encode($priorities);
		die();
	}
	
	public function showStatuses()
	{
		header('Content-Type: text/javascript; charset=utf-8');
		
		$model = $this->getModel('kbrules');
		$statuses = $model->getStatuses();
		
		echo json_encode($statuses);
		die();
	}
	
	public function showCustomFields()
	{
		header('Content-Type: text/javascript; charset=utf-8');
		
		$model = $this->getModel('kbrules');
		$departments = $model->getDepartments();
		$custom_fields = $model->getCustomFields();

		echo json_encode(array('departments' => $departments, 'options' => $custom_fields));
		
		die();
	}
	
	public function showCustomFieldValues()
	{
		header('Content-Type: text/javascript; charset=utf-8');
		
		$model = $this->getModel('kbrules');
		
		$values = $model->getCustomFieldValues();
		echo json_encode($values);
		
		die();
	}
}