<?php
/**
* @package RSSeo!
* @copyright (C) 2016 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/
defined('_JEXEC') or die('Restricted access'); 

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;

Text::script('COM_RSSEO_GKEYWORD_DATE');
Text::script('COM_RSSEO_GKEYWORD_POSITION'); 
$colClass = isset($this->cache->size) ? 'rsseo-box-5' : 'rsseo-box-4'; ?>

<form action="<?php echo Route::_('index.php?option=com_rsseo'); ?>" method="post" name="adminForm" id="adminForm" autocomplete="off" class="form-validate form-horizontal">
	<div class="<?php echo RSSeoAdapterGrid::row(); ?>">
		<div class="<?php echo RSSeoAdapterGrid::column(9); ?>">
			<div class="<?php echo RSSeoAdapterGrid::row(); ?>">
				<div class="<?php echo RSSeoAdapterGrid::column(12); ?>">
					<div class="rsseo-stats">
						<div class="rsseo-box <?php echo $colClass; ?>">
							<div class="rsseo-box-image">
								<i class="fa fa-<?php echo $this->info->missing_title ? 'exclamation-triangle rsseo-box-icon-color3' : 'check rsseo-box-icon-color1'; ?>"></i>
							</div>
							<div class="rsseo-box-content">
								<?php if ($this->info->missing_title) { ?>
								<strong class="rsseo-box-number">
									<a href="<?php echo Route::_('index.php?option=com_rsseo&view=pages&hash=title|'.md5(''), false); ?>" class="hasTooltip" title="<?php echo Text::sprintf('COM_RSSEO_MISSING_TITLES_INFO', $this->info->missing_title); ?>">
										<?php echo $this->info->missing_title; ?>
									</a>
								</strong>
								<?php } else { ?>
								<strong class="rsseo-box-number"><?php echo $this->info->missing_title; ?></strong>
								<?php } ?>
								<span><?php echo Text::_('COM_RSSEO_MISSING_TITLES'); ?></span>
							</div>
						</div>
						<div class="rsseo-box <?php echo $colClass; ?>">
							<div class="rsseo-box-image">
								<i class="fa fa-<?php echo $this->info->missing_keywords ? 'exclamation-triangle rsseo-box-icon-color3' : 'check rsseo-box-icon-color1'; ?>"></i>
							</div>
							<div class="rsseo-box-content">
								<?php if ($this->info->missing_keywords) { ?>
								<strong class="rsseo-box-number">
									<a href="<?php echo Route::_('index.php?option=com_rsseo&view=pages&hash=keywords|'.md5(''), false); ?>" class="hasTooltip" title="<?php echo Text::sprintf('COM_RSSEO_MISSING_KEYWORDS_INFO', $this->info->missing_keywords); ?>">
										<?php echo $this->info->missing_keywords; ?>
									</a>
								</strong>
								<?php } else { ?>
								<strong class="rsseo-box-number"><?php echo $this->info->missing_keywords; ?></strong>
								<?php } ?>
								<span><?php echo Text::_('COM_RSSEO_MISSING_KEYWORDS'); ?></span>
							</div>
						</div>
						<div class="rsseo-box <?php echo $colClass; ?>">
							<div class="rsseo-box-image">
								<i class="fa fa-<?php echo $this->info->missing_description ? 'exclamation-triangle rsseo-box-icon-color3' : 'check rsseo-box-icon-color1'; ?>"></i>
							</div>
							<div class="rsseo-box-content">
								<?php if ($this->info->missing_description) { ?>
								<strong class="rsseo-box-number">
									<a href="<?php echo Route::_('index.php?option=com_rsseo&view=pages&hash=description|'.md5(''), false); ?>" class="hasTooltip" title="<?php echo Text::sprintf('COM_RSSEO_MISSING_DESCRIPTION_INFO', $this->info->missing_description); ?>">
										<?php echo $this->info->missing_description; ?>
									</a>
								</strong>
								<?php } else { ?>
								<strong class="rsseo-box-number"><?php echo $this->info->missing_description; ?></strong>
								<?php } ?>
								<span><?php echo Text::_('COM_RSSEO_MISSING_DESCRIPTION'); ?></span>
							</div>
						</div>
						<div class="rsseo-box <?php echo $colClass; ?>">
							<div class="rsseo-box-image">
								<i class="fa fa-check rsseo-box-icon-color2"></i>
							</div>
							<div class="rsseo-box-content">
								<strong class="rsseo-box-number"><?php echo $this->info->total_pages; ?></strong>
								<span><?php echo Text::_('COM_RSSEO_TOTAL_PAGES'); ?></span>
							</div>
						</div>
						
						<?php if (isset($this->cache->size)) { ?>
						<div class="rsseo-box <?php echo $colClass; ?>">
							<div class="rsseo-box-image">
								<i class="fa fa-trash rsseo-box-icon-color2"></i>
							</div>
							<div class="rsseo-box-content">
								<strong class="rsseo-box-number hasTooltip" title="<?php echo Text::sprintf('COM_RSSEO_CACHE_INFO', $this->cache->files, $this->cache->size); ?>"><?php echo $this->cache->size; ?></strong>
								<span><a href="<?php echo Route::_('index.php?option=com_rsseo&task=clearcache'); ?>"><?php echo Text::_('COM_RSSEO_CLEAR_CACHE'); ?></a></span>
							</div>
						</div>
						<?php } ?>
					</div>
				</div>
			</div>
			
			<div class="<?php echo RSSeoAdapterGrid::row(); ?>">
				<div class="<?php echo RSSeoAdapterGrid::column(12); ?>">
					<div class="<?php echo RSSeoAdapterGrid::card(); ?>">
						<div class="card-body">
							<div class="<?php echo RSSeoAdapterGrid::fdirection('pull-right'); ?> rsseo-chart-filter">
								<?php echo HTMLHelper::_('calendar', '', 'rsto', 'rsto', '%Y-%m-%d' , array('class' => 'input-small', 'onChange' => 'RSSeo.drawGoogleKeywordChartDashboard()', 'placeholder' => Text::_('COM_RSSEO_TO'))); ?>
							</div>
							<div class="<?php echo RSSeoAdapterGrid::fdirection('pull-right'); ?> rsseo-chart-filter">
								<?php echo HTMLHelper::_('calendar', '', 'rsfrom', 'rsfrom', '%Y-%m-%d' , array('class' => 'input-small', 'onChange' => 'RSSeo.drawGoogleKeywordChartDashboard()', 'placeholder' => Text::_('COM_RSSEO_FROM'))); ?>
							</div>
							<div class="<?php echo RSSeoAdapterGrid::fdirection('pull-right'); ?> rsseo-chart-filter">
								<?php echo HTMLHelper::_('select.groupedlist', $this->keywords, 'keyword', array('list.attr' => 'class="custom-select" onchange="RSSeo.drawGoogleKeywordChartDashboard()"', 'id' => 'keyword', 'list.select' => '', 'group.items' => null, 'option.key.toHtml' => false, 'option.text.toHtml' => false)); ?>
							</div>
							
							<div class="clearfix clr"></div>
							<br />
							<div class="chart_container">
								<?php echo HTMLHelper::image('com_rsseo/loader.gif', '', array('id' => 'chart_keywords_loading', 'style' => 'display:none;'), true); ?>
								<div id="chart"></div>
							</div>
						</div>
					</div>
				</div>
			</div>
			
			<div class="<?php echo RSSeoAdapterGrid::row(); ?>">&nbsp;</div>
			
			<div class="<?php echo RSSeoAdapterGrid::row(); ?>">
				<div class="<?php echo RSSeoAdapterGrid::column(6); ?>">
					<?php if (!empty($this->pages)) { ?>
					<div class="<?php echo RSSeoAdapterGrid::card(); ?>">
						<div class="card-body">
							<h4 class="center text-center"><?php echo Text::_('COM_RSSEO_MOST_VISITED_PAGES'); ?></h4>				
							<table class="table table-condensed">
							<?php foreach ($this->pages as $page) { ?>
							<?php $url = rsseoHelper::showURL($page->url, $page->sef); ?>
							<?php $pageurl = ($page->id == 1) ? Uri::root() : $url; ?>
								<tr>
									<td width="90%" class="rstd">
										<a href="<?php echo Route::_('index.php?option=com_rsseo&task=page.edit&id='.$page->id); ?>">
											<?php echo $pageurl; ?>
										</a>
										&nbsp;
										<a href="<?php echo Uri::root().$this->escape($url); ?>" target="_blank">
											<i class="fas fa-external-link"></i>
										</a>
									</td>
									<td class="center"><span class="<?php echo RSSeoAdapterGrid::badge(); ?>"><?php echo $page->hits; ?></span></td>
								</tr>
							<?php } ?>
							</table>
						</div>
					</div>
					<?php } ?>
				</div>
				<div class="<?php echo RSSeoAdapterGrid::column(6); ?>">
					<?php if (!empty($this->lastcrawled)) { ?>
					<div class="<?php echo RSSeoAdapterGrid::card(); ?>">
						<div class="card-body">
							<h4 class="center text-center"><?php echo Text::_('COM_RSSEO_LAST_CRAWLED_PAGES'); ?></h4>				
							<table class="table table-condensed">
							<?php foreach ($this->lastcrawled as $page) { ?>
							<?php $url = rsseoHelper::showURL($page->url, $page->sef); ?>
							<?php $pageurl = ($page->id == 1) ? Uri::root() : $url; ?>
								<tr>
									<td width="73%" class="rstd">
										<a href="<?php echo Route::_('index.php?option=com_rsseo&task=page.edit&id='.$page->id); ?>">
											<?php echo $pageurl; ?>
										</a>
										&nbsp;
										<a href="<?php echo Uri::root().$this->escape($url); ?>" target="_blank">
											<i class="fas fa-external-link"></i>
										</a>
									</td>
									<td class="center small"><?php echo HTMLHelper::_('date', $page->date, rsseoHelper::getConfig('global_dateformat')); ?></td>
								</tr>
							<?php } ?>
							</table>
						</div>
					</div>
					<?php } ?>
				</div>
			</div>
		</div>
		<div class="<?php echo RSSeoAdapterGrid::column(3); ?>">
			<ul class="<?php echo RSSeoAdapterGrid::nav(); ?>">
				<li class="center active">
					<div class="dashboard-container">
						<div class="dashboard-info">
							<span>
								<?php echo HTMLHelper::image('com_rsseo/rsseo.png', 'RSSeo!', array('align' => 'middle'), true); ?>
							</span>
							<table class="dashboard-table">
								<tr>
									<td><strong><?php echo Text::_('COM_RSSEO_PRODUCT_VERSION') ?>: </strong></td>
									<td><b>RSSeo! <?php echo $this->version; ?></b></td>
								</tr>
								<tr>
									<td><strong><?php echo Text::_('COM_RSSEO_COPYRIGHT_NAME') ?>: </strong></td>
									<td>&copy; <?php echo gmdate('Y'); ?> <a href="http://www.rsjoomla.com" target="_blank">RSJoomla.com</a></td>
								</tr>
								<tr>
									<td><strong><?php echo Text::_('COM_RSSEO_LICENSE_NAME') ?>: </strong></td>
									<td>GPL Commercial License</a></td>
								</tr>
								<tr>
									<td><strong><?php echo Text::_('COM_RSSEO_CODE_FOR_UPDATE') ?>: </strong></td>
									<?php if (strlen($this->code) == 20) { ?>
									<td class="correct-code"><?php echo $this->escape($this->code); ?></td>
									<?php } elseif ($this->code) { ?>
									<td class="incorrect-code"><?php echo $this->escape($this->code); ?></td>
									<?php } else { ?>
									<td class="missing-code">
										<a href="<?php echo Route::_('index.php?option=com_config&view=component&component=com_rsseo&path=&return='.base64_encode(Uri::getInstance())); ?>">
											<?php echo Text::_('COM_RSSEO_PLEASE_ENTER_YOUR_CODE_IN_THE_CONFIGURATION'); ?>
										</a>
									</td>
									<?php } ?>
								</tr>
							</table>
						</div>
					</div>
				</li>
				<li>
					<a class="nav-link" href="<?php echo Route::_('index.php?option=com_rsseo&view=pages'); ?>">
						<i class="fa fa-list-alt"></i> <?php echo Text::_('COM_RSSEO_MENU_PAGES'); ?>
					</a>
				</li>
				<li>
					<a class="nav-link" href="<?php echo Route::_('index.php?option=com_rsseo&view=crawler'); ?>">
						<i class="fa fa-bug"></i> <?php echo Text::_('COM_RSSEO_MENU_CRAWLER'); ?>
					</a>
				</li>
				<li>
					<a class="nav-link" href="<?php echo Route::_('index.php?option=com_rsseo&view=sitemap'); ?>">
						<i class="fa fa-sitemap"></i> <?php echo Text::_('COM_RSSEO_MENU_SITEMAP'); ?>
					</a>
				</li>
				<li>
					<a class="nav-link" href="<?php echo Route::_('index.php?option=com_rsseo&view=robots'); ?>">
						<i class="fab fa-android"></i> <?php echo Text::_('COM_RSSEO_MENU_ROBOTS'); ?>
					</a>
				</li>
				<li>
					<a class="nav-link" href="<?php echo Route::_('index.php?option=com_rsseo&view=errors'); ?>">
						<i class="fa fa-exclamation-triangle"></i> <?php echo Text::_('COM_RSSEO_MENU_ERRORS'); ?>
					</a>
				</li>
				<li>
					<a class="nav-link" href="<?php echo Route::_('index.php?option=com_rsseo&view=errorlinks'); ?>">
						<i class="fa fa-exclamation-circle"></i> <?php echo Text::_('COM_RSSEO_MENU_ERROR_LINKS'); ?>
					</a>
				</li>
				<li>
					<a class="nav-link" href="<?php echo Route::_('index.php?option=com_rsseo&view=redirects'); ?>">
						<i class="fa fa-repeat"></i> <?php echo Text::_('COM_RSSEO_MENU_REDIRECTS'); ?>
					</a>
				</li>
				<li>
					<a class="nav-link" href="<?php echo Route::_('index.php?option=com_rsseo&view=keywords'); ?>">
						<i class="fa fa-key"></i> <?php echo Text::_('COM_RSSEO_MENU_KEYWORDS'); ?>
					</a>
				</li>
				<li>
					<a class="nav-link" href="<?php echo Route::_('index.php?option=com_rsseo&view=gkeywords'); ?>">
						<i class="fa fa-key"></i> <?php echo Text::_('COM_RSSEO_MENU_GKEYWORDS'); ?>
					</a>
				</li>
				<li>
					<a class="nav-link" href="<?php echo Route::_('index.php?option=com_rsseo&view=analytics'); ?>">
						<i class="fa fa-pie-chart"></i> <?php echo Text::_('COM_RSSEO_MENU_ANALYTICS'); ?>
					</a>
				</li>
				<li>
					<a class="nav-link" href="<?php echo Route::_('index.php?option=com_rsseo&view=data'); ?>">
						<i class="fab fa-google"></i> <?php echo Text::_('COM_RSSEO_MENU_STRUCTURED_DATA'); ?>
					</a>
				</li>
				<li>
					<a class="nav-link" href="<?php echo Route::_('index.php?option=com_rsseo&view=statistics'); ?>">
						<i class="fa fa-eye"></i> <?php echo Text::_('COM_RSSEO_MENU_STATISTICS'); ?>
					</a>
				</li>
				<li>
					<a class="nav-link" href="<?php echo Route::_('index.php?option=com_rsseo&view=report'); ?>">
						<i class="fa fa-file-pdf"></i> <?php echo Text::_('COM_RSSEO_MENU_REPORT'); ?>
					</a>
				</li>
				<li>
					<a class="nav-link" href="<?php echo Route::_('index.php?option=com_rsseo&view=backup'); ?>">
						<i class="fa fa-hdd"></i> <?php echo Text::_('COM_RSSEO_MENU_BACKUP_RESTORE'); ?>
					</a>
				</li>
				<li>
					<a class="nav-link" href="<?php echo Route::_('index.php?option=com_rsseo&task=connectivity'); ?>">
						<i class="fa fa-globe"></i> <?php echo Text::_('COM_RSSEO_CHECK_CONNECTIVITY'); ?>
					</a>
				</li>
			</ul>
		</div>
	</div>

	<?php echo HTMLHelper::_('form.token'); ?>
	<input type="hidden" name="task" value="" />
	<?php echo HTMLHelper::_('behavior.keepalive'); ?>
</form>