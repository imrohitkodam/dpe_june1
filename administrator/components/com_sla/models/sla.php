<?php
/**
 * @package    Sla
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.

defined('_JEXEC') or die;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Table\Table;

use Joomla\CMS\Factory;
use \Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\Registry\Registry;

/**
 * Item Model for an Sla.
 *
 * @since  1.0.0
 */
class SlaModelSla extends AdminModel
{
	/**
	 * Method to get the record form.
	 *
	 * @param   array    $data      Data for the form.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return  Form|boolean  A Form object on success, false on failure
	 *
	 * @since   1.0.0
	 */
	public function getForm($data = array(), $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm('com_sla.sla', 'sla', array('control' => 'jform', 'load_data' => $loadData));

		return empty($form) ? false : $form;
	}

	/**
	 * Returns a Table object, always creating it.
	 *
	 * @param   string  $type    The table type to instantiate
	 * @param   string  $prefix  A prefix for the table class name. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return  Table    A database object
	 */
	public function getTable($type = 'Slas', $prefix = 'SlaTable', $config = array())
	{
		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_sla/tables');

		return Table::getInstance($type, $prefix, $config);
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return	mixed	$data  The data for the form.
	 *
	 * @since	1.6
	 */
	protected function loadFormData()
	{
		// Check the session for previously entered form data.
		$data = Factory::getApplication()->getUserState('com_sla.edit.sla.data', array());

		if (empty($data))
		{
			$data = $this->getItem();
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
	 * @since 1.0.0
	 */
	public function save($data)
	{
		$pk   = (!empty($data['id'])) ? $data['id'] : (int) $this->getState('sla.id');
		$sla = SlaSla::getInstance($pk);

		// Bind the data.
		if (!$sla->bind($data))
		{
			$this->setError($sla->getError());

			return false;
		}

		$result = $sla->save();

		// Store the data.
		if (!$result)
		{
			$this->setError($sla->getError());

			return false;
		}

		$this->setState('sla.id', $sla->id);

		return true;
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @return   void
	 *
	 * @since    1.0.0
	 */

	protected function populateState()
	{
		$jinput = Factory::getApplication()->input;
		$id = ($jinput->get('id'))?$jinput->get('id'):$jinput->get('id');
		$this->setState('sla.id', $id);
	}

	/**
	 * Method to get activity type html by sla
	 *
	 * @param   integer  $slaId  sla id
	 *
	 * @return string
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function getSlaActivityTypeHtml($slaId)
	{
		if ($slaId)
		{
			$slaLibrary    = SlaSla::getInstance($slaId);
			$activityTypes = $slaLibrary->getSlaActivityTypes();

			$html = array();

			foreach ($activityTypes as $activity)
			{
				$html[] = '<div class="control-group">
								<div class="control-label">' . $activity->title . '</div>
								<div class="controls">
									<input type="number" name="jform[activity][' . $activity->id . ']" class="form-control" min="0" onChange="sla.validateCount(this);">
								</div>
							</div>';
			}

			return implode("\n", $html);
		}
	}

	/**
	 * Method to get saved and configured activity type html by licence
	 *
	 * @param   integer  $licenceId  licence Id
	 * @param   integer  $slaId      sla Id
	 * @param   boolean  $data       if only need data then pass true
	 *
	 * @return string
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function getSavedSlaActivityTypeHtml($licenceId, $slaId, $data=false)
	{
		// Get configured types
		$slaLibrary                     = SlaSla::getInstance($slaId);
		$activityTypes                  = new Registry($slaLibrary->params);
		$activityTypes['activityTypes'] = explode(',', /** @scrutinizer ignore-type */ $activityTypes['activityTypes']);

		if ($licenceId && $slaId && $activityTypes['activityTypes'])
		{
			$db    = Factory::getDBO();
			$query = $db->getQuery(true);
			$query->select('count(sa.id) as activityCount, st.id as typeId, st.title');
			$query->from($db->quoteName('#__tj_sla_activity_types', 'st'));
			$query->join('LEFT', $db->quoteName('#__tj_sla_activities', 'sa') .
				' ON ' . $db->quoteName('sa.sla_activity_type_id') . '=' . $db->quoteName('st.id') .
				' AND ' . $db->qn('sa.license_id') . ' = ' . (int) $licenceId . ' AND ' . $db->qn('sa.sla_service_id') . ' > 0 '
				);
			$query->where($db->qn('st.id') . ' IN (' . implode(',', $db->q($activityTypes['activityTypes'])) . ')');
			$query->group('st.id');
			$query->order('st.id desc');
			$db->setQuery($query);

			$savedTypes = $db->loadObjectList();

			if ($data)
			{
				return $savedTypes;
			}

			$html       = array();

			foreach ($savedTypes as $savedType)
			{
				$html[] = '<div class="control-group">
								<div class="control-label">' . $savedType->title . '</div>
								<div class="controls">
									<input type="number" class="form-control" value="' . $savedType->activityCount . '" readonly>
								</div>
							</div>';
			}

			return implode("\n", $html);
		}
	}

	/**
	 * Method to get tools html by sla
	 *
	 * @param   integer  $slaId  sla id
	 *
	 * @return string
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function getSlaToolsHtml($slaId)
	{
		if ($slaId)
		{
			$slaLibrary  = SlaSla::getInstance($slaId);
			$tools       = $slaLibrary->getSlaTools();
			$jsonTools   = json_encode($tools);
			$tools       = json_decode($jsonTools, true);
			$toolsClient = array_column($tools, 'tool_client');
			$params      = ComponentHelper::getParams('com_dpe');
			$allTools    = new Registry($params->get('allTools'));

			$html   = array();
			$html[] = '<div class="controls"><div class="row mx-0 mb-20">';

			// Build HTML for tools saved in SLA params
			foreach ($tools as $tool)
			{
				$html[] = '<div class="col-md-3 px-0 checkbox"><label><input type="checkbox" name="jform[tools][' . $tool['tool_client'] . ']"  checked>'
				. $tool['tool_name'] . '</label></div>';
			}

			// Build HTMl for tool that are not saved into SLA Params
			foreach ($allTools->get('tools') as $tool)
			{
				if (!in_array($tool->tool_client, $toolsClient))
				{
					$html[] = '<div class="col-md-3 px-0 checkbox"><label><input type="checkbox" name="jform[tools][' . $tool->tool_client . ']" >'
					. $tool->tool_name . '</label></div>';
				}
			}

			$html[] = '</div></div>';

			return implode("\n", $html);
		}
	}

	/**
	 * Method to get saved and configured tools html by licence
	 *
	 * @param   integer  $licenceId  licence Id
	 * @param   integer  $slaId      sla Id
	 *
	 * @return string
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function getSavedSlaToolsHtml($licenceId, $slaId)
	{
		if (!$licenceId && !$slaId)
		{
			return false;
		}

		// Get configured types
		$slaLibrary = SlaSla::getInstance($slaId);
		$tools      = new Registry($slaLibrary->params);
		$params     = ComponentHelper::getParams('com_dpe');
		$allTools   = new Registry($params->get('allTools'));

		$db    = Factory::getDBO();
		$query = $db->getQuery(true);

		// Query to get all Slaved Sla
		$query->select('tlx.client');

		$query->from($db->quoteName('#__tjmultiagency_licences', 'tl'));
		$query->join(
		'LEFT', $db->quoteName('#__tjmultiagency_licences_xref', 'tlx') .
			' ON ' . $db->quoteName('tl.id') . '=' . $db->quoteName('tlx.licence_id')
			);
		$query->where($db->qn('tl.id') . ' = ' . (int) $licenceId);

		$db->setQuery($query);

		$savedTools = $db->loadColumn();
		$html       = array();
		$html[]     = '<div class="controls"><div class="row mx-0 mb-20">';

		// Build html for saved sla
		foreach ($savedTools as $savedTool)
		{
			foreach ($allTools->get('tools') as $tool)
			{
				if ($tool->tool_client === $savedTool)
				{
					$html[] = '<div class="col-md-3 px-0 checkbox"><label><input type="checkbox" name="jform[tools][' . $savedTool . ']" checked>'
					. $tool->tool_name . '</label></div>';
				}
			}
		}

		// Build HTMl for remaining Sla which are not saved in sla params
		foreach ($allTools->get('tools') as $tool)
		{
			if (!in_array($tool->tool_client, $savedTools))
			{
				$html[] = '<div class="col-md-3 px-0 checkbox"><label><input type="checkbox" name="jform[tools][' . $tool->tool_client . ']">'
				. $tool->tool_name . '</label></div>';
			}
		}

		$html[] = '</div></div>';

		return implode("\n", $html);
	}
}
