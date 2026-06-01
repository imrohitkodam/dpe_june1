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

/**
 * Layout variables
 * -----------------
 * @var   EventbookingTableEvent $item
 * @var   RADConfig              $config
 * @var   boolean                $showLocation
 * @var   stdClass               $location
 * @var   boolean                $isMultipleDate
 * @var   int                    $Itemid
 */

$bootstrapHelper = EventbookingHelperBootstrap::getInstance();

$layoutType = json_decode($item->custom_fields)->layout_type;

?>



<?php


if($layoutType != 'webinar')
{

	if (!$isMultipleDate)
	{
		?>
		<div class="event-details-section container-fluid">
			<h2 class="section-heading mb-4"><?php echo Text::_('COM_DPE_EVENT_DETAILS');?></h2>

			<!-- Key Dates & Times Section -->
			<div class="key-dates-card mb-4 p-4 rounded-3 shadow-sm bg-light">
				<p class="card-title fw-semibold mb-3 d-flex align-items-center">
					<span class="icon-key1 me-2"><i class="far fa-calendar-alt"></i></span>
					<?php echo Text::_('KEY_DATES_TIMES'); ?>
				</p>
				<div class="row g-3">
					<?php if ($item->event_date != EB_TBC_DATE) : ?>
					<div class="col-md-6">
						<div class="d-flex align-items-center mb-2">
							<span class="me-2 text-muted"><i class="far fa-calendar me-1"></i> <?php echo Text::_('EB_EVENT_DATE'); ?>:</span>
							<span class="fw-medium"><?php echo $start->format('d F Y'); ?></span>
						</div>
					</div>
					<div class="col-md-6">
						<div class="d-flex align-items-center mb-2">
							<span class="me-2 text-muted"><i class="far fa-clock me-1"></i> <?php echo Text::_('EB_START_TIME'); ?>:</span>
							<span class="fw-medium"><?php echo $start->format('g:i A'); ?></span>
						</div>
					</div>
					<?php if ($config->get('show_event_end_date', '1') && (int) $item->event_end_date) : ?>
					<div class="col-md-6">
						<div class="d-flex align-items-center mb-2">
							<span class="me-2 text-muted"><i class="far fa-clock me-1"></i> <?php echo Text::_('EB_END_TIME'); ?>:</span>
							<span class="fw-medium"><?php echo $end->format('g:i A'); ?></span>
						</div>
					</div>
					<?php endif; ?>
					<?php if ($config->get('show_registration_start_date', '1') && (int) $item->registration_start_date) : ?>
					<div class="col-md-6">
						<div class="d-flex align-items-center mb-2">
							<span class="me-2 text-muted"><i class="fas fa-user-plus me-1"></i> <?php echo Text::_('EB_REGISTRATION_STARTS'); ?>:</span>
							<span class="fw-medium"><?php echo EventbookingHelperFormatter::getFormattedDatetime($item->registration_start_date); ?></span>
						</div>
					</div>
					<?php endif; ?>
					<?php if ($config->get('show_cut_off_date', '1') && (int) $item->cut_off_date) : ?>
					<div class="col-md-6">
						<div class="d-flex align-items-center mb-2">
							<span class="me-2 text-muted"><i class="fas fa-user-clock me-1"></i> <?php echo Text::_('EB_CUT_OFF_DATE'); ?>:</span>
							<span class="fw-medium"><?php echo EventbookingHelperFormatter::getFormattedDatetime($item->cut_off_date); ?></span>
						</div>
					</div>
					<?php endif; ?>
					<?php endif; ?>
				</div>
			</div>

			<div class="card-pair-wrapper">

				<div class="info-card conference-info-card">
					<div class="card-header-icon">
						<i class="fas fa-info-circle"></i>
						<h3 class="card-title"><?php echo Text::_('COM_DPE_EVENT_CONFIRMATION');?></h3>
					</div>
					<?php

					$start = new DateTime($item->event_date);
					$end   = new DateTime($item->event_end_date);

					// echo "Event Date: " . $start->format('d F Y') . "<br>";
					// echo "Start Time: " . $start->format('g:i A') . "<br>";
					// echo "End Time: " . ;

					?>
					<div class="detail-row">
						<span class="detail-label"><?php echo Text::_('EB_EVENT_DATE') ?></span>
						<span class="detail-value"> <?php
						if ($item->event_date == EB_TBC_DATE)
						{
							echo Text::_('EB_TBC');
						}
						else
						{
					echo $start->format('d F Y') ;//EventbookingHelperFormatter::getFormattedDatetime($item->event_date);
				}
				?>
			</span>
		</div>



		<div class="detail-row">
			<span class="detail-label">Start Time:</span>
			<span class="detail-value"><?php echo $start->format('g:i A');?></span>
		</div>

		<?php
		if ($config->get('show_event_end_date', '1') && (int) $item->event_end_date)
		{
			?>

			<div class="detail-row">
				<span class="detail-label">End Time:</span>
				<span class="detail-value"><?php echo $end->format('g:i A');?></span>
			</div>

		<?php } ?>

		<div class="detail-separator"></div>

		<?php
		if ($item->price_text || $item->individual_price > 0 || $config->show_price_for_free_event)
		{
			if ($config->show_discounted_price && ($item->individual_price != $item->discounted_price))
			{
				?>
				<div class="detail-row">
					<span class="detail-label">
						<?php echo Text::_('EB_ORIGINAL_PRICE'); ?>
					</span>
					<span class="detail-value price-highlight">
						<?php
						if ($item->individual_price > 0)
						{
							echo EventbookingHelper::formatCurrency($item->individual_price, $config, $item->currency_symbol);
						}
						else
						{
							echo '<span class="eb_free">' . Text::_('EB_FREE') . '</span>';
						}
						?>
					</span>
				</div>
				<div class="detail-row">
					<span class="detail-label">
						<?php echo Text::_('EB_DISCOUNTED_PRICE'); ?>
					</span>
					<span class="detail-value price-highlight">
						<?php
						if ($item->discounted_price > 0)
						{
							echo EventbookingHelper::formatCurrency($item->discounted_price, $config, $item->currency_symbol);

							if ($item->early_bird_discount_amount > 0 && (int) $item->early_bird_discount_date)
							{
								echo ' <em>' . Text::sprintf('EB_UNTIL_DATE', HTMLHelper::_('date', $item->early_bird_discount_date, $config->date_format . ' '. $config->get('event_time_format', 'g:i a'), null)) . '</em>';
							}
						}
						else
						{
							echo '<span class="eb_free">' . Text::_('EB_FREE') . '</span>';
						}
						?>
					</span>
				</div>
				<?php
			}
			else
			{
				?>
				<div class="detail-row">
					<span class="detail-label">
						<?php echo Text::_('EB_INDIVIDUAL_PRICE'); ?>
					</span>
					<span class="detail-value price-highlight">
						<?php
						echo $item->priceDisplay;
						?>
					</span>
				</div>
				<?php
			}
		} 

		
		?>
		<?php
		// if ($config->get('show_event_end_date', '1') && (int) $item->event_end_date)
		// {
		// ?>
			<!-- <tr class="eb-event-property">
		// 		<td class="eb-event-property-label">
		// 			<?php //echo Text::_('EB_EVENT_END_DATE'); ?>
		// 		</td>
		// 		<td class="eb-event-property-value">
		// 			<?php //echo EventbookingHelperFormatter::getFormattedDatetime($item->event_end_date); ?>
		// 		</td>
		// 	</tr> -->
		<?php
		// }

		if ($config->get('show_registration_start_date', '1') && (int) $item->registration_start_date)
		{
			$registrationStartDate = Factory::getDate($item->registration_start_date, Factory::getApplication()->get('offset'));
			$currentDate           = Factory::getDate('now', Factory::getApplication()->get('offset'));

			if ($registrationStartDate > $currentDate)
			{
				?>
				<div class="detail-row">
					<span class="detail-label">
						<?php echo Text::_('EB_REGISTRATION_START_DATE'); ?>
					</span>
					<span class="detail-value">
						<?php echo EventbookingHelperFormatter::getFormattedDatetime($item->registration_start_date); ?>
					</span>
				</div>
				<?php
			}
		}

		if ($config->get('show_cut_off_date', '1') && (int) $item->cut_off_date)
		{
			?>
			<div class="detail-row">
				<span class="detail-label">
					<?php echo Text::_('EB_CUT_OFF_DATE'); ?>
				</span>
				<span class="detail-value">
					<?php echo EventbookingHelperFormatter::getFormattedDatetime($item->cut_off_date); ?>
				</span>
			</div>
			<?php
		}

		if ($config->get('show_cancel_before_date', '0') && (int) $item->cancel_before_date)
		{
			?>
			<div class="detail-row">
				<span class="detail-label">
					<?php echo Text::_('EB_CANCEL_BEFORE_DATE'); ?>
				</span>
				<span class="detail-value">	
					<?php echo EventbookingHelperFormatter::getFormattedDatetime($item->cancel_before_date); ?>
				</span>
			</div>
			<?php
		}

		if ($config->show_capacity == 1 || ($config->show_capacity == 2 && $item->event_capacity))
		{
			?>
			<div class="detail-separator"></div>

			<div class="detail-row">
				<span class="detail-label">
					<?php echo Text::_('EB_CAPACITY'); ?>
				</span>
				<span class="detail-value">	
					<?php
					if ($item->event_capacity)
					{
						echo $item->event_capacity;
					}
					else
					{
						echo Text::_('EB_UNLIMITED');
					}
					?>
				</span>
			</div>
			<?php
		}

		if ($config->show_registered
			&& ((int) $item->total_registrants >= (int) $config->get('show_registered_if_greater_than_or_equal', 0))
			&& $item->registration_type != 3)
		{
			?>
			<div class="detail-row">
				<span class="detail-label">
					<?php echo Text::_('EB_REGISTERED'); ?>
				</span>
				<span class="detail-value">	
					<?php
					echo $item->total_registrants . ' ';

					if ($config->show_list_of_registrants
						&& $item->total_registrants > 0
						&& EventbookingHelper::callOverridableHelperMethod('Acl', 'canViewRegistrantList', [$item->id]))
					{
						?>
						<a href="<?php echo Route::_('index.php?option=com_eventbooking&view=registrantlist&id=' . $item->id . '&tmpl=component'); ?>"
							class="eb-colorbox-register-lists"><span class="view_list"><?php echo Text::_('EB_VIEW_LIST'); ?></span></a>
							<?php
						}
						?>
					</span>
				</div>
				<?php
			}

			if ($config->show_available_place && $item->event_capacity && $item->registration_type != 3)
			{
				?>
				<div class="detail-row">
					<span class="detail-label">
						<?php echo Text::_('EB_AVAILABLE_PLACE'); ?>
					</span>
					<span class="detail-value">	
						<?php echo max($item->event_capacity - $item->total_registrants, 0); ?>
					</span>
				</div>
				<?php
			}

			if ($config->show_waiting_list && $item->registration_type != 3
				&& $item->event_capacity > 0 && ($item->event_capacity <= $item->total_registrants))
			{
				$numberWaitingList = EventbookingHelperRegistration::countNumberWaitingList($item);
			}
			else
			{
				$numberWaitingList = 0;
			}

			if ($numberWaitingList > 0)
			{
				?>
				<div class="detail-row">
					<span class="detail-label">
						<?php echo Text::_('EB_WAITING_LIST'); ?>
					</span>
					<span class="detail-value">	
						<?php
						$numberWaitingList = EventbookingHelperRegistration::countNumberWaitingList($item);
						echo $numberWaitingList . ' ';

						if ($config->show_list_of_waiting_list
							&& $numberWaitingList > 0
							&& EventbookingHelper::callOverridableHelperMethod('Acl', 'canViewRegistrantList', [$item->id]))
						{
							?>
							<a href="<?php echo Route::_('index.php?option=com_eventbooking&view=registrantlist&id=' . $item->id . '&registrant_type=3&tmpl=component'); ?>"
								class="eb-colorbox-register-lists"><span class="view_list"><?php echo Text::_('EB_VIEW_LIST'); ?></span></a>
								<?php
							}
							?>
						</span>
					</div>
					<?php
				}
			}?>

	<?php // Whether we should show price for this event


	if ($item->fixed_group_price > 0)
	{
		?>
		<div class="detail-row">
			<span class="detail-label">
				<?php echo Text::_('EB_FIXED_GROUP_PRICE'); ?>
			</span>
			<span class="detail-value price-highlight">
				<?php echo EventbookingHelper::formatCurrency($item->fixed_group_price, $config, $item->currency_symbol); ?>
			</span>
		</div>
		<?php
	}

	if ($item->late_fee_amount > 0)
	{
		?>
		<div class="detail-row">
			<span class="detail-label">
				<?php echo Text::_('EB_LATE_FEE'); ?>
			</span>
			<span class="detail-value price-highlight">
				<?php
				if ($item->late_fee_type == 1)
				{
						// Late Fee by percent
					echo $item->late_fee_amount . '%';
				}
				else
				{
					echo EventbookingHelper::formatCurrency($item->late_fee_amount, $config, $item->currency_symbol);
				}

				echo '<em> ' . Text::sprintf('EB_FROM_DATE', HTMLHelper::_('date', $item->late_fee_date, $config->date_format . ' H:i', null)) . '</em>';
				?>
			</span>
		</div>
		<?php
	}

	if ($config->show_event_creator)
	{
		?>
		<div class="detail-row">
			<span class="detail-label">
				<?php echo Text::_('EB_CREATED_BY'); ?>
			</span>
			<span class="detail-value">
				<a href="<?php echo Route::_('index.php?option=com_eventbooking&view=search&created_by=' . $item->created_by . '&Itemid=' . $Itemid); ?>"><?php echo $item->creator_name; ?></a>
			</span>
		</div>
		<?php
	}

	if (isset($item->paramData))
	{
		echo EventbookingHelperHtml::loadCommonLayout('common/event_fields.php', ['item' => $item]);
	}



	if ($config->show_event_categories)
	{
		?>
		<tr class="eb-event-property">
			<td class="eb-event-property-label">
				<?php echo Text::_('EB_CATEGORIES'); ?>
			</td>
			<td class="eb-event-property-value">
				<?php echo EventbookingHelperHtml::loadCommonLayout('elements/categories.php', ['categories' => $item->categories, 'Itemid' => $Itemid]); ?>
			</td>
		</tr>
		<?php
	}

	if ($item->attachment && !empty($config->show_attachment_in_frontend))
	{
		?>
		<tr class="eb-event-property">
			<td class="eb-event-property-label">
				<?php echo Text::_('EB_ATTACHMENT'); ?>
			</td>
			<td class="eb-event-property-value">
				<?php echo EventbookingHelperHtml::loadCommonLayout('elements/attachments.php', ['attachments' => explode('|', $item->attachment)]); ?>
			</td>
		</tr>
		<?php
	}
	?>

</div>
<?php }else{

	$start = new DateTime($item->event_date);
	$end   = new DateTime($item->event_end_date);
	?>



	<section class="container-fluid bg-white pt-5 pb-5 event-details-section">
		<div class="main-content-row">
			<h2 class="text-center fw-bolder mb-5 custom-h2-dark">Event Details & Pricing</h2>

			<div class="row justify-content-center g-4 details-cards-row">

				<div class="col-lg-4 col-md-6">
					<div class="details-card rounded-3 h-100 card-blue">
						<p class="card-title fw-semibold mb-3 d-flex align-items-center">
							<span class="icon-key1 me-2"><i class="far fa-calendar-alt"></i></span> <?php echo Text::_('COM_DPE_EVENT_CUSTOM_LAYOUT_DATEANDTIME');?>
						</p>
						<div class="details-list text-start">
							<p class="mb-2 d-flex justify-content-between">
								<span class="fw-semibold text-dark-details"><?php echo Text::_('COM_DPE_EVENT_LBL_LOGDATE');?>:</span> 
								<span class="text-dark-details"><?php echo $start->format('d F Y');?></span>
							</p>
							<p class="mb-2 d-flex justify-content-between">
								<span class="fw-semibold text-dark-details">Start Time:</span> 
								<span class="text-dark-details"><?php echo $start->format('g:i A');?></span>
							</p>
							<p class="mb-0 d-flex justify-content-between">
								<span class="fw-semibold text-dark-details">End Time:</span> 
								<span class="text-dark-details"><?php echo $end->format('g:i A');?></span>
							</p>
						</div>
					</div>
				</div>
				<div class="col-lg-4 col-md-6">
					<div class="details-card rounded-3 h-100 card-green text-center">
						<p class="card-title fw-semibold mb-3 d-flex align-items-center justify-content-center text-dark">
							<span class="icon-key1 me-2"><i class="fas fa-tag"></i></span> 
							<?php echo Text::_('COM_DPE_PRICING_INFORMATION');?>
						</p>
						<p class="mb-1 text-success-dark">Individual Price:</p>
						<h3 class="display-9 fw-bolder text-success-dark mb-3"><?php if ($item->individual_price > 0)
						{
							echo EventbookingHelper::formatCurrency($item->individual_price, $config, $item->currency_symbol);
						}
						else
						{
							echo '<span class="eb_free">' . Text::_('EB_FREE') . '</span>';
						}
						?></h3>
						<!-- <small class="text-success-muted"><?php //echo Text::_('COM_DPE_EVENT_GROUP_REGISTRATION');?></small> -->
					</div>
				</div>


				<?php
				if ($showLocation && $location)
				{
					?>

					<div class="col-lg-4 col-md-6">
						<div class="details-card rounded-3 h-100 card-purple text-center">
							<p class="card-title fw-semibold mb-3 d-flex align-items-center justify-content-center text-dark">
								<span class="icon-key1 me-2"><i class="fas fa-map-marker-alt"></i></span> 
								<?php echo Text::_('EB_LOCATION_CUSTOM_LAYOUT'); ?>
							</p>
							<?php

							$location->layout = $layoutType;
							?>
							<p class="mb-1 text-purple-dark">Platform:</p>
							<h3 class="fw-bolder text-purple-dark mb-3"><a href="<?php echo Route::_('index.php?option=com_eventbooking&view=map&location_id=' . $location->id . '&Itemid=' . $Itemid . '&tmpl=component'); ?>" class="eb-colorbox-map"><?php echo $location->name; ?></a></h3>
							<small class="text-purple-muted">Participate from anywhere, at your convenience.</small>
						</div>
					</div>


					<?php
				}
				?>

			</div>

			<p class="text-center text-secondary-emphasis mt-5 mb-3">
				Don't miss out on this valuable opportunity to enhance your understanding of DfE Digital Standards.
			</p>
			<p class="text-center fw-semibold text-dark register-now-link">
				<a href="<?php echo Route::_('index.php?option=com_eventbooking&task=register.individual_registration&event_id=' . $item->id . '&Itemid=' . $Itemid, false);?>"><?php echo Text::_('COM_DPE_EVENT_SECURE_SPOT');?><span class="arrow-icon ms-2 fs-4 text-dark">➜</span></a>
			</p>
		</div>
	</section>


<?php } ?>


