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
class plgJcommentsjlike_jcomments extends JPlugin {

	function onAfterdisplayJcomment($context, $addata)
	{
		$app=JFactory::getApplication();
		if($app->getName()!='site'){
			return;
		}
		$html='';
		$app = JFactory::getApplication ();

		$show_comments=-1;
		$show_like_buttons=1;

		JRequest::setVar ( 'data', json_encode ( array ('cont_id' => $addata['id'], 'element' => $context, 'title' => $addata['title'], 'url' => $addata['url'], 'plg_name'=>'jlike_jcomments', 'show_comments'=>$show_comments, 'show_like_buttons'=>$show_like_buttons ) ) );
		require_once(JPATH_SITE.'/'.'components/com_jlike/helper.php');
		$jlikehelperObj=new comjlikeHelper();
		return $html = $jlikehelperObj->showlike();
   }

}
