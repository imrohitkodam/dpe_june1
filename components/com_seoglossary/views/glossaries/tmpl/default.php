<?php
/**
 * SEO Glossary Glossaries View Default layout
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
if(!defined('DS')){
define('DS',DIRECTORY_SEPARATOR);
}

SeoglossaryHelper::injectTemplateForViewLayout();

$user = JFactory::getUser( );
$userId = $user->get( 'id' );
$canDo = SeoglossaryHelper::getActions( );
SeoglossaryHelper::setMenuMetadata();
$d = JFactory::getDocument();

$l = JFactory::getApplication()->input->getString('letter', null);
$l = str_replace('(s)', '#', $l);
if (!is_null($l) && (!empty($l) || $l == '0')) {
	$l = trim(strip_tags($l));
	SeoglossaryHelper::addTitleElement($l);
	if ($this->params->get('set_description', 1) == 1) {
		$d->setDescription( $l . ' - ' . $d->getDescription());
	}
}

if ( JFactory::getApplication( )->getMenu( )->getActive( ) )
{
    $menu_item = JFactory::getApplication( )->getMenu( )->getActive();
		if($menu_item->component=="com_seoglossary")
		{
	$currentMenuName = ($menu_item->getparams()->get('show_page_heading') && !($menu_item->getparams()->get('page_heading') == '')) ? $menu_item->getparams()->get('page_heading') : $menu_item->title;
    if (!($menu_item->getparams()->get('page_title') == '')) {
        $d->setTitle($menu_item->getparams()->get('page_title'));
    }
		}
		else
		{
	$currentMenuName = null;
			
		}
}
else
{
	$currentMenuName = null;
}
require_once JPATH_COMPONENT . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'glossaries.php';
$myglossdata = new SeoglossaryModelglossaries;
$catid = JFactory::getApplication()->input->getInt('catid', null);
if ($catid !== null) {
	$c = $myglossdata->getGlossaryCategory($catid);
	if ($c->state != 1) {
	JFactory::getApplication()->enqueueMessage(JText::_("Page Not Found"),'error');
	}
}

$l = JFactory::getLanguage();
$fullCode = $l->getTag();
$codes_a = explode('-', $fullCode);
$code = reset($codes_a);
?>
<div id="com_glossary" class="theme-<?php echo $this->glossarytheme;?>">

<?php if ( $this->params->get( 'show_fb_list', 0 ) != 0 ) { ?>
<div id="fb-root"></div>
<script language="javascript">(function(d, s, id) {
  var js, fjs = d.getElementsByTagName(s)[0];
  if (d.getElementById(id)) return;
  js = d.createElement(s); js.id = id;
  js.src = "//connect.facebook.net/<?php echo str_replace('-','_',$fullCode); ?>/all.js#xfbml=1";
  fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));</script>
<?php } ?>

<?php if ( $this->params->get( 'show_tw_list', 0 ) != 0 ) { ?>
<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>
<?php } ?>


	<?php if ( $this->params->get( 'show_glossary_name', 1 ) != 0 ){
        $cat = $myglossdata->getGlossaryCategory($catid);
        ?>
        <h1><?php
        if ( empty( $currentMenuName ) )
        {
            echo $cat->name;
        }
        else
        {
            echo $currentMenuName;
        }
        ?></h1>
        <div id="seog-glossary-description"><?php echo $cat->description; ?></div>

    <?php } // endif ?>

	<?php 
	if( $this->params->get( 'show_count', 1 ) != 0 ) 
	{ 
		echo JText::sprintf( 'COM_SEOGLOSSARY_NUMBER_ENTRIES_IN_GLOSSARY', $this->pagination->total );
	} 
	?>
	
	<?php // Social Media Sharing buttons
		  if( ($this->params->get('show_tw_list',0) != 0)  || $this->params->get('show_fb_list',0) != 0 ) { ?>
	<div class="listitem_socialmediashares">
	<?php } ?>
	<?php if( $this->params->get('show_tw_list',0) != 0 ) { ?>
	<div class="socialmediabutton">
	<a href="https://twitter.com/share" class="twitter-share-button" data-lang="<?php echo $code; ?>">Tweet</a>
	</div>
	<?php } ?>
	
	<?php if( $this->params->get('show_fb_list',0) != 0 ) { ?>
	<div class="socialmediabutton">
	<div class="fb-like" data-send="false" data-layout="button_count" data-width="450" data-show-faces="true" data-font="tahoma"></div>
	</div>
	<?php } ?>
	<?php if( ($this->params->get('show_tw_list',0) != 0)  || $this->params->get('show_fb_list',0) != 0 ) { ?>
	</div>
	<div></div>
	<?php } 
	// End Social Media Sharing buttons ?>
	
	<?php  if(($canDo->get('core.create'))){
	?>
	<div class="seogl-newterm"><a href="<?php echo JRoute::_('index.php?option=com_seoglossary&view=glossary&layout=edit' . (($catid !== null) ? '&catid='.$catid : ''));?>" class="btn btn-small btn-success">
		<?php echo JText::_( 'COM_SEOGLOSSARY_NEW_ENTRY' ); ?>
	</a></div>
	<?php }?>
	
	<?php
	if ( $this->params->get( 'search', 1 ) != 0 )
	{
		echo $this->searchform;
	}
	?>
	
	<?php
	if ( $this->params->get( 'show_glossarieslist', 1) != 0 )
	{
		echo $this->glossariesList;
	}
	?>

	<?php
	if ( $this->params->get( 'alphabet', 1 ) != 0 )
	{
		echo $this->alphabetform;
	}
	?>
	
	<?php
	if ( $this->params->get( 'top_pagination', 0 ) != 0 )
	{
		echo '<div id="seog-top-pagination">' . $this->pagination->getPagesLinks( ) . '</div>';
	}
	?>
<?php
if(count($this->items)==0)
{
	echo "<h3>".JTEXT::_('COM_SEOGLOSSARY_NORESULT')."</h3>";
}
else 
{
if($this->glossarytheme=="grid") {
	if($this->params->get('advance_jquery',1)==true)
	{
		JHtml::_('jquery.framework');
	}
	$d->addStyleSheet(JURI::base().'components/com_seoglossary/assets/css/masonary_seog.css');
	$d->addScript(JURI::base().'components/com_seoglossary/assets/js/masonry.pkgd.js');
	?>
	<div class="container-fluid">
<div class="row row-fluid grid" data-masonry='{ "itemSelector": ".grid-item" }'>
	<?php foreach ($this->items as $i => $item) :
	$tterm_link = SeoglossaryHelper::getUrlLink($item,JFactory::getApplication()->input->getInt( 'Itemid' ));
	$icon=null;
			if(@$item->icon)
						{
							$icon=$item->icon;
						}
						else if($this->show_grid_theme)
						{
							$icon=JURI::base()."components/com_seoglossary/assets/images/default-img.jpg";
						}
			?>
			<div class="span4 col-xs-12 col-md-4 grid grid-item"  style="margin-left:0px;" >
				<div class="masonary_seo">
					<?php if($icon) { ?>
				<div class="view second-effect">
					
				<img src="<?php echo $icon;?>" alt="<?php echo $item->tterm;?>" class="wpcufpn_default">
				<div class="mask">  
			   <a href="<?php echo $tterm_link; ?>" class="info"> <?php echo $item->tterm;?></a> 
				  </div>
				</div>  <?php } ?>
			  
				<span class="title"><span style="height:1.35em" class="line_limit"><a href="<?php echo $tterm_link; ?>"> <?php echo $item->tterm;?></a><?php  if(($canDo->get('core.edit')))
				{
				?>
		<a href="<?php echo JRoute::_( 'index.php?option=com_seoglossary&view=glossary&catid='.$item->catid.'&layout=edit&id='.$item->id );?>" class="button btn btn-danger" target="_blank">
		<?php echo JText::_( 'JACTION_EDIT' ); ?></a> <?php } ?></span></span>
				<span class="date">
				<?php
				
if($this->params->get('show_glossary',0)!=0)
{
$cat=$myglossdata->getGlossaryCategory($item->catid); ?>
<?php echo JText::_( 'COM_SEOGLOSSARY_TITLE_CATGLOSSARIES' ); ?>: <?php
echo($cat->name."<br/>");
}
if( ( $this->params->get('hide_author',1) != 0 ) && !empty($item->tname) ) {
				?>

					<?php echo JText::_( 'COM_SEOGLOSSARY_AUTHOR' ); ?>: <?php
					if ( !empty( $item->tname ) )
					{
						echo $item->tname."<br/>";
					}
					?>
					<?php }?>
				<?php
				if(( $this->params->get('show_hits',1) != 0) &&(@$item->hits)) {
				?>
					<?php echo JText::_( 'JGLOBAL_HITS' ); ?>: <?php
					
						echo $item->hits."<br/>";
					?>
					<?php }?>
					<?php if($item->tsynonyms) { ?>
					<?php echo JText::_('COM_SEOGLOSSARY_FORM_LBL_GLOSSARY_TSYNONYMS'); ?>:
					<?php	
						echo $item->tsynonyms."<br/>";
					}
					$a = new SeoglossaryHelper( );
					$html = SeoglossaryHelper::applyShortcuts($item->tdefinition, $item->tterm);
						
					$prx = new stdClass();
					$prx->text = $html;
					
					JPluginHelper::importPlugin('content');
					
					$params = array();
					if($this->params->get('apply_content_plugins',1)!=0) {
						JFactory::getApplication()->triggerEvent('onContentPrepare', array('com_seoglossary.glossaries', &$prx, &$params, 0));
					}
					?>
				
			</span>
				<span class="text"><span  class="line_limit"><?php echo $prx->text;?></span></span>
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
				<?php if (!empty($item->tmore) && $this->params->get('show_read_more_tooltip', 0) == 1) {
                        $format = '<div class="wpcu-front-box bottom"><a href="%s">' . $this->params->get('more_link', '...') . '</a></div>';
                        echo sprintf($format, $tterm_link);
                    }?>
				</div>
				
			</div>
			<?php endforeach; ?>
</div>
	</div>
<?php }
else if($this->glossarytheme=="alpha")
{
	 if (version_compare(JVERSION, '3.10.0', 'lt')) {
	JHTML::_('behavior.modal');
	 }
	?>
	<style type="text/css">
	.sbox-content-iframe#sbox-content
	{
		overflow: hidden;
	}
	</style>
<div class="glossary-items">
		<?php
		$results=array();
		foreach ($this->items as $i => $item) :	
    $firstLetter = substr($item->tterm, 0, 1);
    $results[$firstLetter][] = $item;
endforeach;
foreach($results as $key=>$result)
{
	?>

<div class="glossary-group" id="<?php echo strtolower($key);?>">
	<h3 class="glossary-group-title"><?php echo $key;?></h3>
	<div class="glossary-group-items">
		<ul class="nav nav-pills nav-justified">
						<?php foreach($result as $res):
						if((@$res->extlink)&&($res->exturl))
						{
							$tterm_link =SeoglossaryHelper::getUrlLink($res,JFactory::getApplication()->input->getInt( 'Itemid' ));
						}
						else
						{
						$tterm_link = JRoute::_('index.php?option=com_seoglossary&view=glossary&catid='.((int)$res->catid).'&id='.((int)$res->id).'&Itemid='.JFactory::getApplication()->input->getInt("Itemid").'&tmpl=component&show_layout=1');
						}
						?>
						<li class="col-xs-12 col-sm-6 col-md-4 span3"><a href="<?php echo $tterm_link;?>" class="modal" 
    rel="{size: {x: 800, y: 500}, handler:'iframe'}"><?php echo $res->tterm;?></a></li>
						<?php endforeach; ?>
		</ul>
	</div>
</div>
<?php }
?>
</div>
<?php 

}
else {
	$class="";
	if($this->glossarytheme=="flat")
	{
		$class="flat";
	}
	else if($this->glossarytheme=="responsive")
	{
		$class="res";
	}
	?>
	<table class="glossaryclear table  <?php echo $class;?>" id="glossarylist">
		<thead>
			<tr class="header-seoglossary">
				<th class="glossary25"><?php echo JText::_( 'COM_SEOGLOSSARY_GLOSSARIES_TTERM' );?></th>
				<th class="glossary72"><?php echo JText::_( 'COM_SEOGLOSSARY_FORM_LBL_GLOSSARY_TDEFINITION' );?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($this->items as $i => $item) :
			?>
			<tr class="row<?php echo $i % 2;?>">
				<td>
					<?php
						$tterm_link =SeoglossaryHelper::getUrlLink($item,JFactory::getApplication()->input->getInt( 'Itemid' ));
	
					?>
					<a href="<?php echo $tterm_link; ?>"> <?php echo $item->tterm;?></a><?php  if(($canDo->get('core.edit')))
		{
		?>
		<a href="<?php echo JRoute::_( 'index.php?option=com_seoglossary&view=glossary&catid='.$item->catid.'&layout=edit&id='.$item->id );?>" class="button btn btn-danger" target="_blank">
		<?php echo JText::_( 'JACTION_EDIT' ); ?>
	</a>
		<?php }?>
					
				</td>
				<td><?php

if($this->params->get('show_glossary',0)!=0)
{
$cat=$myglossdata->getGlossaryCategory($item->catid);
if($this->params->get('show_top',1)!=0)
{
				?>
				<div>
					<em><?php echo JText::_( 'COM_SEOGLOSSARY_TITLE_CATGLOSSARIES' ); ?></em> -  <?php
echo($cat->name);
if($this->params->get('show_glossarydescription',0)!=0)
{
					?>
					<div>
						<em><?php echo JText::_( 'COM_SEOGLOSSARY_GLOSSARIES_DESCRIPTION' ); ?></em> -  <?php  echo( $cat->description );
						}
						}
						?>
					</div>
				</div><?php }?>
				<div>
					<?php
					$a = new SeoglossaryHelper( );
					$html = SeoglossaryHelper::applyShortcuts($item->tdefinition, $item->tterm);
						
					$prx = new stdClass();
					$prx->text = $html;
					
					JPluginHelper::importPlugin('content');
					$params = array();
					if($this->params->get('apply_content_plugins',1)!=0) {
						JFactory::getApplication()->triggerEvent('onContentPrepare', array('com_seoglossary.glossaries', &$prx, &$params, 0));
					}
				
					$html = $prx->text;
					//$html = $a->replaceContext( $html, $this->glossaryItems );
					if(@$item->icon)
						{
							echo '<img src="'.$item->icon.'">';
						}
                    if (!empty($item->tmore) && $this->params->get('show_read_more_tooltip', 0) == 1) {
                        $format = '<div class="seog-frontend-more-link"><a href="%s">' . $this->params->get('more_link', '...') . '</a></div>';
                        $html .= sprintf($format, $tterm_link);
                    }

					echo $html;
					
					?>
				</div>
				
				<?php
if( ( $this->params->get('hide_author',1) != 0 ) && !empty($item->tname) ) {
				?>

				<div class="authorblock">
					<em><?php echo JText::_( 'COM_SEOGLOSSARY_AUTHOR' ); ?></em> -  <?php
					if ( !empty( $item->tname ) )
					{
						echo $item->tname;
					}
					?>
				</div><?php }?>
				<?php
				if(( $this->params->get('show_hits',1) != 0) &&(@$item->hits)) {
				?>
				<div class="hits">
					<em><?php echo JText::_( 'JGLOBAL_HITS' ); ?></em> -  <?php
					
						echo $item->hits;
					?>
				</div><?php }
				if($item->tsynonyms) {
				?>
				<em><?php echo JText::_('COM_SEOGLOSSARY_FORM_LBL_GLOSSARY_TSYNONYMS'); ?></em> - 
				<?php	
						echo $item->tsynonyms."<br/>";
				}
				?>
				<div class="tags">
					<?php
					$item->tags = new JHelperTags;
					$item->tags->getItemTags('com_seoglossary.glossary' , $item->id);
					$item->tagLayout = new JLayoutFile('joomla.content.tags');
					echo $item->tagLayout->render($item->tags->itemTags);
					?>
				</div>
				
				</td>
			</tr>
			<?php endforeach;?>
		</tbody>
	</table>
			<?php }
			}
			?>	
				<?php
				if ( $this->params->get( 'bottom_pagination', 1 ) != 0 )
				{
					echo '<div id="seog-top-pagination" class="pagination">'.$this->pagination->getPagesLinks().'</div>';
				}
				?>
	

    <?php
    $disqus_shortname = trim($this->params->get('disqus_shortname', ''));
    $disqus_glossaries = $this->params->get('disqus_glossaries', 0);

    if ($disqus_glossaries && !empty($disqus_shortname)) : ?>
        <div id="disqus_thread"></div>
        <script type="text/javascript">
            /* * * CONFIGURATION VARIABLES: EDIT BEFORE PASTING INTO YOUR WEBPAGE * * */
            var disqus_shortname = '<?php echo $disqus_shortname; ?>'; // required: replace example with your forum shortname
            var disqus_identifier = '<?php echo "seoglossary-{$catid}"; ?>';

            /* * * DON'T EDIT BELOW THIS LINE * * */
            (function() {
                var dsq = document.createElement('script'); dsq.type = 'text/javascript'; dsq.async = true;
                dsq.src = '//' + disqus_shortname + '.disqus.com/embed.js';
                (document.getElementsByTagName('head')[0] || document.getElementsByTagName('body')[0]).appendChild(dsq);
            })();
        </script>
        <noscript>Please enable JavaScript to view the <a href="http://disqus.com/?ref_noscript">comments powered by Disqus.</a></noscript>
        <a href="http://disqus.com" class="dsq-brlink">comments powered by <span class="logo-disqus">Disqus</span></a>
    <?php endif; ?>

    <?php
    if ( $this->params->get( 'alphabetbelow', 0 ) != 0 )
    {
        echo $this->alphabetform;
    }
    ?>
</div>