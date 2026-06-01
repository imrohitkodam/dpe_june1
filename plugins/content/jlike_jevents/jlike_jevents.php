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
$lang->load('plg_jlike_jevents', JPATH_ADMINISTRATOR);

class plgContentjlike_jevents extends JPlugin {

	function onJEventsHeader($obj)
	{
		$app=JFactory::getApplication();
		if($app->getName()!='site'){
			return;
		}

		$html='';
		$app = JFactory::getApplication ();

		if ($app->scope != 'com_jevents') {
			return;
		}

		$uri= JFactory::getURI();
		//$route='index.php?'.$uri->getQuery();
		$route=JURI::getInstance()->toString();
		$input=JFactory::getApplication()->input;
		$cont_id=$input->get('evid','','INT');
		$task=$input->get('task','','STRING');
		$option=$input->get('option','','STRING');
		$view=$input->get('view','','STRING');

		//Not to show anything related to commenting
		$show_comments=-1;
		$show_like_buttons =1 ;

		if($task=='icalevent.detail' or $task=='icalrepeat.detail' )
		{
			$element	=	'';

			$element	=	$option.'.icalevent.detail';

			JRequest::setVar ( 'data', json_encode ( array ('cont_id' => $cont_id, 'element' => $element, 'title' =>'Event', 'url' => $route,'plg_name'=>'jlike_jevents','show_comments'=>$show_comments,'show_like_buttons'=>$show_like_buttons ) ) );

			require_once(JPATH_SITE.'/'.'components/com_jlike/helper.php');
			$jlikehelperObj=new comjlikeHelper();
			$html = $jlikehelperObj->showlike();
			echo $html;
		}
	}

	function onJEventsFooter()
	{

		$app=JFactory::getApplication();
		if($app->getName()!='site'){
			return;
		}

		$html='';
		$app = JFactory::getApplication ();

		if ($app->scope != 'com_jevents') {
			return;
		}

		$uri= JFactory::getURI();
		//$route='index.php?'.$uri->getQuery();
		$route=JURI::getInstance()->toString();
		$input=JFactory::getApplication()->input;
		$cont_id=$input->get('evid','','INT');
		$task=$input->get('task','','STRING');
		$option=$input->get('option','','STRING');
		$view=$input->get('view','','STRING');
		$show_like_buttons =0;

		//Not to show anything related to commenting
		$show_comments=-1;
		$jlike_comments = $this->params->get('jlike_comments');

		if($jlike_comments)
		{
			//show comment count
			$show_comments=0;

			if($task=='icalevent.detail' or $task=='icalrepeat.detail' )
			{
				//show comments
				$show_comments=1;
			}
		}

		if($task=='icalevent.detail' or $task=='icalrepeat.detail' )
		{
			$element	=	'';

			$element	=	$option.'.icalevent.detail';

			JRequest::setVar ( 'data', json_encode ( array ('cont_id' => $cont_id, 'element' => $element, 'title' =>'Event', 'url' => $route,'plg_name'=>'jlike_jevents','show_comments'=>$show_comments,'show_like_buttons'=>$show_like_buttons ) ) );

			require_once(JPATH_SITE.'/'.'components/com_jlike/helper.php');
			$jlikehelperObj=new comjlikeHelper();
			$html = $jlikehelperObj->showlike();
			echo $html;
		}
   }

}
