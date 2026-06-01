<?php
/**
 * @package		Komento
 * @copyright	Copyright (C) 2012 Stack Ideas Private Limited. All rights reserved.
 * @license		GNU/GPL, see LICENSE.php
 *
 * Komento is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * See COPYRIGHT.php for copyright notices and details.
 */

defined('_JEXEC') or die('Restricted access');

jimport('joomla.plugin.plugin');
jimport('joomla.filesystem.file');

/**
 * Komento System Plugin
 *
 */
class plgSystemJlike_sobipro extends JPlugin
{
	private $extension		= null;

	/**
	 * com_sobipro
	 */
	public function ContentDisplayEntryView( &$text )
	{
		$app=JFactory::getApplication();
		if($app->getName()!='site'){
			return;
		}
		$article		= new stdClass;
		$article->id	= JRequest::getInt( 'sid' );
		$article->title	= JRequest::getVar('sid','string');;
		$params			= new stdClass;

		$this->execute( __FUNCTION__, null, $article, $params, null );
	}

	private function execute( $eventTrigger, $context = '', &$article, &$params, $page = 0 )
	{
		$html='';
		$app = JFactory::getApplication ();
		if ($app->scope != 'com_sobipro') {
			return;
		}

		$route=JRoute::_(JURI::getInstance()->toString());
		$article->titlearr=explode(":",$article->title);

		$cont_id	=	$article->id;

		$element	=	'';
		$input=JFactory::getApplication()->input;
		$option=$input->get('option','','STRING');
		$view=$input->get('view','','STRING');
		$layout=$input->get('layout','','STRING');
		if($option)
			$element	.=	$option;
		if($view)
			$element	.=	'.'.$view;
		if($layout)
			$element	.=	'.'.$layout;

		$url	=	$route;

		$show_like_buttons = 1;
		//echo "<pre>";print_r(array ('cont_id' => $cont_id, 'element' => $element, 'title' => $article->titlearr[1], 'url' => $route  ));die;
		JRequest::setVar ( 'data', json_encode ( array ('cont_id' => $cont_id, 'element' => $element, 'title' =>''.$article->titlearr[1], 'url' => $route,'show_like_buttons'=>$show_like_buttons) ) );

		require_once(JPATH_SITE.'/'.'components/com_jlike/helper.php');
		$jlikehelperObj=new comjlikeHelper();
		$html = $jlikehelperObj->showlike();
		echo $html;
	}

	//TRUNCATE TABLE `like_jlike_likes` ;
	//TRUNCATE TABLE `like_jlike_content`;
	//TRUNCATE TABLE `like_jlike_like_lists`;
	//TRUNCATE TABLE `like_jlike_likes_lists_xref`;
}
