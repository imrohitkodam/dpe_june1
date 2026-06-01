<?php
/**
 * @package    Tjlms
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2018 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */
// No direct access.
defined('_JEXEC') or die;
jimport('joomla.application.component.controlleradmin');
jimport('joomla.filesystem.folder');

use Joomla\CMS\Factory;
use Joomla\Utilities\ArrayHelper;

JLoader::register('TjlmsControllerLessons', JPATH_COMPONENT_ADMINISTRATOR . '/controllers/lessons.php');

/**
 * Lessons list controller class.
 *
 * @since  1.3.4
 */
class TjlmsControllerManagelessons extends TjlmsControllerLessons
{
	/**
	 * Proxy for getModel.
	 *
	 * @param   string  $name    The model name. Optional.
	 * @param   string  $prefix  The class prefix. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return  object  The model.
	 *
	 * @since   1.3.4
	 */
	public function getModel($name = 'lessonform', $prefix = 'TjlmsModel', $config = array())
	{
		$model = parent::getModel($name, $prefix, array( 'ignore_request' => true ));

		return $model;
	}
}
