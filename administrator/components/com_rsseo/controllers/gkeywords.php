<?php
/**
* @package RSSeo!
* @copyright (C) 2016 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

class rsseoControllerGkeywords extends AdminController
{
	protected $text_prefix = 'COM_RSSEO_GKEYWORDS';
	
	/**
	 * Constructor.
	 *
	 * @param	array	$config	An optional associative array of configuration settings.

	 * @return	rsseoControllerKeywords
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
	public function getModel($name = 'Gkeyword', $prefix = 'rsseoModel', $config = array('ignore_request' => true)) {
		return parent::getModel($name, $prefix, $config);
	}
	
	public function deletelog() {
		$model = $this->getModel();
		
		$model->deletelog();
		
		$this->setRedirect(Route::_('index.php?option=com_rsseo&view=gkeywords',false), Text::_('COM_RSSEO_LOG_WAS_CLEARED'));
	}
}