<?php
/**    
 * SEO Glossary Panel view Default layout
 *
 * We developed this code with our hearts and passion.
 * We hope you found it useful, easy to understand and change.
 * Otherwise, please feel free to contact us at contact@joomunited.com
 *
 * @package 	SEO Glossary
 * @copyright 	Copyright (C) 2012 JoomUnited (http://www.joomunited.com). All rights reserved.
 * @license 	GNU General Public License version 2 or later; http://www.gnu.org/licenses/gpl-2.0.html
 */

// No direct access
defined('_JEXEC') or die;
 if (version_compare(JVERSION, '3.10.0', 'lt')) {
JHTML::_('behavior.modal');
 }
$document = JFactory::getDocument();
$document->addStyleSheet(JURI::root() . '/components/com_seoglossary/assets/css/materialize.css');

?>
<?php if(!empty( $this->sidebar)): ?>
	<div id="j-sidebar-container" class="span2">
		<?php echo $this->sidebar; ?>
	</div>
	<div id="j-main-container" class="span10">
<?php else : ?>
	<div id="j-main-container">
		<?php endif;?>
<div class="icons" id="cpanel">
    	<div class="seoicon">
        <a href="index.php?option=com_seoglossary&view=catglossaries">
<img alt="Objects" src="<?php echo JURI::base();?>components/com_seoglossary/assets/images/folder.png" /><br />
    <span><?php echo JText::_( 'COM_SEOGLOSSARY_PANEL_MANAGE_GLOSSARIES' ); ?></span>
	</a>    
        </div>
    	
        <div class="seoicon">
        <a href="index.php?option=com_seoglossary&view=glossaries">
<img alt="Objects" src="<?php echo JURI::base();?>components/com_seoglossary/assets/images/page_full.png" /><br />
    <span><?php echo JText::_( 'COM_SEOGLOSSARY_PANEL_MANAGE_ENTRIES' ); ?></span>
	</a>    
        </div>
<div class="seoicon">
        <a href="index.php?option=com_seoglossary&view=import">
<img alt="Objects" src="<?php echo JURI::base();?>components/com_seoglossary/assets/images/import.png" /><br />
    <span><?php echo JText::_( 'COM_SEOGLOSSARY_IMPORT' ); ?></span>
	</a>    
        </div>
    
    <div class="seoicon">
        <a  href="<?php echo JURI::base();?>index.php?option=com_config&view=component&component=com_seoglossary">
            <img alt="Objects" src="<?php echo JURI::base();?>components/com_seoglossary/assets/images/settings.png">
            <span><?php echo JText::_( 'COM_SEOGLOSSARY_PANEL_CONFIGURATION' ); ?></span>
        </a>
    </div>
    
        <div class="seoicon">
        <a href="http://www.joomunited.com/products/seo-glossary" target="_blank">
<img alt="Objects" src="<?php echo JURI::base();?>components/com_seoglossary/assets/images/rss.png" /><br />
    <span><?php echo JText::_( 'COM_SEOGLOSSARY_PANEL_ABOUT' ); ?></span>
	</a>    
        </div>
	
<div class="clear"></div>
</div>
</div>