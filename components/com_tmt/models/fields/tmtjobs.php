<?php
/**
 * @package     TMT
 * @subpackage  com_shika
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_BASE') or die;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;

FormHelper::loadFieldClass('list');

/**
 * Supports an HTML select list of categories
 *
 * @since       1.0
 *
 * @deprecated  1.4.0  This class will be removed and no replacement will be provided
 */
class FormFieldTmtjobs extends FormFieldList
{
	/**
	 * The form field type.
	 *
	 * @var		string
	 * @since	1.6
	 */
	protected $type = 'tmtjobs';

	/**
	 * Method to get a list of options for a list input.
	 *
	 * @return	array		An array of HTMLHelper options.
	 *
	 * @since   11.4
	 */
	protected function getOptions()
	{
		$options = array();

		$db = Factory::getDbo();

		$query = $db->getQuery(true);
		$query->select('j.id, j.title');
		$query->from('`#__ja_jobs` AS j');

		// Get all company users
		$subUsersHelper = new subUsersHelper;
		$companyUsers   = $subUsersHelper->getMyCompaniesListData(Factory::getUser()->id);

		if (!empty($companyUsers))
		{
			$companyUsers = implode(',', $companyUsers);

			// Creator id can be any user from logged in user's company
			$query->where('j.user_id IN (' . $companyUsers . ')');
		}
		else
		{
			$query->where('j.user_id=' . Factory::getUser()->id);
		}

		// Show only Approved(open) jobs
		$query->where("j.status = 'Approved'");

		$db->setQuery($query);
		$jobs = $db->loadObjectList();

		$options[] = HTMLHelper::_('select.option', '', Text::_('COM_TMT_SELECT_JOB'));

		foreach ($jobs as $job)
		{
			$options[] = HTMLHelper::_('select.option', $job->id, $job->title);
		}

		// Merge any additional options in the XML definition.
		$options = array_merge(parent::getOptions(), $options);

		return $options;
	}
}
