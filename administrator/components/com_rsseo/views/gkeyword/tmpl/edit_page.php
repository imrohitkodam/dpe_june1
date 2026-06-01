<?php
/**
* @package RSSeo!
* @copyright (C) 2016 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Language\Text;

$avg = $clicks = $impressions = 0; ?>

<div class="container-fluid">	
	<table class="table table-striped table-hover rsseo_import_table">
		<thead>
			<tr>
				<th><?php echo Text::_('COM_RSSEO_GKEYWORD_PAGE'); ?></th>
				<th class="center text-center"><?php echo Text::_('COM_RSSEO_GKEYWORD_IMPRESSIONS'); ?></th>
				<th class="center text-center"><?php echo Text::_('COM_RSSEO_GKEYWORD_CLICKS'); ?></th>
				<th class="center text-center"><?php echo Text::_('COM_RSSEO_GKEYWORD_AVG_POSITION'); ?></th>
				<th class="center text-center"><?php echo Text::_('COM_RSSEO_GKEYWORD_CTR'); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($this->items as $data) { ?>
			<?php $impressions += (int) $data->impressions;?>
			<?php $clicks += (int) $data->clicks;?>
			<?php $avg += $data->avgposition;?>
			<tr>
				<td><?php echo $data->page; ?></td>
				<td class="center text-center"><?php echo $data->impressions; ?></td>
				<td class="center text-center"><?php echo $data->clicks; ?></td>
				<td class="center text-center"><?php echo number_format($data->avgposition, 2); ?></td>
				<td class="center text-center"><?php echo number_format(($data->clicks / $data->impressions) * 100, 2); ?>%</td>
			</tr>
			<?php } ?>
		</tbody>
		<tfoot>
			<tr>
				<td><?php echo Text::_('COM_RSSEO_GKEYWORD_TOTALS'); ?></td>
				<td class="center text-center"><?php echo $impressions; ?></td>
				<td class="center text-center"><?php echo $clicks; ?></td>
				<td class="center text-center"><?php echo number_format($avg / count($this->items), 2); ?></td>
				<td class="center text-center"><?php echo number_format(($clicks / $impressions) * 100, 2); ?>%</td>
			</tr>
		</tfoot>
	</table>
</div>
<?php die; ?>