<?php
/**
* @package RSSeo!
* @copyright (C) 2016 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;

class rsseoModelGkeyword extends AdminModel
{
	/**
	 * @var		string	The prefix to use with controller messages.
	 * @since	1.6
	 */
	protected $text_prefix = 'COM_RSSEO';
	
	/**
	 * Returns a Table object, always creating it.
	 *
	 * @param	type	The table type to instantiate
	 * @param	string	A prefix for the table class name. Optional.
	 * @param	array	Configuration array for model. Optional.
	 *
	 * @return	JTable	A database object
	*/
	public function getTable($type = 'Gkeyword', $prefix = 'rsseoTable', $config = array()) {
		return Table::getInstance($type, $prefix, $config);
	}
	
	/**
	 * Method to get a single record.
	 *
	 * @param	integer	The id of the primary key.
	 *
	 * @return	mixed	Object on success, false on failure.
	 */
	public function getItem($pk = null) {
		$item = parent::getItem($pk);
		
		return $item;
	}
	
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
		$form = $this->loadForm('com_rsseo.gkeyword', 'gkeyword', array('control' => 'jform', 'load_data' => $loadData));
		if (empty($form))
			return false;
		
		if (Factory::getApplication()->input->getInt('id', 0)) {
			$form->setFieldAttribute('name', 'readonly', true);
			$form->setFieldAttribute('site', 'required', false);
			$form->setFieldAttribute('site', 'disabled', true);
		}
		
		return $form;
	}
	
	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return	mixed	The data for the form.
	 * @since	1.6
	 */
	protected function loadFormData() {
		// Check the session for previously entered form data.
		$data = Factory::getApplication()->getUserState('com_rsseo.edit.gkeyword.data', array());

		if (empty($data))
			$data = $this->getItem();
			
		return $data;
	}
	
	public function getData($order = 'DESC') {
		$db		= Factory::getDbo();
		$id		= Factory::getApplication()->input->getInt('id',0);
		$query	= $this->getDataQuery($id, $order);
		
		$this->setFilters($query);
		
		$db->setQuery($query);
		return $db->loadObjectList();
	}
	
	public function getTotal() {
		$db		= Factory::getDbo();
		$id		= Factory::getApplication()->input->getInt('id',0);
		$query	= $this->getDataQuery($id);
		
		$db->setQuery($query);
		$db->execute();
		
		return $db->getNumRows();
	}
	
	public function getDates() {
		$db		= Factory::getDbo();
		$query	= $db->getQuery(true);
		$id		= Factory::getApplication()->input->getInt('id',0);
		$start	= Factory::getDate()->modify('-90 days');
		$end	= Factory::getDate()->modify('-4 days');
		$dates	= array();
		
		// Get dates that have data
		$query->clear()
			->select('DISTINCT '.$db->qn('date'))
			->from($db->qn('#__rsseo_gkeywords_data'))
			->where($db->qn('idk').' = '.$db->q($id));
		$db->setQuery($query);
		$datesWithData = $db->loadColumn();
		
		while ($end >= $start) {
			$date	= $start->format('Y-m-d');
			$month	= $start->format('n');
			if (!in_array($date, $datesWithData)) {
				$dates[$month][] = $date;
			}
			$start->modify('+1 days');
		}
		
		return $dates;
	}
	
	public function getPages() {
		$db		= Factory::getDbo();
		$query	= $db->getQuery(true);
		$input	= Factory::getApplication()->input;
		$id		= $input->getInt('id',0);
		$date	= $input->getString('date');
		
		$query->clear()
			->select($db->qn('page'))
			->select('COUNT(DISTINCT '.$db->qn('page').') AS pages')
			->select('SUM('.$db->qn('impressions').') AS impressions')
			->select('SUM('.$db->qn('clicks').') AS clicks')
			->select('SUM('.$db->qn('position').' * '.$db->qn('impressions').') / SUM('.$db->qn('impressions').') AS avgposition')
			->select('AVG('.$db->qn('ctr').') AS ctr')
			->from($db->qn('#__rsseo_gkeywords_data'))
			->where($db->qn('idk').' = '.$db->q($id))
			->where($db->qn('date').' = '.$db->q($date))
			->group($db->qn('page'))
			->order($db->qn('page').' ASC');
		
		$this->setFilters($query);
		
		$db->setQuery($query);
		return $db->loadObjectList();
	}
	
	public function getJson() {
		$array = array();
		
		if ($data = $this->getData('ASC')) {
			foreach ($data as $object) {
				$array[] = array(Factory::getDate($object->date)->format('d M Y'), (float) number_format($object->avgposition, 2));
			}
			
		}
		
		return json_encode($array);
	}
	
	public function getStatistics() {
		$db		= Factory::getDbo();
		$input	= Factory::getApplication()->input;
		$id		= $input->getInt('id');
		$from	= $input->getString('from', null);
		$to		= $input->getString('to', null);
		$array	= array();
		$query	= $this->getDataQuery($id, 'ASC');
		
		if ($from && !$to) {
			$query->where($db->qn('date').' >= '.$db->q($from));
		} elseif ($to && !$from) {
			$query->where($db->qn('date').' <= '.$db->q($to));
		} elseif ($from && $to) {
			$query->where($db->qn('date').' >= '.$db->q($from));
			$query->where($db->qn('date').' <= '.$db->q($to));
		}
		
		$db->setQuery($query);
		if ($data = $db->loadObjectList()) {
			foreach ($data as $object) {
				$array[] = array(Factory::getDate($object->date)->format('d M Y'), (float) number_format($object->avgposition, 2));
			}
		}
		
		return json_encode($array);
	}
	
	public function getDevices() {
		return array(
			HTMLHelper::_('select.option', 'all', Text::_('COM_RSSEO_GKEYWORD_ALL')),
			HTMLHelper::_('select.option', 'desktop', Text::_('COM_RSSEO_GKEYWORD_DESKTOP')),
			HTMLHelper::_('select.option', 'mobile', Text::_('COM_RSSEO_GKEYWORD_MOBILE'))
		);
	}
	
	public function getDevice() {
		$id	= Factory::getApplication()->input->getInt('id',0);
		return Factory::getApplication()->getUserStateFromRequest('com_rsseo.gkeyword.filter_device'.$id, 'filter_device', 'all');
	}
	
	public function getCountries() {
		$db		= Factory::getDbo();
		$query	= $db->getQuery(true);
		$input	= Factory::getApplication()->input;
		$id		= $input->getInt('id',0);
		$array	= array(HTMLHelper::_('select.option', 'all', Text::_('COM_RSSEO_GKEYWORD_ALL')));
		
		$query->select('DISTINCT '.$db->qn('country'))
			->from($db->qn('#__rsseo_gkeywords_data'))
			->where($db->qn('idk').' = '.$db->q($id));
		$db->setQuery($query);
		if ($countries = $db->loadColumn()) {
			foreach ($countries as $country) {
				$array[] = HTMLHelper::_('select.option', $country, strtoupper($country));
			}
		}
		
		return $array;
	}
	
	public function getCountry() {
		$id	= Factory::getApplication()->input->getInt('id',0);
		return Factory::getApplication()->getUserStateFromRequest('com_rsseo.gkeyword.filter_country'.$id, 'filter_country', 'all');
	}
	
	public function getFrom() {
		$id	= Factory::getApplication()->input->getInt('id',0);
		return Factory::getApplication()->getUserStateFromRequest('com_rsseo.gkeyword.filter_from'.$id, 'filter_from', '');
	}
	
	public function getTo() {
		$id	= Factory::getApplication()->input->getInt('id',0);
		return Factory::getApplication()->getUserStateFromRequest('com_rsseo.gkeyword.filter_to'.$id, 'filter_to', '');
	}
	
	public function deletelog() {
		$db		= Factory::getDbo();
		$query	= $db->getQuery(true)->delete($db->qn('#__rsseo_logs'))->where($db->qn('type').' = '.$db->q('gkeywords'));
		
		$db->setQuery($query);
		$db->execute();
	}
	
	protected function getDataQuery($id, $order = 'DESC') {
		$db		= Factory::getDbo();
		$query	= $db->getQuery(true);
		
		$query->clear()
			->select($db->qn('date'))
			->select('COUNT(DISTINCT '.$db->qn('page').') AS pages')
			->select('SUM('.$db->qn('impressions').') AS impressions')
			->select('SUM('.$db->qn('clicks').') AS clicks')
			->select('SUM('.$db->qn('position').' * '.$db->qn('impressions').') / SUM('.$db->qn('impressions').') AS avgposition')
			->select('AVG('.$db->qn('ctr').') AS ctr')
			->from($db->qn('#__rsseo_gkeywords_data'))
			->where($db->qn('idk').' = '.$db->q($id))
			->group($db->qn('date'))
			->order($db->qn('date').' '.$db->escape($order));
			
		return $query;
	}
	
	protected function setFilters(& $query) {
		if ($query instanceof JDatabaseQuery) {
			$db		= Factory::getDbo();
			$device = $this->getDevice();
			$country= $this->getCountry();
			$from	= $this->getFrom();
			$to		= $this->getTo();
			
			if ($device != 'all') {
				if ($device == 'mobile') {
					$query->where($db->qn('device').' IN ('.$db->q('MOBILE').','.$db->q('TABLET').')');
				} elseif ($device == 'desktop') {
					$query->where($db->qn('device').' = '.$db->q('DESKTOP'));
				}
			}
			
			if ($country != 'all') {
				$query->where($db->qn('country').' = '.$db->q($country));
			}
			
			if ($from && !$to) {
				$query->where($db->qn('date').' >= '.$db->q($from));
			} elseif ($to && !$from) {
				$query->where($db->qn('date').' <= '.$db->q($to));
			} elseif ($from && $to) {
				$query->where($db->qn('date').' >= '.$db->q($from));
				$query->where($db->qn('date').' <= '.$db->q($to));
			}
		}
	}
}