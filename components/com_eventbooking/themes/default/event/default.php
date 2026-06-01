<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use OSSolution\EventBooking\Admin\Event\Events\DisplayEvents;

$item = $this->item;

// This is needed because someone might override EventbookingViewEventHtml class, it is here for backward compatible purpose
EventbookingHelper::callOverridableHelperMethod('Data', 'prepareDisplayData', [[$item], $item->main_category_id, $this->config, $this->Itemid]);

$socialUrl = Uri::getInstance()->toString(['scheme', 'user', 'pass', 'host']) . $item->url;

/* @var EventbookingHelperBootstrap $bootstrapHelper*/
$bootstrapHelper = $this->bootstrapHelper;
$iconPencilClass = $bootstrapHelper->getClassMapping('icon-pencil');
$iconOkClass = $bootstrapHelper->getClassMapping('icon-ok');
$iconRemoveClass = $bootstrapHelper->getClassMapping('icon-remove');
$iconDownloadClass = $bootstrapHelper->getClassMapping('icon-download');
$btnClass = $bootstrapHelper->getClassMapping('btn');
$iconPrint = $bootstrapHelper->getClassMapping('icon-print');
$clearfixClass = $bootstrapHelper->getClassMapping('clearfix');
$return = base64_encode(Uri::getInstance()->toString());

$isMultipleDate = false;

if ($this->config->show_children_events_under_parent_event && $item->event_type == 1) {
	$isMultipleDate = true;
}

$offset = Factory::getApplication()->get('offset');

if ($this->showTaskBar) {
	$layoutData = [
		'item' => $this->item,
		'isMultipleDate' => $isMultipleDate,
		'showInviteFriend' => true,
		'Itemid' => $this->Itemid,
		'return' => $return,
	];

	$registerButtons = EventbookingHelperHtml::loadCommonLayout('common/buttons.php', $layoutData);
}

if (!$this->config->get('show_group_rates', 1)) {
	$this->rowGroupRates = [];
}

$cssClasses = $item->cssClasses ?? [];
$cssClasses[] = 'eb-container';
$cssClasses[] = 'eb-event';

if ($this->print) {
	$cssClasses[] = 'eb-print-page';
}

if ($this->input->getInt('hmvc_call')) {
	$hTag = 'h2';
} else {
	$hTag = 'h1';
}

$webinarLayout = (json_decode($this->item->custom_fields)->layout_type == 'webinar')?1:0;

$shortDescriptionHeading = (json_decode($this->item->custom_fields)->field_shortdescription_heading)?json_decode($this->item->custom_fields)->field_shortdescription_heading:'';
$shortdescriptionSubheading = (json_decode($this->item->custom_fields)->field_shortdescription_subheading)?json_decode($this->item->custom_fields)->field_shortdescription_subheading:'';
$shortdescriptionImage = (json_decode($this->item->custom_fields)->field_shortdescription_image)?json_decode($this->item->custom_fields)->field_shortdescription_image:'';
$descriptionHeading = (json_decode($this->item->custom_fields)->field_description_heading)?json_decode($this->item->custom_fields)->field_description_heading:'';
$descriptionSubHeading = (json_decode($this->item->custom_fields)->field_description_subheading)?json_decode($this->item->custom_fields)->field_description_subheading:'';
$descriptionContent = (json_decode($this->item->custom_fields)->field_description_content)?json_decode($this->item->custom_fields)->field_description_content:'';

$descriptionImage = (json_decode($this->item->custom_fields)->field_description_image)?json_decode($this->item->custom_fields)->field_description_image:'';

?>
<div id="eb-event-page" class="<?php// echo implode(' ', $cssClasses); ?><?php if ($this->config->activate_transparent)
echo ' eb-container-transparent'; ?>">
	<!-- <div class="eb-box-heading <?php echo $clearfixClass; ?>">
		<<?php //echo $hTag; ?> class="eb-page-heading">
			<?php
			//echo $item->title;

			if ($this->config->get('show_print_button', '1') === '1' && !$this->print) {
				$uri = clone Uri::getInstance($this->url);
				$uri->setVar('tmpl', 'component');
				$uri->setVar('print', '1');
				?>
				<div id="pop-print" class="btn hidden-print">
					<a href="<?php echo $uri->toString(); ?> " rel="nofollow" target="_blank">
						<span class="<?php echo $iconPrint; ?>"></span>
					</a>
				</div>
				<?php
			}
			?>
		</<?php echo $hTag; ?>>
	</div> -->



	<div class="container-fluid conference-banner">
		<div class="content">
			<h1><<?php echo $hTag; ?> class="eb-page-heading">
				<?php
				echo $item->title;

				if ($this->config->get('show_print_button', '1') === '1' && !$this->print) {
					$uri = clone Uri::getInstance($this->url);
					$uri->setVar('tmpl', 'component');
					$uri->setVar('print', '1');
					?>
					<div id="pop-print" class="btn hidden-print">
						<a href="<?php echo $uri->toString(); ?> " rel="nofollow" target="_blank">
							<span class="<?php echo $iconPrint; ?>"></span>
						</a>
					</div>
					<?php
				}
				?>
				</<?php echo $hTag; ?>></h1>

				<p><?php echo (json_decode($item->custom_fields)->field_extraheading)?json_decode($item->custom_fields)->field_extraheading:'';?></p>

				<div class="button-group">
					<!-- DPE Hack start -->
					<a href="<?php echo( ($item->price_text == 'Free') && ($item->registration_handle_url != ''))? $item->registration_handle_url:Route::_('index.php?option=com_eventbooking&task=register.individual_registration&event_id=' . $item->id . '&Itemid=' . $this->Itemid, false);?>" class="btn dpemat-btn btn-filled">
						Register (Individual) <i class="fas fa-arrow-right"></i>
					</a>
					<a href="<?php echo (($item->price_text == 'Free') && ($item->registration_handle_url != ''))? $item->registration_handle_url:Route::_('index.php?option=com_eventbooking&task=register.group_registration&event_id=' . $item->id . '&Itemid=' . $this->Itemid, false); ?>" class="btn dpemat-btn btn-outline">
						Register (Group) <i class="fas fa-users"></i>
					</a>
					<!-- DPE Hack end -->
				</div>
			</div>
		</div>



		<div id="eb-event-details" class="eb-description1">
			<?php
		// Facebook, twitter, Gplus share buttons
			if ($this->config->show_fb_like_button || $this->config->show_twitter_button) {
				echo $this->loadTemplate('share', ['socialUrl' => $socialUrl]);
			}

			if ($this->showTaskBar && in_array($this->config->get('register_buttons_position', 0), [1, 2])) {
				?>
				<div class="eb-taskbar eb-register-buttons-top <?php echo $clearfixClass; ?>">
					<ul>
						<?php //echo $registerButtons; ?>
					</ul>
				</div>
				<?php
			}
			?>

			<div class="eb-description-details <?php echo $clearfixClass; ?>">
				<?php

				if($webinarLayout){?>

					<section class="container-fluid custom-gradient-bg pt-5 pb-5">
						<div class="row align-items-start  main-content-row">

							<div class="col-lg-6 col-md-12 mb-4 mb-lg-0 p-md-5 text-lg-start text-center">

								<h1 class="display-5 fw-bolder mb-3 custom-headline">
									<?php echo $shortDescriptionHeading; ?>
								</h1>

								<p class="webinar-details text-secondary-emphasis mb-4">
									<?php echo $shortdescriptionSubheading; ?>
								</p>
								<div class="d-flex flex-column flex-md-row justify-content-center justify-content-md-start align-items-center gap-3 pt-2 custom-link-section">
									<a href="<?php echo Route::_('index.php?option=com_eventbooking&task=register.individual_registration&event_id=' . $item->id . '&Itemid=' . $this->Itemid, false);?>" class="register-link text-decoration-none d-flex align-items-center fw-semibold text-dark p-2 px-4 rounded hover-bg-light border border-primary">
										Register (Individual)
										<i class="fas fa-arrow-right ms-2 text-primary-dark"></i>
									</a>
									<a href="<?php echo Route::_('index.php?option=com_eventbooking&task=register.group_registration&event_id=' . $item->id . '&Itemid=' . $this->Itemid, false); ?>" class="register-link text-decoration-none d-flex align-items-center fw-semibold text-dark p-2 px-4 rounded hover-bg-light border border-primary">
										Register (Group)
										<i class="fas fa-users ms-2 text-primary-dark"></i>
									</a>
								</div>
							</div>
							<div class="col-lg-6 col-md-12 p-4 p-md-5 d-flex justify-content-center">
								<div class="graphic-outer-border1 p-0" style="width: 100%;"> 
							<div class="webinar-promo-box text-center p-0 rounded-3 shadow-sm position-relative" style="width: 100%; height: 100%;">
								<img src="<?php echo $shortdescriptionImage; ?>" alt="<?php echo $shortdescriptionImage ?: $item->title; ?>"
								style="width: 100%; height: 100%; object-fit: contain; display: block;" />

									</div>
								</div>
							</div>

						</div>
					</section>
					<section class="container-fluid bg-white pt-5 pb-5 discover-section">
						<div class="row align-items-start  main-content-row">

							<div class="col-lg-6 col-md-12 mb-4 mb-lg-0 p-md-5 text-lg-start text-center">


								<h2 class="fw-bolder mb-4 custom-h2"><?php echo $descriptionHeading; ?></h2>
								<!-- Heading -->

								<p class="webinar-details text-secondary-emphasis mb-4 text-lg-start text-center">
									<?php echo $descriptionSubHeading;?>
								</p>
								<!-- Subheading -->
								<?php echo $descriptionContent; ?>
								<!-- content-->
							</div>
							<div class="col-lg-6 col-md-12 p-md-5 d-flex justify-content-center my-4">
								<div class="graphic-outer-border p-0" style="width: 100%;"> 
									<div class="webinar-promo-box text-center rounded-3 shadow-sm position-relative">
										<img src="<?php echo $descriptionImage; ?>" alt="<?php echo $descriptionImage ?: $item->title; ?>" style="width: 100%; height: 100%; object-fit: contain; display: block;" />

									</div>
								</div>
							</div>

						</div>
					</section>

					<?php 
				}else
				{
					echo $item->description;?>
				

				
				<div class="mat-image-event"><?php
				if ($this->config->get('show_image_in_event_detail', 1) && $this->config->display_large_image && !empty($item->image_url)) {
					?>
					<img src="<?php echo $item->image_url; ?>" alt="<?php echo $item->image_alt ?: $item->title; ?>"
					class="eb-event-large-image img-polaroid" />
					<?php
				} elseif ($this->config->get('show_image_in_event_detail', 1) && !empty($item->thumb_url)) {
					EventbookingHelperJquery::colorbox('a.eb-modal');
					?>
					<a href="<?php echo $item->image_url; ?>" class="eb-modal"><img src="<?php echo $item->thumb_url; ?>"
						class="eb-thumb-left" alt="<?php echo $item->image_alt ?: $item->title; ?>" /></a>
						<?php
					}
					?>
				</div>
			<?php }
				?>
			</div>

			<div id="eb-event-info" class="<?php echo $bootstrapHelper->getClassMapping('row-fluid clearfix'); ?>">
				<?php
				if (!empty($this->items)) {
					if (EventbookingHelperHtml::isLayoutOverridden('common/events_children.php')) {
						echo EventbookingHelperHtml::loadCommonLayout('common/events_children.php', ['items' => $this->items, 'config' => $this->config, 'Itemid' => $this->Itemid, 'nullDate' => $this->nullDate, 'ssl' => 0, 'viewLevels' => $this->viewLevels, 'categoryId' => $this->item->category_id, 'bootstrapHelper' => $this->bootstrapHelper]);
					} else {
					// Prepare additional data needed by events_table_layout
						$this->categoryId = $this->item->category_id;
						$this->category = EventbookingHelperDatabase::getCategory($this->item->category_id);

						echo $this->loadCommonLayout('common/events_table_layout.php', ['displayChildrenEvents' => true]);
					}
				} else {
					$leftCssClass = 'span8';

					if (empty($this->rowGroupRates)) {
						$leftCssClass = 'span12';
					}
					?>
					<div id="eb-event-info-left" class="<?php //echo $bootstrapHelper->getClassMapping($leftCssClass); ?>">
						<h3 id="eb-event-properties-heading">
							<?php //echo Text::_('EB_EVENT_PROPERTIES'); ?>
						</h3>
						<?php
						$layoutData = [
							'item' => $this->item,
							'config' => $this->config,
							'location' => $item->location,
							'showLocation' => true,
							'isMultipleDate' => false,
							'nullDate' => $this->nullDate,
							'Itemid' => $this->Itemid,
						];

					if ($webinarLayout){
						
						$layoutData['layoutType'] = 'custom';
						echo EventbookingHelperHtml::loadCommonLayout('common/event_properties_customLayout.php', $layoutData);
					}else
					{
 						echo EventbookingHelperHtml::loadCommonLayout('common/event_properties.php', $layoutData);
					}
						?>


						<?php
						if (count($this->rowGroupRates) && ($webinarLayout != 1)) {
							echo $this->loadTemplate('group_rates');
						}?>
					</div></div>
				</div>




				<?php
			}
			?>
		</div>


		<div class="<?php echo $clearfixClass; ?>"></div>
		<?php

		if ($this->config->show_location_info_in_event_details && $item->location && ($item->location->image || EventbookingHelper::isValidMessage($item->location->description))) {

			$item->location->extraLocation = (json_decode($item->custom_fields)->field_extralocation)?json_decode($item->custom_fields)->field_extralocation:'';
			echo $this->loadTemplate('location', ['location' => $item->location]);
		}

		foreach ($this->horizontalPlugins as $plugin) {
			?>
			<h3 class="eb-horizontal-plugin-header<?php if (!empty($plugin['name']))
			echo ' eb-plugin-' . $plugin['name']; ?>">
			<?php //echo $plugin['title']; ?>
		</h3>
		<?php
		echo $plugin['form'];
	}

	if ($this->config->display_ticket_types && !empty($item->ticketTypes)) {
		echo EventbookingHelperHtml::loadCommonLayout('common/tickettypes.php', ['ticketTypes' => $item->ticketTypes, 'config' => $this->config, 'event' => $item]);
		?>
		<div class="<?php echo $clearfixClass; ?>"></div>
		<?php
	}

	echo EventbookingHelperHtml::loadCommonLayout('common/event_message.php', ['config' => $this->config, 'event' => $item]);

	if (
		$this->showTaskBar && in_array($this->config->get('register_buttons_position', 0), [0, 2])
	) {
		?>
		<div class="eb-taskbar eb-register-buttons-bottom <?php echo $clearfixClass; ?>">
			<ul>
				<?php
					//echo $registerButtons;

				if ($this->config->get('show_back_button', 1)) {
					?>
					<li>
						<a class="eb-button-button-link <?php echo $bootstrapHelper->getClassMapping('btn'); ?>"
							href="javascript: window.history.go(-1);"><?php echo Text::_('EB_BACK'); ?></a>
						</li>
						<?php
					}
					?>
				</ul>
			</div>
			<?php
		}

		if (count($this->plugins)) {
			echo $this->loadTemplate('plugins');
		}

		if ($this->config->show_social_bookmark && !$this->print) {
			echo $this->loadTemplate('social_buttons', ['socialUrl' => $socialUrl]);// 
		}
		?>
	</div>
</div>

</div>
<?php if(json_decode($item->custom_fields)->field_extrafooter)
{
	echo json_decode($item->custom_fields)->field_extrafooter;
}?>



<footer class="conatiner-fluid site-footer">
	<div class="footer-content-wrapper">

		<div class="footer-column footer-brand-info">
			<h4 class="footer-heading">		<?php echo Text::_('COM_DPE_FOOTER_DPE_NAME')?></h4>
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

<?php

Factory::getApplication()->getDocument()->addScriptDeclaration('
	function cancelRegistration(registrantId)
	{
		var form = document.adminForm ;

		if (confirm("' . Text::_('EB_CANCEL_REGISTRATION_CONFIRM') . '"))
		{
			form.task.value = "registrant.cancel" ;
			form.id.value = registrantId ;
			form.submit() ;
		}
	}
	');
	?>
	<form name="adminForm" id="adminForm"
	action="<?php echo Route::_('index.php?option=com_eventbooking&Itemid=' . $this->Itemid); ?>" method="post">
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="id" value="" />
	<?php echo HTMLHelper::_('form.token'); ?>
</form>


<script type="text/javascript">
	<?php
	if ($this->print) {
		?>
		window.print();
		<?php
	}
	?>


	document.addEventListener("DOMContentLoaded", function() {
		jQuery('.sp-page-title').css('display','none');
		jQuery('#sp-page-title').css('display','none');
		jQuery('#sp-bottom').css('display','none');
		jQuery('#sp-footer').css('display','none');


		const source = document.querySelector(".mat-image-event");
		const target = document.querySelector(".mat-image");

		if (source && target) {
			target.appendChild(source);
		}
	});

</script>
<?php
$eventObj = new DisplayEvents(
	'onDisplayEvents',
	['items' => [$item]]
);

Factory::getApplication()->triggerEvent('onDisplayEvents', $eventObj);


