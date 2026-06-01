<?php
/**
 * Finder Search SEO Glossary plugin file
 *
 * We developed this code with our hearts and passion.
 * We hope you found it useful, easy to understand and change.
 * Otherwise, please feel free to contact us at contact@joomunited.com
 *
 * @package 	SEO Glossary
 * @copyright 	Copyright (C) 2012 JoomUnited (http://www.joomunited.com). All rights reserved.
 * @license 	GNU General Public License version 2 or later; http://www.gnu.org/licenses/gpl-2.0.html
 */

// no direct access

defined('JPATH_BASE') or die;
use Joomla\Registry\Registry;
JLoader::register('FinderIndexerAdapter', JPATH_ADMINISTRATOR . '/components/com_finder/helpers/indexer/adapter.php');
require_once JPATH_SITE . '/components/com_seoglossary/helpers/seoglossary.php';

/**
 * Smart Search adapter for com_seoglossary.
 *
 * @since  2.5
 */
class PlgFinderseoglossary extends FinderIndexerAdapter
{
   /**
	 * The plugin identifier.
	 *
	 * @var    string
	 * @since  2.5
	 */
    protected $context = 'glossary';
   
	/**
	 * The extension name.
	 *
	 * @var    string
	 * @since  2.5
	 */
    protected $extension = 'com_seoglossary';
   /**
	 * The sublayout to use when rendering the results.
	 *
	 * @var    string
	 * @since  2.5
	 */
    protected $layout = 'glossary';
   
	/**
	 * The type of content that the adapter indexes.
	 *
	 * @var    string
	 * @since  2.5
	 */
    protected $type_title = 'glossary';
    /**
	 * The table name.
	 *
	 * @var    string
	 * @since  2.5
	 */
    protected $table = '#__seoglossary';
    
    protected $state_field = 'state';
    /**
	 * Load the language file on instantiation.
	 *
	 * @var    boolean
	 * @since  3.1
	 */
    protected $autoloadLanguage = true;
   /**
	 * Method to index an item. The item must be a Result object.
	 *
	 * @param   Result  $item  The item to index as a Result object.
	 *
	 * @return  void
	 *
	 * @since   2.5
	 * @throws  Exception on database error.
	 */
    protected function index(FinderIndexerResult $item, $format = 'html')
	{
		// Check if the extension is enabled
		if (JComponentHelper::isEnabled($this->extension) == false)
		{
			return;
		}
		  // Check if the extension is enabled.
        if (JComponentHelper::isEnabled($this->extension) == false)
        {
            return;
        }
        $item->setLanguage();
        $extension = ucfirst(substr($item->extension, 4));
        $Itemid=SeoglossaryHelper::getGlossaryMenu( $item->catid);
        $item->title=$item->tterm;
		$item_id="";
        if ((@$item->extlink) && ($item->exturl))
				{
				if (strpos($item->exturl, 'menu:') !== false)
					{
					$link = explode(":", $item->exturl);
					$menuitem = intval($link[1]);
					$url = 'index.php?Itemid=' . $menuitem;
					}
				  else if (strpos($item->exturl, 'k2:') !== false)
					{
					$link = explode(":", $item->exturl);
					require_once (JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_k2' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'route.php');

					require_once (JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_k2' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'utilities.php');

					$db = JFactory::getDbo();
					$query = 'SELECT i.*, c.name AS categoryname,c.id AS categoryid, c.alias AS categoryalias, c.params AS categoryparams FROM #__k2_items as i 		LEFT JOIN #__k2_categories c ON c.id = i.catid where i.id=' . (int)$link[1];
					$db->setQuery($query);
					$item = $db->loadObject();
					$url = urldecode(JRoute::_(K2HelperRoute::getItemRoute($item->id . ':' . urlencode($item->alias) , $item->catid . ':' . urlencode($item->categoryalias))));
					}
				  else
					{
					$url = $item->exturl;
					}
				}
			  else if ($Itemid == 0)
				{
					
					
				if($Itemid)
				{
					$item_id='&Itemid='.$Itemid;
				}
				$url = 'index.php?option=com_seoglossary&view=glossary&catid=' . $item->catid . '&id=' . $item->id.$item_id;
				}
			  else
				{
				$url ='index.php?option=com_seoglossary&view=glossary&catid=' . $item->catid . '&id=' . $item->id . '&Itemid=' . $Itemid;
				}
                 $item->url=$url;
        $item->route=$this->removeAdminSegment( $item->url);
		$item->path =$item->route;
		$item->description=$item->tdefinition;
      if($item->created)
      {
         
      }
      else
      {
         $item->created=JFactory::getDate()->toSql();
      }
        $item->publish_start_date=$item->created;
        $item->start_date=$item->created;
        $item->language=$item->language;
        $item->published=$item->state;
        $item->state=$item->state;
        $item->access=1;
        // Add the type taxonomy data.
        $item->addTaxonomy('Type', 'glossary');


        // Get content extras.
        FinderIndexerHelper::getContentExtras($item);

        // Index the item.
        $this->indexer->index($item);
        
	}
      /**
     * Method to setup the indexer to be run.
     *
     * @return  boolean  True on success.
     *
     * @since   2.5
     */
    protected function setup()
    {

        return true;
    }
    
    private function removeAdminSegment($url = '')
	{
		if ($url) {
			$url = ltrim($url, '/');
			$url = str_replace('administrator/', '', $url);
		}

		return $url;
	}
     /**
     * Method to get the SQL query used to retrieve the list of content items.
     *
     * @param   mixed  $query  A JDatabaseQuery object or null.
     *
     * @return  JDatabaseQuery  A database object.
     *
     * @since   2.5
     */
    protected function getListQuery($query = null)
    {
        $db = JFactory::getDbo();

        // Check if we can use the supplied SQL query.
        $query = $query instanceof JDatabaseQuery ? $query : $db->getQuery(true)
            ->select('a.*,g.language as language')
            ->from('#__seoglossary AS a')
            ->join( 'LEFT', '#__seoglossaries AS g ON g.id = a.catid' );
            
        return $query;
    }
    

}