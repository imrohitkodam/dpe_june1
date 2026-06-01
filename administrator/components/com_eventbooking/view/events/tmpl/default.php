<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die ;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

$this->includeTemplate('script');

$rootUri                      = Uri::root(true);
$isMultilingual               = Multilanguage::isEnabled();
$languages                    = LanguageHelper::getLanguages('lang_code');
$colspan                      = 12;
$additionalDatesPluginEnabled = PluginHelper::isEnabled('eventbooking', 'dates');
?>
<form action="<?php echo $this->getFormAction(); ?>" method="post" name="adminForm" id="adminForm">
	<div id="j-main-container" class="eb-joomla4-container">
		<?php echo $this->loadTemplate('filter'); ?>
		<div class="clearfix"></div>
		<table class="table itemList table-striped" id="eventList">
			<thead>
			<tr>
				<th width="1%" class="nowrap center hidden-phone">
					<?php echo $this->searchToolsSortHeader(); ?>
				</th>
				<th width="20">
					<input type="checkbox" name="toggle" value="" onclick="Joomla.checkAll(this)" />
				</th>
				<th class="title" style="text-align: left;">
					<?php echo $this->searchToolsSort('EB_TITLE',  'tbl.title'); ?>
				</th>
				<th class="title" width="13%" style="text-align: left;">
					<?php echo Text::_('EB_CATEGORY'); ?>
				</th>
				<th class="center title" width="13%">
					<?php echo $this->searchToolsSort('EB_EVENT_DATE',  'tbl.event_date'); ?>
				</th>
				<th class="title center" width="7%">
					<?php echo $this->searchToolsSort('EB_PRICE',  'tbl.individual_price'); ?>
				</th>
				<th class="title center" width="7%">
					<?php echo $this->searchToolsSort('EB_CAPACITY',  'tbl.event_capacity'); ?>
				</th>
				<th class="title" width="7%">
					<?php echo $this->searchToolsSort('EB_NUMBER_REGISTRANTS',  'total_registrants'); ?>
				</th>
				<?php
				if ($this->config->activate_recurring_event || $additionalDatesPluginEnabled)
				{
					$colspan++;
				?>
					<th width="8%" nowrap="nowrap">
						<?php echo $this->searchToolsSort('EB_EVENT_TYPE',  'tbl.event_type'); ?>
					</th>
				<?php
				}
				?>
				<th width="5%" class="nowrap hidden-phone">
					<?php echo $this->searchToolsSort('JGRID_HEADING_ACCESS',  'tbl.access'); ?>
				</th>
				<?php
					if ($this->config->show_event_creator_on_events_management)
					{
						$colspan++;
					?>
						<th><?php echo $this->searchToolsSort('EB_CREATED_BY',  'u.username'); ?></th>
					<?php
					}
				?>
				<th width="5%" nowrap="nowrap" class="center">
					<?php echo $this->searchToolsSort('EB_PUBLISHED',  'tbl.published'); ?>
				</th>
				<th width="2%" nowrap="nowrap" class="center">
					<?php echo $this->searchToolsSort('JGLOBAL_HITS',  'tbl.hits'); ?>
				</th>
				<?php
				if ($isMultilingual)
				{
					$colspan++;
				?>
					<th class="center">
						<?php echo $this->searchToolsSort('EB_LANGUAGE',  'tbl.language'); ?>
					</th>
                <?php
				}
				?>
				<th width="1%" nowrap="nowrap" class="center">
					<?php echo $this->searchToolsSort('EB_ID',  'tbl.id'); ?>
				</th>
			</tr>
			</thead>
			<tfoot>
			<tr>
				<td colspan="<?php echo $colspan ; ?>">
					<?php echo $this->pagination->getPaginationLinks(); ?>
				</td>
			</tr>
			</tfoot>
			<tbody <?php if ($this->saveOrder) :?> class="js-draggable" data-url="<?php echo $this->saveOrderingUrl; ?>" data-direction="<?php echo strtolower($this->state->filter_order_Dir); ?>" <?php endif; ?>>
			<?php
			$k = 0;

			for ($i = 0, $n = count($this->items); $i < $n; $i++)
			{
				$row       = $this->items[$i];
				$link      = $this->getEditItemLink($row);
				$checked   = HTMLHelper::_('grid.id', $i, $row->id);
				$published = HTMLHelper::_('jgrid.published', $row->published, $i);

				$classes = ['row' . $k];

				if ($row->event_type == 0)
				{
					$classes[] = 'eb-standard-event';
				}
				elseif ($row->event_type == 1)
				{
					$classes[] = 'eb-parent-event';
				}
				else
				{
					$classes[] = 'eb-child-event';
				}
				?>
				<tr class="<?php echo implode(' ', $classes); ?>">
					<td class="order nowrap center hidden-phone">
						<?php $this->reOrderCell($row); ?>
					</td>
					<td>
						<?php echo $checked; ?>
					</td>
					<td>
						<a href="<?php echo $link; ?>">
							<?php echo $row->title ; ?>
						</a>
					</td>
					<td>
						<?php echo $row->category_name ; ?>
					</td>
					<td class="center">
						<?php echo HTMLHelper::_('date', $row->event_date, $this->config->date_format . ' H:i', null); ?>
					</td>
					<td class="center">
						<?php
							if ($row->individual_price > 0)
							{
								echo EventbookingHelper::formatAmount($row->individual_price, $this->config);
							}
							else
							{
								echo Text::_('EB_FREE');
							}
						?>
					</td>
					<td class="center">
						<?php echo $row->event_capacity; ?>
					</td>
					<td class="center">
						<a href="<?php echo Route::_('index.php?option=com_eventbooking&view=registrants&filter_event_id=' . $row->id);?>"> <?php echo (int) $row->total_registrants; ?></a>
					</td>
					<?php
					if ($this->config->activate_recurring_event || $additionalDatesPluginEnabled)
					{
					?>
						<td align="left">
							<?php
							if ($row->event_type == 0)
							{
								echo Text::_('EB_STANDARD_EVENT');
							}
							elseif($row->event_type == 1)
							{
								echo Text::_('EB_PARENT_EVENT');
							}
							else
							{
								echo Text::_('EB_CHILD_EVENT');
							}
							?>
						</td>
					<?php
					}
					?>
					<td>
						<?php echo $row->access_level; ?>
					</td>
						<?php
						if ($this->config->show_event_creator_on_events_management)
						{
						?>
							<td>
							<?php
								if ($row->username)
								{
								?>
									<a href="<?php echo Route::_('index.php?option=com_users&task=user.edit&id=' . $row->created_by); ?>" title="View Profile" target="_blank"><?php echo $row->name; ?><strong> [<?php echo $row->username ; ?>]</strong></a>
								<?php
								}
						?>
							</td>
						<?php
						}
						?>
					<td class="center">
						<?php
							if ($row->published == 2)
							{
								echo Text::_('EB_CANCELLED');
							}
							else
							{
								echo $published;
							}
						?>
					</td>
					<td class="center">
						<?php echo $row->hits; ?>
					</td>
					<?php
					if ($isMultilingual)
					{
					?>
						<td class="center">
                            <?php
							if ($row->language && $row->language != '*' && isset($languages[$row->language]))
							{
								echo '<img src="' . $rootUri . '/media/mod_languages/images/' . $languages[$row->language]->image . '.gif" />';
							}
							else
							{
								echo Text::_('EB_ALL');
							}
							?>
						</td>
                    <?php
					}
					?>
					<td class="center">
						<?php echo $row->id; ?>
					</td>
				</tr>
				<?php
				$k = 1 - $k;
			}
			?>
			</tbody>
		</table>
	</div>
	<?php $this->renderFormHiddenVariables(); ?>
</form>