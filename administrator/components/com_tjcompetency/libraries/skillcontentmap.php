<?php
/**
 * @package     TjCompetency
 * @subpackage  com_tjcompetency
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2021 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Object\CMSObject;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\String\StringHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\HTML\HTMLHelper;

/**
 * Competency skillcontentmap class. Handles all application interaction with a Competency SkillContentMap
 *
 * @since  1.0.0
 */
class TjCompetencySkillContentMap extends CMSObject
{
	public $id = null;

	public $skill_id = null;

	public $scale_id = null;

	public $client = null;

	public $client_id = null;

	public $description = "";

	public $state = 1;

	public $checked_out = null;

	public $checked_out_time = null;

	public $created_on = null;

	public $created_by = 0;

	public $modified_on = null;

	public $modified_by = 0;

	protected static $competencySkillContentMapObj = array();

	protected static $pluginType = 'tjcompetencycontenttype';

	/**
	 * Constructor activating the default information of the Competency skillcontentmap
	 *
	 * @param   int  $id  The unique event key to load.
	 *
	 * @since   1.0.0
	 */
	public function __construct($id = 0)
	{
		if (!empty($id))
		{
			$this->load($id);
		}

		$db = Factory::getDbo();

		$this->checked_out_time = $this->modified_on = $db->getNullDate();
	}

	/**
	 * Returns the global competency skillcontentmap object
	 *
	 * @param   integer  $id  The primary key of the competency skillcontentmap id to load (optional).
	 *
	 * @return  Object  Competency skillcontentmap object.
	 *
	 * @since   1.0.0
	 */
	public static function getInstance($id = 0)
	{
		if (!$id)
		{
			return new TjCompetencySkillContentMap;
		}

		if (empty(self::$competencySkillContentMapObj[$id]))
		{
			$competencySkillContentMap = new TjCompetencySkillContentMap($id);
			self::$competencySkillContentMapObj[$id] = $competencySkillContentMap;
		}

		return self::$competencySkillContentMapObj[$id];
	}

	/**
	 * Method to load a competency skillcontentmap object by competency skillcontentmap id
	 *
	 * @param   int  $id  The competency skillcontentmap id
	 *
	 * @return  boolean  True on success
	 *
	 * @since 1.0.0
	 */
	public function load($id)
	{
		$table = TjCompetency::table("skillcontentmaps");

		if (!$table->load($id))
		{
			return false;
		}

		$this->setProperties($table->getProperties());

		return true;
	}

	/**
	 * Method to save the Competency skillcontentmap object to the database
	 *
	 * @return  boolean  True on success
	 *
	 * @since 1.0.0
	 * @throws  \RuntimeException
	 */
	public function save()
	{
		// Create the competency skillcontentmap table object
		$table = TjCompetency::table("skillcontentmaps");
		$table->bind($this->getProperties());

		$currentDateTime = Factory::getDate()->toSql();

		$user = Factory::getUser();

		// Allow an exception to be thrown.
		try
		{
			// Check and store the object.
			if (!$table->check())
			{
				$this->setError($table->getError());

				return false;
			}

			// Check if new record
			$isNew = empty($this->id);

			if ($isNew)
			{
				if (empty($table->created_on))
				{
					$table->created_on = $currentDateTime;
				}

				$table->created_by = $user->id;
			}
			else
			{
				$table->modified_on = $currentDateTime;
				$table->modified_by = $user->id;
			}

			// Store the user data in the database
			if (!($table->store()))
			{
				$this->setError($table->getError());

				return false;
			}

			$this->id = $table->id;

			// Fire the onTjCompetencySkillContentMapAfterSave event.
			

			Factory::getApplication()->triggerEvent('onTjCompetencySkillContentMapAfterSave', array($isNew, $this));
		}
		catch (\Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}

		return true;
	}

	/**
	 * Method to bind an associative array of data to a competency skillcontentmap object
	 *
	 * @param   array  &$array  The associative array to bind to the object
	 *
	 * @return  boolean  True on success
	 *
	 * @since 1.0.0
	 */
	public function bind(&$array)
	{
		if (empty ($array))
		{
			$this->setError(Text::_('COM_TJCOMPETENCY_EMPTY_DATA'));

			return false;
		}

		// Bind the array
		if (!$this->setProperties($array))
		{
			$this->setError(Text::_('COM_TJCOMPETENCY_BINDING_ERROR'));

			return false;
		}

		// Make sure its an integer
		$this->id = (int) $this->id;

		return true;
	}

	/**
	 * Method to fetch Competency Content types
	 *
	 * @return  array  Array of Competency Content types
	 *
	 * @since 1.0.0
	 */
	public static function getCompetencyContentTypes()
	{
		return PluginHelper::getPlugin(self::$pluginType);
	}

	/**
	 * Method to fetch Competency Content types options for dropdown
	 *
	 * @param   string  $contentType  Content type
	 *
	 * @return  boolean  True or False
	 *
	 * @since 1.0.0
	 */
	public static function validateCompetencyContentType($contentType)
	{
		if (empty($contentType))
		{
			return false;
		}

		$plugins = self::getCompetencyContentTypes();

		if (!empty($plugins))
		{
			$dispatcher = JDispatcher::getInstance();

			foreach ($plugins as $key => $value)
			{
				if ($value->name == $contentType)
				{
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Method to fetch Competency Content Name
	 *
	 * @param  string  $client    Client Name
	 *
	 * @param  int     $clientId  Client Id
	 *
	 * @return  string|void  Content Name
	 *
	 * @since 1.0.0
	 */
	public static function getContentName($client, $clientId)
	{
		PluginHelper::importPlugin(self::$pluginType, $client);
		
		$plgContentType = Factory::getApplication()->triggerEvent($client . 'GetContentName', array($clientId));

		if (!empty($plgContentType[0]))
		{
			return $plgContentType[0];
		}
	}

	/**
	 * Method to fetch Competency Content Name
	 *
	 * @param  string  $client    Client Name
	 *
	 * @param  int     $clientId  Client Id
	 *
	 * @return  string|void  Content Name
	 *
	 * @since 1.0.0
	 */
	public static function getContentLink($client, $clientId)
	{
		PluginHelper::importPlugin(self::$pluginType, $client);
		
		$plgContentType = Factory::getApplication()->triggerEvent($client . 'GetContentLink', array($clientId));

		if (!empty($plgContentType[0]))
		{
			return $plgContentType[0];
		}
		else
		{
			return 'javascript:void(0);';
		}
	}

	/**
	 * Method to fetch Competency Content Count
	 *
	 * @param  int     $skillId  Skill Id
	 *
	 * @param  string  $client   Client Name
	 *
	 * @return  int  Content count
	 *
	 * @since 1.0.0
	 */
	public static function getContentCount($skillId = '', $client = '')
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select('count(a.id)');
		$query->from($db->quoteName('#__tjcompetency_skill_content_map', 'a'));

		if (!empty($skillId))
		{
			$query->where('a.skill_id = ' . $db->quote($skillId));
		}

		if (!empty($client))
		{
			$query->where('a.client = ' . $db->quote($client));
		}

		$db->setQuery($query);

		return $db->loadResult();
	}
}
