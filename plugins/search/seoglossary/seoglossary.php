<?php
/**
 * Search SEO Glossary plugin file
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
defined( '_JEXEC' ) or die ;

jimport( 'joomla.plugin.plugin' );

/**
 * Seoglossary Search plugin
 *
 * @package		Joomla.Plugin
 * @subpackage	Search.glossary
 * @since		1.6
 */
class plgSearchSeoglossary extends JPlugin
{
	/**
	 * Constructor
	 *
	 * @access      protected
	 * @param       object  $subject The object to observe
	 * @param       array   $config  An array that holds the plugin configuration
	 * @since       1.5
	 */
	public function __construct( &$subject, $config )
	{

		parent::__construct( $subject, $config );
		$this->loadLanguage( );

	}
	
	public function onContentSearchAreas()
    {
        return $this->onSearchAreas();
    }

	/**
	 * @return array An array of search areas
	 */
	function onSearchAreas()
	{

		if ( !is_dir( JPATH_SITE . '/components/com_seoglossary/models' ) )
		{
			return;
		}
		require_once(JPATH_SITE . '/components/com_seoglossary/helpers/seoglossary.php');
		SeoglossaryHelper::loadLanguage();
		static $areas = array( 'seoglossary' => 'PLG_SEARCH_SEOGLOSSARY_SEARCH_AREA' );
		return $areas;
	}

	/**
	 * Weblink Search method
	 *
	 * The sql must return the following fields that are used in a common display
	 * routine: href, title, section, created, text, browsernav
	 * @param string Target search string
	 * @param string mathcing option, exact|any|all
	 * @param string ordering option, newest|oldest|popular|alpha|category
	 * @param mixed An array if the search it to be restricted to areas, null if search all
	 */
	function onContentSearch( $text, $phrase = '', $ordering = '', $areas = null )
	{

		if ( !is_dir( JPATH_SITE . '/components/com_seoglossary/models' ) )
		{
			return;
		}
		require_once(JPATH_SITE . '/components/com_seoglossary/helpers/seoglossary.php');
		SeoglossaryHelper::loadLanguage();

		$db = JFactory::getDbo( );
		$app = JFactory::getApplication( );
		$user = JFactory::getUser( );
		$groups = implode( ',', $user->getAuthorisedViewLevels( ) );

		$searchText = $text;

		if ( is_array( $areas ) )
		{
			if ( !array_intersect( $areas, array_keys( $this->onContentSearchAreas( ) ) ) )
			{
				return array( );
			}
		}

		$sContent = $this->params->get( 'search_content', 1 );
		$sArchived = $this->params->get( 'search_archived', 1 );
		$limit = $this->params->def( 'search_limit', 50 );

		$text = trim( $text );
		if ( $text == '' )
		{
			return array( );
		}
		$section = JText::_( 'PLG_SEARCH_SEOGLOSSARY' );

		$params = JComponentHelper::getParams( 'com_seoglossary' );		
		$search_in_name = (int)$params->get( 'search_in_name', 1 );
		$search_in_definition = (int)$params->get( 'search_in_definition', 1 );
		$search_in_definition = (int)$params->get( 'search_in_definition', 1 );
		$search_in_synonym = (int)$params->get( 'search_in_synonym', 0 );
		$search_in_keywords = (int)$params->get( 'search_in_keywords', 0 );
		$search_in_meta = (int)$params->get( 'search_in_meta', 0 );
		$show_glossary_name  = (int)$params->get( 'show_glossary_name', 1 );
		
		$wheres = array( );
		switch ($phrase)
		{
			case 'exact' :
				$text = $db->Quote( $db->escape( $text, true ), false );
				$wheres2 = array( );
				if ($search_in_name)
					$wheres2[] = 'a.tterm LIKE ' . $text;
				if ($search_in_definition)
					$wheres2[] = 'a.tdefinition LIKE ' . $text;
				if ($search_in_synonym)
					$wheres2[] = 'a.tsynonyms LIKE ' . $text;
				if ($search_in_keywords)
					$wheres2[] = 'a.metakey  LIKE ' . $text;
				if ($search_in_meta)
					$wheres2[] = 'a.metadesc  LIKE ' . $text;
					
					
				if (count($wheres2) == 0)
					$wheres2[] = '0 = 1';
					
				$where = '(' . implode( ') OR (', $wheres2 ) . ')';
				break;
				
			case 'any' :
			case 'all' :
			default :
				$words = explode( ' ', trim($text) );
				$wheres = array( );
				foreach ( $words as $word )
				{
					if (empty($word))
						continue;
						
					$word = $db->Quote( '%' . $db->escape( $word, true ) . '%', false );
					$wheres2 = array( );
					if ($search_in_name)
						$wheres2[] = 'a.tterm LIKE ' . $word;
					if ($search_in_definition)
						$wheres2[] = 'a.tdefinition LIKE ' . $word . ' OR a.tmore LIKE ' . $word;
						if ($search_in_synonym)
					$wheres2[] = 'a.tsynonyms LIKE ' . $word;
					if ($search_in_keywords)
					$wheres2[] = 'a.metakey  LIKE ' . $word;
					if ($search_in_meta)
					$wheres2[] = 'a.metadesc  LIKE ' . $word;
				
						
					if (count($wheres2) == 0)
						$wheres2[] = '0 = 1';
						
					$wheres[] = implode( ' OR ', $wheres2 );
				}
				$where = '(' . implode( ($phrase == 'all' ? ') AND (' : ') OR ('), $wheres ) . ')';
				break;
		}

		switch ($ordering)
		{

			case 'newest' :
			default :
				$order = 'a.id DESC';
		}

			$return = array( );
			$lang = JFactory::getLanguage();
			$tag = JFactory::getLanguage()->getTag();
			$query = $db->getQuery( true );
			$nullDate = $db->getNullDate();
			$now = JFactory::getDate()->toSql();
			$query->select( 'a.id, a.tterm AS title, a.created,a.tdefinition AS text,a.tsynonyms, a.catid AS catid'. (($show_glossary_name == 1) ? ', g.name AS section' : '') );
			$query->from( '#__seoglossary AS a' );
			$query->join( 'LEFT', '#__seoglossaries AS g ON a.catid = g.id' );
			$query->where( 'a.state =1' );
			$query->where( 'g.state =1' );
			$query->where( $where );
			$query->where( 'g.language in (' . $db->quote($tag) . ',' . $db->quote('*') . ')');
			$query->where ("(a.publish_up = ".$db->Quote($nullDate)." OR a.publish_up <= ".$db->Quote($now).")" );
			$query->where ( "(a.publish_down = ".$db->Quote($nullDate)." OR a.publish_down >= ".$db->Quote($now).")");
			$query->order( $order );
			$db->setQuery( $query, 0, $limit );
			$rows = $db->loadObjectList( );

			if($rows)
			{
				foreach ( $rows as $key => $row )
				{
					$rows[$key]->href = SeoglossaryHelper::getUrlLink($row);
					$rows[$key]->created=$row->created;
					$rows[$key]->browsernav = '';
					$rows[$key]->tag='';
				}

			}

		
		return $rows;
	}

}
