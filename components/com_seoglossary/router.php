<?php
/**    
 * SEO Glossary Route
 *
 * We developed this code with our hearts and passion.
 * We hope you found it useful, easy to understand and change.
 * Otherwise, please feel free to contact us at contact@joomunited.com
 *
 * @package 	SEO Glossary
 * @copyright 	Copyright (C) 2012 JoomUnited (http://www.joomunited.com). All rights reserved.
 * @license 	GNU General Public License version 2 or later; http://www.gnu.org/licenses/gpl-2.0.html
 */

defined('_JEXEC') or die;

/**
 * @param	array	A named array
 * @return	array
 */
if (version_compare(JVERSION, '4.0', 'ge'))
{
class SeoglossaryRouter extends JComponentRouterBase
{
public function build( &$query )
{
	$segments = array();

	require_once JPATH_SITE . '/components/com_seoglossary/helpers/seoglossary.php';
	$db = JFactory::getDbo();
	$params = JComponentHelper::getParams('com_seoglossary');
	$term = null;
	$cid=null;
	if (isset($query['layout']) && $query['layout'] == 'edit') {
	if (isset($query['id']))
		{
			$cid=$query['id'];
		}
	}
	// Is it a Term link ?
	if (isset($query['id']))
	{
		$id = (int)$query['id'];
		$sql = "SELECT * FROM #__seoglossary WHERE id = '".(int)$id."';";
		$db = JFactory::getDbo();
		$db->setQuery($sql);
		$term = $db->loadObject();
		
		if ($term === NULL || $term->state != 1)
			return array();
			
		unset($query['id']);
	}
	
	if ( $term !== NULL && !isset($query['catid']) ) 
		$query['catid'] = $term->catid;
	
	// Category Id
	if (isset($query['catid'])) {
		$catid = (int)$query['catid'];
		$lang="";
		if(JLanguageMultilang::isEnabled())
		{
			$lang='AND  language in (' . $db->quote(JFactory::getLanguage()->getTag()) . ',' . $db->quote('*') . ')';
		}
		$sql = "SELECT id FROM #__menu WHERE published = 1 AND (link = 'index.php?option=com_seoglossary&view=glossaries&catid={$catid}' OR link LIKE  'index.php?option=com_seoglossary&view=glossaries&catid={$catid}&%' OR link LIKE  'index.php?option=com_seoglossary&view=glossaries%&catid={$catid}' ) ".$lang." LIMIT 0,1;";
		$db->setQuery($sql);
		$id = $db->loadResult();
		if ($id === NULL) {
			$sql = "SELECT name FROM #__seoglossaries WHERE id = '{$catid}';";
			$db->setQuery($sql);
			$name = $db->loadResult();
			if ($name === null)
				return array();
		
			$category_slug = SeoglossaryHelper::slugify($name);
			$segments[] ="glossaries";
			$segments[] =  "{$catid}-{$category_slug}";
			if (isset($query['Itemid'])) 
				unset($query['Itemid']);
		} else {
			$query['Itemid'] = $id;
		}
		
		unset($query['view']);
		unset($query['catid']);
		unset($query['ordering']);
		unset($query['direction']);
		unset($query['theme']);
		unset($query['show_grid_image']);
	}
	
	// Front-end edit link
	if (isset($query['layout']) && $query['layout'] == 'edit') {
		unset($query['layout']);

		$newEntryText = $params->get('new_entry_url', 'add-new-entry');
		$segments[] = SeoglossaryHelper::slugify($newEntryText);
		if($cid)
		{
		$segments[] =$cid;	
		}
		return $segments;
	}
	
	// Letter
	if (isset($query['letter'])) {
		$segments[] = ''; // Hack/Workaround. Somehow needed when the letter is '0'.
		$segments[] = $query['letter'];
		
		unset($query['letter']);
		
		return $segments;
	}
	
	// Term
	if ($term !== NULL) {
		$term_slug = SeoglossaryHelper::slugify($term->tterm);
	if($term->alias)
	{
	 $segments[] = $term->alias;
	} else {
	 $segments[] = "{$term->id}-{$term_slug}";
	}
	
	}
	 

	return $segments;
}

/**
 * @param	array	A named array
 * @param	array
 *
 * Formats:
 *
 * index.php?/banners/task/id/Itemid
 *
 * index.php?/banners/id/Itemid
 */
public function parse( &$segments )
{
	
	
	$query = array();
	
	require_once JPATH_SITE . '/components/com_seoglossary/helpers/seoglossary.php';
	$cparams = JComponentHelper::getParams('com_seoglossary');
	$db = JFactory::getDbo();
	
	// Are we inside a menu?
	$app = JFactory::getApplication();
	$menu = $app->getMenu();
	$item = $menu->getActive();
	$catid = null;
	
	
	if ($item === NULL) {
		
		
		// We have to detect in which 
		// glossary we are
		if(count($segments)!=1)
	{
		$glossary = array_shift($segments);

		// Front-end edit link
	$newEntryText = $cparams->get('new_entry_url', 'add-new-entry');	
	$edit_slug = SeoglossaryHelper::slugify($newEntryText);
	
	if ($edit_slug == str_replace(':','-',mb_strtolower($glossary))) {
		$query['id']=@$segments[0];
		array_shift($segments);
		
		$query['view'] = 'glossary';
		$query['layout'] = 'edit';
		
		return $query;
	}
	else{	
		$catid = (int)$segments[0];
		$catslug=str_replace($catid."-","",$segments[0]);
		if ( $catid === NULL ) {
			
			
			JFactory::getApplication()->enqueueMessage(JText::_("Page Not Found"),'error');
			return $query;
		}
		$segment = reset($segments);
		
		$sql = "SELECT alias FROM #__seoglossaries WHERE id = '{$catid}';";
		$db->setQuery($sql);
		$name = $db->loadResult();
		if ($name === null || SeoglossaryHelper::slugify($name) != mb_strtolower($catslug)) {
			
			JFactory::getApplication()->enqueueMessage(JText::_("Page Not Found"),'error');
			return $query;
		}
		array_shift($segments);
		$query['view'] = 'glossaries';
		$query['catid'] = $catid;
	}
	}
	}else {
		
		$query['view'] = $item->query['view'];
		$query['catid'] = $item->query['catid'];
		
	}
	
	if (!count($segments))
		return $query;
	
	
	$segment = reset($segments);
	
	$segment = reset($segments);
	$newEntryText = $cparams->get('new_entry_url', 'add-new-entry');	
	$edit_slug = SeoglossaryHelper::slugify($newEntryText);
	
	if ($edit_slug == str_replace(':','-',mb_strtolower($segment))) {
		$query['id']=@$segments[0];
		array_shift($segments);
		
		$query['view'] = 'glossary';
		$query['layout'] = 'edit';
		
		return $query;
	}
	
	if (!count($segments))
		return $query;
	
	// Letter
	if (count($segments) == 1 && strpos($segment, ':') === FALSE) {
		$alias = str_replace(":", "-", $segments[0]);
		$alias = str_replace(" ", "-", $alias);
		$sql = "SELECT * FROM #__seoglossary WHERE alias = ".$db->Quote($alias)."  AND state=1;";
		$db->setQuery($sql);
		$gloss = $db->loadObject();
		if($gloss)
		{
			array_shift($segments);
			$query['view'] = 'glossary';
			$query['id'] = $gloss->id;
			
			return $query;
		}
		else
		{
		$letter = $segment;
	
		$catid = (int)$query['catid'];
		$sql = "SELECT * FROM #__seoglossaries WHERE id = '{$catid}' LIMIT 1;";;
		$db->setQuery($sql);
		$cat = $db->loadObject();
		
		if ($cat === null) {
			JFactory::getApplication()->enqueueMessage(JText::_("Page Not Found"),'error');
			return $query;
		}


		$alphabet = explode(',', str_replace('#', '(s)', $cat->alphabet));
		foreach($alphabet as &$l)
			$l = trim(mb_strtolower($l));
		
		if (!in_array(mb_strtolower($letter), $alphabet)) {
			JFactory::getApplication()->enqueueMessage(JText::_("Page Not Found"),'error');
			return $query;
		}
		
		$query['letter'] = (string)$letter;
		array_shift($segments);
		}
	}
	if (!count($segments))
		return $query;
	// Term
	$id = NULL;
	list( $id, $slug ) = explode ( ':', $segment );
	if ( $id === NULL  || !is_numeric($id) ) {
		$alias = str_replace(":", "-", $segments[0]);
		$alias = str_replace(" ", "-", $alias);
		$sql = "SELECT * FROM #__seoglossary WHERE alias = ".$db->Quote($alias)." AND state=1;";
		$db->setQuery($sql);
		$res= $db->loadObject();
		$tterm=$res->tterm;
		$id=$res->id;
	}
		else
		{
			$alias = str_replace(":", "-", $segments[0]);
			$alias = str_replace(" ", "-", $alias);
			$sql = "SELECT id,tterm FROM #__seoglossary WHERE alias = ".$db->Quote($alias);
			$db->setQuery($sql);
			$res= $db->loadObject();
		if(count($res)==0)
			{
			$sql = "SELECT tterm FROM #__seoglossary WHERE id = '".(int)$id."';";
			$db->setQuery($sql);
			$res= $db->loadObject();
			$tterm=$res->tterm;
			}
		else
			{
			$id=$res->id;
			$tterm=$res->tterm;
			}
		}
		if (($tterm === null)||($id==null)) {
		JFactory::getApplication()->enqueueMessage(JText::_("Page Not Found"),'error');
		return array();
		}
	$query['view'] = 'glossary';
	$query['id'] = $id;
	return $query;
}
}


/**
 * Legacy SEF class for B/C
 *
 * @param array $query
 * @return array
 */
function seoglossaryBuildRoute( &$query )
{
	$router = new SeoglossaryRouter();

	return $router->build( $query );
}

/**
 * Legacy SEF class for B/C
 *
 * @param array $segments
 * @return array
 */
function seoglossaryParseRoute( $segments )
{
	$router = new SeoglossaryRouter();

	return $router->parse( $segments );
}
}
else
{
	
/**
 * @param	array	A named array
 * @return	array
 */
function SeoglossaryBuildRoute(&$query)
{
	$segments = array();

	require_once JPATH_SITE . '/components/com_seoglossary/helpers/seoglossary.php';
	$db = JFactory::getDbo();
	$params = JComponentHelper::getParams('com_seoglossary');
	$term = null;
	$cid=null;
	if (isset($query['layout']) && $query['layout'] == 'edit') {
	if (isset($query['id']))
		{
			$cid=$query['id'];
		}
	}
	// Is it a Term link ?
	if (isset($query['id']))
	{
		$id = (int)$query['id'];
		$sql = "SELECT * FROM #__seoglossary WHERE id = '".(int)$id."';";
		$db = JFactory::getDbo();
		$db->setQuery($sql);
		$term = $db->loadObject();
		
		if ($term === NULL || $term->state != 1)
			return array();
			
		unset($query['id']);
	}
	
	if ( $term !== NULL && !isset($query['catid']) ) 
		$query['catid'] = $term->catid;
	
	// Category Id
	if (isset($query['catid'])) {
		$catid = (int)$query['catid'];
		$lang="";
		if(JLanguageMultilang::isEnabled())
		{
			$lang='AND  language in (' . $db->quote(JFactory::getLanguage()->getTag()) . ',' . $db->quote('*') . ')';
		}
		$sql = "SELECT id FROM #__menu WHERE published = 1 AND (link = 'index.php?option=com_seoglossary&view=glossaries&catid={$catid}' OR link LIKE  'index.php?option=com_seoglossary&view=glossaries&catid={$catid}&%' ) ".$lang." LIMIT 0,1;";
		$db->setQuery($sql);
		$id = $db->loadResult();
		if ($id === NULL) {
			$sql = "SELECT name FROM #__seoglossaries WHERE id = '{$catid}';";
			$db->setQuery($sql);
			$name = $db->loadResult();
			if ($name === null)
				return array();
		
			$category_slug = SeoglossaryHelper::slugify($name);
			$segments[] =  "{$catid}-{$category_slug}";
			if (isset($query['Itemid'])) 
				unset($query['Itemid']);
		} else {
			$query['Itemid'] = $id;
		}
		
		unset($query['view']);
		unset($query['catid']);
	}
	
	// Front-end edit link
	if (isset($query['layout']) && $query['layout'] == 'edit') {
		unset($query['layout']);

		$newEntryText = $params->get('new_entry_url', 'add-new-entry');
		$segments[] = SeoglossaryHelper::slugify($newEntryText);
		if($cid)
		{
		$segments[] =$cid;	
		}
		return $segments;
	}
	
	// Letter
	if (isset($query['letter'])) {
		$segments[] = ''; // Hack/Workaround. Somehow needed when the letter is '0'.
		$segments[] = $query['letter'];
		
		unset($query['letter']);
		
		return $segments;
	}
	
	// Term
	if ($term !== NULL) {
		$term_slug = SeoglossaryHelper::slugify($term->tterm);
	if($term->alias)
	{
	 $segments[] = $term->alias;
	} else {
	 $segments[] = "{$term->id}-{$term_slug}";
	}
	
	}
	 

	return $segments;
}

/**
 * @param	array	A named array
 * @param	array
 *
 * Formats:
 *
 * index.php?/banners/task/id/Itemid
 *
 * index.php?/banners/id/Itemid
 */
function SeoglossaryParseRoute($segments)
{
	
	$query = array();
	
	require_once JPATH_SITE . '/components/com_seoglossary/helpers/seoglossary.php';
	$params = JComponentHelper::getParams('com_seoglossary');
	$db = JFactory::getDbo();
	
	// Are we inside a menu?
	$app = JFactory::getApplication();
	$menu = $app->getMenu();
	$item = $menu->getActive();
	$catid = null;
	
	if ($item === NULL) {
		
		// We have to detect in which 
		// glossary we are
		$glossary = array_shift($segments);
		list( $catid, $catslug) = explode( ':', $glossary );
		$catid = (int)$catid;
		if ( $catid === NULL ) {
			
			JFactory::getApplication()->enqueueMessage(JText::_("Page Not Found"),'error');
			return $query;
		}
	
		$sql = "SELECT name FROM #__seoglossaries WHERE id = '{$catid}';";
		$db->setQuery($sql);
		$name = $db->loadResult();
		
		if ($name === null || SeoglossaryHelper::slugify($name) != mb_strtolower($catslug)) {
			
			JFactory::getApplication()->enqueueMessage(JText::_("Page Not Found"),'error');
			return $query;
		}
		
		$query['view'] = 'glossaries';
		$query['catid'] = $catid;
	} else {
		$query['view'] = $item->query['view'];
		$query['catid'] = $item->query['catid'];
	}

	if (!count($segments))
		return $query;
	
	
	$segment = reset($segments);
	
	// Front-end edit link
	$newEntryText = $params->get('new_entry_url', 'add-new-entry');	
	$edit_slug = SeoglossaryHelper::slugify($newEntryText);
	if ($edit_slug == str_replace(':','-',mb_strtolower($segment))) {
		$query['id']=@$segments[1];
		array_shift($segments);
		
		$query['view'] = 'glossary';
		$query['layout'] = 'edit';
		return $query;
	}
	
	if (!count($segments))
		return $query;
	
	// Letter
	if (count($segments) == 1 && strpos($segment, ':') === FALSE) {
		$alias = str_replace(":", "-", $segments[0]);
		$alias = str_replace(" ", "-", $alias);
		$sql = "SELECT * FROM #__seoglossary WHERE alias = ".$db->Quote($alias)."  AND state=1;";
		$db->setQuery($sql);
		$gloss = $db->loadObject();
		if($gloss)
		{
			$query['view'] = 'glossary';
			$query['id'] = $gloss->id;
			return $query;
		}
		else
		{
		$letter = $segment;
	
		$catid = (int)$query['catid'];
		$sql = "SELECT * FROM #__seoglossaries WHERE id = '{$catid}' LIMIT 1;";;
		$db->setQuery($sql);
		$cat = $db->loadObject();
		
		if ($cat === null) {
			JFactory::getApplication()->enqueueMessage(JText::_("Page Not Found"),'error');
			return $query;
		}


		$alphabet = explode(',', str_replace('#', '(s)', $cat->alphabet));
		foreach($alphabet as &$l)
			$l = trim(mb_strtolower($l));
		
		if (!in_array(mb_strtolower($letter), $alphabet)) {
			JFactory::getApplication()->enqueueMessage(JText::_("Page Not Found"),'error');
			return $query;
		}
		
		$query['letter'] = (string)$letter;
		array_shift($segments);
		}
	}
	if (!count($segments))
		return $query;
	// Term
	$id = NULL;
	list( $id, $slug ) = explode ( ':', $segment );
	if ( $id === NULL  || !is_numeric($id) ) {
		$alias = str_replace(":", "-", $segments[0]);
		$alias = str_replace(" ", "-", $alias);
		$sql = "SELECT * FROM #__seoglossary WHERE alias = ".$db->Quote($alias)." AND state=1;";
		$db->setQuery($sql);
		$res= $db->loadObject();
		$tterm=$res->tterm;
		$id=$res->id;
	}
		else
		{
			$alias = str_replace(":", "-", $segments[0]);
			$alias = str_replace(" ", "-", $alias);
			$sql = "SELECT id,tterm FROM #__seoglossary WHERE alias = ".$db->Quote($alias);
			$db->setQuery($sql);
			$res= $db->loadObject();
		if(count($res)==0)
			{
			$sql = "SELECT tterm FROM #__seoglossary WHERE id = '".(int)$id."';";
			$db->setQuery($sql);
			$res= $db->loadObject();
			$tterm=$res->tterm;
			}
		else
			{
			$id=$res->id;
			$tterm=$res->tterm;
			}
		}
		if (($tterm === null)||($id==null)) {
		JFactory::getApplication()->enqueueMessage(JText::_("Page Not Found"),'error');
		return array();
		}
	$query['view'] = 'glossary';
	$query['id'] = $id;
	return $query;
}
}