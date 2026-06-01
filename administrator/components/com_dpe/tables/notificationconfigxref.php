
<?php
/**
 * @package    Tjfields
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2023 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Table\Table;
use Joomla\CMS\Factory;

/**
 * Table class for selfassessment
 *
 * @package  Emc
 *
 * @since    __DEPLOY_VERSION__
 */
class DpeTableNotificationConfigXref extends Table
{
	/**
	 * Constructor
	 *
	 * @param   \JDatabaseDriver  &$db  \JDatabaseDriver object.
	 */
	public function __construct(&$db)
	{
		parent::__construct('#__dpe_ucm_field_notification_configurations_xref', 'id', $db);
	}

	
}
