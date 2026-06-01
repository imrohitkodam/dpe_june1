<?php
/**
 * @package    Com_Dpe
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2018 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;
use Joomla\Data\DataObject;

use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;

/**
 * Xref  Tableto store the ticket and logs time spent data
 *
 * @since  1.0.0
 */
class DpeTableticketstimelog extends Table
{

	/**
	 * Constructor
	 *
	 * @param   DataObjectbase  &$db  A database connector object
	 */
	public function __construct(&$db)
	{
		parent::__construct('#__ticket_log_timelogs_xref', 'id', $db);
		$this->_trackAssets = false;
	}

}
