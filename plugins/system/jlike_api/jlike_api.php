<?php
defined ( '_JEXEC' ) or die ( 'Restricted access' );
/**
 * @package		jLike
 * @author 		Techjoomla http://www.techjoomla.com
 * @copyright 	Copyright (C) 2011-2012 Techjoomla. All rights reserved.
 * @license 	GNU/GPL v2 http://www.gnu.org/licenses/gpl-2.0.html
 *
 */

// Import library dependencies
jimport ( 'joomla.plugin.plugin' );
require_once(JPATH_SITE.'/components/com_jlike/helper.php');
class plgSystemjlike_api extends JPlugin {


	function onAfterregisterlike($contentdata)
	{
		$app=JFactory::getApplication();
		if($app->getName()!='site'){
			return;
		}
			//INSERT COMMENT ENTRIES TO KOMENTO COMPONENTS TABLE
			if($contentdata->element=='komento.comment')
			{
				$this->registerlike_Komento($contentdata);
			}
			//INSERT COMMENT ENTRIES TO KOMENTO COMPONENTS TABLE		
	}	
	
	function onAfteraddlable($list_id)
	{
		$app=JFactory::getApplication();
		if($app->getName()!='site'){
			return;
		}
		return;			
	}	
	
	function registerlike_Komento($contentdata)
	{
		
		$db = JFactory::getDBO();
		
		if($contentdata->method=='like')	
		{
			$type='likes';	
			$actionsTable=new stdClass();
			$now			= JFactory::getDate()->toMySQL();
			$actionsTable->id='';
			$actionsTable->type			= $type;
			$actionsTable->comment_id	= $contentdata->element_id;
			$actionsTable->action_by	= $contentdata->userid;
			$actionsTable->actioned		= $now;			
			if (!$db->insertObject('#__komento_actions', $actionsTable, 'id'))
			return false;
		
			
		}
		else if($contentdata->method=='unlike')
		{
			$comment_id=$contentdata->element_id;
			$user_id=$contentdata->userid;
			$type='likes';	
			$where = array();
			$query  = 'DELETE FROM `#__komento_actions`';

			if($type !== 'all')
			{
				$where[] = '`type` = ' . $db->quote($type);
			}

			if($comment_id)
			{
			$where[] = '`comment_id` = ' . $db->quote($comment_id);
			}

			if($user_id !== 'all')
			{
			$where[] = '`action_by` = ' . $db->quote($user_id);
			}

			if(count($where))
			{
				$query .= ' WHERE ' . implode(' AND ', $where);
			}
			$db->setQuery($query);
			return $db->query();
		}

		
	}

}
