<?php
/**
 * @package RSform!Pro
 * @copyright (C) www.rsjoomla.com
 * @license GPL, http://www.gnu.org/copyleft/gpl.html
 */

 use Joomla\CMS\Plugin\CMSPlugin;
 use Joomla\CMS\Plugin\Plugin;

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

class plgSystemRsfprsticketspro extends CMSPlugin
{
	protected $autoloadLanguage = true;

	protected function canRun()
	{
		if (file_exists(JPATH_ADMINISTRATOR.'/components/com_rsticketspro/helpers/rsticketspro.php'))
		{
			require_once JPATH_ADMINISTRATOR.'/components/com_rsticketspro/helpers/rsticketspro.php';
			return true;
		}

		return false;
	}

	public function onRsformFormSave($form)
	{
		$app                    = JFactory::getApplication();
		$data                   = $app->input->post->get('rstp', array(), 'array');
		$data['form_id']        = $form->FormId;
		$data['rstp_mappings']  = array();
		$mappings               = $app->input->post->get('rstp_mappings', array(), 'array');

		try
		{
			if (!empty($mappings))
			{
				foreach ($mappings as $field_name => $value)
				{
					// we must keep just one placeholder for the files
					if ($field_name == 'files' && strlen($value) > 0)
					{
						preg_match_all('/\{(.*?)\}/m', $value, $matches, PREG_SET_ORDER, 0);

						if (count($matches) == 0)
						{
							throw new Exception(JText::_('PLG_SYSTEM_RSFPRSTICKETSPRO_ERROR_FILE_PLACEHOLDER'));
						}
						else
						{
							// if multiple placeholders found then we need to keep just one or just keep the one that its specified and remove any other chars
							$value = trim($matches[0][0]);
						}
					}

					$data['rstp_mappings'][$field_name] = $value;
				}
			}
		}
		catch (Exception $e)
		{
			$app->enqueueMessage($e->getMessage(), 'warning');
			return false;
		}

		$row = $this->getTable();
		if (!$row)
		{
			return false;
		}

		if (!$row->save($data))
		{
			$app->enqueueMessage($row->getError(), 'error');
		}
	}

	protected function getTable()
	{
		return JTable::getInstance('RSForm_Rsticketspro', 'Table');
	}

	public function onRsformBackendFormCopy($args)
	{
		$formId 	= $args['formId'];
		$newFormId 	= $args['newFormId'];

		/* @var $row TableRSForm_Rsticketspro */
		if ($row = $this->getTable())
		{
			if ($row->load($formId))
			{
				$row->bind(array('form_id' => $newFormId));

				$row->check();

				return $row->store();
			}
		}
	}

	public function onRsformFormDelete($formId)
	{
		if ($row = $this->getTable())
		{
			$row->delete($formId);
		}
	}

	public function onRsformBackendAfterShowFormEditTabsTab()
	{
		?>
		<li><a href="javascript: void(0);" id="rsticketspro"><span class="rsficon rsficon-ticket"></span><span class="inner-text"><?php echo JText::_('PLG_SYSTEM_RSFPRSTICKETSPRO_SETTINGS'); ?></span></a></li>
		<?php
	}

	protected function getDepartments($options = true)
	{
		$departments = RSTicketsProHelper::getDepartments(false);

		if ($options)
		{
			return $departments;
		}
		else
		{
			$departments_ids = array();
			if (!empty($departments))
			{
				foreach ($departments as $dep)
				{
					$departments_ids[] = $dep->value;
				}
			}

			return $departments_ids;
		}
	}

	public function onRsformBackendAfterShowFormEditTabs()
	{
		if (!$this->canRun())
		{
			?>
			<div id="rsticketsprodiv">
				<div class="alert alert-error">
					<h4 class="alert-heading"><?php echo JText::_('PLG_SYSTEM_RSFPRSTICKETSPRO_ERROR');?></h4>
					<div class="alert-message"><?php echo JText::_('PLG_SYSTEM_RSFPRSTICKETSPRO_ERROR_NO_RSTICKETSPRO');?></div>
				</div>
			</div>
			<?php

			return;
		}

		$formId = JFactory::getApplication()->input->getInt('formId');
		$db 	= JFactory::getDbo();
		$row 	= $this->getTable();

		if (!$row)
		{
			return;
		}

		$row->load($formId);

		// Fields
		$lists = array();

		// Boolean inputs
		$rstp_published 	= empty($row->rstp_published) ? 0 : 1;
		$rstp_debug 		= empty($row->rstp_debug) ? 0 : 1;

		$lists['debug'] 	         = RSFormProHelper::renderHTML('select.booleanlist', 'rstp[rstp_debug]', '', $rstp_debug);
		$lists['published']          = RSFormProHelper::renderHTML('select.booleanlist', 'rstp[rstp_published]', '', $rstp_published);
		$lists['trigger_on_payment'] = RSFormProHelper::renderHTML('select.booleanlist', 'rstp[rstp_trigger_on_payment]', '', $row->rstp_trigger_on_payment);

		$hasPayment = JPluginHelper::isEnabled('system', 'rsfppayment');

		$custom_fields 	= array();
		$mappings 		= array();
		$departments 	= $this->getDepartments();

		// the default fields are always present
		$core_fields = array(
			'department_id'     => array('label' => JText::_('PLG_SYSTEM_RSFPRSTICKETSPRO_FIELD_DEPARTMENT_ID'), 'values' => ''),
			'name' 			    => array('label' => JText::_('PLG_SYSTEM_RSFPRSTICKETSPRO_FIELD_NAME')),
			'email' 		    => array('label' => JText::_('PLG_SYSTEM_RSFPRSTICKETSPRO_FIELD_EMAIL')),
			'alternative_email' => array('label' => JText::_('PLG_SYSTEM_RSFPRSTICKETSPRO_FIELD_ALTERNATIVE_EMAIL')),
			'subject' 		    => array('label' => JText::_('PLG_SYSTEM_RSFPRSTICKETSPRO_FIELD_SUBJECT')),
			'message' 		    => array('label' => JText::_('PLG_SYSTEM_RSFPRSTICKETSPRO_FIELD_MESSAGE'), 'type'=>'editor'),
			'priority_id' 	    => array('label' => JText::_('PLG_SYSTEM_RSFPRSTICKETSPRO_FIELD_PRIORITY'), 'values' => ''),
			'files' 		    => array('label' => JText::_('PLG_SYSTEM_RSFPRSTICKETSPRO_FIELD_FILES')),
		);

		if (!RSTicketsProHelper::getConfig('show_alternative_email'))
		{
			unset($core_fields['alternative_email']);
		}

		// add the priorities
		$query = $db->getQuery(true);

		$query->select('*')
			->from($db->qn('#__rsticketspro_priorities'))
			->where($db->qn('published') . '=' . $db->q(1))
			->order($db->qn('ordering') . ' ' . $db->escape('asc'));
		$db->setQuery($query);

		if ($priorities = $db->loadObjectList())
		{
			foreach ($priorities as $priority)
			{
				$core_fields['priority_id']['values'] .= $priority->id.'|'.$priority->name."\n";
			}
		}

		// correlate the departments with they'r ids
		$departments_correlation = array();
		if (!empty($departments))
		{
			foreach ($departments as $dep)
			{
				$departments_correlation[$dep->value] = $dep->text;
				// add the possible values for the department_id core field
				$core_fields['department_id']['values'] .=  $dep->value.'|'.$dep->text."\n";

				// handle the custom fields for the selected departments
				$custom_fields[$dep->value] = $this->getDepartmentFields($dep->value);
			}
		}

		if (!empty($row->rstp_mappings))
		{
			$mappings = $row->rstp_mappings;
		}

		$editor = RSFormProHelper::getEditor();

		?>
		<div id="rsticketsprodiv">
			<div class="row-fluid">
				<div class="span12">
					<fieldset>
						<legend><?php echo JText::_('PLG_SYSTEM_RSFPRSTICKETSPRO_SETTINGS'); ?></legend>
						<table width="100%" class="table table-bordered">
							<tr>
								<td width="80" align="right" nowrap="nowrap" class="key">
									<span class="hasTooltip" title="<?php echo JText::_('PLG_SYSTEM_RSFPRSTICKETSPRO_USE_DESC'); ?>"><?php echo JText::_('PLG_SYSTEM_RSFPRSTICKETSPRO_USE'); ?></span>
								</td>
								<td nowrap="nowrap" colspan="2"><?php echo $lists['published']; ?></td>
							</tr>
							<?php if ($hasPayment) { ?>
								<tr>
									<td width="80" align="right" nowrap="nowrap" class="key">
										<span class="hasTooltip" title="<?php echo JText::_('PLG_SYSTEM_RSFPRSTICKETSPRO_TRIGGER_ON_PAYMENT_DESC'); ?>"><?php echo JText::_('PLG_SYSTEM_RSFPRSTICKETSPRO_TRIGGER_ON_PAYMENT'); ?></span>
									</td>
									<td nowrap="nowrap" colspan="2"><?php echo $lists['trigger_on_payment']; ?></td>
								</tr>
							<?php } ?>
							<tr>
								<td width="80" align="right" nowrap="nowrap" class="key">
									<span class="hasTooltip" title="<?php echo JText::_('PLG_SYSTEM_RSFPRSTICKETSPRO_DEBUG_DESC'); ?>"><?php echo JText::_('PLG_SYSTEM_RSFPRSTICKETSPRO_DEBUG'); ?></span>
								</td>
								<td nowrap="nowrap" colspan="2"><?php echo $lists['debug']; ?></td>
							</tr>
						</table>
					</fieldset>
					<?php if ($core_fields) { ?>
						<fieldset>
							<legend><?php echo JText::_('PLG_SYSTEM_RSFPRSTICKETSPRO_CORE_FIELDS'); ?></legend>
							<table class="table table-bordered">
								<?php
								$i = 0;
								foreach ($core_fields as $field => $data)
								{ ?>
									<tr>
										<td width="80" align="right" nowrap="nowrap"
											class="key">
											<label for="rstp_mappings_<?php echo $i; ?>"><?php echo RSFormProHelper::htmlEscape($data['label']); ?></label>
											<?php if ($field == 'files') { ?>
												<br/><small><?php echo JText::_('PLG_SYSTEM_RSFPRSTICKETSPRO_FIELD_FILES_DESC');?></small>
											<?php } ?>
										</td>
										<td>
											<?php
											$filter_attributes = 'data-delimiter=" " data-placeholders="display"';
											if ($field == 'files')
											{
												$filter_attributes = 'data-filter-type="include" data-delimiter=" " data-filter="localpath" data-placeholders="display"';
											}
											?>
											<?php if (isset($data['type']) && $data['type'] == 'editor') { ?>
												<div style="overflow: hidden">
													<?php echo $editor->display('rstp_mappings['.$field.']', (isset($mappings[$field]) ? htmlentities($mappings[$field], ENT_COMPAT, 'UTF-8') : ''), 500, 320, 70, 10); ?>
												</div>
											<?php } else { ?>
											<input name="rstp_mappings[<?php echo $field; ?>]" style="width: 80%;"
											       type="text"
												   id="rstp_mappings_<?php echo $i; ?>"
												   value="<?php echo(isset($mappings[$field]) ? RSFormProHelper::htmlEscape($mappings[$field]) : ''); ?>"
												   class="rs_inp rs_80" <?php echo $filter_attributes; ?>/>
											<?php } ?>
											<?php if (isset($data['values'])) { ?>
												<br/><?php echo JText::_('PLG_SYSTEM_RSFPRSTICKETSPRO_POSSIBLE_VALUES');?><br/>
												<?php
												if ($field == 'department_id')
												{
													echo '<div id="department_possible">';
												}
												$values = RSFormProHelper::htmlEscape($data['values']);
												echo nl2br($values);
												if ($field == 'department_id')
												{
													echo '</div>';
												}
												?>
											<?php } ?>
										</td>
									</tr>
								<?php
									$i++;
								}
								?>
							</table>
						</fieldset>
					<?php  } ?>

					<div id="rsf_rsticketspro_custom_fields">
						<?php if ($custom_fields) { $i = 0; ?>
							<?php foreach ($custom_fields as $department_id => $c_fields) {
								if (empty($c_fields))
								{
									continue;
								}
								?>
								<fieldset>
									<legend><?php echo JText::sprintf('PLG_SYSTEM_RSFPRSTICKETSPRO_DEPARTMENT_CUSTOM_FIELDS', $departments_correlation[$department_id]); ?></legend>
									<table class="table table-bordered">
										<?php
										foreach ($c_fields as $c_field => $c_data)
										{
											?>
											<tr>
												<td width="80" align="right" nowrap="nowrap" class="key"><label for="rstp_mappings_custom_<?php echo $i; ?>"><?php echo RSFormProHelper::htmlEscape($c_data['label']); ?></label></td>
												<td>
													<input type="text" name="rstp_mappings[custom_fields][<?php echo $department_id;?>][<?php echo $c_field; ?>]" style="width: 80%;" id="rstp_mappings_custom_<?php echo $i; ?>"  value="<?php echo (isset($mappings['custom_fields'][$department_id][$c_field]) ? RSFormProHelper::htmlEscape($mappings['custom_fields'][$department_id][$c_field]) : ''); ?>" class="rs_inp rs_80"  data-delimiter=" " data-placeholders="display" />
													<?php if (isset($c_data['values'])) { ?>
														<br/><?php echo JText::_('PLG_SYSTEM_RSFPRSTICKETSPRO_POSSIBLE_VALUES');?><br/>
														<?php
														$values = RSFormProHelper::htmlEscape($c_data['values']);
														echo nl2br($values);?>
													<?php } ?>
												</td>
											</tr>
											<?php
											$i++;
										}
										?>
									</table>
								</fieldset>
							<?php } ?>
						<?php } ?>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	private function getSettings($formId)
	{
		static $cache = array();

		if (!isset($cache[$formId]))
		{
			$row = $this->getTable();
			if ($row->load($formId) && $row->rstp_published)
			{
				$cache[$formId] = $row;
			}
			else
			{
				$cache[$formId] = false;
			}
		}

		if (!empty($cache[$formId]))
		{
			return clone $cache[$formId];
		}

		return false;
	}

	private function getDepartmentFields($department_id)
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query->select('*')
			->from($db->qn('#__rsticketspro_custom_fields'))
			->where($db->qn('department_id') . '=' . $db->q($department_id))
			->where($db->qn('published') . '=' . $db->q(1))
			->order($db->qn('ordering') . ' ' . $db->escape('asc'));
		$db->setQuery($query);

		$custom_fields = $db->loadObjectList();

		$fields = array();
		if ($custom_fields)
		{
			foreach ($custom_fields as $c_field)
			{
				$data = array('label' => $c_field->label);
				if (!empty($c_field->values))
				{
					$data['values'] = $c_field->values;
				}
				$fields[$c_field->id] = $data;
			}
		}

		return $fields;
	}

	public function onRsformAfterConfirmPayment($SubmissionId)
	{
		require_once JPATH_ADMINISTRATOR . '/components/com_rsform/helpers/submissions.php';
		$submission = RSFormProSubmissionsHelper::getSubmission($SubmissionId, false);

		if ($submission)
		{
			if ($row = $this->getSettings($submission->FormId))
			{
				if ($row->rstp_trigger_on_payment)
				{
					$this->createTicket($submission->FormId, $SubmissionId, $row);
				}
			}
		}
	}

	/*
	* Functions used only inside this class
	*/
	public function onRsformFrontendAfterFormProcess($args)
	{
		$formId 		= (int) $args['formId'];
		$SubmissionId 	= (int) $args['SubmissionId'];

		if ($row = $this->getSettings($formId))
		{
			if (empty($row->rstp_trigger_on_payment))
			{
				$this->createTicket($formId, $SubmissionId, $row);
			}
		}
	}

	private function createTicket($formId, $SubmissionId, $row)
	{
		if (!$this->canRun())
		{
			return;
		}

		$db = JFactory::getDbo();

		list($replace, $with) = RSFormProHelper::getReplacements($SubmissionId);
		$replace[] = '\n';
		$with[] = "\n";

		if (empty($row->rstp_mappings))
		{
			return;
		}

		if (empty($row->rstp_mappings['department_id']))
		{
			return;
		}

		$data = $row->rstp_mappings;

		$data['department_id'] = str_replace($replace, $with, $data['department_id']);

		// just one department
		$data['department_id'] = (int) $data['department_id'];

		$available_departments = $this->getDepartments(false);

		if (!in_array($data['department_id'], $available_departments)) {
			return;
		}

		// will use this variable for writing purpose
		$dep_id = $data['department_id'];

		// check if the department selected has the files field
		$settings = RSTicketsProHelper::getDepartment($dep_id);

		if (empty($settings->upload))
		{
			unset($data['files']);
		}

		// keep just the selected department custom fields
		if (isset($data['custom_fields']) && isset($data['custom_fields'][$dep_id])) {
			$data['custom_fields'] = $data['custom_fields'][$dep_id];
		}


		$file_upload_name = '';
		foreach ($data as $colname => $value)
		{
			if ($colname == 'custom_fields')
			{
				foreach ($value as $c_field_id => $c_value)
				{
					$data[$colname][$c_field_id] = str_replace($replace, $with, $c_value);
				}
			}
			else
			{
				if ($colname == 'files')
				{
					$file_upload_name = str_replace(array('{', ':localpath', '}'), '', $value);
				}
				$data[$colname] = str_replace($replace, $with, $value);
			}
		}

		try
		{
			$errors = array();
			// check the email field
			if (empty($data['email']) || !JMailHelper::isEmailAddress($data['email']))
			{
				$errors[] = JText::_('PLG_SYSTEM_RSFPRSTICKETSPRO_ERROR_EMAIL_ADDRESS');
			}

			if (!empty($data['alternative_email']) && !JMailHelper::isEmailAddress($data['alternative_email']))
			{
				$errors[] = JText::_('PLG_SYSTEM_RSFPRSTICKETSPRO_ERROR_ALTERNATIVE_EMAIL_ADDRESS');
			}

			// must write a subject
			if (empty($data['subject']))
			{
				$errors[] = JText::_('PLG_SYSTEM_RSFPRSTICKETSPRO_ERROR_SUBJECT');
			}

			// must write a message
			if (empty($data['message']))
			{
				$errors[] = JText::_('PLG_SYSTEM_RSFPRSTICKETSPRO_ERROR_MESSAGE');
			}

			// must select a priority
			if (empty($data['priority_id']))
			{
				$errors[] = JText::_('PLG_SYSTEM_RSFPRSTICKETSPRO_ERROR_PRIORITY');
			}

			if (!empty($errors))
			{
				$errors = implode('<br/>', $errors);
				throw new Exception($errors);
			}

			// the priority must be integer
			$data['priority_id'] = (int) $data['priority_id'];

			// split the data
			$data['fields'] = isset($data['custom_fields']) ? $data['custom_fields'] : array();

			// unset custom fields
			if (isset($data['custom_fields']))
			{
				unset($data['custom_fields']);
			}

			// default separator
			$separator = '<br />';
			$multiple = false;

			// try to identify the upload field and its configured separator
			if (!empty($file_upload_name))
			{
				if ($component_id = RSFormProHelper::getComponentId($file_upload_name, $formId))
				{
					$component_properties = RSFormProHelper::getComponentProperties($component_id);

					$separator = $component_properties['FILESSEPARATOR'];
					if ($component_properties['MULTIPLE'] === 'YES')
					{
						$multiple = true;
					}
				}
			}

			if (!empty($data['files']) && $multiple && $separator)
			{
				$data['files'] = explode($separator, $data['files']);
			}

			$department = JTable::getInstance('Departments','RsticketsproTable');

			// load department
			$department->load($dep_id);
			$upload_extensions = str_replace("\r\n", "\n", $department->upload_extensions);
			$upload_extensions = explode("\n", $upload_extensions);

			if (!empty($data['files']))
			{
				if (!is_array($data['files']))
				{
					$data['files'] = array($data['files']);
				}

				foreach ( $data['files'] as $i => $file)
				{
					// Check if this attachment is allowed
					if (!RSTicketsProHelper::isAllowedExtension(RSTicketsProHelper::getExtension($file), $upload_extensions))
					{
						unset($data['files'][$i]);
						continue;
					}

					$file_contents = file_get_contents($file);
					if ($department->upload_size > 0 && strlen($file_contents) > $department->upload_size*1048576)
					{

						unset($data['files'][$i]);
						continue;
					}

					$file_name = explode(DIRECTORY_SEPARATOR, $file);
					$file_name = array_pop($file_name);

					$data['files'][$i] = array(
						'src' => 'cron',
						'filename' => $file_name,
						'contents' => $file_contents,
					);
				}
			}

			$query = $db->getQuery(true)
				->select($db->qn('id'))
				->from($db->qn('#__users'))
				->where($db->qn('email').' LIKE '.$db->q($data['email']));

			$db->setQuery($query);

			$data['customer_id'] = (int) $db->loadResult();

			$user = JFactory::getUser();
			if ($user->get('id') > 0)
			{
				$data['user_id'] = $user->get('id');
			}

			$data['id']                  = null;
			$data['staff_id']            = null;
			$data['status_id']           = RST_STATUS_OPEN;
			$data['date']                = JFactory::getDate()->toSql();
			$data['last_reply']          = $data['date'];
			$data['last_reply_customer'] = 1;

			// fill user information
			$server          = JFactory::getApplication()->input->server;
			$data['agent']   = $server->get('HTTP_USER_AGENT', '', 'none');
			$data['referer'] = $server->get('HTTP_REFERER', '', 'none');
			$data['ip']      = $server->get('REMOTE_ADDR', '', 'none');
			$data['logged']  = $user->get('id') > 0 ? 1 : 0;

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
				throw new Exception($ticket->getError());
			}
			else if(!empty($ticket->ticket_id))
			{
				// If the editor is turned off
				if (!RSTicketsProHelper::getConfig('allow_rich_editor'))
				{
					// the html option in this case must always be true
					$query = $db->getQuery(true);

					$query->update($db->qn('#__rsticketspro_ticket_messages'))
						->set($db->qn('html') . ' = ' . $db->q(1))
						->where($db->qn('ticket_id') . ' = ' . $db->q($ticket->ticket_id));

					$db->setQuery($query);
					$db->execute();
				}
			}
		}
		catch (Exception $e)
		{
			if ($row->rstp_debug)
			{
				JFactory::getApplication()->enqueueMessage(JText::sprintf('PLG_SYSTEM_RSFPRSTICKETSPRO_ERROR_ADDING_TICKET', $e->getMessage()), 'warning');
			}
		}
	}

	/*
	* End Functions used only inside this class
	*/
	public function onRsformFormBackup($form, $xml, $fields)
	{
		if ($row = $this->getTable())
		{
			if ($row->load($form->FormId))
			{
				$row->check();

				$data = $row->getProperties();
				unset($data['form_id']);

				$xml->add('rsticketspro');
				foreach ($data as $property => $value)
				{
					$xml->add($property, $value);
				}
				$xml->add('/rsticketspro');
			}
		}
	}

	public function onRsformFormRestore($form, $xml, $fields)
	{
		if (isset($xml->rsticketspro))
		{
			$data = array(
				'form_id' => $form->FormId
			);

			foreach ($xml->rsticketspro->children() as $property => $value)
			{
				$data[$property] = (string) $value;
			}

			$row = $this->getTable();
			$row->save($data);
		}
	}
}