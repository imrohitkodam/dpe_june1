<?php
/**
* @package RSSeo!
* @copyright (C) 2016 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Table\Table;
use Joomla\Registry\Registry;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Factory;

class rsseoModelPage extends AdminModel
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
	public function getTable($type = 'Page', $prefix = 'rsseoTable', $config = array()) {
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
		$db		= Factory::getDbo();
		$query	= $db->getQuery(true);
		$config = rsseoHelper::getConfig();
		
		if ($item = parent::getItem($pk)) {
			$item->url = $item->url ? html_entity_decode($item->url,ENT_COMPAT,'UTF-8') : '';
			$item->url = str_replace('&apos;',"'",$item->url);
			
			// Convert the robots field to an array.
			try {
				if ($item->robots) {
					$registry = new Registry;
					$registry->loadString($item->robots);
					$item->robots = $registry->toArray();
				} else {
					$item->robots = array();
				}
			} catch (Exception $e) {
				$item->robots = array();
			}
			
			// Convert the custom metadata field to an array.
			try {
				if ($item->custom) {
					$registry = new Registry;
					$registry->loadString($item->custom);
					$item->custom = $registry->toArray();
				} else {
					$item->custom = array();
				}
			} catch (Exception $e) {
				$item->custom = array();
			}
			
			// Get density params.
			try {
				if ($item->densityparams) {
					$registry = new Registry;
					$registry->loadString($item->densityparams);
					$item->densityparams = $registry->toArray();
				} else {
					$item->densityparams = array();
				}
			} catch (Exception $e) {
				$item->densityparams = array();
			}
			
			// Get images without alt attribure
			try {
				if ($item->imagesnoalt) {
					$registry = new Registry;
					$registry->loadString($item->imagesnoalt);
					$item->imagesnoalt = $registry->toArray();
				} else {
					$item->imagesnoalt = array();
				}
			} catch (Exception $e) {			
				$item->imagesnoalt = array();
			}
			
			// Get images without width and height attribure
			try {
				if ($item->imagesnowh) {
					$registry = new Registry;
					$registry->loadString($item->imagesnowh);
					$item->imagesnowh = $registry->toArray();
				} else {
					$item->imagesnowh = array();
				}
			} catch (Exception $e) {
				$item->imagesnowh = array();
			}
			
			switch($item->grade) {
				case ($item->grade >= 0 && $item->grade < 33): 
					$item->color = 'red'; 
				break;
				
				case ($item->grade >= 33 && $item->grade < 66):
					$item->color = 'orange'; 
				break;
				
				case -1:
					$item->color = '';
				break;
				
				default:
					$item->color = 'green'; 
				break;
			}
			
			if ($config->crawler_title_duplicate) {
				$query->clear()
					->select('COUNT(id)')
					->from($db->qn('#__rsseo_pages'))
					->where($db->qn('title').' = '.$db->q($item->title))
					->where($db->qn('published').' = 1');
				$db->setQuery($query);
				$item->params['duplicate_title'] = $db->loadResult();
			}
			
			if ($config->crawler_description_duplicate) {
				$query->clear()
					->select('COUNT(id)')
					->from($db->qn('#__rsseo_pages'))
					->where($db->qn('description').' = '.$db->q($item->description))
					->where($db->qn('published').' = 1');
				$db->setQuery($query);
				$item->params['duplicate_desc'] = $db->loadResult();
			}
		}
		
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
		$jinput = Factory::getApplication()->input;
		
		// Get the form.
		$form = $this->loadForm('com_rsseo.page', 'page', array('control' => 'jform', 'load_data' => $loadData));
		if (empty($form))
			return false;
		
		if ($jinput->get('id')) {
			$form->setFieldAttribute('url', 'readonly', 'true');
			$form->setFieldAttribute('level', 'readonly', 'true');
			
			if ($jinput->get('id') == 1) {
				$form->setFieldAttribute('url', 'required', 'false');
			}
			
		} else {
			$form->setValue('frequency', null, 'weekly');
			$form->setValue('priority', null, '0.5');
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
		$data = Factory::getApplication()->getUserState('com_rsseo.edit.page.data', array());

		if (empty($data))
			$data = $this->getItem();

		return $data;
	}
	
	/**
	 * Method to toggle the "in sitemap" setting of pages.
	 *
	 * @param	array	The ids of the items to toggle.
	 * @param	int		The value to toggle to.
	 *
	 * @return	boolean	True on success.
	 */
	public function addsitemap($pks, $value = 0) {
		// Sanitize the ids.
		$pks = (array) $pks;
		$pks = array_map('intval', $pks);

		if (empty($pks)) {
			$this->setError(Text::_('JERROR_NO_ITEMS_SELECTED'));
			return false;
		}

		try {
			$db = $this->getDbo();
			$query = $db->getQuery(true);

			$query->update($db->qn('#__rsseo_pages'))
				->set($db->qn('insitemap').' = '.(int) $value)
				->where($db->qn('id').' IN ('.implode(',',$pks).')');
			$db->setQuery($query);
			$db->execute();
		} catch (Exception $e) {
			$this->setError($e->getMessage());
			return false;
		}

		return true;
	}
	
	
	/**
	 * Method to remove all pages.
	 *
	 *
	 * @return	void.
	 */
	public function removeall() {
		try {
			$db		= Factory::getDBO();
			$query	= $db->getQuery(true);
			
			// Truncate table
			$db->truncateTable('#__rsseo_pages');
			$db->truncateTable('#__rsseo_broken_links');
			$db->truncateTable('#__rsseo_page_images');
			
			$query->insert($db->qn('#__rsseo_pages'))
				->set($db->qn('id').' = 1')
				->set($db->qn('url').' = '.$db->q(''))
				->set($db->qn('title').' = '.$db->q(''))
				->set($db->qn('keywords').' = '.$db->q(''))
				->set($db->qn('keywordsdensity').' = '.$db->q(''))
				->set($db->qn('description').' = '.$db->q(''))
				->set($db->qn('params').' = '.$db->q(''))
				->set($db->qn('densityparams').' = '.$db->q(''))
				->set($db->qn('imagesnoalt').' = '.$db->q(''))
				->set($db->qn('imagesnowh').' = '.$db->q(''))
				->set($db->qn('custom').' = '.$db->q(''))
				->set($db->qn('level').' = 0')
				->set($db->qn('grade').' = '.$db->q('0.00'))
				->set($db->qn('published').' = 1')
				->set($db->qn('date').' = '.$db->q(Factory::getDate()->toSql()));
			$db->setQuery($query);
			$db->execute();
		} catch (Exception $e) {
			$this->setError($e->getMessage());
			return false;
		}
		
		return true;
	}
	
	/**
	 * Method to save the form data.
	 *
	 * @param	array	The form data.
	 *
	 * @return	boolean	True on success.
	 * @since	1.6
	 */
	public function save($data) {
		// Initialise variables;
		$table = $this->getTable();
		$pk = (!empty($data['id'])) ? $data['id'] : (int) $this->getState($this->getName() . '.id');
		$isNew = true;

		// Load the row if saving an existing tag.
		if ($pk > 0) {
			$table->load($pk);
			$isNew = false;
		}

		// Bind the data.
		if (!$table->bind($data)) {
			$this->setError($table->getError());
			return false;
		}

		// Check the data.
		if (!$table->check()) {
			if (Factory::getApplication()->input->getInt('ajax',0)) {
				header("Content-Type: application/json");
				echo json_encode(array('error' => $table->getError()));
				die;
			}
			
			$this->setError($table->getError());
			return false;
		}
		
		if ($isNew) {
			$table->modified = 0;
		}

		// Store the data.
		if (!$table->store()) {
			$this->setError($table->getError());
			return false;
		}
		
		$this->setState($this->getName() . '.id', $table->id);
		
		// After store page
		$crawler_type = rsseoHelper::getConfig('crawler_type');
		if ($crawler_type == 'loopback') {
			require_once JPATH_ADMINISTRATOR. '/components/com_rsseo/helpers/crawler.php';
			$crawler = crawlerHelper::getInstance(0, $table->id);
			$crawler->crawl();
		}
		
		return true;
	}
	
	public function getDetails() {
		require_once JPATH_SITE.'/administrator/components/com_rsseo/helpers/class.webpagesize.php';
		$item = $this->getItem();
		
		set_time_limit(100);
		$class = new WebpageSize(Uri::root().$item->url);
		$pages = $class->getPages();
		$total = $class->getTotal();
		
		return array('pages' => $pages, 'total' => $total);
	}
	
	public function getBroken() {
		$db		= Factory::getDbo();
		$query	= $db->getQuery(true);
		$id		= Factory::getApplication()->input->getInt('id',0);
		
		$query->select('*')
			->from($db->qn('#__rsseo_broken_links'))
			->where($db->qn('published').' = 1')
			->where($db->qn('pid').' = '.$id);
		
		$db->setQuery($query);
		return $db->loadObjectList();
	}
	
	public function getMetaTypes() {
		$options   = array();
		$options[] = HTMLHelper::_('select.option', 'name', Text::_('COM_RSSEO_METADATA_TYPE_NAME'));
		$options[] = HTMLHelper::_('select.option', 'property', Text::_('COM_RSSEO_METADATA_TYPE_PROPERTY'));
		
		return $options;
	}
	
	public function ajax() {
		$db			= Factory::getDbo();
		$query		= $db->getQuery(true);
		$config		= rsseoHelper::getConfig();
		$app		= Factory::getApplication();
		$input		= $app->input;
		$title		= $input->get('title', array(), 'array');
		$keywords	= $input->get('keywords', array(), 'array');
		$description= $input->get('description', array(), 'array');
		
		if ($title) {
			foreach ($title as $id => $value) {
				$query->clear()->update($db->qn('#__rsseo_pages'))->set($db->qn('title').' = '.$db->q($value))->set($db->qn('modified').' = '.$db->q(1))->where($db->qn('id').' = '.$db->q($id));
				$db->setQuery($query);
				$db->execute();
			}
		}
		
		if ($keywords) {
			foreach ($keywords as $id => $value) {
				$query->clear()->update($db->qn('#__rsseo_pages'))->set($db->qn('keywords').' = '.$db->q($value))->set($db->qn('modified').' = '.$db->q(1))->where($db->qn('id').' = '.$db->q($id));
				$db->setQuery($query);
				$db->execute();
			}
		}
		
		if ($description) {
			foreach ($description as $id => $value) {
				$query->clear()->update($db->qn('#__rsseo_pages'))->set($db->qn('description').' = '.$db->q($value))->set($db->qn('modified').' = '.$db->q(1))->where($db->qn('id').' = '.$db->q($id));
				$db->setQuery($query);
				$db->execute();
			}
		}
		
		if ($config->crawler_type == 'loopback') {
			header("Content-Type: application/json");
			
			require_once JPATH_ADMINISTRATOR. '/components/com_rsseo/helpers/crawler.php';
			$crawler = crawlerHelper::getInstance(0, $id);
			
			echo $crawler->crawl();
			$app->close();
		}
	}
	
	public function batchProcess($pks) {
		// Sanitize the ids.
		$pks = (array) $pks;
		$pks = array_map('intval', $pks);
		
		$batch = Factory::getApplication()->input->get('batch',array(),'array');
		
		if (empty($pks)) {
			$this->setError(Text::_('JERROR_NO_ITEMS_SELECTED'));
			return false;
		}
		
		try {
			$db		 = $this->getDbo();
			$query	 = $db->getQuery(true);
			
			$query->update($db->qn('#__rsseo_pages'))
				->where($db->qn('id').' IN ('.implode(',', $pks).')');
			
			if (!empty($batch['keywords'])) $query->set($db->qn('keywords').' = '.$db->q($batch['keywords']));
			if (!empty($batch['description'])) $query->set($db->qn('description').' = '.$db->q($batch['description']));
			if (!empty($batch['canonical'])) $query->set($db->qn('canonical').' = '.$db->q($batch['canonical']));
			if (!empty($batch['customhead'])) $query->set($db->qn('customhead').' = '.$db->q($batch['customhead']));
			if (!empty($batch['scripts'])) $query->set($db->qn('scripts').' = '.$db->q($batch['scripts']));
			if (!empty($batch['css'])) $query->set($db->qn('css').' = '.$db->q($batch['css']));
			if ($batch['frequency'] != '') $query->set($db->qn('frequency').' = '.$db->q($batch['frequency']));
			if ($batch['priority'] != '') $query->set($db->qn('priority').' = '.$db->q($batch['priority']));
			if ($batch['published'] != '-') $query->set($db->qn('published').' = '.$db->q($batch['published']));
			if ($batch['insitemap'] != '-') $query->set($db->qn('insitemap').' = '.$db->q($batch['insitemap']));
			
			if (isset($batch['robots']) && is_array($batch['robots'])) {
				$registry = new Registry;
				$registry->loadArray($batch['robots']);
				$query->set($db->qn('robots').' = '.$db->q((string) $registry));
			}
			
			$db->setQuery($query);
			$db->execute();
			
			return true;
		} catch (Exception $e) {
			$this->setError($e->getMessage());
			return false;
		}
	}
}