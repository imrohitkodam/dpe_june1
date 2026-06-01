<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproViewConfiguration extends JViewLegacy
{
	protected $tabs;
	protected $field;
	protected $form;
	protected $fieldsets;
	protected $config;
	protected $sidebar;
	
	public function display($tpl = null)
	{
		$user = JFactory::getUser();

		if (!$user->authorise('core.admin', 'com_rsticketspro')) {
			$app = JFactory::getApplication();
			$app->enqueueMessage(JText::_('JERROR_ALERTNOAUTHOR'), 'error');
			$app->redirect(JRoute::_('index.php?option=com_rsticketspro', false));
		}
		
		$this->addToolbar();

		$this->tabs		 = $this->get('RSTabs');
		$this->form		 = $this->get('Form');
		$this->fieldsets = $this->form->getFieldsets();

		if (!RSTicketsProHelper::cronPluginExists())
		{
			$this->form->setFieldAttribute('show_alternative_email', 'type', 'hidden');
		}
		
		// config
		$this->config	= $this->get('Config');
		
		parent::display($tpl);
	}
	
	protected function addToolbar() {
		// set title
		JToolbarHelper::title('RSTickets! Pro', 'rsticketspro');

		RSTicketsProToolbarHelper::addToolbar('configuration');
		
		JToolbarHelper::apply('configuration.apply');
		JToolbarHelper::save('configuration.save');
		JToolbarHelper::cancel('configuration.cancel');
	}
}