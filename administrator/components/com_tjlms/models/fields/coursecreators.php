<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Tjlms
 * @copyright  Copyright (C) 2005 - 2014. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * TMT is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

defined('JPATH_BASE') or die;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Form\Field\ListField;

JFormHelper::loadFieldClass('list');

/**
 * Supports an HTML select list of categories
 *
 * @since  1.6
 */
class JFormFieldcoursecreators extends JFormFieldList
{
	/**
	 * The form field type.
	 *
	 * @var		string
	 *
	 * @since	1.6
	 */
	protected $type = 'coursecreators';

	/**
	 * Method to get the field input markup.
	 *
	 * @return	string	The field input markup.
	 *
	 * @since	1.3
	 */
	protected function getOptions()
	{
		if (!class_exists('TjlmsModelcourses'))
		{
			$path = JPATH_SITE . '/components/com_tjlms/models/courses.php';
			JLoader::register('TjlmsModelcourses', $path);
		}

		$tjlmsModelcourses = new TjlmsModelcourses;

		$componentParams = ComponentHelper::getParams('com_tjlms');
		$param = $componentParams->get('show_user_or_username', 'name');

		$creators = $tjlmsModelcourses->getCourseCreators();

		$courseCreatorsOption = array();
		$courseCreatorsOption[] = HTMLHelper::_('select.option', '', "- SELECT CREATOR -");

		foreach ($creators as $courseCreator)
		{
			$courseCreatorsOption[] = HTMLHelper::_('select.option', $courseCreator->created_by, $courseCreator->$param);
		}

		$options = array_merge(parent::getOptions(), $courseCreatorsOption);

		return $courseCreatorsOption;
	}
}
