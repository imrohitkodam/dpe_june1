<?php
/**
 * @package     TMT
 * @subpackage  com_shika
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die();
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;


/**
 * TMT utility class for common methods
 *
 * @since  _DEPLOY_VERSION_
 */
class TmtUtilities
{
	/**
	 * Method to get options for category list filter/dropdown
	 *
	 * @param   string  $extension     Category extension
	 *
	 * @param   int     $dropDownList  Return dropdown list to be used in filters
	 *
	 * @return	array	$options  id and title of all categories
	 *
	 * @since	1.3.31
	 */
	public function categories($extension = 'com_tmt', $dropDownList = false)
	{
		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_categories/models/', 'CategoriesModel');
		$model = BaseDatabaseModel::getInstance('Categories', 'CategoriesModel', array('ignore_request' => true));

		$model->setState('filter.extension', $extension);
		$model->setState('filter.published', 1);
		$categoryList = $model->getItems();

		if ($dropDownList)
		{
			$options = array ();
			$options[] = HTMLHelper::_('select.option', '', Text::_('COM_TMT_SELECT_CATEGORY'));

			foreach ($categoryList as $cat)
			{
				$options[] = HTMLHelper::_('select.option', $cat->id, $cat->title);
			}

			return $options;
		}

		return $categoryList;
	}

	/**
	 * Method to get options for question difficulty list filter/dropdown
	 *
	 * @param   int  $dropDownList  Return dropdown list to be used in filters
	 *
	 * @return	array	$options  key - value pairs of question difficulty levels
	 *
	 * @since	1.3.31
	 */
	public function questionDifficultyLevels($dropDownList = false)
	{
		$difficultyLevels = array (
			'easy'   => Text::_('COM_TMT_DIFF_LEVEL_EASY'),
			'medium' => Text::_('COM_TMT_DIFF_LEVEL_MEDIUM'),
			'hard'   => Text::_('COM_TMT_DIFF_LEVEL_HARD')
		);

		if ($dropDownList)
		{
			$options = array ();
			$options[] = HTMLHelper::_('select.option', '', Text::_('COM_TMT_SELECT_LEVEL'));

			foreach ($difficultyLevels as $key => $value)
			{
				$options[] = HTMLHelper::_('select.option', $key, $value);
			}

			return $options;
		}

		return $difficultyLevels;
	}

	/**
	 * Method to get options for question types list filter/dropdown
	 *
	 * @param   int  $dropDownList  Return dropdown list to be used in filters
	 *
	 * @return	array	$options  key - value pairs of question types
	 *
	 * @since	1.3.31
	 */
	public function questionTypes($dropDownList = false)
	{
		$questionTypes = array (
			'radio'       => Text::_('COM_TMT_QTYPE_MCQ_SINGLE'),
			'checkbox'    => Text::_('COM_TMT_QTYPE_MCQ_MULTIPLE'),
			'text'        => Text::_('COM_TMT_QTYPE_SUB_TEXT'),
			'textarea'    => Text::_('COM_TMT_QTYPE_SUB_TEXTAREA'),
			'objtext'     => Text::_('COM_TMT_QTYPE_OBJ_TEXT'),
			'file_upload' => Text::_('COM_TMT_QTYPE_ONLY_QUESTION'),
			'rating'      => Text::_('COM_TMT_QTYPE_RATING')
		);

		if ($dropDownList)
		{
			$options = array ();
			$options[] = HTMLHelper::_('select.option', '', Text::_('COM_TMT_SELECT_QTYPE'));

			foreach ($questionTypes as $key => $value)
			{
				$options[] = HTMLHelper::_('select.option', $key, $value);
			}

			return $options;
		}

		return $questionTypes;
	}

	/**
	 * Method to get time format in hours , mins, seconds
	 *
	 * @param   int  $time  total time in secs
	 *
	 * @return  string
	 *
	 * @since  1.3.31
	 */
	public function timeFormat($time)
	{
		$attemptTimeTaken = array();

		$timeHr = floor($time / 3600);

		if ($timeHr)
		{
			$attemptTimeTaken[] = Text::sprintf('COM_TMT_HOURS', $timeHr);
		}

		$timeMin = floor(($time / 60) % 60);

		if ($timeMin != 0)
		{
			$attemptTimeTaken[] = Text::sprintf('COM_TMT_MINUTES', $timeMin);
		}

		$timeSec = floor($time % (60));

		if ($timeSec != 0)
		{
			$attemptTimeTaken[] = Text::sprintf('COM_TMT_SECONDS', $timeSec);
		}

		return implode(' , ', $attemptTimeTaken);
	}
}
