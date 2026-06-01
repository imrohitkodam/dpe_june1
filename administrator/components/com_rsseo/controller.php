<?php
/**
* @package RSSeo!
* @copyright (C) 2016 www.rsjoomla.com
* @license GPL, http://www.gnu.org/licenses/gpl-2.0.html
*/

// No direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Cache\Cache;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Path;
use Joomla\CMS\Factory;

class RSSeoController extends BaseController
{
	public function __construct() {
		parent::__construct();
		Table::addIncludePath(JPATH_ADMINISTRATOR.'/components/com_rsseo/tables');
	}

	/**
	 * Method to display a view.
	 *
	 * @param	boolean			If true, the view output will be cached
	 * @param	array			An array of safe url parameters and their variable types, for valid values see {@link JFilterInput::clean()}.
	 *
	 * @return	JController		This object to support chaining.
	 * @since	1.5
	 */
	public function display($cachable = false, $urlparams = false) {
		rsseoHelper::addSubmenu(Factory::getApplication()->input->getCmd('view'));
		
		parent::display();
		return $this;
	}
	
	/**
	 *	Method to display the RSSeo! Dashboard
	 *
	 * @return void
	 */
	public function main() {
		return $this->setRedirect('index.php?option=com_rsseo');
	}
	
	/**
	 *	Method to check a page loading time and size
	 *
	 * @return string
	 */
	public function pagecheck() {
		require_once JPATH_SITE.'/administrator/components/com_rsseo/helpers/class.webpagesize.php';
		$db		= Factory::getDBO();
		$query	= $db->getQuery(true);
		$id		= Factory::getApplication()->input->getInt('id',0);
		
		$query->clear()
			->select($db->qn('url'))
			->from($db->qn('#__rsseo_pages'))
			->where($db->qn('id').' = '.$id);
		$db->setQuery($query);
		$url = $db->loadResult();
		
		set_time_limit(100);
		$size = new WebpageSize(Uri::root().$url);
		$page_size = $size->sizeofpage();
		$time_total = $size->getTime();
		$page_load = number_format($time_total,3);
	
		echo Text::sprintf('COM_RSSEO_PAGE_SIZE_DESCR',$page_size,$id)."RSDELIMITER".Text::sprintf('COM_RSSEO_PAGE_TIME_DESCR',$page_load);
		Factory::getApplication()->close();
	}
	
	/**
	 *	Method to search for pages
	 *
	 * @return string
	 */
	public function search() {
		$db		= Factory::getDBO();
		$query	= $db->getQuery(true);
		$search	= Factory::getApplication()->input->getString('search');
		$type	= Factory::getApplication()->input->getString('type','');
		$html	= array();
		
		$query->select($db->qn('title'))->select($db->qn('url'))
			->from($db->qn('#__rsseo_pages'))
			->where('('.$db->qn('url').' LIKE '.$db->q('%'.$search.'%').' OR '.$db->qn('title').' LIKE '.$db->q('%'.$search.'%').')')
			->where($db->qn('published').' = 1');
		$db->setQuery($query,0,10);
		$results = $db->loadObjectList();
		
		$add 	= $type == 'redirect' ? 'RSSeo.addRedirect' : 'RSSeo.addCanonical';
		$close 	= $type == 'redirect' ? 'RSSeo.closeRedirectSearch();' : 'RSSeo.closeCanonicalSearch();';
		
		$html[] = '<li class="rss_close"><a href="javascript:void(0);" onclick="'.$close.'">'.Text::_('COM_RSSEO_GLOBAL_CLOSE').'</a></li>';
		
		if (!empty($results)) {
			foreach ($results as $result) {
				$url = $type == 'redirect' ? $result->url : Uri::root().$result->url;
				$html[] = '<li><a href="javascript:void(0);" onclick="'.$add.'(\''.$url.'\')">'.($result->title ? '<strong>'.$result->title.'</strong><br />' : '').$url.'</a></li>';
			}
		} else $html[] = '<li>'.Text::_('COM_RSSEO_NO_RESULTS').'</li>';
		
		echo implode("\n",$html);
		Factory::getApplication()->close();
	}
	
	/**
	 *	Method to check for connectivity
	 *
	 * @return void
	 */
	public function connectivity() {
		$functions	= array('cURL','file_get_contents','fopen','fsockopen');
		$errors		= rsseoHelper::fopen(Uri::root(), 1, true);
		
		if (count($errors) == 4) {
			$msg = Text::_('COM_RSSEO_CONNECTIVITY_ERROR');
		} elseif (empty($errors)) {
			$msg = Text::_('COM_RSSEO_CONNECTIVITY_OK');
		} else {
			$ok = array_diff($functions,$errors);
			$msg = Text::sprintf('COM_RSSEO_CONNECTIVITY_MESSAGE', implode(',',$errors), implode(',',$ok));
		}
		
		return $this->setRedirect('index.php?option=com_rsseo', $msg);
	}
	
	/**
	 *	Method to crawl a page
	 *
	 * @return void
	 */
	public function crawl() {
		$app		= Factory::getApplication();
		$initialize = $app->input->getInt('init');
		$id			= $app->input->getInt('id');
		$original	= $app->input->getInt('original',0);
		
		require_once JPATH_ADMINISTRATOR. '/components/com_rsseo/helpers/crawler.php';
		$crawler = crawlerHelper::getInstance($initialize, $id, $original);
		echo $crawler->crawl();
		$app->close();
	}
	
	/**
	 *	Method to crawl a page using AJAX
	 *
	 * @return json
	 */
	public function ajaxcrawl() {
		$app		= Factory::getApplication();
		$initialize = $app->input->getInt('init',0);
		$id			= $app->input->getInt('id',0);
		$original	= $app->input->getInt('original',0);
		
		header('Content-Type: application/json');
		
		require_once JPATH_ADMINISTRATOR. '/components/com_rsseo/helpers/ajaxcrawler.php';
		$crawler = ajaxCrawlerHelper::getInstance($initialize, $id, $original);
		echo $crawler->crawl();
		
		$app->close();
	}
	
	public function visitors() {
		$model = $this->getModel('statistics');
		
		$data				= array();
		$data['visitors']	= $model->getChartVisitors();
		$data['total']		= $model->getTotalVisitorsTimeframe();
		$data['all']		= $model->getTotalVisitors();
		
		header('Content-Type: application/json');
		
		echo json_encode($data);
		die();
	}
	
	public function pageviews() {
		$model = $this->getModel('statistics');
		
		$data				= array();
		$data['pageviews']	= $model->getChartPageViews();
		$data['total']		= $model->getTotalPageViewsTimeframe();
		$data['all']		= $model->getTotalPageViews();
		
		header('Content-Type: application/json');
		
		echo json_encode($data);
		die();
	}
	
	public function removeVisitors() {
		$db		= Factory::getDBO();
		$query	= $db->getQuery(true);
		$pks	= Factory::getApplication()->input->get('cid', array(), 'array');
		
		if ($pks) {
			foreach ($pks as $pk) {
				$query->clear()
					->delete($db->qn('#__rsseo_visitors'))
					->where($db->qn('session_id').' = '.$db->q($pk));
				$db->setQuery($query);
				$db->execute();
			}
		}
		
		$this->setRedirect(Route::_('index.php?option=com_rsseo&view=statistics', false), Text::_('COM_RSSEO_ITEMS_REMOVED'));
	}
	
	public function removeAllVisitors() {
		Factory::getDBO()->truncateTable('#__rsseo_visitors');
		
		$this->setRedirect(Route::_('index.php?option=com_rsseo&view=statistics', false), Text::_('COM_RSSEO_ALL_VISITORS_REMOVED'));
	}
	
	public function createrobots() {
		$file = JPATH_SITE.'/robots.txt';
		
		if (file_exists($file)) {
			return $this->setRedirect(Route::_('index.php?option=com_rsseo&view=robots', false), Text::_('COM_RSSEO_CANNOT_CREATE_ROBOTS_ALREADY_EXISTS'));
		}
		
		$robots = '# If the Joomla site is installed within a folder such as at
# e.g. www.example.com/joomla/ the robots.txt file MUST be
# moved to the site root at e.g. www.example.com/robots.txt
# AND the joomla folder name MUST be prefixed to the disallowed
# path, e.g. the Disallow rule for the /administrator/ folder
# MUST be changed to read Disallow: /joomla/administrator/
#
# For more information about the robots.txt standard, see:
# http://www.robotstxt.org/orig.html
#
# For syntax checking, see:
# http://tool.motoricerca.info/robots-checker.phtml

User-agent: *
Disallow: /administrator/
Disallow: /bin/
Disallow: /cache/
Disallow: /cli/
Disallow: /components/
Disallow: /includes/
Disallow: /installation/
Disallow: /language/
Disallow: /layouts/
Disallow: /libraries/
Disallow: /logs/
Disallow: /modules/
Disallow: /plugins/
Disallow: /tmp/';
		
		// Attempt to write the file on disk
		if (!File::write($file, $robots)) {
			return $this->setRedirect(Route::_('index.php?option=com_rsseo&view=robots', false), Text::sprintf('COM_RSSEO_CANNOT_WRITE_ROBOTS', JPATH_SITE));
		}
		
		// Apply permissions
		if (Path::canChmod($file)) {
			$permission	= rsseoHelper::validatePermission(ComponentHelper::getParams('com_rsseo')->get('robots_permissions', 644));
			Path::setPermissions($file,'0'.$permission);
		}
		
		return $this->setRedirect(Route::_('index.php?option=com_rsseo&view=robots', false), Text::_('COM_RSSEO_ROBOTS_CREATED'));
	}
	
	public function saverobots() {
		$file	= JPATH_SITE.'/robots.txt';
		$robots	= Factory::getApplication()->input->getString('robots');
		
		if (file_exists($file) && is_writable($file)) {
			if (File::write($file, $robots)) {
				return $this->setRedirect(Route::_('index.php?option=com_rsseo&view=robots', false), Text::_('COM_RSSEO_ROBOTS_SAVED'));
			}
		}
		
		return $this->setRedirect(Route::_('index.php?option=com_rsseo&view=robots', false), Text::_('COM_RSSEO_ERROR_SAVING_ROBOTS'));
	}
	
	public function clearcache() {
		$options	= array('storage' => 'file', 'defaultgroup' => 'plg_system_rsseo', 'cachebase' => realpath(Factory::getConfig()->get('cache_path', JPATH_SITE.'/cache')));
		$jcache		= Cache::getInstance('callback', $options);
		
		$jcache->clean();
		
		return $this->setRedirect(Route::_('index.php?option=com_rsseo', false), Text::_('COM_RSSEO_CACHE_CLEARED'));
	}
}