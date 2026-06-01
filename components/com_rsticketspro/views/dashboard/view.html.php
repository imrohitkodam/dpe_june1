<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproViewDashboard extends JViewLegacy
{
	public function display($tpl = null)
	{
		$this->globalMessage	        = JText::_(RSTicketsProHelper::getConfig('global_message'));
		$this->globalMessagePosition	= RSTicketsProHelper::getConfig('global_message_position');
		$this->model					= $this->getModel('dashboard');
		$this->params					= JFactory::getApplication()->getParams('com_rsticketspro');
		$this->user						= JFactory::getUser();
		$this->categories				= $this->params->get('split_kb_to_tabs', 0) ? $this->model->getCategories($this->params->get('top_category_id', 0)) : $this->model->getCategories(0);
		$this->tickets					= $this->get('tickets');
		$this->login_link				= JRoute::_('index.php?option=com_users&view=login&return=' . base64_encode((string) JUri::getInstance()));
		$this->kb_subcats_limit			= (int) $this->params->get('kb_subcategories_list', -1);
		$this->kb_itemid				= (int) $this->params->get('kb_itemid');
		$this->search_link  			= RSTicketsProHelper::route('index.php?option=com_rsticketspro&view=knowledgebase' . (empty($this->kb_itemid) ? '&layout=results' : '&Itemid=' . $this->kb_itemid));
		$this->itemid       			= JFactory::getApplication()->input->getInt('Itemid', 0);
		$this->db_thumb_type			= $this->params->get('db_thumb_type', 'icons');
		$this->db_submit_ticket_thumb	= $this->params->get('db_submit_ticket_thumb');
		$this->db_view_tickets_thumb	= $this->params->get('db_view_tickets_thumb');
		$this->db_search_tickets_thumb	= $this->params->get('db_search_tickets_thumb');

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
	
	public function trim($string, $max = 255, $more='...')
	{
		return RSTicketsProHelper::shorten($string, $max, $more);
	}
}