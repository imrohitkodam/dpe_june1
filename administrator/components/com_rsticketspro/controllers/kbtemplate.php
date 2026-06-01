<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproControllerKbtemplate extends JControllerLegacy
{
    public function __construct($config = array())
	{
		parent::__construct($config);
		
		$this->registerTask('apply', 'save');
	}
	
	public function cancel() {
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));
		
		$this->setRedirect(JRoute::_('index.php?option=com_rsticketspro&view=knowledgebase', false));
	}
	
	public function save() {
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));
		
		$input = JFactory::getApplication()->input;
		$data  = $input->get('jform', array(), 'array');
		$model = $this->getModel('kbtemplate');
		
		if (!$model->save($data)) {
			$this->setMessage($model->getError(), 'error');
		} else {
			$this->setMessage(JText::_('RST_KB_TEMPLATE_SAVED_OK', 'info'));
		}
		
		$task = $this->getTask();
		if ($task == 'save') {
			$this->setRedirect(JRoute::_('index.php?option=com_rsticketspro&view=knowledgebase', false));
		} elseif ($task == 'apply') {
			$this->setRedirect(JRoute::_('index.php?option=com_rsticketspro&view=kbtemplate', false));
		}
	}
}