<?php
/**
* @package RSSeo!
* @copyright (C) 2016 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;

Text::script('COM_RSSEO_DELETE');
Text::script('COM_RSSEO_METADATA_TYPE_NAME');
Text::script('COM_RSSEO_METADATA_TYPE_PROPERTY');
HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('behavior.keepalive'); ?>

<div id="rsseo-page-loading" style="display: none;">
	<?php echo HTMLHelper::image('com_rsseo/loading.gif', '', array(), true); ?>
</div>
<div id="rsseo-page-overlay" style="display: none;"></div>

<form action="<?php echo Route::_('index.php?option=com_rsseo&view=page&layout=edit&id='.(int) $this->item->id); ?>" method="post" name="adminForm" id="adminForm" autocomplete="off" class="form-validate form-horizontal">
	<div class="<?php echo RSSeoAdapterGrid::row(); ?>">
		<div class="<?php echo RSSeoAdapterGrid::column(6); ?>">
			<?php $url = rsseoHelper::showURL($this->item->url, $this->item->sef); ?>
			<?php $extra = $this->item->id ? '<a target="_blank" href="'.Uri::root().$this->escape($url).'"><i class="fas fa-external-link"></i></a>' : null; ?>
			
			<div class="control-group">
				<div class="control-label">
					<?php echo $this->form->getLabel('url'); ?>
				</div>
				<div class="controls">
					<?php echo RSSeoAdapterGrid::inputGroup($this->form->getInput('url'), null, $extra); ?>
				</div>
			</div>
			
			<?php if ($this->config->enable_sef && ($this->item->id != 1 && $this->item->url != 'index.php')) echo $this->form->renderField('sef'); ?>
			<?php if (rsseoHelper::shortEnabled() && $this->item->id != 1) echo $this->form->renderField('short'); ?>
			
			<div class="control-group">
				<div class="control-label">
					<?php echo $this->form->getLabel('title'); ?>
				</div>
				<div class="controls">
					<?php echo RSSeoAdapterGrid::inputGroup($this->form->getInput('title'), null, '<span id="titleCounter">30</span>'); ?>
				</div>
			</div>
			
			<div class="control-group">
				<div class="control-label">
					<?php echo $this->form->getLabel('keywords'); ?>
				</div>
				<div class="controls">
					<?php echo RSSeoAdapterGrid::inputGroup($this->form->getInput('keywords'), null, '<span id="keywordsCounter">30</span>'); ?>
				</div>
			</div>
			
			<div class="control-group">
				<div class="control-label">
					<?php echo $this->form->getLabel('description'); ?>
				</div>
				<div class="controls">
					<?php echo RSSeoAdapterGrid::inputGroup($this->form->getInput('description'), null, '<span id="descriptionCounter">30</span>'); ?>
				</div>
			</div>
			
			<div class="control-group">
				<div class="control-label">
					<?php echo $this->form->getLabel('canonical'); ?>
				</div>
				<div class="controls">
					<?php echo $this->form->getInput('canonical'); ?>
					<div id="rss_results"><ul id="rsResultsUl"></ul></div>
				</div>
			</div>
			
			<?php echo $this->form->renderField('keywordsdensity'); ?>
			<?php echo $this->form->renderField('frequency'); ?>
			<?php echo $this->form->renderField('priority'); ?>
			<?php echo $this->form->renderField('level'); ?>
			<?php echo $this->form->renderField('original'); ?>
			<?php echo $this->form->renderField('published'); ?>
			<?php if ($this->item->parent) { ?>
			<div class="control-group">
				<div class="control-label">
					<label><?php echo Text::_('COM_RSSEO_PAGE_CRAWLED_FROM'); ?></label>
				</div>
				<div class="controls">
					<a href="<?php echo Uri::root().$this->item->parent; ?>" target="_blank"><?php echo $this->item->parent; ?></a>
				</div>
			</div>
			<?php } ?>
			
			<fieldset class="options-form">
				<legend><?php echo Text::_('COM_RSSEO_PAGE_ROBOTS'); ?></legend>
				<?php foreach($this->form->getGroup('robots') as $field) { ?>
				<?php echo $field->renderField(); ?>
				<?php } ?>
			</fieldset>
			
			<fieldset class="options-form">
				<legend class="hasTooltip" title="<?php echo $this->escape(Text::_('COM_RSSEO_PAGE_CUSTOM_HEAD_SCRIPT_DESC')); ?>"><?php echo Text::_('COM_RSSEO_PAGE_CUSTOM_HEAD_SCRIPT'); ?></legend>
				<?php echo $this->form->getInput('customhead'); ?>
				<p class="muted text-muted"><?php echo Text::_('COM_RSSEO_PAGE_CUSTOM_HEAD_INFO'); ?></p>
			</fieldset>
			
			<fieldset class="options-form">
				<legend class="hasTooltip" title="<?php echo $this->escape(Text::_('COM_RSSEO_PAGE_REMOVE_SCRIPTS_DESC')); ?>"><?php echo Text::_('COM_RSSEO_PAGE_REMOVE_SCRIPTS'); ?></legend>
				<?php echo $this->form->getInput('scripts'); ?>
				<p class="muted text-muted"><?php echo Text::_('COM_RSSEO_PAGE_REMOVE_SCRIPTS_INFO'); ?></p>
			</fieldset>
			
			<fieldset class="options-form">
				<legend class="hasTooltip" title="<?php echo $this->escape(Text::_('COM_RSSEO_PAGE_REMOVE_CSS_DESC')); ?>"><?php echo Text::_('COM_RSSEO_PAGE_REMOVE_CSS'); ?></legend>
				<?php echo $this->form->getInput('css'); ?>
				<p class="muted text-muted"><?php echo Text::_('COM_RSSEO_PAGE_REMOVE_CSS_INFO'); ?></p>
			</fieldset>
		</div>
		
		<?php if ($this->item->id && $this->item->crawled) { ?>
		<div class="<?php echo RSSeoAdapterGrid::column(6); ?>">
			<div class="rsseo-snippet-container hasTooltip" title="<?php echo Text::_('COM_RSSEO_PAGE_SNIPPET_INFO'); ?>">
				<div class="rsseo-snippet-title"><a href="<?php echo Uri::root().$this->escape($url); ?>" target="_blank"><?php echo $this->item->title; ?></a></div>
				<div class="rsseo-snippet-url"><?php echo Uri::root().$url; ?></div>
				<div class="rsseo-snippet-description"><?php echo $this->item->description; ?></div>
			</div>
			
			<table class="table table-striped table-bordered" id="rsseo-page-info">
				<tbody>
					<tr>
						<td class="rsseo-td-info"><?php echo Text::_('COM_RSSEO_PAGE_SEO_GRADE'); ?> </td>
						<td style="vertical-align: middle;">
							<?php $grade = ($this->item->grade <= 0) ? 0 : ceil($this->item->grade); ?>
							<div class="rsj-progress" style="width:100%;">
								<span class="<?php echo $this->item->color; ?>" style="width: <?php echo $grade; ?>%;">
									<span><?php echo $grade; ?>%</span>
								</span>
							</div>
						</td>
					</tr>
					
					<?php if ($this->config->crawler_sef && isset($this->item->params['url_sef'])) { ?>
					<tr>
						<td colspan="2">
							<strong><?php echo Text::_('COM_RSSEO_CHECK_FOR_SEF'); ?></strong>
						</td>
					</tr>
					<tr>
						<td class="rsseo-td-info">
							<a href="https://www.rsjoomla.com/support/documentation/rsseo/troubleshooting/search-friendly-url.html" target="_blank">
								<?php echo HTMLHelper::image('com_rsseo/help.png', '', array(), true); ?>
							</a>
							<?php $url_sef = $this->item->params['url_sef'] == 1; ?>
							<?php echo HTMLHelper::image('com_rsseo/'.($url_sef ? 'ok' : 'notok').'.png', '', array(), true); ?>
						</td>
						<td>
							<?php echo $url_sef ? Text::_('COM_RSSEO_CHECKPAGE_URL_SEF_YES') : Text::_('COM_RSSEO_CHECKPAGE_URL_SEF_NO'); ?>
						</td>
					</tr>
					<?php } ?>
					
					<?php if ($this->config->crawler_title_duplicate && isset($this->item->params['duplicate_title'])) { ?>
					<tr>
						<td colspan="2">
							<strong><?php echo Text::_('COM_RSSEO_CHECK_FOR_DUPLICATE_PAGE_TITLES'); ?></strong>
						</td>
					</tr>
					<tr>
						<td class="rsseo-td-info">
							<a href="https://www.rsjoomla.com/support/documentation/rsseo/troubleshooting/duplicate-title-tags.html" target="_blank">
								<?php echo HTMLHelper::image('com_rsseo/help.png', '', array(), true); ?>
							</a>
							<?php $duplicate_title = $this->item->params['duplicate_title'] > 1; ?>
							<?php echo HTMLHelper::image('com_rsseo/'.(!$duplicate_title ? 'ok' : 'notok').'.png', '', array(), true); ?>
						</td>
						<td>
							<?php if ($duplicate_title) { ?>
							<?php echo Text::sprintf('COM_RSSEO_CHECKPAGE_METATITLE_DUPLICATE_YES', ($this->item->params['duplicate_title'] - 1)); ?>
							<a href="<?php echo Route::_('index.php?option=com_rsseo&view=pages&hash=title|'.md5($this->item->title), false); ?>" target="_blank"><?php echo Text::_('COM_RSSEO_CHECKPAGE_METATITLE_DUPLICATE_YES_VIEW'); ?></a>
							<?php } else { ?>
							<?php echo Text::_('COM_RSSEO_CHECKPAGE_METATITLE_DUPLICATE_NO') ?>
							<?php } ?>
						</td>
					</tr>
					<?php } ?>
					
					<?php if ($this->config->crawler_title_length && isset($this->item->params['title_length'])) { ?>
					<tr>
						<td colspan="2">
							<strong><?php echo Text::_('COM_RSSEO_CHECK_FOR_PAGE_TITLE_LENGTH'); ?></strong>
						</td>
					</tr>
					<tr>
						<td class="rsseo-td-info">
							<a href="https://www.rsjoomla.com/support/documentation/rsseo/troubleshooting/title-tag-length.html" target="_blank">
								<?php echo HTMLHelper::image('com_rsseo/help.png', '', array(), true); ?>
							</a>
							<?php $tlength = $this->item->params['title_length']; ?>
							<?php $titlelength = ($tlength == 0 || $tlength > 70 || $tlength < 10); ?>
							<?php echo HTMLHelper::image('com_rsseo/'.(!$titlelength ? 'ok' : 'notok').'.png', '', array(), true); ?>
						</td>
						<td>
							<?php 
								if ($tlength == 0) 
									echo Text::_('COM_RSSEO_CHECKPAGE_METATITLE_LENGTH_0');
								else if ($tlength < 10)
									echo Text::sprintf('COM_RSSEO_CHECKPAGE_METATITLE_LENGTH_SHORT',$tlength);
								else if ($tlength > 70)
									echo Text::sprintf('COM_RSSEO_CHECKPAGE_METATITLE_LENGTH_LONG',$tlength);
								else echo Text::sprintf('COM_RSSEO_CHECKPAGE_METATITLE_LENGTH_OK',$tlength);
							?>
						</td>
					</tr>
					<?php } ?>
					
					<?php if ($this->config->crawler_description_duplicate && isset($this->item->params['duplicate_desc'])) { ?>
					<tr>
						<td colspan="2">
							<strong><?php echo Text::_('COM_RSSEO_CHECK_FOR_DUPLICATE_PAGE_DESCRIPTION'); ?></strong>
						</td>
					</tr>
					<tr>
						<td class="rsseo-td-info">
							<a href="https://www.rsjoomla.com/support/documentation/rsseo/troubleshooting/duplicate-meta-description.html" target="_blank">
								<?php echo HTMLHelper::image('com_rsseo/help.png', '', array(), true); ?>
							</a>
							<?php $duplicate_desc = $this->item->params['duplicate_desc'] > 1; ?>
							<?php echo HTMLHelper::image('com_rsseo/'.(!$duplicate_desc ? 'ok' : 'notok').'.png', '', array(), true); ?>
						</td>
						<td>
							<?php if ($duplicate_desc) { ?>
							<?php echo Text::sprintf('COM_RSSEO_CHECKPAGE_METADESC_DUPLICATE_YES', ($this->item->params['duplicate_desc'] - 1)); ?>
							<a href="<?php echo Route::_('index.php?option=com_rsseo&view=pages&hash=description|'.md5($this->item->description), false); ?>" target="_blank"><?php echo Text::_('COM_RSSEO_CHECKPAGE_METADESC_DUPLICATE_YES_VIEW'); ?></a>
							<?php } else { ?>
							<?php echo Text::_('COM_RSSEO_CHECKPAGE_METADESC_DUPLICATE_NO') ?>
							<?php } ?>
						</td>
					</tr>
					<?php } ?>
					
					<?php if ($this->config->crawler_description_length && isset($this->item->params['description_length'])) { ?>
					<tr>
						<td colspan="2">
							<strong><?php echo Text::_('COM_RSSEO_CHECK_FOR_PAGE_DESCRIPTION_LENGTH'); ?></strong>
						</td>
					</tr>
					<tr>
						<td class="rsseo-td-info">
							<a href="https://www.rsjoomla.com/support/documentation/rsseo/troubleshooting/-meta-description-tag-length.html" target="_blank">
								<?php echo HTMLHelper::image('com_rsseo/help.png', '', array(), true); ?>
							</a>
							<?php $dlength = $this->item->params['description_length']; ?>
							<?php $descrlength = ($dlength == 0 || $dlength > 300 || $dlength < 70); ?>
							<?php echo HTMLHelper::image('com_rsseo/'.(!$descrlength ? 'ok' : 'notok').'.png', '', array(), true); ?>
						</td>
						<td>
							<?php 
								if ($dlength == 0) 
									echo Text::_('COM_RSSEO_CHECKPAGE_METADESC_LENGTH_0');
								else if ($dlength < 70)
									echo Text::sprintf('COM_RSSEO_CHECKPAGE_METADESC_LENGTH_SHORT',$dlength);
								else if ($dlength > 300)
									echo Text::sprintf('COM_RSSEO_CHECKPAGE_METADESC_LENGTH_LONG',$dlength);
								else echo Text::sprintf('COM_RSSEO_CHECKPAGE_METADESC_LENGTH_OK',$dlength);
							?>
						</td>
					</tr>
					<?php } ?>
					
					<?php if ($this->config->crawler_keywords && isset($this->item->params['keywords'])) { ?>
					<tr>
						<td colspan="2">
							<strong><?php echo Text::_('COM_RSSEO_CHECK_FOR_PAGE_KEYWORDS'); ?></strong>
						</td>
					</tr>
					<tr>
						<td class="rsseo-td-info">
							<a href="https://www.rsjoomla.com/support/documentation/rsseo/troubleshooting/number-of-meta-keywords.html" target="_blank">
								<?php echo HTMLHelper::image('com_rsseo/help.png', '', array(), true); ?>
							</a>
							<?php $keywordsnr = $this->item->params['keywords']; ?>
							<?php $keywords = $keywordsnr > 10; ?>
							<?php echo HTMLHelper::image('com_rsseo/'.(!$keywords ? 'ok' : 'notok').'.png', '', array(), true); ?>
						</td>
						<td>
							<?php 
								if ($keywordsnr == 0)
									echo Text::_('COM_RSSEO_CHECKPAGE_METAKEYWORDS_0');
								else if ($keywordsnr < 10)
									echo Text::sprintf('COM_RSSEO_CHECKPAGE_METAKEYWORDS_SMALL', $keywordsnr);
								else if ($keywordsnr > 10)
									echo Text::sprintf('COM_RSSEO_CHECKPAGE_METAKEYWORDS_BIG', $keywordsnr);
								else echo Text::_('COM_RSSEO_CHECKPAGE_METAKEYWORDS_OK');
							?>
						</td>
					</tr>
					<?php } ?>
					
					<?php if ($this->config->crawler_headings && isset($this->item->params['headings'])) { ?>
					<tr>
						<td colspan="2">
							<strong><?php echo Text::_('COM_RSSEO_CHECK_FOR_PAGE_HEADINGS'); ?></strong>
						</td>
					</tr>
					<tr>
						<td class="rsseo-td-info">
							<a href="https://www.rsjoomla.com/support/documentation/rsseo/troubleshooting/headings.html" target="_blank">
								<?php echo HTMLHelper::image('com_rsseo/help.png', '', array(), true); ?>
							</a>
							<?php $headings = $this->item->params['headings'] <= 0; ?>
							<?php echo HTMLHelper::image('com_rsseo/'.(!$headings ? 'ok' : 'notok').'.png', '', array(), true); ?>
						</td>
						<td>
							<?php echo $headings ? Text::_('COM_RSSEO_CHECKPAGE_HEADINGS_ERROR') : Text::sprintf('COM_RSSEO_CHECKPAGE_HEADINGS_OK',$this->item->params['headings']); ?>
						</td>
					</tr>
					<?php } ?>
					
					<?php if ($this->config->crawler_intext_links && isset($this->item->params['links'])) { ?>
					<tr>
						<td colspan="2">
							<strong><?php echo Text::_('COM_RSSEO_CHECK_FOR_PAGE_IE_LINKS'); ?></strong>
						</td>
					</tr>
					<tr>
						<td class="rsseo-td-info">
							<a href="https://www.rsjoomla.com/support/documentation/rsseo/troubleshooting/number-of-internal-and-external-links-on-a-page.html" target="_blank">
								<?php echo HTMLHelper::image('com_rsseo/help.png', '', array(), true); ?>
							</a>
							<?php $ielinks = $this->item->params['links'] > 100; ?>
							<?php echo HTMLHelper::image('com_rsseo/'.(!$ielinks ? 'ok' : 'notok').'.png', '', array(), true); ?>
						</td>
						<td>
							<?php echo $ielinks ? Text::_('COM_RSSEO_CHECKPAGE_IE_LINKS_ERROR') : Text::_('COM_RSSEO_CHECKPAGE_IE_LINKS_OK'); ?>
						</td>
					</tr>
					<?php } ?>
					
					<?php if ($this->config->crawler_images && isset($this->item->params['images'])) { ?>
					<tr>
						<td colspan="2">
							<strong><?php echo Text::_('COM_RSSEO_CHECK_FOR_PAGE_IMAGES'); ?></strong>
						</td>
					</tr>
					<tr>
						<td class="rsseo-td-info">
							<a href="https://www.rsjoomla.com/support/documentation/rsseo/troubleshooting/images.html" target="_blank">
								<?php echo HTMLHelper::image('com_rsseo/help.png', '', array(), true); ?>
							</a>
							<?php $images = $this->item->params['images'] > 10; ?>
							<?php echo HTMLHelper::image('com_rsseo/'.(!$images ? 'ok' : 'notok').'.png', '', array(), true); ?>
						</td>
						<td>
							<?php echo $images ? Text::sprintf('COM_RSSEO_CHECKPAGE_IMAGES_ERROR',$this->item->params['images']) : Text::sprintf('COM_RSSEO_CHECKPAGE_IMAGES_OK',$this->item->params['images']); ?>
						</td>
					</tr>
					<?php } ?>
					
					<?php if ($this->config->crawler_images_alt && isset($this->item->params['images_wo_alt'])) { ?>
					<tr>
						<td colspan="2">
							<strong><?php echo Text::_('COM_RSSEO_CHECK_FOR_PAGE_IMAGES_W_ALT'); ?></strong>
						</td>
					</tr>
					<tr>
						<td class="rsseo-td-info">
							<a href="https://www.rsjoomla.com/support/documentation/rsseo/troubleshooting/images-with-descriptive-names.html" target="_blank">
								<?php echo HTMLHelper::image('com_rsseo/help.png', '', array(), true); ?>
							</a>
							<?php $images_alt = $this->item->params['images_wo_alt'] > 0; ?>
							<?php echo HTMLHelper::image('com_rsseo/'.(!$images_alt ? 'ok' : 'notok').'.png', '', array(), true); ?>
						</td>
						<td>
							<?php echo $images_alt ? Text::sprintf('COM_RSSEO_CHECKPAGE_IMAGES_WO_ALT_ERROR',$this->item->params['images_wo_alt']) : Text::_('COM_RSSEO_CHECKPAGE_IMAGES_WO_ALT_OK'); ?>
						</td>
					</tr>
					<?php } ?>
					
					<?php if ($this->config->crawler_images_hw && isset($this->item->params['images_wo_hw'])) { ?>
					<tr>
						<td colspan="2">
							<strong><?php echo Text::_('COM_RSSEO_CHECK_FOR_PAGE_IMAGES_W_HW'); ?></strong>
						</td>
					</tr>
					<tr>
						<td class="rsseo-td-info">
							<a href="https://www.rsjoomla.com/support/documentation/rsseo/troubleshooting/images-without-the-height-or-width-attribute.html" target="_blank">
								<?php echo HTMLHelper::image('com_rsseo/help.png', '', array(), true); ?>
							</a>
							<?php $images_hw = $this->item->params['images_wo_hw'] > 0; ?>
							<?php echo HTMLHelper::image('com_rsseo/'.(!$images_hw ? 'ok' : 'notok').'.png', '', array(), true); ?>
						</td>
						<td>
							<?php echo $images_hw ? Text::sprintf('COM_RSSEO_CHECKPAGE_IMAGES_WO_HW_ERROR',$this->item->params['images_wo_hw']) : Text::_('COM_RSSEO_CHECKPAGE_IMAGES_WO_HW_OK'); ?>
						</td>
					</tr>
					<?php } ?>
					
					<tr>
						<td class="rsseo-td-info">
							<a href="https://www.rsjoomla.com/support/documentation/rsseo/troubleshooting/images-with-descriptive-names.html" target="_blank">
								<?php echo HTMLHelper::image('com_rsseo/help.png', '', array(), true); ?>
							</a>
						</td>
						<td>
							<?php echo Text::_('COM_RSSEO_CHECKPAGE_IMAGES_NAMES_DESC'); ?>
						</td>
					</tr>
				</tbody>
			</table>
			
			<fieldset class="options-form">
				<legend><a href="<?php echo Route::_('index.php?option=com_rsseo&view=page&layout=links&id='.$this->item->id,false); ?>"><?php echo Text::_('COM_RSSEO_PAGE_INT_EXT_LINKS'); ?></a></legend>
				<table class="table table-striped">
					<tr>
						<td><?php echo Text::_('COM_RSSEO_PAGE_INT_LINKS'); ?></td>
						<td class="center"><?php echo $this->item->internal; ?></td>
					</tr>
					<tr>
						<td><?php echo Text::_('COM_RSSEO_PAGE_EXT_LINKS'); ?></td>
						<td class="center"><?php echo $this->item->external; ?></td>
					</tr>
				</table>
			</fieldset>
			
			
			<?php if ($this->config->crawler_images_alt && !empty($this->item->imagesnoalt)) { ?>
			<fieldset class="options-form">
				<legend><?php echo Text::_('COM_RSSEO_PAGE_IMAGES_WITHOUT_ALT'); ?> </legend>
				<table class="table table-striped">
					<?php foreach ($this->item->imagesnoalt as $image) { ?>
					<?php if (empty($image)) continue; ?>
					<tr>
						<td><?php echo $image; ?></td>
					</tr>
					<?php } ?>
				</table>
			</fieldset>
			<?php } ?>
			
			<?php if ($this->config->crawler_images_hw && !empty($this->item->imagesnowh)) { ?>
			<fieldset class="options-form">
				<legend><?php echo Text::_('COM_RSSEO_PAGE_IMAGES_WITHOUT_WH'); ?> </legend>
				<table class="table table-striped">
					<?php foreach ($this->item->imagesnowh as $image) { ?>
					<?php if (empty($image)) continue; ?>
					<tr>
						<td><?php echo $image; ?></td>
					</tr>
					<?php } ?>
				</table>
			</fieldset>
			<?php } ?>
			
			<?php if ($this->config->keyword_density_enable && !empty($this->item->densityparams)) { ?>
			<fieldset class="options-form">
				<legend><?php echo Text::_('COM_RSSEO_PAGE_KEYWORD_DENSITY'); ?> </legend>
				<table class="table table-striped">
					<?php foreach ($this->item->densityparams as $keyword => $value) { ?>
					<tr>
						<td style="vertical-align:middle;" width="6%">
							<a href="https://www.rsjoomla.com/support/documentation/rsseo/troubleshooting/keyword-density-tool.html" target="_blank">
								<?php echo HTMLHelper::image('com_rsseo/help.png', '', array(), true); ?>
							</a>
						</td>
						<td><?php echo $keyword; ?></td>
						<td><?php echo $value; ?></td>
					</tr>
					<?php } ?>
				</table>
			</fieldset>
			<?php } ?>
			
			<?php if ($this->item->id) { ?>
			<fieldset class="options-form">
				<legend>
					<a href="javascript:void(0)" onclick="RSSeo.pageLoadingTime(<?php echo $this->item->id; ?>);">
						<?php echo Text::_('COM_RSSEO_PAGE_CHECK_LOAD_SIZE'); ?> 
					</a>
				</legend>
				<?php echo HTMLHelper::image('com_rsseo/loader.gif', '', array('id' => 'loader', 'style' => 'display:none;vertical-align:bottom;'), true); ?>
				<table class="table table-striped">
					<tbody>
						<tr id="pageloadtr" style="display:none;">
							<td style="vertical-align:middle;" width="6%">
								<a href="https://www.rsjoomla.com/support/documentation/rsseo/troubleshooting/page-loading-time.html" target="_blank">
									<?php echo HTMLHelper::image('com_rsseo/help.png', '', array(), true); ?>
								</a>
							</td>
							<td><?php echo Text::_('COM_RSSEO_CHECKPAGE_TOTAL_PAGE_DESCR'); ?></td>
							<td><span id="pageload"></span></td>
						</tr>
						<tr id="pagesizetr" style="display:none;">
							<td style="vertical-align:middle;" width="6%">
								<a href="https://www.rsjoomla.com/support/documentation/rsseo/troubleshooting/page-size.html" target="_blank">
									<?php echo HTMLHelper::image('com_rsseo/help.png', '', array(), true); ?>
								</a>
							</td>
							<td><?php echo Text::_('COM_RSSEO_CHECKPAGE_PAGE_SIZE'); ?></td>
							<td><span id="pagesize"></span></td>
						</tr>
					</tbody>
				</table>
			</fieldset>
			<?php } ?>
		</div>
		<?php } ?>
	</div>

    <div class="<?php echo RSSeoAdapterGrid::row(); ?>">
        <div class="<?php echo RSSeoAdapterGrid::column(12); ?>">
            <fieldset class="options-form">
                <legend><?php echo Text::_('COM_RSSEO_CUSTOM_METADATA'); ?></legend>
                <button type="button" class="btn btn-info button mb-2" onclick="RSSeo.addCustomMetadata()"><?php echo Text::_('COM_RSSEO_ADD_NEW'); ?></button>
                <div class="table-responsive">
                    <table class="table table-striped" id="metaDraggable">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 20%"><?php echo Text::_('COM_RSSEO_METADATA_TYPE'); ?></th>
                                <th class="text-center" style="width: 35%"><?php echo Text::_('COM_RSSEO_METADATA_NAME'); ?></th>
                                <th class="text-center" style="width: 35%"><?php echo Text::_('COM_RSSEO_METADATA_CONTENT'); ?></th>
                                <th class="text-center" style="width: 10%">&nbsp;</th>
                            </tr>
                        </thead>
                        <tbody id="customMeta">
                        <?php if (!empty($this->item->custom)) { ?>
                            <?php $i = 1; ?>
                            <?php foreach ($this->item->custom as $meta) { ?>
                                <tr id="meta00<?php echo $i; ?>">
                                    <td class="text-center">
                                        <select name="jform[custom][type][]" class="custom-select">
                                            <?php echo HTMLHelper::_('select.options', $this->get('MetaTypes'), 'value', 'text', $meta['type']);?>
                                        </select>
                                    </td>
                                    <td class="text-center"><input type="text" name="jform[custom][name][]" class="form-control" value="<?php echo $meta['name']; ?>" /></td>
                                    <td class="text-center"><input type="text" name="jform[custom][content][]" class="form-control" value="<?php echo $meta['content']; ?>" /></td>
                                    <td class="text-center"><a href="javascript:void(0)" class="btn btn-danger" onclick="RSSeo.removeCustomMetadata('00<?php echo $i; ?>');"><?php echo Text::_('COM_RSSEO_DELETE');?></a></td>
                                </tr>
                                <?php $i++; ?>
                            <?php } ?>
                        <?php } ?>
                        </tbody>
                    </table>
                </div>
            </fieldset>

	        <?php if ($this->item->id && $this->item->crawled) { ?>
            <fieldset class="options-form">
                <legend><?php echo Text::_('COM_RSSEO_PAGE_BROKEN_LINKS'); ?></legend>

                <?php if ($this->config->crawler_type == 'ajax') { ?>
                <button id="brokenButton" type="button" class="btn btn-info button mb-2" onclick="RSSeo.broken('<?php echo addslashes(Uri::root().$this->item->url); ?>', <?php echo $this->item->id; ?>)">
                <?php } else { ?>
                <button id="brokenButton" type="button" class="btn btn-info button mb-2" onclick="RSSeo.checkBroken(<?php echo $this->item->id; ?>,0)">
                <?php } ?>

                <?php echo Text::_('COM_RSSEO_CHECK'); ?> <?php echo HTMLHelper::image('com_rsseo/loader.gif', '', array('id' => 'loader_links', 'style' => 'display:none;'), true); ?></button>

                <div class="rsj-progress" style="display: none; width: 100%; margin-top:10px;" id="brokenProgress">
                    <span id="brokenBar" style="width: 0%;" class="green">
                        <span id="brokenPercentage">0%</span>
                    </span>
                </div>

                <table class="table table-striped">
                    <thead>
                    <tr>
                        <th><?php echo Text::_('COM_RSSEO_PAGE_BROKEN_URL'); ?></th>
                        <th class="center"><?php echo Text::_('COM_RSSEO_PAGE_BROKEN_URL_CODE'); ?></th>
                    </tr>
                    </thead>
                    <tbody id="brokenLinks">
                    <?php foreach ($this->broken as $i => $brokenLink) { ?>
                        <tr class="row<?php echo $i % 2; ?>">
                            <td><?php echo $brokenLink->url; ?></td>
                            <td class="center"><b><?php echo $brokenLink->code; ?></b> <?php echo rsseoHelper::getResponseMessage($brokenLink->code); ?></td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>

            </fieldset>
	        <?php } ?>
        </div>
    </div>

	<?php echo HTMLHelper::_('form.token'); ?>
	<?php echo $this->form->getInput('id')."\n"; ?>
	<input type="hidden" name="id" value="<?php echo $this->form->getValue('id'); ?>" />
	<input type="hidden" name="task" value="" />
</form>