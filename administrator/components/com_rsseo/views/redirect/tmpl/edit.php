<?php
/**
* @package RSSeo!
* @copyright (C) 2016 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;

HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('behavior.keepalive'); ?>

<form action="<?php echo Route::_('index.php?option=com_rsseo&view=redirect&layout=edit&id='.(int) $this->item->id); ?>" method="post" name="adminForm" id="adminForm" autocomplete="off" class="form-validate form-horizontal">
	
	<?php echo $this->form->renderField('published'); ?>
	<div class="control-group">
		<div class="control-label"><?php echo $this->form->getLabel('from'); ?></div>
		<div class="controls">
			<?php echo RSSeoAdapterGrid::inputGroup($this->form->getInput('from'), '<span id="rsroot">'.Uri::root().'</span>', '<i class="fa fa-info-circle hasTooltip" title="'.Text::_('COM_RSSEO_REDIRECT_INFO').'"></i>'); ?>
			<div class="clr"></div>
			<div id="rss_results"><ul id="rsResultsUl"></ul></div>
		</div>
	</div>
	<?php echo $this->form->renderField('to'); ?>
	<?php echo $this->form->renderField('type'); ?>
	
	<?php if (!empty($this->referrers)) { ?>
	<table class="table table-striped adminlist">
		<thead>
			<tr>
				<th><?php echo Text::_('COM_RSSEO_REFERER'); ?></th>
				<th class="center text-center" width="15%"><?php echo Text::_('COM_RSSEO_REFERER_DATE'); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php foreach ($this->referrers as $referer) { ?>
			<tr>
				<td><b><?php echo $referer->referer ? $this->escape($referer->referer) : Text::_('COM_RSSEO_DIRECT_LINK'); ?></b> <?php if ($referer->url) echo '<small>('.Text::_('COM_RSSEO_LINK').': '.$this->escape($referer->url).')</small>'; ?></td>
				<td class="center text-center"><?php echo HTMLHelper::_('date',$referer->date, rsseoHelper::getConfig('global_dateformat')); ?></td>
			</tr>
		<?php } ?>
		</tbody>
	</table>
	<?php } ?>

	<?php echo HTMLHelper::_('form.token'); ?>
	<input type="hidden" name="task" value="" />
	<?php echo $this->form->getInput('id'); ?>
</form>