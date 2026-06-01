<?php
/**
 * @package        	Joomla
 * @subpackage		Event Booking
 * @author  		Tuan Pham Ngoc
 * @copyright    	Copyright (C) 2010 - 2024 Ossolution Team
 * @license        	GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

if ($this->isDirectMenuLink && $this->introText) {
	$description = $this->introText;
} else {
	$description = $this->category ? $this->category->description : $this->introText;
}
?>

<div class="events-hero-banner">
	<div class="dpebanner-content-wrap">
		<h1 class="dpebanner-title"><?php
		
	if ($this->params->get('show_page_heading', 1)) {

		?>
			<?php
			echo $this->escape($this->params->get('page_heading'));

			if ($this->config->get('enable_ics_export')) {
				echo EventbookingHelperHtml::loadCommonLayout('common/ics_export.php');
			}
			?>
		<?php
	}?>
	</h1>
		<p class="dpebanner-tagline">
			<?php echo Text::_('COM_DPE_EVENTS_SUBHEADING');?>
		</p>
		<a href="#" class="dpebtn-view-all">
			View All Events <i class="fas fa-arrow-right"></i>
		</a>
	</div>
</div>





<div id="eb-upcoming-events-page-default"
	class="eb-container<?php if ($this->config->activate_transparent)
		echo ' eb-container-transparent'; ?>">
	<?php

	if ($description) {
		?>
		<div class="eb-description"><?php echo $description; ?></div>
		<?php
	}

	if ($this->config->get('show_search_bar', 0)) {
		echo $this->loadCommonLayout('common/search_filters.php');
	}

	if (count($this->items)) {
		if (EventbookingHelperHtml::isLayoutOverridden('common/events_list.php')) {
			$layoutData = [
				'events' => $this->items,
				'config' => $this->config,
				'Itemid' => $this->Itemid,
				'nullDate' => $this->nullDate,
				'ssl' => 0,
				'viewLevels' => $this->viewLevels,
				'category' => $this->category,
				'bootstrapHelper' => $this->bootstrapHelper,
				'params' => $this->params,
			];

			echo EventbookingHelperHtml::loadCommonLayout('common/events_list.php', $layoutData);
		} else {
			echo $this->loadCommonLayout('common/events_list_layout.php');
		}
	} else {
		?>
		<p class="text-info"><?php echo Text::_('EB_NO_UPCOMING_EVENTS') ?></p>
		<?php
	}

	if ($this->pagination->total > $this->pagination->limit) {
		?>
		<div class="pagination">
			<?php echo $this->pagination->getPagesLinks(); ?>
		</div>
		<?php
	}
	?>



	<form method="post" name="adminForm" id="adminForm"
		action="<?php echo Route::_('index.php?option=com_eventbooking&view=upcomingevents&layout=default&Itemid=' . $this->Itemid); ?>">
		<input type="hidden" name="id" value="0" />
		<input type="hidden" name="task" value="" />
	</form>
</div>



<section class="newsletter-full-width-section">
    <div class="newsletter-content-container">
        <?php echo Text::_('COM_DPE_EVENT_LIST_PAGE_FOOTER'); ?>

        <form class="newsletter-form" id="saveToJmailAlert">
            <input 
                type="email" 
                placeholder="Enter your email" 
                class="newsletter-input" 
                aria-label="Enter your email" id="userEmail"
            >
            <a href="#" type="submit" class="newsletter-button">
                Subscribe <i class="fa-regular fa-paper-plane"></i>
            </a>

        </form>
    </div>
</section>


<footer class="conatiner-fluid site-footer">
    <div class="footer-content-wrapper">

        <div class="footer-column footer-brand-info">
            <h4 class="footer-heading">     <?php echo Text::_('COM_DPE_FOOTER_DPE_NAME')?></h4>
            <p class="brand-description">   
            <?php echo Text::_('COM_DPE_FOOTER_COMPANY_DESCRIPTION')?>
            </p>
        </div>

        <div class="footer-column footer-links">
            <h4 class="footer-heading">Quick Links</h4>
            <ul class="link-list">
                <li><a href="<?php echo Route::_('index.php?option=com_content&view=article&id=85');?>"><?php echo Text::_('EB_TERMS_CONDITIONS')?></a></li>
                <li><a href="<?php echo Route::_('index.php?option=com_content&view=article&id=86');?>"><?php echo Text::_('COM_DPE_EVENT_PRIVACY_NOTICE')?></a></li>
                <li><a href="<?php echo Route::_('index.php?option=com_content&view=article&id=88');?>"><?php echo Text::_('COM_DPE_EVENT_COOKIE')?></a></li>
                <li><a href="<?php echo Route::_('index.php?option=com_content&view=article&id=87');?>"><?php echo Text::_('COM_DPE_EVENT_ACCEPTABLE_USE_POLICY')?></a></li>
            </ul>
        </div>

        <div class="footer-column footer-contact">
            <h4 class="footer-heading">Contact Us</h4>
            <div class="contact-item">
                <i class="fas fa-phone"></i>
                <span><a href="tel:08000862018"><?php echo Text::_('COM_DPE_DPO_PHONE')?>  </a></span>
            </div>
            <div class="contact-item">
                <i class="fas fa-envelope"></i>
                <span><a href="mailto:dpo@dataprotection.education"><?php echo Text::_('COM_DPE_DPO_EMAIL')?></a></span>
            </div>
            <div class="contact-item">
                <i class="fas fa-clock"></i>
                <span><?php echo Text::_('COM_DPE_TIMINGS')?></span>
            </div>
        </div>

    </div>

    <div class="footer-copyright-bar">
        <p class="copyright-text">
        <?php echo Text::_('COM_DPE_FOOTER_COPYRIGHT')?>
        </p>
    </div>
</footer>

<script type="text/javascript">
	document.addEventListener("DOMContentLoaded", function() {
	jQuery('.sp-page-title').css('display','none');
	jQuery('#sp-page-title').css('display','none');
	jQuery('#sp-bottom').css('display','none');
	jQuery('#sp-footer').css('display','none');
})

    jQuery('.newsletter-button').click(function(){

        var userEmail = jQuery('#userEmail').val();

        if(!userEmail)
        {
            alert("<?php echo Text::_('COM_JMAILALERTS_EMAIL_NOT_FOUND_UNSUBSCRIBE_USER'); ?>");
            return false;
        }

        jQuery.ajax({
            url: Joomla.getOptions("system.paths").root + "/index.php?option=com_dpe&task=users.jmailAlertSubFromEventFooter&format=json",
            type: "POST",
            data: {'emailid':userEmail},
            dataType: 'json',
            success:function(response)
            {   
                if(response.data == true)
                {   
                    alert(response.message);
                }
                
            }
        })
    })


</script>