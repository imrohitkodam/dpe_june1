<?php
/**
 * @version    SVN: <svn_id>
 * @package    Techjoomla.Libraries
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2015 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

// No direct access
defined('_JEXEC') or die('Restricted access');
jimport('joomla.application.component.view');
jimport('techjoomla.view.csv');

/**
 * TjCsv
 *
 * @package     Techjoomla.Libraries
 * @subpackage  TjCsv
 * @since       1.0
 */
class TjlmsViewActivities extends TjExportCsv
{
	/**
	 * call exportCsv function from techjoomla (techjoomla.view.csv) library.
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  Toolbar instance
	 *
	 * @since	1.6
	 */
	public function display($tpl = null)
	{
		parent::display();
	}
}
