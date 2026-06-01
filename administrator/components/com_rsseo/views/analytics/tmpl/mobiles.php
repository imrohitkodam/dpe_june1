<?php
/**
* @package RSSeo!
* @copyright (C) 2016 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Language\Text;
?>
<?php if (is_array($this->mobiles)) { ?>
	<fieldset class="options-form">
		<legend><?php echo Text::_('COM_RSSEO_GA_MOBILES'); ?></legend>
		<table class="table table-striped table-bordered">
			<thead>
				<tr>
					<th width="15%"><?php echo Text::_('COM_RSSEO_GA_MOBILES_OS'); ?></th>
					<th class="center text-center"><?php echo Text::_('COM_RSSEO_GA_MOBILES_VISITS'); ?></th>
					<th class="center text-center"><?php echo Text::_('COM_RSSEO_GA_MOBILES_PAGEVISITS'); ?></th>
					<th class="center text-center"><?php echo Text::_('COM_RSSEO_GA_MOBILES_BOUNCERATE'); ?></th>
					<th class="center text-center"><?php echo Text::_('COM_RSSEO_GA_MOBILES_AVGTIME'); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php if (!empty($this->mobiles)) { ?>
			<?php foreach ($this->mobiles as $i => $result) { ?>
				<tr class="row<?php echo $i % 2; ?>">
					<td><?php echo $result->browser; ?></td>
					<td class="center text-center"><?php echo $result->visits; ?></td>
					<td class="center text-center"><?php echo $result->pagesvisits; ?></td>
					<td class="center text-center"><?php echo $result->bouncerate; ?></td>
					<td class="center text-center"><?php echo $result->avgtimesite; ?></td>
				</tr>
			<?php } ?>
			<?php } ?>
			</tbody>
		</table>
	</fieldset>
<?php } else echo $this->mobiles; ?>