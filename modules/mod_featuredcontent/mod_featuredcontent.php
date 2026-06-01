<?php

/**
 * @package     Joomla.Site
 * @subpackage  mod_custom
 *
 * @copyright   (C) 2009 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Component\ComponentHelper;

require_once  JPATH_ROOT.'/modules/mod_featuredcontent/helper.php';


    $articleType = $params->get('articleType');
    $sliderTimer = $params->get('sliderTimer');
    $tag         = ($params->get('tags'))?$params->get('tags'):'';
    $category    = ($params->get('catid'))?$params->get('catid'):''; 
    $ordering    = ($params->get('ordering'))?$params->get('ordering'):'DESC'; 
    $contentDatas  = ModFeaturedContentHelper::getContentDetails($articleType, $tag, $category, $ordering);

     
require ModuleHelper::getLayoutPath('mod_featuredcontent', $params->get('layout', 'default'));
