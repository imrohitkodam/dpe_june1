<?php
/**    
 * SEO Glossary X Map Plugin
 *
 * We developed this code with our hearts and passion.
 * We hope you found it useful, easy to understand and change.
 * Otherwise, please feel free to contact us at contact@joomunited.com
 *
 * @package 	SEO Glossary
 * @copyright 	Copyright (C) 2012 JoomUnited (http://www.joomunited.com). All rights reserved.
 * @license 	GNU General Public License version 2 or later; http://www.gnu.org/licenses/gpl-2.0.html
 */

defined( '_JEXEC' ) or die( 'Restricted access' );
use Joomla\Utilities\ArrayHelper;

/** Adds support for Seoglossary  to Xmap */
class xmap_com_seoglossary
{
    private static $views = array('glossaries');
    private static $enabled = false;
    
    public static function getTree($xmap, $parent, $params)
    {
    
        $uri = new JUri($parent->link);
        if (!in_array($uri->getVar('view'), self::$views)) {
            return;
        }
       
        $params['groups'] = implode(',', JFactory::getUser()->getAuthorisedViewLevels());
        $params['include_glossary'] = ArrayHelper::getValue($params, 'include_glossary', 1);
        $params['include_glossary'] = ($params['include_glossary'] == 1 || ($params['include_glossary'] == 2 && $xmap->view == 'xml') || ($params['include_glossary'] == 3 && $xmap->view == 'html'));

        $params['priority'] = ArrayHelper::getValue($params, 'priority', $parent->priority);
        $params['changefreq'] = ArrayHelper::getValue($params, 'changefreq', $parent->changefreq);

        if ($params['priority'] == -1) {
            $params['priority'] = $parent->priority;
        }

        if ($params['changefreq'] == -1) {
            $params['changefreq'] = $parent->changefreq;
        }
        switch ($uri->getVar('view')) {
            case 'glossaries':
                self::getGlossaryTree($xmap, $parent, $params, $uri->getVar('catid', 0));
                break;
        }
    }

    private static function getGlossaryTree($xmap, $parent, $params, $cat_id)
    {
        require_once(JPATH_SITE . '/components/com_seoglossary/helpers/seoglossary.php');
        $uri = new JUri($parent->link);
        $db = JFactory::getDbo();
		$nullDate = $db->getNullDate();
		$now = JFactory::getDate()->toSql();
        $query = $db->getQuery(true)
            ->select(array('s.id', 's.tterm','s.catid','s.extlink','s.exturl'))
            ->from('#__seoglossary AS s')
            ->where('s.state = 1');
            $lang = JFactory::getLanguage();
		$query->where( '( g.language LIKE \'' . $lang->getTag() .'\' OR g.language = \'*\' )');
                $query->join( 'LEFT', '#__seoglossaries AS g ON g.id = s.catid' );
		$query->where( 'g.state = 1' );
		$query->where( '(s.catid=' . (int)$cat_id . ')' );
		
		$query->where ("(s.publish_up = ".$db->Quote($nullDate)." OR s.publish_up <= ".$db->Quote($now).")" );
		$query->where ( "(s.publish_down = ".$db->Quote($nullDate)." OR s.publish_down >= ".$db->Quote($now).")");
        $db->setQuery($query);
        $rows = $db->loadObjectList();
        if (empty($rows)) {
            return;
        }
        
        $xmap->changeLevel(1);
        foreach ($rows as $row) {
            $node = new stdclass;
            $node->id = $parent->id;
            $node->name = $row->tterm;
            $node->uid = $parent->uid . '_tid_' . $row->id;
            $node->browserNav = $parent->browserNav;
            $node->priority = $params['priority'];
            $node->changefreq = $params['changefreq'];
            $node->pid = $parent->id;
           
			
            // workaround
            if (strpos($parent->link, '&Itemid=') === false) {
                $url= SeoglossaryHelper::getUrlLink($row,$parent->id);
            }
            else
            {
                $url= SeoglossaryHelper::getUrlLink($row);
                
            }
              if((@$row->extlink)&&($row->exturl))
              {
                $node->link=$url;       
              }
              else
              {
                 $node->link=substr_replace(JURI::base(), "", -1).JRoute::_($url);
                  
             }
            $xmap->printNode($node);
            
        }

        $xmap->changeLevel(-1);
    }
    
}
?>