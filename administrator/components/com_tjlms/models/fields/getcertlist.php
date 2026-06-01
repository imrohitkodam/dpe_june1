<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Tjlms
 * @copyright  Copyright (C) 2005 - 2014. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * Shika is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

defined('JPATH_BASE') or die;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Form\Field\ListField;

JFormHelper::loadFieldClass('list');

/**
 * Custom field aded for coupon code
 *
 * @since  1.6
 */
class JFormFieldGetcertlist extends JFormFieldList
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
		$user = Factory::getUser();
		$db = Factory::getDbo();

		// Create a new query object.
		$query = $db->getQuery(true);

		$query->select('title,id');
		$query->from('#__tjlms_certificate_template');
		$query->where($db->quoteName('state') . " = 1");
		$query->where('(' . $db->quoteName('access') . " = 1 OR " . $db->quoteName('created_by') . " = " . $db->quote($user->id) . ')');
		$db->setQuery($query);

		$certlist = $db->loadObjectList();
		$options = array();
		$options[0] = HTMLHelper::_('select.option', '', Text::_('COM_TJLMS_CERTIFICATE_SELECT_CERTIFICATE'));

		foreach ($certlist as $cert)
		{
			$options[] = HTMLHelper::_('select.option', $cert->id, $cert->title);
		}

		return $options;
	}
}
