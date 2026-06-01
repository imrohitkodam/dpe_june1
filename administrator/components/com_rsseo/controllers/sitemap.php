<?php
/**
* @package RSSeo!
* @copyright (C) 2016 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Path;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\Registry\Registry;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;

class rsseoControllerSitemap extends AdminController
{
	/**
	 * Constructor.
	 *
	 * @param	array	$config	An optional associative array of configuration settings.

	 * @return	rsseoControllerSitemap
	 * @see		JController
	 * @since	1.6
	 */
	public function __construct($config = array()) {
		parent::__construct($config);
	}
	
	/**
	 * Proxy for getModel.
	 *
	 * @param	string	$name	The name of the model.
	 * @param	string	$prefix	The prefix for the PHP class name.
	 *
	 * @return	JModel
	 * @since	1.6
	 */
	public function getModel($name = 'Sitemap', $prefix = 'rsseoModel', $config = array('ignore_request' => true)) {
		return parent::getModel($name, $prefix, $config);
	}
	
	/**
	 *	Attempt to create the sitemap.xml and ror.xml
	 *
	 */
	public function create() {
		Factory::getDocument()->setMimeEncoding('application/json');

		try {
			$type = Factory::getApplication()->input->getCmd('file');

			// Verify if it's a valid sitemap file
			if (!in_array($type, array('sitemap', 'ror'))) {
				throw new Exception(Text::sprintf('COM_RSSEO_SITEMAP_CANNOT_CREATE_WRONG_NAME', $type));
			}

			$file	= JPATH_SITE.'/'.$type.'.xml';
			$empty	= '';

			// Attempt to write the file on disk
			if (!File::write($file, $empty)) {
				throw new Exception(Text::sprintf('COM_RSSEO_SITEMAP_CANNOT_WRITE', $type, JPATH_SITE));
			}

			// Apply permissions
			if (Path::canChmod($file)) {
				$permission	= rsseoHelper::validatePermission(ComponentHelper::getParams('com_rsseo')->get('sitemap_permissions', 644));
				Path::setPermissions($file,'0'.$permission);
			}

			echo json_encode(array(
				'status'  => 1,
				'message' => Text::sprintf('COM_RSSEO_SITEMAP_CREATED_SUCCESSFULLY', $type)
			));
		} catch (Exception $e) {
			echo json_encode(array(
				'status'  => 0,
				'message' => $e->getMessage()
			));
		}

		Factory::getApplication()->close();
	}
	
	/**
	 *	Generate the XML sitemap
	 *
	 */
	public function generate() {
		require_once JPATH_ADMINISTRATOR.'/components/com_rsseo/helpers/sitemap.php';
		
		$db				= Factory::getDBO();
		$query			= $db->getQuery(true);
		$jinput 		= Factory::getApplication()->input;
		$new			= $jinput->getInt('new',0);
		$modified_date	= $jinput->getInt('modified_date',1);
		$modified		= $jinput->getCmd('modified','');
		$website		= $jinput->getString('website','');
		$images			= $jinput->getInt('images',0);
		$cron			= $jinput->getInt('cron','0');
		$config			= rsseoHelper::getConfig();
		$limit			= (int) $config->sitemap_limit;
		$limit			= $limit ? $limit : 250;

		Factory::getDocument()->setMimeEncoding('application/json');
		
		if ($config->enable_sitemap_cron && $new) {
			$component	= ComponentHelper::getComponent('com_rsseo');
			$cparams	= $component->params;
			
			if ($cparams instanceof Registry) {
				$cparams->set('enable_sitemap_cron', 0);
				$query->clear();
				$query->update($db->qn('#__extensions'));
				$query->set($db->qn('params'). ' = '.$db->q((string) $cparams));
				$query->where($db->qn('extension_id'). ' = '. $db->q($component->id));
				
				$db->setQuery($query);
				$db->execute();
			}
		}
		
		try {
			// Get a new instance of the Sitemap class
			$options = array('new' => $new, 'modified_date' => $modified_date, 'modified' => $modified, 'images' => $images, 'website' => $website);
			$sitemap = sitemapHelper::getInstance($options);

			$query->clear()
				->select($db->qn('id'))->select($db->qn('url'))->select($db->qn('sef'))->select($db->qn('title'))
				->select($db->qn('level'))->select($db->qn('priority'))->select($db->qn('frequency'))->select($db->qn('date'))
				->from($db->qn('#__rsseo_pages'))
				->where($db->qn('sitemap') . ' = 0')
				->where($db->qn('insitemap') . ' = 1')
				->where($db->qn('published') . ' != -1')
				->where($db->qn('canonical') . ' = ' . $db->q(''));
				
			if ($config->exclude_noindex) {
				$query->where($db->qn('robots').' NOT LIKE '.$db->q('%"index":"0"%'));
			}
				
			if ($config->exclude_autocrawled) {
				$query->where($db->qn('level').' <> '.$db->q('127'));
			}
			
			$query->order($db->qn('level'));
			$db->setQuery($query, 0, $limit);

			$sitemap->setHeader();

			if ($pages = $db->loadObjectList()) {
				foreach ($pages as $page) {
					$page->url = rsseoHelper::showURL($page->url, $page->sef);
					
					$sitemap->add($page);

					$query->clear()
						->update($db->qn('#__rsseo_pages'))
						->set($db->qn('sitemap') . ' = 1')
						->where($db->qn('id') . ' = ' . $db->q($page->id));
					$db->setQuery($query);
					$db->execute();
				}
			} else {
				$sitemap->close();
				
				if ($cron) {
					$component	= ComponentHelper::getComponent('com_rsseo');
					$cparams	= $component->params;
					
					if ($cparams instanceof Registry) {
						$cparams->set('enable_sitemap_cron', 1);
						$query->clear();
						$query->update($db->qn('#__extensions'));
						$query->set($db->qn('params'). ' = '.$db->q((string) $cparams));
						$query->where($db->qn('extension_id'). ' = '. $db->q($component->id));
						
						$db->setQuery($query);
						$db->execute();
					}
				}

				echo json_encode(array(
					'status' 	=> 1,
					'progress' 	=> 100,
					'finished'	=> 1,
					'message'	=> Text::_('COM_RSSEO_SITEMAP_GENERATED_SUCCESSFULLY')
				));

				Factory::getApplication()->close();
			}

			$query->clear()
				->select('COUNT(id)')
				->from($db->qn('#__rsseo_pages'))
				->where($db->qn('insitemap') . ' = 1')
				->where($db->qn('published') . ' != -1')
				->where($db->qn('canonical') . ' = ' . $db->q(''));
				
			if ($config->exclude_noindex) {
				$query->where($db->qn('robots').' NOT LIKE '.$db->q('%"index":"0"%'));
			}
			
			if ($config->exclude_autocrawled) {
				$query->where($db->qn('level').' <> '.$db->q('127'));
			}
			
			$db->setQuery($query);
			$total = (int)$db->loadResult();

			$query->clear()
				->select('COUNT(id)')
				->from($db->qn('#__rsseo_pages'))
				->where($db->qn('sitemap') . ' = 1')
				->where($db->qn('insitemap') . ' = 1')
				->where($db->qn('published') . ' != -1')
				->where($db->qn('canonical') . ' = ' . $db->q(''));
			
			if ($config->exclude_noindex) {
				$query->where($db->qn('robots').' NOT LIKE '.$db->q('%"index":"0"%'));
			}
			
			if ($config->exclude_autocrawled) {
				$query->where($db->qn('level').' <> '.$db->q('127'));
			}
			
			$db->setQuery($query);
			$processed = (int)$db->loadResult();

			echo json_encode(array(
				'status' 	=> 1,
				'finished'	=> 0,
				'cron'		=> $cron,
				'progress' 	=> ceil($processed * 100 / $total)
			));
		} catch (Exception $e) {
			echo json_encode(array(
				'status'  => 0,
				'message' => $e->getMessage()
			));
		}

		Factory::getApplication()->close();
	}
	
	/**
	 *	Create the HTML sitemap
	 *
	 */
	public function html() {
		$db			= Factory::getDBO();
		$query		= $db->getQuery(true);
		$jinput		= Factory::getApplication()->input;
		$menus		= $jinput->get('menus',array(),'array');
		$exclude	= $jinput->get('exclude',array(),'array');
		
		$component	= ComponentHelper::getComponent('com_rsseo');
		$cparams	= $component->params;
		
		if ($cparams instanceof Registry) {
			$cparams->set('sitemap_menus', base64_encode(serialize($menus)));
			$cparams->set('sitemap_excludes', base64_encode(serialize($exclude)));
			$query->clear();
			$query->update($db->qn('#__extensions'));
			$query->set($db->qn('params'). ' = '.$db->q((string) $cparams));
			$query->where($db->qn('extension_id'). ' = '. $db->q($component->id));
			
			$db->setQuery($query);
			$db->execute();
		}
		
		$this->setMessage(Text::_('COM_RSSEO_HTML_SITEMAP_CREATED'));
		return $this->setRedirect('index.php?option=com_rsseo&view=sitemap');
	}
}