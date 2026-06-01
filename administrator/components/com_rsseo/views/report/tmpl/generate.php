<?php
/**
* @package RSSeo!
* @copyright (C) 2016 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

?>

<style type="text/css">
.table {
	width: 100%;
	margin-bottom: 18px;
	border-spacing: 0;
	border-collapse: collapse;
}
.table th,
.table td {
	padding: 8px;
	line-height: 18px;
	text-align: left;
	vertical-align: top;
	border-top: 1px solid #ddd;
}
.table th {
	font-weight: bold;
}
.table thead th {
	vertical-align: bottom;
}
.table td.center,
.table thead th.center {
	text-align: center;
}
</style>

<center><h1><?php echo Text::sprintf('COM_RSSEO_REPORT_GENERATED', HTMLHelper::_('date', 'now', rsseoHelper::getConfig('global_dateformat'))); ?></h1></center>

<?php if ($this->data->last_crawled && !empty($this->lcrawled)) { ?>
<h2><?php echo Text::_('COM_RSSEO_REPORT_LAST_CRAWLED'); ?></h2>
<table class="table">
	<thead>
		<tr>
			<th><?php echo Text::_('COM_RSSEO_REPORT_PAGE'); ?></th>
			<th class="center"><?php echo Text::_('COM_RSSEO_REPORT_DATE'); ?></th>
		</tr>
	</thead>
	<tbody>
	<?php foreach ($this->lcrawled as $page) { ?>
	<?php $url = rsseoHelper::showURL($page->url, $page->sef); ?>
	<?php $pageurl = ($page->id == 1) ? Uri::root() : $url; ?>
		<tr>
			<td width="70%" style="word-break:break-all; word-wrap:break-word;"><?php echo $pageurl; ?></td>
			<td class="center"><?php echo HTMLHelper::_('date', $page->date, rsseoHelper::getConfig('global_dateformat')); ?></td>
		</tr>
	<?php } ?>
	</tbody>
</table>
<div style="page-break-after: always;"></div>
<?php } ?>

<?php if ($this->data->most_visited && $this->mvisited) { ?>
<h2><?php echo Text::_('COM_RSSEO_REPORT_MOST_VISITED'); ?></h2>
<table class="table">
	<thead>
		<tr>
			<th><?php echo Text::_('COM_RSSEO_REPORT_PAGE'); ?></th>
			<th class="center"><?php echo Text::_('COM_RSSEO_REPORT_HITS'); ?></th>
		</tr>
	</thead>
	<tbody>
	<?php foreach ($this->mvisited as $page) { ?>
	<?php $url = rsseoHelper::showURL($page->url, $page->sef); ?>
	<?php $pageurl = ($page->id == 1) ? Uri::root() : $url; ?>
		<tr>
			<td width="70%" style="word-break:break-all; word-wrap:break-word;"><?php echo $pageurl; ?></td>
			<td class="center"><?php echo $page->hits; ?></td>
		</tr>
	<?php } ?>
	</tbody>
</table>
<div style="page-break-after: always;"></div>
<?php } ?>

<?php if ($this->data->no_title && $this->notitle) { ?>
<h2><?php echo Text::_('COM_RSSEO_REPORT_NO_TITLE'); ?></h2>
<table class="table">
	<thead>
		<tr>
			<th><?php echo Text::_('COM_RSSEO_REPORT_PAGE'); ?></th>
			<th class="center"><?php echo Text::_('COM_RSSEO_REPORT_DATE'); ?></th>
		</tr>
	</thead>
	<tbody>
	<?php foreach ($this->notitle as $page) { ?>
	<?php $url = rsseoHelper::showURL($page->url, $page->sef); ?>
	<?php $pageurl = ($page->id == 1) ? Uri::root() : $url; ?>
		<tr>
			<td width="70%" style="word-break:break-all; word-wrap:break-word;"><?php echo $pageurl; ?></td>
			<td class="center"><?php echo HTMLHelper::_('date', $page->date, rsseoHelper::getConfig('global_dateformat')); ?></td>
		</tr>
	<?php } ?>
	</tbody>
</table>
<div style="page-break-after: always;"></div>
<?php } ?>

<?php if ($this->data->no_desc && $this->nodesc) { ?>
<h2><?php echo Text::_('COM_RSSEO_REPORT_NO_DESC'); ?></h2>
<table class="table">
	<thead>
		<tr>
			<th><?php echo Text::_('COM_RSSEO_REPORT_PAGE'); ?></th>
			<th class="center"><?php echo Text::_('COM_RSSEO_REPORT_DATE'); ?></th>
		</tr>
	</thead>
	<tbody>
	<?php foreach ($this->nodesc as $page) { ?>
	<?php $url = rsseoHelper::showURL($page->url, $page->sef); ?>
	<?php $pageurl = ($page->id == 1) ? Uri::root() : $url; ?>
		<tr>
			<td width="70%" style="word-break:break-all; word-wrap:break-word;"><?php echo $pageurl; ?></td>
			<td class="center"><?php echo HTMLHelper::_('date', $page->date, rsseoHelper::getConfig('global_dateformat')); ?></td>
		</tr>
	<?php } ?>
	</tbody>
</table>
<div style="page-break-after: always;"></div>
<?php } ?>

<?php if ($this->data->error_links && $this->elinks) { ?>
<h2><?php echo Text::_('COM_RSSEO_REPORT_ERROR_LINKS'); ?></h2>
<table class="table">
	<thead>
		<tr>
			<th><?php echo Text::_('COM_RSSEO_REPORT_PAGE'); ?></th>
			<th class="center"><?php echo Text::_('COM_RSSEO_REPORT_CODE'); ?></th>
			<th class="center"><?php echo Text::_('COM_RSSEO_REPORT_COUNT'); ?></th>
		</tr>
	</thead>
	<tbody>
	<?php foreach ($this->elinks as $page) { ?>
	<tr>
		<td width="70%" style="word-break:break-all; word-wrap:break-word;"><?php echo $page->url; ?></td>
		<td class="center"><?php echo $page->code; ?></td>
		<td class="center"><?php echo $page->count; ?></td>
	</tr>
	<?php } ?>
	</tbody>
</table>
<div style="page-break-after: always;"></div>
<?php } ?>

<?php if ($this->data->enable_gkeywords && !empty($this->data->keywords) && !empty($this->keywords)) { ?>
<h2><?php echo Text::_('COM_RSSEO_REPORT_GKEYWORDS'); ?></h2>
<table class="table">
	<thead>
		<tr>
			<th><?php echo Text::_('COM_RSSEO_REPORT_GKEYWORD'); ?></th>
			<th class="center"><?php echo Text::_('COM_RSSEO_REPORT_GKEYWORD_PAGES'); ?></th>
			<th class="center"><?php echo Text::_('COM_RSSEO_REPORT_GKEYWORD_IMPRESSIONS'); ?></th>
			<th class="center"><?php echo Text::_('COM_RSSEO_REPORT_GKEYWORD_CLICKS'); ?></th>
			<th class="center"><?php echo Text::_('COM_RSSEO_REPORT_GKEYWORD_AVGPOSITION'); ?></th>
			<th class="center"><?php echo Text::_('COM_RSSEO_REPORT_GKEYWORD_CTR'); ?></th>
		</tr>
	</thead>
	<tbody>
	<?php foreach ($this->keywords as $keyword) { ?>
		<tr>
			<td style="word-break:break-all; word-wrap:break-word;"><?php echo $keyword->name; ?> (<?php echo $keyword->site; ?>)</td>
			<td class="center"><?php echo $keyword->pages; ?></td>
			<td class="center"><?php echo $keyword->impressions; ?></td>
			<td class="center"><?php echo $keyword->clicks; ?></td>
			<td class="center"><?php echo $keyword->avg; ?></td>
			<td class="center"><?php echo $keyword->ctr; ?></td>
		</tr>
	<?php } ?>
	</tbody>
</table>
<?php } ?>