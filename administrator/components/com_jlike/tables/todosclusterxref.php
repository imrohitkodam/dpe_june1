<?php
/**
 * @package     JLike
 * @subpackage  com_jlike
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2021 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\Table\Table;

/**
 * Todo cluster Xref Table class
 *
 * @since  __DEPLOY_VERSION__
 */
class JlikeTableTodosClusterXref extends Table
{
	/**
	 * Constructor
	 *
	 * @param   JDatabase  &$db  A database connector object
	 */
	public function __construct(&$db)
	{
		parent::__construct('#__jlike_todos_cluster_xref', 'todo_id', $db);
		$this->_autoincrement = false;
	}
}
