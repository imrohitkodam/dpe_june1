<?php
/**
 * @version    SVN: <svn_id>
 * @package    Tjlms
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2018 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

defined('_JEXEC') or die;

jimport('joomla.application.component.modellist');

use Joomla\CMS\Factory;

JLoader::register('TjlmsModelLessons', JPATH_COMPONENT_ADMINISTRATOR . '/models/lessons.php');

/**
 * Tjlms model.
 *
 * @since  1.3.4
 */
class TjlmsModelManagelessons extends TjlmsModelLessons
{
}
