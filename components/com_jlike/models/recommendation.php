<?php
/**
 * @package     JLike
 * @subpackage  com_jlike
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access.
defined('_JEXEC') or die;
use Joomla\CMS\Object\CMSObject;
use Joomla\CMS\Table\Table;
use Joomla\CMS\MVC\Model\ItemModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;
use Joomla\Registry\Registry;
use Joomla\CMS\Plugin\PluginHelper;

//DPE hack
use Joomla\CMS\Language\Text;


jimport('techjoomla.tjnotifications.tjnotifications');

use Joomla\Utilities\ArrayHelper;
/**
 * Jlike model.
 *
 * @since  1.6
 */
class JlikeModelRecommendation extends ItemModel
{
	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @return void
	 *
	 * @since    1.6
	 *
	 */
	protected function populateState()
	{
		$app = Factory::getApplication('com_jlike');

		// Load state from the request userState on edit or from the passed variable on default
		if (Factory::getApplication()->input->get('layout') == 'edit')
		{
			$id = Factory::getApplication()->getUserState('com_jlike.edit.recommendation.id');
		}
		else
		{
			$id = Factory::getApplication()->input->get('id');
			Factory::getApplication()->setUserState('com_jlike.edit.recommendation.id', $id);
		}

		$this->setState('recommendation.id', $id);

		// Load the parameters.
		$params       = ComponentHelper::getParams('com_jlike');
		$params_array = $params->toArray();

		if (isset($params_array['item_id']))
		{
			$this->setState('recommendation.id', $params_array['item_id']);
		}

		$this->setState('params', $params);
	}

	/**
	 * Method to get an object.
	 *
	 * @param   integer  $id  The id of the object to get.
	 *
	 * @return  mixed    Object on success, false on failure.
	 */
	public function &getData($id = null)
	{
		if ($this->_item === null)
		{
			$this->_item = false;

			if (empty($id))
			{
				$id = $this->getState('recommendation.id');
			}

			// Get a level row instance.
			$table = $this->getTable();

			// Attempt to load the row.
			if ($table->load($id))
			{
				// Check published state.
				if ($published = $this->getState('filter.published'))
				{
					if ($table->state != $published)
					{
						return $this->_item;
					}
				}

				// Convert the JTable to a clean JObject.
				$properties  = $table->getProperties(1);
				$this->_item = ArrayHelper::toObject($properties, 'stdClass');

			}
		}

		if (isset($this->_item->created_by) )
		{
			$this->_item->created_by_name = Factory::getUser($this->_item->created_by)->name;
		}

		return $this->_item;
	}

	/**
	 * Get an instance of Table class
	 *
	 * @param   string  $type    Name of the Table class to get an instance of.
	 * @param   string  $prefix  Prefix for the table class name. Optional.
	 * @param   array   $config  Array of configuration values for the Table object. Optional.
	 *
	 * @return  Table|bool Table if success, false on failure.
	 */
	public function getTable($type = 'Recommendation', $prefix = 'JlikeTable', $config = array())
	{
		$this->addTablePath(JPATH_ADMINISTRATOR . '/components/com_jlike/tables');

		return Table::getInstance($type, $prefix, $config);
	}

	/**
	 * Get the id of an item by alias
	 *
	 * @param   string  $alias  Item alias
	 *
	 * @return  mixed
	 */
	public function getItemIdByAlias($alias)
	{
		$table = $this->getTable();

		$table->load(array('alias' => $alias));

		return $table->id;
	}

	/**
	 * Method to check in an item.
	 *
	 * @param   integer  $id  The id of the row to check out.
	 *
	 * @return  boolean True on success, false on failure.
	 *
	 * @since    1.6
	 */
	public function checkin($id = null)
	{
		// Get the id.
		$id = (!empty($id)) ? $id : (int) $this->getState('recommendation.id');

		if ($id)
		{
			// Initialise the table
			$table = $this->getTable();

			// Attempt to check the row in.
			if (method_exists($table, 'checkin'))
			{
				if (!$table->checkin($id))
				{
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Method to check out an item for editing.
	 *
	 * @param   integer  $id  The id of the row to check out.
	 *
	 * @return  boolean True on success, false on failure.
	 *
	 * @since    1.6
	 */
	public function checkout($id = null)
	{
		// Get the user id.
		$id = (!empty($id)) ? $id : (int) $this->getState('recommendation.id');

		if ($id)
		{
			// Initialise the table
			$table = $this->getTable();

			// Get the current user object.
			$user = Factory::getUser();

			// Attempt to check the row out.
			if (method_exists($table, 'checkout'))
			{
				if (!$table->checkout($user->get('id'), $id))
				{
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Get the name of a category by id
	 *
	 * @param   int  $id  Category id
	 *
	 * @return  Object|null	Object if success, null in case of failure
	 */
	public function getCategoryName($id)
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true);
		$query
			->select('title')
			->from('#__categories')
			->where('id = ' . $id);
		$db->setQuery($query);

		return $db->loadObject();
	}

	/**
	 * Publish the element
	 *
	 * @param   int  $id     Item id
	 * @param   int  $state  Publish state
	 *
	 * @return  boolean
	 */
	public function publish($id, $state)
	{
		$table = $this->getTable();
		$table->load($id);
		$table->state = $state;

		return $table->store();
	}

	/**
	 * Method to delete an item
	 *
	 * @param   int  $id  Element id
	 *
	 * @return  bool
	 */
	public function delete($ids)
	{
		$user = Factory::getUser();

		if (!$user->authorise('core.delete', 'com_jlike'))
		{
			$this->setError(JText::_('JERROR_ALERTNOAUTHOR'), 'warning');

			return false;
		}

		$table = $this->getTable();

		if (is_array($ids))
		{

			foreach ($ids as $id)
			{ 
				if (!$table->delete($id))
				{
					
					$this->setError($table->getError());

					return false;
				}
				else
				{ 
					
					// Trigger the after delete event.
					// Factory::getApplication()->triggerEvent($this->event_after_delete, array($table->getProperties()));
				}
			}

			return true;
		}
		else
		{
			if (!$table->delete($ids))
			{ 
				$this->setError($table->getError());

				return false;
			}
			else
			{
				// Trigger the after delete event.
				// Factory::getApplication()->triggerEvent($this->event_after_delete, array($table->getProperties()));
			}

			return true;
		}

	}

	/**
	 * Method to save the form data.
	 *
	 * @param   array  $data  The form data.
	 *
	 * @return  boolean  True on success, False on error.
	 *
	 * @since   3.0.0
	 */
	public function save($data)
	{
		$table = $this->getTable();

		$table->load(array('content_id' => $data['content_id'], 'assigned_to' => (int) $data['assigned_to']));

		$cdate = Factory::getDate('now');

		if ($table->id)
		{
			$data['modified_date'] = $cdate->toSQL();
		}
		else
		{
			$data['created_date'] = $cdate->toSQL();
			$data['modified_date'] = $cdate->toSQL();
		}

		if ($table->save($data) === true)
		{
			return true;
		}
	}

	/**
	 * Method to assign user.
	 *
	 * @param   array    $data    todo data 
	 * @param   boolean  $notify  Allow notification flag.
	 *
	 * @return  boolean  True on success, False on error.
	 */
	public function setTodo($data, $notify = false)
	{
		// Load contentform model to get content id
		JLoader::import('contentform', JPATH_SITE . '/components/com_jlike/models');
		$contentId = JlikeModelContentForm::getContentID($data);
		$data['content_id'] = $contentId;

		$result = self::save($data);

		if (!$result)
		{
			return false;
		}

		// If notify is true then send notification on after assign the content
		if ($notify)
		{
			$client = "jlike";
			$key = "assignContent";

			$recipients = array (
				// Add specific to, cc (optional), bcc (optional)
				'email' => array (
					'to' => array (Factory::getUser($data['assigned_to'])->email)
				)
			);

			$app                  = Factory::getApplication();
			$mailfrom             = $app->getCfg('mailfrom');
			$fromname             = $app->getCfg('fromname');

			// Get user data
			$userInfo = Factory::getUser($data['assigned_to']);

			// Get assigner data
			$assignerInfo = Factory::getUser($data['assigned_by']);

			// Get content data
			$JlikeModelContentForm = new JlikeModelContentForm;
			$contentData           = $JlikeModelContentForm->getData($contentId);
			$contentData->url      = Uri::root() . substr(Route::_($contentData->url), strlen(Uri::base(true)) + 1);

			$replacements           = new stdClass;
			$replacements->user     = $userInfo;
			$replacements->assigner = $assignerInfo;
			$replacements->content  = $contentData;

			$options = new Registry;
			$options->set('subject', $contentData);

			Tjnotifications::send($client, $key, $recipients, $replacements, $options);

			// TRIGGER After Recommendation
			PluginHelper::importPlugin('system');
			Factory::getApplication()->triggerEvent('onAfterRecommendation', array($data));
		}

		return true;
	}

	/**
	 * Method to get a single record.
	 *
	 * @param   integer  $pk  The id of the primary key.
	 *
	 * @return  mixed    Object on success, false on failure.
	 *
	 * @since   3.0.0
	 */
	 public function getItem($pk = null)
	 {
		 parent::getItem();
	 }

	 // DPE Hack to resend the todo to the user.

	/**
	 * Method to Resend Todo Notification to the users.
	 *
	 * @return  void
	 *
	 * @since   __DEPLOY_VERSION__
	 */

	public function resendTodo($messageData)
	{

		$user      = Factory::getUser();
		$todoId = $messageData['todoId'];

		require_once JPATH_SITE . '/components/com_jlike/models/recommendation.php';
		require_once JPATH_SITE . '/components/com_jlike/models/comment.php';
		require_once JPATH_SITE . '/components/com_jlike/models/content.php';

		Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_jlike/tables');
		$sendNotificationData = array();

  	       $tododDataTable = Table::getInstance('Todos', 'JlikeTable');
		   $tododDataTable->load(array('id' => $todoId));

			$data['id']          = $tododDataTable->id;
		    $data['task_title']  = $tododDataTable->title;

			
			// Create object of recommendation model
			$JlikeModelRecommendation = new JlikeModelRecommendation;

			// Get content id of added todo
			$contentData = (array) $JlikeModelRecommendation->getData($data['id']);

			// Create object of content model
			$JlikeModelContent = new JlikeModelContent;

			// Get content data - title, url, element from content table
			$todoData = (array) $JlikeModelContent->getData($contentData['content_id']);
			

			// Append todo fields in $data
			$data['title']       = $tododDataTable->title;
			$data['url']         = $todoData['url'];
			$data['entry_id']    = $tododDataTable->id;
			$data['element']     = $tododDataTable->element;
			$data['assigned_by'] = $tododDataTable->assigned_by;
			$data['assigned_to'] = $tododDataTable->assigned_to;
			$data['sender_msg']  = $tododDataTable->sender_msg;
			$data['cc_users']    = $tododDataTable->cc_users;
			$data['params']      = $tododDataTable->params;
			$data['due_date']    = $tododDataTable->due_date;
			$data['todoType']    = 'resend';
			
			$params = ComponentHelper::getParams('com_jlike');

			 $msg = Text::sprintf('COM_JLIKE_RECOMMEND_NOTIFICATION', Factory::getUser($tododDataTable->assigned_by)->name, $tododDataTable->title);

				$options = array('plg_type'=>'content', 'plg_name'=>'jlike_dpe');

				$recommendModel = Jlike::model('Recommend', array('ignore_request' => true));

				if ($tododDataTable->status != 'C')
				{
					$result = $recommendModel->sendRecommendationNotification($params->get('integration'), $msg, $data['assigned_to'], $data, array(), $options);
				}
				else
				{
					$result['message'] = 'Todo is already completed';
				}
	}
}
