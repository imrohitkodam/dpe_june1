<?php
/**
* @package RSSeo!
* @copyright (C) 2016 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Pagination\Pagination;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;

class rsseoModelStatistics extends BaseDatabaseModel
{
	
	public function __construct($config = array()) {
		parent::__construct($config);
		
		$app	= Factory::getApplication();
		$input	= $app->input;
		
		// Get pagination request variables
		$limitp = $app->getUserStateFromRequest('com_rsseo.pageviews.limit', 'limit', 10, 'int');
		$limitstartp = $input->getInt('limitstart', 0);
		$limitv = $app->getUserStateFromRequest('com_rsseo.visitors.limit', 'limit', 10, 'int');
		$limitstartv = $input->getInt('limitstart', 0);
		
		// In case limit has been changed, adjust it
		$limitstartp = ($limitp != 0 ? (floor($limitstartp / $limitp) * $limitp) : 0);
		$limitstartv = ($limitv != 0 ? (floor($limitstartv / $limitv) * $limitv) : 0);

		$this->setState('com_rsseo.pageviews.limit', $limitp);
		$this->setState('com_rsseo.pageviews.limitstart', $limitstartp);
		$this->setState('com_rsseo.visitors.limit', $limitv);
		$this->setState('com_rsseo.visitors.limitstart', $limitstartv);
		
		$this->setPageViewsQuery();
		$this->setVisitorsQuery();
	}
	
	public function getTotalVisitors() {
		$db		= Factory::getDbo();
		$query	= $db->getQuery(true);
		
		// Get total visitors
		$query->select('COUNT(DISTINCT('.$db->qn('session_id').'))')
			->from($db->qn('#__rsseo_visitors'));
		$db->setQuery($query);
		return (int) $db->loadResult();
	}
	
	public function getTotalPageViews() {
		$db		= Factory::getDbo();
		$query	= $db->getQuery(true);
		
		// Get total visitors
		$query->select('COUNT('.$db->qn('id').')')
			->from($db->qn('#__rsseo_visitors'));
		$db->setQuery($query);
		return (int) $db->loadResult();
	}
	
	public function getTotalVisitorsTimeframe() {
		$db			= Factory::getDbo();
		$query		= $db->getQuery(true);
		$config		= Factory::getConfig();
		$timezone	= new DateTimeZone($config->get('offset'));
		$offset		= $timezone->getOffset(new DateTime('now', new DateTimeZone('UTC')));
		$input		= Factory::getApplication()->input;
		$dFrom		= Factory::getDate()->modify('-7 days')->toSql();
		$dTo		= Factory::getDate()->setTime(23,59,59)->toSql();
		$from		= $input->getString('from', $dFrom);
		$to			= $input->getString('to', $dTo);
		$from		= Factory::getDate($from)->setTime(0,0,0)->toSql();
		$to			= Factory::getDate($to)->setTime(23,59,59)->toSql();
		
		// Get total visitors
		$query->select('COUNT(DISTINCT('.$db->qn('session_id').'))')
			->from($db->qn('#__rsseo_visitors'))
			->where('DATE_ADD('.$db->qn('date').', INTERVAL '.$offset.' SECOND) > '.$db->q($from))
			->where('DATE_ADD('.$db->qn('date').', INTERVAL '.$offset.' SECOND) < '.$db->q($to));
		$db->setQuery($query);
		return (int) $db->loadResult();
	}
	
	public function getTotalPageViewsTimeframe() {
		$db			= Factory::getDbo();
		$query		= $db->getQuery(true);
		$config		= Factory::getConfig();
		$timezone	= new DateTimeZone($config->get('offset'));
		$offset		= $timezone->getOffset(new DateTime('now', new DateTimeZone('UTC')));
		$input		= Factory::getApplication()->input;
		$dFrom		= Factory::getDate()->modify('-7 days')->toSql();
		$dTo		= Factory::getDate()->setTime(23,59,59)->toSql();
		$from		= $input->getString('from', $dFrom);
		$to			= $input->getString('to', $dTo);
		$from		= Factory::getDate($from)->setTime(0,0,0)->toSql();
		$to			= Factory::getDate($to)->setTime(23,59,59)->toSql();
		
		// Get total visitors
		$query->select('COUNT('.$db->qn('id').')')
			->from($db->qn('#__rsseo_visitors'))
			->where('DATE_ADD('.$db->qn('date').', INTERVAL '.$offset.' SECOND) > '.$db->q($from))
			->where('DATE_ADD('.$db->qn('date').', INTERVAL '.$offset.' SECOND) < '.$db->q($to));
		$db->setQuery($query);
		return (int) $db->loadResult();
	}
	
	public function getChartVisitors() {
		$db			= Factory::getDbo();
		$query		= $db->getQuery(true);
		$config		= Factory::getConfig();
		$timezone	= new DateTimeZone($config->get('offset'));
		$offset		= $timezone->getOffset(new DateTime('now', new DateTimeZone('UTC')));
		$input		= Factory::getApplication()->input;
		$dFrom		= Factory::getDate()->modify('-7 days')->toSql();
		$dTo		= Factory::getDate()->setTime(23,59,59)->toSql();
		$from		= $input->getString('from', $dFrom);
		$to			= $input->getString('to', $dTo);
		$from		= Factory::getDate($from)->setTime(0,0,0)->toSql();
		$to			= Factory::getDate($to)->setTime(23,59,59)->toSql();
		$return		= array();
		
		// Get the visitors
		$query->clear()
			->select('COUNT(DISTINCT('.$db->qn('session_id').')) AS count')
			->select('DATE(DATE_ADD('.$db->qn('date').', INTERVAL '.$offset.' SECOND)) as thedate')
			->from($db->qn('#__rsseo_visitors'))
			->where('DATE_ADD('.$db->qn('date').', INTERVAL '.$offset.' SECOND) > '.$db->q($from))
			->where('DATE_ADD('.$db->qn('date').', INTERVAL '.$offset.' SECOND) < '.$db->q($to))
			->group('DATE('.$db->qn('date').')');
		$db->setQuery($query);
		$visitors = $db->loadObjectList();
		
		if ($visitors) {
			$return[] = array(Text::_('COM_RSSEO_CHART_DATE'), Text::_('COM_RSSEO_CHART_VISITORS'));
			
			foreach ($visitors as $visitor) {
				$date = Factory::getDate($visitor->thedate)->format('d M Y');
				$return[] = array($date, (int) $visitor->count);
			}
		}
		
		return $return;
	}
	
	public function getChartPageViews() {
		$db			= Factory::getDbo();
		$query		= $db->getQuery(true);
		$config		= Factory::getConfig();
		$timezone	= new DateTimeZone($config->get('offset'));
		$offset		= $timezone->getOffset(new DateTime('now', new DateTimeZone('UTC')));
		$input		= Factory::getApplication()->input;
		$dFrom		= Factory::getDate()->modify('-7 days')->toSql();
		$dTo		= Factory::getDate()->setTime(23,59,59)->toSql();
		$from		= $input->getString('from', $dFrom);
		$to			= $input->getString('to', $dTo);
		$from		= Factory::getDate($from)->setTime(0,0,0)->toSql();
		$to			= Factory::getDate($to)->setTime(23,59,59)->toSql();
		$return		= array();
		
		// Get the pageviews
		$query->clear()
			->select('COUNT('.$db->qn('id').') AS count')
			->select('DATE(DATE_ADD('.$db->qn('date').', INTERVAL '.$offset.' SECOND)) as thedate')
			->from($db->qn('#__rsseo_visitors'))
			->where('DATE_ADD('.$db->qn('date').', INTERVAL '.$offset.' SECOND) > '.$db->q($from))
			->where('DATE_ADD('.$db->qn('date').', INTERVAL '.$offset.' SECOND) < '.$db->q($to))
			->group('DATE('.$db->qn('date').')');
		$db->setQuery($query);
		$pageviews = $db->loadObjectList();
		
		if ($pageviews) {
			$return[] = array(Text::_('COM_RSSEO_CHART_DATE'), Text::_('COM_RSSEO_CHART_PAGEVIEWS'));
			
			foreach ($pageviews as $pageview) {
				$date = Factory::getDate($pageview->thedate)->format('d M Y');
				$return[] = array($date, (int) $pageview->count);
			}
		}
		
		return $return;
	}
	
	public function getVisitors() {
		$db		= Factory::getDbo();
		
		$db->setQuery($this->visitorsQuery, $this->getState('com_rsseo.visitors.limitstart'), $this->getState('com_rsseo.visitors.limit'));
		return $db->loadObjectList();
	}
	
	public function getVisitorsTotal() {
		$db		= Factory::getDbo();
		
		$db->setQuery($this->visitorsQuery);
		$db->execute();
		
		return (int) $db->getNumRows();
	}
	
	protected function setVisitorsQuery() {
		$db			= Factory::getDbo();
		$config		= Factory::getConfig();
		$timezone	= new DateTimeZone($config->get('offset'));
		$offset		= $timezone->getOffset(new DateTime('now', new DateTimeZone('UTC')));
		$input		= Factory::getApplication()->input;
		$dFrom		= Factory::getDate()->modify('-7 days')->toSql();
		$dTo		= Factory::getDate()->setTime(23,59,59)->toSql();
		$from		= $input->getString('from', $dFrom);
		$to			= $input->getString('to', $dTo);
		$from		= Factory::getDate($from)->setTime(0,0,0)->toSql();
		$to			= Factory::getDate($to)->setTime(23,59,59)->toSql();
		
		$subquery = $db->getQuery(true)
			->clear()
			->select($db->qn('session_id'))->select('MAX('.$db->qn('date').') AS '.$db->qn('date'))
			->from($db->qn('#__rsseo_visitors'))
			->where('DATE_ADD('.$db->qn('date').', INTERVAL '.$offset.' SECOND) > '.$db->q($from))
			->where('DATE_ADD('.$db->qn('date').', INTERVAL '.$offset.' SECOND) < '.$db->q($to))
			->group($db->qn('session_id'));
		
		$this->visitorsQuery = 'SELECT * FROM ('.$subquery.') AS filtered_rsseo_visitors JOIN '.$db->qn('#__rsseo_visitors').' USING ('.$db->qn('session_id').', '.$db->qn('date').') GROUP BY '.$db->qn('session_id').', '.$db->qn('date').' ORDER BY '.$db->qn('date').' DESC';
	}
	
	public function getPageViews() {
		$db		= Factory::getDbo();
		
		$db->setQuery($this->pageviewsQuery, $this->getState('com_rsseo.pageviews.limitstart'), $this->getState('com_rsseo.pageviews.limit'));
		return $db->loadObjectList();
	}
	
	public function getPageViewsTotal() {
		$db		= Factory::getDbo();
		
		$db->setQuery($this->pageviewsQuery);
		$db->execute();
		
		return (int) $db->getNumRows();
	}
	
	public function getPageViewsPagination() {
		return new Pagination($this->getPageViewsTotal(), $this->getState('com_rsseo.pageviews.limitstart'), $this->getState('com_rsseo.pageviews.limit'));
	}
	
	protected function setPageViewsQuery() {
		$db		= Factory::getDbo();
		$query	= $db->getQuery(true);
		$input	= Factory::getApplication()->input;
		$id		= $input->getInt('id',0);
		
		if ($input->get('layout','') == 'pageviews') {		
			$query->select('v2.*')
				->select($db->qn('u.username'))
				->from($db->qn('#__rsseo_visitors','v1'))
				->join('LEFT',$db->qn('#__rsseo_visitors','v2').' ON '.$db->qn('v1.session_id').' = '.$db->qn('v2.session_id'))
				->join('LEFT',$db->qn('#__users','u').' ON '.$db->qn('v2.user_id').' = '.$db->qn('u.id'))
				->where($db->qn('v1.id').' = '.$db->q($id))
				->order($db->qn('date').' ASC');
				
			$this->pageviewsQuery = (string) $query;
		}
	}
}