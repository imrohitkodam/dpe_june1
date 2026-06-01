<?php
/**
 * @package     corejoomla.site
 * @subpackage  plg_content_polls
 *
 * @copyright   Copyright (C) 2009 - 2014 corejoomla.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

class plgContentPolls extends JPlugin
{
	public function onContentPrepare($context, &$article, &$params, $page = 0)
	{
		//Don't run this plugin when the content is being indexed
		if ($context == 'com_finder.indexer' || JFactory::getApplication()->isAdmin()) 
		{
			return true;
		}
		
		// simple performance check to determine whether bot should process further
		if (strpos($article->text, 'CONTENTPOLL') === false && strpos($article->text, 'loadmodule') === false) 
		{
			return true;
		}
		
		$document = JFactory::getDocument();
		$cjlib = JPATH_ROOT.'/components/com_cjlib/framework.php';
		
		if(file_exists($cjlib))
		{
			require_once $cjlib;
		}
		else
		{
			return;
		}
		
		CJLib::import('corejoomla.framework.core');
		CJFunctions::load_jquery(array('libs'=>array()));
		
		$document->addScript(JURI::root(true).'/media/com_communitypolls/anywhere/anywhere.js');
		$document->addScript('https://www.google.com/jsapi');
		$document->addScriptDeclaration('google.load("visualization", "1", {packages:["corechart"]});');
		
		$article->text = preg_replace_callback('/\{CONTENTPOLL(.*?)\}/', 'replace_poll_tags', $article->text);
	}
}

if(!function_exists('replace_poll_tags'))
{
	function replace_poll_tags($matches){
	
		$params = json_decode(str_replace(']', '}', str_replace('[', '{', trim($matches[1]))));
	
		if(empty($params->container)){
	
			$params->container = 'content-poll-'.rand(1000, 9999);
		}
	
		if(empty($params->template)){
				
			$params->template = 'default';
		}
	
		$params->noscripts = true;
		$params->anywhere = false;
	
		$document = JFactory::getDocument();
		$document->addStyleSheet(JURI::root(true).'/media/com_communitypolls/anywhere/templates/'.$params->template.'/style.css');
		$document->addScriptDeclaration('jQuery(document).ready(function(cj$){PollsAnywhere('.json_encode($params).')});');
	
		return '<div class="'.$params->container.'"><input type="hidden" /></div>';
	}
}