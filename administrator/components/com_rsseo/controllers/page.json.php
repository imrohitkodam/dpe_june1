<?php
/**
* @package RSSeo!
* @copyright (C) 2016 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Factory;

class rsseoControllerPage extends FormController
{
	/**
	 * Class constructor.
	 *
	 * @param   array  $config  A named array of configuration variables.
	 *
	 * @since	1.6
	 */
	public function __construct() {
		parent::__construct();
	}
	
	public function postSaveHook(JModelLegacy $model, $validData = array()) {
		$ajax = Factory::getApplication()->input->getInt('ajax',0);
		
		if ($ajax) {
			if (empty($validData['id'])) {
				$validData['id'] = $model->getState('page.id');
			}
			
			$this->setRedirect(null);
			
			header("Content-Type: application/json");
			echo json_encode($validData);
			die;
		}
	}
	
	public function refresh() {
		$jinput = Factory::getApplication()->input->get('jform',array(),'array');
		require_once JPATH_ADMINISTRATOR. '/components/com_rsseo/helpers/crawler.php';
		$crawler = crawlerHelper::getInstance(0, (int) $jinput['id']);
		$crawler->crawl();
		
		return $this->setRedirect('index.php?option=com_rsseo&view=page&layout=edit&id='.$jinput['id']);
	}
	
	public function check() {
		$id		= Factory::getApplication()->input->getInt('id',0);
		$pageId	= Factory::getApplication()->input->getInt('pageId',0);
		
		echo rsseoHelper::checkBroken($id, $pageId);
		Factory::getApplication()->close();
	}
	
	public function broken() {
		require_once JPATH_ADMINISTRATOR. '/components/com_rsseo/helpers/ajaxcrawler.php';
		
		ajaxCrawlerHelper::broken();
		
		Factory::getApplication()->close();
	}
	
	public function links() {
		header("Content-Type: application/json");
		
		$app	= Factory::getApplication();
		$config = rsseoHelper::getConfig();
		$id		= $app->input->getInt('id',0);
		
		if ($config->crawler_type == 'ajax') {
			require_once JPATH_ADMINISTRATOR. '/components/com_rsseo/helpers/ajaxcrawler.php';
			$crawler = ajaxCrawlerHelper::getInstance(0, $id);
		} else {
			require_once JPATH_ADMINISTRATOR. '/components/com_rsseo/helpers/crawler.php';
			$crawler = crawlerHelper::getInstance(0, $id);
		}
		
		echo $crawler->links();
		$app->close();
	}
}