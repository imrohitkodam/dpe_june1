<?php
/**
* @package RSSeo!
* @copyright (C) 2016 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Language\Text;
?>
<?php if (is_array($this->sources)) { ?>
	<fieldset class="options-form">
		<legend><?php echo Text::_('COM_RSSEO_GA_SOURCES'); ?></legend>
		<table class="table table-striped table-bordered">
			<thead>
				<tr>
					<th><?php echo Text::_('COM_RSSEO_GA_SOURCES_SOURCE'); ?></th>
					<th class="center text-center"><?php echo Text::_('COM_RSSEO_GA_SOURCES_VISITS'); ?></th>
					<th class="center text-center"><?php echo Text::_('COM_RSSEO_GA_SOURCES_PAGEVISITS'); ?></th>
					<th class="center text-center"><?php echo Text::_('COM_RSSEO_GA_SOURCES_BOUNCERATE'); ?></th>
					<th class="center text-center"><?php echo Text::_('COM_RSSEO_GA_SOURCES_AVGTIME'); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php if (!empty($this->sources)) { ?>
			<?php foreach ($this->sources as $i => $result) { ?>
				<tr class="row<?php echo $i % 2; ?>">
					<td><?php echo $result->source; ?></td>
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
<?php } else echo $this->sources; ?>