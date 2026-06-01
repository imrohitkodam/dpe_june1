<?php
/**
 * @package     TjCompetency
 * @subpackage  com_tjcompetency
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2021 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access
defined('_JEXEC') or die('Restricted access');
jimport('joomla.application.component.view');
jimport('techjoomla.view.csv');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

/**
 * TjCsv
 *
 * @package     Techjoomla.Libraries
 * @subpackage  TjCsv
 * @since       1.0
 */
class TjCompetencyViewSkills extends TjExportCsv
{
	/**
	 * Display view
	 *
	 * @param   STRING  $tpl  template name
	 *
	 * @return  Object|Boolean in case of success instance and failure - boolean
	 *
	 * @since	1.0
	 */
	public function display($tpl = null)
	{
		$input = Factory::getApplication()->input;
		$user  = Factory::getUser();

		$this->canDo = JHelperContent::getActions('com_tjcompetency');

		if (!$this->canDo || !$user->id)
		{
			// Redirect to the list screen.
			$redirect = JRoute::_('index.php?option=com_tjcompetency&view=skills', false);
			Factory::getApplication()->redirect($redirect, Text::_('JERROR_ALERTNOAUTHOR'));

			return false;
		}
		else
		{
			$this->items = $this->get('Items');
			
			foreach ($this->items as $key => &$item)
			{
				unset($item->description);
			}

			if ($input->get('task') == 'download')
			{
				$fileName = $input->get('file_name');
				$this->download($fileName);
				Factory::getApplication()->close();
			}
			else
			{
				parent::display();
			}
		}
	}
}
