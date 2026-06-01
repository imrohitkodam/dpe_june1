<?php
/**
* @package RSSeo!
* @copyright (C) 2016 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Factory;

class rsseoModelRedirect extends AdminModel
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
	public function getTable($type = 'Redirect', $prefix = 'rsseoTable', $config = array()) {
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
		$form = $this->loadForm('com_rsseo.redirect', 'redirect', array('control' => 'jform', 'load_data' => $loadData));
		if (empty($form))
			return false;
		
		if (!Factory::getApplication()->input->get('id')) {
			$form->setValue('to',null,'http://');
		}
		
		if ($eid = Factory::getApplication()->input->getString('eid','')) {
			$form->setFieldAttribute('from','type', 'textarea');
			$ids = explode(',',$eid);
			$ids = array_map('intval', $ids);
			
			$db		= Factory::getDbo();
			$query	= $db->getQuery(true);
			
			$query->select($db->qn('url'))->from($db->qn('#__rsseo_error_links'))->where($db->qn('id').' IN ('.implode(',',$ids).')');
			$db->setQuery($query);
			if ($urls = $db->loadColumn()) {
				foreach ($urls as $i => $url) {
					$urls[$i] = str_replace(Uri::root(), '' , $url);
				}
				
				$form->setValue('from', null, implode("\n", $urls));
			}
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
		$data = Factory::getApplication()->getUserState('com_rsseo.edit.redirect.data', array());

		if (empty($data))
			$data = $this->getItem();
			
		return $data;
	}
	
	public function getReferrers() {
		$db		= Factory::getDbo();
		$query	= $db->getQuery(true);
		$id		= Factory::getApplication()->input->get('id',0);
		
		$query->select('*')
			->from($db->qn('#__rsseo_redirects_referer'))
			->where($db->qn('rid').' = '.$id)
			->order($db->qn('date').' DESC');
		$db->setQuery($query);
		return $db->loadObjectList();
	}
	
	public function savemultiple() {
		$db			= Factory::getDbo();
		$query		= $db->getQuery(true);
		$data		= Factory::getApplication()->input->get('jform',array(),'array');
		$from		= $data['from'];
		$to			= $data['to'];
		$type		= $data['type'];
		$published	= $data['published'];
		$from		= str_replace("\r",'',$from);
		$from		= explode("\n",$from);
		
		if ($from) {
			foreach ($from as $line) {
				$query->clear()
					->select($db->qn('id'))
					->from($db->qn('#__rsseo_redirects'))
					->where($db->qn('from').' = '.$db->q($line));
				$db->setQuery($query);
				if (!$db->loadResult()) {
					$query->clear()
						->insert($db->qn('#__rsseo_redirects'))
						->set($db->qn('from').' = '.$db->q($line))
						->set($db->qn('to').' = '.$db->q($to))
						->set($db->qn('type').' = '.$db->q($type))
						->set($db->qn('published').' = '.$db->q($published));
					$db->setQuery($query);
					$db->execute();
				}
			}
		}
	}

	public function referrals() {
		$db		= Factory::getDbo();
		$query	= $db->getQuery(true);
		$data	= Factory::getApplication()->input->get('jform',array(),'array');

		$query->clear()
			->delete($db->qn('#__rsseo_redirects_referer'))
			->where($db->qn('rid').' = '.$db->q($data['id']));
		$db->setQuery($query);
		$db->execute();
	}
}