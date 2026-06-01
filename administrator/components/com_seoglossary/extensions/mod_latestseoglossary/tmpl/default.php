<?php
/**
 * Latest SEO Glossary module Default layout
 *
 * We developed this code with our hearts and passion.
 * We hope you found it useful, easy to understand and change.
 * Otherwise, please feel free to contact us at contact@joomunited.com
 *
 * @package 	SEO Glossary
 * @copyright 	Copyright (C) 2012 JoomUnited (http://www.joomunited.com). All rights reserved.
 * @license 	GNU General Public License version 2 or later; http://www.gnu.org/licenses/gpl-2.0.html
 */

// no direct access
defined( '_JEXEC' ) or die ;
?>
<?php 

$document=JFactory::getDocument();
if($theme==1)
{
	$coloumn=$params->get('coloumn',3);
	$lowercol=1;
if($coloumn==3)
{
		$lowercol=2;	
}
$document->addStyleSheet(JURI::base().'components/com_seoglossary/assets/js/assets/owl.carousel.css');
$document->addStyleSheet(JURI::base().'components/com_seoglossary/assets/js/assets/owl.theme.default.css');
$document->addScript(JURI::base().'components/com_seoglossary/assets/js/owl.carousel.js');
$document->addStyleSheet(JURI::base().'components/com_seoglossary/assets/css/masonary_seog.css');
$qparams=JComponentHelper::getParams( 'com_seoglossary' );
$document->addScriptDeclaration(" jQuery(document).ready(function() {
              jQuery('.owl_carosal_".$module->id."').owlCarousel({
                items: ".$coloumn.",
                nav:true,
              loop:true,
                responsiveClass:true,
                responsive:{
                0:{ items:1,
            nav:true
            },
            600:{
            items:".$lowercol.",
            nav:false
            },
            900:{
            items:".$coloumn.",
            nav:false
            }
            }
            });
            })");
	?>
	 <div class="owl_carosal_<?php echo $module->id;?>  owl-carousel owl-theme">
		<?php
		 foreach ($list as $item) :
		 $icon=null;
			if(@$item->icon)
						{
							$icon=$item->icon;
						}
						else
						{
							$icon=JURI::base()."components/com_seoglossary/assets/images/trans.png";
						}
						$tterm_link = SeoglossaryHelper::getUrlLink($item);
		 ?>
		 <div class="item">
		<div class="masonary_seo">
					<?php if($icon) { ?>
				<div class="view second-effect">
					
				<img src="<?php echo $icon;?>" alt="<?php echo $tterm_link; ?>" class="wpcufpn_default">
				<div class="mask">  
			   <a href="<?php echo $tterm_link; ?>" class="info"> <?php echo $item->tterm;?></a> 
				  </div>
				</div>  <?php } ?>
			  
				<span class="title"><span style="height:1.35em" class="line_limit"><a href="<?php echo $tterm_link; ?>"> <?php echo $item->tterm;?></a></span></span>
				<span class="date">
				<?php
if( ( $qparams->get('hide_author',1) != 0 ) && !empty($item->tname) ) {
				?>

					<?php echo JText::_( 'COM_SEOGLOSSARY_AUTHOR' ); ?>: <?php
					if ( !empty( $item->tname ) )
					{
						echo $item->tname."<br/>";
					}
					?>
					<?php }?>
				<?php
				if(( $qparams->get('show_hits',1) != 0) &&(@$item->hits)) {
				?>
					<?php echo JText::_( 'JGLOBAL_HITS' ); ?>: <?php
					
						echo $item->hits."<br/>";
					?><?php }
					?>
			</span>
				<span class="text"><span  class="line_limit"><?php
				$extra="";
				$countword=intval( $params->get( 'count_intro' ,50) );
				if(strlen(strip_tags( $item->tdefinition ))>=$countword)
				   {
						$extra="...";
					
				   }
				echo substr( strip_tags( $item->tdefinition ), 0, intval( $params->get( 'count_intro' ,50) ) ) .$extra;?></span></span>
				<span class="custom_fields">
					<div class="tags">
					<?php
					$item->tags = new JHelperTags;
					$item->tags->getItemTags('com_seoglossary.glossary' , $item->id);
					$item->tagLayout = new JLayoutFile('joomla.content.tags');
					echo $item->tagLayout->render($item->tags->itemTags);
					?>
				</div>
				</span>
				<?php if (!empty($item->tmore) ) {
                        $format = '<div class="wpcu-front-box bottom"><a href="%s">' . $qparams->get('more_link', '...') . '</a></div>';
                        echo sprintf($format, $tterm_link);
                    }?>
				</div>
		 </div>
		 <?php endforeach;
		?>
	 </div>
	
<?php }
else
{
	$document->addStyleDeclaration(".seoglossary".$module->id." li {list-style-type:none;width:100%;}");
?>
<ul class="seoglossary<?php echo $module->id?> seoglossary<?php echo htmlspecialchars( $params->get( 'moduleclass_sfx' ) );?> <?php if($bootstrap_layout=="nav") { ?>nav nav-pills<?php }else if($bootstrap_layout=="sidebar") { ?>nav nav-list<?php } ?>">
	<?php foreach ($list as $item) :
	$tterm_link = SeoglossaryHelper::getUrlLink($item);
	if( ($params->get( 'mode', 0) == 2) )
			{
				$extra="";
				$countword=intval( $params->get( 'count_intro' ,50) );
					if(strlen(strip_tags( $item->tdefinition ))>=$countword)
				   {
						$extra="...";
					
				   }
				?>
			<li class="nav-item">	<a href="<?php echo $tterm_link;?>"></a><?php
			
			echo "<br/><span class='seodesc'>" . substr( strip_tags( $item->tdefinition ), 0, $countword ) . $extra."</span>";?>
			</li>
			<?php }else {?>
	<li  class="nav-item">
		<a href="<?php echo $tterm_link;?>"><?php echo $item->tterm;?></a>
        <?php
        if ( ($params->get( 'mode', 0) == 1) )
			{
				$extra="";
				$countword=intval( $params->get( 'count_intro' ,50) );
				if(strlen(strip_tags( $item->tdefinition."<br/>".$item->tmore ))>=$countword)
				   {
			
						$extra="...";
					
				   }
				echo "<br/><span class='seodesc'>" . substr( strip_tags( $item->tdefinition."<br/>".$item->tmore ), 0, intval( $params->get( 'count_intro' ,50) ) ) . $extra."</span>";
			}
			
		?>
	</li>
	<li class="divider"></li>
	<?php } ?>
	<?php endforeach;?>
</ul>
<?php } ?>
