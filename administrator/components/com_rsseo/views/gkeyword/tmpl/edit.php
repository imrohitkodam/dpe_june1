<?php
/**
* @package RSSeo!
* @copyright (C) 2016 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory;

HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('behavior.keepalive'); 
Text::script('COM_RSSEO_GKEYWORD_RUN_ALL');
Text::script('COM_RSSEO_GKEYWORD_RUN_ALL_PAUSE');
Text::script('COM_RSSEO_GKEYWORD_DATE');
Text::script('COM_RSSEO_GKEYWORD_POSITION');
$pages = $impressions = $clicks = $avg = 0; ?>

<form action="<?php echo Route::_('index.php?option=com_rsseo&view=gkeyword&layout=edit&id='.(int) $this->item->id); ?>" method="post" name="adminForm" id="adminForm" autocomplete="off" class="form-validate form-horizontal">
	<div class="<?php echo RSSeoAdapterGrid::row(); ?>">
		<div class="<?php echo RSSeoAdapterGrid::column(12); ?>">
			<?php echo $this->form->renderField('name'); ?>
			<?php echo $this->form->renderField('site'); ?>
		</div>
	</div>
	
	<?php if (!$this->total) { ?>	
	<?php if ($this->item->id) { ?>
	<br />
	<div class="alert alert-info">
		<i class="fa fa-info fa-fw fa-2x" style="vertical-align: sub;"></i> 
		<span id=""><?php echo Text::_('COM_RSSEO_GKEYWORD_NO_DATA'); ?></span>
	</div>
	<?php } ?>
	<?php } else { ?>
		<div class="<?php echo RSSeoAdapterGrid::row(); ?>">
			<div class="<?php echo RSSeoAdapterGrid::column(12); ?>">
				<div class="<?php echo RSSeoAdapterGrid::fdirection('pull-right'); ?> rsseo-chart-filter">
					<?php echo HTMLHelper::_('calendar', $this->to, 'filter_to', 'filter_to', '%Y-%m-%d' , array('class' => 'input-small', 'onChange' => 'document.adminForm.submit();', 'placeholder' => Text::_('COM_RSSEO_TO'))); ?>
				</div>
				
				<div class="<?php echo RSSeoAdapterGrid::fdirection('pull-right'); ?> rsseo-chart-filter">
					<?php echo HTMLHelper::_('calendar', $this->from, 'filter_from', 'filter_from', '%Y-%m-%d' , array('class' => 'input-small', 'onChange' => 'document.adminForm.submit();', 'placeholder' => Text::_('COM_RSSEO_FROM'))); ?>
				</div>
				
				<div class="<?php echo RSSeoAdapterGrid::fdirection('pull-right'); ?> rsseo-chart-filter">
					<select name="filter_device" id="filter_device" class="custom-select" onchange="document.adminForm.submit();">
						<?php echo HTMLHelper::_('select.options', $this->devices, 'value', 'text', $this->device); ?>
					</select>
				</div>
				
				<div class="<?php echo RSSeoAdapterGrid::fdirection('pull-right'); ?> rsseo-chart-filter">
					<select name="filter_country" id="filter_country" class="custom-select" onchange="document.adminForm.submit();">
						<?php echo HTMLHelper::_('select.options', $this->countries, 'value', 'text', $this->country); ?>
					</select>
				</div>
			</div>
		</div>
			
		<br class="clearfix">
		
		<?php if ($this->data) { ?>
			
		<div class="<?php echo RSSeoAdapterGrid::row(); ?>">
			<div class="<?php echo RSSeoAdapterGrid::column(12); ?>">
				<div class="rsseo_chart_container">
					<div id="chart"></div>
				</div>
			</div>
		</div>
		
		<br class="clearfix">
		
		<table class="table table-striped table-hover rsseo_import_table">
			<thead>
				<tr>
					<th><?php echo Text::_('COM_RSSEO_GKEYWORD_DATE'); ?></th>
					<th class="center text-center"><?php echo Text::_('COM_RSSEO_GKEYWORD_PAGES'); ?></th>
					<th class="center text-center"><?php echo Text::_('COM_RSSEO_GKEYWORD_IMPRESSIONS'); ?></th>
					<th class="center text-center"><?php echo Text::_('COM_RSSEO_GKEYWORD_CLICKS'); ?></th>
					<th class="center text-center"><?php echo Text::_('COM_RSSEO_GKEYWORD_AVG_POSITION'); ?></th>
					<th class="center text-center"><?php echo Text::_('COM_RSSEO_GKEYWORD_CTR'); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($this->data as $data) { ?>
				<?php $pages += (int) $data->pages;?>
				<?php $impressions += (int) $data->impressions;?>
				<?php $clicks += (int) $data->clicks;?>
				<?php $avg += $data->avgposition;?>
				<tr>
					<td><?php echo Factory::getDate($data->date)->format(rsseoHelper::getConfig('g_date_format','d M Y')); ?></td>
					<td class="center text-center">
						<?php if ($data->pages) { ?><a href="javascript:void(0)" onclick="RSSeo.showPages('<?php echo $this->item->id; ?>','<?php echo $data->date; ?>')"><?php } ?>
						<?php echo $data->pages; ?>
						<?php if ($data->pages) { ?></a><?php } ?>
					</td>
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
					<td class="center text-center"><?php echo $pages; ?></td>
					<td class="center text-center"><?php echo $impressions; ?></td>
					<td class="center text-center"><?php echo $clicks; ?></td>
					<td class="center text-center"><?php echo number_format($avg / count($this->data), 2); ?></td>
					<td class="center text-center"><?php echo number_format(($clicks / $impressions) * 100, 2); ?>%</td>
				</tr>
			</tfoot>
		</table>
		<?php } else { ?>
		
		<br />
		<div class="alert alert-info">
			<i class="fa fa-info fa-fw fa-2x" style="vertical-align: sub;"></i> 
			<span id=""><?php echo Text::_('COM_RSSEO_GKEYWORD_NO_DATA_WITH_FILTER'); ?></span>
		</div>
		<?php } ?>
	<?php } ?>
	
	<?php $selector = rsseoHelper::isJ4() ? 'process-data' : 'modal-process-data'; ?>
	<?php echo HTMLHelper::_('bootstrap.renderModal', $selector, array('title' => Text::_('COM_RSSEO_GKEYWORD_IMPORT'), 'footer' => $this->loadTemplate('footer'), 'bodyHeight' => 70), $this->loadTemplate('modal')); ?>
	<?php echo HTMLHelper::_('bootstrap.renderModal', 'rsseo-pages', array('title' => Text::_('COM_RSSEO_GKEYWORD_PAGES_REPORT'), 'bodyHeight' => 70)); ?>
	
	<?php echo HTMLHelper::_('form.token'); ?>
	<input type="hidden" name="task" value="" />
	<?php echo $this->form->getInput('id'); ?>
	<?php echo HTMLHelper::_('behavior.keepalive'); ?>
</form>