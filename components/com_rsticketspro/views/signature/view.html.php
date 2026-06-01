<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproViewSignature extends JViewLegacy
{
	public function display($tpl = null)
	{
		$this->canAccess();
		
		$this->form			= $this->get('Form');
		$this->params		= JFactory::getApplication()->getParams('com_rsticketspro');
		$this->show_footer	= RSTicketsProHelper::getConfig('rsticketspro_link');
		$this->footer		= RSTicketsProHelper::getFooter();
		$this->globalMessage 		    = JText::_(RSTicketsProHelper::getConfig('global_message'));
		$this->globalMessagePosition	= RSTicketsProHelper::getConfig('global_message_position');
		
		$this->prepareDocument();

		parent::display($tpl);
	}
	
	protected function prepareDocument()
	{
		// Description
		if ($this->params->get('menu-meta_description'))
		{
			$this->document->setDescription($this->params->get('menu-meta_description'));
		}

		// Keywords
		if ($this->params->get('menu-meta_keywords'))
		{
			$this->document->setMetadata('keywords', $this->params->get('menu-meta_keywords'));
		}

		// Robots
		if ($this->params->get('robots'))
		{
			$this->document->setMetadata('robots', $this->params->get('robots'));
		}
	}
	
	protected function canAccess()
	{
		$app	= JFactory::getApplication();
		$user	= JFactory::getUser();
		
		if ($user->get('guest'))
		{
			$app->redirect(JRoute::_('index.php?option=com_users&view=login&return=' . base64_encode((string) JUri::getInstance()), false));
		}
		
		if (!RSTicketsProHelper::isStaff())
		{
            $app->enqueueMessage(JText::_('RST_CANNOT_CHANGE_SIGNATURE'), 'warning');
			$app->redirect(RSTicketsProHelper::route('index.php?option=com_rsticketspro&view=tickets', false));
		}
		
		if (!$this->get('isAssigned'))
		{
            $app->enqueueMessage(JText::_('RST_CANNOT_CHANGE_SIGNATURE_MUST_BE_STAFF'), 'warning');
            $referer = $app->input->server->get('HTTP_REFERER', '', 'raw');

			if (empty($referer))
			{
				$app->redirect(RSTicketsProHelper::route('index.php?option=com_rsticketspro&view=tickets', false));
			}
			else
			{
				$app->redirect($referer);
			}
		}
	}
}