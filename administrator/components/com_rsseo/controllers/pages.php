<?php
/**
* @package RSSeo!
* @copyright (C) 2016 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Session\Session;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;

class rsseoControllerPages extends AdminController
{
	protected $text_prefix = 'COM_RSSEO_PAGES';
	
	/**
	 * Constructor.
	 *
	 * @param	array	$config	An optional associative array of configuration settings.

	 * @return	rsseoControllerPages
	 * @see		JController
	 * @since	1.6
	 */
	public function __construct($config = array()) {
		parent::__construct($config);
		
		$this->registerTask('removesitemap',	'addsitemap');
	}
	
	/**
	 *	Method to include or exculde pages from the sitemap
	 *
	 * @return	void
	 */
	public function addsitemap() {
		// Check for request forgeries
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$ids    = Factory::getApplication()->input->get('cid', array(), 'array');
		$values = array('addsitemap' => 1, 'removesitemap' => 0);
		$task   = $this->getTask();
		$value  = ArrayHelper::getValue($values, $task, 0, 'int');

		if (empty($ids)) {
			throw new Exception(Text::_('JERROR_NO_ITEMS_SELECTED'), 500);
		} else {
			// Get the model.
			$model = $this->getModel();

			// Publish the items.
			if (!$model->addsitemap($ids, $value)) {
				$this->setMessage($model->getError(),'error');
			}
		}
		
		$this->setRedirect('index.php?option=com_rsseo&view=pages');
	}
	
	/**
	 *	Method to remove all pages
	 *
	 * @return	void
	 */
	public function removeall() {
		// Check for request forgeries
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));
		
		// Get the model.
		$model = $this->getModel();

		// Publish the items.
		if (!$model->removeall()) {
			$this->setMessage($model->getError(),'error');
		} else {
			$this->setMessage(Text::_('COM_RSSEO_ALL_PAGES_DELETED'));
		}
		
		$this->setRedirect('index.php?option=com_rsseo&view=pages');
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
	public function getModel($name = 'Page', $prefix = 'rsseoModel', $config = array('ignore_request' => true)) {
		return parent::getModel($name, $prefix, $config);
	}
	
	/**
	 * Method to run batch operations.
	 *
	 * @param   object  $model  The model.
	 * @return  boolean   True if successful, false otherwise and internal error is set.
	 * @since   1.6
	 */
	public function batch() {
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		// Set the model
		$model	= $this->getModel();
		$pks    = Factory::getApplication()->input->get('cid', array(), 'array');
		
		if (!$model->batchProcess($pks)) {
			throw new Exception($model->getError(), 500);
		} else {
			Factory::getApplication()->enqueueMessage(Text::_('COM_RSSEO_BATCH_COMPLETED'));
		}
		
		// Preset the redirect
		$this->setRedirect('index.php?option=com_rsseo&view=pages');
	}
	
	public function simple() {
		// Check for request forgeries
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));
		
		Factory::getSession()->set('com_rsseo.pages.simple',true);
		
		$this->setRedirect('index.php?option=com_rsseo&view=pages');
	}
	
	public function standard() {
		// Check for request forgeries
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));
		
		Factory::getSession()->set('com_rsseo.pages.simple',false);
		
		$this->setRedirect('index.php?option=com_rsseo&view=pages');
	}
	
	public function ajax() {
		// Get the model.
		$model = $this->getModel();
		
		$model->ajax();
		
		die();
	}
}