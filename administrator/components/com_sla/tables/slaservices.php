<?php
/**
 * @package    Sla
 *
 * @author     Techjoomla <contact@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
use Joomla\CMS\Table\Table;
use Joomla\Data\DataObject;

/**
 * Sla services table class
 *
 * @since  1.0.0
 */
class SlaTableSlaServices extends Table
{
	/**
	 * Constructor
	 *
	 * @param   DataObjectbaseDriver  &$db  Database object
	 *
	 * @since  1.0.0
	 */
	public function __construct(&$db)
	{
		parent::__construct('#__tj_sla_services', 'id', $db);
		$this->setColumnAlias('published', 'state');
	}
}
