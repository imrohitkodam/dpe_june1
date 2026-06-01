<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_tjlms
 *
 * @copyright   Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;

JFormHelper::loadFieldClass('list');

/**
 * Supports an HTML select list of categories
 *
 * @since  1.0.0
 */
class JFormFieldGetArticles extends JFormFieldList
{
	protected $type = 'GetArticles';

	/**
	 * Method to get a list of options for a list input.
	 *
	 * @return	array An array of JHtml options.
	 *
	 * @since   11.4
	 */
	protected function getOptions()
	{
		$db = Factory::getDBO();
		$query = "SELECT id,title FROM #__content WHERE state=1";
		$db->setQuery($query);
		$getarticallist = $db->loadobjectList();
		$options = array();

		if ($getarticallist)
		{
			foreach ($getarticallist as $artical)
			{
				$options[] = JHTML::_('select.option', $artical->id, $artical->title);
			}
		}

		$options = array_merge(parent::getOptions(), $options);

		return $options;
	}
}
