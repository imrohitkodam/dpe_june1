<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproViewPredefinedsearches extends JViewLegacy
{	
	public function display($tpl = null)
	{
		$this->canAccess();
		
		$app				= JFactory::getApplication();
		$this->params		= $app->getParams('com_rsticketspro');
		$this->items 		= $this->get('Items');
		$this->state 		= $this->get('State');
		$this->pagination 	= $this->get('Pagination');

		$app->getPathway()->addItem(JText::_('RST_MANAGE_SEARCHES'), RSTicketsProHelper::route('index.php?option=com_rsticketspro&view=predefinedsearches'));

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
		$app = JFactory::getApplication();
		
		if (JFactory::getUser()->get('guest'))
		{
			$link = base64_encode((string) JUri::getInstance());
			$app->redirect(RSTicketsProHelper::route('index.php?option=com_users&view=login&return='.$link, false));
		}
		
		if (!RSTicketsProHelper::isStaff())
		{
		    $app->enqueueMessage(JText::_('RST_CUSTOMER_CANNOT_VIEW_SEARCHES'), 'warning');
			$app->redirect(RSTicketsProHelper::route('index.php?option=com_rsticketspro&view=tickets', false));
		}
	}
}