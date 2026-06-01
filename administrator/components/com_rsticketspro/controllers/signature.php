<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproControllerSignature extends JControllerForm
{
	protected $view_item = 'signature';
	protected $view_list = 'tickets';

	public function cancel($key = null)
	{
		$this->checkToken();

		$this->setRedirect(JRoute::_('index.php?option=com_rsticketspro&view=tickets', false));
	}

	public function save($key = null, $urlVar = null)
	{
		$this->checkToken();
		
		$input = JFactory::getApplication()->input;
		$data  = $input->get('jform', array(), 'array');
		$model = $this->getModel('signature');
		
		if (!$model->save($data))
		{
			$this->setMessage($model->getError(), 'error');
		}
		else
		{
			$this->setMessage(JText::_('RST_CONFIGURATION_OK'));
		}
		
		$this->setRedirect(JRoute::_('index.php?option=com_rsticketspro&view=signature', false));
	}
}