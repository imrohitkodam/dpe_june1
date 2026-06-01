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

/**
 * SkillContentUserMaps table class
 *
 * @since  1.0.0
 */
class TjCompetencyTableSkillContentUserMaps extends JTable
{
	/**
	 * Constructor
	 *
	 * @param   JDatabaseDriver  &$db  Database object
	 *
	 * @since  1.0.0
	 */
	public function __construct(&$db)
	{
		parent::__construct('#__tjcompetency_skill_user_content_map', 'id', $db);
		$this->setColumnAlias('published', 'state');
	}

	/**
	 * Override check function
	 *
	 * @return  boolean
	 *
	 * @see     Table::check()
	 * @since   1.5
	 */
	public function check()
	{
		return true;
	}

	/**
	 * Overloaded bind function.
	 *
	 * @param   array   $array   named array
	 * @param   string  $ignore  An optional array or space separated list of properties
	 *                           to ignore while binding.
	 *
	 * @return  mixed   Null if operation was satisfactory, otherwise returns an error
	 *
	 * @see     Table::bind()
	 * @since   1.6
	 */
	public function bind($array, $ignore = '')
	{
		return parent::bind($array, $ignore);
	}

	/**
	 * Overridden Table::store to set created/modified and user id.
	 *
	 * @param   boolean  $updateNulls  True to update fields even if they are null.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   1.6
	 */
	public function store($updateNulls = false)
	{
		$date = \JFactory::getDate();
		$user = \JFactory::getUser();

		$this->modified_on = $date->toSql();

		if ($this->id)
		{
			// Existing category
			$this->modified_by = $user->get('id');
		}
		else
		{
			// New category. A category created_time and created_user_id field can be set by the user,
			// so we don't touch either of these if they are set.
			if (!(int) $this->created_on)
			{
				$this->created_on = $date->toSql();
			}

			if (empty($this->created_by))
			{
				$this->created_by = $user->get('id');
			}
		}

		// Verify that the alias is unique
		$table = JTable::getInstance('SkillContentUserMaps', 'TjCompetencyTable', array('dbo' => $this->getDbo()));

		if (empty($this->user_id))
		{
			$this->setError(\JText::_('COM_COMPETENCY_COMPETENCY_SKILLCONTENTUSERMAP_FORM_NO_USER_ID'));

			return false;
		}
		elseif (empty($this->client_id))
		{
			$this->setError(\JText::_('COM_COMPETENCY_COMPETENCY_SKILLCONTENTUSERMAP_FORM_NO_CONTENT_ID'));

			return false;
		}
		elseif ($table->load(array('user_id' => $this->user_id, 'client' => $this->client, 'client_id' => (int) $this->client_id, 'skill_id' => $this->skill_id, 'scale_id' => (int) $this->scale_id))
			&& ($table->id != $this->id || $this->id == 0))
		{
			$this->setError(\JText::_('COM_TJCOMPETENCY_ERROR_SKILLCONTENTUSERMAP_UNIQUE'));

			return false;
		}

		return parent::store($updateNulls);
	}
}
