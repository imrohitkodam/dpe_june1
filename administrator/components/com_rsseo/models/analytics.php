<?php
/**
* @package RSSeo!
* @copyright (C) 2016 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;

class rsseoModelAnalytics extends ListModel
{
	protected $gapi;
	
	public function __construct() {
		require_once JPATH_ADMINISTRATOR.'/components/com_rsseo/helpers/gapi.php';
		
		try {
			$this->gapi = rsseoGoogleAPI::getInstance('ganalytics');
		} catch (Exception $e) {}
		
		parent::__construct();
	}
	
	public function getTabs() {
		$tabs =  new RSSeoAdapterTabs('com-rsseo-analytics');
		return $tabs;
	}
	
	public function getProfiles() {
		return ($this->gapi instanceof rsseoGoogleAPI) ? $this->gapi->getProfiles() : array(HTMLHelper::_('select.option', '', Text::_('COM_RSSEO_SELECT_GA_ACCOUNT')));
	}
	
	public function getSelected() {
		return isset($_COOKIE['rsseoAnalyticsID']) ? $_COOKIE['rsseoAnalyticsID'] : null;
	}
	
	public function getGAgeneral() {
		try {
			$data = array();
			if ($general = $this->gapi->getData('general')) {
				foreach ($general as $i => $value) {
					$object = new stdClass();
					$object->title = Text::_('COM_RSSEO_GA_GENERAL_'.$i);
					$object->value = $value == '' ? Text::_('COM_RSSEO_NOT_AVAILABLE') : $this->clean($i, $value);
					$object->descr = Text::_('COM_RSSEO_GA_GENERAL_'.$i.'_DESC');
					
					$data[] = $object;
				}
			}
		} catch (Exception $e) {
			$data = $e->getMessage();
		}
		
		return $data;
	}
	
	public function getGAnewreturning() {
		try {
			$data = array();
			
			if ($newvsreturning = $this->gapi->getData('newvsreturning')) {
				foreach ($newvsreturning as $array) {
					$object		= new stdClass;
					$key		= ($array[0] == 'Returning Visitor' || $array[0] == 'returning') ? Text::_('COM_RSSEO_RETURNINGVISITOR') : Text::_('COM_RSSEO_NEWVISITOR');
					$data[$key] = array();
					
					$object->sessions	= isset($array[1]) ? $this->clean('ga:sessions', $array[1]) : Text::_('COM_RSSEO_NOT_AVAILABLE');
					$object->pageviews	= isset($array[2]) ? $this->clean('ga:pageviewsPerSession', $array[2], 2) : Text::_('COM_RSSEO_NOT_AVAILABLE');
					$object->duration	= isset($array[3]) ? $this->clean('ga:avgSessionDuration', $array[3]) : Text::_('COM_RSSEO_NOT_AVAILABLE');
					$object->bouncerate	= isset($array[4]) ? $this->clean('ga:bounceRate', $array[4]) : Text::_('COM_RSSEO_NOT_AVAILABLE');
					
					$data[$key] = $object;
				}
			}
		} catch (Exception $e) {
			$data = $e->getMessage();
		}
		
		return $data;
	}
	
	public function getGAvisits() {
		try {
			$data = array();
			if ($sessions = $this->gapi->getData('sessions')) {
				$total	= isset($sessions['total']) ? $sessions['total'] : 1;
				$rows	= isset($sessions['rows']) ? $sessions['rows'] : array();
				
				foreach ($rows as $row) {
					$object = new stdClass();
					$object->date	 	= isset($row[0]) ? Factory::getDate(substr($row[0],0,4).'-'.substr($row[0],4,2).'-'.substr($row[0],6,2))->format('l, F d, Y') : '';
					$object->sessions	= isset($row[1]) ? $this->clean('ga:sessions', $row[1]) : Text::_('COM_RSSEO_NOT_AVAILABLE');
					$object->percent	= isset($row[1]) && $total ? number_format((($row[1] * 100) / $total), 2). ' %' : '-';
					
					$data[] = $object;
				}
			}
		} catch (Exception $e) {
			$data = $e->getMessage();
		}
		
		return $data;
	}
	
	public function getGAgeocountry() {
		try {
			$data = array();
			if ($rows = $this->gapi->getData('geocountry')) {
				
				foreach ($rows as $row) {
					$object = new stdClass();
					$object->country		= isset($row[0]) ? $row[0] : '';
					$object->visits			= isset($row[1]) ? $this->clean('ga:sessions', $row[1]) : Text::_('COM_RSSEO_NOT_AVAILABLE');
					$object->newvisits		= isset($row[2]) ? $this->clean('ga:newUsers', $row[2]) : Text::_('COM_RSSEO_NOT_AVAILABLE');
					$object->bouncerate		= isset($row[3]) ? $this->clean('ga:bounceRate', $row[3]) : Text::_('COM_RSSEO_NOT_AVAILABLE');
					$object->pagesvisits	= isset($row[4]) ? $this->clean('ga:pageviewsPerSession', $row[4]) : Text::_('COM_RSSEO_NOT_AVAILABLE');
					$object->avgtimesite	= isset($row[5]) ? $this->clean('ga:avgSessionDuration', $row[5]) : Text::_('COM_RSSEO_NOT_AVAILABLE');
					
					$data[] = $object;
				}
			}
		} catch (Exception $e) {
			$data = $e->getMessage();
		}
		
		return $data;
	}
	
	public function getGAbrowsers() {
		try {
			$data = array();
			if ($rows = $this->gapi->getData('browsers')) {
				
				foreach ($rows as $row) {
					$object = new stdClass();
					$object->browser		= isset($row[0]) ? $row[0] : '';
					$object->visits			= isset($row[1]) ? $this->clean('ga:sessions', $row[1]) : Text::_('COM_RSSEO_NOT_AVAILABLE');
					$object->pagesvisits	= isset($row[2]) ? $this->clean('ga:pageviewsPerSession', $row[2]) : Text::_('COM_RSSEO_NOT_AVAILABLE');
					$object->avgtimesite	= isset($row[3]) ? $this->clean('ga:avgSessionDuration', $row[3]) : Text::_('COM_RSSEO_NOT_AVAILABLE');
					$object->bouncerate		= isset($row[4]) ? $this->clean('ga:bounceRate', $row[4]) : Text::_('COM_RSSEO_NOT_AVAILABLE');
					
					$data[] = $object;
				}
			}
		} catch (Exception $e) {
			$data = $e->getMessage();
		}
		
		return $data;
	}
	
	public function getGAmobiles() {
		try {
			$data = array();
			if ($rows = $this->gapi->getData('mobiles')) {
				
				foreach ($rows as $row) {
					$object = new stdClass();
					$object->browser		= isset($row[0]) ? $row[0] : '';
					$object->visits			= isset($row[1]) ? $this->clean('ga:sessions', $row[1]) : Text::_('COM_RSSEO_NOT_AVAILABLE');
					$object->pagesvisits	= isset($row[2]) ? $this->clean('ga:pageviewsPerSession', $row[2]) : Text::_('COM_RSSEO_NOT_AVAILABLE');
					$object->avgtimesite	= isset($row[3]) ? $this->clean('ga:avgSessionDuration', $row[3]) : Text::_('COM_RSSEO_NOT_AVAILABLE');
					$object->bouncerate		= isset($row[4]) ? $this->clean('ga:bounceRate', $row[4]) : Text::_('COM_RSSEO_NOT_AVAILABLE');
					
					$data[] = $object;
				}
			}
		} catch (Exception $e) {
			$data = $e->getMessage();
		}
		
		return $data;
	}
	
	public function getGAsources() {
		try {
			$data = array();
			if ($rows = $this->gapi->getData('sources')) {
				
				foreach ($rows as $row) {
					$object = new stdClass();
					$object->source			= isset($row[0]) && isset($row[1]) ? $row[0].' / '.$row[1] : '';
					$object->visits			= isset($row[2]) ? $this->clean('ga:sessions', $row[2]) : Text::_('COM_RSSEO_NOT_AVAILABLE');
					$object->pagesvisits	= isset($row[3]) ? $this->clean('ga:pageviewsPerSession', $row[3]) : Text::_('COM_RSSEO_NOT_AVAILABLE');
					$object->avgtimesite	= isset($row[4]) ? $this->clean('ga:avgSessionDuration', $row[4]) : Text::_('COM_RSSEO_NOT_AVAILABLE');
					$object->bouncerate		= isset($row[5]) ? $this->clean('ga:bounceRate', $row[5]) : Text::_('COM_RSSEO_NOT_AVAILABLE');
					
					$data[] = $object;
				}
			}
		} catch (Exception $e) {
			$data = $e->getMessage();
		}
		
		return $data;
	}
	
	public function getGAcontent() {
		try {
			$data = array();
			if ($rows = $this->gapi->getData('content')) {

				foreach ($rows as $row) {
					$object = new stdClass();
					$object->page			= isset($row[0]) ? $row[0] : '';
					$object->visits			= isset($row[1]) ? $this->clean('ga:sessions', $row[1]) : Text::_('COM_RSSEO_NOT_AVAILABLE');
					$object->pageviews		= isset($row[2]) ? $this->clean('ga:pageviews', $row[2]) : Text::_('COM_RSSEO_NOT_AVAILABLE');
					$object->avgtimesite	= isset($row[3]) ? $this->clean('ga:avgTimeOnPage', $row[3]) : Text::_('COM_RSSEO_NOT_AVAILABLE');
					$object->bouncerate		= isset($row[4]) ? $this->clean('ga:bounceRate', $row[4]) : Text::_('COM_RSSEO_NOT_AVAILABLE');
					
					$data[] = $object;
				}
			}
		} catch (Exception $e) {
			$data = $e->getMessage();
		}
		
		return $data;
	}
	
	public function getGAsourceschart() {
		try {
			$data = $this->gapi->getData('sourcesChart');
		} catch (Exception $e) {
			$data = $e->getMessage();
		}
		
		return $data;
	}
	
	protected function clean($property, $value, $decimals = 0) {
		$percentage = array('ga:percentNewSessions','ga:bounceRate','ga:exitRate', 5);
		$time = array('ga:avgSessionDuration','ga:avgTimeOnPage', 4);
		
		if (in_array($property,$percentage)) {
			return number_format($value,2).' %';
		} else if (in_array($property, $time)) {
			return rsseoHelper::convertseconds(number_format($value,0));
		} else {
			return number_format($value,$decimals);
		}
	}
}