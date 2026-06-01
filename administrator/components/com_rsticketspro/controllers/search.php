<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproControllerSearch extends JControllerLegacy
{
	public function cancel()
	{
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));
		
		$this->setRedirect(JRoute::_('index.php?option=com_rsticketspro&view=tickets', false));
	}
	
	public function reset()
	{
		$model = $this->getModel('tickets');
		$model->resetSearch();
		
		$this->setRedirect(JRoute::_('index.php?option=com_rsticketspro&view=tickets', false));
	}

	public function advanced()
	{
		$this->setRedirect(JRoute::_('index.php?option=com_rsticketspro&view=search', false));
	}
}