<?php
/**
 * @package    Com_Dpe
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2019 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

defined('_JEXEC') or die;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Language\Text;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Table\Table;

/**
 * Class DpeMainHelper
 *
 * @since  1.0.0
 */
class DpeMainHelper
{
	/**
	 * Get an instance of the named model
	 *
	 * @param   string  $name  Model name
	 *
	 * @return null|object
	 */
	public static function getModel($name)
	{
		$model = null;

		// If the file exists, let's
		if (file_exists(JPATH_SITE . '/components/com_dpe/models/' . strtolower($name) . '.php'))
		{
			require_once JPATH_SITE . '/components/com_dpe/models/' . strtolower($name) . '.php';
			$model = BaseDatabaseModel::getInstance($name, 'DpeModel');
		}

		return $model;
	}

	/**
	 * Get layout html
	 *
	 * @param   string  $viewname       name of view
	 * @param   string  $layout         layout of view
	 * @param   string  $searchTmpPath  site/admin template
	 * @param   string  $useViewpath    site/admin view
	 *
	 * @return  string
	 */
	public function getViewPath($viewname, $layout = "", $searchTmpPath = 'SITE', $useViewpath = 'SITE')
	{
		$searchTmpPath = ($searchTmpPath == 'SITE') ? JPATH_SITE : JPATH_ADMINISTRATOR;
		$useViewpath   = ($useViewpath == 'SITE') ? JPATH_SITE : JPATH_ADMINISTRATOR;
		$app           = Factory::getApplication();

		if (!empty($layout))
		{
			$layoutname = $layout . '.php';
		}
		else
		{
			$layoutname = "default.php";
		}

		$override = $searchTmpPath . '/templates/' . $app->getTemplate() . '/html/com_dpe/' . $viewname . '/' . $layoutname;

		if (File::exists($override))
		{
			return $override;
		}
		else
		{
			return $useViewpath . '/components/com_dpe/views/' . $viewname . '/tmpl/' . $layoutname;
		}
	}

	/**
	 * Function to get assigned users
	 *
	 * @param   INT  $contentId  Id of jlike content
	 *
	 * @param   INT  $userId     Id of user
	 *
	 * @param   INT  $ownUser    Id of content owner to skip from assigned user dropdown
	 *
	 * @return  object
	 */
	public function getAssignedUser($contentId, $userId = null, $ownUser = null)
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true);

		$query->select('u.id, u.name, u.email, u.username');
		$query->from($db->quoteName('#__jlike_todos') . 'as a');
		$query->join('INNER', $db->quoteName('#__users') . 'as u on u.id = a.assigned_to');
		$query->where($db->quoteName('a.content_id') . ' = ' . $db->quote((int) $contentId));
		$query->where($db->qn('u.block') . ' = 0');

		if (!empty($userId))
		{
			$query->where($db->quoteName('a.assigned_to') . ' = ' . $db->quote((int) $userId));
		}

		if (!empty($ownUser))
		{
			$query->where($db->quoteName('a.assigned_to') . ' != ' . $db->quote((int) $ownUser));
		}

		$db->setQuery($query);
		$results = $db->loadObjectList();

		return $results;
	}

	/**
	 * Get all jtext for javascript
	 *
	 * @return   void
	 *
	 * @since   1.0
	 */
	public static function getLanguageConstant()
	{
		Text::script('COM_DPE_ASSIGNMENT_SELECT_USERS');
		Text::script('COM_DPE_SELECT_DATE_FOR_ASSIGNMENT');
		Text::script('COM_DPE_SELECT_SCHOOL_ROP');
		Text::script('COM_DPE_DEASSIGNMENT_SELECT_USERS');
		Text::script('COM_DPE_NO_USERS');
	}

	/**
	 * Function to show time in hr and min format
	 *
	 * @param   INT  $time  time in seconds
	 *
	 * @return  string
	 */
	public function showHoursAndMins($time)
	{
		if ($time < 1)
		{
			return;
		}

		$secTomin = round($time / 60);
		$hours = floor($secTomin / 60);
		$minutes = ($secTomin % 60);

		if ($hours <= 0)
		{
			return Text::sprintf('COM_DPE_TIME_FORMAT_WITH_MIN', $minutes);
		}
		else
		{
			return Text::sprintf('COM_DPE_TIME_FORMAT_WITH_HRMIN', $hours, $minutes);
		}
	}

	/**
	 * Function to get field value of user
	 *
	 * @param   INT  $userId     Id of user
	 *
	 * @param   INT  $itemId     Id of ucm content
	 *
	 * @param   INT  $client     ucm type client
	 *
	 * @param   INT  $clusterId  cluster id
	 *
	 * @return  array|boolean
	 */
	public function getFieldValues($userId, $itemId = null, $client = null, $clusterId = null)
	{
		Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjfields/tables');

		// If client not passed then get client by passing content id to field value table
		if (!$client && $itemId)
		{
			$tjFieldFieldValuesTable = Table::getInstance('fieldsvalue', 'TjfieldsTable');
			$tjFieldFieldValuesTable->load(array('content_id' => $itemId));
			$client = $tjFieldFieldValuesTable->client;
		}

		if (!$client)
		{
			return false;
		}

		$tjFieldFieldTable = Table::getInstance('field', 'TjfieldsTable');
		$tjFieldFieldTable->load(array('client' => $client, 'type' => 'assignee', 'state' => 1));

		if ($tjFieldFieldTable->id)
		{
			$db    = Factory::getDbo();
			$query = $db->getQuery(true);
			$query->select($db->qn('value'));
			$query->from($db->qn('#__tjfields_fields_value', 'fv'));
			$query->where($db->qn('fv.field_id') . ' = ' . (int) $tjFieldFieldTable->id);
			$query->where($db->qn('fv.value') . ' = ' . (int) $userId);

			if ($clusterId !== null)
			{
				$query->join('INNER', $db->qn('#__tj_ucm_data', 'ud') . ' ON (' . $db->qn('ud.id') . ' = ' . $db->qn('fv.content_id') . ')');
				$query->where($db->qn('ud.cluster_id') . ' = ' . (int) $clusterId);
			}

			if ($itemId !== null)
			{
				$query->where($db->qn('fv.content_id') . ' = ' . (int) $itemId);
			}

			if ($client !== null)
			{
				$query->where($db->qn('fv.client') . ' = ' . $db->q($client));
			}

			$db->setQuery($query);

			return $db->loadResult();
		}
	}

	/**
	 * Function to get field value of user
	 *
	 * @param   INT  $parent_id         parent_id
	 *
	 * @param   INT  $visualizationData Data
	 *
	 * @return  array|boolean
	 */
	public function filter_by_parent($parent_id,$visualizationData)
	{
		$retval = array();

		foreach($visualizationData as $a)
		{
			if ($a['com_tjucm_ropdataflow_parentstepindataflow'] == $parent_id)
			{
				$retval[]=$a;
			}
		}

		return $retval;
	}

	/**
	 * Function to Generate Tree HTML
	 *
	 * @param   INT  $parent_id          parent_id
	 * @param   INT  $level              parent_id
	 * @param   INT  $visualizationData  parent_id
	 *
	 * @param   INT  $visualizationData Data
	 *
	 * @return  String
	 */
	public function print_list($parent, $level, $visualizationData)
	{
		$children = $this->filter_by_parent($parent, $visualizationData);

		if (empty($children))
		{
			return;
		}

		$class = empty($level) ? 'myTree': '';

		$html = '';

		$html .= '<ol class="sortable '.$class.'">';

		foreach($children as $child)
		{
			// indent and display the title of this child <br>
			$html .= '<li class="treeItem" ucmid="'. $child['com_tjucm_ropdataflow_contentid'].'"
			title="'. $child['com_tjucm_ropdataflow_dataflowstepdescription'].'"
			id="'. $child['com_tjucm_ropdataflow_contentid'].'" >'.'<div>'.$child['com_tjucm_ropdataflow_dataflowstepdescription'].'</div>';

			$html .= $this->print_list($child['com_tjucm_ropdataflow_contentid'], $level+1,$visualizationData);

			$html .=  '</li>';
		}

		$html .= '</ol>';

		return $html;
	}

	public function getFieldsValueByFieldIdcontentId($fieldId, $contentId)
	{

		    $db    = Factory::getDbo();
			$query = $db->getQuery(true);
			$query->select($db->qn('value'));
			$query->from($db->qn('#__tjfields_fields_value', 'fv'));
			$query->where($db->qn('fv.field_id') . ' = ' . (int) $fieldId);

			if ($contentId !== null)
			{
				$query->where($db->qn('fv.content_id') . ' = ' . (int) $contentId);
			}


			$db->setQuery($query);
			
			return $db->loadColumn();
	}
}
