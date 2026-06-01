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

?>

<form action="<?php echo Route::_('index.php?option=com_rsseo&view=crawler');?>" method="post" name="adminForm" id="adminForm">
	<?php echo RSSeoAdapterGrid::sidebar(); ?>
		<?php if ($this->config->crawler_type == 'ajax' && $this->shared) { ?>
			<div class="alert alert-error"><i class="fa fa-info-circle"></i> <?php echo Text::_('COM_RSSEO_CRAWLER_AJAX_ERROR_SHARED'); ?></div>
		<?php } ?>
		<?php if ($this->config->crawler_enable_auto) { ?>
			<div class="alert alert-info" id="rssmessage" style="display:none;"><i class="fa fa-info-circle"></i> <?php echo Text::_('COM_RSSEO_CRAWLER_MESSAGE'); ?></div>
		<?php } ?>
		<table class="table table-striped table-bordered">
			<tr>
				<td width="300"><?php echo Text::_('COM_RSSEO_CRAWLER_URL'); ?></td>
				<td><span id="url"></span></td>
			</tr>
			<tr>
				<td width="300"><?php echo Text::_('COM_RSSEO_CRAWLER_LEVEL'); ?></td>
				<td><span id="level"></span></td>
			</tr>
			<tr>
				<td width="300"><?php echo Text::_('COM_RSSEO_CRAWLER_PAGES_SCANED'); ?></td>
				<td><span id="scaned"></span></td>
			</tr>
			<tr>
				<td width="300"><?php echo Text::_('COM_RSSEO_CRAWLER_PAGES_LEFT'); ?></td>
				<td><span id="remaining"></span></td>
			</tr>
			<tr>
				<td width="300"><?php echo Text::_('COM_RSSEO_CRAWLER_PAGES_TOTAL'); ?></td>
				<td><span id="total"></span></td>
			</tr>
			<tr>
				<td colspan="2">
					<?php if (!$this->offline) { ?>
					<?php if ($this->config->crawler_type == 'loopback') { ?>
					<button type="button" class="btn btn-primary button_start" onclick="RSSeo.doCrawl(1,0);"><?php echo Text::_('COM_RSSEO_CRAWLER_START'); ?></button>
					<button type="button" style="display:none;" class="btn btn-primary button_pause" onclick="RSSeo.pauseCrawl();"><?php echo Text::_('COM_RSSEO_CRAWLER_PAUSE'); ?></button>
					<button type="button" style="display:none;" class="btn btn-primary button_continue" onclick="RSSeo.continueCrawl();"><?php echo Text::_('COM_RSSEO_CRAWLER_CONTINUE'); ?></button>
					<?php } else { ?>
					<button type="button" class="btn btn-primary button_start" onclick="RSSeo.initCrawler('<?php echo Uri::root(); ?>',1);"><?php echo Text::_('COM_RSSEO_CRAWLER_START'); ?></button>
					<button type="button" style="display:none;" class="btn btn-primary button_pause" onclick="RSSeo.pause();"><?php echo Text::_('COM_RSSEO_CRAWLER_PAUSE'); ?></button>
					<button type="button" style="display:none;" class="btn btn-primary button_continue" onclick="RSSeo.continue();"><?php echo Text::_('COM_RSSEO_CRAWLER_CONTINUE'); ?></button>
					<?php } ?>
					<?php } ?>
					
					<?php echo HTMLHelper::image('com_rsseo/loader.gif', '', array('id' => 'rsseoCrawling', 'style' => 'display:none;'), true); ?>
				</td>
			</tr>
		</table>
	</div>

	<?php echo HTMLHelper::_( 'form.token' ); ?>
	<input type="hidden" name="pause" id="pause" value="0" />
	<input type="hidden" name="auto" id="auto" value="<?php echo $this->config->crawler_enable_auto; ?>" />
	<input type="hidden" name="pageid" id="pageid" value="" />
	<input type="hidden" name="pageurl" id="pageurl" value="" />
	<input type="hidden" name="task" value="" />
</form>