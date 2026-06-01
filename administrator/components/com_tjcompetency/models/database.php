<?php
/**
 * @package     TjCompetency
 * @subpackage  com_tjcompetency
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2021 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;

require_once JPATH_ADMINISTRATOR . '/components/com_installer/models/database.php';

/**
 * Manage TjCompetency database operations
 *
 * @since  1.0.0
 */
class TjCompetencyModelDatabase extends InstallerModelDatabase
{
	protected $extensionName = 'com_tjcompetency';
}
