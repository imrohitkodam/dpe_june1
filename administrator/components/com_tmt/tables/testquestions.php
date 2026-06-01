<?php

/**
 * @package    Com_Tmt
 * @copyright  Copyright (C) 2009 -2015 Techjoomla, Tekdi Web Solutions . All rights reserved.
 * @license    GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link       http://www.techjoomla.com
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Table\Table;

/**
 * Sections Table class
 *
 * @since  1.0
 *
 */
class TmtTabletestquestions extends Table
{
	/**
	 * Constructor
	 *
	 * @param   type  &$db  A database connector object
	 */
	public function __construct(&$db)
	{
		parent::__construct('#__tmt_tests_questions', 'id', $db);
		$this->setColumnAlias('ordering', 'order');
	}
}
