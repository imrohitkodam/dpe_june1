<?php
/**
 * @version     1.1
 * @package     mod_showcasebuilder
 * @copyright   Copyright (C) 2013. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      JoomlaForce Team <support@joomlaforce.com> - http://www.joomlaforce.com
 */


// no direct access
defined('_JEXEC') or die('Restricted access'); 
use Joomla\CMS\Language\Text;
?>

<link rel="stylesheet" href="<?php echo JURI::base() ?>modules/mod_showcasebuilder/assets/css/prettyPhoto.css" type="text/css" media="screen" title="prettyPhoto main stylesheet" charset="utf-8" />

<div class="content_slider_wrapper<?php echo ($hv_switch != 0 ? "_vertical": ""); ?>" id="slider<?php echo $module->id;?>">
<?php $i=0; foreach ($list as $item) : ?>

	<div class="circle_slider_text_wrapper" id="sw<?php echo $i?>" style="display: none;">
		<div class="content_slider_text_block_wrap">
        
        <?php if($show_title_article==1 || $show_title_article==3){ ?>
			<h3><?php echo $item->title ?></h3><br /><br />
         <?php } ?>
         <?php if($show_author_article==1 || $show_author_article==3){ ?>
				<b><?php echo Text::_('MOD_SHOWCASEBUILDER_AUTHOR') ?>:</b> <?php echo $item->displayAuthorName ?><br />
         <?php } ?>
          <?php if($show_category_article==1 || $show_category_article==3){ ?>
				<span><b><?php echo Text::_('MOD_SHOWCASEBUILDER_CATEGORY') ?>:</b> <?php echo $item->category_title ?> <br /><br />
         <?php } ?>
           <?php if($show_hits_article==1 || $show_hits_article==3){ ?>
				<b><?php echo Text::_('MOD_SHOWCASEBUILDER_HITS') ?>:</b> <?php echo $item->displayHits ?><br />
         <?php } ?>
          <?php if($show_description_article==1 || $show_description_article==3){ ?>
				  <b><?php echo Text::_('MOD_SHOWCASEBUILDER_DESCRIPTION') ?>:</b> <?php echo $item->introtext ?><br />
         <?php } ?>
          <?php if($show_article_btn_more_info==1 || $show_article_btn_more_info==3){ ?>
				  </span><br /><br /><br />
					<a href="<?php echo $item->link ?>" class="button_regular"><?php echo Text::_('MOD_SHOWCASEBUILDER_MOREINFO') ?></a>
         <?php } ?>
         
		</div>
		<div class="clear"></div>	
	</div>
<?php $i++; endforeach; ?>