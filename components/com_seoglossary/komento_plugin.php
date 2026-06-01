<?php
/**
 * @version    3.2.5
 * @package     com_mymapglossarys
 * @copyright   JoomUnited (C) 2011. All rights reserved.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * ****@author      joomunited - contact@joomunited.com
 */
// no direct access
defined('_JEXEC') or die;

// Always load abstract class
require_once( JPATH_ROOT . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_komento' . DIRECTORY_SEPARATOR . 'komento_plugins' . DIRECTORY_SEPARATOR .'abstract.php' );

class KomentoComSeoglossary extends KomentoExtension
{
    public $component = 'com_seoglossary';
    public $_item;
    public $_staticid;

    public $_map = array(
        'id'            => 'id',
        'title'         => 'name',
        'hits'          => 0,
        'created_by'    => 'created_by',
        'catid'         => 'catid',
        'state'         => 'state',
        );

    public function __construct( $component )
    {
        
        parent::__construct( $component );
    }
    public function load( $cid )
    {
        static $instances = array();

        if( !isset( $instances[$cid] ) )
        {
           $db        = Komento::getDBO();
            $query    = 'SELECT * from #__seoglossary where id='. $db->quote( (int) $cid );
            $db->setQuery( $query );

            if( !$result = $db->loadObject() )
            {
                return $this->onLoadArticleError( $cid );
            }
	    $this->_staticid=$cid ;

            $instances[$cid] = $result;
        }
	
        $this->_item = $instances[$cid];

        return $this;
    }

    public function getContentIds( $categories = '' )
    {
        $articleIds = array();
        $db=JFactory::getDbo();
        $query = 'SELECT id FROM ' . $db->quoteName( '#__seoglossary' ) . ' ORDER BY id';
        $db->setQuery( $query );
        return $db->loadResultArray();	
    }

    public function getCategories()
	{
        $categories=arrray();
		return $categories;
	}

    // to determine if is listing view
    public function isListingView()
    {
        return JFactory::getApplication()->input->getString('view') == 'glossary';
    }

    // to determine if is entry view
    public function isEntryView()
    {
        return JFactory::getApplication()->input->getString('view') == 'glossary';
    }

    public function onExecute( &$article, $html, $view, $options = array() )
    {
	return $html;
		
    }
         public function getComponentIcon()
	{
		return JUri::root(TRUE) . '/images/icons/icon-blue-love.png';
	}
        public function getComponentName()
	{
        JFactory::getLanguage()->load('com_seoglossary', JPATH_ADMINISTRATOR);
		return JText::_( 'COM_SEOGLOSSARY' );
	}
	public function getContentPermalink( $append = '', $external = false )
	{
	
	    $link = 'index.php?option=com_seoglossary&view=glossary&id='.$this->_staticid;
		$link	= JRoute::_( $link );
		return $link;
	}
}
