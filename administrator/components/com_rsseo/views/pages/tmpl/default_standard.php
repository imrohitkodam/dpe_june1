<?php
/**
* @package RSSeo!
* @copyright (C) 2016 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;

$listOrder	= $this->escape($this->state->get('list.ordering', 'level'));
$listDirn	= $this->escape($this->state->get('list.direction', 'ASC')); ?>

<style type="text/css">.tooltip { z-index: 1500; }</style>

<table class="table table-striped">
	<caption id="captionTable" class="sr-only">
		<?php echo Text::_('COM_RSFIREWALL_LOGS_TABLE_CAPTION'); ?>,
		<span id="orderedBy"><?php echo Text::_('JGLOBAL_SORTED_BY'); ?> </span>,
		<span id="filteredBy"><?php echo Text::_('JGLOBAL_FILTERED_BY'); ?></span>
	</caption>
	<thead>
		<th width="1%" class="small center text-center hidden-phone"><?php echo HTMLHelper::_('grid.checkall'); ?></th>
		<th class="small hidden-phone"><?php echo HTMLHelper::_('searchtools.sort', 'COM_RSSEO_PAGES_URL', 'url', $listDirn, $listOrder); ?></th>
		<th class="center text-center small"><?php echo HTMLHelper::_('searchtools.sort', 'COM_RSSEO_PAGES_TITLE', 'title', $listDirn, $listOrder); ?></th>
		<th width="6%" class="center text-center small hidden-phone"><?php echo HTMLHelper::_('searchtools.sort', 'COM_RSSEO_PAGES_LEVEL', 'level', $listDirn, $listOrder); ?></th>
		<th width="8%" class="center text-center small hidden-phone"><?php echo HTMLHelper::_('searchtools.sort', 'COM_RSSEO_PAGES_HTTP_STATUS', 'status', $listDirn, $listOrder); ?></th>
		<th width="6%" class="center text-center small"><?php echo HTMLHelper::_('searchtools.sort', 'COM_RSSEO_PAGES_GRADE', 'grade', $listDirn, $listOrder); ?></th>
		<th width="8%" class="center text-center small hidden-phone"><?php echo HTMLHelper::_('searchtools.sort', 'COM_RSSEO_PAGES_LAST_CRAWLED', 'date', $listDirn, $listOrder); ?></th>
		<th width="1%" class="center text-center small hidden-phone"><?php echo Text::_('COM_RSSEO_PAGES_STATUS'); ?></th>
		<th width="7%" class="center text-center small hidden-phone"><?php echo Text::_('COM_RSSEO_PAGES_PAGE_MODIFIED'); ?></th>
		<th width="7% "class="center text-center small hidden-phone"><?php echo Text::_('COM_RSSEO_PAGES_ADD_TO_SITEMAP'); ?></th>
		<th width="5%" class="center text-center small hidden-phone"><?php echo Text::_('COM_RSSEO_GLOBAL_REFRESH'); ?></th>
		<th width="1%" class="center text-center small hidden-phone"><?php echo HTMLHelper::_('searchtools.sort', 'COM_RSSEO_HITS', 'hits', $listDirn, $listOrder); ?></th>
		<th width="1%" class="center text-center small hidden-phone"><?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'id', $listDirn, $listOrder); ?></th>
	</thead>
	<tbody>
		<?php foreach ($this->items as $i => $item) { ?>
		<?php $url = rsseoHelper::showURL($item->url, $item->sef); ?>
		<tr class="row<?php echo $i % 2; ?>">
			<td class="center text-center small hidden-phone"><?php echo HTMLHelper::_('grid.id', $i, $item->id); ?></td>
			<td class="small hidden-phone rstd">
				<a href="<?php echo Route::_('index.php?option=com_rsseo&task=page.edit&id='.$item->id); ?>">
					<?php echo $item->url ? str_replace('&amp;','&', $this->escape($item->url)) : $item->url; ?>
				</a>
				<a href="<?php echo Uri::root().$this->escape(html_entity_decode($url,ENT_COMPAT,'UTF-8')); ?>" target="_blank">
					<i class="fas fa-external-link"></i>
				</a>
				<?php if ($this->sef && $this->config->enable_sef) echo $item->sef ? '<br /><strong>'.$url.'</strong>' : ''; ?>
			</td>
			<td class="small has-context rstd">
				<a href="<?php echo Route::_('index.php?option=com_rsseo&task=page.edit&id='.$item->id); ?>">
					<span id="title<?php echo $item->id; ?>">
						<?php echo empty($item->title) ? Text::_('COM_RSSEO_GLOBAL_NO_TITLE') : $this->escape($item->title); ?>
					</span>
				</a>
			</td>
			
			<td class="center text-center small hidden-phone">
				<?php echo ($item->level >= 127) ? Text::_('COM_RSSEO_GLOBAL_UNDEFINED') : $item->level; ?>
			</td>
			
			<td class="center text-center small">
				<span id="status<?php echo $item->id; ?>" class="<?php echo RSSeoAdapterGrid::badge($item->status == 200 ? 'success' : 'danger'); ?>">
					<?php echo !empty($item->status) ? $item->status : '-'; ?>
				</span>
			</td>
			
			<td class="center text-center small">
				<?php $grade = ($item->grade <= 0) ? 0 : ceil($item->grade); ?>
				<div class="rsj-progress" style="width: 100%">
					<span id="page<?php echo $item->id; ?>" style="width: <?php echo $grade; ?>%;" class="<?php echo $item->color; ?>">
						<span><?php echo $grade; ?>%</span>
					</span>
				</div>
			</td>
			
			<td class="center text-center small hidden-phone">
				<span id="date<?php echo $item->id; ?>">
					<?php echo HTMLHelper::_('date', $item->date, $this->config->global_dateformat); ?>
				</span>
			</td>
			
			<td class="center text-center small hidden-phone">
				<?php echo HTMLHelper::_('jgrid.published', $item->published, $i, 'pages.'); ?>
			</td>
			
			<td class="center text-center small hidden-phone">
				<?php 
					$states = array(
						1 => array('unpublish', '', '', 'JYES', true, 'publish', 'publish'),
						0 => array('publish', '', '', 'JNO', true, 'unpublish', 'unpublish')
					);
				?>
				<?php echo HTMLHelper::_('jgrid.state', $states, $item->modified, $i, '', false); ?>
			</td>
			
			<td class="center text-center small hidden-phone">
				<?php 
					$states = array(
						1 => array('removesitemap', 'COM_RSSEO_PAGE_REMOVE_FROM_SITEMAP', 'COM_RSSEO_PAGE_REMOVE_FROM_SITEMAP', 'COM_RSSEO_PAGE_REMOVE_FROM_SITEMAP', true, 'publish', 'publish'),
						0 => array('addsitemap', 'COM_RSSEO_PAGE_ADD_TO_SITEMAP', 'COM_RSSEO_PAGE_ADD_TO_SITEMAP', 'COM_RSSEO_PAGE_ADD_TO_SITEMAP', true, 'unpublish', 'unpublish')
					);
				?>
				<?php echo HTMLHelper::_('jgrid.state', $states, $item->insitemap, $i, 'pages.'); ?>
			</td>
			
			<td class="center text-center small hidden-phone">
				<?php if ($this->config->crawler_type == 'ajax') { ?>
				<a href="javascript:void(0)" onclick="RSSeo.refresh('<?php echo Uri::root().$item->url; ?>',<?php echo $item->id; ?>, 1)" id="restore<?php echo $item->id; ?>" style="display:none;">&nbsp;</a>
				<a href="javascript:void(0)" onclick="RSSeo.refresh('<?php echo Uri::root().$item->url; ?>',<?php echo $item->id; ?>, 0)" id="refresh<?php echo $item->id; ?>">
				<?php } else { ?>
				<a href="javascript:void(0)" onclick="RSSeo.checkPage(<?php echo $item->id; ?>,0)" id="refresh<?php echo $item->id; ?>">
				<?php } ?>
					<?php echo Text::_('COM_RSSEO_GLOBAL_REFRESH'); ?>
				</a>
				
				<?php echo HTMLHelper::image('com_rsseo/loader.gif', '', array('id' => 'loading'.$item->id, 'style' => 'display:none;'), true); ?>
			</td>
			
			<td class="center text-center small hidden-phone">
				<?php echo $item->hits; ?>
			</td>
			
			<td class="center text-center small hidden-phone">
				<?php echo $item->id; ?>
			</td>
		</tr>
		<?php } ?>
	</tbody>
	<tfoot>
		<tr>
			<td colspan="16">
				<?php echo $this->pagination->getListFooter(); ?>
			</td>
		</tr>
	</tfoot>
</table>