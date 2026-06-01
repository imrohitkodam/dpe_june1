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
use Joomla\CMS\Language\Text;

$lang = Factory::getLanguage();
$lang->load('com_rsticketspro', JPATH_SITE);

class RsticketsproModelSubmit extends JModelAdmin
{
	protected $fields = array();

	public function getTable($type = 'Tickets', $prefix = 'RsticketsproTable', $config = array())
	{
		return parent::getTable($type, $prefix, $config);
	}

	public function getForm($data = array(), $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm('com_rsticketspro.submit', 'submit', array('control' => 'jform', 'load_data' => $loadData));
		if (empty($form))
		{
			return false;
		}

		if (RSTicketsProHelper::getConfig('captcha_enabled') !== '1')
		{
			$form->setFieldAttribute('captcha', 'label', '');
			$form->setFieldAttribute('captcha', 'description', '');
		}

		$isStaff 				= RSTicketsProHelper::isStaff();
		$showAlternativeEmail 	= RSTicketsProHelper::getConfig('show_alternative_email');
		$allowPasswordChange 	= RSTicketsProHelper::getConfig('allow_password_change');
		$permissions 			= $this->getPermissions();
		if (!$isStaff || !$permissions || (!$permissions->add_ticket_customers && !$permissions->add_ticket_staff))
		{
			$user = Factory::getUser();

			$form->setFieldAttribute('email', 'showon', null);
			$form->setFieldAttribute('name', 'showon', null);

			if ($showAlternativeEmail)
			{
				$form->setFieldAttribute('alternative_email', 'showon', null);
			}

			if ($allowPasswordChange)
			{
				$form->setFieldAttribute('password', 'showon', null);
			}

			if ($user->id)
			{
				$form->setValue('email', null, $user->email);
				$form->setFieldAttribute('email', 'disabled', 'true');
				$form->setValue('name', null, $user->name);
				$form->setFieldAttribute('name', 'disabled', 'true');

				if ($showAlternativeEmail)
				{
					$form->setValue('alternative_email', null, RSTicketsProHelper::getAlternativeEmail($user->id));
				}
			}
		}

		if (Factory::getApplication()->isClient('site') && !RSTicketsProHelper::getConfig('use_btn_group_radio'))
		{
			$form->setFieldAttribute('submit_type', 'class', '');
		}

		return $form;
	}

	protected function loadFormData()
	{
		// Check the session for previously entered form data.
		$app     = Factory::getApplication();
		$default = array();

		// check with menu parameters
		if ($app->isClient('site'))
		{
			$params = $app->getParams();
			if ($department_id = $params->get('department_id'))
			{
				$default['department_id'] = $department_id;
			}
            if ($message = $params->get('message'))
            {
                $default['message'] = $message;
            }
			if ($department_id = $app->input->getInt('department_id'))
			{
				$default['department_id'] = $department_id;
			}

			// Grab params from URL.
			if ($jform = $app->input->get->get('jform', array(), 'array'))
			{
				foreach ($jform as $key => $value)
				{
					$default[$key] = $value;
				}
			}
		}

		$data = $app->getUserState('com_rsticketspro.edit.submit.data', $default);

		return $data;
	}

	public function getRSFieldset()
	{

		require_once JPATH_COMPONENT_ADMINISTRATOR . '/helpers/adapters/fieldset.php';

		$fieldset = new RSFieldset();

		return $fieldset;
	}

	public function getDepartments()
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select($db->qn('id'))
			->select($db->qn('priority_id'))
			->select($db->qn('upload'))
			->select($db->qn('upload_ticket_required'))
			->select($db->qn('upload_extensions'))
			->select($db->qn('upload_size'))
			->select($db->qn('upload_files'))
			->from($db->qn('#__rsticketspro_departments'))
			->where($db->qn('published') . '=' . $db->q(1))
			->order($db->qn('ordering') . ' ' . $db->escape('asc'));
		$db->setQuery($query);
		$list = $db->loadObjectList();

		$departments = array();
		$is_logged   = Factory::getUser()->get('id') > 0;
		$max_files   = (int) ini_get('max_file_uploads');
		$max_size    = ini_get('upload_max_filesize');
		foreach ($list as $department)
		{
			if ($department->upload == 0)
			{ // uploads are not allowed
				$department->upload = 0;
			}
			elseif ($department->upload == 1 || ($department->upload == 2 && $is_logged))
			{ // uploads are allowed for everyone or only for logged in users
				$department->upload = 1;
			}
			else
			{
				$department->upload = 0;
			}

			// convert allowed extensions to human readable format
			$upload_extensions = $department->upload_extensions;
			$upload_extensions = str_replace(array("\r\n", "\r"), "\n", $upload_extensions);
			$upload_extensions = str_replace("\n", ", ", $upload_extensions);
			if (trim($upload_extensions) == '')
			{
				$upload_extensions = '*';
			}
			$department->upload_extensions = $upload_extensions;
			// set the message
			$department->upload_message = Text::sprintf('RST_TICKET_ATTACHMENTS_ALLOWED', $department->upload_extensions);

			// if the server allows less files than what we've selected, use that number instead
			if ($department->upload_files > $max_files || empty($department->upload_files))
			{
				$department->upload_files = $max_files;
			}

            $max = $department->upload_files;
            if ($department->upload_files == 0)
            {
                $max = Text::_('RST_UNLIMITED');
            }
            $department->upload_message_max_files = Text::sprintf('RST_TICKET_ATTACHMENTS_MAX_ALLOWED', $max);

            $size = $department->upload_size;
            if ((float) $size == 0)
            {
                $size = $max_size;
            }
            else
            {
                $size = $department->upload_size . 'M';
            }

            $department->upload_message_max_size = Text::sprintf('RST_TICKET_ATTACHMENTS_MAX_SIZE_ALLOWED', $size);

			$departments[$department->id] = $department;
		}

		return $departments;
	}

	public function getCustomFields()
	{
		$db = Factory::getDbo();
		$app = Factory::getApplication();
		$query = $db->getQuery(true);
		$fields = array();
		$fieldValues = $app->getUserState('com_rsticketspro.edit.submit.fields', array());

		$query->select('*')
			->from($db->qn('#__rsticketspro_custom_fields'))
			->where($db->qn('published') . '=' . $db->q(1))
			->order($db->qn('department_id'))
			->order($db->qn('ordering') . ' ' . $db->escape('asc'));

		if ($list = $db->setQuery($query)->loadObjectList())
		{
			foreach ($list as $field)
			{
				$selected = isset($fieldValues['department_' . $field->department_id]) ? $fieldValues['department_' . $field->department_id] : array();
				$field = RSTicketsProHelper::showCustomField($field, $selected, true, $field->department_id);

				if ($field !== false)
				{
					$fields[] = $field;
				}
			}
		}

		return $fields;
	}

	protected function getCustomFieldsByDepartmentId($department_id)
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true);

		$query->select('*')
			->from($db->qn('#__rsticketspro_custom_fields'))
			->where($db->qn('department_id') . '=' . $db->q($department_id))
			->where($db->qn('published') . '=' . $db->q(1))
			->order($db->qn('ordering') . ' ' . $db->escape('asc'));
		$db->setQuery($query);

		return $db->loadObjectList();
	}

	// using this because Factory::getUser($inexistent_id) throws errors
	protected function getUserById($user_id)
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true);

		$query->select('*')
			->from($db->qn('#__users'))
			->where($db->qn('id') . '=' . $db->q($user_id));
		$db->setQuery($query);

		return $db->loadObject();
	}

	protected function getUserByEmail($email)
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true);

		$query->select('*')
			->from($db->qn('#__users'))
			->where($db->qn('email') . ' LIKE ' . $db->q($email));
		$db->setQuery($query);

		return $db->loadObject();
	}

	public function save($data, $fields = array(), $files = array())
	{
		JSession::checkToken() or die(Text::_('JINVALID_TOKEN_NOTICE')); 

		define('RST_SUBMIT_TYPE_NEW_USER', 1);
		define('RST_SUBMIT_TYPE_EXISTING_USER', 2);
		define('RST_SUBMIT_TYPE_CURRENT_USER', 3);

		$app         = Factory::getApplication();
		$permissions = $this->getPermissions();
		$customer    = null;
		$isStaff     = RSTicketsProHelper::isStaff();
		$user        = Factory::getUser();

		// is he a staff member?
		if ($isStaff && $permissions)
		{
			// can he select the type of user?
			if ($permissions->add_ticket_staff || $permissions->add_ticket_customers)
			{
				// This is hack, In Dpe we not allowing to staff or super user to add ticket on behalf other user
				$type = isset($data['submit_type']) ? $data['submit_type'] : RST_SUBMIT_TYPE_CURRENT_USER;
			}
			elseif ($permissions->add_ticket)
			{
				$type = RST_SUBMIT_TYPE_CURRENT_USER;
			}
			else
			{
				$this->setError(Text::_('RST_STAFF_CANNOT_SUBMIT_TICKET'));

				return false;
			}
		}
		else
		{
			// logged in, grab current user information
			if ($user->get('id'))
			{
				$type = RST_SUBMIT_TYPE_CURRENT_USER;
			}
			else
			{
				$type = RST_SUBMIT_TYPE_NEW_USER;
			}
		}

		if ($type == RST_SUBMIT_TYPE_NEW_USER)
		{
			// validate the email address supplied
			jimport('joomla.mail.helper');
			if (empty($data['email']) || !JMailHelper::isEmailAddress($data['email']))
			{
				$this->setError(Text::_('RST_TICKET_EMAIL_ERROR'));

				return false;
			}

			// validate the name
			if (empty($data['name']))
			{
				$this->setError(Text::_('RST_TICKET_NAME_ERROR'));

				return false;
			}

			$customer            = $this->getUserByEmail($data['email']);
			$data['customer_id'] = 0;
		}
		elseif ($type == RST_SUBMIT_TYPE_EXISTING_USER)
		{
			if (empty($data['customer_id']))
			{
				$this->setError(Text::_('RST_TICKET_CUSTOMER_ERROR'));

				return false;
			}

			$customer = $this->getUserById($data['customer_id']);
			// what if the user ID doesn't exist in the database?
			if (empty($customer))
			{
				$this->setError(Text::_('RST_TICKET_CUSTOMER_ERROR'));

				return false;
			}
		}
		elseif ($type == RST_SUBMIT_TYPE_CURRENT_USER && !$data['customer_id']) //DPE HAck to add customer
		{
			$customer = Factory::getUser();
		}

		// checking permissions
		if ($customer)
		{
			// is he a staff member?
			if ($isStaff)
			{
				// trying to submit a ticket on behalf of himself, but no access
				if ($customer->id == $user->id && !$permissions->add_ticket)
				{
					$this->setError(Text::_('RST_TICKET_EMAIL_STAFF_NO_PERMISSION_ERROR'));

					return false;
				}

				$is_customer_staff = RSTicketsProHelper::isStaff($customer->id);

				// staff trying to submit a ticket on behalf of another staff member with no permission
				if ($is_customer_staff && $customer->id != $user->id && !$permissions->add_ticket_staff)
				{
					$this->setError(Text::sprintf('RST_TICKET_EMAIL_STAFF_ERROR', $customer->email));

					return false;
				}

				// staff trying to submit a ticket on behalf of a customer with no permission
				if (!$is_customer_staff && !$permissions->add_ticket_customers)
				{
					$this->setError(Text::_('RST_TICKET_STAFF_CANNOT_ADD_TICKET_TO_CUSTOMER_ERROR'));

					return false;
				}
			}
			else
			{
				$is_customer_staff = RSTicketsProHelper::isStaff($customer->id);

				// customer trying to submit a ticket on behalf of another staff member
				if ($is_customer_staff && $customer->id != $user->id)
				{
					$this->setError(Text::sprintf('RST_TICKET_EMAIL_STAFF_ERROR', $customer->email));

					return false;
				}

				if (!$user->id && (bool) RSTicketsProHelper::getConfig('allow_password_change') && (bool) RSTicketsProHelper::checkIfEmailExists($customer->email))
				{
					$this->setError(Text::sprintf('RST_TICKET_EMAIL_CUSTOMER_ERROR', $customer->email));

					return false;
				}
			}

			$data['customer_id'] = $customer->id;
		}
		else
		{
			if (!$user->id && RSTicketsProHelper::getConfig('allow_password_change'))
			{
				if (!isset($data['password']) || !strlen(trim($data['password'])))
				{
					$this->setError(Text::_('RST_TICKET_EMPTY_PASSWORD'));
				
					return false;
				}
				
				// Password strength
				$rule 	= JFormHelper::loadRuleType('password');
				$field 	= new SimpleXMLElement('<field></field>');
				if (!$rule->test($field, $data['password']))
				{
					// Rule should throw a notice
					return false;
				}
			}
		}

		// Let's see if we have a blocklist
		if ($blocklist = RSTicketsProHelper::getConfig('blocklist'))
		{
			$blocklist = str_replace("\r\n", "\n", $blocklist);
			$blocklist = explode("\n", $blocklist);

			switch ($type)
			{
				case RST_SUBMIT_TYPE_NEW_USER:
					$email = $data['email'];
					break;

				case RST_SUBMIT_TYPE_CURRENT_USER:
				case RST_SUBMIT_TYPE_EXISTING_USER:
					$email = $customer->email;
					break;
			}

			if ($blocklist)
			{
				$found_blocklist = false;
				foreach ($blocklist as $blocked_email)
				{
					if (strpos($blocked_email, '*') !== false)
					{
						// Wildcard found
						$parts = explode('*', $blocked_email);
						foreach ($parts as $b => $part)
						{
							$parts[$b] = preg_quote($part, '/');
						}
						$pattern = '/'.implode('(.*)', $parts).'/i';
						if (preg_match($pattern, $email, $match))
						{
							$found_blocklist = true;
							break;
						}
					}
					else
					{
						// Regular address, see if it matches
						if (strtolower($email) == strtolower($blocked_email))
						{
							$found_blocklist = true;
							break;
						}
					}
				}

				if ($found_blocklist)
				{
					$this->setError(Text::sprintf('COM_RSTICKETSPRO_BLOCKLISTED_EMAIL_ERROR', htmlspecialchars($email, ENT_COMPAT, 'utf-8')));

					return false;
				}
			}
		}

		// validate the provided alternative email address if any
		if (!empty($data['alternative_email'])) {
			// remove any whitespaces
			$data['alternative_email'] = trim($data['alternative_email']);

			if (strlen($data['alternative_email']) > 0 && !JMailHelper::isEmailAddress($data['alternative_email'])) {
				$this->setError(Text::_('RST_ALTERNATIVE_EMAIL_ERROR'));
				return false;
			}
		}

		// let's validate departments
		$departments = $this->getDepartments();
		// must select a department
		if (empty($data['department_id']) || !isset($departments[$data['department_id']]))
		{
			$this->setError(Text::_('RST_TICKET_DEPARTMENT_ERROR'));

			return false;
		}
		$department = $departments[$data['department_id']];

		// validate custom fields
		$data['fields'] = array();
		if ($customFields = $this->getCustomFieldsByDepartmentId($data['department_id']))
		{
			$sentFields = isset($fields['department_' . $data['department_id']]) ? $fields['department_' . $data['department_id']] : array();
			foreach ($customFields as $field)
			{
				// freetext fields don't send a value so no need to validate them
				if ($field->type == 'freetext')
				{
					continue;
				}

				// field si required
				if ($field->required)
				{
					// set the validation message
					$validation_message = Text::_($field->validation);
					// if no validation message, go with the default one
					if (empty($validation_message))
					{
						$validation_message = Text::sprintf('RST_VALIDATION_DEFAULT_ERROR', Text::_($field->label));
					}

					// handle arrays
					if (is_array($sentFields[$field->name]))
					{
						$value = implode('', $sentFields[$field->name]);
					}
					else
					{
						$value = $sentFields[$field->name];
					}
					// no value has been sent?
					if (empty($value))
					{
						$this->setError($validation_message);

						return false;
					}
					// handle 3rd party validation
					$return = true;
					$app->triggerEvent('onRsticketsproCustomFieldValidation', array($data, $field, $value, $sentFields, &$return));
					if ($return === false)
					{
						return false;
					}
				}

				if (isset($sentFields[$field->name]))
				{
					$data['fields'][$field->id] = $sentFields[$field->name];
				}
			}
		}

		// must write a subject
		if (empty($data['subject']))
		{
			$this->setError(Text::_('RST_TICKET_SUBJECT_ERROR'));

			return false;
		}

		// must write a message
		if (empty($data['message']))
		{
			$this->setError(Text::_('RST_TICKET_MESSAGE_ERROR'));

			return false;
		}

		// must select a priority
		if (empty($data['priority_id']))
		{
			$this->setError(Text::_('RST_TICKET_PRIORITY_ERROR'));

			return false;
		}

		// let's validate files if the department allows uploads for this user
		$data['files'] = array();
		if ($department->upload)
		{
			// too many files
			if ($department->upload_files > 0 && count($files) > $department->upload_files)
			{
				$files = array_slice($files, 0, $department->upload_files);
			}

			$upload_extensions = explode(', ', $department->upload_extensions);

			foreach ($files as $file)
			{
				if ($file['error'] == UPLOAD_ERR_NO_FILE)
				{
					continue;
				}

				if ($file['error'] != UPLOAD_ERR_OK)
				{
					switch ($file['error'])
					{
						default:
							$msg = 'RST_TICKET_UPLOAD_ERROR';
							break;
						case UPLOAD_ERR_INI_SIZE:
							$msg = 'RST_TICKET_UPLOAD_ERROR_INI_SIZE';
							break;
						case UPLOAD_ERR_FORM_SIZE:
							$msg = 'RST_TICKET_UPLOAD_ERROR_FORM_SIZE';
							break;
						case UPLOAD_ERR_PARTIAL:
							$msg = 'RST_TICKET_UPLOAD_ERROR_PARTIAL';
							break;
						case UPLOAD_ERR_NO_TMP_DIR:
							$msg = 'RST_TICKET_UPLOAD_ERROR_NO_TMP_DIR';
							break;
						case UPLOAD_ERR_CANT_WRITE:
							$msg = 'RST_TICKET_UPLOAD_ERROR_CANT_WRITE';
							break;
						case UPLOAD_ERR_EXTENSION:
							$msg = 'RST_TICKET_UPLOAD_ERROR_PHP_EXTENSION';
							break;
					}

					$this->setError(Text::sprintf($msg, $file['name']));

					return false;
				}

				// is this an allowed extension?
				if (!RSTicketsProHelper::isAllowedExtension(RSTicketsProHelper::getExtension($file['name']), $upload_extensions))
				{
					$this->setError(Text::sprintf('RST_TICKET_UPLOAD_EXTENSION_ERROR', $file['name'], $department->upload_extensions));

					return false;
				}
				// check file size
				if ($department->upload_size > 0 && $file['size'] > $department->upload_size * 1048576)
				{
					$this->setError(Text::sprintf('RST_TICKET_UPLOAD_SIZE_ERROR', $file['name'], $department->upload_size));

					return false;
				}

				$data['files'][] = array(
					'src'      => 'upload',
					'tmp_name' => $file['tmp_name'],
					'name'     => $file['name']
				);
			}

            if ($department->upload_ticket_required && empty($data['files']))
            {
                $this->setError(Text::_('COM_RSTICKETSPRO_UPLOAD_TICKET_REQUIRED_ERROR'));

                return false;
            }
		}

		if ($app->isClient('site'))
		{
		    // Need to check consent
            if (RSTicketsProHelper::getConfig('forms_consent'))
            {
	            if ($isStaff && RSTicketsProHelper::getConfig('forms_consent_staff_skip'))
	            {
		            $data['consent'] = 1;
	            }

	            if (empty($data['consent']))
	            {
		            $this->setError(Text::_('COM_RSTICKETSPRO_CONSENT_IS_NEEDED_TO_SUBMIT_THIS_FORM'));
		            return false;
	            }
            }
            // no need to check for captcha in the backend
			if ($this->getHasCaptcha())
			{
				$captchaType = RSTicketsProHelper::getConfig('captcha_enabled');
				if ($captchaType == 1)
				{
					// Standard Captcha
					require_once JPATH_ADMINISTRATOR . '/components/com_rsticketspro/helpers/captcha/captcha.php';
					$captcha = new RsticketsproCaptcha;

					if (!$captcha->check($data['captcha']))
					{
						$this->setError(Text::_('RST_TICKET_CAPTCHA_ERROR'));

						return false;
					}
				}
				elseif ($captchaType > 1 && $captchaType < 5)
				{
					$response = $app->input->get('g-recaptcha-response', '', 'raw');
					$ip       = \Joomla\Utilities\IpHelper::getIp();
					$secret   = RSTicketsProHelper::getConfig('recaptcha_new_secret_key');

					try
					{
						jimport('joomla.http.factory');
						$http = JHttpFactory::getHttp();
						if ($request = $http->get('https://www.google.com/recaptcha/api/siteverify?secret=' . urlencode($secret) . '&response=' . urlencode($response) . '&remoteip=' . urlencode($ip)))
						{
							$json = json_decode($request->body);
						}
					} catch (Exception $e)
					{
						$this->setError($e->getMessage());

						return false;
					}

					if (empty($json->success) || !$json->success)
					{
						if (!empty($json) && isset($json->{'error-codes'}) && is_array($json->{'error-codes'}))
						{
							foreach ($json->{'error-codes'} as $code)
							{
								$this->setError(Text::_('RST_CAPTCHA_NEW_ERR_' . str_replace('-', '_', $code)));

								return false;
							}
						}
					}
				}
				elseif ($captchaType == 5) {
					$jcaptcha = Factory::getApplication()->get('captcha');

					if (!empty($jcaptcha)) {
						try {
							$input = Factory::getApplication()->input;
							$codeField = $input->get('rscaptcha');

							$jcaptcha = JCaptcha::getInstance($jcaptcha, array('namespace' => 'rscaptcha'));
							if (!is_null($jcaptcha) && !$jcaptcha->checkAnswer($codeField)) {
								return false;
							}
						} catch (Exception $e) {
							Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
							return false;
						}
					} else {
						Factory::getApplication()->enqueueMessage(Text::_('RST_CAPTCHA_BUILT_IN_NOT_SELECTED'), 'error');
						return false;
					}
				}
			}
		}

		// overwrite some options
		$data['id']                  = null;
		$data['staff_id']            = null;
		$data['status_id']           = RST_STATUS_OPEN;
		$data['date']                = Factory::getDate()->toSql();
		$data['last_reply']          = $data['date'];
		$data['last_reply_customer'] = 1;
		$data['replies']             = null;
		$data['autoclose_sent']      = null;
		$data['flagged']             = null;
		$data['feedback']            = null;
		$data['has_files']           = null;
		$data['time_spent']          = null;

		// fill user information
		$server          = $app->input->server;
		$data['logged']  = $user->get('id') > 0 ? 1 : 0;
		$data['agent']   = $server->get('HTTP_USER_AGENT', '', 'raw');
		$data['referer'] = $server->get('HTTP_REFERER', '', 'raw');
		$data['ip']      = \Joomla\Utilities\IpHelper::getIp();

		if (!RSTicketsProHelper::getConfig('store_ip'))
        {
            $data['ip'] = '0.0.0.0';
        }
        if (!RSTicketsProHelper::getConfig('store_user_agent'))
        {
            $data['agent'] = '';
        }

		require_once JPATH_ADMINISTRATOR . '/components/com_rsticketspro/helpers/ticket.php';

		$ticket = new RSTicketsProTicketHelper();
		$ticket->bind($data); 
		
		if (!$ticket->saveTicket())
		{
			$this->setError($ticket->getError());

			return false;
		}

		// DPE HAck to get ticket id in logs
		$session = Factory::getSession();
		$session->clear('ticketIdForUCMLog'); 
        $session->set('ticketIdForUCMLog', $ticket->ticket_id);

		// everything is correct, return true
		return true;
	}

	public function getPermissions()
	{
		return RSTicketsProHelper::getCurrentPermissions();
	}

	public function getHasCaptcha()
	{
		if (RSTicketsProHelper::getConfig('captcha_enabled'))
		{
			$enabledFor = RSTicketsProHelper::getConfig('captcha_enabled_for');
			$user       = Factory::getUser();
			$isStaff    = RSTicketsProHelper::isStaff();

			return (
				(in_array('unregistered', $enabledFor) && $user->get('guest')) || // unregistered users
				(in_array('customers', $enabledFor) && !$isStaff) || // customers
				(in_array('staff', $enabledFor) && $isStaff) // staff members
			);
		}

		return false;
	}
}