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

Text::script('COM_RSSEO_ANALYTICS_SELECT_ACCOUNT'); ?>

<form action="<?php echo Route::_('index.php?option=com_rsseo&view=analytics');?>" method="post" name="adminForm" id="adminForm">
	<?php echo RSSeoAdapterGrid::sidebar(); ?>
	
	<div class="text-right rsseo-filter-bar">
		<div class="btn-group">
			<select id="profile" class="custom-select" size="1" name="profile">
				<?php echo HTMLHelper::_('select.options', $this->profiles, 'value', 'text', $this->selected); ?>
			</select>
		</div>
		
		<div style="display:inline-block">
			<?php echo HTMLHelper::_('calendar', $this->rsstart, 'rsstart', 'rsstart', '%Y-%m-%d' , array('class' => 'input-small')); ?>
		</div>
		
		<div style="display:inline-block">
			<?php echo HTMLHelper::_('calendar', $this->rsend, 'rsend', 'rsend', '%Y-%m-%d' , array('class' => 'input-small')); ?>
		</div>
		
		<div class="btn-group">
			<button class="btn btn-info button" type="button" onclick="RSSeo.updateAnalytics();"><?php echo Text::_('COM_RSSEO_GLOBAL_UPDATE'); ?></button>
		</div>
	</div>
	
	<br>
	
	<div id="rsseo-analytics">
		<?php $this->tabs->addTitle('COM_RSSEO_GA_VISITORS_LBL','ga-visitors'); ?>
		<?php $this->tabs->addTitle('COM_RSSEO_GA_TRAFFIC_LBL','ga-traffic'); ?>
		<?php $this->tabs->addTitle('COM_RSSEO_GA_CONTENT_LBL','ga-content'); ?>
		<?php $this->tabs->addContent($this->loadTemplate('gavisitors')); ?>
		<?php $this->tabs->addContent($this->loadTemplate('gatraffic')); ?>
		<?php $this->tabs->addContent($this->loadTemplate('gacontent')); ?>
		<?php echo $this->tabs->render(); ?>
	</div>
	
	<?php echo HTMLHelper::_( 'form.token' ); ?>
	<input type="hidden" name="task" value="" />
</form>