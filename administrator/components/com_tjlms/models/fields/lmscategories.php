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

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Access\Access;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Form\Field\ListField;

JFormHelper::loadFieldClass('list');

/**
 * Supports an HTML select list of categories
 *
 * @since  1.0.0
 */
class JFormFieldLmscategories extends JFormFieldList
{
	/**
	 * The form field type.
	 *
	 * @var		string
	 * @since	1.6
	 */
	protected $type = 'Lmscategories';

	/**
	 * Method to get a list of options for a list input.
	 *
	 * @return	array		An array of JHtml options.
	 *
	 * @since   11.4
	 */
	protected function getOptions()
	{
		$userId = Factory::getUser()->id;
		$allowedViewLevels = Access::getAuthorisedViewLevels($userId);

		// Static function options($extension, $config = array('filter.published' => array(0,1)))
		$lang = Factory::getLanguage();
		$tag  = $lang->gettag();

		$lms_cat_options = HTMLHelper::_('category.options',
									'com_tjlms',
									$config = array('filter.published' => array(1), 'filter.language' => array('*', $tag),'filter.access' => $allowedViewLevels)
									);

		foreach ($lms_cat_options as $cat)
		{
			$options[] = HTMLHelper::_('select.option', $cat->value, $cat->text);
		}

		// Merge any additional options in the XML definition.
		$options = array_merge(parent::getOptions(), $options);

		return $options;
	}
}
