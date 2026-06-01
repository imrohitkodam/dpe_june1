<?php
/**
* @package RSSeo!
* @copyright (C) 2016 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
?>

<form action="<?php echo Route::_('index.php?option=com_rsseo&view=backup');?>" method="post" name="adminForm" id="adminForm" enctype="multipart/form-data">

	<?php echo RSSeoAdapterGrid::sidebar(); ?>
		<?php if (!empty($this->process)) { ?>
		<?php if ($this->process == 'backup') { ?>
		<?php echo $this->backup; ?>
		<?php } else if ($this->process == 'restore') { ?>
		<?php echo $this->restore; ?>
		<div class="text-center">
			<input type="file" size="50" name="rspackage">
			<button type="button" class="btn btn-info button" onclick="Joomla.submitbutton()"><?php echo Text::_('COM_RSSEO_IMPORT'); ?></button>
		</div>
		<?php } ?>
		<?php } else { ?>
		<div class="text-center">
			<h3>
				<a href="<?php echo Route::_('index.php?option=com_rsseo&view=backup&process=backup');?>"><?php echo Text::_('COM_RSSEO_BACKUP'); ?></a> |
				<a href="<?php echo Route::_('index.php?option=com_rsseo&view=backup&process=restore');?>"><?php echo Text::_('COM_RSSEO_RESTORE'); ?></a>
			</h3>
		</div>
		<?php } ?>
	</div>

	<?php echo HTMLHelper::_( 'form.token' ); ?>
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="process" value="<?php echo $this->process; ?>" />
</form>