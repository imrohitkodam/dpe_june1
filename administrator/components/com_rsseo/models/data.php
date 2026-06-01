<?php
/**
* @package RSSeo!
* @copyright (C) 2016 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\Registry\Registry;
use Joomla\CMS\Factory;

class rsseoModelData extends AdminModel
{	
	/**
	 * @var		string	The prefix to use with controller messages.
	 * @since	1.6
	 */
	protected $text_prefix = 'COM_RSSEO';
	
	/**
	 * Method to get the record form.
	 *
	 * @param	array	$data		Data for the form.
	 * @param	boolean	$loadData	True if the form is to load its own data (default case), false if not.
	 *
	 * @return	mixed	A JForm object on success, false on failure
	 * @since	1.6
	 */
	public function getForm($data = array(), $loadData = true) {
		// Get the form.
		$form = $this->loadForm('com_rsseo.data', 'data', array('control' => 'jform', 'load_data' => $loadData));
		if (empty($form))
			return false;
		
		return $form;
	}
	
	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return	mixed	The data for the form.
	 * @since	1.6
	 */
	protected function loadFormData() {
		return $this->getData();
	}
	
	public function getData() {
		$db		= Factory::getDbo();
		$query	= $db->getQuery(true);
		$data	= array();
		
		$query->select($db->qn('type'))
			->select($db->qn('data'))
			->from($db->qn('#__rsseo_data'));
		$db->setQuery($query);
		if ($objects = $db->loadObjectList()) {
			foreach ($objects as $object) {
				try {
					$registry = new Registry;
					$registry->loadString($object->data);
					$data[$object->type] = $registry->toArray();
				} catch (Exception $e) {
					$data[$object->type] = '';
				}
			}
		}
		
		return $data;
	}
	
	public function save($data) {
		$db		= Factory::getDbo();
		$query	= $db->getQuery(true);
		
		foreach ($data as $type => $properties) {
			$registry = new Registry;
			$registry->loadArray($properties);
			
			$query->clear()
				->select('*')
				->from($db->qn('#__rsseo_data'))
				->where($db->qn('type').' = '.$db->q($type));
			$db->setQuery($query);
			$db->execute();
			if ($db->getNumRows()) {
				$query->clear()
					->update($db->qn('#__rsseo_data'))
					->set($db->qn('data').' = '.$db->q($registry->toString()))
					->where($db->qn('type').' = '.$db->q($type));
				$db->setQuery($query);
				$db->execute();
			} else {
				try {
					$query->clear()
						->insert($db->qn('#__rsseo_data'))
						->set($db->qn('data').' = '.$db->q($registry->toString()))
						->set($db->qn('type').' = '.$db->q($type));
					$db->setQuery($query);
					$db->execute();
				} catch (Exception $e) {
					Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
				}
			}
		}
		
		return true;
	}
	
	public function getTabs() {
		$tabs =  new RSSeoAdapterTabs('structuredData');
		return $tabs;
	}
}