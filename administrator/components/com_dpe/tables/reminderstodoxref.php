<?php
/**
 * @version    CVS: 1.0.0
 * @package    Com_Dpe
 * @copyright  Copyright (C) 2005 - 2014. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;
use Joomla\Data\DataObject;

use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Table\Table;

/**
 * applicant Table class
 *
 * @since  1.6
 */
class DpeTableRemindersTodoXref extends Table
{
	/**
	 * Constructor
	 *
	 * @param   DataObjectbase  &$db  A database connector object
	 */
	public function __construct(&$db)
	{
		parent::__construct('#__jlike_reminders_todo_xref', 'reminder_id', $db);
		$this->_autoincrement = false;
	}
}
