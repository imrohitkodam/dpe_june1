<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');


class RsticketsproViewSearch extends JViewLegacy
{
	public function display($tpl = null) {
		
		$this->globalMessage	        = JText::_(RSTicketsProHelper::getConfig('global_message'));
		$this->globalMessagePosition	= RSTicketsProHelper::getConfig('global_message_position');
		$this->form				= $this->get('Form');
		$this->params			= JFactory::getApplication()->getParams('com_rsticketspro');
		$this->advanced         = $this->get('Advanced');
		$this->show_footer		= RSTicketsProHelper::getConfig('rsticketspro_link');
		$this->footer			= RSTicketsProHelper::getFooter();
		$this->is_staff			= RSTicketsProHelper::isStaff();
		$this->permissions		= RSTicketsProHelper::getCurrentPermissions();
		$this->itemid			= $this->get('itemid');
		
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
}