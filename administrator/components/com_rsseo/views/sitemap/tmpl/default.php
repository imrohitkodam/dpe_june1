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

<form action="index.php?option=com_rsseo&view=sitemap" method="post" name="adminForm" id="adminForm">
	<?php echo RSSeoAdapterGrid::sidebar(); ?>
		<fieldset class="options-form">
			<legend><?php echo Text::_('COM_RSSEO_XML_SITEMAP'); ?></legend>
			<?php if (!$this->sitemap || !$this->ror) { ?>
			<table class="table table-bordered">
				<?php if (!$this->sitemap) { ?>
				<tr id="sitemap">
					<td>
						<?php echo HTMLHelper::image('com_rsseo/loader.gif', '', array('id' => 'sitemaploading', 'style' => 'display:none;'), true); ?>
						<span id="sitemapspan">
							<?php echo Text::sprintf('COM_RSSEO_CREATE_SITEMAP_ROR_XML','<span class="'.RSSeoAdapterGrid::badge('info').'">sitemap.xml</span>','<a href="javascript:void(0)" class="btn btn-primary btn-sm" onclick="RSSeo.createFile(\'sitemap\');">'.Text::_('COM_RSSEO_GLOBAL_HERE').'</a>'); ?>
						</span>
					</td>
				</tr>
				<?php } ?>
				<?php if (!$this->ror) { ?>
				<tr id="ror">
					<td>
						<?php echo HTMLHelper::image('com_rsseo/loader.gif', '', array('id' => 'rorloading', 'style' => 'display:none;'), true); ?>
						<span id="rorspan">
							<?php echo Text::sprintf('COM_RSSEO_CREATE_SITEMAP_ROR_XML','<span class="'.RSSeoAdapterGrid::badge('info').'">ror.xml</span>','<a href="javascript:void(0)" class="btn btn-primary btn-sm" onclick="RSSeo.createFile(\'ror\');">'.Text::_('COM_RSSEO_GLOBAL_HERE').'</a>'); ?>
						</span>
					</td>
				</tr>
				<?php } ?>
			</table>
			<br />
			<?php } ?>
			
			<?php if (rsseoHelper::getConfig('enable_sitemap_cron')) { ?>
			<div id="sitemapInfo" class="alert alert-danger" style="display:none;"><?php echo Text::_('COM_RSSEO_SITEMAP_MESSAGE'); ?></div>
			<?php } ?>
			
			<?php $disabled = $this->sitemap || $this->ror ? '' : ' disabled="disabled"'; ?>
			<div class="form-horizontal">
				<?php echo $this->form->renderFieldset('general'); ?>
				<div class="control-group">
					<div class="control-label">
						&nbsp;
					</div>
					<div class="controls">
						<a id="btnsitemap" <?php echo $this->sitemap ? '' : 'style="display:none;"'; ?> class="btn btn-info sitemapbutton" href="<?php echo Uri::root(); ?>sitemap.xml" target="_blank">sitemap.xml</a>
						<a id="btnror" <?php echo $this->ror ? '' : 'style="display:none;"'; ?> class="btn btn-info sitemapbutton" href="<?php echo Uri::root(); ?>ror.xml" target="_blank">ror.xml</a>
					</div>
				</div>
				<div class="control-group">
					<div class="control-label">
						&nbsp;
					</div>
					<div class="controls">
						<button id="sitemapbtn" class="btn btn-primary button" type="button" onclick="RSSeo.createSitemap(1, <?php echo (int) rsseoHelper::getConfig('enable_sitemap_cron'); ?>);" <?php echo $disabled; ?>><?php echo Text::_('COM_RSSEO_GENERATE_SITEMAP'); ?></button>
					</div>
				</div>
			</div>
			
			<div class="com-rsseo-progress" id="com-rsseo-import-progress">
				<div class="com-rsseo-bar hasTooltip" title="<?php echo Text::sprintf('COM_RSSEO_SITEMAP_INFO',$this->percent); ?>" id="com-rsseo-bar" style="width: <?php echo $this->percent; ?>%;"><?php echo $this->percent; ?>%</div>
			</div>
			
		</fieldset>
		<br />
		
		<fieldset class="options-form">
			<legend><?php echo Text::_('COM_RSSEO_HTML_SITEMAP'); ?></legend>
			
			<div class="<?php echo RSSeoAdapterGrid::row(); ?>">
				<div class="<?php echo RSSeoAdapterGrid::column(6); ?>">
					<label for="menus" class="rsnofloat"><?php echo Text::_('COM_RSSEO_SITEMAP_HTML_MENUS'); ?></label> <br>
					<?php $menusSelected = unserialize(base64_decode(rsseoHelper::getConfig('sitemap_menus'))); ?>
					<joomla-field-fancy-select class="menus_fancy" placeholder="<?php echo Text::_('JGLOBAL_TYPE_OR_SELECT_SOME_OPTIONS'); ?>">
						<select name="menus[]" id="menus" multiple="multiple" size="5" class="advancedSelect">
							<?php echo HTMLHelper::_('select.options', HTMLHelper::_('rsseomenu.menus'), 'value', 'text',$menusSelected); ?>
						</select>
					</joomla-field-fancy-select>
				</div>
				<div class="<?php echo RSSeoAdapterGrid::column(6); ?>">
					<label for="assetgroups_1" class="rsnofloat"><?php echo Text::_('COM_RSSEO_SITEMAP_HTML_EXCLUDE_MENU_ITEMS'); ?></label> <br>
					<?php $excludeSelected = unserialize(base64_decode(rsseoHelper::getConfig('sitemap_excludes'))); ?>
					<joomla-field-fancy-select class="exclude_fancy" placeholder="<?php echo Text::_('JGLOBAL_TYPE_OR_SELECT_SOME_OPTIONS'); ?>">
						<?php echo HTMLHelper::_('rsseomenu.menuitemlist','exclude[]',$excludeSelected,'multiple="multiple" size="5" class="advancedSelect"'); ?>
					</joomla-field-fancy-select>
				</div>
			</div>
			
			<div class="<?php echo RSSeoAdapterGrid::row(); ?>" style="margin-top: 1rem;">
				<div class="control-group">
					<div class="controls">
						<button class="btn btn-primary" type="button" onclick="Joomla.submitbutton('sitemap.html');"><?php echo Text::_('COM_RSSEO_GENERATE_SITEMAP'); ?></button>
					</div>
				</div>
			</div>
		</fieldset>
	</div>

	<?php echo HTMLHelper::_( 'form.token' ); ?>
	<input type="hidden" name="task" value="" />
</form>