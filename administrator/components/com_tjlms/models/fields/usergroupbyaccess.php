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
use Joomla\CMS\Table\Table;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Form\Field\ListField;

JFormHelper::loadFieldClass('list');

/**
 * Custom field aded for coupon code
 *
 * @since  1.6
 */
class JFormFieldusergroupbyaccess extends JFormFieldList
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
		$app       = Factory::getApplication();
		$input     = Factory::getApplication()->input;
		$selectedcourse = $input->get('selectedcourse', '', 'array');

		if (!empty($selectedcourse))
		{
			$selectedcourse = reset($selectedcourse);
		}

		$db = Factory::getDbo();

		// Add Table Path
		Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjlms/tables');
		$courseTable = Table::getInstance('course', 'TjlmsTable', array('dbo', $db));
		$courseTable->load(array('id' => $selectedcourse));

		JLoader::register('ComtjlmsHelper', JPATH_SITE . '/components/com_tjlms/helpers/main.php');
		JLoader::load('ComtjlmsHelper');
		$comtjlmsHelper = new ComtjlmsHelper;

		$groups = $comtjlmsHelper->getACLGroups($courseTable->access);

		$options = array();
		$options[0] = HTMLHelper::_('select.option', '', Text::_('COM_TJLMS_FILTER_USERGROUP'));

		foreach ($groups as $group)
		{
			$options[] = HTMLHelper::_('select.option', $group->id, $group->title);
		}

		return $options;
	}
}
