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
 * Scales table class
 *
 * @since  1.0.0
 */
class TjCompetencyTableScales extends JTable
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
		parent::__construct('#__tjcompetency_scales', 'id', $db);
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
		// Check for a title.
		if (trim($this->title) == '')
		{
			$this->setError(\JText::_('COM_TJCOMPETENCY_ERROR_MUSTCONTAIN_A_TITLE_SCALE'));

			return false;
		}

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
		if (isset($array['params']) && is_array($array['params']))
		{
			$registry = new Registry($array['params']);
			$array['params'] = (string) $registry;
		}

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

		$table = JTable::getInstance('Scales', 'TjCompetencyTable', array('dbo' => $this->getDbo()));

		if ($table->load(array('scale_set_id' => $this->scale_set_id, 'sequence_number' => $this->sequence_number))
			&& ($table->id != $this->id || $this->id == 0))
		{
			$this->setError(\JText::_('COM_TJCOMPETENCY_ERROR_SCALES_UNIQUE'));

			return false;
		}


		return parent::store($updateNulls);
	}
}
