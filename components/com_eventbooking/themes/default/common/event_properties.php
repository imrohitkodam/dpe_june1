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
?>



<?php
if (!$isMultipleDate)
{
	?>
	<div class="event-details-section conatiner-fluid">
		<h2 class="section-heading"><?php echo Text::_('COM_DPE_EVENT_DETAILS');?></h2>

		<div class="card-pair-wrapper">

			<div class="info-card conference-info-card">
				<div class="card-header-icon">
					<i class="fas fa-info-circle"></i>
					<h3 class="card-title"><?php echo Text::_('COM_DPE_EVENT_CONFIRMATION');?></h3>
				</div>
				<?php               $start = new DateTime($item->event_date);
				$end = new DateTime($item->event_end_date);

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

		if ($showLocation && $location)
		{
			?>
			<div class="detail-row location-row">
			<span class="detail-label"><?php echo Text::_('EB_LOCATION'); ?></span>
					
			<span class="detail-value location-value text-primary">

					<?php
					echo EventbookingHelperHtml::loadCommonLayout('elements/location.php', ['location' => $location, 'Itemid' => $Itemid]);
					?>
				</span>
			</div>
			<?php
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
	

