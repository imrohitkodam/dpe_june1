<?php
/**
 * SEO Glossary Glossary View Default layout
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
if (version_compare(JVERSION, '4.0', 'ge'))
{}else
{
JHtml::_( 'behavior.tooltip' );
}
JHtml::_( 'behavior.formvalidator' );

$canDo = SeoglossaryHelper::getActions( );
SeoglossaryHelper::setMenuMetadata();
require_once JPATH_COMPONENT . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'glossaries.php';
$myglossdata = new SeoglossaryModelglossaries;
$c = $myglossdata->getGlossaryCategory($this->item->catid);
$itemid=JFactory::getApplication()->input->getInt( 'Itemid' );
if ($this->item->state != 1 || $c->state != 1) {
	JFactory::getApplication()->enqueueMessage(JText::_("Page Not Found"),'error');
	return false;
}

$d = JFactory::getDocument();
if ($this->params->get('set_description', 1) == 1) {
	$def = strip_tags($this->item->tdefinition);
	$def = str_replace("\n", " ", $def);
	$def = str_replace("\r", "", $def);
	$def = trim($def);
	$short_def = substr($def, 0, 140);
	if ($short_def != $def) {
		$short_def .= '...';
	}
	$d->setDescription($this->item->tterm . ' - ' . $short_def);
}
if ($this->params->get('set_keyword', 1) == 1) {
	$keywords = $d->getMetaData('keywords');
	if (!empty($keywords)) {
		$keywords = ', ' . $keywords;
	}
	$d->setMetaData( 'keywords', $this->item->tterm  . $keywords );
}
if($this->item->metakey)
{
	$d->setMetaData( 'keywords', $this->item->metakey);
}
if($this->item->metadesc)
{
	$d->setDescription($this->item->metadesc);
}
$l = JFactory::getLanguage();
$fullCode = $l->getTag();
$codes_a = explode('-', $fullCode);
$code = reset($codes_a);
$show_layout=JFactory::getApplication()->input->getInt('show_layout');
if($show_layout==1)
{
	$this->glossarytheme="grid";
	?>
<style type="text/css">
#com_glossary  #glossarysearch,#com_glossary .nav-pills,#com_glossary .glossaryalphabet,#com_glossary h3,.custom-select.bigselect
{
	display:none !important;
}
#com_glossary
{
	max-width: 90%;
}
	</style>
<?php
}
?>
<div id="com_glossary" class="theme-<?php echo $this->glossarytheme;?>">

    <?php if ( $this->params->get( 'show_term_name', 1 ) != 0 ): ?>
        <h1 class="seogl-term-title"><?php echo $this->item->tterm; ?></h1>
    <?php endif; ?>


<?php if ( $this->params->get( 'show_fb_term', 0 ) != 0 ) { ?>
<div id="fb-root"></div>
<script language="javascript">(function(d, s, id) {
  var js, fjs = d.getElementsByTagName(s)[0];
  if (d.getElementById(id)) return;
  js = d.createElement(s); js.id = id;
  js.src = "//connect.facebook.net/<?php echo str_replace('-','_',$fullCode); ?>/all.js#xfbml=1";
  fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));</script>
<?php } ?>

<?php if ( $this->params->get( 'show_tw_term', 0 ) != 0 ) { ?>
<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>
<?php } ?>


<?php if( ($this->params->get('show_tw_term',0) != 0) || $this->params->get('show_fb_term',0) != 0 ) { ?>
<div class="term_socialmediashares">
<?php } ?>
<?php if( $this->params->get('show_tw_term',0) != 0 ) { ?>
<div class="socialmediabutton">
<a href="https://twitter.com/share" class="twitter-share-button" data-lang="<?php echo $code; ?>">Tweet</a>
</div>
<?php } ?>
<?php if( $this->params->get('show_fb_term',0) != 0 ) { ?>
<div class="socialmediabutton">
<div class="fb-like" data-send="false" data-layout="button_count" data-width="450" data-show-faces="true" data-font="tahoma"></div>
</div>
<?php } ?>
<?php if( ($this->params->get('show_tw_term',0) != 0) || $this->params->get('show_fb_term',0) != 0 ) { ?>
</div>
<?php } ?>


	<?php  if(($canDo->get('core.create')))
{
	?>
	<div class="seogl-newterm"><a href="<?php echo JRoute::_( 'index.php?option=com_seoglossary&view=glossary&catid='.$this->item->catid.'&layout=edit' );?>" class="btn btn-small btn-success">
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
	<?php if($this->glossarytheme=="grid") {
		 $item=$this->item;
	if($this->params->get('advance_jquery',1)==true)
	{
		JHtml::_('jquery.framework');
	}
	$d->addStyleSheet(JURI::base().'components/com_seoglossary/assets/css/masonary_seog.css');
	$d->addScript(JURI::base().'components/com_seoglossary/assets/js/masonry.pkgd.js');
	?>
	<div class="container-fluid">
<div class="row row-fluid grid" data-masonry='{ "itemSelector": ".grid-item" }'>
	<?php 
	$tterm_link = SeoglossaryHelper::getUrlLink($item,$itemid);
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
			<div class="span12 col-xs-12 col-md-12 grid grid-item grid-item-front"  style="margin-left:0px;" >
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
					?><?php }
					$a = new SeoglossaryHelper( );
						$html = SeoglossaryHelper::applyShortcuts($this->item->tdefinition."\r\n".$this->item->tmore, $this->item->tterm);
						
					$prx = new stdClass();
					$prx->text = $html;
					
					JPluginHelper::importPlugin('content');
					$params = array();
					if($this->params->get('apply_content_plugins',1)!=0) {
						JFactory::getApplication()->triggerEvent('onContentPrepare', array('com_seoglossary.glossaries', &$prx, &$params, 0));
					}
					?>
					<?php if($item->tsynonyms) {?>
				<?php echo JText::_('COM_SEOGLOSSARY_FORM_LBL_GLOSSARY_TSYNONYMS'); ?>:
				<?php	
						echo $item->tsynonyms."<br/>";
					?>
					<?php } ?>
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
				</div>
				
			</div>
</div>
	</div>
<?php } else {
	$class="";
	if($this->glossarytheme=="flat")
	{
		$class="flat";
	}
	else if($this->glossarytheme=="responsive")
	{
		$class="responsive";
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
			<tr class="row1">
				<td><a href="javascript:void(0)"> <?php echo $this->item->tterm;?></a><?php  if(($canDo->get('core.edit')))
		{
		?>
		<a href="<?php echo JRoute::_( 'index.php?option=com_seoglossary&view=glossary&catid='.$this->item->catid.'&layout=edit&id='.$this->item->id );?>" class="button btn btn-danger" target="_blank">
		<?php echo JText::_( 'JACTION_EDIT' ); ?>
	</a>
		<?php }?></td>
				<td><?php

if($this->params->get('show_glossary',0)!=0)
{
$cat=$myglossdata->getGlossaryCategory($this->item->catid);
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
						<em><?php echo JText::_( 'COM_SEOGLOSSARY_GLOSSARIES_DESCRIPTION' ); ?></em> -  <?php echo $cat->description;
						}
						}
						?>
					</div>
					<?php }?>
					<div>
						<?php
						$a = new SeoglossaryHelper( );
						$html = SeoglossaryHelper::applyShortcuts($this->item->tdefinition."\r\n".$this->item->tmore, $this->item->tterm);
						
						$prx = new stdClass();
						$prx->text = $html;
						
						JPluginHelper::importPlugin('content');
						$params = array();
						if($this->params->get('apply_content_plugins',1)!=0) {
							JFactory::getApplication()->triggerEvent('onContentPrepare', array('com_seoglossary.glossaries', &$prx, &$params, 0));
						}
						if(@$this->item->icon)
						{
							echo '<img src="'.$this->item->icon.'">';
						}
						$html = $prx->text;
						echo $html;
						?>
					</div>
					<?php

if(($this->params->get('hide_author',1)!=0)&&((!empty($this->item->tname)||!empty($this->item->tmail)))) {
					?>

					<div class="authorblock">
						<em><?php echo JText::_( 'COM_SEOGLOSSARY_AUTHOR' ); ?></em> -  <?php
						if ( !empty( $this->item->tname ) )
						{
							echo $this->item->tname;
						}
						?>
					</div>
					<?php }?>
					<?php
				if(( $this->params->get('show_hits',1) != 0) &&(@$this->item->hits)) {
				?>
				<div class="hits">
					<em><?php echo JText::_( 'JGLOBAL_HITS' ); ?></em> -  <?php
					
						echo $this->item->hits;
					?>
				</div><?php }?>
					<?php if($this->item->tsynonyms) {?>
				
				<?php echo JText::_('COM_SEOGLOSSARY_FORM_LBL_GLOSSARY_TSYNONYMS'); ?>:
				<?php	
						echo $this->item->tsynonyms."<br/>";
					?>
				<?php } ?>
				<div class="tags">
					<?php
					$this->item->tags = new JHelperTags;
					$this->item->tags->getItemTags('com_seoglossary.glossary' , $this->item->id);
					$this->item->tagLayout = new JLayoutFile('joomla.content.tags');
					echo $this->item->tagLayout->render($this->item->tags->itemTags);
					?>
				</div>
				
				</td>
			</tr>
		</tbody>
	</table>
<?php } ?>
    <?php
    $disqus_shortname = trim($this->params->get('disqus_shortname', ''));
    $disqus_terms = $this->params->get('disqus_terms', 0);

    if ($disqus_terms && !empty($disqus_shortname)) : ?>
    <div id="disqus_thread"></div>
    <script type="text/javascript">
        /* * * CONFIGURATION VARIABLES: EDIT BEFORE PASTING INTO YOUR WEBPAGE * * */
        var disqus_shortname = '<?php echo $disqus_shortname; ?>'; // required: replace example with your forum shortname
        var disqus_identifier = '<?php echo "seoglossary-{$this->item->catid}-{$this->item->id}"; ?>';

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