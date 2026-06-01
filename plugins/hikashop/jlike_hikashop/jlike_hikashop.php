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

//Load language file
$lang =  JFactory::getLanguage();
$lang->load('plg_jlike_hikashop', JPATH_ADMINISTRATOR);

class plgHikashopjlike_hikashop extends JPlugin {


	function onHikashopAfterDisplayView($data)
	{

		$app=JFactory::getApplication();
		if($app->getName()!='site'){
			return;
		}
		$html='';

		if ($app->scope != 'com_hikashop') {
			return;
		}

		$uri= JFactory::getURI();
		$route='index.php?'.$uri->getQuery();
		//$route=JURI::getInstance()->toString();
		$input=JFactory::getApplication()->input;
		$cont_id=$input->get('cid','','INT');
		$name=$input->get('name','','STRING');
		$task=$input->get('task','','STRING');
		$option=$input->get('option','','STRING');
		$view=$input->get('view','','STRING');
		$layout=$input->get('layout','','STRING');
		$element=$option.'.'.$view;

		// show like button
		$show_like_buttons = 1;

		//Not to show anything related to commenting
		$show_comments=-1;
		$jlike_comments = $this->params->get('jlike_comments');

		if($jlike_comments)
		{
			//show comment count
			$show_comments=1;
		}

		JRequest::setVar ( 'data', json_encode ( array ('cont_id' => $cont_id, 'element' => $element, 'title' => $name, 'url' => $route,'plg_name'=>'jlike_hikashop','show_comments'=>$show_comments, 'show_like_buttons'=>$show_like_buttons ) ) );
		require_once(JPATH_SITE.'/'.'components/com_jlike/helper.php');
		$jlikehelperObj=new comjlikeHelper();
		$html = $jlikehelperObj->showlike();
		echo $html;
   }

}
