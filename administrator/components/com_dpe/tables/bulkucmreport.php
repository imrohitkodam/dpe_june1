
<?php
/**
 * @package    DPE
 *
 * @author     Techjoomla <contact@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license    The GNU General Public License version 2 (GPLv2); see LICENSE.txt
 */

defined('_JEXEC') or die;
use Joomla\CMS\Table\Table;
use Joomla\Data\DataObject;

/**
 * Dpe BulkUcm Report table class
 *
 * @since  1.0.0
 */
class DpeTableBulkucmreport extends Table
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
		parent::__construct('#__ucm_reports_download_detail', 'id', $db);
		
	}
}
