<?php
/**
 * @package        	Joomla
 * @subpackage		Event Booking
 * @author  		Tuan Pham Ngoc
 * @copyright    	Copyright (C) 2010 - 2024 Ossolution Team
 * @license        	GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die ;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Uri\Uri;
use OSSolution\EventBooking\Admin\Event\Events\DisplayEvents;
use Joomla\CMS\Router\Route;

$return                  = base64_encode(Uri::getInstance()->toString());
$eventPropertiesPosition = (int) $this->params->get('event_properties_position', 0);
$config            = EventbookingHelper::getConfig();

if (!$this->config->get('show_register_buttons', 1))
{
	$hideRegisterButtons = true;
}
else
{
	$hideRegisterButtons = false;
}

if ($this->params->get('image_lazy_loading', '0'))
{
	$imgLoadingAttr = ' loading="lazy"';
}
else
{
	$imgLoadingAttr = '';
}

$lazyLoadingStartIndex = $this->params->get('image_lazy_loading_start_index', 0);

/* @var EventbookingHelperBootstrap $bootstrapHelper */
$bootstrapHelper         = $this->bootstrapHelper;
$activeCategoryId        = $this->categoryId;

if ($eventPropertiesPosition === 0)
{
	$eventDescriptionClass = $bootstrapHelper->getClassMapping('span7');
	$eventPropertiesClass  = $bootstrapHelper->getClassMapping('span5');
}
else
{
	$eventDescriptionClass = $bootstrapHelper->getClassMapping('clearfix');
	$eventPropertiesClass  = $bootstrapHelper->getClassMapping('clearfix');
}

$rowFluid   = $bootstrapHelper->getClassMapping('row-fluid');
$btn        = $bootstrapHelper->getClassMapping('btn');
$clearfix   = $bootstrapHelper->getClassMapping('clearfix');
?>
<div id="eb-events">
	<?php
	for ($i = 0 , $n = count($this->items) ;  $i < $n ; $i++)
	{
		$type = ($i % 2 == 0) ? '1' : '0';

		$event = $this->items[$i];
		$event_date = $event->event_date;
$event_end_date = $event->event_end_date; // example different date

$start = new DateTime($event_date);
$end   = new DateTime($event_end_date);

// Compare if start and end are on the same date


$layoutData = [
	'item'                => $event,
	'isMultipleDate'      => $event->is_multiple_date,
	'showInviteFriend'    => false,
	'Itemid'              => $this->Itemid,
	'return'              => $return,
	'hideRegisterButtons' => $hideRegisterButtons,
];

$registerButtons = EventbookingHelperHtml::loadCommonLayout('common/buttons.php', $layoutData);

$layoutData = [
	'item'           => $event,
	'config'         => $this->config,
	'location'       => $event->location,
	'showLocation'   => $this->config->show_location_in_category_view,
	'isMultipleDate' => $event->is_multiple_date,
	'nullDate'       => $this->nullDate,
	'Itemid'         => $this->Itemid,
];

$eventProperties = EventbookingHelperHtml::loadCommonLayout('common/event_properties.php', $layoutData);

$cssClasses = $event->cssClasses ?? [];

$cssClasses[] = 'eb-event';
$cssClasses[] = 'clearfix';
?>

<div class="<?php echo ($type == '0')?'eventpage-container':'eventpage-container1'?>">
	<div class="<?php echo ($type == '0')?'event-card-container':'event-card-container1'?>">
		<div class="<?php echo ($type == '0')?'event-card-banner':'event-card-banner1'?>">
			<!-- <span class="<?php echo ($type == '0')?'banner-title-overlay':'banner-title-overlay1'?>">DPE MAT Conference Banner</span> -->
			<span>
				<img 
				src="<?php echo $event->image; ?>" 
				class="" 
				alt="<?php echo htmlspecialchars($event->image_alt ?: $event->title, ENT_QUOTES, 'UTF-8'); ?>">
			</span>

			<!-- <span class="<?php echo ($type == '0')?'banner-footer-text':'banner-footer-text1'?>">Secure your MAT's future</span> -->
		</div>

		<div class="<?php echo ($type == '0')?'event-card-content':'event-card-content1'?>">
			<div class="<?php echo ($type == '0')?'content-header':'content-header1'?>">
				<span class="tag-conference1"><?php echo ($event->location_name == Text::_('COM_DPE_EVENT_ONLINE_WEBINAR'))?Text::_('COM_DPE_EVENT_WEBINAR'): Text::_('COM_DPE_EVENT_CONFERENCE'); ?></span></span>
				<?php if($event->featured == 1){?>
					<span class="tag-featured1"> Featured</span>
				<?php }?>
			</div>

			<h2 class="<?php echo ($type == '0')?'event-title':'event-title1'?>">
				<?php
				if ($this->config->hide_detail_button !== '1')
				{
					?>
					<a href="<?php echo $event->url; ?>" title="<?php echo $event->title; ?>" class="eb-event-title-link">
						<?php echo $event->title; ?>
					</a>
					<?php
				}
				else
				{
					?>
					<?php echo $event->title; ?>
					<?php
				}
			?>            </h2>
			<p class="event-description1">
				<?php echo json_decode($event->custom_fields)->field_subheadingforlist;?>
			</p>

			<div class="event-details-group1">
				<div class="detail-item1">

					<?php if ($start->format('Y-m-d') === $end->format('Y-m-d')) { ?>
						<i class="fa-regular fa-calendar"></i> <span><?php echo $start->format('j F Y');?></span>
						<i class="fa-regular fa-clock"></i><span><?php echo $start->format('g:i A') . ' - ' . $end->format('g:i A');?></span>
						<?php
					} else {?>

						<i class="fa-regular fa-calendar"></i> <span><?php     echo $start->format('j F Y g:i A') . ' - ' . $end->format('j F Y g:i A');
					?></span>
				<?php }?>
				
				<!-- <span class="detail-separator">|</span> -->

			</div>
			<div class="detail-item1">
				<!-- <?php //print_r($event->location);?> -->
				<i class="fas fa-map-marker-alt"></i> <span><?php echo $event->location->name;?></span>
			</div>
		</div>
		<?php if($event->individual_price != '0.00'){?>

			<div class="event-price-group">
				<span class="price-label"><?php echo Text::_('EB_INDIVIDUAL_PRICE');?>:</span>
				<span class="price-value"><?php echo $this->config->currency_symbol.$event->individual_price;?></span>
				<?php if ($event->early_bird_discount_type){

					$earlyBirdDate = date('M d, Y', strtotime($event->early_bird_discount_date));

					?>
					<span class="price-early-bird"> <?php echo Text::sprintf('COM_DPE_EVENT_EARLY_BIRD', $earlyBirdDate); ?>
				</span>

			<?php }?>
		</div>
	<?php } ?>



	<div class="event-actions1">

		<?php if($event->price_text == 'Free'){?>
			<a href="<?php echo Route::_('index.php?option=com_eventbooking&task=register.individual_registration&event_id=' . $event->id . '&Itemid=' . $this->Itemid, false); ?>"
				class="btn-action1 btn-primary1">
				<i class="fas fa-user-plus"></i>
				<?php echo Text::_('COM_DPE_EVENT_REGISTER_NOW_BTN'); ?>
			</a>
		<?php }else{?>
			<a href="<?php echo Route::_('index.php?option=com_eventbooking&task=register.individual_registration&event_id=' . $event->id . '&Itemid=' . $this->Itemid, false); ?>"
				class="btn-action1 btn-primary1">
				<i class="fas fa-user-plus"></i>
				<?php echo Text::_('COM_DPE_EVENT_REGISTER_NOW_BTN'); ?>
			</a>
			<a href="<?php echo Route::_('index.php?option=com_eventbooking&task=register.group_registration&event_id=' . $event->id . '&Itemid=' . $this->Itemid, false); ?>" class="btn-action1 btn-primary1">
				<i class="fas fa-users"></i><?php echo Text::_('COM_DPE_EVENT_REGISTER_NOW_BTN_GROUP'); ?>

			</a>
		<?php }?>


		<?php if ($config->show_save_to_personal_calendar && $item->event_date != EB_TBC_DATE)
		{ 		
			?>
			<?php echo EventbookingHelperHtml::loadCommonLayout('common/save_calendar.php', ['item' => $event, 'Itemid' => $this->Itemid]); ?>
			<?php
		}

		if ($this->config->hide_detail_button !== '1' || $event->is_multiple_date)
			{?>
				<a class="btn-details1 <?php echo $btn; ?>" href="<?php echo $event->url; ?>">
					<i class="fas fa-info-circle"></i>
					<?php echo $event->is_multiple_date ? Text::_('EB_CHOOSE_DATE_LOCATION') : Text::_('EB_DETAILS'); ?>
				</a>
			<?php } ?>

		</div>


	</div>
</div>
</div>




<div class="<?php //echo implode(' ', $cssClasses); ?>">
	<!-- <div class="eb-box-heading <?php echo $clearfix; ?>">
		<h2 class="eb-event-title pull-left">

		</h2>
	</div> -->
	<div class="<?php //echo $clearfix; ?>">
		<?php
		if (in_array($this->config->get('register_buttons_position', 0), [1, 2]))
		{
			?>
			<div class="eb-taskbar eb-register-buttons-top <?php echo $clearfix; ?>">
				<ul>
					<?php
				//	echo $registerButtons;

					// if ($this->config->hide_detail_button !== '1' || $event->is_multiple_date)
					// {
					// 	?>
					 	<!-- <li>
					// 		<a class="<?php //echo $btn; ?>" href="<?php //echo $event->url; ?>">
					// 			<?php //echo $event->is_multiple_date ? Text::_('EB_CHOOSE_DATE_LOCATION') : Text::_('EB_DETAILS');?>
					// 		</a>
					// 	</li> -->
					 	<?php
					// }
					?>
				</ul>
			</div>
			<?php
		}

		if ($eventPropertiesPosition === 0)
		{
			?>
			<div class="<?php echo $rowFluid; ?>">
				<?php
			}

			if ($eventPropertiesPosition == 1)
			{
				?>
				<div class="eb-event-properties-table <?php echo $eventPropertiesClass; ?>">
					<?php //echo $eventProperties; ?>
				</div>
				<?php
			}
			?>
			<div class="eb-description-details <?php echo $eventDescriptionClass; ?>">
				<?php
				if (!empty($event->thumb_url))
				{
					?>
					<!-- <a href="<?php echo $event->url; ?>"><img<?php if ($imgLoadingAttr && $i >= $lazyLoadingStartIndex) echo $imgLoadingAttr; ?>  src="<?php echo $event->thumb_url; ?>" class="eb-thumb-left" alt="<?php echo $event->image_alt ?: $event->title; ?>"/></a> -->
					<?php
				}

				// echo $event->short_description;
				?>
			</div>
			<?php
			if (in_array($eventPropertiesPosition, [0, 2]))
			{
				?>
				<div class="eb-event-properties-table <?php echo $eventPropertiesClass; ?>">
					<?php //echo $eventProperties; ?>
				</div>
				<?php
			}

			if ($eventPropertiesPosition == 0)
			{
				?>
			</div>
			<?php
		}

		if ($this->config->display_ticket_types && !empty($event->ticketTypes))
		{
			echo EventbookingHelperHtml::loadCommonLayout('common/tickettypes.php', ['ticketTypes' => $event->ticketTypes, 'config' => $this->config, 'event' => $event]);
			?>
			<div class="<?php echo $clearfix; ?>"></div>
			<?php
		}

				// Event message to tell user that they already registered, need to login to register or don't have permission to register...
		echo EventbookingHelperHtml::loadCommonLayout('common/event_message.php', ['config' => $this->config, 'event' => $event]);

		if (in_array($this->config->get('register_buttons_position', 0), [0, 2]))
		{
			?>
			<div class="eb-taskbar <?php echo $clearfix; ?>">
				<ul>
					<?php
					// echo $registerButtons;

					if ($this->config->hide_detail_button !== '1' || $event->is_multiple_date)
					{
						?>
						<!-- <li>
							<a class="<?php //echo $btn; ?>" href="<?php echo $event->url; ?>">
								<?php //echo $event->is_multiple_date ? Text::_('EB_CHOOSE_DATE_LOCATION') : Text::_('EB_DETAILS');?>
							</a>
						</li> -->
						<?php
					}
					?>
				</ul>
			</div>
			<?php
		}
		?>
	</div>
</div>
<?php
}
?>
</div>
<?php

// Add Google Structured Data
PluginHelper::importPlugin('eventbooking');

$eventObj = new DisplayEvents(
	'onDisplayEvents',
	['items' => $this->items]
);

Factory::getApplication()->triggerEvent('onDisplayEvents', $eventObj);


?>
