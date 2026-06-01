<?php
/**
* @package RSSeo!
* @copyright (C) 2016 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;

require_once JPATH_SITE.'/administrator/components/com_rsseo/helpers/Google/autoload.php';

class rsseoGoogleAPI {
	
	protected static $client;
	
	protected static $ca;
	
	public function __construct($type) {
		$client = new Google\Client();
		$secret	= Factory::getConfig()->get('secret');
		
		// Check for a valid GSA Key
		if (!file_exists(JPATH_ADMINISTRATOR.'/components/com_rsseo/assets/keys/'.md5($secret.'private_key').'.json')) {
			throw new Exception(Text::_('COM_RSSEO_GSA_KEY_FILE_ERROR'));
		}
		
		self::$ca = self::getCA();
		
		// Set the HTTP Client
		$httpClient = new GuzzleHttp\Client(['verify' => self::$ca]);
		$client->setHttpClient($httpClient);
		
		// Set the GSA Key
		$client->setAuthConfig(JPATH_ADMINISTRATOR.'/components/com_rsseo/assets/keys/'.md5($secret.'private_key').'.json');
		
		if ($type == 'gkeywords') {
			$client->addScope('https://www.googleapis.com/auth/webmasters.readonly');
			$client->addScope('https://www.googleapis.com/auth/webmasters');
		} elseif ($type == 'ganalytics') {
			$client->addScope('https://www.googleapis.com/auth/analytics.readonly');
		}
		
		self::$client = $client;
	}
	
	public static function getInstance($type) {
		static $instances = array();
		
		$hash = md5($type);
		
		if (!isset($instances[$hash])) {
			$instances[$hash] = new rsseoGoogleAPI($type);
		}
		
		return $instances[$hash];
	}
	
	// Get Webmasters sites options array
	public static function getSites($select = false) {
		$data	 = $select ? array(HTMLHelper::_('select.option', '', Text::_('COM_RSSEO_GKEYWORDS_SELECT_SITE'))) : array();
		
		$cache = Factory::getCache('rsseo_google_sites');
		$cache->setCaching(true);
		$cache->setLifeTime(300);
		$array = $cache->get(array('rsseoGoogleAPI', 'getSitesData'));
		$cache->gc();
		
		if ($array) {
			foreach ($array as $site) {
				$data[] = HTMLHelper::_('select.option', $site->siteUrl, $site->siteUrl);
			}
		}
		
		return $data;
	}
	
	// Get Webmasters sites
	public static function getSitesData() {
		$service = new Google\Service\Webmasters(self::$client);
		$sites	 = $service->sites->listSites();
		
		return $sites->getSiteEntry();
	}
	
	// Get webmasters search analytics
	public static function getSearchData($options = array()) {
		$service = new Google\Service\Webmasters(self::$client);
		$request = new Google\Service\Webmasters\SearchAnalyticsQueryRequest;
		$filter	 = new Google\Service\Webmasters\ApiDimensionFilterGroup;
		$dFilter = new Google\Service\Webmasters\ApiDimensionFilter;
		
		$request->setStartDate($options['start']);
		$request->setEndDate($options['end']);
		$request->setDimensions(array('query', 'page', 'device', 'country'));
		$request->setSearchType('web');
		$request->setRowLimit(5000);
		
		$dFilter->setDimension('query');
		$dFilter->setOperator('equals');
		$dFilter->setExpression($options['keyword']);
		
		$filter->setFilters(array($dFilter));
		$request->setDimensionFilterGroups(array($filter));
		
		$keywords = $service->searchanalytics->query($options['site'], $request);
		
		return $keywords->getRows();
	}
	
	// Get users profiles
	public static function getProfiles($select = true) {
		$data		 = $select ? array(HTMLHelper::_('select.option', '', Text::_('COM_RSSEO_SELECT_GA_ACCOUNT'))) : array();
		$ga4Profiles = array();
		
		// Get GA4 profiles
		$options	 = array('pageSize' => 200);
		$pageToken	 = true;
		$service_ga4 = new Google\Service\GoogleAnalyticsAdmin(self::$client);

		while ($pageToken) {
			$accountSummaries	= $service_ga4->accountSummaries->listAccountSummaries($options);
			$accounts			= $accountSummaries->getAccountSummaries();
			$pageToken			= $accountSummaries->getNextPageToken();

			if ($pageToken){
				$options = array('pageSize' => 200, 'pageToken' => $pageToken);
			}

			if ($accounts) {
				foreach ($accounts as $account) {
					$properties = $account->getPropertySummaries();
					if ($properties) {
						foreach ($properties as $property) {
							$datastreams = $service_ga4->properties_dataStreams->listPropertiesDataStreams($property->getProperty())->getDataStreams();
							
							if ($datastreams) {
								foreach ($datastreams as $datastream) {
									$name	= $datastream->getName();
									
									preg_match('#properties\/(.*?)/dataStreams\/(.*?)#is', $name, $match);
									
									if (isset($match[1])) {
										if ($datastream->type == 'WEB_DATA_STREAM') {
											$propertyID		= 'ga4:'.$match[1];
											$ga4Profiles[]	= HTMLHelper::_('select.option', $propertyID, $datastream->getDisplayName());
										}
									}
								}
							}
						}
					}
				}
			}
		}
		
		if ($ga4Profiles) {
			$ga4OptStart = new stdClass();
			$ga4OptStart->value = '<OPTGROUP>';
			$ga4OptStart->text = Text::_('COM_RSSEO_GA4_PROFILES');
			$ga4OptEnd = new stdClass();
			$ga4OptEnd->value = '</OPTGROUP>';
			$ga4OptEnd->text = Text::_('COM_RSSEO_GA4_PROFILES');
			
			$data = array_merge($data, array($ga4OptStart), $ga4Profiles, array($ga4OptEnd));
		}

		return $data;
	}
	
	public static function getData($type = 'general') {
		$key	= self::getKey();
		$cache 	= Factory::getCache('com_rsseo_analytics');
		
		$cache->setCaching(true);
		$data = $cache->get(array('rsseoGoogleAPI', $type), array($key, $type));
		
		return $data;
	}
	
	// Get general data
	public static function general() {
		$input	 = Factory::getApplication()->input;
		$profile = $input->getString('profile','');
		$start	 = $input->getString('start','30daysAgo');
		$end	 = $input->getString('end','yesterday');
		
		if (substr($profile, 0, 4) == 'ga3:') {
			$profile = substr_replace($profile, '', 0, 4);
			$service = new Google\Service\Analytics(self::$client);
			$general = $service->data_ga->get('ga:'.$profile, $start, $end, 'ga:sessions,ga:users,ga:newUsers,ga:pageviews,ga:avgSessionDuration,ga:bounceRate');
			$results = $general->getTotalsForAllResults();
			
			return array($results['ga:sessions'], $results['ga:users'], $results['ga:newUsers'], $results['ga:pageviews'], $results['ga:avgSessionDuration'], $results['ga:bounceRate']);
		} else {
			$profile	= substr_replace($profile, '', 0, 4);
			$service	= new Google\Service\AnalyticsData(self::$client);
			$metrics 	= array('sessions', 'totalUsers', 'newUsers', 'screenPageViews', 'averageSessionDuration', 'bounceRate');
			
			foreach ($metrics as $i => $metric) {
				$metricobj = new Google\Service\AnalyticsData\Metric();
				$metricobj->setName($metric);
				$metrics[$i] = $metricobj;
			}
			
			$dateRange = new Google\Service\AnalyticsData\DateRange();
			$dateRange->setStartDate($start);
			$dateRange->setEndDate($end);
			
			$request = new Google\Service\AnalyticsData\RunReportRequest();
			$request->setProperty($profile);
			$request->setDateRanges($dateRange);
			$request->setMetrics($metrics);
			$request->setMetricAggregations('TOTAL');
			$request->setKeepEmptyRows(true);
			
			$response = $service->properties->runReport('properties/'.$profile, $request);
			
			$rows = array();
			foreach ($response->getRows() as $row) {
				if (isset($row->getMetricValues()[0])) {
					foreach ($row->getMetricValues() as $item) {
						$rows[] = $item->getValue();
					}
				}
			}
			
			return $rows;
		}
	}
	
	// Get new vs returning sessions
	public static function newvsreturning() {
		$input	 = Factory::getApplication()->input;
		$profile = $input->getString('profile','');
		$start	 = $input->getString('start','30daysAgo');
		$end	 = $input->getString('end','yesterday');
		
		if (substr($profile, 0, 4) == 'ga3:') {
			$profile = substr_replace($profile, '', 0, 4);
			$service = new Google\Service\Analytics(self::$client);
			
			$newvsreturning = $service->data_ga->get('ga:'.$profile, $start, $end, 'ga:sessions,ga:pageviewsPerSession,ga:avgSessionDuration,ga:bounceRate', array('dimensions' => 'ga:userType'));
			return $newvsreturning->getRows();
		} else {
			$profile	= substr_replace($profile, '', 0, 4);
			$service	= new Google\Service\AnalyticsData(self::$client);
			$metrics 	= array('sessions', 'screenPageViewsPerSession', 'averageSessionDuration', 'bounceRate');
			$dimensions	= array('newVsReturning');
			
			foreach ($metrics as $i => $metric) {
				$m = new Google\Service\AnalyticsData\Metric();
				$m->setName($metric);
				$metrics[$i] = $m;
			}
			
			foreach ($dimensions as $j => $dimension) {
				$d = new Google\Service\AnalyticsData\Dimension();
				$d->setName($dimension);
				$dimensions[$j] = $d;
			}
			
			$ordering = new Google\Service\AnalyticsData\OrderBy();
			$metricOrderBy = new Google\Service\AnalyticsData\MetricOrderBy();
			$metricOrderBy->setMetricName('sessions');
			$ordering->setMetric($metricOrderBy);
			$ordering->setDesc(true);
			
			$dateRange = new Google\Service\AnalyticsData\DateRange();
			$dateRange->setStartDate($start);
			$dateRange->setEndDate($end);
			
			$request = new Google\Service\AnalyticsData\RunReportRequest();
			$request->setProperty($profile);
			$request->setDateRanges($dateRange);
			$request->setMetrics($metrics);
			$request->setDimensions($dimensions);
			$request->setMetricAggregations('TOTAL');
			$request->setKeepEmptyRows(true);
			$request->setOrderBys($ordering);
			
			$response	= $service->properties->runReport('properties/'.$profile, $request);
			
			$rows = array();
			foreach ($response->getRows() as $row) {
				$values = array();
				
				if (isset($row->getDimensionValues()[0])) {
					foreach ($row->getDimensionValues() as $item) {
						$values[] = $item->getValue();
					}
				}

				if (isset($row->getMetricValues()[0])) {
					foreach ($row->getMetricValues() as $item) {
						$values[] = $item->getValue();
					}
				}
				
				$rows[] = $values;
			}
			
			return $rows;
		}
	}
	
	public static function sessions() {
		$data	 = array();
		$input	 = Factory::getApplication()->input;
		$profile = $input->getString('profile','');
		$start	 = $input->getString('start','30daysAgo');
		$end	 = $input->getString('end','yesterday');
		
		if (substr($profile, 0, 4) == 'ga3:') {
			$profile = substr_replace($profile, '', 0, 4);
			$service = new Google\Service\Analytics(self::$client);
			$sessions = $service->data_ga->get('ga:'.$profile, $start, $end, 'ga:sessions', array('dimensions' => 'ga:date'));
			$data['rows']  = $sessions->getRows();
			$data['total'] = $sessions->getTotalsForAllResults()['ga:sessions'];
		} else {
			$profile	= substr_replace($profile, '', 0, 4);
			$service	= new Google\Service\AnalyticsData(self::$client);
			$metrics 	= array('sessions');
			$dimensions	= array('date');
			
			foreach ($metrics as $i => $metric) {
				$m = new Google\Service\AnalyticsData\Metric();
				$m->setName($metric);
				$metrics[$i] = $m;
			}
			
			foreach ($dimensions as $j => $dimension) {
				$d = new Google\Service\AnalyticsData\Dimension();
				$d->setName($dimension);
				$dimensions[$j] = $d;
			}
			
			$dateRange = new Google\Service\AnalyticsData\DateRange();
			$dateRange->setStartDate($start);
			$dateRange->setEndDate($end);
			
			$request = new Google\Service\AnalyticsData\RunReportRequest();
			$request->setProperty($profile);
			$request->setDateRanges($dateRange);
			$request->setMetrics($metrics);
			$request->setDimensions($dimensions);
			$request->setMetricAggregations('TOTAL');
			$request->setKeepEmptyRows(true);
			
			$response	= $service->properties->runReport('properties/'.$profile, $request);
			
			if (method_exists($response, 'getTotals') && isset($response->getTotals()[0]->getMetricValues()[0])){
				$data['total'] = $response->getTotals()[0]->getMetricValues()[0]->getValue();
			}
			
			$rows = array();
			foreach ($response->getRows() as $row) {
				$values = array();
				
				if (isset($row->getDimensionValues()[0])) {
					$values[] = $row->getDimensionValues()[0]->getValue();
				}

				if (isset($row->getMetricValues()[0])) {
					$values[] = $row->getMetricValues()[0]->getValue();
				}
				
				$rows[] = $values;
			}
			
			$data['rows']  = $rows;
		}
		
		return $data;
	}
	
	public static function geocountry() {
		$input	 = Factory::getApplication()->input;
		$profile = $input->getString('profile','');
		$start	 = $input->getString('start','30daysAgo');
		$end	 = $input->getString('end','yesterday');
		
		if (substr($profile, 0, 4) == 'ga3:') {
			$profile = substr_replace($profile, '', 0, 4);
			$service = new Google\Service\Analytics(self::$client);
			
			$geocountry = $service->data_ga->get('ga:'.$profile, $start, $end, 'ga:sessions,ga:newUsers,ga:bounceRate,ga:pageviewsPerSession,ga:avgSessionDuration', array('dimensions' => 'ga:country', 'sort' => '-ga:sessions'));
			return $geocountry->getRows();
		} else {
			$profile	= substr_replace($profile, '', 0, 4);
			$service	= new Google\Service\AnalyticsData(self::$client);
			$metrics 	= array('sessions', 'newUsers', 'bounceRate', 'screenPageViewsPerSession', 'averageSessionDuration');
			$dimensions	= array('country');
			
			foreach ($metrics as $i => $metric) {
				$m = new Google\Service\AnalyticsData\Metric();
				$m->setName($metric);
				$metrics[$i] = $m;
			}
			
			foreach ($dimensions as $j => $dimension) {
				$d = new Google\Service\AnalyticsData\Dimension();
				$d->setName($dimension);
				$dimensions[$j] = $d;
			}
			
			$ordering = new Google\Service\AnalyticsData\OrderBy();
			$metricOrderBy = new Google\Service\AnalyticsData\MetricOrderBy();
			$metricOrderBy->setMetricName('sessions');
			$ordering->setMetric($metricOrderBy);
			$ordering->setDesc(true);
			
			$dateRange = new Google\Service\AnalyticsData\DateRange();
			$dateRange->setStartDate($start);
			$dateRange->setEndDate($end);
			
			$request = new Google\Service\AnalyticsData\RunReportRequest();
			$request->setProperty($profile);
			$request->setDateRanges($dateRange);
			$request->setMetrics($metrics);
			$request->setDimensions($dimensions);
			$request->setMetricAggregations('TOTAL');
			$request->setKeepEmptyRows(true);
			$request->setOrderBys($ordering);
			
			$response	= $service->properties->runReport('properties/'.$profile, $request);
			
			$rows = array();
			foreach ($response->getRows() as $row) {
				$values = array();
				
				if (isset($row->getDimensionValues()[0])) {
					foreach ($row->getDimensionValues() as $item) {
						$values[] = $item->getValue();
					}
				}

				if (isset($row->getMetricValues()[0])) {
					foreach ($row->getMetricValues() as $item) {
						$values[] = $item->getValue();
					}
				}
				
				$rows[] = $values;
			}
			
			return $rows;
		}
	}
	
	public static function browsers() {
		$input	 = Factory::getApplication()->input;
		$profile = $input->getString('profile','');
		$start	 = $input->getString('start','30daysAgo');
		$end	 = $input->getString('end','yesterday');
		
		if (substr($profile, 0, 4) == 'ga3:') {
			$profile = substr_replace($profile, '', 0, 4);
			$service = new Google\Service\Analytics(self::$client);
		
			$browsers = $service->data_ga->get('ga:'.$profile, $start, $end, 'ga:sessions,ga:pageviewsPerSession,ga:avgSessionDuration,ga:bounceRate', array('dimensions' => 'ga:browser', 'sort' => '-ga:sessions'));
			return $browsers->getRows();
		} else {
			$profile	= substr_replace($profile, '', 0, 4);
			$service	= new Google\Service\AnalyticsData(self::$client);
			$metrics 	= array('sessions', 'screenPageViewsPerSession', 'averageSessionDuration', 'bounceRate');
			$dimensions	= array('browser');
			
			foreach ($metrics as $i => $metric) {
				$m = new Google\Service\AnalyticsData\Metric();
				$m->setName($metric);
				$metrics[$i] = $m;
			}
			
			foreach ($dimensions as $j => $dimension) {
				$d = new Google\Service\AnalyticsData\Dimension();
				$d->setName($dimension);
				$dimensions[$j] = $d;
			}
			
			$ordering = new Google\Service\AnalyticsData\OrderBy();
			$metricOrderBy = new Google\Service\AnalyticsData\MetricOrderBy();
			$metricOrderBy->setMetricName('sessions');
			$ordering->setMetric($metricOrderBy);
			$ordering->setDesc(true);
			
			$dateRange = new Google\Service\AnalyticsData\DateRange();
			$dateRange->setStartDate($start);
			$dateRange->setEndDate($end);
			
			$request = new Google\Service\AnalyticsData\RunReportRequest();
			$request->setProperty($profile);
			$request->setDateRanges($dateRange);
			$request->setMetrics($metrics);
			$request->setDimensions($dimensions);
			$request->setMetricAggregations('TOTAL');
			$request->setKeepEmptyRows(true);
			$request->setOrderBys($ordering);
			
			$response	= $service->properties->runReport('properties/'.$profile, $request);
			
			$rows = array();
			foreach ($response->getRows() as $row) {
				$values = array();
				
				if (isset($row->getDimensionValues()[0])) {
					foreach ($row->getDimensionValues() as $item) {
						$values[] = $item->getValue();
					}
				}

				if (isset($row->getMetricValues()[0])) {
					foreach ($row->getMetricValues() as $item) {
						$values[] = $item->getValue();
					}
				}
				
				$rows[] = $values;
			}
			
			return $rows;
		}
	}
	
	public static function mobiles() {
		$input	 = Factory::getApplication()->input;
		$profile = $input->getString('profile','');
		$start	 = $input->getString('start','30daysAgo');
		$end	 = $input->getString('end','yesterday');
		
		if (substr($profile, 0, 4) == 'ga3:') {
			$profile = substr_replace($profile, '', 0, 4);
			$service = new Google\Service\Analytics(self::$client);
		
			$mobiles = $service->data_ga->get('ga:'.$profile, $start, $end, 'ga:sessions,ga:pageviewsPerSession,ga:avgSessionDuration,ga:bounceRate', array('dimensions' => 'ga:operatingSystem','segment' => 'gaid::-14', 'sort' => '-ga:sessions'));
			return $mobiles->getRows();
		} else {
			$profile	= substr_replace($profile, '', 0, 4);
			$service	= new Google\Service\AnalyticsData(self::$client);
			$metrics 	= array('sessions', 'screenPageViewsPerSession', 'averageSessionDuration', 'bounceRate');
			$dimensions	= array('operatingSystem');
			
			foreach ($metrics as $i => $metric) {
				$m = new Google\Service\AnalyticsData\Metric();
				$m->setName($metric);
				$metrics[$i] = $m;
			}
			
			foreach ($dimensions as $j => $dimension) {
				$d = new Google\Service\AnalyticsData\Dimension();
				$d->setName($dimension);
				$dimensions[$j] = $d;
			}
			
			$ordering = new Google\Service\AnalyticsData\OrderBy();
			$metricOrderBy = new Google\Service\AnalyticsData\MetricOrderBy();
			$metricOrderBy->setMetricName('sessions');
			$ordering->setMetric($metricOrderBy);
			$ordering->setDesc(true);
			
			$dateRange = new Google\Service\AnalyticsData\DateRange();
			$dateRange->setStartDate($start);
			$dateRange->setEndDate($end);
			
			$request = new Google\Service\AnalyticsData\RunReportRequest();
			$request->setProperty($profile);
			$request->setDateRanges($dateRange);
			$request->setMetrics($metrics);
			$request->setDimensions($dimensions);
			$request->setMetricAggregations('TOTAL');
			$request->setKeepEmptyRows(true);
			$request->setOrderBys($ordering);
			
			$response	= $service->properties->runReport('properties/'.$profile, $request);
			
			$rows = array();
			foreach ($response->getRows() as $row) {
				$values = array();
				
				if (isset($row->getDimensionValues()[0])) {
					foreach ($row->getDimensionValues() as $item) {
						$values[] = $item->getValue();
					}
				}

				if (isset($row->getMetricValues()[0])) {
					foreach ($row->getMetricValues() as $item) {
						$values[] = $item->getValue();
					}
				}
				
				$rows[] = $values;
			}
			
			return $rows;
		}
	}
	
	public static function sources() {		
		$input	 = Factory::getApplication()->input;
		$profile = $input->getString('profile','');
		$start	 = $input->getString('start','30daysAgo');
		$end	 = $input->getString('end','yesterday');
		
		if (substr($profile, 0, 4) == 'ga3:') {
			$profile = substr_replace($profile, '', 0, 4);
			$service = new Google\Service\Analytics(self::$client);
		
			$sources = $service->data_ga->get('ga:'.$profile, $start, $end, 'ga:sessions,ga:pageviewsPerSession,ga:avgSessionDuration,ga:bounceRate', array('dimensions' => 'ga:source,ga:medium','sort' => '-ga:sessions', 'max-results' => 20));
			return $sources->getRows();
		} else {
			$profile	= substr_replace($profile, '', 0, 4);
			$service	= new Google\Service\AnalyticsData(self::$client);
			$metrics 	= array('sessions', 'screenPageViewsPerSession', 'averageSessionDuration', 'bounceRate');
			$dimensions	= array('sessionSource','sessionMedium');
			
			foreach ($metrics as $i => $metric) {
				$m = new Google\Service\AnalyticsData\Metric();
				$m->setName($metric);
				$metrics[$i] = $m;
			}
			
			foreach ($dimensions as $j => $dimension) {
				$d = new Google\Service\AnalyticsData\Dimension();
				$d->setName($dimension);
				$dimensions[$j] = $d;
			}
			
			$ordering = new Google\Service\AnalyticsData\OrderBy();
			$metricOrderBy = new Google\Service\AnalyticsData\MetricOrderBy();
			$metricOrderBy->setMetricName('sessions');
			$ordering->setMetric($metricOrderBy);
			$ordering->setDesc(true);
			
			$dateRange = new Google\Service\AnalyticsData\DateRange();
			$dateRange->setStartDate($start);
			$dateRange->setEndDate($end);
			
			$request = new Google\Service\AnalyticsData\RunReportRequest();
			$request->setProperty($profile);
			$request->setDateRanges($dateRange);
			$request->setMetrics($metrics);
			$request->setDimensions($dimensions);
			$request->setMetricAggregations('TOTAL');
			$request->setKeepEmptyRows(true);
			$request->setOrderBys($ordering);
			$request->setLimit(20);
			
			$response	= $service->properties->runReport('properties/'.$profile, $request);
			
			$rows = array();
			foreach ($response->getRows() as $row) {
				$values = array();
				
				if (isset($row->getDimensionValues()[0])) {
					foreach ($row->getDimensionValues() as $item) {
						$values[] = $item->getValue();
					}
				}

				if (isset($row->getMetricValues()[0])) {
					foreach ($row->getMetricValues() as $item) {
						$values[] = $item->getValue();
					}
				}
				
				$rows[] = $values;
			}
			
			return $rows;
		}
	}
	
	public static function sourcesChart() {
		$input	 = Factory::getApplication()->input;
		$profile = $input->getString('profile','');
		$start	 = $input->getString('start','30daysAgo');
		$end	 = $input->getString('end','yesterday');
		
		if (substr($profile, 0, 4) == 'ga3:') {
			$profile = substr_replace($profile, '', 0, 4);
			$service = new Google\Service\Analytics(self::$client);
		
			$directvisits = $service->data_ga->get('ga:'.$profile, $start, $end, 'ga:sessions', array('dimensions' => 'ga:medium','filters' => 'ga:medium==(none)'));
			$directvisitstotal = $directvisits->getTotalsForAllResults();
			
			$searchvisits = $service->data_ga->get('ga:'.$profile, $start, $end, 'ga:sessions', array('dimensions' => 'ga:medium','filters' => 'ga:medium==organic'));
			$searchvisitstotal = $searchvisits->getTotalsForAllResults();
			
			$refferingvisits = $service->data_ga->get('ga:'.$profile, $start, $end, 'ga:sessions', array('dimensions' => 'ga:medium','filters' => 'ga:medium==referral'));
			$refferingvisitstotal = $refferingvisits->getTotalsForAllResults();
			
			return array($directvisitstotal['ga:sessions'],$searchvisitstotal['ga:sessions'],$refferingvisitstotal['ga:sessions']);
		} else {
			$profile	= substr_replace($profile, '', 0, 4);
			$service	= new Google\Service\AnalyticsData(self::$client);
			$metrics 	= array('sessions');
			$dimensions	= array('sessionMedium');
			$directvisitstotal = $searchvisitstotal = $refferingvisitstotal = 0;
			
			foreach ($metrics as $i => $metric) {
				$m = new Google\Service\AnalyticsData\Metric();
				$m->setName($metric);
				$metrics[$i] = $m;
			}
			
			foreach ($dimensions as $j => $dimension) {
				$d = new Google\Service\AnalyticsData\Dimension();
				$d->setName($dimension);
				$dimensions[$j] = $d;
			}
			
			$dimensionFilterDirect = new Google\Service\AnalyticsData\Filter();
			$dimensionFilterExpressionDirect = new Google\Service\AnalyticsData\FilterExpression();
			$stringFilterDirect = new Google\Service\AnalyticsData\StringFilter();
			$dimensionFilterDirect->setFieldName('sessionMedium');
			$stringFilterDirect->setValue('(none)');
			$dimensionFilterDirect->setStringFilter($stringFilterDirect);
			$dimensionFilterExpressionDirect->setFilter($dimensionFilterDirect);
			
			$dimensionFilterSearch = new Google\Service\AnalyticsData\Filter();
			$dimensionFilterExpressionSearch = new Google\Service\AnalyticsData\FilterExpression();
			$stringFilterSearch = new Google\Service\AnalyticsData\StringFilter();
			$dimensionFilterSearch->setFieldName('sessionMedium');
			$stringFilterSearch->setValue('organic');
			$dimensionFilterSearch->setStringFilter($stringFilterSearch);
			$dimensionFilterExpressionSearch->setFilter($dimensionFilterSearch);
			
			$dimensionFilterRefferal = new Google\Service\AnalyticsData\Filter();
			$dimensionFilterExpressionRefferal = new Google\Service\AnalyticsData\FilterExpression();
			$stringFilterRefferal = new Google\Service\AnalyticsData\StringFilter();
			$dimensionFilterRefferal->setFieldName('sessionMedium');
			$stringFilterRefferal->setValue('referral');
			$dimensionFilterRefferal->setStringFilter($stringFilterRefferal);
			$dimensionFilterExpressionRefferal->setFilter($dimensionFilterRefferal);
			
			$dateRange = new Google\Service\AnalyticsData\DateRange();
			$dateRange->setStartDate($start);
			$dateRange->setEndDate($end);
			
			$request = new Google\Service\AnalyticsData\RunReportRequest();
			$request->setProperty($profile);
			$request->setDateRanges($dateRange);
			$request->setMetrics($metrics);
			$request->setDimensions($dimensions);
			$request->setMetricAggregations('TOTAL');
			$request->setKeepEmptyRows(true);
			$request->setDimensionFilter($dimensionFilterExpressionDirect);
			
			$response	= $service->properties->runReport('properties/'.$profile, $request);
			
			if (method_exists($response, 'getTotals') && isset($response->getTotals()[0]->getMetricValues()[0])){
				$directvisitstotal = $response->getTotals()[0]->getMetricValues()[0]->getValue();
			}
			
			$request->setDimensionFilter($dimensionFilterExpressionSearch);
			
			$response	= $service->properties->runReport('properties/'.$profile, $request);
			
			if (method_exists($response, 'getTotals') && isset($response->getTotals()[0]->getMetricValues()[0])){
				$searchvisitstotal = $response->getTotals()[0]->getMetricValues()[0]->getValue();
			}
			
			$request->setDimensionFilter($dimensionFilterExpressionRefferal);
			
			$response	= $service->properties->runReport('properties/'.$profile, $request);
			
			if (method_exists($response, 'getTotals') && isset($response->getTotals()[0]->getMetricValues()[0])){
				$refferingvisitstotal = $response->getTotals()[0]->getMetricValues()[0]->getValue();
			}
			
			return array($directvisitstotal, $searchvisitstotal, $refferingvisitstotal);
		}
	}
	
	public static function content() {
		$input	 = Factory::getApplication()->input;
		$profile = $input->getString('profile','');
		$start	 = $input->getString('start','30daysAgo');
		$end	 = $input->getString('end','yesterday');
		
		if (substr($profile, 0, 4) == 'ga3:') {
			$profile = substr_replace($profile, '', 0, 4);
			$service = new Google\Service\Analytics(self::$client);
		
			$content = $service->data_ga->get('ga:'.$profile, $start, $end, 'ga:sessions,ga:pageviews,ga:avgTimeOnPage,ga:bounceRate', array('dimensions' => 'ga:pagePath','sort' => '-ga:pageviews', 'max-results' => 20));
			return $content->getRows();
		} else {
			$profile	= substr_replace($profile, '', 0, 4);
			$service	= new Google\Service\AnalyticsData(self::$client);
			$metrics 	= array('sessions', 'screenPageViews', 'userEngagementDuration', 'bounceRate');
			$dimensions	= array('pagePathPlusQueryString');
			
			foreach ($metrics as $i => $metric) {
				$m = new Google\Service\AnalyticsData\Metric();
				$m->setName($metric);
				$metrics[$i] = $m;
			}
			
			foreach ($dimensions as $j => $dimension) {
				$d = new Google\Service\AnalyticsData\Dimension();
				$d->setName($dimension);
				$dimensions[$j] = $d;
			}
			
			$ordering = new Google\Service\AnalyticsData\OrderBy();
			$metricOrderBy = new Google\Service\AnalyticsData\MetricOrderBy();
			$metricOrderBy->setMetricName('screenPageViews');
			$ordering->setMetric($metricOrderBy);
			$ordering->setDesc(true);
			
			$dateRange = new Google\Service\AnalyticsData\DateRange();
			$dateRange->setStartDate($start);
			$dateRange->setEndDate($end);
			
			$request = new Google\Service\AnalyticsData\RunReportRequest();
			$request->setProperty($profile);
			$request->setDateRanges($dateRange);
			$request->setMetrics($metrics);
			$request->setDimensions($dimensions);
			$request->setMetricAggregations('TOTAL');
			$request->setKeepEmptyRows(true);
			$request->setOrderBys($ordering);
			$request->setLimit(20);
			
			$response	= $service->properties->runReport('properties/'.$profile, $request);
			
			$rows = array();
			foreach ($response->getRows() as $row) {
				$values = array();
				
				if (isset($row->getDimensionValues()[0])) {
					foreach ($row->getDimensionValues() as $item) {
						$values[] = $item->getValue();
					}
				}

				if (isset($row->getMetricValues()[0])) {
					foreach ($row->getMetricValues() as $item) {
						$values[] = $item->getValue();
					}
				}
				
				$rows[] = $values;
			}
			
			return $rows;
		}
	}
	
	protected static function getCA() {
		$verify = false;
		
		if (file_exists(JPATH_SITE . '/libraries/vendor/composer/ca-bundle/res/cacert.pem')) {
			$verify = JPATH_SITE . '/libraries/vendor/composer/ca-bundle/res/cacert.pem';
		} else if (file_exists(JPATH_SITE . '/libraries/src/Http/Transport/cacert.pem')) {
			$verify = JPATH_SITE . '/libraries/src/Http/Transport/cacert.pem';
		} else if (file_exists(JPATH_SITE . '/libraries/joomla/http/transport/cacert.pem')) {
			$verify = JPATH_SITE . '/libraries/joomla/http/transport/cacert.pem';
		}
		
		return $verify;
	}
	
	protected static function getKey() {
		$input	 = Factory::getApplication()->input;
		$profile = $input->getString('profile','');
		$start	 = $input->getString('start','30daysAgo');
		$end	 = $input->getString('end','yesterday');
		
		return md5($profile.$start.$end);
	}
}