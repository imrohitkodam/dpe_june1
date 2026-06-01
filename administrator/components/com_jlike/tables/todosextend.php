<?php
/**
 * @package     JLike
 * @subpackage  com_jlike
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;
use Joomla\CMS\Table\Table;

/**
 * Todos extended Table class
 *
 * @since  __DEPLOY_VERSION__
 */
class JlikeTableTodosExtend extends Table
{
	/**
	 * Constructor
	 *
	 * @param   \JDatabaseDriver  $db     \JDatabaseDriver object.
	 */
	public function __construct(&$db)
	{
		parent::__construct('#__jlike_todos_extended', 'todo_id', $db);
		$this->_autoincrement = false;
	}
}
