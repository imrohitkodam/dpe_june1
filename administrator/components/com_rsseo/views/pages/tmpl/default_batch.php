<?php
/**
* @package RSSeo!
* @copyright (C) 2016 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Language\Text;

?>

<div class="container-fluid">
	<div class="<?php echo RSSeoAdapterGrid::row(); ?> form-horizontal">
		<div class="<?php echo RSSeoAdapterGrid::column(6); ?>">
			<fieldset class="options-form">
				<legend><?php echo Text::_('COM_RSSEO_GENERAL'); ?></legend>
				<?php echo $this->batch->renderField('published'); ?>
				<?php echo $this->batch->renderField('insitemap'); ?>
				<?php echo $this->batch->renderField('keywords'); ?>
				<?php echo $this->batch->renderField('description'); ?>
				<?php echo $this->batch->renderField('canonical'); ?>
				<?php echo $this->batch->renderField('frequency'); ?>
				<?php echo $this->batch->renderField('priority'); ?>
			</fieldset>
			
			<fieldset class="options-form">
				<legend><?php echo Text::_('COM_RSSEO_PAGE_ROBOTS'); ?></legend>
				<div class="<?php echo RSSeoAdapterGrid::row(); ?>">
					<div class="<?php echo RSSeoAdapterGrid::column(12); ?>">
						<?php echo $this->batch->renderField('index','robots'); ?>
						<?php echo $this->batch->renderField('follow','robots'); ?>
						<?php echo $this->batch->renderField('archive','robots'); ?>
						<?php echo $this->batch->renderField('snippet','robots'); ?>
					</div>
				</div>
			</fieldset>
		</div>
		
		<div class="<?php echo RSSeoAdapterGrid::column(6); ?>">
			<fieldset class="options-form">
				<legend><?php echo Text::_('COM_RSSEO_CONFIGURATION_CUSTOM_HEAD_SCRIPTS'); ?></legend>
				<?php echo $this->batch->renderField('customhead'); ?>
			</fieldset class="options-form">
			<fieldset class="options-form">
				<legend><?php echo Text::_('COM_RSSEO_PAGE_REMOVE_SCRIPTS'); ?></legend>
				<?php echo $this->batch->renderField('scripts'); ?>
			</fieldset>
			<fieldset class="options-form">
				<legend><?php echo Text::_('COM_RSSEO_PAGE_REMOVE_CSS'); ?></legend>
				<?php echo $this->batch->renderField('css'); ?>
			</fieldset>
		</div>
	</div>
</div>