<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Tmt
 * @author     Techjoomla <contact@techjoomla.com>
 * @copyright  Copyright (C) 2013 - 2014. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access

defined('_JEXEC') or die;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Factory;
use Joomla\Registry\Registry;
use Joomla\CMS\Access\Access;
use Joomla\CMS\Filter\OutputFilter;
use Joomla\CMS\Language\Text;
use Joomla\Utilities\ArrayHelper;
/**
 * question Table class
 *
 * @since  1.5
 */
class TmtTablequestion extends Table
{
	public $ordering;

	public $alias;
	/**
	 * Constructor
	 *
	 * @param   JDatabase  &$db  A database connector object
	 */
	public function __construct(&$db)
	{
		parent::__construct('#__tmt_questions', 'id', $db);
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
		$task = $input->getString('task', '');

		if ((!Factory::getUser()->authorise('core.edit.state', 'com_tmt') && $array['state'] == 1) && ($task == 'save' || $task == 'apply'))
		{
			$array['state'] = 0;
		}

		// Support for multiple or not foreign key field: category_id

		if (isset($array['category_id']))
		{
			if (is_array($array['category_id']))
			{
				$array['category_id'] = implode(',', $array['category_id']);
			}
			elseif (strrpos($array['category_id'], ',') != false)
			{
				$array['category_id'] = explode(',', $array['category_id']);
			}
			elseif (empty($array['category_id']))
			{
				$array['category_id'] = '';
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

		if (!Factory::getUser()->authorise('core.admin', 'com_tmt.question.' . $array['id']))
		{
			$actions         = Access::getActionsFromFile(
				JPATH_ADMINISTRATOR . '/components/com_tmt/access.xml',
				"/access/section[@name='question']/"
			);
			$default_actions = Access::getAssetRules('com_tmt.question.' . $array['id'])->getData();
			$array_jaccess = array();

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

		return parent::bind($array, $ignore);
	}

	/**
	 * This function convert an array of JAccessRule objects into an rules array.
	 *
	 * @param   type  $jaccessrules  an arrao of JAccessRule objects.
	 *
	 * @return  array
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
				$actions[$group] = ((bool) $allow);
			}

			$rules[$action] = $actions;
		}

		return $rules;
	}

	/**
	 * Overloaded check function
	 *
	 * @return  mixed
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

		$this->alias = trim($this->alias);

		if (empty($this->alias))
		{
			$this->alias = substr(MD5(uniqid()), 0, 10);
		}

		if ($this->alias)
		{
			if (Factory::getConfig()->get('unicodeslugs') == 1)
			{
				$this->alias = OutputFilter::stringURLUnicodeSlug($this->alias);
			}
			else
			{
				$this->alias = OutputFilter::stringURLSafe($this->alias);
			}
		}

		// Check if course with same alias is present

		$table = Table::getInstance('Question', 'TmtTable', array('dbo',$db));

		if ($table->load(array('alias' => $this->alias)) && ($table->id != $this->id || $this->id == 0))
		{
			$msg = Text::_('COM_TMT_SAVE_ALIAS_WARNING');
			Factory::getApplication()->enqueueMessage($msg, 'error');

			return false;
		}

		// Check if category with same alias is present

		$category = Table::getInstance('Category', 'JTable', array('dbo',$db));
		if ($category->load(array('alias' => $this->alias)))
		{
			$msg = Text::_('COM_TMT_SAVE_COURSE_WARNING_DUPLICATE_CATALIS');

			Factory::getApplication()->enqueueMessage($msg, 'error');

			return false;
		}

		$tmt_views = array(
			'answer',
			'answers',
			'category',
			'categories',
			'question',
			'questions',
			'test',
			'tests'
		);

		if (in_array($this->alias, $tmt_views))
		{
			$this->setError(Text::_('COM_TMT_VIEW_WITH_SAME_ALIAS'));

			return false;
		}

		if (trim(str_replace('-', '', $this->alias)) == '')
		{
			$this->alias = Factory::getDate()->format("Y-m-d-H-i-s");
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
		$state = (int) $state;

		// If there are no primary keys set check to see if the instance key is set.

		if (empty($pks))
		{
			if ($this->$k)
			{
				$pks = array(
					$this->$k
				);
			}

			// Nothing to set publishing state on, return false.

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

		return 'com_tmt.question.' . (int) $this->$k;
	}

	/**
	 * Returns the parent asset's id. If you have a tree structure, retrieve the parent's id using the external key field
	 *
	 * @param   Object  $table  Jtable
	 * @param   INT     $id     Id
	 *
	 * @return  INT parent asset's id
	 *
	 * @since  1.0.0
	 */
	protected function _getAssetParentId(Table $table = null, $id = null)
	{
		// We will retrieve the parent-asset from the Asset-table

		$assetParent = Table::getInstance('Asset');

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
}
