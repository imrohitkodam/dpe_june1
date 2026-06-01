<?php
/**
 * @package     JLike
 * @subpackage  com_jlike
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Factory;

JLoader::import('components.com_jlike.helpers.jlike', JPATH_ADMINISTRATOR);

/**
 * path controller class.
 *
 * @since  1.6
 */
class JlikeControllerPath extends FormController
{
	/**
	 * function to get category for perticular type.
	 * 
	 * @return  null
	 * 
	 * @since    1.6
	 */
	public function getCategory()
	{
		$jInput = Factory::getApplication()->input;
		$extension = $jInput->post->get('extension', '', 'STRING');
		$JlikeHelper = new JLikeHelper;
		$categories = $JlikeHelper->getCategory($extension);

		if (!empty($categories))
		{
			echo json_encode($categories);
		}
		else
		{
			$categories['empty'] = 1;
			echo json_encode($categories);
		}

		jexit();
	}
}
