<?php
	/**
	 * Content SEO Glossary plugin file
	 *
	 * We developed this code with our hearts and passion.
	 * We hope you found it useful, easy to understand and change.
	 * Otherwise, please feel free to contact us at contact@joomunited.com
	 *
	 * @package 	SEO Glossary
	 * @copyright 	Copyright (C) 2012 JoomUnited (http://www.joomunited.com). All rights reserved.
	 * @license 	GNU General Public License version 2 or later; http://www.gnu.org/licenses/gpl-2.0.html
	 */
	
	// No direct access.
	defined('_JEXEC') or die;
	use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;

	jimport('joomla.plugin.plugin');
	
	/**
	 * SEO Glossary plugin.
	 *
	 */
	 require_once(JPATH_SITE . '/components/com_seoglossary/helpers/compat.php');
	class plgContentSeoglossary extends CMSPlugin
	{
		/**
		 * Constructor
		 *
		 * @access	  protected
		 * @param	   object  $subject The object to observe
		 * @param	   array   $config  An array that holds the plugin configuration
		 * @since	   1.5
		 */
		public function __construct(& $subject, $config)
		{
			if( !is_dir( JPATH_SITE . '/components/com_seoglossary/models' ) )
			{
				return true;
			}
			parent::__construct($subject, $config);
	
		}
		
		public function onBeforeDisplay(&$article, &$params, $limitstart)
		{
	
		}
		
			
		/**
		 * @since	1.6
		 */
		public function onContentPrepare($context, &$content, &$params, $limitstart) 
		{
		if (Factory::getApplication()->input->getInt('diagnose',0) == 1) {
		    echo "<b>SEOGlossary : Triggering content plugin....</b><br />";
		}
			
			$doc = Factory::getdocument();
			if($doc->getType() != 'html') return true;
			// we don't run in modal pages or other incomplete pages
			//$nogo = array('component','raw');
			//if(in_array(Factory::getApplication()->input->getString('tmpl'),$nogo)) return true;
			$regex = "#{seog_disable*(.*?)}(.*?){/seog_disable}#s";
			$regex_enable = "#{seog_enable*(.*?)}(.*?){/seog_enable}#s";	
			$app = Factory::getApplication();
			$component=array();
			$com_params = ComponentHelper::getparams( 'com_seoglossary' );
			$disable_component=$com_params->get('disable_component');
			$event=$com_params->get('event',1);
			$component=explode('.',$context);
			$disable_context=array();
			$disable_contextarr=$com_params->get('disable_context',null);
			if($disable_contextarr)
			{
			$disable_context=explode(',',$disable_contextarr);
			}
			$found=true;
			$disable_menu=$com_params->get('disable_menu');
			$regexfound=false;
			$fmenu = $app->getMenu()->getActive();
			if ($app->isClient('administrator'))
			{
				return;
			}
			if (preg_match_all($regex_enable, $content->text, $matches, PREG_PATTERN_ORDER) > 0) {
				$regexfound=true;
			}
			
			
			 if($disable_component)
				{
				if (in_array($component[0], $disable_component)) {
				$found=false;
					}
				}
				if($disable_context)
				{
				if (in_array($context, $disable_context)) {
							
						$found=false;
					}
				}
			if($component[0]=="com_content")
			{
				$disable_category=$com_params->get('disable_category');
				if($disable_category)
				{
				if (in_array(@$content->catid, $disable_category)) {
					$found=false;
				}
				}
			}
			
			if($disable_menu)
			{
				if($fmenu)
				{
				if (in_array($fmenu->id, $disable_menu)) {
						$found=false;
				}
				}
				
			}
			if ( !is_dir( JPATH_SITE . '/components/com_seoglossary/models' ) )
			{
				return true;
			}
	
			if ( $com_params->get( 'show_glossaryplugin', 1 ) == 0 )
			{
				return true;
			}
			if($regexfound)
			{
				
			}
			else if(!($found))
			{
				return true;
			}
			
			require_once JPATH_SITE . '/components/com_seoglossary/models/glossaries.php';
			require_once JPATH_SITE . '/components/com_seoglossary/helpers/seoglossary.php';
				$this->loadLanguage();
			$doc = Factory::getDocument();
			$com_params = ComponentHelper::getParams( 'com_seoglossary' );
	
		$custom_css = $com_params->get('custom_css', '') . "\n";
		if (!defined('SEOG_CUSTOM_CSS')) {
		    $doc->addStyleDeclaration($custom_css);
		    define('SEOG_CUSTOM_CSS', 1);
		}
	
			if($com_params->get('show_glossaryplugin',1)==0 || $com_params->get('disable_tooltips',0) == 1 || $com_params->get('disable_javascript',0) == 1 )
			{
				return;
			}
			
			$tooltip_layout=$com_params->get('tooltip_layout',1);
			if($tooltip_layout==0){
			
			if($com_params->get('advance_jquery',1)==1)
				{
					if (version_compare(JVERSION, '4.0', 'ge'))
                {
					//	$doc->addScript( JURI::root(true).'/media/vendor/jquery/js/jquery.js' );

					
				}
				else
				{
					if (defined('SEOGJ3')) {
							JHtml::_('jquery.framework');
                	}
					else
					{
						$doc->addScript( '//code.jquery.com/jquery-3.6.4.js' );
					}
				}
				}
				
			
			$delay = $com_params->get('tooltip_delay',1000);
			$tool_width=intval($com_params->get('tooltip_width','200'));
			$datatheme=$com_params->get('datatheme','qtip-plain');
			$datatipeventout=$com_params->get('datatipeventout','mouseout');
				
			$showclose=$com_params->get('showclose',0);
			if($showclose)
			{
				$mjs=",button: 'Close'";
			}
			else
			{
				$mjs="";
			}
			if($datatipeventout=="load")
			{
				
				$djs=",
			hide: {
			event: 'unfocus'
			}";
			}
			else
			{
				$datatipdelay=$com_params->get('datatipdelay',500);
				$djs=",
				hide: {
				delay: '".$datatipdelay."'
				}";
			}
			$tooltip_position=$com_params->get('tooltip_position','top center');
			$my_position=$com_params->get('my_position','bottom center');
			$js="";
			if($com_params->get('advance_jquery_conflict',0)==1)
			{
				$js.="jQuery.noConflict();";
			}
			    if (!defined('SEOG_JS')) {
			$click='';
			if($event==0)
			{
				$click=" show: {event: 'click'},";
			}
					$doc->addScript( JURI::root(true)."/components/com_seoglossary/assets/js/jquery.qtip.js" );
				$doc->addStyleSheet( JURI::base(true)."/components/com_seoglossary/assets/css/jquery.qtip.css" );
			$js.="jQuery(document).ready(function() {jQuery('.mytool a,.mytool abbr').qtip({
			style: { classes: '".$datatheme."' },
			
			position: {
				   my: '".$my_position."',
				   at:'".$tooltip_position."',
				   target: 'mouse' ,
					adjust: {
					    method: 'flipinvert'
					}
			},
			".$click."
			content: {      
			    text: function(event, api) {
		        return jQuery(this).attr('title');
			},
			title: function(event, api) {
		        return jQuery(this).text();
			}
			".$mjs."
			}".$djs."
			})});";
			
			$doc->addScriptDeclaration($js);
			
				$tooltip_style=".qtip{width:95%!important;max-width:".$tool_width."px;}";
				
				if($datatheme=="qtip-plain")
				{
					$font_size=intval($com_params->get('font_size','12'));
					$background_color = $com_params->get('background_color', '#EEEEEE');
					$barder_color = $com_params->get('barder_color', '#FFFFFF');
					$font_color = $com_params->get('font_color', '#444444');
					$head_color=$com_params->get('backgroundhead_color', '#444444');
					$tooltip_style.=".qtip-default .qtip-titlebar{
					background-color:".$head_color.";
					color:".$font_color.";
					border-color: ".$barder_color.";	
					}
					.qtip-content
					{
					background-color:".$background_color.";
					color:".$font_color.";
					border-color: ".$barder_color.";	
					}
					.qtip-default
					{
					background-color:".$head_color.";
					border:1px solid ".$barder_color.";
					color:".$font_color.";
					font-size:".$font_size."px;
					}
					
					";
	
				}
				$doc->addStyleDeclaration($tooltip_style);
			define('SEOG_JS', 1);
			
			}
		}
		else if($tooltip_layout==2)
		{
			if (version_compare(JVERSION, '4.0', 'ge'))
                {
					if($event==1)
					{
						
			Joomla\CMS\HTML\HTMLHelper::_('bootstrap.popover', 'a.hasPopover', ['trigger' => 'hover focus']);
					}
					else
					{
			Joomla\CMS\HTML\HTMLHelper::_('bootstrap.popover', 'a.hasPopover', ['trigger' => 'click focus']);
					}
		}else{
			JHtml::_('bootstrap.popover');
				}
		}
		else if($tooltip_layout==3)
		{
			if($com_params->get('advance_jquery',1)==1)
			{
				if (version_compare(JVERSION, '4.0', 'ge'))
                {
					//	$doc->addScript( JURI::root(true).'/media/vendor/jquery/js/jquery.js' );
					
				}
				else
				{
				if (defined('SEOGJ3')) {
					
				JHtml::_('jquery.framework');
					}
					else
					{
						$doc->addScript( '//code.jquery.com/jquery-3.7.0.min.js' );
					}
				}
			}
			 if (!defined('SEOG_JS2')) {
				$trigger='';
				if($event==1)
					{
						$trigger=" trigger: 'custom',";
					}
					else
					{
						$trigger=" trigger: 'click',";
					}
			$doc->addScript(JURI::root(true).'/components/com_seoglossary/assets/js/tooltipster.bundle.min.js');
			$doc->addScript(JURI::root(true).'/components/com_seoglossary/assets/js/tooltipster-scrollableTip.min.js');
			$doc->addStyleSheet(JURI::root(true).'/components/com_seoglossary/assets/css/tooltipster.bundle.min.css');
			$js="jQuery(document).ready(function() {jQuery('.mytool a,.mytool abbr').tooltipster({
			   animation: 'fade',
			   contentAsHTML:true,
			   theme: 'tooltipster-light',
			   ".$trigger."
			   interactive:true,
			plugins: ['sideTip', 'scrollableTip'],
			   maxWidth:400,
    triggerOpen: {
          mouseenter: true,
        touchstart: true
    },
    triggerClose: {
       mouseleave: true,
        originClick: true,
        touchleave: true,
		scroll: true,
        tap: true
    }
			})});";
			$doc->addScriptDeclaration($js);
			$background_color = $com_params->get('mbackgroundhead_color', '#444444');
					$barder_color = $com_params->get('mbarder_color', '#FAFAFA');
					$font_color = $com_params->get('mfont_color', '#FAFAFA');
					$head_color=$com_params->get('mbackgroundhead_color', '#FAFAFA');
			
					
			$css="abbr
			{
			border-bottom: 1px dotted;
			}
			a.tooltipstered {
			 border-bottom: 1px dashed;
			}.tooltipster-sidetip.tooltipster-light .tooltipster-box{border-radius:3px;border:1px solid ".$barder_color.";background:".$background_color."}.tooltipster-sidetip.tooltipster-light .tooltipster-content{color:".$font_color."}.tooltipster-sidetip.tooltipster-light .tooltipster-arrow{height:9px;margin-left:-9px;width:18px}.tooltipster-sidetip.tooltipster-light.tooltipster-left .tooltipster-arrow,.tooltipster-sidetip.tooltipster-light.tooltipster-right .tooltipster-arrow{height:18px;margin-left:0;margin-top:-9px;width:9px}.tooltipster-sidetip.tooltipster-light .tooltipster-arrow-background{border:9px solid transparent}.tooltipster-sidetip.tooltipster-light.tooltipster-bottom .tooltipster-arrow-background{border-bottom-color:".$background_color.";top:1px}.tooltipster-sidetip.tooltipster-light.tooltipster-left .tooltipster-arrow-background{border-left-color:".$background_color.";left:-1px}.tooltipster-sidetip.tooltipster-light.tooltipster-right .tooltipster-arrow-background{border-right-color:".$background_color.";left:1px}.tooltipster-sidetip.tooltipster-light.tooltipster-top .tooltipster-arrow-background{border-top-color:".$background_color.";top:-1px}.tooltipster-sidetip.tooltipster-light .tooltipster-arrow-border{border:9px solid transparent}.tooltipster-sidetip.tooltipster-light.tooltipster-bottom .tooltipster-arrow-border{border-bottom-color:".$barder_color."}.tooltipster-sidetip.tooltipster-light.tooltipster-left .tooltipster-arrow-border{border-left-color:".$barder_color."}.tooltipster-sidetip.tooltipster-light.tooltipster-right .tooltipster-arrow-border{border-right-color:".$barder_color."}.tooltipster-sidetip.tooltipster-light.tooltipster-top .tooltipster-arrow-border{border-top-color:".$barder_color."}.tooltipster-sidetip.tooltipster-light.tooltipster-bottom .tooltipster-arrow-uncropped{top:-9px}.tooltipster-sidetip.tooltipster-light.tooltipster-right .tooltipster-arrow-uncropped{left:-9px}";
			$doc->addStyleDeclaration($css);
			define('SEOG_JS2', 1); 
			}
		}
		else
		{
			if($com_params->get('advance_jquery',1)==1)
			{
				if (version_compare(JVERSION, '4.0', 'ge'))
                {
					//	$doc->addScript( JURI::root(true).'/media/vendor/jquery/js/jquery.js' );
					
				}
				else
				{
				if (defined('SEOGJ3')) {
				JHtml::_('jquery.framework');
					}
					else
					{
						$doc->addScript( '//code.jquery.com/jquery-3.7.0.min.js' );
					}
				}
			}
			 if (!defined('SEOG_JS2')) {
			$doc->addScript(JURI::root(true).'/components/com_seoglossary/assets/js/jqeasytooltip.v1.3.js');
			$doc->addStyleSheet(JURI::root(true).'/components/com_seoglossary/assets/css/jqeasytooltip.css');
			$tooltip_style ="abbr
			{
			border-bottom: 1px dotted;
			}
			span .jqeasytooltip {
			 border-bottom: 1px dashed;
			}";
			  $doc->addStyleDeclaration($tooltip_style);
			     
			  $js="<script type='text/javascript'>function closeJQTip(id){ if(window.jQuery)
				{ jQuery('.jqeasytooltip'+id).jqeasytooltip(('close',{})); } }</script>";
			  $doc->addCustomTag($js);
			     define('SEOG_JS2', 1); 
			      }

		}
		

   
			$db = Factory::getDbo();
			$nullDate = $db->getNullDate();
			$now = Factory::getDate()->toSql();
			$search_text = $db->escape(strip_tags($content->text));
			$lang = Factory::getLanguage();
			$query = $db->getQuery(true);
			$query = $db->getQuery(true);
			$query->select('t.* ');
			$query->from('#__seoglossary as t');
			$query->join('LEFT', '#__seoglossaries g ON t.catid = g.id');
			$query->where('t.state = 1');
			$query->where('g.state = 1');
			if(version_compare($db->getConnection()->server_info,'7.9.9','>'))
			{
				$query->where("(LOWER('{$search_text}') LIKE CONCAT('%', LOWER(t.tterm), '%'))");
			}
			else
			{
				$query->where("(LOWER('{$search_text}') LIKE CONCAT('%', LOWER(t.tterm), '%') OR '{$search_text}' REGEXP REPLACE(REPLACE(tsynonyms, ', ', '|'), ',', '|'))");
			}
			$query->where('g.language IN (' . $db->quote(Factory::getLanguage()->getTag()) . ',' . $db->quote('*') . ')');
			$query->where("(t.publish_up = ".$db->Quote($nullDate)." OR t.publish_up <= ".$db->Quote($now).")");
			$query->where("((t.publish_down = ".$db->Quote($nullDate)." OR t.publish_down >= ".$db->Quote($now)."))");
			$query->where("  NOT FIND_IN_SET(".$db->quote($context).",g.disable_context) ");
				if($fmenu)
				{
					$menu_id=(int)$fmenu->id;
					$query->where("NOT FIND_IN_SET(".$menu_id.",g.disable_menu)");		
				}
				if($component[0])
				{
					$disable_component=$db->quote($component[0]);
					$query->where("NOT FIND_IN_SET(".$disable_component.",g.disable_component)");		
				}
					if($component[0]=="com_content")
					{
						if(isset($content->catid))
						{
						$query->where("NOT FIND_IN_SET(".$content->catid.",g.disable_category) ");
						}
					}
			$query->order('t.ordering');
			$db->setQuery($query);
			if (!($glossdata = $db->loadObjectList())) {
				if (Factory::getApplication()->input->getInt('diagnose',0) == 1) {
					echo "<b>" . $db->getErrorMsg() . "</b><br />";
				}
			}
			
			$glossaryItems = array( );
			$a = new SeoglossaryHelper();
			$i = 0;
			if($found)
			{
			foreach ( $glossdata as $item )
			{
				$i++;
				$a = new SeoglossaryHelper( );
				$defination ="";
				if(@$item->icon)
				{
					$defination.="<img src='".$item->icon."'>";
				}
				$defination .=$a->processimage( $item->tdefinition );
					$link = SeoglossaryHelper::getUrlLink($item); 
				if($event==0)
				{
					$link ='javascript:void(0)';
				}
		    if (!empty($item->tmore) && $com_params->get('show_read_more_tooltip', 0) == 1) {
			$format = '<div class="seog-tooltip-more-link"><a href="%s">' . $com_params->get('more_link', '...') . '</a></div>';
			$defination .= sprintf($format, JRoute::_($link));
		    }
	
		    if (Factory::getApplication()->input->getInt('diagnose',0) == 1) {
			echo "<b>Looking for {$item->tterm}.</b><br />";
		    }
	
				$glossaryItems[] = array(
					'link' => $link,
			'phrase' => ($item->tterm),
					'explanation' => ($defination),
			'synonym_of' => 0,
			'show_tooltip'=>@$item->showtooltip,
				'target'=>@$item->target,
				'nofollow'=>@$item->nofollow
				);
				
				if ($com_params->get ('no_link', 0) == 1)
				{
					$idx = count($glossaryItems) - 1;
					$entry = &$glossaryItems[$idx];
					unset($entry['link']);
				}
	
		    $synonyms = explode(',', $item->tsynonyms);
		    foreach($synonyms as $syn)
		    {
			$syn = trim($syn);
			if (empty($syn))
			    continue;
	
			if (Factory::getApplication()->input->getInt('diagnose',0) == 1) {
			    echo "<b>Looking for {$syn}.</b><br />";
			}
	
			$glossaryItems[] = array(
			    'link' => $link,
			    'phrase' => $syn,
			    'explanation' => ($defination),
			    'synonym_of' => @$item->tterm,
				'show_tooltip'=>@$item->showtooltip,
				'target'=>@$item->target,
				'nofollow'=>@$item->nofollow
			);
	
			if ($com_params->get ('no_link', 0) == 1)
			{
			    $idx = count($glossaryItems) - 1;
			    $entry = &$glossaryItems[$idx];
			    unset($entry['link']);
			}
		    }
	
			}
			
			
			$html = ($content->text);
			$replace="";
			$content->text = $a->replaceContext( $html, $glossaryItems );
			}
			$seogItems = array();
			if (preg_match_all($regex, $content->text, $matches, PREG_PATTERN_ORDER) > 0) {
			foreach ($matches[0] as $match) {
				$replace = strip_tags(preg_replace("/{.+?}/", "", $match));
					
			$content->text = preg_replace($regex, $replace, $content->text,1);
				}
			}
			
			if (preg_match_all($regex_enable, $content->text, $matches, PREG_PATTERN_ORDER) > 0) {
				$m=0;
				foreach ($matches[0] as $match) {
					$m++;
					$replace = strip_tags(preg_replace("/{.+?}/", "", $match));
					foreach ( $glossdata as $item )
					{
						if(strip_tags($item->tterm)==$replace)
						{
							$i++;
				$a = new SeoglossaryHelper( );
				$defination = $a->processimage( $item->tdefinition );
		   $link = SeoglossaryHelper::getUrlLink($item); 
	
		    if (!empty($item->tmore) && $com_params->get('show_read_more_tooltip', 0) == 1) {
			$format = '<div class="seog-tooltip-more-link"><a href="%s">' . $com_params->get('more_link', '...') . '</a></div>';
			$defination .= sprintf($format, JRoute::_($link));
		    }
	
		    if (Factory::getApplication()->input->getInt('diagnose',0) == 1) {
			echo "<b>Looking for {$item->tterm}.</b><br />";
		    }
	
				$seogItems[] = array(
					'link' => $link,
			'phrase' => ($item->tterm),
					'explanation' => ($defination),
			'synonym_of' => 0,
				'show_tooltip'=>@$item->showtooltip,
				'target'=>@$item->target,
				'nofollow'=>@$item->nofollow
				);
				
				if ($com_params->get ('no_link', 0) == 1)
				{
					$idx = count($seogItems) - 1;
					$entry = &$seogItems[$idx];
					unset($entry['link']);
				}
	
		    $synonyms = explode(',', $item->tsynonyms);
		    foreach($synonyms as $syn)
		    {
			$syn = trim($syn);
			if (empty($syn))
			    continue;
	
			if (Factory::getApplication()->input->getInt('diagnose',0) == 1) {
			    echo "<b>Looking for {$syn}.</b><br />";
			}
	
			$seogItems[] = array(
			    'link' => $link,
			    'phrase' => $syn,
			    'explanation' => ($defination),
			    'synonym_of' => @$item->tterm,
				'show_tooltip'=>@$item->showtooltip,
				'target'=>@$item->target,
				'nofollow'=>@$item->nofollow
			);
	
			if ($com_params->get ('no_link', 0) == 1)
			{
			    $idx = count($seogItems) - 1;
			    $entry = &$seogItems[$idx];
			    unset($entry['link']);
			}
			}
			
			$html = ($content->text);
			$replace="";
			
			$html = preg_replace($regex_enable, strip_tags($item->tterm), $html,1);
			$content->text = $a->replaceContext( $html, $seogItems);
			
						}			   
					}
			}
			}
			
			return true;
		}
	}
