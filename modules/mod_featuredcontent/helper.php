<?php
/**
 * @package     Shika
 * @subpackage  mod_featuredcontent
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2024 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Component\ComponentHelper;

/**
 * Helper for mod_lms_filter
 *
 * @since  1.0
 */

class ModFeaturedContentHelper
{


   public static function getContentDetails($articleType, $tag, $category, $ordering)
   {


      $db = Factory::getDBO();
      $query = $db->getQuery('true');
      $query->select('c.id,c.catid,c.alias,c.title,c.introtext,c.images');
      $query->from('#__content as c');

      if ($articleType == 'featured')
      {
       $query->where('c.featured = 1');
    }
    elseif ($articleType == 'tag')
    {  
       $tag = implode(',', (is_array($tag))?$tag:(array)$tag);
      

       if ($tag)
       {
          $query->leftJoin($db->quoteName('#__contentitem_tag_map', 'tag'),
          $db->quoteName('tag.content_item_id') . ' = ' . $db->quoteName('c.id')
       );
         $query->where('tag.tag_id IN ( ' . $tag.')');
       }
       
    }
    if($category)
    {
       $category = implode(',', $category);
       $query->where('c.catid IN ( ' . $category.')');

    }

    $ordering = ($ordering)?$ordering:'desc';

   $today = Factory::getDate()->toSql();
   $query->where($db->quoteName('c.publish_up') . ' <= ' . $db->quote($today));

   $query->where('c.state = 1')->order('c.created'.' '.$ordering);

 
    $db->setQuery($query);
    

    $contentDatas = $db->loadObjectList();

    foreach($contentDatas as $key => $contentData)
    { 
      $introText = 
      $contentDatas[$key]->introtext = substr(strip_tags($contentData->introtext,'<p>'), 0,250).'...';

      $slug = $contentData->alias ? ($contentData->id . ':' . $contentData->alias) : $contentData->id;

      $contentDatas[$key]->link = Route::_(version_compare(JVERSION, '4.0.0', '<') ? ContentHelperRoute::getArticleRoute($slug, $contentData->catid, $contentData->language) : Joomla\Component\Content\Site\Helper\RouteHelper::getArticleRoute($slug, $contentData->catid, $contentData->language));    
   }

   return $contentDatas;
}


}

