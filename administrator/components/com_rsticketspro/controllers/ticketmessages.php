<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

use Joomla\Utilities\ArrayHelper;

defined('_JEXEC') or die('Restricted access');

class RsticketsproControllerTicketmessages extends JControllerAdmin
{
	protected $view_list = 'ticket';

	public function getModel($name = 'Ticketmessage', $prefix = 'RsticketsproModel', $config = array('ignore_request' => true))
	{
		return parent::getModel($name, $prefix, $config);
	}

	public function delete()
	{
		// Check for request forgeries
		$this->checkToken('get');

		// Get items to remove from the request.
		$cid = $this->input->get('cid', array(), 'array');

		if (!is_array($cid) || count($cid) < 1)
		{
			\JLog::add(\JText::_($this->text_prefix . '_NO_ITEM_SELECTED'), \JLog::WARNING, 'jerror');
		}
		else
		{
			// Get the model.
			$model = $this->getModel();

			// Make sure the item ids are integers
			$cid = ArrayHelper::toInteger($cid);

			// Remove the items.
			if ($model->delete($cid))
			{
				$this->setMessage(\JText::plural($this->text_prefix . '_N_ITEMS_DELETED', count($cid)));
			}
			else
			{
				$this->setMessage($model->getError(), 'error');
			}

			// Invoke the postDelete method to allow for the child class to access the model.
			$this->postDeleteHook($model, $cid);
		}

		$append = '&id=' . $this->input->getInt('ticket_id');

		$this->setRedirect(\JRoute::_('index.php?option=' . $this->option . '&view=' . $this->view_list . $append, false));
	}

	public function deleteattachment()
	{
		// Check for request forgeries
		$this->checkToken('get');

		// Get items to remove from the request.
		$cid = $this->input->get('cid', array(), 'array');

		if (!is_array($cid) || count($cid) < 1)
		{
			\JLog::add(\JText::_($this->text_prefix . '_NO_ITEM_SELECTED'), \JLog::WARNING, 'jerror');
		}
		else
		{
			// Get the model.
			$model = $this->getModel();

			// Make sure the item ids are integers
			$cid = ArrayHelper::toInteger($cid);

			// Remove the items.
			if ($model->deleteattachment($cid))
			{
				$this->setMessage(\JText::plural($this->text_prefix . '_N_ITEMS_DELETED', count($cid)));
			}
			else
			{
				$this->setMessage($model->getError(), 'error');
			}
		}

		$append = '&id=' . $this->input->getInt('ticket_id');

		$this->setRedirect(\JRoute::_('index.php?option=' . $this->option . '&view=' . $this->view_list . $append, false));
	}
}