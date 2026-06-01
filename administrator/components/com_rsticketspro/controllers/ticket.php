<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Session\Session;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Table\Table;


class RsticketsproControllerTicket extends BaseController
{
	protected $option = 'com_rsticketspro';
	protected $context = 'ticket';

    public function __construct($config = array())
	{
		parent::__construct($config);

		$this->registerTask('reopen', 'changeTicketStatus');
		$this->registerTask('close', 'changeTicketStatus');
	}

	protected function getLoginLink()
	{
		$link = base64_encode((string) Uri::getInstance());

		return RSTicketsProHelper::route('index.php?option=com_users&view=login&return=' . $link, false);
	}

	protected function getListingLink()
	{
		$app = Factory::getApplication();
		if ($app->isClient('administrator'))
		{
			return RSTicketsProHelper::route('index.php?option=com_rsticketspro&view=tickets', false);
		}
		else
		{
			return RSTicketsProHelper::route('index.php?option=com_rsticketspro&view=rsticketspro', false);
		}
	}

	public function flag()
	{
		$app           = Factory::getApplication();
		$cid           = $app->input->getInt('cid');
		$flagged       = $app->input->getInt('flagged');
		$model         = $this->getModel('ticket');

		// logged in?
		if ($model->isGuest())
		{
			return $this->setRedirect($this->getLoginLink());
		}
		// only staff members can call this
		if (!$model->isStaff())
		{
			$app->close();
		}

		// check permissions for the ticket
		if (!$model->hasPermission($cid))
		{
            $app->enqueueMessage($model->getError(), 'warning');

			return $this->setRedirect($this->getListingLink());
		}

		@ob_end_clean();
		$model->setFlag($cid, $flagged);

		echo '1';

		$app->close();
	}

	public function rate()
	{
		$app         = Factory::getApplication();
		$cid         = $app->input->getInt('cid');
		$rating      = $app->input->getInt('rating');
		$access_code = $app->input->get('access_code');

		$model = $this->getModel('ticket');

		if (strlen($access_code))
		{
			$ticket   = $model->getTicket($cid);
			$customer = Factory::getUser($ticket->customer_id);

			if ((int) $ticket->feedback != 0)
			{
				$app->redirect(Uri::root(), Text::_('RST_EMAIL_ALREADY_RATED'));
			}

			if ($access_code !== md5($ticket->id . ' | ' . $customer->email))
			{
				throw new Exception(Text::_('RST_EMAIL_ACCESS_CODE_INCORRECT'), 403);
			}

			$model->setRating($cid, $rating);

			$app->redirect(Uri::root(), Text::_('RST_FEEDBACK_RECEIVED_FROM_EMAIL'));
		}
		else
		{
			// logged in?Text::_('RST_YOU_HAVE_TO_BE_LOGGED_IN')
			if ($model->isGuest())
			{
				throw new Exception(Text::_('RST_YOU_HAVE_TO_BE_LOGGED_IN'), 403);
			}
			// no point in trying to rate when config doesn't allow it
			if (!RSTicketsProHelper::getConfig('show_ticket_voting'))
			{
				$app->close();
			}
			// only customers can call this
			if ($model->isStaff())
			{
				$app->close();
			}
			// check permissions for the ticket
			if (!$model->hasPermission($cid))
			{
				throw new Exception($model->getError(), 403);
			}

			@ob_end_clean();
			$model->setRating($cid, $rating);
			echo '1';

			$app->close();
		}

	}

	public function delete()
	{
		$app = Factory::getApplication();
		$cid = $app->input->getInt('cid');

		$model = $this->getModel('ticket');
		// logged in?
		if ($model->isGuest())
		{
			return $this->setRedirect($this->getLoginLink());
		}
		// only staff members can call this
		if (!$model->isStaff())
		{
			throw new Exception(Text::_('RST_CANNOT_DELETE_TICKETS'), 403);
		}
		if (!$model->hasPermission($cid))
		{
            $app->enqueueMessage($model->getError(), 'warning');

			return $this->setRedirect($this->getListingLink());
		}
		$permissions = $model->getStaffPermissions();
		if ($permissions->delete_ticket)
		{
			$model->delete($cid);
			$this->setMessage(Text::_('RST_TICKET_DELETED_OK'));
		}
		else
		{
			$this->setMessage(Text::sprintf('RST_TICKET_NOT_DELETED', $cid), 'error');
		}
		$this->setRedirect($this->getListingLink());
	}

	public function notify()
	{
		// this is called only when autoclose is enabled
		if (!RSTicketsProHelper::getConfig('autoclose_enabled'))
		{
			return $this->setRedirect($this->getListingLink());
		}
		$app = Factory::getApplication();
		$cid = $app->input->getInt('cid');

		$model = $this->getModel('ticket');
		// logged in?
		if ($model->isGuest())
		{
			return $this->setRedirect($this->getLoginLink());
		}
		// only staff members can call this
		if (!$model->isStaff())
		{
			throw new Exception(Text::_('RST_CANNOT_NOTIFY_TICKETS'), 403);
		}
		if (!$model->hasPermission($cid))
		{
            $app->enqueueMessage($model->getError(), 'warning');

			return $this->setRedirect($this->getListingLink());
		}

		$model->notify($cid);
		$this->setMessage(Text::_('RST_TICKET_NOTIFIED_OK'));
		$this->setRedirect($this->getListingLink());
	}

	public function bulkUpdate()
	{
		$app  = Factory::getApplication();
		$cids = $app->input->get('cid', array(), 'array');

		$model = $this->getModel('ticket');
		// logged in?
		if ($model->isGuest())
		{
			return $this->setRedirect($this->getLoginLink());
		}
		// only staff members can call this
		if (!$model->isStaff())
		{
			throw new Exception(Text::_('RST_CANNOT_UPDATE_TICKETS'), 403);
		}

		$staff_id    = $app->input->getInt('bulk_staff_id', -1);
		$priority_id = $app->input->getInt('bulk_priority_id');
		$status_id   = $app->input->getInt('bulk_status_id');
		$notify      = $app->input->getInt('bulk_notify');
		$delete      = $app->input->getInt('bulk_delete');

		// no point notifying if autoclose is disabled
		if (!RSTicketsProHelper::getConfig('autoclose_enabled'))
		{
			$notify = 0;
		}

		// get staff member permissions
		$permissions = $model->getStaffPermissions();

		foreach ($cids as $cid)
		{
			// first, let's make sure this ticket can be opened by the current user
			if ($model->hasPermission($cid))
			{
				// if we are deleting tickets then it doesn't make any sense to check the other options
				if ($delete)
				{
					// check for delete permission & if ticket has been deleted
					if (!$permissions->delete_ticket || !$model->delete($cid))
					{
						$app->enqueueMessage(Text::sprintf('RST_TICKET_NOT_DELETED', $cid), 'error');
					}
				}
				else
				{
					$data = array();

					// can assign?
					if ($permissions->assign_tickets && $staff_id > -1)
					{
						$data['staff_id'] = $staff_id;
					}

					// can update ticket information?
					if ($permissions->update_ticket)
					{
						if ($priority_id)
						{
							$data['priority_id'] = $priority_id;
						}
						if ($status_id)
						{
							$data['status_id'] = $status_id;
						}
					}

					if ($data)
					{
						$model->updateInfo($cid, $data);
					}

					// let's see if we need to notify as well
					if ($notify)
					{
						$model->notify($cid);
					}
				}
			}
		}

		if ($delete)
		{
			$this->setMessage(Text::_('RST_TICKETS_DELETED_OK'));
		}
		else
		{
			$this->setMessage(Text::_('RST_TICKETS_UPDATED_OK'));
			if ($notify)
			{
				$this->setMessage(Text::_('RST_TICKET_NOTIFIED_OK'));
			}
		}

		$this->setRedirect($this->getListingLink());
	}

	// used to update custom fields
	public function updateFields()
	{
		$app   = Factory::getApplication();
		$cid   = $app->input->getInt('cid');
		$data  = $app->input->get('rst_custom_fields', array(), 'array');
		$model = $this->getModel('ticket');
		$url   = Route::_('index.php?option=com_rsticketspro&view=ticket&id=' . RSTicketsProHelper::sef($cid), false);

		// logged in?
		if ($model->isGuest())
		{
			return $this->setRedirect($this->getLoginLink());
		}
		// only staff members can call this
		if (!$model->isStaff())
		{
			throw new Exception(Text::_('RST_CANNOT_UPDATE_TICKET'), 403);
		}
		if (!$model->hasPermission($cid))
		{
            $app->enqueueMessage($model->getError(), 'warning');

			return $this->setRedirect($this->getListingLink());
		}
		$permissions = $model->getStaffPermissions();
		if (!$permissions->update_ticket_custom_fields)
		{
            $app->enqueueMessage(Text::_('RST_CANNOT_UPDATE_TICKET'), 'warning');

			return $this->setRedirect($url);
		}

		$model->updateFields($cid, $data);

		$this->setMessage(Text::_('RST_TICKET_UPDATED_OK'));
		$this->setRedirect($url);
	}

	// used to update ticket information
	public function updateInfo()
	{
		$app   = Factory::getApplication();
		$cid   = $app->input->getInt('cid');
		$data  = $app->input->get('ticket', array(), 'array');
		$model = $this->getModel('ticket');
		$url   = Route::_('index.php?option=com_rsticketspro&view=ticket&id=' . RSTicketsProHelper::sef($cid), false);

		// Hack start DPE: Get jfrom data
		$data  = array_merge($data, $app->input->get('jform', array(), 'array'));

		if (!$data['customer_id'])
		{
			$app->enqueueMessage(Text::_('RST_PLEASE_SELECT_CUSTOMER_TICKET'), 'error');
			$app->redirect($url);
		}

		// Hack end DPE: Get jfrom data

		// logged in?
		if ($model->isGuest())
		{
			return $this->setRedirect($this->getLoginLink());
		}
		// get permissions
		$permissions = $model->getStaffPermissions();

		$userGroups        = Factory::getUser()->groups;
		$multiagencyParams = ComponentHelper::getParams('com_multiagency');
		$orgAdminRoleId    = (int) $multiagencyParams->get('multiagency_school_admin_group', '0', 'INT');
		$isOrgAdmin        = in_array($orgAdminRoleId, $userGroups);

		// only staff members can call this
		if (!$model->isStaff() && !$isOrgAdmin)
		{
			throw new Exception(Text::_('RST_CANNOT_UPDATE_TICKET'), 403);
		}
		if (!$model->hasPermission($cid))
		{
            $app->enqueueMessage($model->getError(), 'warning');

			return $this->setRedirect($this->getListingLink());
		}

		// check permissions to update the ticket information
		if (!$permissions->update_ticket && !$isOrgAdmin)
		{
			unset($data['subject']);
			unset($data['priority_id']);
		}

		// check permissions to move to another department
		if (!$permissions->move_ticket)
		{
			unset($data['department_id']);
		}

		// check permissions to change ticket status
		if (!$permissions->change_ticket_status)
		{
			unset($data['status_id']);
		}

		// check permissions to assign tickets
		if (!$permissions->assign_tickets)
		{
			unset($data['staff_id']);
		}

		// check permissions to change customer
		// no permissions at all
		if (!$permissions->add_ticket_customers && !$permissions->add_ticket_staff && !$permissions->add_ticket)
		{
			unset($data['customer_id']);
		}
		else
		{
			$user     = Factory::getUser();
			$customer = Factory::getUser($data['customer_id']);
			$is_staff = RSTicketsProHelper::isStaff($customer->get('id'));

			// cannot change to himself...
			if ($customer->id == $user->id && !$permissions->add_ticket)
			{
				unset($data['customer_id']);
			}

			// cannot change to another staff member
			if ($customer->id != $user->id && $is_staff && !$permissions->add_ticket_staff)
			{
				unset($data['customer_id']);
			}

			// cannot change to another customer
			if ($customer->id != $user->id && !$is_staff && !$permissions->add_ticket_customers)
			{
				unset($data['customer_id']);
			}
		}

		$model->updateInfo($cid, $data);

		if(!$isOrgAdmin){
		// DPE Hack to store time log for ticket
		PluginHelper::importPlugin('tjucmdpe');
		Factory::getApplication()->triggerEvent('onAfterTicketCreateSaveTimeSave',array($cid,$data));
		}


		$this->setMessage(Text::_('RST_TICKET_UPDATED_OK'));
		$this->setRedirect($url);
	}

	public function saveTimeSpent()
	{
		$app        = Factory::getApplication();
		$cid        = $app->input->getInt('cid');
		$data       = $app->input->get('ticket', array(), 'array');
		$time_spent = $data['time_spent'];
		$model      = $this->getModel('ticket');

		// logged in?
		if ($model->isGuest())
		{
			return $this->setRedirect($this->getLoginLink());
		}
		// not enabled
		if (!RSTicketsProHelper::getConfig('enable_time_spent'))
		{
			throw new Exception(Text::_('RST_CANNOT_UPDATE_TIME_SPENT'), 403);
		}
		// only staff members can call this
		if (!$model->isStaff())
		{
			throw new Exception(Text::_('RST_CANNOT_UPDATE_TIME_SPENT'), 403);
		}
		if (!$model->hasPermission($cid))
		{
            $app->enqueueMessage($model->getError(), 'warning');

			return $this->setRedirect($this->getListingLink());
		}

		$table = $model->getTable();

		$table->save(array(
			'id'         => $cid,
			'time_spent' => $time_spent
		));

		$this->setMessage(Text::_('RST_TIME_SPENT_UPDATED_OK'));
		$this->setRedirect(Route::_('index.php?option=com_rsticketspro&view=ticket&id=' . RSTicketsProHelper::sef($cid), false));
	}

	public function cancel()
	{
		$this->setRedirect($this->getListingLink());
	}

	public function changeTicketStatus()
	{
		$app   = Factory::getApplication();
		$model = $this->getModel('ticket');
		$id    = $app->input->getInt('id');
		$task  = $app->input->get('task');

		$permissions = $model->getStaffPermissions();

		if ($task == 'reopen')
		{
			$canChangeStatus = ($model->isStaff() && $permissions->change_ticket_status) || (!$model->isStaff() && RSTicketsProHelper::getConfig('allow_ticket_reopening'));
			$status_id       = RST_STATUS_OPEN;
			$successMsg      = Text::_('RST_TICKET_REOPENED_OK');
			$errorMsg        = Text::_('RST_CANNOT_REOPEN_TICKET');
		}
		elseif ($task == 'close')
		{
			$canChangeStatus = ($model->isStaff() && $permissions->change_ticket_status) || (!$model->isStaff() && RSTicketsProHelper::getConfig('allow_ticket_closing'));
			$status_id       = RST_STATUS_CLOSED;
			$successMsg      = Text::_('RST_TICKET_CLOSED_OK');
			$errorMsg        = Text::_('RST_CANNOT_CLOSE_TICKET');
		}

		if ($model->hasPermission($id) && $canChangeStatus)
		{
			$model->updateInfo($id, array(
				'status_id' => $status_id
			));

			$this->setMessage($successMsg);
			$this->setRedirect(Route::_('index.php?option=com_rsticketspro&view=ticket&id=' . RSTicketsProHelper::sef($id), false));
		}
		else
		{
			throw new Exception($errorMsg, 403);
		}
	}

	public function reply()
	{
		// Check for request forgeries.
		Session::checkToken() or die(Text::_('JINVALID_TOKEN'));

		$app     = Factory::getApplication();
		$input   = $app->input;
		$data    = $input->get('ticket', array(), 'array');
		$id      = $input->getInt('id');
		$files   = $input->files->get('ticket', null, 'raw');
		$model   = $this->getModel('ticket');
		$ticket  = $model->getTicket($id);
		$context = "$this->option.edit.$this->context";

		if ($ticket->status_id == RST_STATUS_CLOSED)
		{
            $app->enqueueMessage(Text::_('RST_TICKET_REPLIES_CLOSED_ERROR'), 'warning');

			return $this->setRedirect($this->getListingLink());
		}

		if (!$model->hasPermission($id))
		{
            $app->enqueueMessage($model->getError(), 'warning');

			return $this->setRedirect($this->getListingLink());
		}
		// overwrite some options
		$data['id']        = null;
		$data['user_id']   = Factory::getUser()->id;
		$data['date']      = Factory::getDate()->toSql();
		$data['ticket_id'] = $id;

		if ($app->isClient('administrator'))
        {
            $data['consent'] = array(1);
        }

		if (!$model->reply($id, $data, is_array($files) && isset($files['files']) ? $files['files'] : array()))
		{
			// Save the data in the session.
			$app->setUserState($context . '.data', $data);

			$this->setMessage($model->getError(), 'error');
		}
		else
		{
			// Clear the data in the session
			$app->setUserState($context . '.data', null);

			$this->setMessage(Text::_('RST_TICKET_SUBMIT_REPLY_OK', 'info'));
		}

		$this->setRedirect(Route::_('index.php?option=com_rsticketspro&view=ticket&id=' . RSTicketsProHelper::sef($id), false));
	}

	public function downloadFile()
	{
		$app   = Factory::getApplication();
		$input = $app->input;
		$id    = $input->getInt('id');

		$model = $this->getModel('ticket');
		$file  = Table::getInstance('Ticketfiles', 'RsticketsproTable');

		// check if file exists
		if (!$file->load($id) || !$file->id)
		{
			throw new Exception(Text::_('RST_CANNOT_DOWNLOAD_FILE_NOT_EXIST'), 500);
		}

		// check if ticket can be opened by the user
		$ticket = $model->getTicket($file->ticket_id);
		if (!$ticket || !$ticket->id)
		{
			throw new Exception(Text::_('RST_CANNOT_DOWNLOAD_FILE'), 403);
		}

		if ($access_code = Factory::getApplication()->input->get('access_code', ''))
		{
			if (!$model->hasDownloadPermission($access_code, $file->id, $ticket->id))
			{
				throw new Exception(Text::_('RST_CANNOT_DOWNLOAD_FILE'), 403);
			}
		}
		else
		{
			if (!$model->hasPermission($file->ticket_id))
			{
				throw new Exception(Text::_('RST_CANNOT_DOWNLOAD_FILE'), 403);
			}
		}

		$path = $file->getRealPath();
		if (!file_exists($path))
		{
			throw new Exception(Text::_('RST_CANNOT_DOWNLOAD_FILE_NOT_EXIST'), 500);
		}

		// increment downloads
		$file->hit();

		@ob_end_clean();
		$filename = $file->filename;
		header("Cache-Control: public, must-revalidate");
		header('Cache-Control: pre-check=0, post-check=0, max-age=0');
		if (strstr(@$_SERVER["HTTP_USER_AGENT"], "MSIE") == false)
		{
			header("Cache-Control: no-cache");
			header("Pragma: no-cache");
		}
		header("Expires: 0");
		header("Content-Description: File Transfer");
		header("Expires: Sat, 01 Jan 2000 01:00:00 GMT");
		header("Content-Type: application/octet-stream; charset=utf-8");
		header("Content-Length: " . (string) filesize($path));
		header('Content-Disposition: attachment; filename="' . $filename . '"');
		header("Content-Transfer-Encoding: binary\n");
		@readfile($path);

		$app->close();
	}
}
