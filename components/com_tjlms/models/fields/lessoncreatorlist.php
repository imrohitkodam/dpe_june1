<?php
/**
 * @package    Shika
 * @author     TechJoomla | <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2005 - 2014. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * Shika is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

// No direct access
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;

JFormHelper::loadFieldClass('list');
use Joomla\CMS\Factory;

/**
 * Supports an HTML select list
 *
 * @since  1.3.4
 */
class FormFieldLessoncreatorlist extends FormFieldList
{
	protected $user;

	protected $canManageMaterial;

	protected $canManageMaterialOwn;

	/**
	 * Method to get a list of options for a list input.
	 *
	 * @return	array		An array of JHtml options.
	 *
	 * @since   1.3.4
	 */
	protected function getOptions()
	{
		if (!class_exists('TjlmsModelManagelessons'))
		{
			$path = JPATH_SITE . '/components/com_tjlms/models/managelessons.php';
			JLoader::register('TjlmsModelManagelessons', $path);
		}

		$tjlmsModelManagelessons = new TjlmsModelManagelessons;

		$componentParams = ComponentHelper::getParams('com_tjlms');
		$param = $componentParams->get('show_user_or_username', 'name');

		$lessonCreators = $tjlmsModelManagelessons->getLessonCreators(1);

		$lessonCreatorsOption = array();
		$lessonCreatorsOption[] = HTMLHelper::_('select.option', 0, Text::_('JOPTION_SELECT_AUTHOR'));

		foreach ($lessonCreators as $lessonCreator)
		{
			$lessonCreatorsOption[] = HTMLHelper::_('select.option', $lessonCreator->created_by, $lessonCreator->$param);
		}

		return $lessonCreatorsOption;
	}
}
