<?php

/**
 * @version     1.1
 * @package     mod_showcasebuilder
 * @copyright   Copyright (C) 2013. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      JoomlaForce Team <support@joomlaforce.com> - http://www.joomlaforce.com
 */


// no direct access

defined('_JEXEC') or die;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Access\Access;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
require_once JPATH_SITE.'/components/com_content/helpers/route.php';
jimport( 'joomla.application.categories' );
JModelLegacy::addIncludePath(JPATH_SITE.'/components/com_content/models', 'ContentModel');

JText::script('MOD_SHOWCASEBUILDER_JSTEXT');

class modShowcaseBuilderHelper {
	
public static function getList(&$params)
	{
		$app = Factory::getApplication();
		
		// Get an instance of the generic articles model
		$model = BaseDatabaseModel::getInstance('Articles', 'ContentModel', array('ignore_request' => true));
		// Set application parameters in model
		$appParams = Factory::getApplication()->getParams();
		$model->setState('params', $appParams);
		// Set the filters based on the module params
		
		// Category filter
		$model->setState('filter.category_id', $params->get('catid', array()));
		// Set ordering
		$orderby 			= $params->get('orderby', 'a.ordering');
		$ordering			= $params->get('ordering', 'DESC');
		// Set author
		$author 			= $params->get('author');
		//Check if select all
		if($author!=0)
		$model->setState('filter.author_id', $author);
		
		$model->setState('list.ordering', $orderby);
		$model->setState('list.direction', $ordering);
		
		// Excluded articles
		$excluded_articles = $params->get('excluded_articles', '');
		
		if ($excluded_articles) {
			$excluded_articles = explode("\r\n", $excluded_articles);
			$model->setState('filter.article_id', $excluded_articles);
			$model->setState('filter.article_id.include', false); // Exclude
		}
		

		$model->setState('list.start', 0);
		$model->setState('list.limit', (int) $params->get('limit', 1));
		$model->setState('filter.published', 1);
		$model->setState('list.select', 'a.fulltext, a.id, a.title, a.alias, a.introtext, a.state, a.catid, a.created, a.created_by, a.created_by_alias,' .
			' a.modified, a.modified_by, a.publish_up, a.publish_down, a.images, a.urls, a.attribs, a.metadata, a.metakey, a.metadesc, a.access,' .
			' a.hits, a.featured' );
		
		//Date Filtering
		$date_filtering = $params->get('date_filtering', '0');
		$date_filtering_option = $params->get('date_filtering_option', 'relative');
		
   		 if ($date_filtering !== '0') {
        $model->setState('filter.date_filtering', $date_filtering_option);
        $model->setState('filter.date_field', $params->get('date_field', 'a.created'));
        $model->setState('filter.start_date_range', $params->get('start_date_range', '1000-01-01 00:00:00'));
        $model->setState('filter.end_date_range', $params->get('end_date_range', '9999-12-31 23:59:59'));
        $model->setState('filter.relative_date', $params->get('relative_date', 30));
    }

			
		
		// Access filter
		$access = !ComponentHelper::getParams('com_content')->get('show_noauth');
		$authorised = Access::getAuthorisedViewLevels(Factory::getUser()->get('id'));
		$model->setState('filter.access', $access);
		
		// Filter by language
		$model->setState('filter.language', $app->getLanguageFilter());
		
		//  Featured switch
		switch ($params->get('show_featured'))
		{
			case '1':
				$model->setState('filter.featured', 'only');
				break;
			case '0':
				$model->setState('filter.featured', 'hide');
				break;
			default:
				$model->setState('filter.featured', 'show');
				break;
		}

		//	Retrieve Content
		
		$items = $model->getItems();

		foreach ($items as &$item)
		{
			$item->readmore = strlen(trim($item->fulltext));
			$item->slug = $item->id.':'.$item->alias;
			$item->catslug = $item->catid.':'.$item->category_alias;
			
			//Value article
			$item->image 			= JURI::base() . self::getImage($item->introtext,$item->images);
			$item->nameimage 		= self::getImage($item->introtext,$item->images);
			$item->title 			= htmlspecialchars($item->title);
			$item->category_title	= $item->category_title;
			//nuovi parametri
			$item->displayHits 		= $item->hits;
        	$item->displayAuthorName = $item->author;
			
			//nuovi parametri
			$item->introtext 		= HTMLHelper::_('content.prepare', $item->introtext);
			
			$nonsefurl 				= ContentHelperRoute::getArticleRoute($item->slug, $item->catslug);
			$nonsefurl				= preg_replace('/Itemid=(.+)/', 'Itemid=0', $nonsefurl );
			$item->link 			= Route::_($nonsefurl);

			if ($access || in_array($item->access, $authorised))
			{
				// We know that user has the privilege to view the article
				$item->link = Route::_(ContentHelperRoute::getArticleRoute($item->slug, $item->catid));
				$item->linkText = Text::_('MOD_ARTICLES_NEWS_READMORE');
			}
			else {
				$item->link = Route::_('index.php?option=com_users&view=login');
				$item->linkText = Text::_('MOD_ARTICLES_NEWS_READMORE_REGISTER');
			}

			$item->introtext = HTMLHelper::_('content.prepare', $item->introtext, '', 'mod_articles_news.content');

			//new DISABILITATA QUESTA FUNZIONE PERCHE ALTRIMENTI NON VISUALIZZAVA LE IMMAGINI DELL'ARTICOLO DENTRO LA SLIDE
			/*if (!$params->get('image'))
			{
				$item->introtext = preg_replace('/<img[^>]*>/', '', $item->introtext);
			}*/

			$results = $app->triggerEvent('onContentAfterDisplay', array('com_content.article', &$item, &$params, 1));
			$item->afterDisplayTitle = trim(implode("\n", $results));

			$results = $app->triggerEvent('onContentBeforeDisplay', array('com_content.article', &$item, &$params, 1));
			$item->beforeDisplayContent = trim(implode("\n", $results));
			
		}

		return $items;
	}	
	
	
	static function DoScripts($items,$list,$params,$jquery_var,$ModuleId){

		$document = Factory::getDocument();
		$uri = JURI::root();
			
		//Stylesheet
		$document->addStyleSheet( $uri.'modules/mod_showcasebuilder/assets/fonts/ostrich_sans/stylesheet.css' );
		$document->addStyleSheet( 'http://fonts.googleapis.com/css?family=Dosis:400,200,300,500,600,700,800' );
		$document->addStyleSheet( $uri.'modules/mod_showcasebuilder/assets/css/content_slider_style.css' );

		$document->addScript($uri.'modules/mod_showcasebuilder/assets/js/jquery.content_slider.js');
		$document->addScript($uri.'modules/mod_showcasebuilder/assets/js/jquery.mousewheel.js');
		$document->addScript($uri.'modules/mod_showcasebuilder/assets/js/jquery.prettyPhoto.js');
		$document->addScript($uri.'modules/mod_showcasebuilder/assets/js/jquery.animate-colors.js');
		$document->addScript($uri.'modules/mod_showcasebuilder/assets/js/additional_content.js');
		
        	
		$doscript = modShowcaseBuilderHelper::DoFunction($items,$list,$params,$jquery_var,$ModuleId);
		$document->addScriptDeclaration($doscript);

		return $doscript;

	}
	
	//Retrive image
	private static function getImage($text, $image_src="") {
		
		$image_src = json_decode($image_src);	
		//echo $image_src->image_intro."<br>";	
		if (JVERSION>=2.5 && @$image_src->image_intro) {
			return $image_src->image_intro;
		} else {
			preg_match('/< *img[^>]*src *= *["\']?([^"\']*)/i', $text, $matches);	
			if (isset($matches[1])) {
				return $matches[1];
			}			
		}
	}
		
	

	static function DoFunction($items,$list,$params,$jquery_var,$ModuleId)

	{
		
		$doc = Factory::getDocument();
		$modulespath = JURI::base()."modules/mod_showcasebuilder/assets/images/";
		$noimage = JURI::base()."modules/mod_showcasebuilder/assets/images/no-image-icon.jpg";
		
		//Parametri oggetti da visualizzare
		$show_title_article = $params->get('enable_titlearticle', '1');
		$show_author_article = $params->get('enable_authorarticle', '1');
		$show_category_article = $params->get('enable_categoryarticle', '1');
		$show_hits_article = $params->get('enable_hitsarticle', '1');
		$show_description_article = $params->get('enable_descriptionarticle', '1');
		$show_article_btn_more_info = $params->get('enable_articlebtnmorearticle', '1');
		
		//Start Params showcase
		$max_shown_items = $params->get('max_shown_items', '3');
		$hv_switch = $params->get('hv_switch', '0');
		$active_item = $params->get('active_item', '0');
		$wrapper_text_max_height = $params->get('wrapper_text_max_height', '100');
		$automatic_height_resize = $params->get('automatic_height_resize', '1');
		$middle_click = $params->get('middle_click', '1');
		$bind_arrow_keys = $params->get('bind_arrow_keys', '1');
		$small_border = $params->get('small_border', '4');
		$big_border = $params->get('big_border', '8');
		$border_radius = $params->get('border_radius', '-1');
		$radius_proportion = $params->get('radius_proportion', '1');
		$mode_picture = $params->get('mode_picture', '2');
		$border_on_off = $params->get('border_on_off', '1');
		$allow_shadow = $params->get('allow_shadow', '1');
		$small_resolution_max_height = $params->get('small_resolution_max_height', '0');
		$small_pic_width = $params->get('small_pic_width', '84');
		$small_pic_height = $params->get('small_pic_height', '84');
		$child_div_width = $params->get('child_div_width', '104');
		$child_div_height = $params->get('child_div_height', '104');
		$big_pic_width = $params->get('big_pic_width', '231');
		$big_pic_height = $params->get('big_pic_height', '231');
		$moving_speed = $params->get('moving_speed', '70');
		$moving_speed_offset = $params->get('moving_speed_offset', '100');
		$moving_easing = $params->get('moving_easing', 'linear');
		$arrow_speed = $params->get('arrow_speed', '300');
		$arrow_easing = $params->get('arrow_easing', 'linear');
		$hover_movement = $params->get('hover_movement', '6');
		$hover_speed = $params->get('hover_speed', '100');
		$hover_easing = $params->get('hover_easing', 'linear');
		$prettyPhoto_speed = $params->get('prettyPhoto_speed', '200');
		$prettyPhoto_easing = $params->get('prettyPhoto_easing', 'linear');
		$prettyPhoto_width = $params->get('prettyPhoto_width', '21');
		$prettyPhoto_start = $params->get('prettyPhoto_start', '0.93');
		$prettyPhoto_movement = $params->get('prettyPhoto_movement', '45'); 
		$prettyPhoto_color = $params->get('prettyPhoto_color', '#1AB99B');
		$prettyPhoto_img = $params->get('prettyPhoto_img', '');
		if ($prettyPhoto_img != NULL)
		$prettyPhoto_img = JURI::root().$prettyPhoto_img;
		
		$auto_play = $params->get('auto_play', '0');
		$auto_play_direction = $params->get('auto_play_direction', '1'); 
		$auto_play_pause_time = $params->get('auto_play_pause_time', '3000');
		$preload_all_images = $params->get('preload_all_images', '0');
		$enable_mousewheel = $params->get('enable_mousewheel', '1');
		$activate_border_div = $params->get('activate_border_div', '1');
		$border_color = $params->get('border_color', '#282828');
		$arrow_color = $params->get('arrow_color', '#282828');
		$arrow_width = $params->get('arrow_width', '28');
		$arrow_height = $params->get('arrow_height', '57');
		$small_arrow_width = $params->get('small_arrow_width', '20');
		$small_arrow_height = $params->get('small_arrow_height', '20');
		$use_thin_arrows = $params->get('use_thin_arrows', '0');
		$top_offset = $params->get('top_offset', '0');
		$left_offset = $params->get('left_offset', '0');
		$responsive_by_available_space = $params->get('responsive_by_available_space', '1');
		$keep_on_top_middle_circle = $params->get('keep_on_top_middle_circle', '0');
		$hide_arrows = $params->get('hide_arrows', '0');
		$hide_prettyPhoto = $params->get('hide_prettyPhoto', '0');
		$hide_content = $params->get('hide_content', '0');
		$content_margin_left = $params->get('content_margin_left', '0'); 
		$circle_left_offset = $params->get('circle_left_offset', '0');
		$minus_width = $params->get('minus_width', '0');
		$main_circle_position = $params->get('main_circle_position', '0');
		$enable_scrool_with_touchmove_on_horizontal_version = $params->get('enable_scrool_with_touchmove_on_horizontal_version', '1');
		$enable_scrool_with_touchmove_on_vertical_version = $params->get('enable_scrool_with_touchmove_on_vertical_version', '0');
		$movement_coefficient = $params->get('movement_coefficient', '1');
		//End Params
		
		$scroller ="(function($){ ";
		$scroller .=$jquery_var."(document).ready(function() {";
		$scroller .=" var image_array = new Array();";
		$scroller .= "image_array = [ ";
		foreach ($list as $item) :
			
			// Popolamento contenuto dentro popup
				$contentHTML = "";
			 if($show_title_article==1 || $show_title_article==2){
					$contentHTML .= "<h3>".$item->title."</h3>";
        		}
          	if ($show_author_article==1 || $show_author_article==2){
					$contentHTML .= " <b>".Text::_('MOD_SHOWCASEBUILDER_AUTHOR').": </b>". $item->displayAuthorName;
       			} 
          	if($show_category_article==1 || $show_category_article==2){
				$contentHTML .= " <b>".Text::_('MOD_SHOWCASEBUILDER_CATEGORY').": </b>".$item->category_title;
         		} 
          	if($show_hits_article==1 || $show_hits_article==2){ 
				$contentHTML .= " <b>".Text::_('MOD_SHOWCASEBUILDER_HITS').": </b>".$item->displayHits;
        		}
          	if($show_description_article==1 || $show_description_article==2){
				$contentHTML .= ' <b>'.Text::_('MOD_SHOWCASEBUILDER_DESCRIPTION').': </b>'.str_replace(array("\n","\r","<br/>","<br>","<br />"),"",$item->introtext);
         	}
			if($show_article_btn_more_info==1 || $show_article_btn_more_info==2){
				$contentHTML .= '<a href="'.$item->link.'" class="btn btn-primary">'.Text::_('MOD_SHOWCASEBUILDER_MOREINFO').'</a>';
         	}
			// Popolamento contenuto dentro popup
			
			if ($item->nameimage!=NULL)
				$scroller .= "	{image: '".$item->image."', link_url: '".$item->image."', link_rel: 'prettyPhoto',
								lower_text_label_show: 1, lower_text_label:  '".preg_replace('/[^A-Za-z0-9\. -]/', ' ', $item->title)."', title: '".preg_replace('/[^A-Za-z0-9\. -]/', '', $item->title)."', content: '".$contentHTML."'}, ";
			else
				$scroller .= "	{image: '".$noimage."', lower_text_label_show: 1, lower_text_label:  '".preg_replace('/[^A-Za-z0-9\. -]/', ' ', $item->title)."'}, ";
		endforeach;
		
		// TROVARE SISTEMA PRATICO PER RINOMINARE DINAMICAMENTE NOME SLIDER #SLIDER1
		$scroller .= "];";
		$scroller .=$jquery_var."('#slider".$ModuleId."').content_slider({
			map : image_array,											
			title: image_array,
			content: image_array,
			max_shown_items: ".$max_shown_items.",
			wrapper_text_max_height: ".$wrapper_text_max_height.",						
			hv_switch: ".$hv_switch.",									
			active_item: ".$active_item.",								
			automatic_height_resize: ".$automatic_height_resize.",		
			middle_click: ".$middle_click.",		
			bind_arrow_keys: ".$bind_arrow_keys.",
			small_border: ".$small_border.",
			big_border: ".$big_border.",
			border_radius:  ".$border_radius.",								
			radius_proportion: ".$radius_proportion.",
			mode: ".$mode_picture.",
			border_on_off: ".$border_on_off.",
			allow_shadow: ".$allow_shadow.",
			small_resolution_max_height: ".$small_resolution_max_height.",
			small_pic_width: ".$small_pic_width.",
			small_pic_height: ".$small_pic_height.",
			child_div_width: ".$child_div_width.",
			child_div_height: ".$child_div_height.",
			big_pic_width: ".$big_pic_width.",
			big_pic_height: ".$big_pic_height.",
			moving_speed: ".$moving_speed.",
			moving_speed_offset: ".$moving_speed_offset.",
			moving_easing: '".$moving_easing."',
			arrow_speed: ".$arrow_speed.",
			arrow_easing: '".$arrow_easing."',
			hover_movement: ".$hover_movement.",
			hover_speed: ".$hover_speed.",
			hover_easing: '".$hover_easing."',
			prettyPhoto_speed: ".$prettyPhoto_speed.",
			prettyPhoto_easing: '".$prettyPhoto_easing."',
			prettyPhoto_width: ".$prettyPhoto_width.",
			prettyPhoto_start: ".$prettyPhoto_start.",
			prettyPhoto_movement: ".$prettyPhoto_movement.",
			prettyPhoto_color: '".$prettyPhoto_color."',
			prettyPhoto_img: '".$prettyPhoto_img."', 							
			auto_play: ".$auto_play.",
			auto_play_direction: ".$auto_play_direction.",
			auto_play_pause_time: ".$auto_play_pause_time.",
			preload_all_images: ".$preload_all_images.",
			enable_mousewheel: ".$enable_mousewheel.",
			activate_border_div: ".$activate_border_div.",
			border_color: '".$border_color."',
			arrow_color: '".$arrow_color."',
			arrow_width: ".$arrow_width.",
			arrow_height: ".$arrow_height.",
			small_arrow_width: ".$small_arrow_width.",
			small_arrow_heigth: ".$small_arrow_height.",
			use_thin_arrows: ".$use_thin_arrows.",
			top_offset: ".$top_offset.",
			left_offset: ".$left_offset.",
			responsive_by_available_space: ".$responsive_by_available_space.",
			keep_on_top_middle_circle: ".$keep_on_top_middle_circle.",
			hide_arrows: ".$hide_arrows.",
			hide_prettyPhoto: ".$hide_prettyPhoto.", 							
			hide_content: ".$hide_content.",
			content_margin_left: ".$content_margin_left.",
			circle_left_offset: ".$circle_left_offset.",
			minus_width: ".$minus_width.",
			main_circle_position: ".$main_circle_position.", 						
			enable_scrool_with_touchmove_on_horizontal_version: ".$enable_scrool_with_touchmove_on_horizontal_version.",
			enable_scrool_with_touchmove_on_vertical_version: ".$enable_scrool_with_touchmove_on_vertical_version.",
			movement_coefficient: ".$movement_coefficient."
		});
	});
})(".$jquery_var.");";
		
		return $scroller;

	}

}