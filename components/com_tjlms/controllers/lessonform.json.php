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
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

JLoader::register('TjlmsControllerLesson', JPATH_COMPONENT_ADMINISTRATOR . '/controllers/lesson.json.php');

/**
 * Lesson controller class.
 *
 * @since  1.0.0
 */
class TjlmsControllerLessonform extends TjlmsControllerLesson
{
		/**
		 * Retrieves a model from the model folder
		 *
		 * @param   string  $name    The model name to instantiate
		 * @param   array   $prefix  Configuration array for model. Optional.
		 * @param   array   $config  Configuration array for model. Optional.
		 * 
		 * @return  BaseDatabaseModel|boolean object or false on failure
		 *
		 * @since   1.3.4
		 *
		 **/

		public function getmodel($name = '', $prefix = '', $config = Array())
		{
			require_once JPATH_ADMINISTRATOR . '/components/com_tjlms/models/lesson.php';

			$model = new TjlmsmodelLesson;

			return $model;
		}
}
