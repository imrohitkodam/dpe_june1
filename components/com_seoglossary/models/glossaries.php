<?php
/**
 * SEO Glossary Glossaries Model
 *
 * We developed this code with our hearts and passion.
 * We hope you found it useful, easy to understand and change.
 * Otherwise, please feel free to contact us at contact@joomunited.com
 *
 * @package 	SEO Glossary
 * @copyright 	Copyright (C) 2012 JoomUnited (http://www.joomunited.com). All rights reserved.
 * @license 	GNU General Public License version 2 or later; http://www.gnu.org/licenses/gpl-2.0.html
 */

defined( '_JEXEC' ) or die ;

jimport( 'joomla.application.component.modellist' );
use Joomla\Utilities\ArrayHelper;


/**
 * Methods supporting a list of Seoglossary records.
 */
class SeoglossaryModelGlossaries extends JModelList
{

	/**
	 * Constructor.
	 *
	 * @param    array    An optional associative array of configuration settings.
	 * @see        JController
	 * @since    1.6
	 */
	public function __construct( $config = array() )
	{
		if ( empty( $config['filter_fields'] ) )
		{
			$config['filter_fields'] = array(
				'id',
				'a.id',
				'ordering',
				'a.ordering',
				'state',
				'a.state',
				'tterm',
				'a.tterm',
                'tsynonyms',
                'a.tsynonyms',
				'tfirst',
				'a.tfirst',
				'tdefinition',
				'a.tdefinition',
				'tmore',
				'a.tmore',
				'tname',
				'a.tname',
				'tmail',
				'a.tmail',
				'tcomment',
				'a.tcomment',
				'tedit',
				'a.tedit',
				'catid',
				'a.catid',
			);
		}

		parent::__construct( $config );
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 */
	protected function populateState( $ordering = null, $direction = null )
	{
		// Initialise variables.
		$app = JFactory::getApplication();
		$theme="";
		// Load the filter state.
		$menu = $app->getMenu();
		$menuItem = $menu->getActive();
		if($menuItem)
		{
				$theme=@$menuItem->query['theme'];
				
		}
		
		$published = $app->getUserStateFromRequest( $this->context . '.filter.state', 'filter_published', '', 'string' );
		$this->setState( 'filter.state', $published );
                

		// Load the parameters.
		$params = JComponentHelper::getParams( 'com_seoglossary' );
		$this->setState( 'params', $params );
		if(	$theme=="alpha")
		{
			$limitbox =0;
			$value = 0;
			$ordering="tterm";
			$direction ="asc";
		}
		else
		{
		$limitbox = $params->get( 'entry_perpage', 15 );
		$value = $app->input->get('limitstart', 0, 'uint');
		$ordering=$app->getUserStateFromRequest($this->context . '.filter.ordering', 'ordering', 'tterm', 'string');
		$direction =$app->getUserStateFromRequest($this->context . '.filter.direction', 'direction', 'asc', 'string');
		
		}
		
		if (!in_array(strtoupper($direction), array('ASC', 'DESC', '')))
		{
			$direction = 'ASC';
		}
		$limit = $app->getUserStateFromRequest( $this->context . '.list.limit', 'limit' ,$limitbox);
		$this->setState('list.limit', $limit);
		$value = $app->input->get('limitstart', 0, 'uint');
		$this->setState('list.start', $value);
		$filterSearch =$app->getUserStateFromRequest($this->context . '.filter.search', 'filter_search', '', 'string');
		$this->setState('list.filter', $filterSearch);
		$glossarysearchmethod = $app->getUserStateFromRequest($this->context . '.filter.glossarysearchmethod', 'glossarysearchmethod', '', 'string');
		$this->setState('list.ordering', $ordering);
		
		$this->setState('list.direction', $direction);
		
		$this->setState('list.glossarysearchmethod', $glossarysearchmethod);
		
		$this->setState('filter.language', JLanguageMultilang::isEnabled());
		
		// List state information.
		//parent::populateState( $ordering,$direction  );
	}

	/**
	 * Method to get a store id based on model configuration state.
	 *
	 * This is necessary because the model is used by the component and
	 * different modules that might need different sets of data or different
	 * ordering requirements.
	 *
	 * @param	string		$id	A prefix for the store id.
	 * @return	string		A store id.
	 * @since	1.6
	 */
	protected function getStoreId( $id = '' )
	{
		// Compile the store id.
		$id .= ':' . $this->getState( 'filter.search' );
		$id .= ':' . $this->getState( 'filter.state' );

		return parent::getStoreId( $id );
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return	JDatabaseQuery
	 * @since	1.6
	 */
	protected function getListQuery( )
	{
		//catid
		$catid = JFactory::getApplication()->input->getInt('catid', null);
		$app = JFactory::getApplication();
		// Create a new query object.
		$db = $this->getDbo();
		$query = $db->getQuery( true );

		// Select the required fields from the table.
		$query->select( $this->getState( 'list.select', 'a.*' ) );
		$query->from( '#__seoglossary AS a' );

		// Join over the users for the checked out user.
		$query->select( 'uc.name AS editor' );
		$query->join( 'LEFT', '#__users AS uc ON uc.id=a.checked_out' );
		$query->join( 'LEFT', '#__seoglossaries AS g ON g.id = a.catid' );
		$query->where( 'g.state = 1' );
        $params = JComponentHelper::getParams( 'com_seoglossary' );
		// Filter by published state
		$published = $this->getState( 'filter.state' );
		if ( is_numeric( $published ) )
		{
			$query->where( 'a.state = ' . (int)$published );
		}
		else
		if ( $published === '' )
		{
			$query->where( '(a.state IN (1))' );
		}

		// Filter by search in title
		$cid = $this->getState( 'filter.catid' );
		
		if ( empty( $cid ) )
		{
			$cid = JFactory::getApplication()->input->getInt('catid', null);
		}
				
		if ( !empty( $cid ) )
		{
			if(is_array($cid))
                        {
                            ArrayHelper::toInteger($cid);
                            $cid = @implode(',', $cid);
                           
                            $query->where( '(a.catid  IN (' . $cid . '))' );
                        }
                        else
                        {
			$query->where( '(a.catid=' . (int)$cid . ')' );
                        }
		}
		$letter = JFactory::getApplication()->input->getString( 'letter' );
		if ( !empty( $letter ) || @$letter == '0' )
		{
			$letter = $db->quote($db->escape($letter, true) . '%', false);
				if ($letter == '(s)')
				{
					$letter = $db->quote('#');
				}
				$wheres3 = array();
				$where4="";
				$wheres3[] = 'BINARY  UPPER(a.tterm) LIKE  UPPER('. $letter.')';
				$where4 = '(' . implode(') OR (', $wheres3) . ')';
				$query->where( '('.$where4.')' );
				$this->state->set('list.filter','');
				$this->state->set('list.glossarysearchmethod','');
		}
		$search =urldecode($this->state->get('list.filter'));
		$glossarysearchmethod = $this->state->get('list.glossarysearchmethod');
		if ( $search )
		{
			if ( stripos( $search, 'id:' ) === 0 )
			{
				$query->where( 'a.id = ' . (int) substr( $search, 3 ) );
                                
			}
			else
			{   
                $wheres = array( );
                $search_in_name = (int)$params->get( 'search_in_name', 1 );
                $search_in_definition = (int)$params->get( 'search_in_definition', 1 );
				$search_in_synonym = (int)$params->get( 'search_in_synonym', 0 );
				
				if ( $glossarysearchmethod  == 1 )
				{
					$search = $db->Quote( $db->escape( $search, true ) . '%' );
                                        $wheres2 = array( );
				if ($search_in_name)
					$wheres2[] = 'a.tterm LIKE ' . $search;
				if ($search_in_definition)
					$wheres2[] = 'a.tdefinition LIKE ' . $search;
					if ($search_in_synonym)
					$wheres2[] = 'a.tsynonyms LIKE ' . $search;
					
					
				if (count($wheres2) == 0)
					$wheres2[] = '0 = 1';
                                       
				}
				else
				if ( $glossarysearchmethod  == 2 )
				{
					$search = $db->Quote( '%' . $db->escape( $search, true ) . '%' );
					$wheres2 = array( );
				if ($search_in_name)
					$wheres2[] = 'a.tterm LIKE ' . $search;
				if ($search_in_definition)
					$wheres2[] = 'a.tdefinition LIKE ' . $search;
					if ($search_in_synonym)
					$wheres2[] = 'a.tsynonyms LIKE ' . $search;
					
				if (count($wheres2) == 0)
					$wheres2[] = '0 = 1';
                                       
				}
				else
				if ( $glossarysearchmethod  == 3 )
				{
					$search = $db->Quote( $db->escape( $search, true ) );
                                         $wheres2 = array( );
				if ($search_in_name)
					$wheres2[] = 'a.tterm LIKE ' . $search;
				if ($search_in_definition)
					$wheres2[] = 'a.tdefinition LIKE ' . $search;
					if ($search_in_synonym)
					$wheres2[] = 'a.tsynonyms LIKE ' . $search;
					
				if (count($wheres2) == 0)
					$wheres2[] = '0 = 1';
				}
				else
				if ( $glossarysearchmethod  == 4 )
				{
					$search = $db->Quote( $db->escape( $search, true ) );
                                        if(($params->get('search_in_name',1))==1)
                                        {
                                            $query->where( ' SOUNDEX(a.tterm) = SOUNDEX(' . $search . ' ) ' );                                 }
                                       
				}
				
                                if(@$wheres2 )
                                {
                                    $where = '((' . implode( ') OR (', $wheres2 ) . '))';
                                    $query->where($where);
                                }
								

			}
		}
		$nullDate = $db->getNullDate();
		$now = JFactory::getDate()->toSql();
		//Language selection
		if ($this->getState('filter.language'))
		{
			$query->where('g.language in (' . $db->quote(JFactory::getLanguage()->getTag()) . ',' . $db->quote('*') . ')');
		}
		
		$query->where ("(a.publish_up = ".$db->Quote($nullDate)." OR a.publish_up <= ".$db->Quote($now).")" );
		$query->where ( "(a.publish_down = ".$db->Quote($nullDate)." OR a.publish_down >= ".$db->Quote($now).")");
			
		// Add the list ordering clause.
		$orderCol = $this->state->get( 'list.ordering' );
		$orderDirn = $this->state->get( 'list.direction' );
		
		if($orderCol=="rand()")
		{
			$query->order( $db->escape( 'rand()' ) );
			
		}
		else if ( $orderCol && $orderDirn )
		{
			
			if(in_array($orderCol,array('id','order','tterm')))
			{
			$query->order( $db->escape( 'a.'.$orderCol . ' ' . $orderDirn ) );
			}
			else
			{
				$query->order( 'a.tterm ASC' );
			}
		}
		
		return $query;
	}

	public function getGlossaryData( )
	{
		//Be sure we are in the com_seoglossary component, because other components have a catid parameter
		$catid = (JFactory::getApplication()->input->getInt('option', '') == 'com_seoglossary') ? JFactory::getApplication()->input->getInt('catid', null) : null;
		$lang = JFactory::getLanguage();
		$db = JFactory::getDBO( );
		$query = "SELECT s.id,s.tterm,s.tsynonyms,s.tdefinition,s.catid FROM #__seoglossary s LEFT JOIN #__seoglossaries g ON s.catid = g.id WHERE " . ( ($catid !== null) ? 'g.id = '. $catid .' AND ' : '') . "s.id > 0 AND s.state = 1 AND g.state = 1 AND ( g.language in (" . $db->quote(JFactory::getLanguage()->getTag()) . "," . $db->quote('*') . ") );";
		$db->setQuery( $query );
		$rows = $db->loadObjectList( );
		return $rows;

	}

	public function getGlossaryCategory( $id )
	{

		$db = JFactory::getDBO( );
		$query = "SELECT name, description, state FROM #__seoglossaries WHERE id=" . (int)$id;
		$db->setQuery( $query );

		$row = $db->loadObject( );

		return $row;

	}
}
