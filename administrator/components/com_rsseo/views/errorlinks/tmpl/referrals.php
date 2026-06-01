<?php
/**
* @package RSSeo!
* @copyright (C) 2016 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
?>

<button type="button" class="btn btn-danger mb-3" onclick="if (confirm('<?php echo Text::_('COM_RSSEO_ERROR_LINKS_REMOVE_REFERRALS_PROMPT'); ?>')) RSSeo.removeErrorReferal('<?php echo Factory::getApplication()->input->getInt('id',0); ?>');"><?php echo Text::_('COM_RSSEO_ERROR_LINKS_REMOVE_REFERRALS'); ?></button>

<table class="table table-striped table-bordered">
	<thead>
		<th><?php echo Text::_('COM_RSSEO_ERROR_LINK_REFERAL'); ?></th>
		<th class="text-center center hidden-phone"><?php echo Text::_('COM_RSSEO_ERROR_LINK_REFERAL_DATE'); ?></th>
	</thead>
	<tbody>
		<?php foreach ($this->referrals as $i => $item) { ?>
		<tr class="row<?php echo $i % 2; ?>">
			<td class="nowrap has-context">
				<?php echo $this->escape($item->referer); ?>
			</td>
			<td class="center text-center hidden-phone">
				<?php echo HTMLHelper::_('date', $item->date, rsseoHelper::getConfig('global_dateformat')); ?>
			</td>
		</tr>
		<?php } ?>
	</tbody>
</table>