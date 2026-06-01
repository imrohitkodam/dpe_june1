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
 * Tjlms cluster xref table class
 *
 * @since  1.0.0
 */
class DpeTableChecklistNextReviewDate extends Table
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
		parent::__construct('#__dpe_checklist_next_date_of_review_xref', 'id', $db);
	}
}
