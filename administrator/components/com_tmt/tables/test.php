<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_TMT
 * @copyright  Copyright (C) 2005 - 2014. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * Shika is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Factory;
use Joomla\CMS\Access\Access;
use Joomla\Registry\Registry;
use Joomla\CMS\Filter\OutputFilter;
use Joomla\CMS\Language\Text;
use Joomla\String\StringHelper;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Helper\ContentHelper;

/**
 * Test Table class
 *
 * @since  1.0.0
 */
class TmtTabletest extends Table
{
	/**
	 * Constructor
	 *
	 * @param   JDatabase  &$db  A database connector object
	 */
	public function __construct(&$db)
	{
		parent::__construct('#__tmt_tests', 'id', $db);
	}

	/**
	 * Overloaded bind function to pre-process the params.
	 *
	 * @param   array   $array   Named array
	 * @param   string  $ignore  string
	 *
	 * @return   null|string    null is operation was satisfactory, otherwise returns an error
	 *
	 * @since  1.0.0
	 */
	public function bind($array, $ignore = '')
	{
		$input = Factory::getApplication()->input;
		$task  = $input->getString('task', '');

		if (($task == 'save' || $task == 'apply') && $array['state'] == 1)
		{
			JLoader::import('administrator.components.com_tjlms.helpers.tjlms', JPATH_SITE);
			$canChange	= TjlmsHelper::canChangeTestState($array['id']);

			if (!$canChange)
			{
				$array['state'] = 0;
			}
		}

		// Support for multiple or not foreign key field: job_id
		if (isset($array['job_id']))
		{
			if (is_array($array['job_id']))
			{
				$array['job_id'] = implode(',', $array['job_id']);
			}
			elseif (strrpos($array['job_id'], ',') != false)
			{
				$array['job_id'] = explode(',', $array['job_id']);
			}
			elseif (empty($array['job_id']))
			{
				$array['job_id'] = '0';
			}
		}

		// Support for multiple or not foreign key field: reviewers
		if (isset($array['reviewers']))
		{
			if (is_array($array['reviewers']))
			{
				$array['reviewers'] = implode(',', $array['reviewers']);
			}
			elseif (strrpos($array['reviewers'], ',') != false)
			{
				$array['reviewers'] = explode(',', $array['reviewers']);
			}
			elseif (empty($array['reviewers']))
			{
				$array['reviewers'] = '';
			}
		}

		if (isset($array['params']) && is_array($array['params']))
		{
			$registry = new Registry;
			$registry->loadArray($array['params']);
			$array['params'] = (string) $registry;
		}

		if (isset($array['metadata']) && is_array($array['metadata']))
		{
			$registry = new Registry;
			$registry->loadArray($array['metadata']);
			$array['metadata'] = (string) $registry;
		}

		if (!Factory::getUser()->authorise('core.admin', 'com_tmt.test.' . $array['id']))
		{
			$actions         = Access::getActionsFromFile(
				JPATH_ADMINISTRATOR . '/components/com_tmt/access.xml',
				"/access/section[@name='test']/"
			);
			$default_actions = Access::getAssetRules('com_tmt.test.' . $array['id'])->getData();
			$array_jaccess   = array();

			foreach ($actions as $action)
			{
				$array_jaccess[$action->name] = $default_actions[$action->name];
			}

			$array['rules'] = $this->RulestoArray($array_jaccess);
		}

		// Bind the rules for ACL where supported.
		if (isset($array['rules']) && is_array($array['rules']))
		{
			$this->setRules($array['rules']);
		}

		/*$array['alias'] = trim($array['alias']);

		if (!$array['alias'])
		{
			$array['alias'] = trim(OutputFilter::stringURLSafe($array['title']));
		}*/

		return parent::bind($array, $ignore);
	}

	/**
	 * This function convert an array of JAccessRule objects into an rules array.
	 *
	 * @param   type  $jaccessrules  an arrao of JAccessRule objects.
	 *
	 * @return  rule
	 *
	 * @since  1.0.0
	 */
	private function RulestoArray($jaccessrules)
	{
		$rules = array();

		foreach ($jaccessrules as $action => $jaccess)
		{
			$actions = array();

			foreach ($jaccess->getData() as $group => $allow)
			{
				$actions[$group] = (bool) $allow;
			}

			$rules[$action] = $actions;
		}

		return $rules;
	}

	/**
	 * Overloaded check function
	 *
	 * @return  check
	 *
	 * @since  1.0.0
	 */
	public function check()
	{
		$db = Factory::getDbo();

		// If there is an ordering column and this is a new row then get the next ordering value
		if (property_exists($this, 'ordering') && $this->id == 0)
		{
			$this->ordering = self::getNextOrder();
		}

		if (trim($this->title) == '')
		{
			$this->setError(Text::_('COM_CONTENT_WARNING_PROVIDE_VALID_NAME'));

			return false;
		}

		if (empty($this->id) && empty($this->alias))
		{
			if (Factory::getConfig()->get('unicodeslugs') == 1)
			{
				$this->alias = OutputFilter::stringURLUnicodeSlug($this->title);
			}
			else
			{
				$this->alias = OutputFilter::stringURLSafe($this->title);
			}

			$table = Table::getInstance('Test', 'TmtTable');

			if ($table->load(array('alias' => $this->alias)))
			{
				$msg = Text::_('COM_TJLMS_SAVE_ALIAS_WARNING');
			}

			while ($table->load(array('alias' => $this->alias)))
			{
				$this->alias = StringHelper::increment($this->alias, 'dash');
			}

			if (isset($msg))
			{
				Factory::getApplication()->enqueueMessage($msg, 'warning');
			}
		}

		if (trim($this->alias) == '')
		{
			$this->alias = $this->title;
		}

		$this->alias = OutputFilter::stringURLSafe($this->alias);

		if (trim(str_replace('-', '', $this->alias)) == '')
		{
			$this->alias = \Factory::getDate()->format('Y-m-d-H-i-s');
		}

		if (isset($this->lesson_id))
		{
			unset($this->lesson_id);
		}

		return parent::check();
	}

	/**
	 * Method to set the publishing state for a row or list of rows in the database
	 * table.  The method respects checked out rows by other users and will attempt
	 * to checkin rows that it can after adjustments are made.
	 *
	 * @param   mixed    $pks     An optional array of primary key values to update.
	 * @param   integer  $state   The publishing state. eg. [0 = unpublished, 1 = published]
	 * @param   integer  $userId  The user id of the user performing the operation.
	 *
	 * @return    boolean    True on success.
	 *
	 * @since    1.0.4
	 */
	public function publish($pks = null, $state = 1, $userId = 0)
	{
		// Initialise variables.
		$k = $this->_tbl_key;

		// Sanitize input.
		ArrayHelper::toInteger($pks);
		$userId = (int) $userId;
		$state  = (int) $state;

		// If there are no primary keys set check to see if the instance key is set.
		if (empty($pks))
		{
			if ($this->$k)
			{
				$pks = array(
					$this->$k
				);
			}
			else
			{
				$this->setError(Text::_('JLIB_DATABASE_ERROR_NO_ROWS_SELECTED'));

				return false;
			}
		}

		// Build the WHERE clause for the primary keys.
		$where = $k . '=' . implode(' OR ' . $k . '=', $pks);

		// Determine if there is checkin support for the table.
		if (!property_exists($this, 'checked_out') || !property_exists($this, 'checked_out_time'))
		{
			$checkin = '';
		}
		else
		{
			$checkin = ' AND (checked_out = 0 OR checked_out = ' . (int) $userId . ')';
		}

		// Update the publishing state for rows with the given primary keys.
		$this->_db->setQuery('UPDATE `' . $this->_tbl . '`' . ' SET `state` = ' . (int) $state . ' WHERE (' . $where . ')' . $checkin);
		$this->_db->execute();

		// Check for a database error.
		if ($this->_db->getErrorNum())
		{
			$this->setError($this->_db->getErrorMsg());

			return false;
		}

		// If checkin is supported and all rows were adjusted, check them in.
		if ($checkin && (count($pks) == $this->_db->getAffectedRows()))
		{
			// Checkin each row.
			foreach ($pks as $pk)
			{
				$this->checkin($pk);
			}
		}

		// If the JTable instance value is in the list of primary keys that were set, set the instance.
		if (in_array($this->$k, $pks))
		{
			$this->state = $state;
		}

		$this->setError('');

		return true;
	}

	/**
	 * Define a namespaced asset name for inclusion in the #__assets table
	 *
	 * @see JTable::_getAssetName
	 *
	 * @return string The asset name
	 *
	 * @since  1.0.0
	 */
	protected function _getAssetName()
	{
		$k = $this->_tbl_key;

		return 'com_tmt.test.' . (int) $this->$k;
	}

	/**
	 * Returns the parent asset's id. If you have a tree structure, retrieve the parent's id using the external key field
	 *
	 * @param   Object  $table  Jtable
	 * @param   INT     $id     Id
	 *
	 * @return  The parent asset's id
	 *
	 * @since  1.0.0
	 */
	protected function _getAssetParentId(Table $table = null, $id = null)
	{
		// We will retrieve the parent-asset from the Asset-table
		$assetParent   = Table::getInstance('Asset');

		// Default: if no asset-parent can be found we take the global asset
		$assetParentId = $assetParent->getRootId();

		// The item has the component as asset-parent
		$assetParent->loadByName('com_tmt');

		// Return the found asset-parent-id
		if ($assetParent->id)
		{
			$assetParentId = $assetParent->id;
		}

		return $assetParentId;
	}

	/**
	 * Overrides Table::store to set modified data and user id.
	 *
	 * @param   boolean  $updateNulls  True to update fields even if they are null.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   1.6
	 * @deprecated  3.1.4 Class will be removed upon completion of transition to UCM
	 */
	public function store($updateNulls = false)
	{
		$date = Factory::getDate();
		$user = Factory::getUser();

		if (!$this->id)
		{
			// New article. An article created and created_by field can be set by the user,
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
		$table = Table::getInstance('Test', 'TmtTable', array('dbo' => $this->getDbo()));

		if ($table->load(array('alias' => $this->alias)) && ($table->id != $this->id || $this->id == 0))
		{
			$this->setError(Text::_('JLIB_DATABASE_ERROR_ARTICLE_UNIQUE_ALIAS'));

			return false;
		}

		return parent::store($updateNulls);
	}
}
