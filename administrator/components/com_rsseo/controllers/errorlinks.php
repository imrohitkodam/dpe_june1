<?php
/**
* @package RSSeo!
* @copyright (C) 2016 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory;

class rsseoControllerErrorlinks extends BaseController
{
	/**
	 * Constructor.
	 *
	 * @param	array	$config	An optional associative array of configuration settings.

	 * @return	rsseoControllerErrorlinks
	 * @see		JController
	 * @since	1.6
	 */
	public function __construct($config = array()) {
		parent::__construct($config);
	}
	
	public function delete() {
		// Check for request forgeries
		Session::checkToken() or die(Text::_('JINVALID_TOKEN'));
		
		// Get items to remove from the request.
		$cid = Factory::getApplication()->input->get('cid', array(), 'array');
		
		$cid = array_map('intval', $cid);
		
		// Get the model.
		$model = $this->getModel('Errorlinks');
		
		$model->delete($cid);
		
		$this->setMessage(Text::_('COM_RSSEO_ERROR_LINKS_REMOVED'));
		$this->setRedirect(Route::_('index.php?option=com_rsseo&view=errorlinks',false));
	}
	
	public function createRedirect() {
		// Check for request forgeries
		Session::checkToken() or die(Text::_('JINVALID_TOKEN'));
		
		// Get items to remove from the request.
		$cid = Factory::getApplication()->input->get('cid', array(), 'array');
		
		$cid = array_map('intval', $cid);
		
		if ($cid) {
			return $this->setRedirect(Route::_('index.php?option=com_rsseo&view=redirect&layout=edit&eid='.implode(',',$cid) , false));
		}
		
		return $this->setRedirect(Route::_('index.php?option=com_rsseo&view=errorlinks', false));
	}

	public function referrals() {
		// Get the model.
		$model = $this->getModel('Errorlinks');

		$id = Factory::getApplication()->input->getInt('id', 0);

		$model->referrals($id);
	}
}