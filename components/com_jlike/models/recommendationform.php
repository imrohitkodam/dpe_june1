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
use Joomla\CMS\Form\Form;
use Joomla\CMS\MVC\Model\FormModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;
use Joomla\Utilities\ArrayHelper;
use TJQueue\Admin\TJQueueProduce;
use Joomla\CMS\Log\Log;

if (ComponentHelper::getComponent('com_tjqueue', true)->enabled)
{
	jimport('tjqueueproduce', JPATH_ADMINISTRATOR . '/components/com_tjqueue/libraries');
}

require_once JPATH_SITE . '/components/com_jlike/models/comment.php';
require_once JPATH_SITE . '/components/com_jlike/models/content.php';
require_once JPATH_SITE . '/components/com_jlike/models/recommendation.php';
JLoader::import('components.com_jlike.includes.jlike', JPATH_SITE);

/**
 * Jlike model.
 *
 * @since  1.6
 */
class JlikeModelRecommendationForm extends FormModel
{
	private $item = null;

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @return void
	 *
	 * @since  1.6
	 */
	protected function populateState()
	{
		$app = Factory::getApplication();

		// Load state from the request userState on edit or from the passed variable on default
		if ($app->input->get('layout') == 'edit' && $app->input->getInt('id'))
		{
			$id = $app->input->getInt('id') ? $app->input->getInt('id') : $app->getUserState('com_jlike.edit.recommendation.id');
		}
		else
		{
			$id = $app->input->get('id');
			$app->setUserState('com_jlike.edit.recommendation.id', $id);
		}

		$this->setState('recommendation.id', $id);

		// Load the parameters.
		$params = ComponentHelper::getParams('com_jlike');
		$params_array = $params->toArray();

		if (isset($params_array['item_id']))
		{
			$this->setState('recommendation.id', $params_array['item_id']);
		}

		$this->setState('params', $params);
	}

	/**
	 * Method to get an ojbect.
	 *
	 * @param   integer  $id  The id of the object to get.
	 *
	 * @return Object|boolean Object on success, false on failure.
	 *
	 * @throws Exception
	 */
	public function &getData($id = null)
	{
		if ($this->item === null)
		{
			$this->item = false;

			if (empty($id))
			{
				$id = $this->getState('recommendation.id');
			}

			// Get a level row instance.
			$table = $this->getTable();

			// Attempt to load the row.
			if ($table !== false && $table->load($id))
			{
				$user = Factory::getUser();
				$id   = $table->id;

				if ($id)
				{
					$canEdit = $user->authorise(
					'core.edit', 'com_jlike. recommendation.' . $id
					) || $user->authorise('core.create', 'com_jlike. recommendation.' . $id);
				}
				else
				{
					$canEdit = $user->authorise('core.edit', 'com_jlike') || $user->authorise('core.create', 'com_jlike');
				}

				if (!$canEdit && $user->authorise('core.edit.own', 'com_jlike.recommendation.' . $id))
				{
					$canEdit = $user->id == $table->created_by;
				}

				if (!$canEdit)
				{
					throw new Exception(Text::_('JERROR_ALERTNOAUTHOR'), 500);
				}

				// Check published state.
				if ($published = $this->getState('filter.published'))
				{
					if ($table->state != $published)
					{
						return $this->item;
					}
				}

				// Convert the JTable to a clean JObject.
				$properties  = $table->getProperties(1);
				$this->item = ArrayHelper::toObject($properties, 'JObject');

				// Load agency

				$params = ComponentHelper::getParams('com_jlike');

				if (ComponentHelper::isEnabled('com_multiagency') && $params->get('enable_multiagency'))
				{
					$todoClusterXrefTable = Jlike::table('TodosClusterXref');
					$todoClusterXrefTable->load($id);
					$this->item->clusters = $todoClusterXrefTable->cluster_id;
				}

				// DPE Hack to load reminder's data
				Factory::getApplication()->triggerEvent('onGetDataLoadReminderData', array($table->id, $this->item));

				// DPE Hack end
			}
		}

		return $this->item;
	}

	/**
	 * Method to get the table
	 *
	 * @param   string  $type    Name of the Table class
	 * @param   string  $prefix  Optional prefix for the table class name
	 * @param   array   $config  Optional configuration array for Table object
	 *
	 * @return  Table|boolean Table if found, boolean false on failure
	 */
	public function getTable($type = 'Recommendation', $prefix = 'JlikeTable', $config = array())
	{
		$this->addTablePath(JPATH_ADMINISTRATOR . '/components/com_jlike/tables');

		return Table::getInstance($type, $prefix, $config);
	}

	/**
	 * Get an item by alias
	 *
	 * @param   string  $alias  Alias string
	 *
	 * @return int Element id
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
	 * Method to get the profile form.
	 *
	 * The base form is loaded from XML
	 *
	 * @param   array    $data      An optional array of data for the form to interogate.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return    Form    A Form object on success, false on failure
	 *
	 * @since    1.6
	 */
	public function getForm($data = array(), $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm('com_jlike.recommendation', 'recommendation', array(
			'control'   => 'jform',
			'load_data' => $loadData
			)
		);

		if (empty($form))
		{
			return false;
		}

		return $form;
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return    mixed    The data for the form.
	 *
	 * @since    1.6
	 */
	protected function loadFormData()
	{
		$data = Factory::getApplication()->getUserState('com_jlike.edit.recommendationform.data', array());

		if (empty($data))
		{
			$data = $this->getData();
		}

		return $data;
	}

	/**
	 * Method to save the form data.
	 *
	 * @param   array  $data  The form data
	 *
	 * @return bool
	 *
	 * @throws Exception
	 * @since 1.6
	 */
	public function save($data)
	{ 
		
		$id        = (!empty($data['id'])) ? $data['id'] : (int) $this->getState('recommendation.id');
		$state     = (!empty($data['state'])) ? 1 : 0;
		$user      = Factory::getUser();
		$oldStatus = '';

		if (!$user->id)
		{
			$user  = Factory::getUser($data['assigned_to']);
		}

		if ($id)
		{
			// Check the user can edit this item
			$authorised = $user->authorise(
			'core.edit', 'com_jlike.recommendation.' . $id
			) || $authorised = $user->authorise('core.edit.own', 'com_jlike.recommendation.' . $id);
		}
		else
		{
			// Check the user can create new items in this section
			$authorised = $user->authorise('core.create', 'com_jlike');
		}

		if ($authorised !== true)
		{
			// DPE Hack start to check key when this code will execute by cron
			if ($data['key'] != ComponentHelper::getParams('com_dpe')->get('private_key_storage_cron'))
			{
				throw new Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403);
			}
			// DPE Hack end
		}

		$table = $this->getTable();

		// Load the row if saving an existing category.
		if ($id > 0)
		{
			$table->load($id);
			$oldStatus = $table->status;
		}

		// Update date on completion

		if ($data['status'] == 'C')
		{
			$data['done_date'] = Factory::getDate()->toSql();
		}

		// DPE hack to store created date
		$currentDateTime = Factory::getDate()->toSql();
		$data['created_date'] = $currentDateTime;

		if ($table->save($data) === true)
		{
			$data['id']          = $table->id;
			$data['task_title']  = $table->title;

			// Get Social Integration form each component
			PluginHelper::importPlugin($data["plg_type"], $data["plg_name"]);
			Factory::getApplication()->triggerEvent('onAfter' . $data["plg_name"] . 'OnTodoAfterSave',  array($data));

			// Create object of recommendation model
			$JlikeModelRecommendation = new JlikeModelRecommendation;

			// Get content id of added todo
			$contentData = (array) $JlikeModelRecommendation->getData($data['id']);

			// Create object of content model
			$JlikeModelContent = new JlikeModelContent;

			// Get content data - title, url, element from content table
			$todoData = (array) $JlikeModelContent->getData($contentData['content_id']);

			// Append todo fields in $data
			$data['title']       = $todoData['title'];
			$data['url']         = $todoData['url'];
			$data['entry_id']    = $table->id;
			$data['element']     = $todoData['element'];
			$data['assigned_by'] = $table->assigned_by;
			$data['assigned_to'] = $table->assigned_to;
			$data['sender_msg']  = $table->sender_msg;
			$data['cc_users']    = $table->cc_users;
			$currentDateTime = Factory::getDate()->toSql();
			$data['created_date'] = $currentDateTime;

			// Execute trigger after save todo. DPE Hack to call the mehod of system plugin
			PluginHelper::importPlugin('system');
			Factory::getApplication()->triggerEvent('onAfterJlikeTodoSave', array($data));

			// Execute trigger after save todo. DPE Hack to call the mehod of system plugin
			PluginHelper::importPlugin('dpe');
			Factory::getApplication()->triggerEvent('onAfterTodoStoreCheklistReviewDate', array($data['id'], $data['checklistUrl']));

			$params = ComponentHelper::getParams('com_jlike');

			// Send recommendation notification
			if (!$id && $params->get('send_auto_reminders') && $data['plg_type'] != 'com_jticketing')
			{
				// Dont send email to about JT.
				$msg = Text::sprintf('COM_JLIKE_RECOMMEND_NOTIFICATION', Factory::getUser($data['assigned_by'])->name, $data['title']);

				$options = array('plg_type'=>'content', 'plg_name'=>'jlike_dpe');

				$recommendModel = Jlike::model('Recommend', array('ignore_request' => true));
				$recommendModel->sendRecommendationNotification($params->get('integration'), $msg, $data['assigned_to'], $data, array(), $options);
			}

			if ($params->get('send_auto_reminders') && $data['plg_type'] != 'com_jticketing')
			{
				if ($data['status'] == 'C')
				{
					// Execute trigger after save todo.
					$recommendModel = Jlike::model('Recommend', array('ignore_request' => true));
					$recommendModel->sendTodoCompleteNotification($params->get('integration'), '', $data['assigned_by'], $data);
				}
			}

			if ($data['status'] == 'C')
			{
				// Plugin trigger on after todo completion
				Factory::getApplication()->triggerEvent('onAfterTodoCompletion', array($table->id));
			}

			return $table->id;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Method to delete data
	 *
	 * @param   array  $data  Data to be deleted
	 *
	 * @return bool|int If success returns the id of the deleted item, if not false
	 *
	 * @throws Exception
	 */
	public function delete($data)
	{

		$id = (!empty($data['id'])) ? $data['id'] : (int) $this->getState('recommendation.id');

		if (Factory::getUser()->authorise('core.delete', 'com_jlike.recommendation.' . $id) !== true)
		{
			throw new Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		$table = $this->getTable();

		// Create object of recommendation model
		$JlikeModelRecommendation = new JlikeModelRecommendation;

		// Get content id of added todo
		$contentData = (array) $JlikeModelRecommendation->getData($data['id']);

		// Create object of content model
		$JlikeModelContent = new JlikeModelContent;

		// Get content data - title, url, element from content table
		$todoData = (array) $JlikeModelContent->getData($contentData['content_id']);

		if ($table->delete($data['id']) === true)
		{
			// Execute trigger after deleting todo
			Factory::getApplication()->triggerEvent('onAfterJlikeTodoDelete', array($todoData));

			return $id;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Check if data can be saved
	 *
	 * @return bool
	 */
	public function getCanSave()
	{
		$table = $this->getTable();

		return $table !== false;
	}

	/**
	 * Get user avatar and profile
	 *
	 * @param   array  $data  Data
	 *
	 * @return array
	 */
	public function getUserAvatar($data)
	{
		$returnData = array();

		$helperPath = JPATH_SITE . '/components/com_jlike/helpers/main.php';

		if (!class_exists('ComjlikeMainHelper'))
		{
			// Require_once $path;
			JLoader::register('ComjlikeMainHelper', $helperPath);
			JLoader::load('ComjlikeMainHelper');
		}

		$ComjlikeMainHelper = new ComjlikeMainHelper;
		$sLibObj            = $ComjlikeMainHelper->getSocialLibraryObject('', $data);

		// Assigned by user

		$assignedBy       = new stdClass;
		$assignedBy->id   = $data['assigned_by'];
		$assignedBy->name = Factory::getUser($data['assigned_by'])->name;
		$ment_usr         = Factory::getUser($data['assigned_by']);

		$link = '';
		$link = $profileUrl = $sLibObj->getProfileUrl($ment_usr);

		if ($profileUrl)
		{
			if (!parse_url($profileUrl, PHP_URL_HOST))
			{
				$link = Uri::root() . substr(Route::_($sLibObj->getProfileUrl($ment_usr)), strlen(Uri::base(true)) + 1);
			}
		}

		$assignedBy->profile_link  = $link;
		$assignedBy->avatar        = $sLibObj->getAvatar($ment_usr, 50);
		$data['assigned_by']       = $assignedBy;
		$returnData['assigned_by'] = $assignedBy;

		// Assigned to user
		$assignedTo       = new stdClass;
		$assignedTo->id   = $data['assigned_to'];
		$assignedTo->name = Factory::getUser($data['assigned_to'])->name;

		$ment_usr         = Factory::getUser($data['assigned_to']);
		$link = '';
		$link = $profileUrl = $sLibObj->getProfileUrl($ment_usr);

		if ($profileUrl)
		{
			if (!parse_url($profileUrl, PHP_URL_HOST))
			{
				$link = Uri::root() . substr(Route::_($sLibObj->getProfileUrl($ment_usr)), strlen(Uri::base(true)) + 1);
			}
		}

		$assignedTo->profile_link = $link;
		$assignedTo->avatar       = $sLibObj->getAvatar($ment_usr, 50);
		$data['assigned_to']      = $assignedTo;
		$returnData['assigned_to'] = $assignedTo;

		return $returnData;
	}

	/**
	 * Allows preprocessing of the JForm object.
	 *
	 * @param   JForm   $form   The form object
	 * @param   array   $data   The data to be merged into the form object
	 * @param   string  $group  The plugin group to be executed
	 *
	 * @return  void
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	protected function preprocessForm(Form $form, $data, $group = 'content')
	{
		$app    = Factory::getApplication();
		$params = ComponentHelper::getParams('com_jlike');

		/* Show extra fields of Agency
		Get plugin jlike_multiagency of type 'content' */

		$plgSystemDpe = PluginHelper::getPlugin('content', 'jlike_multiagency');

		// If plugin and agency component and config is true then only load plugin field xml
		if (!empty($plgSystemDpe) && (ComponentHelper::isEnabled('com_multiagency') && $params->get('enable_multiagency')))
		{
			$form->loadFile(JPATH_ROOT . '/plugins/content/jlike_multiagency/forms/agency.xml', true);
		}

		parent::preprocessForm($form, $data, $group);
	}

	/**
	 * Method to push data in queue.
	 *
	 * @param   array  $data  record data
	 *
	 * @return  array
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function addToQueue($data, $queueProducer = null)
	{
		$return = [];

		// Reuse the same instance if provided
		$TJQueueProduce = $queueProducer ?: new TJQueueProduce;

		$messageBody = (object) $data;

		try
		{
			$TJQueueProduce->message->setBody(json_encode($messageBody));
			$TJQueueProduce->message->setProperty('client', 'jlike.todos');

			$TJQueueProduce->produce();
		}
		catch (Exception $e)
		{
			return [
				'success' => 0,
				'message' => $e->getMessage()
			];
		}

		return [
			'success' => 1,
			'message' => ''
		];
	}
}
