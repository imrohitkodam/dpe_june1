<?php
/**
 * @package     Multiagency
 * @subpackage  com_multiagency
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2021 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Table\Table;
use Joomla\Data\DataObject;

use Joomla\Utilities\ArrayHelper;
/**
 * licence xref Table class
 *
 * @since  __DEPLOY_VERSION__
 */
class MultiagencyTablelicencexref extends Table
{
	/**
	 * Constructor
	 *
	 * @param   DataObjectbase  &$db  A database connector object
	 */
	public function __construct(&$db)
	{
		parent::__construct('#__tjmultiagency_licences_xref', 'id', $db);
	}
}
