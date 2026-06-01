<?php
/**
 * @version    CVS: 1.0.0
 * @package    Com_Dpe
 * @copyright  Copyright (C) 2005 - 2014. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Table\Table;
use Joomla\Data\DataObject;

use Joomla\Utilities\ArrayHelper;
/**
 * applicant Table class
 *
 * @since  1.6
 */
class DpeTableOnbaordTodoTemplate extends Table
{
	/**
	 * Constructor
	 *
	 * @param   DataObjectbase  &$db  A database connector object
	 */
	public function __construct(&$db)
	{	
		parent::__construct('#__onboard_todo_tmplate', 'id', $db);
	    // $this->_autoincrement = false;
	}
}
