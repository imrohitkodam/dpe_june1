<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproControllerKbconvert extends JControllerLegacy
{
	public function cancel()
	{
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));
		
		$input 		= JFactory::getApplication()->input;
		$data  		= $input->get('jform', array(), 'array');
		$ticketId 	= $data['ticket_id'];
		$this->setRedirect(JRoute::_('index.php?option=com_rsticketspro&view=ticket&id=' . $ticketId, false));
	}
	
	public function save()
	{
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));
		
		$input 		= JFactory::getApplication()->input;
		$data  		= $input->get('jform', array(), 'array');
		$ticketId 	= $data['ticket_id'];
		
		$model = $this->getModel('kbconvert');
		if (!$model->save($data))
		{
			$this->setMessage($model->getError(), 'error');
		}
		else
		{
			$this->setMessage(JText::_('RST_KB_ARTICLE_SAVED_OK', 'info'));
		}
		
		$this->setRedirect(JRoute::_('index.php?option=com_rsticketspro&view=ticket&id=' . $ticketId, false));
	}
	
	public function manual()
	{
		// Check for request forgeries
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		$ticketId = JFactory::getApplication()->input->getInt('id');
		$model 	  = $this->getModel('ticket');
		
		// small check to determine if it's already been converted
		if ($article = $model->isConvertedToKB($ticketId))
		{
			$url = JRoute::_('index.php?option=com_rsticketspro&task=kbarticle.edit&id='.$article->id);
			$this->setRedirect(JRoute::_('index.php?option=com_rsticketspro&view=ticket&id='.$ticketId, false), JText::sprintf('RST_KB_ALREADY_CONVERTED', $url, $article->name), 'notice');
		}
		else
		{
			$this->setRedirect(JRoute::_('index.php?option=com_rsticketspro&view=kbconvert&ticket_id=' . $ticketId, false));
		}
	}
	
	public function automatic()
	{
		// Check for request forgeries
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));
		
		$db		  = JFactory::getDbo();
		$ticketId = JFactory::getApplication()->input->getInt('id');
		$model 	  = $this->getModel('ticket');

		$this->setMessage(JText::_('RST_KB_NO_RULE'), 'notice');
		$this->setRedirect(JRoute::_('index.php?option=com_rsticketspro&view=ticket&id=' . $ticketId, false));
		
		// small check to determine if it's already been converted
		if ($article = $model->isConvertedToKB($ticketId))
		{
			$url = JRoute::_('index.php?option=com_rsticketspro&task=kbarticle.edit&id=' . $article->id);
			$this->setMessage(JText::sprintf('RST_KB_ALREADY_CONVERTED', $url, $article->name), 'notice');
			return false;
		}

		// get ticket
		$ticket 		= $model->getItem();
		$ticketMessages = $model->getTicketMessages($ticketId, true);

		// get rules
		$query = $db->getQuery(true);
		$query->select('*')
			  ->from($db->qn('#__rsticketspro_kb_rules'))
			  ->where($db->qn('published').'='.$db->q(1));
		$db->setQuery($query);
		$rules = $db->loadObjectList();
		foreach ($rules as $rule)
		{
			$result = array();
			$rule->conditions = unserialize($rule->conditions);
			if (!empty($rule->conditions))
			{
				$conditionCount = count($rule->conditions);
				$hasOr = false;

				foreach ($rule->conditions as $conditionCounter => $condition)
				{
					if ($condition->connector == 'OR' && $conditionCounter < $conditionCount - 1)
					{
						$hasOr = true;
					}

					switch ($condition->type)
					{
						case 'department':
							if ($condition->condition == 'eq')
							{
								$result[] = $ticket->department_id == $condition->value;
							}
							elseif ($condition->condition == 'neq')
							{
								$result[] = $ticket->department_id != $condition->value;
							}
							elseif ($condition->condition == 'like')
							{
								$result[] = strpos($ticket->department, $condition->value) !== false;
							}
							elseif ($condition->condition == 'notlike')
							{
								$result[] = strpos($ticket->department, $condition->value) === false;
							}
						break;
						
						case 'subject':
							if ($condition->condition == 'eq')
							{
								$result[] = $ticket->subject == $condition->value;
							}
							elseif ($condition->condition == 'neq')
							{
								$result[] = $ticket->subject != $condition->value;
							}
							elseif ($condition->condition == 'like')
							{
								$result[] = strpos($ticket->subject, $condition->value) !== false;
							}
							elseif ($condition->condition == 'notlike')
							{
								$result[] = strpos($ticket->subject, $condition->value) === false;
							}
						break;
						
						case 'priority':
							if ($condition->condition == 'eq')
							{
								$result[] = $ticket->priority_id == $condition->value;
							}
							elseif ($condition->condition == 'neq')
							{
								$result[] = $ticket->priority_id != $condition->value;
							}
							elseif ($condition->condition == 'like')
							{
								$result[] = strpos($ticket->priority->name, $condition->value) !== false;
							}
							elseif ($condition->condition == 'notlike')
							{
								$result[] = strpos($ticket->priority->name, $condition->value) === false;
							}
						break;
						
						case 'status':
							if ($condition->condition == 'eq')
							{
								$result[] = $ticket->status_id == $condition->value;
							}
							elseif ($condition->condition == 'neq')
							{
								$result[] = $ticket->status_id != $condition->value;
							}
							elseif ($condition->condition == 'like')
							{
								$result[] = strpos($ticket->status->name, $condition->value) !== false;
							}
							elseif ($condition->condition == 'notlike')
							{
								$result[] = strpos($ticket->status->name, $condition->value) === false;
							}
						break;
						
						case 'message':
							if ($condition->condition == 'eq')
							{
								$tmp = false;
								foreach ($ticketMessages as $message)
								{
									if ($message->message == $condition->value)
									{
										$tmp = true;
										break;
									}
								}
								
								$result[] = $tmp;
							}
							elseif ($condition->condition == 'neq')
							{
								$tmp = true;
								foreach ($ticketMessages as $message)
								{
									if ($message->message == $condition->value)
									{
										$tmp = false;
										break;
									}
								}
								
								$result[] = $tmp;
							}
							elseif ($condition->condition == 'like')
							{
								$tmp = false;
								foreach ($ticketMessages as $message)
								{
									if (strpos($message->message, $condition->value) !== false)
									{
										$tmp = true;
										break;
									}
								}
								
								$result[] = $tmp;
							}
							elseif ($condition->condition == 'notlike')
							{
								$tmp = true;
								foreach ($ticketMessages as $message)
								{
									if (strpos($message->message, $condition->value) !== false)
									{
										$tmp = false;
										break;
									}
								}
								
								$result[] = $tmp;
							}
						break;
						
						case 'custom_field':
							$query = $db->getQuery(true);
							$query->select($db->qn('cfv.value'))
								  ->select($db->qn('cf.type'))
								  ->from($db->qn('#__rsticketspro_custom_fields_values', 'cfv'))
								  ->join('left', $db->qn('#__rsticketspro_custom_fields', 'cf').' ON ('.$db->qn('cf.id').'='.$db->qn('cfv.custom_field_id').')')
								  ->where($db->qn('cfv.custom_field_id').'='.$db->q($condition->custom_field))
								  ->where($db->qn('cfv.ticket_id').'='.$db->q($ticketId))
								  ->where($db->qn('cf.published').'='.$db->q(1));
							$db->setQuery($query);
							if ($field = $db->loadObject())
							{
								$value = $field->value;
								$types = array('select', 'multipleselect', 'checkbox', 'radio');
								
								if ($condition->condition == 'eq')
								{
									if (in_array($field->type, $types))
									{
										$value = explode("\n", $value);
									}
									
									if (is_array($value))
									{
										$tmp = false;
										foreach ($value as $val)
										{
											if ($val == $condition->value)
											{
												$tmp = true;
												break;
											}
										}
										
										$result[] = $tmp;
									}
									else
									{
										$result[] = $value == $condition->value;
									}
								}
								elseif ($condition->condition == 'neq')
								{
									if (in_array($field->type, $types))
									{
										$value = explode("\n", $value);
									}
									
									if (is_array($value))
									{
										$tmp = true;
										foreach ($value as $val)
										{
											if ($val == $condition->value)
											{
												$tmp = false;
												break;
											}
										}
										
										$result[] = $tmp;
									}
									else
									{
										$result[] = $value != $condition->value;
									}
								}
								elseif ($condition->condition == 'like')
								{									
									$result[] = strpos($value, $condition->value) !== false;
								}
								elseif ($condition->condition == 'notlike')
								{
									$result[] = strpos($value, $condition->value) === false;
								}
							}
							else
							{
								$result[] = false;
							}
						break;
					}
				}

				// No 'OR' clause means all results should be true because we're using 'AND'
				if (!$hasOr)
				{
					$result = !in_array(false, $result);
				}
				else
				{
					// Search for a single true value in the array
					$result = in_array(true, $result);
				}

				// Found rule
				if ($result)
				{
					$params = (object) array(
						'name' 				=> $ticket->subject,
						'category_id' 		=> $rule->category_id,
						'publish_article' 	=> $rule->publish_article,
						'private' 			=> $rule->private
					);
					require_once JPATH_ADMINISTRATOR . '/components/com_rsticketspro/helpers/ticket.php';

					try
					{
						RSTicketsProTicketHelper::convert($ticket, $ticketMessages, $params);
						$this->setMessage(JText::sprintf('RST_KB_ARTICLE_SAVED_OK_AUTOMATIC', $rule->name));
						return true;
					}
					catch (Exception $e)
					{
						$this->setMessage($e->getMessage(), 'error');
						return false;
					}
				}
			}
		}

		return false;
	}
}