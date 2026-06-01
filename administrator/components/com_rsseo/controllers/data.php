<?php
/**
* @package RSSeo!
* @copyright (C) 2016 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;

class rsseoControllerData extends FormController
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
	
	public function save($key = NULL, $urlVar = NULL) {
		$jform	= Factory::getApplication()->input->get('jform', array(), 'array');
		$model	= $this->getModel();
		
		$model->save($jform);
		
		$this->setRedirect('index.php?option=com_rsseo&view=data', Text::_('COM_RSSEO_SD_DATA_SAVED'));
	}
}