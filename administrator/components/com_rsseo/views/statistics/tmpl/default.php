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
use Joomla\CMS\Uri\Uri;

HTMLHelper::_('behavior.keepalive');
Text::script('COM_RSSEO_LOAD_MORE');
Text::script('COM_RSSEO_LOADING');  ?>

<form action="<?php echo Route::_('index.php?option=com_rsseo&view=statistics');?>" method="post" name="adminForm" id="adminForm">

	<?php echo RSSeoAdapterGrid::sidebar(); ?>
		<?php if (!$this->config->track_visitors) { ?><div class="alert alert-danger"><?php echo Text::_('COM_RSSEO_TRACK_VISITORS_DISABLED'); ?></div><?php } ?>
		
		<div class="<?php echo RSSeoAdapterGrid::card(); ?>">
			<div class="card-body">
				<div id="rsseo-filter">
					<div class="<?php echo RSSeoAdapterGrid::fdirection('pull-right'); ?>">
						<button class="btn btn-info button" type="button" onclick="RSSeo.updateVisitors();"><?php echo Text::_('COM_RSSEO_GLOBAL_UPDATE'); ?></button>
					</div>
					<div class="<?php echo RSSeoAdapterGrid::fdirection('pull-right'); ?>">&nbsp;</div>
					<div class="<?php echo RSSeoAdapterGrid::fdirection('pull-right'); ?>">
						<?php echo HTMLHelper::_('calendar', $this->to, 'rsto', 'rsto', '%Y-%m-%d' , array('class' => 'input-small')); ?>
					</div>
					<div class="<?php echo RSSeoAdapterGrid::fdirection('pull-right'); ?>">&nbsp;</div>
					<div class="<?php echo RSSeoAdapterGrid::fdirection('pull-right'); ?>">
						<?php echo HTMLHelper::_('calendar', $this->from, 'rsfrom', 'rsfrom', '%Y-%m-%d' , array('class' => 'input-small')); ?>
					</div>
					<div class="clearfix clr"> </div>
				</div>
				
				<div class="rsseo-stats">
					<div class="rsseo-box rsseo-box-4">
						<div class="rsseo-box-image">
							<i class="fa fa-user rsseo-box-icon-visitors"></i>
						</div>
						<div class="rsseo-box-content">
							<strong id="total-visitors"><?php echo $this->totalvisitors; ?></strong>
							<span><?php echo Text::_('COM_RSSEO_TOTAL_VISITORS'); ?></span>
						</div>
					</div>
					<div class="rsseo-box rsseo-box-4">
						<div class="rsseo-box-image">
							<i class="fa fa-eye rsseo-box-icon-pageviews"></i>
						</div>
						<div class="rsseo-box-content">
							<strong id="total-pageviews"><?php echo $this->totalpageviews; ?></strong>
							<span><?php echo Text::_('COM_RSSEO_TOTAL_PAGEVIEWS'); ?></span>
						</div>
					</div>
					<div class="rsseo-box rsseo-box-4">
						<div class="rsseo-box-image">
							<i class="fa fa-user rsseo-box-icon-visitors"></i>
						</div>
						<div class="rsseo-box-content">
							<strong id="visitors-timeframe"><?php echo $this->totalvisitorst; ?></strong>
							<span><?php echo Text::_('COM_RSSEO_TOTAL_VISITORS_IN_PERIOD'); ?></span>
						</div>
					</div>
					<div class="rsseo-box rsseo-box-4">
						<div class="rsseo-box-image">
							<i class="fa fa-eye rsseo-box-icon-pageviews"></i>
						</div>
						<div class="rsseo-box-content">
							<strong id="pageviews-timeframe"><?php echo $this->totalpageviewst; ?></strong>
							<span><?php echo Text::_('COM_RSSEO_TOTAL_PAGEVIEWS_IN_PERIOD'); ?></span>
						</div>
					</div>
				</div>
			
				<hr />
			
				<div class="visitors-container">
					<div class="<?php echo RSSeoAdapterGrid::row(); ?>">
						<div class="chart_visitors_container <?php echo RSSeoAdapterGrid::column(6); ?>">
							<h3 class="text-center"><?php echo Text::_('COM_RSSEO_CHART_VISITS_TITLE'); ?></h3>
							<?php echo HTMLHelper::image('com_rsseo/loader.gif', '', array('id' => 'chart_visitors_loading', 'style' => 'display:none;'), true); ?>
							<div id="chart_visitors"></div>
						</div>
						<div class="chart_pageviews_container <?php echo RSSeoAdapterGrid::column(6); ?>">
							<h3 class="text-center"><?php echo Text::_('COM_RSSEO_CHART_PAGEVIEWS_TITLE'); ?></h3>
							<?php echo HTMLHelper::image('com_rsseo/loader.gif', '', array('id' => 'chart_pageviews_loading', 'style' => 'display:none;'), true); ?>
							<div id="chart_pageviews"></div>
						</div>
					</div>
					<div class="clr"></div>
					
					<hr />
					
					<div id="visitors-table">
						<table class="table adminlist">
							<thead>
								<tr>
									<th width="1%" class="small hidden-phone"><?php echo HTMLHelper::_('grid.checkall'); ?></th>
									<th width="15%"><?php echo Text::_('COM_RSSEO_CHART_DATE'); ?></th>
									<th width="15%"><?php echo Text::_('COM_RSSEO_IP'); ?></th>
									<th><?php echo Text::_('COM_RSSEO_EXIT_PAGE'); ?></th>
									<th width="10%">&nbsp;</th>
								</tr>
							</thead>
							<tbody>
							<?php foreach ($this->visitors as $i => $visitor) { ?>
								<tr>
									<td class="center small hidden-phone"><?php echo HTMLHelper::_('grid.id', $i, $visitor->session_id); ?></td>
									<td><?php echo HTMLHelper::_('date', $visitor->date, $this->config->global_dateformat); ?></td>
									<td><?php echo rsseoHelper::obfuscateIP($visitor->ip); ?></td>
									<td><a href="<?php echo Uri::root().rsseoHelper::getSef($visitor->page); ?>" target="_blank"><?php echo empty($visitor->page) ? Uri::root() : rsseoHelper::getSef($visitor->page); ?></a></td>
									<td><a href="javascript:void(0);" onclick="RSSeo.showModal('<?php echo Route::_('index.php?option=com_rsseo&view=statistics&layout=pageviews&tmpl=component&id='.$visitor->id,false); ?>');"><?php echo Text::_('COM_RSSEO_VIEW_PAGEVIEWS'); ?></a></td>
								</tr>
							<?php } ?>
							</tbody>
							<tfoot>
								<tr id="visitors-pagination" <?php if ($this->total <= $this->count) { ?>style="display: none;<?php } ?>">
									<td colspan="5" class="center text-center">
										<button type="button" class="rsseo-btn" onclick="RSSeo.loadVisitors()"><?php echo Text::_('COM_RSSEO_LOAD_MORE'); ?></button>
									</td>
								</tr>
							</tfoot>
						</table>
						<span id="visitors-total" style="display: none;"><?php echo $this->total; ?></span>
						<input type="hidden" name="boxchecked" value="0" />
					</div>
					
				</div>
			</div>
		</div>
	</div>

	<?php echo HTMLHelper::_( 'form.token' ); ?>
	<input type="hidden" name="task" value="" />
</form>

<?php echo HTMLHelper::_('bootstrap.renderModal', 'rsseoModal', array('title' => Text::_('COM_RSSEO_CHART_PAGEVIEWS_TITLE'), 'height' => 600, 'bodyHeight' => 70)); ?>