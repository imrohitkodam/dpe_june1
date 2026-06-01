<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

?>
<div class="info-card group-rates-card">
	<div class="card-header-icon icon-pink">
		<i class="fas fa-users"></i>
		<h3 class="card-title"><?php echo Text::_('EB_GROUP_RATE'); ?></h3>
	</div>

	<table class="group-rate-table">
		<thead>
			<tr>
				<th>				<?php echo Text::_('EB_NUMBER_REGISTRANTS'); ?>
					
				</th>
				<th><?php echo Text::_('EB_RATE_PERSON'); ?> (<?php echo $this->item->currency_symbol ?: $this->config->currency_symbol; ?>)</th>
			</tr>
		</thead>
		<tbody>
		<?php
		$i = 0 ;

		if ($this->config->show_price_including_tax && !$this->config->get('setup_price'))
		{
			$taxRate = $this->item->tax_rate;
		}
		else
		{
			$taxRate = 0;
		}

		foreach ($this->rowGroupRates as $rowRate)
		{
			$groupRate = round($rowRate->price * (1 + $taxRate / 100), 2);
		?>
			<tr>
				<td class="eb_number_registrant_column">
					<?php echo Text::sprintf('EB_FROM_NUMBER_REGISTRANTS', $rowRate->registrant_number); ?>
				</td>
				<td class="eb_rate_column">
					<?php echo EventbookingHelper::formatAmount($groupRate, $this->config); ?>
				</td>
			</tr>
		<?php
		}
		?>
		</tbody>
	</table>
</div>
