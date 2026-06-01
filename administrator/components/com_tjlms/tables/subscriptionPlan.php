<?php
/**
 * @package     Shika
 * @subpackage  com_tjlms
 * 
 * @author      Techjoomla extensions@techjoomla.com
 * 
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Table\Table;

/**
 * Subscription plans Table class.
 *
 * @since  1.3.35
 */
class TjlmsTableSubscriptionPlan extends Table
{
	/**
	 * Constructor
	 *
	 * @param   JDatabaseDriver  &$db  Database connector object
	 *
	 * @since   1.3.35
	 */
	public function __construct(&$db)
	{
		parent::__construct('#__tjlms_subscription_plans', 'id', $db);
	}
}
