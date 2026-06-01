<?php
/**
 * @package     LMS_Shika
 * @subpackage  mod_lms_taglessons
 * @copyright   Copyright (C) 2014 - 2023 Techjoomla. All rights reserved.
 * @license     GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link        http://www.techjoomla.com
 */

// No direct access.
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

/**
 * Helper for mod_lms_taglessons
 *
 * @since  1.3.41
 */
class ModLmsTagLessonsHelper
{
	/**
	 * Get data of lessons
	 *
	 * @param   Array  $params  params array
	 *
	 * @return  Array
	 *
	 * @since   1.3.41
	 */
	public function getLessons($params)
	{
		$lessonCategory = $params->get('lesson_category');
		$fetchLimit     = $params->get('limit');
		$input          = Factory::getApplication()->input;
		$lessonTags     = $input->get('tagid');

		if (empty($lessonTags))
		{
			$lessonTags = $params->get('tags', '', array());
		}

		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjlms/models', 'TjlmsModel');
		$model = BaseDatabaseModel::getInstance('Lessons', 'TjlmsModel', array('ignore_request' => true));
		$model->setState('filter.state', (int) 1);
		$model->setState('filter.in_lib', (int) 1);

		if (!empty($lessonCategory))
		{
			$model->setState('filter.catid', $lessonCategory);
		}

		if (!empty($lessonTags))
		{
			$model->setState('filter.tag_id', $lessonTags);
		}

		if (!empty($fetchLimit))
		{
			$model->setState('list.limit', $fetchLimit);
		}

		$model->setState('list.ordering', 'a.id');
		$model->setState('list.direction', 'DESC');
		$lessons = $model->getItems();

		JLoader::import('main', JPATH_SITE . '/components/com_tjlms/helpers');
		$comtjlmsHelper = new comtjlmsHelper;

		foreach ($lessons as $lesson)
		{
			if (!empty($lesson->image))
			{
				if (!empty($lesson->imagepath) && !empty($lesson->imagefile))
				{
					$lesson->lessonImage = Route::_(Uri::base() . $lesson->imagepath . $lesson->imagefile);
				}
				else
				{
					$lesson->lessonImage = Route::_(Uri::base() . 'media/com_tjlms/images/default/lesson.png');
				}
			}

			if (empty($lesson->image))
			{
				$lesson->lessonImage = Route::_(Uri::base() . 'media/com_tjlms/images/default/lesson.png');
			}

			$lesson->lessonUrl = $comtjlmsHelper->tjlmsRoute('index.php?option=com_tjlms&view=lesson&lesson_id=' . $lesson->id);
		}

		return $lessons;
	}
}
