<?php
/**
* @package RSSeo!
* @copyright (C) 2016 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\MVC\Model\AdminModel;
use \Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Factory;

class rsseoModelSitemap extends AdminModel
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
		$form = $this->loadForm('com_rsseo.sitemap', 'sitemap', array('control' => 'jform', 'load_data' => $loadData));
		if (empty($form))
			return false;
		
		$type = rsseoHelper::getConfig('sitemapmodified_date');
		$type = isset($type) ? $type : 1;
		$images = rsseoHelper::getConfig('sitemapimages');
		$images = isset($images) ? $images : 0;
		$modified = rsseoHelper::getConfig('sitemapmodified');
		$modified = isset($modified) && !empty($modified) ? $modified : HTMLHelper::_('date','now','Y-m-d');
		$root = rsseoHelper::getConfig('sitemapwebsite');
		$root = isset($root) ? $root : Uri::root();
		
		$form->setValue('modified_date', null, $type);
		$form->setValue('modified', null, $modified);
		$form->setValue('website',null, $root);
		$form->setValue('images',null, $images);
		
		return $form;
	}
	
	/**
	 *	Method to get the percentage of processed pages
	*/
	public function getPercent() {
		$db		= Factory::getDBO();
		$query	= $db->getQuery(true);
		$config = rsseoHelper::getConfig();
		
		$query->clear()
			->select('COUNT('.$db->qn('id').')')
			->from($db->qn('#__rsseo_pages'))
			->where($db->qn('insitemap').' = 1')
			->where($db->qn('published').' != -1')
			->where($db->qn('canonical').' = '.$db->q(''));
		
		if ($config->exclude_noindex) {
			$query->where($db->qn('robots').' NOT LIKE '.$db->q('%"index":"0"%'));
		}
		
		if ($config->exclude_autocrawled) {
			$query->where($db->qn('level').' <> '.$db->q('127'));
		}
		
		$db->setQuery($query);
		$total = (int) $db->loadResult();
		
		$query->clear()
			->select('COUNT('.$db->qn('id').')')
			->from($db->qn('#__rsseo_pages'))
			->where($db->qn('sitemap').' = 1')
			->where($db->qn('insitemap').' = 1')
			->where($db->qn('published').' != -1')
			->where($db->qn('canonical').' = '.$db->q(''));
		
		if ($config->exclude_noindex) {
			$query->where($db->qn('robots').' NOT LIKE '.$db->q('%"index":"0"%'));
		}
		
		if ($config->exclude_autocrawled) {
			$query->where($db->qn('level').' <> '.$db->q('127'));
		}
		
		$db->setQuery($query);
		$processed = (int) $db->loadResult();
		
		return $total > 0 ? ceil($processed * 100 / $total) : 0;
	}
}