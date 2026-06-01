<?
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */
defined('_JEXEC') or die; ?>


<rss version="2.0"
     xmlns:atom="http://www.w3.org/2005/Atom"
     xmlns:dc="http://purl.org/dc/elements/1.1/"
     xmlns:sy="http://purl.org/rss/1.0/modules/syndication/">

   <channel>
      <title><?=escape($sitename)?> - <?=translate('Activities')?></title>
      <description></description>
      <link><?=$base_url?></link>
      <lastBuildDate><?= count($activities) ? helper('date.format', array(
                           'date' => $activities->top()->created_on,
                           'gmt_offset' => 0,
                           'format' => 'r'
                        )) : ''
      ?></lastBuildDate>
      <generator>Joomlatools LOGman</generator>
      <language><?=$language?></language>
      
      <dc:language><?= JFactory::getLanguage()->getTag() ?></dc:language>
      <dc:rights>Copyright <?= helper('date.format', array('format' => 'Y')) ?></dc:rights>
      <dc:date><?= count($activities) ? helper('date.format', array(
                       'date' => $activities->top()->created_on,
                       'gmt_offset' => 0,
                       'format' => 'r'
                    )) : ''
      ?></dc:date>  
      
      <sy:updatePeriod><?= $update_period ?></sy:updatePeriod>
      <sy:updateFrequency><?= $update_frequency ?></sy:updateFrequency>
      
      <atom:link href="<?=$base_url?>" rel="self" type="application/rss+xml"/>
        
      <?foreach($activities as $activity):?>
      <item>
         <title><?=escape(helper('activity.activity', array(
             'entity' => $activity,
             'html' => false
         ))) ?></title>
         <dc:creator><?= escape($activity->getAuthor()->getName())?></dc:creator>
         <description><![CDATA[<?=helper('activity.activity', array(
             'entity' => $activity,
             'escaped_urls' => true,
             'links' => false,
             'fqr' => true
         )) ?>
         ]]></description>
         <pubDate><?=helper('date.format', array(
                     'date' => $activity->created_on,
                     'gmt_offset' => 0,
                     'format' => 'r'
         ))?></pubDate>
         <dc:date><?=helper('date.format', array(
                     'date' => $activity->created_on,
                     'gmt_offset' => 0,
                     'format' => 'r'
         ))?></dc:date>
      </item>
      <?endforeach?>
   </channel>
</rss>
