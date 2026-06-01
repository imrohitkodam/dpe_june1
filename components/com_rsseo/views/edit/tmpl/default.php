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

<a href="javascript:void(0)" id="rsseo-frontend-edit" class="rsseo-frontend-edit" onclick="rsseo_show_modal();">
	<?php echo HTMLHelper::image('com_rsseo/logo.png', 'RSSeo!', array(), true); ?>
</a>

<div id="rsseoEditModal" class="rsseo-modal">
	<div class="rsseo-modal-content">
		<div class="rsseo-modal-header">
			<span class="rsseo-close">&times;</span>
			<h2><?php echo Text::_('RSSEO_EDIT_PAGE_OPTIONS'); ?></h2>
		</div>
		<div class="rsseo-modal-body">
			<form method="POST" action="javascript:void(0)" id="rsseo-frontend-edit-form" name="rsseo-frontend-edit-form" class="form-horizontal">
				<div class="<?php echo RSSeoAdapterGrid::row(); ?>">
					<div class="<?php echo RSSeoAdapterGrid::column(6); ?>">
						<fieldset class="options-form">
							<legend><?php echo Text::_('RSSEO_EDIT_PAGE_METADATA'); ?></legend>
							
							<div class="control-group">
								<div class="control-label">
									<label for="jform_title"><?php echo Text::_('RSSEO_EDIT_PAGE_TITLE'); ?></label>
								</div>
								<div class="controls">
									<input type="text" class="form-control" value="<?php echo $this->escape($this->page->title); ?>" id="jform_title" name="jform[title]">
								</div>
							</div>
								
							<div class="control-group">
								<div class="control-label">
									<label for="jform_keywords"><?php echo Text::_('RSSEO_EDIT_PAGE_KEYWORDS'); ?></label>
								</div>
								<div class="controls">
									<input type="text" class="form-control" value="<?php echo $this->escape($this->page->keywords); ?>" id="jform_keywords" name="jform[keywords]">
								</div>
							</div>
								
							<div class="control-group">
								<div class="control-label">
									<label for="jform_description"><?php echo Text::_('RSSEO_EDIT_PAGE_DESCRIPTION'); ?></label>
								</div>
								<div class="controls">
									<textarea id="jform_description" class="form-control" name="jform[description]"><?php echo $this->escape($this->page->description); ?></textarea>
								</div>
							</div>
							
							<div class="control-group">
								<div class="control-label">
									<label for="jform_customhead"><?php echo Text::_('RSSEO_EDIT_CUSTOM_HEAD_SCRIPT'); ?></label>
								</div>
								<div class="controls">
									<textarea id="jform_customhead" class="form-control" name="jform[customhead]"><?php echo $this->escape($this->page->customhead); ?></textarea>
								</div>
							</div>
						</fieldset>
					</div>
					<div class="<?php echo RSSeoAdapterGrid::column(6); ?>">
						<fieldset class="options-form">
							<legend><?php echo Text::_('RSSEO_EDIT_ROBOTS'); ?></legend>
							
							<div class="control-group">
								<div class="control-label">
									<label for="jform_robots_index"><?php echo Text::_('RSSEO_EDIT_ROBOTS_INDEX'); ?></label>
								</div>
								<div class="controls">
									<select id="jform_robots_index" name="jform[robots][index]" class="input-small custom-select" size="1">
										<?php echo HTMLHelper::_('select.options', $this->robotsOptions, 'value', 'text', $this->page->robots['index']); ?>
									</select>
								</div>
							</div>
							
							<div class="control-group">
								<div class="control-label">
									<label for="jform_robots_archive"><?php echo Text::_('RSSEO_EDIT_ROBOTS_ARCHIVE'); ?></label>
								</div>
								<div class="controls">
									<select id="jform_robots_archive" name="jform[robots][archive]" class="input-small custom-select" size="1">
										<?php echo HTMLHelper::_('select.options', $this->robotsOptions, 'value', 'text', $this->page->robots['archive']); ?>
									</select>
								</div>
							</div>
						
							<div class="control-group">
								<div class="control-label">
									<label for="jform_robots_follow"><?php echo Text::_('RSSEO_EDIT_ROBOTS_FOLLOW'); ?></label>
								</div>
								<div class="controls">
									<select id="jform_robots_follow" name="jform[robots][follow]" class="input-small custom-select" size="1">
										<?php echo HTMLHelper::_('select.options', $this->robotsOptions, 'value', 'text', $this->page->robots['follow']); ?>
									</select>
								</div>
							</div>
							
							<div class="control-group">
								<div class="control-label">
									<label for="jform_robots_snippet"><?php echo Text::_('RSSEO_EDIT_ROBOTS_SNIPPET'); ?></label>
								</div>
								<div class="controls">
									<select id="jform_robots_snippet" name="jform[robots][snippet]" class="input-small custom-select" size="1">
										<?php echo HTMLHelper::_('select.options', $this->robotsOptions, 'value', 'text', $this->page->robots['snippet']); ?>
									</select>
								</div>
							</div>
						</fieldset>
					</div>
				</div>
				
				<div class="<?php echo RSSeoAdapterGrid::row(); ?>">
					<div class="<?php echo RSSeoAdapterGrid::column(12); ?>">
						<fieldset class="options-form">
							<legend><?php echo Text::_('RSSEO_EDIT_CUSTOM_METADATA'); ?></legend>
							
							<button type="button" class="btn btn-info button" onclick="rsseo_new_meta()"><?php echo Text::_('RSSEO_EDIT_ADD_NEW'); ?></button>
							<table class="table table-striped" id="metaDraggable">
								<thead>
									<tr>
										<th><?php echo Text::_('RSSEO_EDIT_METADATA_TYPE'); ?></th>
										<th><?php echo Text::_('RSSEO_EDIT_METADATA_NAME'); ?></th>
										<th align="right"><?php echo Text::_('RSSEO_EDIT_METADATA_CONTENT'); ?></th>
										<th width="1%"></th>
									</tr>
								</thead>
								<tbody id="customMeta">
								<?php if (!empty($this->page->custom)) { ?>
								<?php $i = 1; ?>
								<?php foreach ($this->page->custom as $meta) { ?>
								<tr id="meta00<?php echo $i; ?>">
									<td>
										<select name="jform[custom][type][]" class="custom-select">
											<?php echo HTMLHelper::_('select.options', $this->metatypes, 'value', 'text', $meta['type']);?>
										</select>
									</td>
									<td><input type="text" name="jform[custom][name][]" class="form-control" value="<?php echo $meta['name']; ?>" /></td>
									<td><input type="text" name="jform[custom][content][]" class="form-control" value="<?php echo $meta['content']; ?>" /></td>
									<td><a href="javascript:void(0)" class="btn btn-danger" onclick="rsseo_remove_meta('00<?php echo $i; ?>');"><?php echo Text::_('RSSEO_EDIT_DELETE');?></a></td>
								</tr>
								<?php $i++; ?>
								<?php } ?>
								<?php } ?>
								</tbody>
							</table>
						</fieldset>
					</div>
				</div>
				
				<div id="rsseo-frontend-edit-message" class="alert alert-success" style="display: none;"></div>
				
				<input type="hidden" name="jform[url]" value="<?php echo $this->page->url; ?>" />
				<span id="remtn" style="display:none"><?php echo Text::_('RSSEO_EDIT_METADATA_TYPE_NAME',true); ?></span>
				<span id="remtp" style="display:none"><?php echo Text::_('RSSEO_EDIT_METADATA_TYPE_PROPERTY',true); ?></span>
				<span id="red" style="display:none"><?php echo Text::_('RSSEO_EDIT_DELETE',true); ?></span>
			</form>
		</div>
		<div class="rsseo-modal-footer">
			<button type="button" class="btn btn-primary" onclick="rsseo_save_page('<?php echo addslashes(Uri::root()); ?>');">
				<?php echo HTMLHelper::image('com_rsseo/loader.gif', '', array('id' => 'rsseo-frontend-edit-loader', 'style' => 'display:none;'), true); ?> <?php echo Text::_('RSSEO_EDIT_SAVE'); ?>
			</button> 
			<button type="button" class="btn btn-secondary" onclick="rsseo_close_modal();"><?php echo Text::_('RSSEO_EDIT_CLOSE'); ?></button>
		</div>
	</div>
</div>