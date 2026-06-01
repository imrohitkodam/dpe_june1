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
class plgContentJLike_virtuemart extends JPlugin {


	function onContentAfterTitle( $context, &$article, &$params, $limitstart )
	{
		$app=JFactory::getApplication();
		if($app->getName()!='site'){
			return;
		}

		$html='';
		$app = JFactory::getApplication ();

		if ($app->scope != 'com_virtuemart') {
			return;
		}

		$uri= JFactory::getURI();
		$route=JURI::getInstance()->toString();
		$input=JFactory::getApplication()->input;

		$view=$input->get('view','','STRING');

		$element	=	'';
		$element='com_virtuemart.productdetails';
			if($view!='category')
			{
				$virtuemart_product_id=$input->get('virtuemart_product_id','','INT');
				$virtuemart_category_id=$input->get('virtuemart_category_id','','INT');
			}
			else if($view=='category')
			{
				if(empty($article->virtuemart_product_id))
				return;
				$route=$article->link;
				$virtuemart_product_id=$article->virtuemart_product_id;

			}
			else
			return;
			$cont_id	=	$virtuemart_product_id;
			$show_like_buttons = 1;
			JRequest::setVar ( 'data', json_encode ( array ('cont_id' => $cont_id, 'element' => $element, 'title' => $article->slug, 'url' => $route,'show_like_buttons'=>$show_like_buttons ) ) );

		require_once(JPATH_SITE.'/'.'components/com_jlike/helper.php');
		$jlikehelperObj=new comjlikeHelper();
		$html = $jlikehelperObj->showlike();

			return $html;

   }


}
