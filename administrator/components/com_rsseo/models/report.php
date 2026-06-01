<?php
/**
* @package RSSeo!
* @copyright (C) 2016 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\Registry\Registry;
use Joomla\CMS\Factory;

class rsseoModelReport extends BaseDatabaseModel
{
	public function getForm() {
		$form = Form::getInstance('report', JPATH_ADMINISTRATOR.'/components/com_rsseo/models/forms/report.xml', array('control' => 'jform'));
		$data = $this->getData();
		
		$form->bind($data);
		
		return $form;
	}
	
	public function getData($prop = null) {
		try {
			$data	= rsseoHelper::getConfig('report');
			$data	= json_decode($data);
		} catch(Exception $e) {
			$data = $this->defaults();
		}
		
		return !is_null($prop) ? $data->$prop : $data;
	}
	
	public function save($data) {
		$db			= Factory::getDbo();
		$query		= $db->getQuery(true);
		$component	= ComponentHelper::getComponent('com_rsseo');
		$cparams	= $component->params;
		$data		= json_encode($data);
		
		if ($cparams instanceof Registry) {
			$cparams->set('report', $data);
			
			$query->update($db->qn('#__extensions'));
			$query->set($db->qn('params'). ' = '.$db->q((string) $cparams));
			$query->where($db->qn('extension_id'). ' = '. $db->q($component->id));
			
			$db->setQuery($query);
			$db->execute();
		}
		
		return true;
	}
	
	public function getLastCrawled() {
		$db			= Factory::getDbo();
		$query		= $db->getQuery(true);
		$limit		= (int) $this->getData('limit');
		
		$query->select($db->qn('id'))->select($db->qn('url'))
			->select($db->qn('sef'))->select($db->qn('date'))
			->from($db->qn('#__rsseo_pages'))
			->where($db->qn('crawled').' = '.$db->q(1))
			->order($db->qn('date').' DESC');
		
		$db->setQuery($query, 0, $limit);
		return $db->loadObjectList();
	}
	
	public function getNoTitle() {
		$db			= Factory::getDbo();
		$query		= $db->getQuery(true);
		$limit		= (int) $this->getData('limit');
		
		$query->select($db->qn('id'))->select($db->qn('url'))
			->select($db->qn('sef'))->select($db->qn('date'))
			->from($db->qn('#__rsseo_pages'))
			->where($db->qn('title').' = '.$db->q(''))
			->where($db->qn('published').' = '.$db->q(1));
		
		$db->setQuery($query, 0, $limit);
		return $db->loadObjectList();
	}
	
	public function getNoDesc() {
		$db			= Factory::getDbo();
		$query		= $db->getQuery(true);
		$limit		= (int) $this->getData('limit');
		
		$query->select($db->qn('id'))->select($db->qn('url'))
			->select($db->qn('sef'))->select($db->qn('date'))
			->from($db->qn('#__rsseo_pages'))
			->where($db->qn('description').' = '.$db->q(''))
			->where($db->qn('published').' = '.$db->q(1));
		
		$db->setQuery($query, 0, $limit);
		return $db->loadObjectList();
	}
	
	public function getMostVisited() {
		$db			= Factory::getDbo();
		$query		= $db->getQuery(true);
		$limit		= (int) $this->getData('limit');
		
		$query->select($db->qn('id'))->select($db->qn('url'))
			->select($db->qn('sef'))->select($db->qn('hits'))
			->from($db->qn('#__rsseo_pages'))
			->where($db->qn('hits').' > 0')
			->order($db->qn('hits').' DESC');
		
		$db->setQuery($query, 0, $limit);
		return $db->loadObjectList();
	}
	
	public function getErrorLinks() {
		$db			= Factory::getDbo();
		$query		= $db->getQuery(true);
		$limit		= (int) $this->getData('limit');
		
		$query->select($db->qn('url'))->select($db->qn('code'))
			->select($db->qn('count'))
			->from($db->qn('#__rsseo_error_links'))
			->order($db->qn('count').' DESC');
		
		$db->setQuery($query, 0, $limit);
		return $db->loadObjectList();
	}
	
	public function getGKeywords() {
		$db			= Factory::getDbo();
		$query		= $db->getQuery(true);
		$data		= $this->getData();
		
		if (isset($data->keywords) && !empty($data->keywords)) {
			$data->keywords = array_map('intval', $data->keywords);
			
			$query->select('*')
				->from($db->qn('#__rsseo_gkeywords'))
				->where($db->qn('id').' IN ('.implode(',',$data->keywords).')');
			
			$db->setQuery($query);
			if ($keywords = $db->loadObjectList()) {
				foreach ($keywords as $i => $keyword) {
					$query->clear()
						->select($db->qn('date'))
						->select('COUNT(DISTINCT '.$db->qn('page').') AS pages')
						->select('SUM('.$db->qn('impressions').') AS impressions')
						->select('SUM('.$db->qn('clicks').') AS clicks')
						->select('SUM('.$db->qn('position').' * '.$db->qn('impressions').') / SUM('.$db->qn('impressions').') AS avgposition')
						->select('AVG('.$db->qn('ctr').') AS ctr')
						->from($db->qn('#__rsseo_gkeywords_data'))
						->where($db->qn('idk').' = '.$db->q($keyword->id))
						->group($db->qn('date'));
					$db->setQuery($query);
					if ($gKeywordData = $db->loadObjectList()) {
						$pages = $impressions = $clicks = $avg = 0;
						
						foreach ($gKeywordData as $data) {
							$pages += (int) $data->pages;
							$impressions += (int) $data->impressions;
							$clicks += (int) $data->clicks;
							$avg += $data->avgposition;
						}
						
						$keywords[$i]->pages = $pages;
						$keywords[$i]->impressions = $impressions;
						$keywords[$i]->clicks = $clicks;
						$keywords[$i]->avg = number_format($avg / count($gKeywordData), 2);
						$keywords[$i]->ctr = number_format(($clicks / $impressions) * 100, 2).'%';
					} else {
						$keywords[$i]->pages = '-';
						$keywords[$i]->impressions = '-';
						$keywords[$i]->clicks = '-';
						$keywords[$i]->avg = '-';
						$keywords[$i]->ctr = '-';
					}
				}
				
				return $keywords;
			}
		}
		
		return array();
	}
	
	public function getTabs() {
		$tabs =  new RSSeoAdapterTabs('report');
		return $tabs;
	}
	
	protected function defaults() {
		return (object) array('email_report' => 0, 'email' => '', 'mode' => 'weekly', 'mode_days' => 1, 'mode_day' => 1, 'font' => 'times', 'orientation' => 'portrait', 'paper' => 'a4', 'last_crawled' => 0, 'most_visited' => 0, 'error_links' => 0, 'no_title' => 0, 'no_desc' => 0, 'limit' => 10, 'enable_gkeywords' => 0, 'keywords' => array());
	}
}