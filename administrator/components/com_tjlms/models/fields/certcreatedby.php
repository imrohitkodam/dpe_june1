<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Tjlms
 * @copyright  Copyright (C) 2005 - 2017. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * Shika is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

defined('JPATH_BASE') or die;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Form\Field\ListField;

JFormHelper::loadFieldClass('list');

/**
 * Custom field for get certificate creator list
 *
 * @since  1.6
 */
class JFormFieldCertCreatedby extends JFormFieldList
{
	/**
	 * Method to get a list of options for a list input.
	 *
	 * @return	array		An array of JHtml options.
	 *
	 * @since   11.4
	 */
	protected function getOptions()
	{
		$tjlmsparams = ComponentHelper::getParams('com_tjlms');
		$showUserOrUsername = $tjlmsparams->get('show_user_or_username', 'name');

		$db = Factory::getDbo();
		$query = $db->getQuery(true);

		if ($showUserOrUsername)
		{
			$query->select('DISTINCT ct.created_by,u.username as name');
		}
		else
		{
			$query->select('DISTINCT ct.created_by,u.name');
		}

		$query->where('u.block = 0');
		$query->from('#__tjlms_certificate_template AS ct');
		$query->join('inner', '#__users AS u ON ct.created_by=u.id');
		$db->setQuery($query);

		$results = $db->loadObjectList();
		$options = array();
		$options[] = HTMLHelper::_('select.option', '', Text::_('COM_TJLMS_FILTER_SELECT_USER'));

		if ($results)
		{
			foreach ($results as $result)
			{
				$options[] = HTMLHelper::_('select.option', $result->created_by, $result->name);
			}
		}

		return $options;
	}
}
