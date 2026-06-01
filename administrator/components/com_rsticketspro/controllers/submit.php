<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;

class RsticketsproControllerSubmit extends JControllerLegacy
{
	protected $option  = 'com_rsticketspro';
	protected $context = 'submit';

    public function __construct($config = array())
    {
		parent::__construct($config);
	}

	public function showForm() 
	{
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));
		
		$this->setRedirect(JRoute::_('index.php?option=com_rsticketspro&view=submit', false));
	}
	
	public function cancel()
	{
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));
		
		$this->setRedirect(JRoute::_('index.php?option=com_rsticketspro&view=tickets', false));
	}
	
	public function save()
	{
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		$app      = JFactory::getApplication();
		$input    = $app->input;
		$data     = $input->get('jform', array(), 'array');


		$multiPleData = $data['cluster_id'];
		unset($data['cluster_id']);
		$successCount = 0;

		for($i = 0; $i < count($multiPleData); $i++)
		{
			$clusterIdindex = 'cluster_id'.$i;
			$data['cluster'] = ($multiPleData[$clusterIdindex]['cluster_id'] && ($data['mulTciket'] == 'multipleTicket'))?$multiPleData[$clusterIdindex]['cluster_id']:$data['cluster'];

			$data['customer_id'] = ($multiPleData[$clusterIdindex]['MultiOrgCustomer'] && ($data['mulTciket'] == 'multipleTicket'))?$multiPleData[$clusterIdindex]['MultiOrgCustomer']:$data['customer_id'];

			$data['clusterusers'] = ($multiPleData[$clusterIdindex]['clusterusers']  && ($data['mulTciket'] == 'multipleTicket'))?$multiPleData[$clusterIdindex]['clusterusers']:$data['clusterusers'];
			
			$fields   = $input->get('rst_custom_fields', array(), 'array');
			$files    = $input->files->get('jform', null, 'raw');
			$model    = $this->getModel('submit');
			$context  = "$this->option.edit.$this->context";
			$redirect = RSTicketsProHelper::getConfig('submit_redirect');
			$data['subject']     = strip_tags($data['subject']);


		
		if (!$model->save($data, $fields, is_array($files) && isset($files['files']) ? $files['files'] : array()))
		{
			// Save the data in the session.
			$app->setUserState($context . '.data', $data);
			$app->setUserState($context . '.fields', $fields);
			
			$this->setMessage($model->getError(), 'error');
		}
		else
		{
			// Clear the data in the session
			$app->setUserState($context . '.data', null);
			$app->setUserState($context . '.fields', null);

			$this->setMessage(JText::_('RST_TICKET_SUBMIT_OK', 'info'));

			$sucess = ++$successCount;
			//DPE Hack
			$session = Factory::getSession();
			$ticketId = $session->get('ticketIdForUCMLog');
			PluginHelper::importPlugin('tjucmdpe');
			Factory::getApplication()->triggerEvent('onAfterTicketCreateSaveTimeSave',array($ticketId,$data));

			
			
		 }
		}

		if ($sucess > 0 )
		{
			// DPE - Hack - After saving ticket wants to redirect user on ticket list view.
			if ($app->isClient('site'))
			{
				return !empty($redirect) ? $this->setRedirect($redirect) : $this->setRedirect(JRoute::_('index.php?option=com_dpe&view=rsticketspro', false));
			}
		}
		
		$this->setRedirect(JRoute::_('index.php?option=com_rsticketspro&view=submit', false));
	}
}